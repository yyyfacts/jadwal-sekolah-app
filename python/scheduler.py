import sys
import json
import collections
from ortools.sat.python import cp_model

def main():
    # Pastikan script menerima argumen path file JSON
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

    # Dictionary untuk lookup cepat
    guru_dict = {g['id']: g for g in gurus}
    kelas_limits = {k['id']: k for k in kelass}

    # Inisialisasi Model CP-SAT
    model = cp_model.CpModel()

    # Struktur penyimpanan variabel solver
    presences = {}
    starts = {}
    intervals = {}

    intervals_per_kelas = collections.defaultdict(list)
    intervals_per_guru = collections.defaultdict(list)
    assignments_per_class_day = collections.defaultdict(list)

    # 1. PEMBUATAN VARIABEL & CONSTRAINT DASAR
    for a in assignments:
        a_id = a['id']
        duration = int(a['jumlah_jam'])
        guru_id = a['guru_id']
        kelas_id = a['kelas_id']

        day_vars = []

        for d, day_info in enumerate(hari_aktif):
            max_jam = int(day_info['max_jam'])
            day_name = day_info['nama']

            # Validasi 1: Apakah durasi muat di hari ini?
            if duration > max_jam:
                continue 

            # Validasi 2: Apakah Guru bersedia mengajar di hari ini?
            # Kita cek array hari_mengajar (mengakomodasi ID hari "1" atau Nama Hari "Senin")
            guru_info = guru_dict.get(guru_id)
            if guru_info and guru_info.get('hari_mengajar'):
                hm_strs = [str(x).lower() for x in guru_info['hari_mengajar']]
                day_idx_str = str(d + 1)
                if day_idx_str not in hm_strs and day_name.lower() not in hm_strs:
                    continue # Lewati hari ini karena guru libur

            # Variabel Boolean: Apakah jadwal ini ditaruh di hari 'd'?
            p = model.NewBoolVar(f'presence_a{a_id}_d{d}')
            day_vars.append(p)
            presences[(a_id, d)] = p

            # Variabel Integer: Jam mulai (dari jam ke-1 sampai batas max_jam - durasi agar tidak melebihi jadwal)
            max_start = max_jam - duration + 1
            if max_start < 1: max_start = 1
            s = model.NewIntVar(1, max_start, f'start_a{a_id}_d{d}')
            starts[(a_id, d)] = s

            # Variabel Integer: Jam selesai
            e = model.NewIntVar(1 + duration, max_jam + 1, f'end_a{a_id}_d{d}')

            # Optional Interval (Hanya aktif jika 'p' = 1/True)
            ival = model.NewOptionalIntervalVar(s, duration, e, p, f'interval_a{a_id}_d{d}')
            intervals[(a_id, d)] = ival

            # Tambahkan ke dictionary untuk pengecekan No-Overlap nanti
            intervals_per_kelas[(kelas_id, d)].append(ival)
            intervals_per_guru[(guru_id, d)].append(ival)

            # Tambahkan ke daftar perhitungan batas jam harian kelas
            assignments_per_class_day[(kelas_id, d)].append(p * duration)

        # Jika day_vars kosong, artinya jadwal ini tidak punya tempat sama sekali
        if not day_vars:
            print(json.dumps({
                'status': 'INFEASIBLE', 
                'message': f'Gagal! Jadwal Mapel ID {a_id} tidak bisa ditaruh di hari manapun. Cek durasi JP atau ketersediaan hari Guru.'
            }))
            sys.exit(0)

        # Constraint: Jadwal ini HANYA BOLEH ditempatkan tepat di 1 hari saja
        model.AddExactlyOne(day_vars)


    # 2. CONSTRAINT NO-OVERLAP (Tidak boleh bentrok)
    # 2.a. Kelas tidak boleh belajar 2 mapel bersamaan
    for (kelas_id, d), ivals in intervals_per_kelas.items():
        if len(ivals) > 1:
            model.AddNoOverlap(ivals)

    # 2.b. Guru tidak boleh mengajar di 2 kelas bersamaan
    for (guru_id, d), ivals in intervals_per_guru.items():
        if len(ivals) > 1:
            model.AddNoOverlap(ivals)


    # 3. CONSTRAINT LIMIT HARIAN KELAS
    for (kelas_id, d), duration_exprs in assignments_per_class_day.items():
        if not duration_exprs: continue

        day_name = hari_aktif[d]['nama'].lower()
        k_info = kelas_limits.get(kelas_id)
        
        if k_info:
            # Gunakan limit Jumat jika hari adalah Jumat
            limit = int(k_info['limit_jumat']) if 'jumat' in day_name else int(k_info['limit_harian'])
            model.Add(sum(duration_exprs) <= limit)


    # 4. SOLVE MODEL
    solver = cp_model.CpSolver()
    # Opsional: Batasi waktu pencarian maksimal (dalam detik)
    solver.parameters.max_time_in_seconds = 600.0 
    
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
                        'hari': day_info['nama'], # Kirim balik nama hari
                        'jam': start_time         # Jam mengajar fisik
                    })
                    break # Lanjut ke assignment berikutnya

        status_name = solver.StatusName(status)
        print(json.dumps({
            'status': status_name,
            'message': f'Jadwal berhasil diselesaikan dengan status: {status_name}!',
            'solution': solution
        }))
    else:
        print(json.dumps({
            'status': solver.StatusName(status),
            'message': 'Gagal (INFEASIBLE): Algoritma tidak menemukan solusi jadwal yang memungkinkan. Silakan longgarkan batasan jam harian kelas atau ketersediaan hari mengajar guru.'
        }))

if __name__ == '__main__':
    # HINDARI PRINT APAPUN KECUALI JSON (Agar tidak merusak respon di Laravel)
    main()