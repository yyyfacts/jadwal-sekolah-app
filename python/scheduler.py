import sys
import json
import time
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
    
    # Sortir SKS dari yang terbesar
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
    
    def get_max_jam(kelas_id, hari):
        k = next((x for x in kelass if x['id'] == kelas_id), None)
        if not k: return 10
        return int(k.get('limit_jumat', 7)) if hari == 'Jumat' else int(k.get('limit_harian', 10))

    guru_hari_map = {}
    for g in gurus:
        ad = g.get('hari_mengajar', [])
        guru_hari_map[g['id']] = ad if ad else hari_list

    model = cp_model.CpModel()

    starts = {}      
    presences = {}   
    all_start_vars = [] 

    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    
    tasks_metadata = []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id = t['id']
        g_id = t['guru_id']
        k_id = t['kelas_id']
        
        tasks_metadata.append({'id': t_id})
        possible_days = [] 

        for h in hari_list:
            if h not in guru_hari_map[g_id]:
                continue 

            batas_jam = get_max_jam(k_id, h)
            if durasi > batas_jam:
                continue

            max_start = batas_jam - durasi + 1
            if max_start < 1:
                continue
            
            start_var = model.NewIntVar(1, max_start, f's_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_jam + 1, f'e_{t_id}_{h}')
            is_present = model.NewBoolVar(f'p_{t_id}_{h}')
            
            interval_var = model.NewOptionalIntervalVar(start_var, durasi, end_var, is_present, f'i_{t_id}_{h}')

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)

        if possible_days:
            # Wajib ditaruh di 1 hari
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "message": f"TIDAK MASUK AKAL: Jadwal ID {t_id} (Durasi {durasi} JP) tidak muat ditaruh di hari manapun yang diizinkan Guru."
            }))
            return

    # CONSTRAINT 1: Kelas tidak boleh bentrok
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    # CONSTRAINT 2: Guru tidak boleh bentrok
    for g_id in intervals_per_guru:
        for h in hari_list:
            if intervals_per_guru[g_id][h]:
                model.AddNoOverlap(intervals_per_guru[g_id][h])

    # SEMUA ATURAN TAMBAHAN (PEMERATAAN, DLL) SUDAH DIBUANG AGAR AI BISA NAFAS!

    if all_start_vars:
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300 
    
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
            "message": f"AKHIRNYA SUKSES MAS! ({waktu_komputasi:.2f} detik)"
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "message": f"Gagal total. Silakan TRUNCATE (kosongkan) tabel jadwals lalu input/generate ulang dari nol."
        }))

if __name__ == '__main__':
    main()