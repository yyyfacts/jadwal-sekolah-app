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

    mapel_busy = {}
    for m in data.get('mapel_constraints', []):
        mapel_busy[m['id']] = m['waktu_kosong']

    hari_info = data.get('hari_aktif', [])
    hari_list = [h['nama'] for h in hari_info]
    max_jam_map = {h['nama']: int(h['max_jam']) for h in hari_info}
    
    kelas_limits = {k['id']: {'normal': int(k.get('limit_harian', 10)), 'jumat': int(k.get('limit_jumat', 7))} for k in kelass}

    # =================================================================
    # 🚨 FITUR BARU: PRE-CHECK DENGAN SISTEM REKOMENDASI 🚨
    # =================================================================
    
    # 1. DETEKSI KELAS OVERLOAD (Kelebihan Beban SKS)
    beban_kelas_total = {}
    for t in raw_assignments:
        k_id = t['kelas_id']
        beban_kelas_total[k_id] = beban_kelas_total.get(k_id, 0) + int(t['jumlah_jam'])

    for k in kelass:
        k_id = k['id']
        nama_kelas = k['nama_kelas']
        kapasitas_seminggu = sum([kelas_limits[k_id]['jumat'] if str(h).lower() == 'jumat' else kelas_limits[k_id]['normal'] for h in hari_list])
        beban = beban_kelas_total.get(k_id, 0)
        
        if beban > kapasitas_seminggu:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "waktu_komputasi_detik": 0,
                "error_code": "CLASS_OVERLOAD",
                "target_error": nama_kelas,
                "message": f"Kapasitas Kelas {nama_kelas} Kepenuhan (Beban: {beban} JP, Kapasitas Maks: {kapasitas_seminggu} JP).",
                "rekomendasi": f"1. Masuk ke menu Data Kelas, edit kelas {nama_kelas} dan naikkan Limit Harian/Jumatnya minimal total {beban} JP.\n2. Atau, hapus sebagian mata pelajaran di kelas {nama_kelas} agar total beban berkurang."
            }))
            return

    # 2. DETEKSI GURU OVERLOAD (Kelebihan Beban Mengajar)
    beban_guru_total = {}
    for t in raw_assignments:
        g_id = t['guru_id']
        beban_guru_total[g_id] = beban_guru_total.get(g_id, 0) + int(t['jumlah_jam'])

    for g in gurus:
        g_id = g['id']
        nama_guru = g['nama']
        hari_mengajar = g.get('hari_mengajar', []) or hari_list
        kapasitas_guru = sum([max_jam_map.get(h, 10) for h in hari_mengajar if h in hari_list])
        waktu_kosong = len([wk for wk in g.get('waktu_kosong', []) if wk['hari'] in hari_list])
        kapasitas_guru -= waktu_kosong
        
        beban = beban_guru_total.get(g_id, 0)
        if beban > kapasitas_guru:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "waktu_komputasi_detik": 0,
                "error_code": "TEACHER_OVERLOAD",
                "target_error": nama_guru,
                "message": f"Beban Mengajar {nama_guru} Terlalu Banyak (Beban: {beban} JP, Waktu Tersedia: {kapasitas_guru} JP).",
                "rekomendasi": f"1. Masuk ke menu Data Guru, edit {nama_guru} dan tambah jumlah 'Hari Mengajar' atau hapus 'Waktu Kosong' (jam blokir) beliau.\n2. Atau, kurangi jam mengajar {nama_guru} dan oper ke guru lain."
            }))
            return

    # 3. DETEKSI DURASI KEPANJANGAN
    max_daily = max(max_jam_map.values()) if max_jam_map else 0
    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi > max_daily:
            t_id = t['id']
            print(json.dumps({
                "status": "INFEASIBLE", 
                "waktu_komputasi_detik": 0,
                "error_code": "DURATION_TOO_LONG",
                "target_error": f"Mapel ID: {t_id}",
                "message": f"Ada mapel yang SKS-nya ({durasi} JP) lebih panjang dari jam masuk sekolah ({max_daily} JP).",
                "rekomendasi": "Cek menu plotting jadwal, cari mata pelajaran yang durasi gabungannya kelewat batas. Pecah durasi mapel tersebut (Misal: 5 JP dipecah jadi 3 JP dan 2 JP)."
            }))
            return

    # =================================================================
    # PROSES AI: MEMBANGUN MODEL CP-SAT
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

            if m_id in mapel_busy:
                for blocked in mapel_busy[m_id]:
                    if blocked['hari'] == h:
                        jam_blok = int(blocked['jam'])
                        if jam_blok <= batas_jam:
                            blocked_interval = model.NewIntervalVar(jam_blok, 1, jam_blok + 1, f'block_{m_id}_{h}')
                            model.AddNoOverlap([interval_var, blocked_interval])

        if possible_days:
            model.Add(sum(possible_days) == 1)
        else:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "waktu_komputasi_detik": 0, 
                "error_code": "MAPEL_UNPLACEABLE",
                "target_error": f"Mapel ID: {t_id}",
                "message": f"Mapel ID {t_id} tidak bisa ditempatkan di hari apapun.",
                "rekomendasi": "Periksa apakah Guru yang mengajar mapel ini memiliki jadwal kosong (Libur) yang memblokir seluruh hari mengajarnya."
            }))
            return

    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]: model.AddNoOverlap(intervals_per_kelas[k_id][h])
            
            limit_aktif = kelas_limits[k_id]['jumat'] if str(h).lower() == 'jumat' else kelas_limits[k_id]['normal']
            beban_kelas = [is_p * dur for is_p, dur in presences_per_kelas[k_id][h]]
            if beban_kelas: model.Add(sum(beban_kelas) <= limit_aktif)

    for g in gurus:
        g_id, waktu_kosong = g['id'], g.get('waktu_kosong', [])
        for h in hari_list:
            intervals = intervals_per_guru[g_id][h]
            for wk in waktu_kosong:
                if wk['hari'] == h:
                    jam_libur = int(wk['jam'])
                    intervals.append(model.NewIntervalVar(jam_libur, 1, jam_libur+1, 'libur_guru'))
            if intervals: model.AddNoOverlap(intervals)

    # ATURAN MAPEL DOBEL (Sudah diset <= 2 agar lebih fleksibel)
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
        # JIKA LOLOS PRE-CHECK TAPI MASIH GAGAL (DEADLOCK TETRIS)
        print(json.dumps({
            "status": "INFEASIBLE", 
            "waktu_komputasi_detik": round(waktu_komputasi, 2), 
            "error_code": "CSP_DEADLOCK",
            "target_error": "Sistem Puzzle AI",
            "message": "AI gagal menyusun jadwal karena terjadi deadlock (jalan buntu). Kepingan SKS tidak muat di sisa jam yang ada.",
            "rekomendasi": "1. Terlalu banyak mapel yang diinput pecah-pecah (misal: 6x @1 JP), gabungkan menjadi (2x @3 JP).\n2. Longgarkan batasan hari mengajar guru-guru yang jam mengajarnya (SKS) sangat padat."
        }))

if __name__ == '__main__': main()