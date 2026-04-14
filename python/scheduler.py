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
    
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    hari_info = data.get('hari_aktif', [])
    hari_list = [h['nama'] for h in hari_info]
    # max_jam_map adalah total kotak "Belajar" per harinya
    max_jam_map = {h['nama']: int(h['max_jam']) for h in hari_info}

    # =================================================================
    # PRE-CHECK ERROR SEBELUM SOLVING
    # =================================================================
    
    # 1. KELAS OVERLOAD (DIHITUNG DARI MASTER HARI)
    beban_kelas_total = {}
    for t in raw_assignments:
        k_id = t['kelas_id']
        beban_kelas_total[k_id] = beban_kelas_total.get(k_id, 0) + int(t['jumlah_jam'])

    for k in kelass:
        k_id = k['id']
        nama_kelas = k['nama_kelas']
        # Menghitung total slot Belajar per minggu berdasar Master Hari
        kapasitas_seminggu = sum([max_jam_map.get(h, 0) for h in hari_list])
        beban = beban_kelas_total.get(k_id, 0)
        
        if beban > kapasitas_seminggu:
            print(json.dumps({
                "status": "INFEASIBLE", "waktu_komputasi_detik": 0,
                "error_code": "CLASS_OVERLOAD", "target_error": nama_kelas,
                "message": f"Kapasitas Kelas {nama_kelas} Kepenuhan (Beban: {beban} JP, Maks Slot Sekolah: {kapasitas_seminggu} JP)."
            }))
            return

    # 2. GURU OVERLOAD 
    beban_guru_total = {}
    for t in raw_assignments:
        g_id = t['guru_id']
        beban_guru_total[g_id] = beban_guru_total.get(g_id, 0) + int(t['jumlah_jam'])

    for g in gurus:
        g_id = g['id']
        nama_guru = g['nama']
        hari_mengajar = g.get('hari_mengajar', []) or hari_list
        kapasitas_guru = sum([max_jam_map.get(h, 10) for h in hari_mengajar if h in hari_list])
        
        beban = beban_guru_total.get(g_id, 0)
        if beban > kapasitas_guru:
            print(json.dumps({
                "status": "INFEASIBLE", "waktu_komputasi_detik": 0,
                "error_code": "TEACHER_OVERLOAD", "target_error": nama_guru,
                "message": f"Beban Mengajar {nama_guru} Terlalu Banyak (Beban: {beban} JP, Maks: {kapasitas_guru} JP).",
                "rekomendasi": "Tambah 'Hari Mengajar' guru tersebut."
            }))
            return

    # =================================================================
    # CP-SAT MODELING
    # =================================================================
    model = cp_model.CpModel()
    starts, presences, all_start_vars = {}, {}, []
    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    presences_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    tasks_per_mapel_group, tasks_metadata = {}, []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id, g_id, k_id, m_id = t['id'], t['guru_id'], t['kelas_id'], t.get('mapel_id')
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

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            presences_per_kelas[k_id][h].append((is_present, durasi))

        if possible_days:
            model.Add(sum(possible_days) == 1)
        else:
            print(json.dumps({"status": "INFEASIBLE", "error_code": "MAPEL_UNPLACEABLE", "target_error": f"ID {t_id}", "message": "Ada mapel yg durasinya melebihi jam masuk sekolah harian."}))
            return

    # Batasan Anti Bentrok Kelas + Limit Beban Harian Kelas (Murni Max Jam Master Hari)
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]: model.AddNoOverlap(intervals_per_kelas[k_id][h])
            
            limit_aktif = max_jam_map.get(h, 10)
            beban_kelas = [is_p * dur for is_p, dur in presences_per_kelas[k_id][h]]
            if beban_kelas: model.Add(sum(beban_kelas) <= limit_aktif)

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
            "message": f"Sukses! Jadwal berhasil disusun dalam {waktu_komputasi:.2f} detik."
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", "waktu_komputasi_detik": round(waktu_komputasi, 2), 
            "error_code": "CSP_DEADLOCK", "target_error": "Sistem Puzzle AI",
            "message": "AI gagal menyusun jadwal (jalan buntu). Terdapat guru/kelas yang jadwalnya bentrok parah.",
            "rekomendasi": "1. Gabungkan mapel yg pecah-pecah (misal: 6x @1 JP jadi 2x @3 JP).\n2. Cek guru yg 'Hari Mengajar' nya terlalu sedikit."
        }))

if __name__ == '__main__': main()