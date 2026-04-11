import sys
import json
import time  # <-- TAMBAHAN 1: Import library waktu
from ortools.sat.python import cp_model

def main():
    # ==========================================
    # 1. PERSIAPAN DATA
    # ==========================================
    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "message": "JSON path required"}))
        return

    json_path = sys.argv[1]
    try:
        with open(json_path, 'r') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": str(e)}))
        return

    # Ambil data
    raw_assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    
    # --- LOGIKA BLOK SUSUN (HEURISTIK) ---
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    # Constraint Mapel Manual
    mapel_busy = {}
    for m in data.get('mapel_constraints', []):
        mapel_busy[m['id']] = m['waktu_kosong']

    # --- TANGKAP HARI AKTIF & ISTIRAHAT DARI LARAVEL ---
    hari_info = data.get('hari_aktif', [])
    hari_list = [h['nama'] for h in hari_info]
    max_jam_map = {h['nama']: int(h['max_jam']) for h in hari_info}
    istirahat_map = {h['nama']: h.get('jam_istirahat', []) for h in hari_info}
    
    # Limit Harian per Kelas (Jumlah SKS, BUKAN batas grid)
    kelas_limits = {}
    for k in kelass:
        kelas_limits[k['id']] = {
            'normal': int(k.get('limit_harian', 10)),
            'jumat': int(k.get('limit_jumat', 7))
        }

    # ==========================================
    # 2. MEMBANGUN MODEL
    # ==========================================
    model = cp_model.CpModel()

    starts = {}      
    presences = {}   
    all_start_vars = [] 

    # Wadah untuk cek bentrok dan limit
    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    presences_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass} # <-- WADAH LIMIT KELAS
    
    tasks_per_mapel_group = {} 
    tasks_metadata = []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id = t['id']
        g_id = t['guru_id']
        k_id = t['kelas_id']
        m_id = t.get('mapel_id')
        
        tasks_metadata.append({'id': t_id})

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        if group_key not in tasks_per_mapel_group:
            tasks_per_mapel_group[group_key] = []
        tasks_per_mapel_group[group_key].append(t_id)

        possible_days = [] 

        for h in hari_list:
            batas_jam = max_jam_map.get(h, 11) # Grid fisik tabel
            jam_istirahat_hari_ini = istirahat_map.get(h, [])
            
            # Skip jika durasi mapel lebih besar dari jam sekolah hari itu
            if durasi > batas_jam:
                continue

            max_start = batas_jam - durasi + 1
            
            # --- FILTER DOMAIN: LOMPATI JAM ISTIRAHAT ---
            valid_starts = []
            for start_candidate in range(1, max_start + 1):
                bentrok_istirahat = False
                for i in range(durasi):
                    if (start_candidate + i) in jam_istirahat_hari_ini:
                        bentrok_istirahat = True
                        break
                # Blok mapel (misal 2 SKS) akan tetap menyatu dan tidak pecah
                if not bentrok_istirahat:
                    valid_starts.append(start_candidate)

            if not valid_starts:
                continue

            # Terapkan domain waktu mulai yang valid
            domain = cp_model.Domain.FromValues(valid_starts)
            start_var = model.NewIntVarFromDomain(domain, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1, batas_jam + 1, f'end_{t_id}_{h}')
            is_present = model.NewBoolVar(f'present_{t_id}_{h}')
            
            # Interval (Kotak Jadwal)
            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'interval_{t_id}_{h}'
            )

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            presences_per_kelas[k_id][h].append((is_present, durasi))

            # CONSTRAINT: Mapel Dilarang (Input Manual)
            if m_id in mapel_busy:
                for blocked in mapel_busy[m_id]:
                    if blocked['hari'] == h:
                        jam_blok = int(blocked['jam'])
                        if jam_blok <= batas_jam:
                            blocked_interval = model.NewIntervalVar(
                                jam_blok, 1, jam_blok + 1, f'block_{m_id}_{h}'
                            )
                            model.AddNoOverlap([interval_var, blocked_interval])

        # CONSTRAINT: Mapel ini harus muncul TEPAT 1 KALI
        if possible_days:
            model.Add(sum(possible_days) == 1)
        else:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "message": f"Mapel ID {t_id} (Durasi {durasi} jam) kepanjangan, nabrak istirahat atau tidak muat di hari apapun."
            }))
            return

    # --- CONSTRAINT BENTROK KELAS, GURU & LIMIT HARIAN ---
    
    for k_id in intervals_per_kelas:
        for h in hari_list:
            # 1. Kelas tidak boleh ada 2 mapel di jam yang sama
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])
            
            # 2. Limit Harian Kelas (Maksimal SKS per hari)
            limit_aktif = kelas_limits[k_id]['jumat'] if str(h).lower() == 'jumat' else kelas_limits[k_id]['normal']
            beban_kelas = [is_p * dur for is_p, dur in presences_per_kelas[k_id][h]]
            if beban_kelas:
                model.Add(sum(beban_kelas) <= limit_aktif)

    # 3. Guru tidak boleh mengajar di 2 kelas di jam yang sama (+ Libur Guru)
    for g in gurus:
        g_id = g['id']
        waktu_kosong = g.get('waktu_kosong', [])
        for h in hari_list:
            intervals = intervals_per_guru[g_id][h]
            for wk in waktu_kosong:
                if wk['hari'] == h:
                    jam_libur = int(wk['jam'])
                    dummy = model.NewIntervalVar(jam_libur, 1, jam_libur+1, 'libur_guru')
                    intervals.append(dummy)
            
            if intervals:
                model.AddNoOverlap(intervals)

    # 4. Distribusi: Jangan ada mapel (kode sama) numpuk di hari yang sama
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in hari_list:
                daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_presence:
                    model.Add(sum(daily_presence) <= 1)

    # ==========================================
    # 3. STRATEGI & EKSEKUSI
    # ==========================================
    if all_start_vars:
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    start_time = time.time()  

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300 
    solver.parameters.num_search_workers = 8    
    
    status = solver.Solve(model)

    end_time = time.time()  
    waktu_komputasi = end_time - start_time  

    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        final_solution = []
        for t in tasks_metadata:
            t_id = t['id']
            for h in hari_list:
                if (t_id, h) in presences:
                    if solver.Value(presences[(t_id, h)]) == 1:
                        jam_mulai = solver.Value(starts[(t_id, h)])
                        final_solution.append({
                            'id': t_id,
                            'hari': h,
                            'jam': jam_mulai
                        })
                        break
        
        print(json.dumps({
            "status": "OPTIMAL",
            "solution": final_solution,
            "waktu_komputasi_detik": round(waktu_komputasi, 2), 
            "message": f"Jadwal berhasil disusun dalam {waktu_komputasi:.2f} detik dengan metode Blok Susun."
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "waktu_komputasi_detik": round(waktu_komputasi, 2),
            "message": f"Gagal menyusun (Waktu: {waktu_komputasi:.2f} detik). Data terlalu padat atau bentrok parah."
        }))

if __name__ == '__main__':
    main()