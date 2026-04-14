import sys
import json
import time
import math
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
        with open(json_path, 'r') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": str(e)}))
        return

    raw_assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    mapel_busy = {}
    for m in data.get('mapel_constraints', []):
        mapel_busy[m['id']] = m['waktu_kosong']

    hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
    
    kelas_limits = {}
    for k in kelass:
        kelas_limits[k['id']] = {
            'normal': int(k.get('limit_harian', 10)),
            'jumat': int(k.get('limit_jumat', 7))
        }

    def get_max_jam(kelas_id, hari):
        limits = kelas_limits.get(kelas_id, {'normal': 10, 'jumat': 7})
        return limits['jumat'] if hari == 'Jumat' else limits['normal']

    guru_hari_map = {}
    for g in gurus:
        allowed_days = g.get('hari_mengajar', [])
        if not allowed_days:
            allowed_days = hari_list
        guru_hari_map[g['id']] = allowed_days

    # --- DETEKSI DINI ---
    for g in gurus:
        g_id = g['id']
        nama_guru = g['nama']
        total_sks_guru = sum(int(t['jumlah_jam']) for t in raw_assignments if t['guru_id'] == g_id)
        
        kapasitas_maksimal = 0
        for h in guru_hari_map[g_id]:
            kapasitas_maksimal += 7 if h == 'Jumat' else 10
            
        if total_sks_guru > kapasitas_maksimal:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "message": f"STOP! Guru '{nama_guru}' punya beban {total_sks_guru} JP, tapi kapasitas harinya cuma muat {kapasitas_maksimal} JP. Tambah hari mengajar beliau!"
            }))
            return

    # ==========================================
    # 2. MEMBANGUN MODEL
    # ==========================================
    model = cp_model.CpModel()

    starts = {}      
    presences = {}   
    all_start_vars = [] 

    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    presences_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    
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

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        if group_key not in tasks_per_mapel_group:
            tasks_per_mapel_group[group_key] = []
        tasks_per_mapel_group[group_key].append(t_id)

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
            presences_per_guru[g_id][h].append((is_present, durasi))

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
            model.Add(sum(possible_days) == 1)
        else:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "message": f"Bentrok fatal! Mapel ID {t_id} tidak bisa masuk ke hari yang diizinkan."
            }))
            return

    # ==========================================
    # 3. CONSTRAINT (ATURAN YANG SUDAH DILONGGARKAN)
    # ==========================================
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    for g in gurus:
        g_id = g['id']
        waktu_kosong = g.get('waktu_kosong', [])
        for h in hari_list:
            intervals = intervals_per_guru[g_id][h]
            for wk in waktu_kosong:
                if wk['hari'] == h:
                    jam_libur = int(wk['jam'])
                    dummy = model.NewIntervalVar(jam_libur, 1, jam_libur+1, 'libur_guru')
                    intervals.append(dummy)
            if intervals:
                model.AddNoOverlap(intervals)

    # ---> PELONGGARAN 1: Distribusi Mapel Fleksibel <---
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            # Cari guru_id nya
            g_id_current = None
            for t in raw_assignments:
                if t['id'] == task_ids[0]:
                    g_id_current = t['guru_id']
                    break
            
            hari_aktif_guru = len(guru_hari_map[g_id_current]) if g_id_current else 5
            
            # Hitung maksimal numpuk. Kalau pecahan ada 3 tapi cuma masuk 1 hari, max = 3
            max_per_hari = 1
            if len(task_ids) > hari_aktif_guru:
                max_per_hari = math.ceil(len(task_ids) / hari_aktif_guru)
            
            for h in hari_list:
                daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_presence:
                    model.Add(sum(daily_presence) <= max_per_hari)

    # ---> PELONGGARAN 2: Pemerataan Beban Fleksibel <---
    for g in gurus:
        g_id = g['id']
        total_sks_guru = sum(int(t['jumlah_jam']) for t in raw_assignments if t['guru_id'] == g_id)
        
        hari_aktif_guru = len(guru_hari_map[g_id])
        if hari_aktif_guru == 0:
            hari_aktif_guru = 5 
            
        # Diberi kelonggaran lebih besar (+ 5) agar jadwal padat tetap bisa masuk, limit dinaikkan
        batas_dinamis_harian = int(total_sks_guru / hari_aktif_guru) + 5
        if batas_dinamis_harian > 10:
            batas_dinamis_harian = 10
            
        for h in hari_list:
            beban_harian = []
            for is_present, durasi in presences_per_guru[g_id][h]:
                beban_harian.append(is_present * durasi)
            
            if beban_harian:
                model.Add(sum(beban_harian) <= batas_dinamis_harian)

    # ==========================================
    # 4. STRATEGI "BLOK SUSUN" & EKSEKUSI
    # ==========================================
    if all_start_vars:
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300 
    solver.parameters.num_search_workers = 8    
    
    status = solver.Solve(model)

    end_time = time.time()  
    waktu_komputasi = end_time - start_time  

    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        final_solution = []
        for t in tasks_metadata:
            t_id = t['id']
            for h in hari_list:
                if (t_id, h) in presences:
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
            "waktu_komputasi_detik": round(waktu_komputasi, 2), 
            "message": f"Jadwal berhasil disusun dalam {waktu_komputasi:.2f} detik!"
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "waktu_komputasi_detik": round(waktu_komputasi, 2),
            "message": f"Gagal menyusun. Cek jadwal guru yang hanya masuk 1-2 hari tapi beban mengajarnya menumpuk di kelas yang sama."
        }))

if __name__ == '__main__':
    main()