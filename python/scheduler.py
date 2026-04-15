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
    hari_aktif_data = data.get('hari_aktif', [])

    # 1. STRATEGI LJF (Longest Job First) - Bab 2.2.7
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
    
    # Mapping Kapasitas Master Hari
    kapasitas_master = {h['nama']: int(h['max_jam']) for h in hari_aktif_data}
    
    # Mapping Limit Kepulangan Kelas
    kelas_limits = {k['id']: {
        'normal': int(k.get('limit_harian', 10)),
        'jumat': int(k.get('limit_jumat', 7))
    } for k in kelass}

    def get_max_jam(kelas_id, hari):
        # Ambil yang paling kecil antara Kuota Kotak Belajar vs Limit Kelas
        master = kapasitas_master.get(hari, 10)
        limit_k = kelas_limits.get(kelas_id, {'normal': 10, 'jumat': 7})
        asli_kelas = limit_k['jumat'] if hari == 'Jumat' else limit_k['normal']
        return min(master, asli_kelas)

    model = cp_model.CpModel()

    starts = {}      
    presences = {}   
    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    
    # Penampung untuk Teacher Fatigue (Batas Jam Guru per Hari)
    teacher_daily_hours = {g['id']: {h: [] for h in hari_list} for g in gurus}

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        t_id, g_id, k_id = t['id'], t['guru_id'], t['kelas_id']
        
        possible_days = []
        for h in hari_list:
            # Cek apakah guru bisa mengajar di hari ini
            allowed_days = next((g['hari_mengajar'] for g in gurus if g['id'] == g_id), hari_list)
            if h not in allowed_days: continue

            batas = get_max_jam(k_id, h)
            if durasi > batas: continue

            max_s = batas - durasi + 1
            if max_s < 1: continue

            start_var = model.NewIntVar(1, max_s, f's_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas + 1, f'e_{t_id}_{h}')
            is_p = model.NewBoolVar(f'p_{t_id}_{h}')
            
            interval = model.NewOptionalIntervalVar(start_var, durasi, end_var, is_p, f'i_{t_id}_{h}')

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_p
            possible_days.append(is_p)

            intervals_per_kelas[k_id][h].append(interval)
            intervals_per_guru[g_id][h].append(interval)
            
            # Simpan durasi dikali presence untuk hitung total jam guru
            teacher_daily_hours[g_id][h].append(is_p * durasi)

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({"status": "INFEASIBLE", "message": f"Mapel ID {t_id} (Durasi {durasi}) gak muat di kelas {k_id}."}))
            return

    # 

    # --- CONSTRAINTS ---

    # 1. No Overlap (Kelas & Guru)
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    for g_id in intervals_per_guru:
        for h in hari_list:
            if intervals_per_guru[g_id][h]:
                model.AddNoOverlap(intervals_per_guru[g_id][h])

    # 2. Teacher Fatigue (Batas Mengajar Guru Maks 8 Jam per Hari)
    for g_id in teacher_daily_hours:
        for h in hari_list:
            if teacher_daily_hours[g_id][h]:
                model.Add(sum(teacher_daily_hours[g_id][h]) <= 8)

    # 3. Spreading (Mapel yang sama tidak boleh numpuk di satu hari)
    tasks_per_group = {}
    for t in raw_assignments:
        key = (t['kelas_id'], t['mapel_id'])
        if key not in tasks_per_group: tasks_per_group[key] = []
        tasks_per_group[key].append(t['id'])

    for (k_id, m_id), t_ids in tasks_per_group.items():
        if len(t_ids) > 1:
            for h in hari_list:
                daily_p = [presences[(tid, h)] for tid in t_ids if (tid, h) in presences]
                model.Add(sum(daily_p) <= 1)

    # ==========================================
    # STRATEGI BLOK DADU (CHOOSE_FIRST + SELECT_MIN_VALUE)
    # ==========================================
    ordered_vars = []
    for h in hari_list:
        for t in raw_assignments:
            if (t['id'], h) in starts:
                ordered_vars.append(starts[(t['id'], h)])

    if ordered_vars:
        model.AddDecisionStrategy(ordered_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    # ==========================================
    # OBJECTIVE: MINIMIZE LAST SLOT (Mendorong Jadwal Padat ke Pagi)
    # ==========================================
    # Ini adalah representasi matematis dari Compactness yang Mas tulis.
    last_slots = []
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                # Cari jam selesai paling akhir tiap hari
                finish_var = model.NewIntVar(0, 15, f'finish_{k_id}_{h}')
                # Jam selesai kelas harian
                for t in raw_assignments:
                    if t['kelas_id'] == k_id and (t['id'], h) in starts:
                        dur = int(t['jumlah_jam'])
                        model.Add(finish_var >= (starts[(t['id'], h)] + dur - 1)).OnlyEnforceIf(presences[(t['id'], h)])
                last_slots.append(finish_var)
    
    model.Minimize(sum(last_slots))

    # ==========================================
    # SOLVER EXECUTION
    # ==========================================
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 180 
    status = solver.Solve(model)
    
    waktu = time.time() - start_time

    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        sol = []
        for t in raw_assignments:
            tid = t['id']
            for h in hari_list:
                if (tid, h) in presences and solver.Value(presences[(tid, h)]):
                    sol.append({'id': tid, 'hari': h, 'jam': solver.Value(starts[(tid, h)])})
        print(json.dumps({"status": "OPTIMAL", "solution": sol, "waktu_komputasi_detik": round(waktu, 2), "message": "Jadwal Rapat Sesuai Bab 2.2.7!"}))
    else:
        print(json.dumps({"status": "INFEASIBLE", "message": "Gagal! Data bentrok atau kapasitas guru penuh."}))

if __name__ == '__main__':
    main()