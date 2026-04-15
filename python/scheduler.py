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
        with open(json_path, 'r') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": str(e)}))
        return

    raw_assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])

    # LJF (Longest Job First): Sesuai teori Mas di 2.2.7
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    mapel_busy = {}
    for m in data.get('mapel_constraints', []):
        mapel_busy[m['id']] = m['waktu_kosong']

    hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
    
    hari_aktif_data = data.get('hari_aktif', [])
    kapasitas_hari = {}
    for h in hari_aktif_data:
        kapasitas_hari[h['nama']] = int(h['max_jam'])

    guru_hari_map = {}
    for g in gurus:
        allowed_days = g.get('hari_mengajar', [])
        if not allowed_days:
            allowed_days = hari_list
        guru_hari_map[g['id']] = allowed_days

    model = cp_model.CpModel()

    starts = {}      
    presences = {}   
    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    
    tasks_per_mapel_group = {} 
    tasks_metadata = []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id = t['id']
        g_id = t['guru_id']
        k_id = t['kelas_id']
        m_id = t.get('mapel_id')
        
        tasks_metadata.append({'id': t_id, 'durasi': durasi})

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        if group_key not in tasks_per_mapel_group:
            tasks_per_mapel_group[group_key] = []
        tasks_per_mapel_group[group_key].append(t_id)

        possible_days = [] 

        for h in hari_list:
            if h not in guru_hari_map[g_id]:
                continue 

            # Sekarang hanya pakai kapasitas hari (kotak Belajar) tanpa filter batas_maksimal
            batas_jam = kapasitas_hari.get(h, 10)
            
            if durasi > batas_jam:
                continue

            max_start = batas_jam - durasi + 1
            if max_start < 1:
                continue
            
            start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_jam + 1, f'end_{t_id}_{h}')
            is_present = model.NewBoolVar(f'present_{t_id}_{h}')
            
            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'interval_{t_id}_{h}'
            )

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)

            if m_id in mapel_busy:
                for blocked in mapel_busy[m_id]:
                    if blocked['hari'] == h:
                        jam_blok = int(blocked['jam'])
                        if jam_blok <= batas_jam:
                            blocked_interval = model.NewIntervalVar(
                                jam_blok, 1, jam_blok + 1, f'block_{m_id}_{h}'
                            )
                            model.AddNoOverlap([interval_var, blocked_interval])

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({"status": "INFEASIBLE", "message": f"Mapel ID {t_id} tidak muat di hari manapun."}))
            return

    # No Overlap Constraints
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    for g_id in intervals_per_guru:
        for h in hari_list:
            if intervals_per_guru[g_id][h]:
                model.AddNoOverlap(intervals_per_guru[g_id][h])

    # Limit Mapel per Hari (Agar tidak numpuk mapel yang sama di satu hari)
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in hari_list:
                daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_presence:
                    # Max 1 kali mapel yang sama per kelas per hari (standard sekolah)
                    model.Add(sum(daily_presence) <= 1)

    # ==========================================
    # STRATEGI BLOK DADU (SESUAI PERMINTAAN)
    # CHOOSE_FIRST + SELECT_MIN_VALUE
    # ==========================================
    ordered_vars = []
    # Urutan: Senin (semua mapel), Selasa (semua mapel), dst.
    for h in hari_list:
        for t in tasks_metadata:
            if (t['id'], h) in starts:
                ordered_vars.append(starts[(t['id'], h)])

    if ordered_vars:
        model.AddDecisionStrategy(
            ordered_vars, 
            cp_model.CHOOSE_FIRST, 
            cp_model.SELECT_MIN_VALUE
        )

    # ==========================================
    # SOLVER
    # ==========================================
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
                if (t_id, h) in presences:
                    if solver.Value(presences[(t_id, h)]) == 1:
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
            "message": "Jadwal Berhasil Disusun Rapat!"
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "message": "Gagal menemukan solusi. Pastikan tidak ada guru yang bentrok di jam yang sama atau kapasitas hari kurang."
        }))

if __name__ == '__main__':
    main()