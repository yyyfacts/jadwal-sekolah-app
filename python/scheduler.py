import sys
import json
import time
from ortools.sat.python import cp_model

def main():
    start_time = time.time()

    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "message": "Path JSON dibutuhkan!"}))
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

    # 1. PENGURUTAN: Susun dari mapel durasi paling panjang (LJF)
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
    
    # 2. KAPASITAS: Mengambil batas kotak belajar
    kapasitas_master = {h['nama']: int(h['max_jam']) for h in hari_aktif_data}
    kelas_limits = {k['id']: {'normal': int(k.get('limit_harian', 10)), 'jumat': int(k.get('limit_jumat', 7))} for k in kelass}

    guru_hari_map = {}
    for g in gurus:
        ad = g.get('hari_mengajar', [])
        guru_hari_map[g['id']] = ad if ad else hari_list

    model = cp_model.CpModel()

    starts = {}      
    presences = {}   
    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}

    # 3. PEMBUATAN VARIABEL (Hanya aturan dasar tanpa pernak-pernik)
    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        tid, gid, kid = t['id'], t['guru_id'], t['kelas_id']

        possible_days = []
        for h in hari_list:
            if h not in guru_hari_map[gid]: 
                continue

            # Ambil nilai terkecil antara Master Hari vs Limit Kelas (Biar aman)
            master = kapasitas_master.get(h, 10)
            limit_k = kelas_limits.get(kid, {'normal': 10, 'jumat': 7})
            batas = limit_k['jumat'] if h == 'Jumat' else limit_k['normal']
            batas_akhir = min(master, batas)

            if durasi > batas_akhir: 
                continue

            max_start = batas_akhir - durasi + 1
            if max_start < 1: 
                continue

            # Bikin slot jam
            sv = model.NewIntVar(1, max_start, f's_{tid}_{h}')
            ev = model.NewIntVar(1 + durasi, batas_akhir + 1, f'e_{tid}_{h}')
            p = model.NewBoolVar(f'p_{tid}_{h}')
            iv = model.NewOptionalIntervalVar(sv, durasi, ev, p, f'i_{tid}_{h}')

            starts[(tid, h)] = sv
            presences[(tid, h)] = p
            possible_days.append(p)
            
            intervals_per_kelas[kid][h].append(iv)
            intervals_per_guru[gid][h].append(iv)

        # Mapel WAJIB masuk minimal di satu hari
        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "message": f"FATAL: Mapel ID {tid} (Durasi {durasi} JP) tidak muat ditaruh di hari manapun! Cek jadwal khusus guru ini."
            }))
            return

    # 4. ANTI-BENTROK (Satu-satunya batasan keras yang kita pakai)
    for kid in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[kid][h]:
                model.AddNoOverlap(intervals_per_kelas[kid][h])

    for gid in intervals_per_guru:
        for h in hari_list:
            if intervals_per_guru[gid][h]:
                model.AddNoOverlap(intervals_per_guru[gid][h])

    # 5. GRAVITASI HALUS: Maksa jadwal mepet ke jam ke-1 tanpa bikin error
    model.Minimize(sum(starts.values()))

    # 6. EKSEKUSI SOLVER
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300
    solver.parameters.num_search_workers = 8
    
    status = solver.Solve(model)
    waktu = time.time() - start_time

    # 7. HASIL
    if status in [cp_model.OPTIMAL, cp_model.FEASIBLE]:
        sol = []
        for t in raw_assignments:
            tid = t['id']
            for h in hari_list:
                if (tid, h) in presences and solver.Value(presences[(tid, h)]):
                    sol.append({
                        'id': tid, 
                        'hari': h, 
                        'jam': solver.Value(starts[(tid, h)])
                    })
                    break
        print(json.dumps({
            "status": "OPTIMAL", 
            "solution": sol, 
            "waktu_komputasi_detik": round(waktu, 2), 
            "message": "BERHASIL ALHAMDULILLAH! Jadwal jadi tanpa bentrok!"
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "message": "Masih Infeasible Mas. Berarti MURNI ada Guru yang jam ngajarnya over-limit dari kapasitas sekolah!"
        }))

if __name__ == '__main__':
    main()