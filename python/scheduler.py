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

    # Sortir: Mapel durasi besar diutamakan duluan oleh mesin
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    mapel_busy = {}
    for m in data.get('mapel_constraints', []):
        mapel_busy[m['id']] = m['waktu_kosong']

    hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
    
    # =========================================================
    # PERBAIKAN UTAMA: AMBIL KAPASITAS DARI KOTAK "BELAJAR" ASLI
    # =========================================================
    hari_aktif_data = data.get('hari_aktif', [])
    kapasitas_hari = {}
    for h in hari_aktif_data:
        # Ini mengambil total kotak 'Belajar' yang sudah difilter oleh PHP
        kapasitas_hari[h['nama']] = int(h['max_jam'])

    def get_max_jam(kelas_id, hari):
        # Sekarang AI 100% percaya pada jumlah kotak 'Belajar' di web!
        # Kalau Jumat Mas set 9 kotak Belajar, AI pakai 9. Nggak ada lagi patokan angka 7!
        return kapasitas_hari.get(hari, 10) # default 10 jika error

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
            # Deteksi dini juga pakai perhitungan kotak 'Belajar' asli
            kapasitas_maksimal += kapasitas_hari.get(h, 10)
            
        if total_sks_guru > kapasitas_maksimal:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "message": f"STOP! Guru '{nama_guru}' punya beban {total_sks_guru} JP, tapi kapasitas mengajarnya cuma muat {kapasitas_maksimal} JP. Tambah hari mengajar beliau!"
            }))
            return

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
        
        tasks_metadata.append({'id': t_id, 'durasi': durasi})

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

            # --- PROTEKSI ERROR STRING KOSONG DARI DATABASE ---
            batas_jam_dari_db = t.get('batas_maksimal_jam')
            if batas_jam_dari_db is not None and str(batas_jam_dari_db).strip() != "":
                try:
                    b_jam = int(batas_jam_dari_db)
                    model.Add(end_var <= (b_jam + 1)).OnlyEnforceIf(is_present)
                except ValueError:
                    pass 
            # --------------------------------------------------

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
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "message": f"Gagal: Mapel ID {t_id} tidak bisa masuk ke hari mana pun. Cek jadwal guru!"
            }))
            return

    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    for g in gurus:
        g_id = g['id']
        for h in hari_list:
            if intervals_per_guru[g_id][h]:
                model.AddNoOverlap(intervals_per_guru[g_id][h])

    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            g_id_current = next((t['guru_id'] for t in raw_assignments if t['id'] == task_ids[0]), None)
            hari_aktif_guru = len(guru_hari_map[g_id_current]) if g_id_current else 5
            max_per_hari = math.ceil(len(task_ids) / hari_aktif_guru)
            
            for h in hari_list:
                daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_presence:
                    model.Add(sum(daily_presence) <= max_per_hari)

    # ==========================================
    # OBJEKTIF: MEMAKSA AI MERAPATKAN JADWAL KE PAGI HARI
    # ==========================================
    objective_terms = []
    
    for t in tasks_metadata:
        t_id = t['id']
        durasi = t['durasi']
        
        actual_start = model.NewIntVar(1, 15, f'actual_start_{t_id}')
        for h in hari_list:
            if (t_id, h) in presences:
                model.Add(actual_start == starts[(t_id, h)]).OnlyEnforceIf(presences[(t_id, h)])
        
        weight = durasi * durasi 
        objective_terms.append(actual_start * weight)

    model.Minimize(sum(objective_terms))

    # ==========================================
    # EKSEKUSI
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
            "message": f"Berhasil disusun rapat dalam {waktu_komputasi:.2f} detik!"
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "waktu_komputasi_detik": round(waktu_komputasi, 2),
            "message": f"Data terlalu ketat untuk diselesaikan tanpa bentrok! (Cek apakah ada guru yang bentrok murni di jam yang sama)."
        }))

if __name__ == '__main__':
    main()