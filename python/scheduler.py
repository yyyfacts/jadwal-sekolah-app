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
    
    # Sortir balok durasi besar agar masuk duluan
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    # Ambil data mapel yang di-lock manual (waktu sibuk)
    mapel_busy = {}
    for m in data.get('mapel_constraints', []):
        mapel_busy[m['id']] = m['waktu_kosong']

    hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']

    # ==========================================
    # LOGIKA BARU: BACA TOTAL JAM BELAJAR DARI MASTER HARI LARAVEL!
    # ==========================================
    # ==========================================
    # 1. BACA DINAMIS DARI MASTER HARI (UPDATE)
    # ==========================================
    hari_aktif_data = data.get('hari_aktif', [])
    
    # Kita pisah antara 'Penggaris' (Slot) dan 'Isi' (Kapasitas Belajar)
    # Gunakan .get() biar kalau data lama masih ada, nggak langsung error
    hari_max_slot = {h['nama']: int(h.get('max_slot', h.get('max_jam', 10))) for h in hari_aktif_data}
    hari_kapasitas = {h['nama']: int(h.get('kapasitas_belajar', h.get('max_jam', 10))) for h in hari_aktif_data}

    hari_reguler = hari_list[:-1] 
    hari_terakhir = hari_list[-1] 
    
    # Hitung kapasitas belajar murni (Senin-Kamis)
    kapasitas_senin_kamis = sum(hari_kapasitas.get(h, 10) for h in hari_reguler)
    guru_hari_map = {}
    for g in gurus:
        allowed_days = g.get('hari_mengajar', [])
        if not allowed_days:
            allowed_days = hari_list
        guru_hari_map[g['id']] = allowed_days

    # ==========================================
    # MATEMATIKA MENGHITUNG SISA SKS JUMAT
    # ==========================================
    kelas_total_jp = {k['id']: 0 for k in kelass}
    for t in raw_assignments:
        kelas_total_jp[t['kelas_id']] += int(t['jumlah_jam'])

    sisa_jumat_kelas = {}
    for k in kelass:
        k_id = k['id']
        # MATEMATIKA: Sisa = Total SKS Kelas dikurangi kapasitas Senin-Kamis
        sisa = kelas_total_jp[k_id] - kapasitas_senin_kamis
        
        # Sisa nggak boleh minus, dan NGGAK BOLEH lebih dari jam belajar Jumat di Master Hari
        limit_jumat_di_master = jam_belajar_master.get(hari_terakhir, 8)
        sisa_jumat_kelas[k_id] = max(0, min(limit_jumat_di_master, sisa))

    # Aturan Kapasitas Harian (Dinamis banget ngikutin Laravel)
    def get_max_jam(kelas_id, hari):
        if hari == hari_terakhir:
            return sisa_jumat_kelas[kelas_id] # Jumat dipotong sesuai sisa!
        
        # Kalau Senin-Kamis, ambil kapasitas dari Master Hari (Misal 10)
        return jam_belajar_master.get(hari, 10) 

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

            # KUNCINYA: Batas wadah fisik adalah MAX_SLOT (Misal 13)
            batas_wadah = hari_max_slot.get(h, 10)
            
            # Tetap jaga aturan PJOK lu (Maksimal jam ke-8)
            nama_mapel = str(t.get('nama_mapel', '')).upper()
            if 'PJOK' in nama_mapel or 'PENJAS' in nama_mapel:
                batas_wadah = min(batas_wadah, 8)

            if durasi > batas_wadah: continue

            max_start = batas_wadah - durasi + 1
            if max_start < 1: continue
            
            # Start dan End sekarang mengacu ke Batas Wadah (Penggaris panjang)
            start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_wadah + 1, f'end_{t_id}_{h}')
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
    # 3. KENDALA HARGA MATI (MEMAKAI MATEMATIKA DARI MASTER HARI)
    # ==========================================
   # ==========================================
    # 3. KENDALA HARGA MATI (MATEMATIKA BELAJAR)
    # ==========================================
    for k in kelass:
        k_id = k['id']
        
        # Hitung sisa Jumat berdasarkan Kapasitas Belajar Master Hari
        limit_belajar_jumat = hari_kapasitas.get(hari_terakhir, 8)
        sisa_jumat = max(0, min(limit_belajar_jumat, kelas_total_jp[k_id] - kapasitas_senin_kamis))
        
        for h in hari_list:
            beban_harian = durasi_per_kelas_harian[k_id][h]
            if not beban_harian: continue
            
            if h in hari_reguler:
                # Harus pas dengan KAPASITAS BELAJAR (Misal 10), bukan 13!
                target = hari_kapasitas.get(h, 10)
                model.Add(sum(beban_harian) == target)
            elif h == hari_terakhir:
                # Jumat pas dengan sisa matematika
                model.Add(sum(beban_harian) == sisa_jumat)

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
    # 5. EKSEKUSI PENCARIAN (CEPAT & AMAN RAM)
    # ==========================================
    if all_start_vars:
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    solver = cp_model.CpSolver()
    # Pekerja gue turunin 1 biar server Render lu nggak meledak lagi ya
    solver.parameters.max_time_in_seconds = 90 
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
            "message": f"MANTAP! Jadwal dibikin dalam {waktu_komputasi:.2f} detik pakai Data Master Hari Asli!"
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "message": f"Gagal menyusun (Waktu: {waktu_komputasi:.2f} detik). Pastiin data nggak bentrok."
        }))

if __name__ == '__main__':
    main()