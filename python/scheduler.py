import sys
import json
import time  
from ortools.sat.python import cp_model

def main():
    # Mulai menghitung waktu komputasi
    start_time = time.time()  

    # ==========================================
    # 1. PERSIAPAN DATA (DARI LARAVEL)
    # ==========================================
    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "message": "Path file JSON tidak ditemukan!"}))
        return

    json_path = sys.argv[1]
    try:
        with open(json_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": f"Gagal membaca JSON: {str(e)}"}))
        return

    # Ambil data utama
    raw_assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    hari_info = data.get('hari_aktif', [])
    
    # --- HEURISTIK: Urutkan SKS terbesar dulu ---
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    hari_list = [h['nama'] for h in hari_info]
    # Mapping Batas Jam Sekolah per Hari (Master Hari)
    global_max_jam_map = {h['nama']: int(h['max_jam']) for h in hari_info}
    
    # Mapping Limit Harian per Kelas
    kelas_limits = {k['id']: {
        'normal': int(k.get('limit_harian', 10)),
        'jumat': int(k.get('limit_jumat', 9)),
        'nama': k.get('nama_kelas', 'Unknown')
    } for k in kelass}

    # Fungsi untuk mencari batas paling ketat (Limit Kelas vs Jam Sekolah)
    def get_max_jam_allowed(kelas_id, hari):
        limit = kelas_limits.get(kelas_id, {'normal': 10, 'jumat': 9})
        batas_kelas = limit['jumat'] if hari == 'Jumat' else limit['normal']
        batas_global = global_max_jam_map.get(hari, 10)
        return min(batas_kelas, batas_global)

    # Mapping Hari Mengajar Guru
    guru_hari_map = {g['id']: (g.get('hari_mengajar') if g.get('hari_mengajar') else hari_list) for g in gurus}
    guru_nama_map = {g['id']: g.get('nama', 'Guru Anonim') for g in gurus}

    # ==========================================
    # 2. MEMBANGUN MODEL MATEMATIKA (CP-SAT)
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
        # --- FITUR: Hanya proses yang OFFLINE ---
        if t.get('status') == 'online':
            continue

        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id, g_id, k_id, m_id = t['id'], t['guru_id'], t['kelas_id'], t.get('mapel_id')
        locked_hari, locked_jam = t.get('locked_hari'), t.get('locked_jam')
        
        tasks_metadata.append({'id': t_id})
        
        # Kelompokkan mapel yang sama dalam satu kelas (Subject Group)
        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        if group_key not in tasks_per_mapel_group: tasks_per_mapel_group[group_key] = []
        tasks_per_mapel_group[group_key].append(t_id)

        possible_days = [] 
        for h in hari_list:
            # Filter: Hari mengajar guru (kecuali jika di-lock manual)
            if h not in guru_hari_map.get(g_id, hari_list) and h != locked_hari:
                continue 

            # Ambil batas jam paling ketat
            batas_jam = get_max_jam_allowed(k_id, h)
            
            # Cek apakah durasi mapel muat di hari ini
            if durasi > batas_jam:
                if h == locked_hari:
                    print(json.dumps({"status": "INFEASIBLE", "message": f"LOCK ERROR: Mapel {t_id} berdurasi {durasi} JP tapi dikunci di hari {h} yang cuma punya {batas_jam} slot."}))
                    return
                continue 

            max_start = batas_jam - durasi + 1
            start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_jam + 1, f'end_{t_id}_{h}')
            is_present = model.NewBoolVar(f'present_{t_id}_{h}')
            
            interval_var = model.NewOptionalIntervalVar(start_var, durasi, end_var, is_present, f'interval_{t_id}_{h}')

            # --- FITUR: LOCK JADWAL MANUAL ---
            if locked_hari and locked_jam is not None:
                if h == locked_hari:
                    model.Add(is_present == 1)
                    model.Add(start_var == int(locked_jam))
                else:
                    model.Add(is_present == 0)

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            presences_per_guru[g_id][h].append((is_present, durasi))

        # Tugas harus ada di tepat 1 hari
        if possible_days:
            model.Add(sum(possible_days) == 1)
        else:
            print(json.dumps({"status": "INFEASIBLE", "message": f"DATA ERROR: Tugas ID {t_id} tidak punya hari yang muat. Cek durasi vs limit kelas."}))
            return

    # ==========================================
    # 3. ATURAN KERAS (CONSTRAINTS)
    # ==========================================
    
    # A. Anti-Bentrok Kelas & Guru
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]: model.AddNoOverlap(intervals_per_kelas[k_id][h])

    for g_id in gurus:
        gid = g_id['id']
        waktu_kosong = g_id.get('waktu_kosong', [])
        for h in hari_list:
            intervals = intervals_per_guru[gid][h]
            # Tambahkan dummy interval untuk waktu kosong guru
            for wk in waktu_kosong:
                if wk['hari'] == h:
                    jam_libur = int(wk['jam'])
                    intervals.append(model.NewIntervalVar(jam_libur, 1, jam_libur+1, f'wk_{gid}_{h}_{jam_libur}'))
            if intervals: model.AddNoOverlap(intervals)

    # B. Subject Grouping: Mapel yang sama jangan muncul 2x di hari yang sama
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in hari_list:
                daily_p = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_p: model.Add(sum(daily_p) <= 1)

    # C. Pemerataan Beban Harian Guru (LOAD BALANCING)
    for g_id in gurus:
        gid = g_id['id']
        total_sks = sum(int(t['jumlah_jam']) for t in raw_assignments if t['guru_id'] == gid and t.get('status') != 'online')
        h_aktif = len(guru_hari_map.get(gid, hari_list))
        if h_aktif > 0:
            # Beri toleransi agar tidak terlalu kaku (+3 JP)
            batas_dinamis = (total_sks // h_aktif) + 3
            for h in hari_list:
                beban_harian = [is_p * d for is_p, d in presences_per_guru[gid][h]]
                if beban_harian: model.Add(sum(beban_harian) <= batas_dinamis)

    # --- STRATEGI: Pack jam ke pagi hari (SELECT_MIN_VALUE) ---
    if all_start_vars:
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    # ==========================================
    # 4. EKSEKUSI SOLVER
    # ==========================================
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300
    solver.parameters.num_search_workers = 8 
    
    status = solver.Solve(model)
    waktu_komputasi = time.time() - start_time 

    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        final_solution = []
        for t in tasks_metadata:
            tid = t['id']
            for h in hari_list:
                if (tid, h) in presences and solver.Value(presences[(tid, h)]) == 1:
                    final_solution.append({'id': tid, 'hari': h, 'jam': solver.Value(starts[(tid, h)])})
                    break
        
        print(json.dumps({
            "status": "OPTIMAL",
            "solution": final_solution,
            "waktu_komputasi_detik": round(waktu_komputasi, 2),
            "message": f"Jadwal disusun dalam {waktu_komputasi:.2f} detik. Aturan sekolah & hari mengajar guru dipatuhi."
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "waktu_komputasi_detik": round(waktu_komputasi, 2),
            "message": "Gagal menyusun. Terjadi kontradiksi data (misal: total JP kelas melebihi total slot belajar mingguan)."
        }))

if __name__ == '__main__':
    main()