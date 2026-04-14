import sys
import json
import time
from ortools.sat.python import cp_model

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "message": "JSON path required"}))
        return

    json_path = sys.argv[1]
    with open(json_path, 'r') as f:
        data = json.load(f)

    raw_assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    
    # Sortir: Prioritaskan blok besar (3 JP / 4 JP) agar dipasang duluan
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    hari_info = data.get('hari_aktif', [])
    hari_list = [h['nama'] for h in hari_info]
    max_jam_map = {h['nama']: int(h['max_jam']) for h in hari_info}

    # PRE-CHECK DIHILANGKAN UNTUK MENGHEMAT SPACE (Sama seperti sebelumnya)

    model = cp_model.CpModel()
    starts, ends, presences, all_start_vars = {}, {}, {}, []
    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    presences_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    tasks_per_mapel_group, tasks_metadata = {}, []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id, g_id, k_id, m_id = t['id'], t['guru_id'], t['kelas_id'], t.get('mapel_id')
        nama_mapel = t.get('nama_mapel', '').lower()
        tasks_metadata.append({'id': t_id})

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        if group_key not in tasks_per_mapel_group: tasks_per_mapel_group[group_key] = []
        tasks_per_mapel_group[group_key].append(t_id)

        possible_days = [] 

        for h in hari_list:
            batas_jam = max_jam_map.get(h, 10) 
            if durasi > batas_jam: continue

            max_start = batas_jam - durasi + 1
            
            start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_jam + 1, f'end_{t_id}_{h}')
            is_present = model.NewBoolVar(f'present_{t_id}_{h}')
            
            interval_var = model.NewOptionalIntervalVar(start_var, durasi, end_var, is_present, f'interval_{t_id}_{h}')

            # =============================================================
            # ATURAN KHUSUS PJOK: MAX JAM KE 6
            # end_var <= 7 berarti batas akhir pelajaran adalah kotak ke-6
            # =============================================================
            if 'pjok' in nama_mapel or 'olahraga' in nama_mapel or 'penjas' in nama_mapel:
                model.Add(end_var <= 7).OnlyEnforceIf(is_present)

            starts[(t_id, h)] = start_var
            ends[(t_id, h)] = end_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            
            # Simpan end_var untuk fitur VAKUM nanti
            presences_per_kelas[k_id][h].append((is_present, durasi, end_var))

        if possible_days:
            model.Add(sum(possible_days) == 1)

    # Batasan Kelas
    for k_id in intervals_per_kelas:
        for h in hari_list:
            # 1. Anti Bentrok
            if intervals_per_kelas[k_id][h]: 
                model.AddNoOverlap(intervals_per_kelas[k_id][h])
            
            limit_aktif = max_jam_map.get(h, 10)
            beban_kelas = [is_p * dur for is_p, dur, e_var in presences_per_kelas[k_id][h]]
            
            if beban_kelas: 
                # Total JP per hari tidak boleh melewati batas
                total_dur = model.NewIntVar(0, limit_aktif, f'total_dur_{k_id}_{h}')
                model.Add(total_dur == sum(beban_kelas))
                
                # =============================================================
                # ATURAN VAKUM (ANTI BOLONG)
                # Memaksa semua jadwal tidak ada yang melewati total durasi
                # Artinya semua jadwal akan dipadatkan ke kiri tanpa rongga/bolong
                # =============================================================
                for is_p, dur, e_var in presences_per_kelas[k_id][h]:
                    model.Add(e_var <= 1 + total_dur).OnlyEnforceIf(is_p)

    # Batasan Anti Bentrok Guru 
    for g in gurus:
        g_id = g['id']
        for h in hari_list:
            intervals = intervals_per_guru[g_id][h]
            if intervals: model.AddNoOverlap(intervals)

    # Batasan Mapel Kembar Sehari (Maks 2 sesi per hari agar variatif)
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in hari_list:
                daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_presence: model.Add(sum(daily_presence) <= 2)

    # Strategi: Letakkan jadwal sedini mungkin di pagi hari
    if all_start_vars: model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    start_time = time.time()  
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
                    final_solution.append({'id': t_id, 'hari': h, 'jam': solver.Value(starts[(t_id, h)])})
                    break
        
        print(json.dumps({
            "status": "OPTIMAL", 
            "solution": final_solution, 
            "waktu_komputasi_detik": round(waktu_komputasi, 2), 
            "message": f"Sukses! Jadwal berhasil disusun tanpa bolong dalam {waktu_komputasi:.2f} detik."
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", "waktu_komputasi_detik": round(waktu_komputasi, 2), 
            "error_code": "CSP_DEADLOCK", "target_error": "Sistem Puzzle AI",
            "message": "AI gagal menyusun jadwal tanpa bolong. Kemungkinan jadwal guru terlalu padat/bentrok.",
            "rekomendasi": "1. Gabungkan mapel yg pecah-pecah.\n2. Cek guru yg 'Hari Mengajar' nya sedikit."
        }))

if __name__ == '__main__': main()