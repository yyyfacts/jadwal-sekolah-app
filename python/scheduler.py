import sys
import json
import time
from ortools.sat.python import cp_model

def main():
    start_time = time.time()

    # 1. BACA DATA
    if len(sys.argv) < 2: return
    try:
        with open(sys.argv[1], 'r', encoding='utf-8') as f:
            data = json.load(f)
    except: return

    assignments = [a for a in data.get('assignments', []) if a.get('status') == 'offline']
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    hari_info = data.get('hari_aktif', [])
    
    # Urutkan balok paling panjang dulu (biar susunnya gampang)
    assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    hari_list = [h['nama'] for h in hari_info]
    global_max_map = {h['nama']: int(h['max_jam']) for h in hari_info}
    
    kelas_limits = {k['id']: {
        'normal': int(k.get('limit_harian', 10)),
        'jumat': int(k.get('limit_jumat', 9))
    } for k in kelass}

    def get_max_jam(k_id, h_nama):
        limits = kelas_limits.get(k_id, {'normal': 10, 'jumat': 9})
        batas_k = limits['jumat'] if "jumat" in h_nama.lower() else limits['normal']
        return min(batas_k, global_max_map.get(h_nama, 10))

    # 2. MODEL
    model = cp_model.CpModel()
    
    starts = {}
    presences = {}
    intervals_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    
    all_starts = []

    # 3. PROSES BALOK
    for task in assignments:
        t_id = task['id']
        g_id = task['guru_id']
        k_id = task['kelas_id']
        durasi = int(task['jumlah_jam'])
        
        # Pilihan hari mengajar guru
        allowed_days = task.get('hari_mengajar', []) or hari_list
        locked_h = task.get('locked_hari')
        locked_j = task.get('locked_jam')

        possible_days_vars = []
        
        for h in hari_list:
            # Filter hari mengajar guru
            if h not in allowed_days and h != locked_h: continue
            
            max_jam_hari_ini = get_max_jam(k_id, h)
            if durasi > max_jam_hari_ini: continue

            # Variabel Balok
            is_present = model.NewBoolVar(f'p_{t_id}_{h}')
            start_var = model.NewIntVar(1, max_jam_hari_ini - durasi + 1, f's_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, max_jam_hari_ini + 1, f'e_{t_id}_{h}')
            interval_var = model.NewOptionalIntervalVar(start_var, durasi, end_var, is_present, f'i_{t_id}_{h}')

            # Jika di-lock manual
            if locked_h == h and locked_j is not None:
                model.Add(is_present == 1)
                model.Add(start_var == int(locked_j))
            elif locked_h and locked_h != h:
                model.Add(is_present == 0)

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_present
            possible_days_vars.append(is_present)
            all_starts.append(start_var)
            
            intervals_kelas[k_id][h].append(interval_var)
            intervals_guru[g_id][h].append(interval_var)

        # Balok harus terpasang 1 kali
        if possible_days_vars:
            model.Add(sum(possible_days_vars) == 1)
        else:
            print(json.dumps({"status": "INFEASIBLE", "message": f"Balok {t_id} kegedean, gak muat di hari manapun."}))
            return

    # 4. ATURAN ANTI-BENTROK (Overlap)
    for h in hari_list:
        for k_id in intervals_kelas:
            if intervals_kelas[k_id][h]:
                model.AddNoOverlap(intervals_kelas[k_id][h])
        for g_id in intervals_guru:
            if intervals_guru[g_id][h]:
                model.AddNoOverlap(intervals_guru[g_id][h])

    # 5. STRATEGI RATA KIRI (BALOK MEPET KE PAGI)
    # Ini kuncinya biar urut dari kiri ke kanan (jam 1, jam 2, dst)
    model.Minimize(sum(all_starts))

    # 6. SOLVE
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 60
    status = solver.Solve(model)

    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        sol = []
        for t in assignments:
            for h in hari_list:
                if (t['id'], h) in presences and solver.Value(presences[(t['id'], h)]):
                    sol.append({'id': t['id'], 'hari': h, 'jam': solver.Value(starts[(t['id'], h)])})
        print(json.dumps({"status": "OPTIMAL", "solution": sol, "message": "Balok berhasil disusun rapi!"}))
    else:
        print(json.dumps({"status": "INFEASIBLE", "message": "Gagal susun! Balok kepenuhan atau ada guru yang dipaksa di dua tempat."}))

if __name__ == '__main__': main()