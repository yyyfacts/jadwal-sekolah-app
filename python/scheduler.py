import sys
import json
import collections
from ortools.sat.python import cp_model

def main():
    if len(sys.argv) < 2:
        print(json.dumps({'status': 'ERROR', 'message': 'Path file JSON tidak ditemukan.'}))
        sys.exit(1)

    json_path = sys.argv[1]

    try:
        with open(json_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({'status': 'ERROR', 'message': f'Gagal membaca JSON: {str(e)}'}))
        sys.exit(1)

    hari_aktif = data.get('hari_aktif', [])
    gurus = data.get('gurus', [])
    kelass = data.get('kelass', [])
    assignments = data.get('assignments', [])

    guru_dict = {g['id']: g for g in gurus}
    kelas_limits = {k['id']: k for k in kelass}

    model = cp_model.CpModel()

    presences = {}
    starts = {}
    intervals = {}

    intervals_per_kelas = collections.defaultdict(list)
    intervals_per_guru = collections.defaultdict(list)
    assignments_per_class_day = collections.defaultdict(list)

    # 1. PEMBUATAN VARIABEL
    for a in assignments:
        a_id = a['id']
        duration = int(a['jumlah_jam'])
        guru_id = a['guru_id']
        kelas_id = a['kelas_id']

        day_vars = []

        for d, day_info in enumerate(hari_aktif):
            max_jam = int(day_info['max_jam'])
            day_name = day_info['nama']

            if duration > max_jam:
                continue 

            guru_info = guru_dict.get(guru_id)
            if guru_info and guru_info.get('hari_mengajar'):
                hm_strs = [str(x).lower() for x in guru_info['hari_mengajar']]
                day_idx_str = str(d + 1)
                if day_idx_str not in hm_strs and day_name.lower() not in hm_strs:
                    continue 

            p = model.NewBoolVar(f'presence_a{a_id}_d{d}')
            day_vars.append(p)
            presences[(a_id, d)] = p

            max_start = max_jam - duration + 1
            if max_start < 1: max_start = 1
            s = model.NewIntVar(1, max_start, f'start_a{a_id}_d{d}')
            starts[(a_id, d)] = s

            e = model.NewIntVar(1 + duration, max_jam + 1, f'end_a{a_id}_d{d}')

            ival = model.NewOptionalIntervalVar(s, duration, e, p, f'interval_a{a_id}_d{d}')
            intervals[(a_id, d)] = ival

            intervals_per_kelas[(kelas_id, d)].append(ival)
            intervals_per_guru[(guru_id, d)].append(ival)
            assignments_per_class_day[(kelas_id, d)].append(p * duration)

        if not day_vars:
            print(json.dumps({
                'status': 'INFEASIBLE', 
                'message': f'Gagal! Jadwal Mapel ID {a_id} tidak bisa ditaruh di hari manapun. Cek durasi atau kesediaan hari guru.'
            }))
            sys.exit(0)

        model.AddExactlyOne(day_vars)

    # 2. CONSTRAINT NO-OVERLAP
    for (kelas_id, d), ivals in intervals_per_kelas.items():
        if len(ivals) > 1:
            model.AddNoOverlap(ivals)

    for (guru_id, d), ivals in intervals_per_guru.items():
        if len(ivals) > 1:
            model.AddNoOverlap(ivals)

    # 3. CONSTRAINT LIMIT HARIAN KELAS
    for (kelas_id, d), duration_exprs in assignments_per_class_day.items():
        if not duration_exprs: continue
        day_name = hari_aktif[d]['nama'].lower()
        k_info = kelas_limits.get(kelas_id)
        if k_info:
            limit = int(k_info['limit_jumat']) if 'jumat' in day_name else int(k_info['limit_harian'])
            model.Add(sum(duration_exprs) <= limit)

   # 4. SOLVE MODEL & OPTIMASI (PENYESUAIAN SERVER CLOUD/RENDER)
    solver = cp_model.CpSolver()
    
    # 1. Batasi waktu sangat ketat (Max 45 Detik)
    # Render akan memutus koneksi web secara paksa di detik 60.
    # Kita paksa Python berhenti mencari di detik ke-45 agar sempat mengembalikan
    # pesan JSON ke Laravel sebelum diputus oleh Render.
    solver.parameters.max_time_in_seconds = 45.0 
    
    # 2. Turunkan beban RAM (Max 2 Core saja)
    # Agar server Render tidak crash (Out of Memory) yang menyebabkan Error 502.
    solver.parameters.num_search_workers = 2 

    status = solver.Solve(model)

    # 5. KEMBALIKAN HASIL KE LARAVEL
    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        solution = []
        for a in assignments:
            a_id = a['id']
            for d, day_info in enumerate(hari_aktif):
                if (a_id, d) in presences and solver.Value(presences[(a_id, d)]):
                    start_time = solver.Value(starts[(a_id, d)])
                    solution.append({
                        'id': a_id,
                        'hari': day_info['nama'], 
                        'jam': start_time         
                    })
                    break 

        status_name = solver.StatusName(status)
        print(json.dumps({
            'status': status_name,
            'message': f'Jadwal berhasil diselesaikan ({status_name})!',
            'solution': solution
        }))
    elif status == cp_model.UNKNOWN:
        print(json.dumps({
            'status': 'UNKNOWN',
            'message': 'Gagal: Waktu habis (Timeout 45 detik server). Jadwal saat ini terlalu kompleks untuk server ini. Coba longgarkan ketersediaan hari Guru.'
        }))
    else:
        print(json.dumps({
            'status': solver.StatusName(status),
            'message': 'Gagal (INFEASIBLE): Algoritma tidak menemukan solusi jadwal. Pastikan total beban jam tidak melebihi kapasitas.'
        }))

if __name__ == '__main__':
    main()

    # 5. KEMBALIKAN HASIL KE LARAVEL
    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        solution = []
        for a in assignments:
            a_id = a['id']
            for d, day_info in enumerate(hari_aktif):
                if (a_id, d) in presences and solver.Value(presences[(a_id, d)]):
                    start_time = solver.Value(starts[(a_id, d)])
                    solution.append({
                        'id': a_id,
                        'hari': day_info['nama'], 
                        'jam': start_time         
                    })
                    break 

        status_name = solver.StatusName(status)
        print(json.dumps({
            'status': status_name,
            'message': f'Jadwal berhasil diselesaikan ({status_name})!',
            'solution': solution
        }))
    elif status == cp_model.UNKNOWN:
        print(json.dumps({
            'status': 'UNKNOWN',
            'message': 'Gagal: Waktu pencarian habis (Timeout 9 menit)! Aturan jadwal saat ini terlalu kompleks atau mustahil disatukan. Coba kurangi batasan ketersediaan hari Guru atau naikkan Max Jam.'
        }))
    else:
        print(json.dumps({
            'status': solver.StatusName(status),
            'message': 'Gagal (INFEASIBLE): Algoritma tidak menemukan solusi jadwal. Pastikan total jam mengajar guru tidak melebihi sisa slot yang ada.'
        }))

if __name__ == '__main__':
    main()