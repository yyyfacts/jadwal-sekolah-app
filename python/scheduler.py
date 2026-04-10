import sys
import json
import time  
from ortools.sat.python import cp_model

def main():
    start_time = time.time()  

    # ==========================================
    # 1. PERSIAPAN DATA
    # ==========================================
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

    # --- AMBIL DATA DINAMIS DARI LARAVEL ---
    raw_assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    
    # Ambil Setting Hari Aktif dari Web
    hari_info = data.get('hari_aktif', [])
    hari_list = [h['nama'] for h in hari_info]
    max_jam_map = {h['nama']: int(h['max_jam']) for h in hari_info}

    def get_max_jam(hari):
        return max_jam_map.get(hari, 10) # Default 10 jika hari tidak ditemukan

    # --- LOGIKA HEURISTIK BLOK SUSUN ---
    # Mengurutkan SKS besar ke kecil agar lebih mudah masuk ke slot kosong
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    # Mapping Hari Mengajar Guru
    guru_hari_map = {}
    for g in gurus:
        allowed_days = g.get('hari_mengajar', [])
        # Jika guru tidak pilih hari, anggap bisa semua hari yang AKTIF di sistem
        if not allowed_days:
            allowed_days = hari_list
        guru_hari_map[g['id']] = allowed_days

    # ==========================================
    # 2. MEMBANGUN MODEL
    # ==========================================
    model = cp_model.CpModel()

    starts = {}      
    presences = {}   
    all_start_vars = [] 

    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    presences_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    
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
            # FILTER 1: Hanya buat variabel jika hari ini diizinkan oleh Guru
            if h not in guru_hari_map[g_id]:
                continue 

            batas_jam_hari_ini = get_max_jam(h)
            
            # FILTER 2: Jangan paksa masuk kalau durasi SKS > Batas Jam (misal SKS 8 masuk ke hari Jumat yang cuma 7 jam)
            if durasi > batas_jam_hari_ini:
                continue

            max_start = batas_jam_hari_ini - durasi + 1
            
            # --- PEMBENTUKAN VARIABEL INTERVAL ---
            start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_jam_hari_ini + 1, f'end_{t_id}_{h}')
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
            presences_per_guru[g_id][h].append((is_present, durasi))

        # Tugas harus muncul tepat 1 kali di salah satu hari yang valid
        if possible_days:
            model.Add(sum(possible_days) == 1)
        else:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "message": f"Bentrok fatal! Tugas ID {t_id} (Guru {g_id}) tidak muat di hari mana pun. Cek batas jam harian atau SKS."
            }))
            return

    # ==========================================
    # 3. CONSTRAINTS (BATASAN ATURAN)
    # ==========================================

    # --- NO OVERLAP (JANGAN BENTROK) ---
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    for g_id in intervals_per_guru:
        for h in hari_list:
            if intervals_per_guru[g_id][h]:
                model.AddNoOverlap(intervals_per_guru[g_id][h])

    # --- DISTRIBUSI (JANGAN NUMPUK DI HARI SAMA) ---
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in hari_list:
                daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_presence:
                    model.Add(sum(daily_presence) <= 1)

    # --- PEMERATAAN BEBAN HARIAN GURU (LOAD BALANCING) ---
    for g_id in presences_per_guru:
        total_sks_guru = sum(dur for is_present, dur in [item for sublist in presences_per_guru[g_id].values() for item in sublist])
        
        # Hitung hari aktif khusus guru ini
        hari_aktif_guru = len(guru_hari_map.get(g_id, hari_list))
        if hari_aktif_guru > 0:
            batas_dinamis = int(total_sks_guru / hari_aktif_guru) + 2
            for h in hari_list:
                beban_harian = [is_p * dur for is_p, dur in presences_per_guru[g_id][h]]
                if beban_harian:
                    model.Add(sum(beban_harian) <= batas_dinamis)

    # ==========================================
    # 4. EKSEKUSI & STRATEGI
    # ==========================================
    if all_start_vars:
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300 
    solver.parameters.num_search_workers = 8 
    
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
            "waktu_komputasi_detik": round(waktu_komputasi, 2), 
            "message": f"Sukses! Jadwal disusun dalam {waktu_komputasi:.2f} detik mengikuti hari aktif dinamis."
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "waktu_komputasi_detik": round(waktu_komputasi, 2),
            "message": "Gagal! Tidak ditemukan susunan jadwal yang memenuhi semua aturan harian."
        }))

if __name__ == '__main__':
    main()