import sys
import json
import time  
from ortools.sat.python import cp_model

def main():
    # Mulai menghitung waktu komputasi AI
    start_time = time.time()  

    # ==========================================
    # 1. PERSIAPAN & BACA DATA JSON
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

    # Ambil data dari Laravel
    raw_assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    hari_info = data.get('hari_aktif', [])
    
    # --- HEURISTIK: Prioritaskan SKS Besar ---
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    hari_list = [h['nama'] for h in hari_info]
    global_max_jam_map = {h['nama']: int(h['max_jam']) for h in hari_info}
    
    # Mapping Limit Harian per Kelas
    kelas_limits = {k['id']: {
        'normal': int(k.get('limit_harian', 10)),
        'jumat': int(k.get('limit_jumat', 9)),
        'nama': k.get('nama_kelas', 'Unknown')
    } for k in kelass}

    # Fungsi Sakti Pencari Batas Ketat
    def get_max_jam(kelas_id, hari):
        limit = kelas_limits.get(kelas_id, {'normal': 10, 'jumat': 9})
        batas_kelas = limit['jumat'] if hari == 'Jumat' else limit['normal']
        batas_global = global_max_jam_map.get(hari, 10)
        return min(batas_kelas, batas_global)

    # Mapping Hari Mengajar Guru
    guru_hari_map = {g['id']: (g.get('hari_mengajar') if g.get('hari_mengajar') else hari_list) for g in gurus}
    guru_nama_map = {g['id']: g.get('nama', 'Guru Anonim') for g in gurus}

    # ==========================================
    # 2. VALIDASI PRE-SOLVER (DETEKSI DINI)
    # ==========================================
    for k in kelass:
        total_beban = sum(int(t['jumlah_jam']) for t in raw_assignments if t['kelas_id'] == k['id'])
        kapasitas_total = sum(get_max_jam(k['id'], h) for h in hari_list)
        if total_beban > kapasitas_total:
            print(json.dumps({"status": "INFEASIBLE", "message": f"Kapasitas Kelas {k['nama_kelas']} Tidak Cukup! Butuh {total_beban} JP, tapi hanya tersedia {kapasitas_total} slot Belajar."}))
            return

    # ==========================================
    # 3. MEMBANGUN MODEL MATEMATIKA (OR-TOOLS)
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
        
        t_id, g_id, k_id, m_id = t['id'], t['guru_id'], t['kelas_id'], t.get('mapel_id')
        locked_hari, locked_jam = t.get('locked_hari'), t.get('locked_jam')
        
        tasks_metadata.append({'id': t_id})
        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        if group_key not in tasks_per_mapel_group: tasks_per_mapel_group[group_key] = []
        tasks_per_mapel_group[group_key].append(t_id)

        possible_days = [] 
        for h in hari_list:
            # Filter Hari Mengajar Guru (Kecuali jika di-lock manual)
            if h not in guru_hari_map.get(g_id, hari_list) and h != locked_hari:
                continue 

            batas_jam = get_max_jam(k_id, h)
            
            # Cek Durasi vs Jam Sekolah
            if durasi > batas_jam:
                if h == locked_hari:
                    print(json.dumps({"status": "INFEASIBLE", "message": f"Lock Error! Mapel {t_id} (Guru {guru_nama_map[g_id]}) punya {durasi} JP, tapi hari {h} cuma ada {batas_jam} slot Belajar."}))
                    return
                continue 

            max_start = batas_jam - durasi + 1
            start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_jam + 1, f'end_{t_id}_{h}')
            is_present = model.NewBoolVar(f'present_{t_id}_{h}')
            
            interval_var = model.NewOptionalIntervalVar(start_var, durasi, end_var, is_present, f'interval_{t_id}_{h}')

            # --- LOGIKA LOCK MANUAL ---
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

        if possible_days:
            model.Add(sum(possible_days) == 1)
        else:
            print(json.dumps({"status": "INFEASIBLE", "message": f"Konflik! Tugas ID {t_id} tidak punya hari yang cocok. Cek Hari Mengajar Guru."}))
            return

    # ==========================================
    # 4. ATURAN (CONSTRAINTS)
    # ==========================================
    
    # A. Anti-Bentrok Kelas
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]: model.AddNoOverlap(intervals_per_kelas[k_id][h])

    # B. Anti-Bentrok Guru & Waktu Kosong
    for g in gurus:
        g_id = g['id']
        waktu_kosong = g.get('waktu_kosong', [])
        for h in hari_list:
            intervals = intervals_per_guru[g_id][h]
            for wk in waktu_kosong:
                if wk['hari'] == h:
                    jam_libur = int(wk['jam'])
                    intervals.append(model.NewIntervalVar(jam_libur, 1, jam_libur+1, f'wk_{g_id}_{h}_{jam_libur}'))
            if intervals: model.AddNoOverlap(intervals)

    # C. Batasan 1 Pertemuan per Hari (Subject Group)
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in hari_list:
                daily_p = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_p: model.Add(sum(daily_p) <= 1)

    # D. Pemerataan Beban Guru
    for g in gurus:
        gid = g['id']
        total_sks = sum(int(t['jumlah_jam']) for t in raw_assignments if t['guru_id'] == gid)
        h_aktif = len(guru_hari_map.get(gid, hari_list))
        if h_aktif > 0:
            batas_dinamis = (total_sks // h_aktif) + 2
            for h in hari_list:
                beban_harian = [is_p * d for is_p, d in presences_per_guru[gid][h]]
                if beban_harian: model.Add(sum(beban_harian) <= batas_dinamis)

    # E. Strategi Padat (Rata Kiri)
    if all_start_vars:
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    # ==========================================
    # 5. EKSEKUSI SOLVER
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
            "message": f"Sukses! Jadwal disusun dalam {waktu_komputasi:.2f} detik."
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "waktu_komputasi_detik": round(waktu_komputasi, 2),
            "message": "Gagal menyusun. Kombinasi jadwal tidak memungkinkan. Cek beban guru atau ketersediaan jam belajar di Master Hari."
        }))

if __name__ == '__main__':
    main()