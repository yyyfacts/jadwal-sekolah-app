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

    # =====================================================================
    # 1. PERSIAPAN DATA & PENYAMAAN TIPE DATA (SANGAT PENTING)
    # Kita paksa semua ID menjadi String agar tidak ada missmatch saat pencarian limit
    # =====================================================================
    hari_aktif = data.get('hari_aktif', [])
    
    # Konversi keys ke String mutlak!
    gurus = {str(g['id']): g for g in data.get('gurus', [])}
    kelass = {str(k['id']): k for k in data.get('kelass', [])}
    
    semua_assignments = data.get('assignments', [])
    assignments = [a for a in semua_assignments if str(a.get('status', 'offline')).lower() == 'offline']

    # =====================================================================
    # 2. HEURISTIK & FLEKSIBILITAS BEBAN GURU
    # =====================================================================
    beban_guru = collections.defaultdict(int)
    for a in assignments:
        beban_guru[str(a['guru_id'])] += int(a.get('jumlah_jam', 1))

    max_harian_guru = {}
    for g_id, g_info in gurus.items():
        hm = [str(x).lower() for x in g_info.get('hari_mengajar', [])] if g_info else []
        jml_hari = sum(1 for d, day_info in enumerate(hari_aktif) if not hm or str(d + 1) in hm or day_info['nama'].lower() in hm)
        
        jml_hari = max(1, jml_hari) 
        rata_rata = beban_guru[g_id] / jml_hari
        # Kita longgarkan + 3 agar solver tidak gampang nyerah kalau jadwal agak padat
        max_harian_guru[g_id] = max(int(rata_rata) + 3, 5)

    assignments.sort(key=lambda x: (-int(x.get('jumlah_jam', 1)), -beban_guru[str(x['guru_id'])]))

    model = cp_model.CpModel()
    
    presences = {}
    starts = {}
    all_starts = [] 
    
    intervals_per_kelas = collections.defaultdict(list)
    intervals_per_guru = collections.defaultdict(list)
    
    load_per_class_day = collections.defaultdict(list)
    load_per_guru_day = collections.defaultdict(list)

    # =====================================================================
    # 3. PEMBENTUKAN INTERVAL BLOK
    # =====================================================================
    for a in assignments:
        a_id = str(a['id'])
        duration = int(a['jumlah_jam'])
        guru_id = str(a['guru_id'])   # Pastikan String
        kelas_id = str(a['kelas_id']) # Pastikan String
        
        day_vars = []
        
        for d, day_info in enumerate(hari_aktif):
            day_name = day_info['nama'].lower()
            max_jam = int(day_info['max_jam']) 
            
            if duration > max_jam: continue

            g_info = gurus.get(guru_id, {})
            hm = [str(x).lower() for x in g_info.get('hari_mengajar', [])] if g_info else []
            if hm and str(d + 1) not in hm and day_name not in hm: 
                continue

            p = model.NewBoolVar(f'p_{a_id}_{d}')
            s = model.NewIntVar(1, max(1, max_jam - duration + 1), f's_{a_id}_{d}')
            e = model.NewIntVar(1 + duration, max_jam + 1, f'e_{a_id}_{d}')
            
            ival = model.NewOptionalIntervalVar(s, duration, e, p, f'i_{a_id}_{d}')

            day_vars.append(p)
            presences[(a_id, d)] = p
            starts[(a_id, d)] = s
            all_starts.append(s)
            
            intervals_per_kelas[(kelas_id, d)].append(ival)
            intervals_per_guru[(guru_id, d)].append(ival)
            
            load_per_class_day[(kelas_id, d)].append(p * duration)
            load_per_guru_day[(guru_id, d)].append(p * duration)

        if not day_vars:
            print(json.dumps({'status': 'INFEASIBLE', 'message': f'Blok SKS {duration} (ID {a_id}) tidak muat di hari aktif guru.'}))
            sys.exit(0)
            
        model.AddExactlyOne(day_vars)

    # =====================================================================
    # 4. CONSTRAINT: ANTI TUMPANG TINDIH
    # =====================================================================
    for ivals in intervals_per_kelas.values():
        if len(ivals) > 1: model.AddNoOverlap(ivals)
        
    for ivals in intervals_per_guru.values():
        if len(ivals) > 1: model.AddNoOverlap(ivals)

    # =====================================================================
    # 5. CONSTRAINT: LIMIT DINAMIS BACA DARI DATABASE
    # =====================================================================
    # A. Limit Kelas (Sekarang 100% baca settingan database per kelas yang beda-beda)
    for (kelas_id, d), duration_exprs in load_per_class_day.items():
        if not duration_exprs: continue
        day_name = hari_aktif[d]['nama'].lower()
        
        # Karena kelas_id sudah pasti string, dia PASTI akan menemukan data kelasnya
        k_info = kelass.get(kelas_id, {})
        
        # Eksekusi Limit: Beda kelas, beda limitnya. 
        if 'jumat' in day_name:
            limit = int(k_info.get('limit_jumat', 7))
        else:
            limit = int(k_info.get('limit_harian', 10))
            
        model.Add(sum(duration_exprs) <= limit)

    # B. Pemerataan Beban Mengajar Guru yang Lebih Lembut (Soft Bound)
    for (guru_id, d), duration_exprs in load_per_guru_day.items():
        if duration_exprs:
            batas_maksimal = max_harian_guru.get(guru_id, 12)
            model.Add(sum(duration_exprs) <= batas_maksimal)

    # =====================================================================
    # 6. STRATEGI PENCARIAN (TANPA HARDCORE LOCK)
    # =====================================================================
    model.AddDecisionStrategy(
        all_starts, 
        cp_model.CHOOSE_FIRST_UNBOUND, 
        cp_model.SELECT_MIN_VALUE
    )
    
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 500.0
    solver.parameters.num_search_workers = 8
    
    # FIXED_SEARCH DIHAPUS!
    # Algoritma sekarang akan menggunakan Heuristik sebagai "Saran Prioritas", 
    # tapi dia diizinkan untuk mencari jalan keluar lain jika bertemu kelas dengan limit sempit.

    status = solver.Solve(model)

    # =====================================================================
    # 7. HASIL (OUTPUT JSON)
    # =====================================================================
    if status in [cp_model.OPTIMAL, cp_model.FEASIBLE]:
        solution = []
        for a in assignments:
            a_id = str(a['id'])
            for d, day_info in enumerate(hari_aktif):
                if (a_id, d) in presences and solver.Value(presences[(a_id, d)]):
                    solution.append({
                        'id': a['id'], # Kembalikan sebagai tipe asli agar Laravel tidak error
                        'hari': day_info['nama'], 
                        'jam': solver.Value(starts[(a_id, d)])
                    })
                    break
        print(json.dumps({'status': solver.StatusName(status), 'message': 'Jadwal fleksibel sukses dirakit! Limit kelas dinamis berhasil diterapkan.', 'solution': solution}))
    else:
        print(json.dumps({'status': solver.StatusName(status), 'message': 'Infeasible: Terjadi kemacetan total. Ada kelas yang total SKS-nya melampaui gabungan limit harian + jumat.'}))

if __name__ == '__main__':
    main()