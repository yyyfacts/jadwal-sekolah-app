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
        # Kita urutkan dulu: Mapel durasi PANJANG diproses duluan.
        raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

        # Constraint Mapel Manual
        mapel_busy = {}
        for m in data.get('mapel_constraints', []):
            mapel_busy[m['id']] = m['waktu_kosong']

        hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
        
        # Limit Harian per Kelas
        kelas_limits = {}
        for k in kelass:
            kelas_limits[k['id']] = {
                'normal': int(k.get('limit_harian', 10)),
                'jumat': int(k.get('limit_jumat', 7))
            }

        def get_max_jam(kelas_id, hari):
            limits = kelas_limits.get(kelas_id, {'normal': 10, 'jumat': 7})
            return limits['jumat'] if hari == 'Jumat' else limits['normal']

        # ==========================================
        # 2. MEMBANGUN MODEL
        # ==========================================
        model = cp_model.CpModel()

        starts = {}      # Menyimpan variabel Jam Mulai
        presences = {}   # Menyimpan variabel Apakah Hadir
        all_start_vars = [] # Koleksi semua variabel waktu untuk strategi susun

        # Wadah untuk cek bentrok
        intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
        intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
        
        # Untuk memastikan mapel yg terpecah (misal 2+1) tidak hari yg sama
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

            # Grouping untuk constraint distribusi hari
            group_key = (k_id, m_id if m_id else f"guru_{g_id}")
            if group_key not in tasks_per_mapel_group:
                tasks_per_mapel_group[group_key] = []
            tasks_per_mapel_group[group_key].append(t_id)

            possible_days = [] # List variabel "Apakah hadir di hari X"

            for h in hari_list:
                batas_jam = get_max_jam(k_id, h)
                
                # Skip jika durasi mapel lebih besar dari jam sekolah hari itu
                if durasi > batas_jam:
                    continue

                max_start = batas_jam - durasi + 1
                
                # -- INTI VARIABEL --
                start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
                end_var = model.NewIntVar(1 + durasi, batas_jam + 1, f'end_{t_id}_{h}')
                is_present = model.NewBoolVar(f'present_{t_id}_{h}')
                
                # Interval (Kotak Jadwal)
                interval_var = model.NewOptionalIntervalVar(
                    start_var, durasi, end_var, is_present, f'interval_{t_id}_{h}'
                )

                # Simpan referensi
                starts[(t_id, h)] = start_var
                presences[(t_id, h)] = is_present
                possible_days.append(is_present)
                
                # Masukkan ke koleksi strategi susun (Hanya jika mapel itu aktif)
                all_start_vars.append(start_var)

                # Daftarkan ke Guru & Kelas untuk cek bentrok
                intervals_per_kelas[k_id][h].append(interval_var)
                intervals_per_guru[g_id][h].append(interval_var)

                # CONSTRAINT: Mapel Dilarang (Input Manual)
                if m_id in mapel_busy:
                    for blocked in mapel_busy[m_id]:
                        if blocked['hari'] == h:
                            jam_blok = int(blocked['jam'])
                            # Buat kotak hantu (dummy) di jam terlarang
                            if jam_blok <= batas_jam:
                                blocked_interval = model.NewIntervalVar(
                                    jam_blok, 1, jam_blok + 1, f'block_{m_id}_{h}'
                                )
                                # Jangan tabrakan sama kotak hantu
                                model.AddNoOverlap([interval_var, blocked_interval])

            # CONSTRAINT: Mapel ini harus muncul TEPAT 1 KALI dalam seminggu
            if possible_days:
                model.Add(sum(possible_days) == 1)
            else:
                # Jika mapel kepanjangan dan tidak muat di hari apapun
                print(json.dumps({
                    "status": "INFEASIBLE", 
                    "message": f"Mapel ID {t_id} (Durasi {durasi} jam) kepanjangan, tidak muat di hari apapun."
                }))
                return

        # --- CONSTRAINT BENTROK KELAS & GURU ---
        # 1. Kelas tidak boleh ada 2 mapel di jam yang sama
        for k_id in intervals_per_kelas:
            for h in hari_list:
                if intervals_per_kelas[k_id][h]:
                    model.AddNoOverlap(intervals_per_kelas[k_id][h])

        # 2. Guru tidak boleh mengajar di 2 kelas di jam yang sama (+ Libur Guru)
        for g in gurus:
            g_id = g['id']
            waktu_kosong = g.get('waktu_kosong', [])
            for h in hari_list:
                intervals = intervals_per_guru[g_id][h]
                
                # Tambahkan jam libur guru sebagai 'kotak hantu'
                for wk in waktu_kosong:
                    if wk['hari'] == h:
                        jam_libur = int(wk['jam'])
                        dummy = model.NewIntervalVar(jam_libur, 1, jam_libur+1, 'libur_guru')
                        intervals.append(dummy)
                
                if intervals:
                    model.AddNoOverlap(intervals)

        # 3. Distribusi: Jangan ada mapel (kode sama) numpuk di hari yang sama
        for key, task_ids in tasks_per_mapel_group.items():
            if len(task_ids) > 1:
                for h in hari_list:
                    daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                    if daily_presence:
                        model.Add(sum(daily_presence) <= 1)

        # ==========================================
        # 3. STRATEGI "BLOK SUSUN"
        # ==========================================
        if all_start_vars:
            model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

        # ==========================================
        # 4. EKSEKUSI & PERHITUNGAN WAKTU
        # ==========================================
        
        start_time = time.time()  # <-- TAMBAHAN 2: Mulai stopwatch

        solver = cp_model.CpSolver()
        solver.parameters.max_time_in_seconds = 300 # 5 menit maks
        solver.parameters.num_search_workers = 8    # Pakai semua core CPU
        
        status = solver.Solve(model)

        end_time = time.time()  # <-- TAMBAHAN 3: Matikan stopwatch
        waktu_komputasi = end_time - start_time  # Hitung selisih waktu

        if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
            final_solution = []
            for t in tasks_metadata:
                t_id = t['id']
                # Cari hari & jam mana yang dipilih solver
                for h in hari_list:
                    if (t_id, h) in presences:
                        # Cek nilai boolean (1 = Terpilih)
                        if solver.Value(presences[(t_id, h)]) == 1:
                            jam_mulai = solver.Value(starts[(t_id, h)])
                            final_solution.append({
                                'id': t_id,
                                'hari': h,
                                'jam': jam_mulai
                            })
                            break
            
            # <-- TAMBAHAN 4: Kirim data waktu ke Laravel
            print(json.dumps({
                "status": "OPTIMAL",
                "solution": final_solution,
                "waktu_komputasi_detik": round(waktu_komputasi, 2), 
                "message": f"Jadwal berhasil disusun dalam {waktu_komputasi:.2f} detik dengan metode Blok Susun."
            }))
        else:
            # <-- TAMBAHAN 5: Kirim data waktu gagal ke Laravel
            print(json.dumps({
                "status": "INFEASIBLE", 
                "waktu_komputasi_detik": round(waktu_komputasi, 2),
                "message": f"Gagal menyusun (Waktu: {waktu_komputasi:.2f} detik). Data terlalu padat atau bentrok parah."
            }))

    if __name__ == '__main__':
        main()