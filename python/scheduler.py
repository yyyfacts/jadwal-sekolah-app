import sys
import json
import time
import math
from ortools.sat.python import cp_model

def main():
    start_time = time.time()

    # ==========================================
    # 1. PERSIAPAN DATA & PARSING JSON
    # ==========================================
    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "message": "Path JSON diperlukan"}))
        return

    json_path = sys.argv[1]
    try:
        with open(json_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": str(e)}))
        return

    hari_aktif = data.get('hari_aktif', [])
    gurus = data.get('gurus', [])
    kelass = data.get('kelass', [])
    assignments = data.get('assignments', [])

    if not assignments:
        print(json.dumps({"status": "ERROR", "message": "Data penugasan (assignments) kosong."}))
        return

    hari_list = [h['nama'] for h in hari_aktif]
    hari_max_jam = {h['nama']: int(h['max_jam']) for h in hari_aktif}

    kelas_limits = {}
    for k in kelass:
        kelas_limits[k['id']] = {
            'limit_harian': int(k.get('limit_harian', 10)),
            'limit_jumat': int(k.get('limit_jumat', 7))
        }

    def get_max_jam(kelas_id, hari):
        limits = kelas_limits.get(kelas_id, {'limit_harian': 10, 'limit_jumat': 7})
        batas_kelas = limits['limit_jumat'] if hari == 'Jumat' else limits['limit_harian']
        batas_global = hari_max_jam.get(hari, 10)
        return min(batas_kelas, batas_global)

    guru_hari_map = {}
    for g in gurus:
        allowed_days = g.get('hari_mengajar', [])
        if not allowed_days:
            allowed_days = hari_list
        guru_hari_map[g['id']] = allowed_days

    # ==========================================
    # 2. MEMBANGUN MODEL CP-SAT (VERSI TUKANG SUSUN)
    # ==========================================
    model = cp_model.CpModel()

    starts = {}
    presences = {}
    all_start_vars = []

    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}

    tasks_per_mapel_group = {}
    tasks_metadata = []

    # Mengurutkan balok dari yang terbesar (triple) ke terkecil (single) agar muat duluan
    assignments.sort(key=lambda x: (
        0 if x.get('locked_hari') and x.get('locked_jam') is not None else 1,
        -int(x['jumlah_jam'])
    ))

    for t in assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue

        t_id = t['id']
        g_id = t['guru_id']
        k_id = t['kelas_id']
        m_id = t.get('mapel_id')
        
        locked_hari = t.get('locked_hari')
        locked_jam = t.get('locked_jam')

        tasks_metadata.append({'id': t_id})

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        if group_key not in tasks_per_mapel_group:
            tasks_per_mapel_group[group_key] = []
        tasks_per_mapel_group[group_key].append(t_id)

        possible_days = []

        for h in hari_list:
            is_locked_here = (locked_hari == h)

            if locked_hari and not is_locked_here:
                continue

            if not locked_hari and h not in guru_hari_map[g_id]:
                continue

            batas_jam = get_max_jam(k_id, h)
            
            if is_locked_here and locked_jam is not None:
                l_jam = int(locked_jam)
                start_var = model.NewIntVar(l_jam, l_jam, f'start_{t_id}_{h}')
                end_var = model.NewIntVar(l_jam + durasi, l_jam + durasi, f'end_{t_id}_{h}')
                is_present = model.NewBoolVar(f'present_{t_id}_{h}')
                model.Add(is_present == 1) 
            else:
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
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "message": f"Bentrok: ID {t_id} tidak punya ruang hari yang valid."
            }))
            return

    # --- KENDALA MUTLAK (HANYA MENCEGAH BENTROK) ---
    for k_id, days in intervals_per_kelas.items():
        for h, intervals in days.items():
            if len(intervals) > 1:
                model.AddNoOverlap(intervals)

    for g_id, days in intervals_per_guru.items():
        for h, intervals in days.items():
            if len(intervals) > 1:
                model.AddNoOverlap(intervals)

    # --- KENDALA PENYEBARAN MAPEL DIPERLONGGAR EKSTREM ---
    # Membiarkan guru ngajar 1 mapel beberapa kali dalam sehari jika memang mendesak
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            max_per_day = max(2, math.ceil(len(task_ids) / len(hari_list)) + 1)
            for h in hari_list:
                daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if len(daily_presence) > max_per_day:
                    model.Add(sum(daily_presence) <= max_per_day)

    # Catatan: Aturan Pemerataan Beban Guru (Load Balancing) DIHAPUS.
    # Biar solver murni nyusun balok asal muat.

    # ==========================================
    # 3. PENYELESAIAN (VERSI CEPAT & KASAR)
    # ==========================================
    if all_start_vars:
        # Coba susun dari kiri (jam 1) ke kanan
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 180 # 3 Menit cukup untuk susun kasar
    solver.parameters.num_search_workers = 8    
    
    # Biar solver ga terlalu banyak mikir rute optimal
    solver.parameters.linearization_level = 0
    
    status = solver.Solve(model)
    waktu_komputasi = time.time() - start_time

    # ==========================================
    # 4. FORMATTING OUTPUT KE PHP
    # ==========================================
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
            "waktu_komputasi_detik": round(waktu_komputasi, 2),
            "message": f"Susun balok berhasil! Selesai dalam {waktu_komputasi:.2f} detik."
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "waktu_komputasi_detik": round(waktu_komputasi, 2),
            "message": f"Susun balok gagal (Waktu: {waktu_komputasi:.2f} detik). Grid sudah benar-benar penuh atau ada guru yang tumpang tindih mutlak."
        }))

if __name__ == '__main__':
    main()