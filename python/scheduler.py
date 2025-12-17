import sys
import json
from ortools.sat.python import cp_model

def main():
    # 1. SETUP & LOAD DATA
    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "message": "JSON file path required"}))
        return

    json_path = sys.argv[1]
    try:
        with open(json_path, 'r') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": f"File Error: {str(e)}"}))
        return

    raw_assignments = data['assignments']
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    
    # BACA CONSTRAINT MAPEL (YANG DIINPUT MANUAL)
    # Format: { mapel_id: [ {'hari': 'Senin', 'jam': 1}, ... ] }
    mapel_constraints_list = data.get('mapel_constraints', [])
    mapel_busy = {}
    for m in mapel_constraints_list:
        m_id = m['id']
        mapel_busy[m_id] = m['waktu_kosong']

    # Konfigurasi Waktu
    hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
    
    # Mapping Limit Kelas (Untuk memastikan jam pulang sesuai aturan sekolah)
    kelas_limits = {}
    for k in kelass:
        k_id = k['id']
        kelas_limits[k_id] = {
            'normal': k.get('limit_harian', 10),
            'jumat': k.get('limit_jumat', 7)
        }

    def get_max_jam(kelas_id, hari):
        limits = kelas_limits.get(kelas_id, {'normal': 10, 'jumat': 7})
        return limits['jumat'] if hari == 'Jumat' else limits['normal']

    # 3. MODEL CP-SAT
    model = cp_model.CpModel()

    starts = {}
    presences = {}
    
    # Struktur untuk menyimpan interval agar tidak tabrakan
    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    
    tasks_per_mapel_kelas = {}
    tasks = []
    
    # -- PROSES SETIAP TUGAS (ASSIGNMENT) --
    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id = t['id']
        g_id = t['guru_id']
        k_id = t['kelas_id']
        m_id = t.get('mapel_id')

        # Simpan metadata untuk output nanti
        tasks.append({'id': t_id, 'durasi': durasi})

        # Grouping untuk mencegah mapel yang sama muncul 2x sehari (opsional, biar rapi)
        group_key = (k_id, m_id if m_id else f"g_{g_id}")
        if group_key not in tasks_per_mapel_kelas:
            tasks_per_mapel_kelas[group_key] = []
        tasks_per_mapel_kelas[group_key].append(t_id)

        list_presence_hari_ini = []

        for h in hari_list:
            batas_jam = get_max_jam(k_id, h)
            
            # Jika durasi mapel melebihi jam sekolah hari itu, skip
            if durasi > batas_jam:
                continue

            # Variabel: Kapan mulai (start), Kapan selesai (end), Apakah dijadwalkan di hari ini (is_present)
            max_start = batas_jam - durasi + 1
            start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_jam + 1, f'end_{t_id}_{h}')
            is_present = model.NewBoolVar(f'present_{t_id}_{h}')
            
            # PENTING: Interval ini menjaga DURASI TETAP UTUH. Tidak akan dipecah.
            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'interval_{t_id}_{h}'
            )

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_present
            list_presence_hari_ini.append(is_present)

            # Daftarkan interval ke kelas & guru untuk pengecekan bentrok
            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)

            # --- CONSTRAINT UTAMA: PATUHI INPUT MANUAL (WAKTU KOSONG MAPEL) ---
            # Jika user sudah menyilang jam tertentu, Mapel ini HARAM ditaruh di jam itu.
            if m_id in mapel_busy:
                for blocked in mapel_busy[m_id]:
                    if blocked['hari'] == h:
                        jam_blok = int(blocked['jam'])
                        # Kita buat "kotak hantu" (dummy) yang diam di jam terlarang
                        blocked_interval = model.NewIntervalVar(
                            jam_blok, 1, jam_blok + 1, f'block_m{m_id}_{h}_{jam_blok}'
                        )
                        # Kita suruh solver: Jadwal mapel (interval_var) JANGAN SAMPAI nyenggol kotak hantu ini.
                        model.AddNoOverlap([interval_var, blocked_interval])

        # CONSTRAINT WAJIB: Setiap tugas harus muncul tepat 1 kali dalam seminggu
        if list_presence_hari_ini:
            model.Add(sum(list_presence_hari_ini) == 1)
        else:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "message": f"Mapel ID {t_id} (Durasi {durasi} jam) tidak muat di hari apapun! Cek batasan jam kelas."
            }))
            return

    # -- CEK BENTROK KELAS --
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    # -- CEK BENTROK GURU & WAKTU LIBUR GURU --
    for g in gurus:
        g_id = g['id']
        waktu_kosong = g.get('waktu_kosong', [])
        for h in hari_list:
            intervals_guru_hari_ini = intervals_per_guru[g_id][h]
            
            # Masukkan jam libur guru sebagai dummy interval agar tidak ditabrak jadwal
            for wk in waktu_kosong:
                if wk['hari'] == h:
                    jam_libur = int(wk['jam'])
                    dummy_interval = model.NewIntervalVar(
                        jam_libur, 1, jam_libur + 1, f'busy_g{g_id}_{h}_{jam_libur}'
                    )
                    intervals_guru_hari_ini.append(dummy_interval)
            
            if intervals_guru_hari_ini:
                model.AddNoOverlap(intervals_guru_hari_ini)

    # -- CONSTRAINT DISTRIBUSI (Agar Mapel yang sama tidak numpuk di hari yang sama) --
    # Jika mapel dipecah jadi 2 baris (misal 2 jam + 1 jam), jangan taruh di hari yang sama.
    for key, task_ids in tasks_per_mapel_kelas.items():
        if len(task_ids) > 1:
            for h in hari_list:
                presences_in_this_day = []
                for tid in task_ids:
                    if (tid, h) in presences:
                        presences_in_this_day.append(presences[(tid, h)])
                
                # Maksimal 1 blok per mapel per hari
                if presences_in_this_day:
                    model.Add(sum(presences_in_this_day) <= 1)
    
    # 4. SOLVING
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 120
    # Menggunakan multi-thread agar lebih cepat menemukan solusi
    solver.parameters.num_search_workers = 8 
    
    status = solver.Solve(model)

    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        final_solution = []
        for t in tasks:
            t_id = t['id']
            for h in hari_list:
                if (t_id, h) in presences:
                    # Ambil hasil perhitungan AI
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
            "message": "Jadwal Berhasil Disusun! Input manual dipatuhi."
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "message": "Jadwal Gagal Disusun (Bentrok/Penuh). Coba kurangi constraint waktu kosong."
        }))

if __name__ == '__main__':
    main()