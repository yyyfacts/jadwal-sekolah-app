import sys
import json
import time  
from ortools.sat.python import cp_model

def main():
    start_time = time.time()  

    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "message": "JSON path required"}))
        return

    json_path = sys.argv[1]
    try:
        with open(json_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": str(e)}))
        return

    raw_assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    mapel_busy = {}
    for m in data.get('mapel_constraints', []):
        mapel_busy[m['id']] = m['waktu_kosong']

    # ==========================================
    # LOGIKA BARU: BACA DINAMIS DARI MASTER HARI
    # ==========================================
    hari_aktif_data = data.get('hari_aktif', [])
    
    # Ambil nama hari dan batas maksimal jamnya dari Laravel
    hari_list = [h['nama'] for h in hari_aktif_data]
    hari_max_jam = {h['nama']: int(h['max_jam']) for h in hari_aktif_data}

    # Pisahkan hari terakhir (Jumat) dan hari-hari sebelumnya (Senin-Kamis)
    hari_reguler = hari_list[:-1] if len(hari_list) > 1 else []
    hari_terakhir = hari_list[-1] if hari_list else None

    # Hitung total kapasitas hari reguler (Misal Senin-Kamis masing-masing 10 = 40)
    kapasitas_reguler = sum(hari_max_jam[h] for h in hari_reguler)

    guru_hari_map = {}
    for g in gurus:
        allowed_days = g.get('hari_mengajar', [])
        if not allowed_days:
            allowed_days = hari_list
        guru_hari_map[g['id']] = allowed_days

    # Hitung Total JP tiap kelas
    kelas_total_jp = {k['id']: 0 for k in kelass}
    for t in raw_assignments:
        kelas_total_jp[t['kelas_id']] += int(t['jumlah_jam'])

    # Fungsi Get Max Jam yang super pintar dan dinamis
    def get_max_jam(kelas_id, hari):
        if hari == hari_terakhir:
            # Cari tahu sisa SKS yang harus ditampung di hari terakhir
            sisa = kelas_total_jp[kelas_id] - kapasitas_reguler
            sisa = max(0, sisa)
            # Batasi slot sesuai sisa SKS, tapi jangan sampai melebihi limit asli Master Hari
            return min(sisa, hari_max_jam[hari]) 
        
        # Untuk hari reguler (Senin-Kamis), pakai batas mutlak dari Master Hari
        return hari_max_jam[hari]

    # ==========================================
    # 2. MEMBANGUN MODEL
    # ==========================================
    model = cp_model.CpModel()

    starts, presences = {}, {}
    all_start_vars = [] 

    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    durasi_per_kelas_harian = {k['id']: {h: [] for h in hari_list} for k in kelass}
    
    tasks_per_mapel_group = {} 
    tasks_metadata = []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id, g_id, k_id = t['id'], t['guru_id'], t['kelas_id']
        m_id = t.get('mapel_id')
        
        tasks_metadata.append({'id': t_id})

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        tasks_per_mapel_group.setdefault(group_key, []).append(t_id)

        possible_days = [] 

        for h in hari_list:
            if h not in guru_hari_map[g_id]: continue 

            batas_jam = get_max_jam(k_id, h)
            if durasi > batas_jam: continue

            max_start = batas_jam - durasi + 1
            if max_start < 1: continue
            
            start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_jam + 1, f'end_{t_id}_{h}')
            is_present = model.NewBoolVar(f'present_{t_id}_{h}')
            
            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'interval_{t_id}_{h}'
            )

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            durasi_per_kelas_harian[k_id][h].append(is_present * durasi)

            if m_id in mapel_busy:
                for blocked in mapel_busy[m_id]:
                    if blocked['hari'] == h:
                        jam_blok = int(blocked['jam'])
                        if jam_blok <= batas_jam:
                            blocked_interval = model.NewIntervalVar(jam_blok, 1, jam_blok + 1, f'block_{m_id}_{h}')
                            model.AddNoOverlap([interval_var, blocked_interval])

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({"status": "INFEASIBLE", "message": f"Bentrok fatal! Mapel ID {t_id} tidak punya pilihan hari."}))
            return

    # ==========================================
    # 3. KENDALA HARGA MATI (SESUAI MASTER HARI)
    # ==========================================
    for k in kelass:
        k_id = k['id']
        sisa_hari_terakhir = max(0, kelas_total_jp[k_id] - kapasitas_reguler)
        # Target jam Jumat = sisa jam, nggak boleh lebih dari limit Master Hari
        target_sisa = min(sisa_hari_terakhir, hari_max_jam[hari_terakhir]) if hari_terakhir else 0

        for h in hari_list:
            beban_harian = durasi_per_kelas_harian[k_id][h]
            if not beban_harian: continue
            
            if h in hari_reguler:
                # WAJIB SAMA DENGAN LIMIT MASTER HARI (Nggak peduli mau 10, 9, atau 8)
                model.Add(sum(beban_harian) == hari_max_jam[h])
            elif h == hari_terakhir:
                # WAJIB SAMA DENGAN SISA JP
                model.Add(sum(beban_harian) == target_sisa)

    # ==========================================
    # 4. KENDALA DASAR (TIDAK BOLEH BENTROK)
    # ==========================================
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    for g_id in intervals_per_guru:
        for h in hari_list:
            intervals = intervals_per_guru[g_id][h]
            waktu_kosong = next((g.get('waktu_kosong', []) for g in gurus if g['id'] == g_id), [])
            for wk in waktu_kosong:
                if wk['hari'] == h:
                    jam_libur = int(wk['jam'])
                    dummy = model.NewIntervalVar(jam_libur, 1, jam_libur+1, f'libur_guru_{g_id}_{h}_{jam_libur}')
                    intervals.append(dummy)
            if intervals:
                model.AddNoOverlap(intervals)

    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in hari_list:
                daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_presence:
                    model.AddAtMostOne(daily_presence)

    # ==========================================
    # 5. EKSEKUSI PENCARIAN
    # ==========================================
    if all_start_vars:
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 200 
    solver.parameters.num_search_workers = 1    
    
    status = solver.Solve(model)
    waktu_komputasi = time.time() - start_time  

    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        final_solution = []
        for t in tasks_metadata:
            t_id = t['id']
            for h in hari_list:
                if (t_id, h) in presences and solver.Value(presences[(t_id, h)]) == 1:
                    final_solution.append({
                        'id': t_id,
                        'hari': h,
                        'jam': solver.Value(starts[(t_id, h)])
                    })
                    break
        
        print(json.dumps({
            "status": "OPTIMAL",
            "solution": final_solution,
            "message": f"MANTAP! Jadwal berhasil dibikin RATA KIRI dalam {waktu_komputasi:.2f} detik sesuai settingan Master Hari!"
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "message": f"Gagal menyusun (Waktu: {waktu_komputasi:.2f} detik). Jadwal mentok."
        }))

if __name__ == '__main__':
    main()