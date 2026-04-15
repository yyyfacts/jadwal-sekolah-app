import sys
import json
import collections
from ortools.sat.python import cp_model

def main():
    if len(sys.argv) < 2:
        print(json.dumps({'status': 'ERROR', 'message': 'Path file JSON tidak ditemukan.'}))
        sys.exit(1)

    try:
        with open(sys.argv[1], 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({'status': 'ERROR', 'message': f'Gagal membaca JSON: {str(e)}'}))
        sys.exit(1)

    # 1. SETUP DATA (Langsung dari JSON yang dikirim PHP)
    hari_aktif = data.get('hari_aktif', [])
    gurus = {g['id']: g for g in data.get('gurus', [])}
    kelass = {k['id']: k for k in data.get('kelass', [])}
    assignments = data.get('assignments', [])

    # Hitung total jam guru untuk prioritas pengurutan
    beban_guru = collections.defaultdict(int)
    for a in assignments:
        beban_guru[a['guru_id']] += int(a.get('jumlah_jam', 1))

    # URUTKAN: SKS Terbesar dulu -> Guru beban terberat (Biar yang susah-susah masuk duluan)
    assignments.sort(key=lambda x: (-int(x.get('jumlah_jam', 1)), -beban_guru[x['guru_id']]))

    model = cp_model.CpModel()
    
    presences = {}
    starts = {}
    
    intervals_per_kelas = collections.defaultdict(list)
    intervals_per_guru = collections.defaultdict(list)
    offline_load_per_class_day = collections.defaultdict(list)
    
    all_starts = []

    # 2. PEMBUATAN BLOK JADWAL
    for a in assignments:
        a_id = a['id']
        duration = int(a['jumlah_jam'])
        guru_id = a['guru_id']
        kelas_id = a['kelas_id']
        status = str(a.get('status', 'offline')).lower()
        
        day_vars = []
        
        for d, day_info in enumerate(hari_aktif):
            day_name = day_info['nama']
            max_jam = int(day_info['max_jam']) # Total slot 'Belajar' di hari ini
            
            # Cek 1: Durasi muat ga di sisa hari ini?
            if duration > max_jam:
                continue

            # Cek 2: Apakah Guru bersedia mengajar di hari ini?
            g_info = gurus.get(guru_id, {})
            hm = [str(x).lower() for x in g_info.get('hari_mengajar', [])] if g_info else []
            if hm and str(d + 1) not in hm and day_name.lower() not in hm: 
                continue

            # Variabel Penempatan
            p = model.NewBoolVar(f'p_{a_id}_{d}')
            
            # Jam Mulai & Interval (Otomatis menyambung: Start -> End)
            s = model.NewIntVar(1, max(1, max_jam - duration + 1), f's_{a_id}_{d}')
            e = model.NewIntVar(1 + duration, max_jam + 1, f'e_{a_id}_{d}')
            ival = model.NewOptionalIntervalVar(s, duration, e, p, f'i_{a_id}_{d}')

            day_vars.append(p)
            presences[(a_id, d)] = p
            starts[(a_id, d)] = s
            all_starts.append(s)
            
            intervals_per_kelas[(kelas_id, d)].append(ival)
            intervals_per_guru[(guru_id, d)].append(ival)
            
            # Limit harian fisik hanya berlaku untuk mapel OFFLINE
            if status == 'offline':
                offline_load_per_class_day[(kelas_id, d)].append(p * duration)

        if not day_vars:
            print(json.dumps({'status': 'INFEASIBLE', 'message': f'Jadwal ID {a_id} SKS {duration} tidak ada slot hari yang cukup.'}))
            sys.exit(0)
            
        model.AddExactlyOne(day_vars)

    # 3. RULE UTAMA (ANTI-BENTROK & LIMIT FISIK)
    for ivals in intervals_per_kelas.values():
        if len(ivals) > 1: model.AddNoOverlap(ivals)
        
    for ivals in intervals_per_guru.values():
        if len(ivals) > 1: model.AddNoOverlap(ivals)

    # Terapkan Limit Harian (Hanya menghitung durasi Offline)
    for (kelas_id, d), duration_exprs in offline_load_per_class_day.items():
        if not duration_exprs: continue
        day_name = hari_aktif[d]['nama'].lower()
        k_info = kelass.get(kelas_id, {})
        limit = int(k_info.get('limit_jumat', 7)) if 'jumat' in day_name else int(k_info.get('limit_harian', 10))
        model.Add(sum(duration_exprs) <= limit)

    # 4. EKSEKUSI SOLVER (TANPA NEBAK-NEBAK)
    # Paksa susun dari jam terawal (paling pagi) secara berurutan
    model.AddDecisionStrategy(all_starts, cp_model.CHOOSE_FIRST_UNBOUND, cp_model.SELECT_MIN_VALUE)
    
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 500.0
    solver.parameters.num_search_workers = 8
    solver.parameters.search_branching = cp_model.FIXED_SEARCH

    status = solver.Solve(model)

    if status in [cp_model.OPTIMAL, cp_model.FEASIBLE]:
        solution = []
        for a in assignments:
            a_id = a['id']
            for d, day_info in enumerate(hari_aktif):
                if (a_id, d) in presences and solver.Value(presences[(a_id, d)]):
                    solution.append({
                        'id': a_id,
                        'hari': day_info['nama'], 
                        'jam': solver.Value(starts[(a_id, d)]) # Waktu Mulai (Jam Ke-)
                    })
                    break
        print(json.dumps({'status': solver.StatusName(status), 'message': 'Jadwal Pintar berhasil disusun sempurna.', 'solution': solution}))
    else:
        print(json.dumps({'status': solver.StatusName(status), 'message': 'Gagal: Sulit menemukan kombinasi tanpa bentrok dengan sisa slot.'}))

if __name__ == '__main__':
    main()