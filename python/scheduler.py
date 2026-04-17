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
        with open(json_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": str(e)}))
        return

    raw_assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    
    # Sortir balok durasi besar agar masuk duluan
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    # Ambil data mapel yang di-lock manual
    mapel_busy = {}
    for m in data.get('mapel_constraints', []):
        mapel_busy[m['id']] = m['waktu_kosong']

    hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']

    guru_hari_map = {}
    for g in gurus:
        allowed_days = g.get('hari_mengajar', [])
        if not allowed_days:
            allowed_days = hari_list
        guru_hari_map[g['id']] = allowed_days

    # ==========================================
    # LOGIKA: BACA LIMIT KELAS
    # ==========================================
    kelas_limits = {
        k['id']: {
            'harian': k.get('limit_harian', 10),
            'jumat': k.get('limit_jumat', 8)
        } for k in kelass
    }

    def get_max_jam(kelas_id, hari):
        if hari == 'Jumat':
            return kelas_limits[kelas_id]['jumat']
        return kelas_limits[kelas_id]['harian']

    # ==========================================
    # LOGIKA BARU: RUMUS MATEMATIKA AMAN & CEPAT
    # ==========================================
    total_jam_guru = {g['id']: 0 for g in gurus}
    max_block_guru = {g['id']: 0 for g in gurus}

    for t in raw_assignments:
        g_id = t['guru_id']
        durasi = int(t['jumlah_jam'])
        if g_id in total_jam_guru:
            total_jam_guru[g_id] += durasi
            if durasi > max_block_guru[g_id]:
                max_block_guru[g_id] = durasi

    max_jam_dinamis = {}
    min_jam_dinamis = {}

    for g in gurus:
        g_id = g['id']
        total_jam = total_jam_guru[g_id]
        max_block = max_block_guru[g_id]
        
        hari_aktif_guru = g.get('hari_mengajar', [])
        jumlah_hari_aktif = len(hari_aktif_guru) if hari_aktif_guru else len(hari_list)
        
        if jumlah_hari_aktif > 0:
            rata_atas = math.ceil(total_jam / jumlah_hari_aktif)
            rata_bawah = math.floor(total_jam / jumlah_hari_aktif)
            
            # ATAP: Toleransi +2 (Aman dari crash balok 3-jam)
            limit_max = max(rata_atas + 2, max_block)
            
            # LANTAI: Toleransi -2 (Aman dari njomplang)
            # Jika jam gurunya banyak, kita paksa minimal harinya terisi.
            if total_jam >= jumlah_hari_aktif * 2:
                limit_min = max(1, rata_bawah - 1)
            else:
                limit_min = 0 # Biarkan kosong jika jamnya memang sedikit banget
        else:
            limit_max = 0
            limit_min = 0
            
        max_jam_dinamis[g_id] = limit_max
        min_jam_dinamis[g_id] = limit_min

    # ==========================================
    # 2. MEMBANGUN MODEL
    # ==========================================
    model = cp_model.CpModel()

    starts, presences = {}, {}
    all_start_vars = [] 

    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    durasi_per_kelas_harian = {k['id']: {h: [] for h in hari_list} for k in kelass}
    durasi_per_guru_harian = {g['id']: {h: [] for h in hari_list} for g in gurus} 
    
    tasks_per_mapel_group = {} 
    tasks_metadata = []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id, g_id, k_id = t['id'], t['guru_id'], t['kelas_id']
        m_id = t.get('mapel_id')
        batas_maks_jam = t.get('batas_maksimal_jam') 
        
        tasks_metadata.append({'id': t_id})

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        tasks_per_mapel_group.setdefault(group_key, []).append(t_id)

        possible_days = [] 

        for h in hari_list:
            if h not in guru_hari_map[g_id]: continue 

            batas_jam = get_max_jam(k_id, h)
            if durasi > batas_jam: continue

            max_start = batas_jam - durasi + 1
            if max_start < 1: continue
            
            start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_jam + 1, f'end_{t_id}_{h}')
            is_present = model.NewBoolVar(f'present_{t_id}_{h}')
            
            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'interval_{t_id}_{h}'
            )

            if batas_maks_jam is not None:
                model.Add(end_var <= int(batas_maks_jam) + 1).OnlyEnforceIf(is_present)

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            durasi_per_kelas_harian[k_id][h].append(is_present * durasi)
            durasi_per_guru_harian[g_id][h].append(is_present * durasi) 

            if m_id in mapel_busy:
                for blocked in mapel_busy[m_id]:
                    if blocked['hari'] == h:
                        jam_blok = int(blocked['jam'])
                        if jam_blok <= batas_jam:
                            blocked_interval = model.NewIntervalVar(jam_blok, 1, jam_blok + 1, f'block_{m_id}_{h}')
                            model.AddNoOverlap([interval_var, blocked_interval])

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({"status": "INFEASIBLE", "message": f"Bentrok fatal! Mapel ID {t_id} tidak punya pilihan hari."}))
            return

    # ==========================================
    # 3. KENDALA HARGA MATI (KELAS)
    # ==========================================
    for k in kelass:
        k_id = k['id']
        batas_jumat = kelas_limits[k_id]['jumat']
        batas_harian = kelas_limits[k_id]['harian']
        
        for h in hari_list:
            beban_harian = durasi_per_kelas_harian[k_id][h]
            if not beban_harian: continue
            
            if h in ['Senin', 'Selasa', 'Rabu', 'Kamis']:
                model.Add(sum(beban_harian) == batas_harian)
            elif h == 'Jumat':
                model.Add(sum(beban_harian) == batas_jumat)

    # ==========================================
    # 3.5 KENDALA RENTANG JAM GURU (CEPAT & ANTI NJOMPLANG)
    # ==========================================
    for g in gurus:
        g_id = g['id']
        batas_atas = max_jam_dinamis[g_id]
        batas_bawah = min_jam_dinamis[g_id]
        
        hari_aktif = guru_hari_map[g_id]
        
        for h in hari_list:
            beban_guru = durasi_per_guru_harian[g_id][h]
            if beban_guru:
                # 1. Tahan atasnya (Maksimal)
                model.Add(sum(beban_guru) <= batas_atas)
                
                # 2. Tahan bawahnya (Minimal)
                if h in hari_aktif and batas_bawah > 0:
                    if h == 'Jumat':
                        # Jumat jam fisiknya pendek, beri diskon minimum 1 jam
                        model.Add(sum(beban_guru) >= max(1, batas_bawah - 1))
                    else:
                        model.Add(sum(beban_guru) >= batas_bawah)

    # ==========================================
    # 4. KENDALA DASAR (TIDAK BOLEH BENTROK)
    # ==========================================
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    for g_id in intervals_per_guru:
        for h in hari_list:
            intervals = intervals_per_guru[g_id][h]
            waktu_kosong = next((g.get('waktu_kosong', []) for g in gurus if g['id'] == g_id), [])
            for wk in waktu_kosong:
                if wk['hari'] == h:
                    jam_libur = int(wk['jam'])
                    dummy = model.NewIntervalVar(jam_libur, 1, jam_libur+1, f'libur_guru_{g_id}_{h}_{jam_libur}')
                    intervals.append(dummy)
            if intervals:
                model.AddNoOverlap(intervals)

    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in hari_list:
                daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_presence:
                    model.AddAtMostOne(daily_presence)

    # ==========================================
    # 5. EKSEKUSI PENCARIAN
    # ==========================================
    if all_start_vars:
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

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
                    final_solution.append({
                        'id': t_id,
                        'hari': h,
                        'jam': solver.Value(starts[(t_id, h)])
                    })
                    break
        
        print(json.dumps({
            "status": "OPTIMAL",
            "solution": final_solution,
            "message": f"MANTAP! Jadwal berhasil dibikin RATA KIRI secara cepat dalam {waktu_komputasi:.2f} detik!"
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "message": f"Gagal menyusun (Waktu: {waktu_komputasi:.2f} detik). Jadwal mentok."
        }))

if __name__ == '__main__':
    main()