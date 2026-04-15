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

    raw_assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])

    # Sorting Balok Besar Duluan (LPT Heuristic)
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
    
    kelas_limits = {
        k['id']: {
            'normal': int(k.get('limit_harian', 10)),
            'jumat': int(k.get('limit_jumat', 7))
        } for k in kelass
    }

    def get_max_jam(kelas_id, hari):
        limits = kelas_limits.get(kelas_id, {'normal': 10, 'jumat': 7})
        return limits['jumat'] if hari == 'Jumat' else limits['normal']

    # Pilihan Hari Guru
    guru_hari_map = {g['id']: (g.get('hari_mengajar', []) or hari_list) for g in gurus}

    # ==========================================
    # 2. MEMBANGUN MODEL
    # ==========================================
    model = cp_model.CpModel()

    starts, presences = {}, {}
    all_start_vars = [] 
    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    presences_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    tasks_per_mapel_group = {} 
    tasks_metadata = []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id, g_id, k_id = t['id'], t['guru_id'], t['kelas_id']
        m_id = t.get('mapel_id')
        nama_mapel = t.get('nama_mapel', '').upper()
        
        tasks_metadata.append({'id': t_id})
        group_key = (k_id, m_id if m_id else f"g_{g_id}")
        tasks_per_mapel_group.setdefault(group_key, []).append(t_id)

        possible_days = [] 

        for h in hari_list:
            if h not in guru_hari_map.get(g_id, hari_list): continue 

            # Ambil batas jam normal/jumat
            batas_jam = get_max_jam(k_id, h)
            
            # --- ATURAN KHUSUS P J O K ---
            # Maksimal selesai di jam ke-7 (supaya olahraga nggak siang-siang banget)
            if "P J O K" in nama_mapel or "OLAHRAGA" in nama_mapel:
                # Batas jam untuk PJOK diperketat ke jam 7, atau limit hari itu mana yang lebih kecil
                batas_jam_pjok = min(7, batas_jam)
                max_start = batas_jam_pjok - durasi + 1
            else:
                max_start = batas_jam - durasi + 1

            if max_start < 1: continue # Nggak muat kalau durasinya kepanjangan

            start_var = model.NewIntVar(1, max_start, f's_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_jam + 1, f'e_{t_id}_{h}')
            is_present = model.NewBoolVar(f'p_{t_id}_{h}')
            
            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'i_{t_id}_{h}'
            )

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            presences_per_guru[g_id][h].append((is_present, durasi))

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({"status": "INFEASIBLE", "message": f"ID {t_id} ({nama_mapel}) gak muat di slot hari/jam tersedia."}))
            return

    # ==========================================
    # 3. KENDALA FISIK & DISTRIBUSI
    # ==========================================
    # Bentrok Kelas
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    # Bentrok Guru
    for g_id in intervals_per_guru:
        for h in hari_list:
            if intervals_per_guru[g_id][h]:
                model.AddNoOverlap(intervals_per_guru[g_id][h])

    # Distribusi Harian (Jangan ada mapel sama di satu hari)
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in hari_list:
                d_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if d_presence: model.Add(sum(d_presence) <= 1)

    # Load Balancing Guru
    for g in gurus:
        g_id = g['id']
        total_jp = sum(int(t['jumlah_jam']) for t in raw_assignments if t['guru_id'] == g_id)
        hari_aktif = len([h for h in hari_list if h in guru_hari_map[g_id]])
        if hari_aktif == 0: hari_aktif = 5
        limit_beban = (total_jp // hari_aktif) + 2 # Kelonggaran 2 jam
        for h in hari_list:
            beban = [p * d for p, d in presences_per_guru[g_id][h]]
            if beban: model.Add(sum(beban) <= limit_beban)

    # ==========================================
    # 4. EKSEKUSI
    # ==========================================
    # CHOOSE_FIRST: Balok gede duluan. SELECT_MIN_VALUE: Jam pagi duluan.
    model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300
    solver.parameters.num_search_workers = 8   
    
    status = solver.Solve(model)
    waktu_komputasi = time.time() - start_time

    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        solution = []
        for t in tasks_metadata:
            t_id = t['id']
            for h in hari_list:
                if (t_id, h) in presences and solver.Value(presences[(t_id, h)]) == 1:
                    solution.append({'id': t_id, 'hari': h, 'jam': solver.Value(starts[(t_id, h)])})
                    break
        print(json.dumps({
            "status": "OPTIMAL", 
            "solution": solution, 
            "message": f"Jadwal rapi pagi (PJOK aman max jam 7) dalam {waktu_komputasi:.2f} detik."
        }))
    else:
        print(json.dumps({"status": "INFEASIBLE", "message": "Mentok! Cek beban guru atau batasan PJOK."}))

if __name__ == '__main__':
    main()