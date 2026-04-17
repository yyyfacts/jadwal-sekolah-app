import sys
import json
import time  
import math
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
    
    # Sortir balok durasi besar agar masuk duluan
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    mapel_busy = {}
    for m in data.get('mapel_constraints', []):
        mapel_busy[m['id']] = m['waktu_kosong']

    # ==========================================
    # 1. BACA DINAMIS DARI MASTER HARI LARAVEL
    # ==========================================
    hari_aktif_data = data.get('hari_aktif', [])
    
    hari_list = [h['nama'] for h in hari_aktif_data]
    
    # KITA PISAHKAN PENGGARIS (SLOT) DAN KAPASITAS BELAJAR (SKS)
    # Gunakan .get() agar aman jika Laravel belum update field-nya
    hari_max_slot = {h['nama']: int(h.get('max_slot', h.get('max_jam'))) for h in hari_aktif_data}
    hari_kapasitas = {h['nama']: int(h.get('kapasitas_belajar', h.get('max_jam'))) for h in hari_aktif_data}

    # Pisahkan hari terakhir dan hari reguler
    hari_reguler = hari_list[:-1] if len(hari_list) > 1 else []
    hari_terakhir = hari_list[-1] if hari_list else None
    
    # Kapasitas matematika SKS murni untuk Senin-Kamis
    kapasitas_reguler = sum(hari_kapasitas[h] for h in hari_reguler)

    guru_hari_map = {}
    for g in gurus:
        allowed_days = g.get('hari_mengajar', [])
        if not allowed_days:
            allowed_days = hari_list
        guru_hari_map[g['id']] = allowed_days

    kelas_total_jp = {k['id']: 0 for k in kelass}
    for t in raw_assignments:
        kelas_total_jp[t['kelas_id']] += int(t['jumlah_jam'])

    # Fungsi 1: Panjang Penggaris (Slot)
    def get_max_slot_hari(hari):
        return hari_max_slot[hari]

    # Fungsi 2: Hitung Sisa SKS Matematika
    def get_target_belajar_kelas(kelas_id, hari):
        if hari == hari_terakhir:
            sisa = kelas_total_jp[kelas_id] - kapasitas_reguler
            sisa = max(0, sisa)
            return min(sisa, hari_kapasitas[hari]) 
        return hari_kapasitas[hari]

    # ==========================================
    # 2. MEMBANGUN MODEL AI
    # ==========================================
    model = cp_model.CpModel()

    starts, presences = {}, {}
    all_start_vars = [] 

    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    
    durasi_per_kelas_harian = {k['id']: {h: [] for h in hari_list} for k in kelass}
    durasi_per_guru_harian = {g['id']: {h: [] for h in hari_list} for g in gurus}
    
    tasks_per_mapel_group = {} 
    tasks_metadata = []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id, g_id, k_id = t['id'], t['guru_id'], t['kelas_id']
        m_id = t.get('mapel_id')
        nama_mapel = str(t.get('nama_mapel', '')).upper().replace(' ', '')
        
        tasks_metadata.append({'id': t_id})
        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        tasks_per_mapel_group.setdefault(group_key, []).append(t_id)

        possible_days = [] 

        for h in hari_list:
            if h not in guru_hari_map[g_id]: continue 

            # BATAS WADAH (PENGGARIS) ADALAH TOTAL SLOT (Misal 13)
            batas_slot = get_max_slot_hari(h)
            
            # Khusus PJOK, penggarisnya dipotong jadi 8
            if 'PJOK' in nama_mapel or 'PENJAS' in nama_mapel or 'OLAHRAGA' in nama_mapel:
                batas_slot = min(batas_slot, 8)

            if durasi > batas_slot: continue

            max_start = batas_slot - durasi + 1
            if max_start < 1: continue
            
            start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_slot + 1, f'end_{t_id}_{h}')
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
            durasi_per_guru_harian[g_id][h].append(is_present * durasi)

            if m_id in mapel_busy:
                for blocked in mapel_busy[m_id]:
                    if blocked['hari'] == h:
                        jam_blok = int(blocked['jam'])
                        if jam_blok <= batas_slot:
                            blocked_interval = model.NewIntervalVar(jam_blok, 1, jam_blok + 1, f'block_{m_id}_{h}')
                            model.AddNoOverlap([interval_var, blocked_interval])

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({"status": "INFEASIBLE", "message": f"Bentrok! Mapel ID {t_id} tidak muat di jadwal."}))
            return

    # ==========================================
    # 3. KENDALA WAJIB (MATEMATIKA PENGURANGAN)
    # ==========================================
    for k in kelass:
        k_id = k['id']
        for h in hari_list:
            beban_harian = durasi_per_kelas_harian[k_id][h]
            if not beban_harian: continue
            
            # Targetkan BEBAN BELAJAR murni (Misal 10), bukan panjang penggaris (13)
            target_belajar = get_target_belajar_kelas(k_id, h)
            model.Add(sum(beban_harian) == target_belajar)

    # ==========================================
    # 4. PEMERATAAN GURU
    # ==========================================
    for g in gurus:
        g_id = g['id']
        total_sks_guru = sum(int(t['jumlah_jam']) for t in raw_assignments if t['guru_id'] == g_id)
        hari_aktif_guru = len(guru_hari_map[g_id])
        if hari_aktif_guru == 0: hari_aktif_guru = len(hari_list)

        batas_harian_guru = int(math.ceil(total_sks_guru / hari_aktif_guru)) + 1 
        
        for h in hari_list:
            beban_harian_guru = durasi_per_guru_harian[g_id][h]
            if beban_harian_guru:
                model.Add(sum(beban_harian_guru) <= batas_harian_guru)

    # ==========================================
    # 5. KENDALA DASAR (TIDAK BOLEH BENTROK)
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
    # 6. EKSEKUSI (ANTI ERROR 502 RENDER)
    # ==========================================
    if all_start_vars:
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    solver = cp_model.CpSolver()
    
    # PEKERJA 1 BIAR RAM RENDER AMAN:
    solver.parameters.num_search_workers = 8  
    # WAKTU 90 DETIK BIAR GAK TIMEOUT:
    solver.parameters.max_time_in_seconds = 300
    
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
            "message": f"BERHASIL! Jadwal selesai dalam {waktu_komputasi:.2f} detik. Matematika Penggaris vs SKS Bekerja!"
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "message": f"Gagal menyusun (Waktu: {waktu_komputasi:.2f} detik). Cek kembali aturan Matematika lu."
        }))

if __name__ == '__main__':
    main()