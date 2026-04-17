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
    kelass          = data.get('kelass', [])
    gurus           = data.get('gurus', [])

    # Sortir balok durasi besar agar masuk duluan (bantu solver)
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    # Ambil data mapel yang di-lock manual
    mapel_busy = {}
    for m in data.get('mapel_constraints', []):
        mapel_busy[m['id']] = m['waktu_kosong']

    hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']

    # Hari aktif tiap guru
    guru_hari_map = {}
    for g in gurus:
        allowed_days = g.get('hari_mengajar', [])
        if not allowed_days:
            allowed_days = hari_list[:]
        guru_hari_map[g['id']] = allowed_days

    # ==========================================
    # LOGIKA: BACA LIMIT KELAS
    # ==========================================
    kelas_limits = {
        k['id']: {
            'harian': k.get('limit_harian', 10),
            'jumat':  k.get('limit_jumat', 8)
        }
        for k in kelass
    }

    def get_max_jam(kelas_id, hari):
        if hari == 'Jumat':
            return kelas_limits[kelas_id]['jumat']
        return kelas_limits[kelas_id]['harian']

    # ==========================================
    # RUMUS MATEMATIKA BARU: SPREAD ADAPTIF
    # Tujuan: distribusi merata seperti 8 8 7 6 6
    # tanpa error akibat limit atas/bawah terlalu ketat
    # ==========================================
    total_jam_guru = {g['id']: 0 for g in gurus}
    max_block_guru = {g['id']: 0 for g in gurus}

    for t in raw_assignments:
        g_id  = t['guru_id']
        durasi = int(t['jumlah_jam'])
        if g_id in total_jam_guru:
            total_jam_guru[g_id] += durasi
            if durasi > max_block_guru[g_id]:
                max_block_guru[g_id] = durasi

    max_jam_dinamis = {}
    min_jam_dinamis = {}

    for g in gurus:
        g_id      = g['id']
        total_jam = total_jam_guru[g_id]
        max_block = max_block_guru[g_id]

        hari_aktif = g.get('hari_mengajar', [])
        n = len(hari_aktif) if hari_aktif else len(hari_list)

        if n == 0 or total_jam == 0:
            max_jam_dinamis[g_id] = 0
            min_jam_dinamis[g_id] = 0
            continue

        rata_bawah = total_jam // n            # floor division
        rata_atas  = math.ceil(total_jam / n)  # ceil
        sisa       = total_jam % n             # berapa hari perlu rata_atas

        # ----------------------------------------------------------
        # LIMIT ATAS
        # Hari "lebih"  mendapat rata_atas.
        # Blok besar (mis. 3 jam) bisa membuat satu hari melebihi
        # rata_atas → tambahkan toleransi_blok seperlunya.
        # Toleransi = selisih max_block vs rata_atas, dibatasi +2.
        # ----------------------------------------------------------
        toleransi_blok = 0
        if max_block > rata_atas:
            toleransi_blok = min(2, max_block - rata_atas)

        limit_max = rata_atas + toleransi_blok

        # Pastikan limit_max minimal bisa menampung 1 blok terbesar
        limit_max = max(limit_max, max_block)

        # ----------------------------------------------------------
        # LIMIT BAWAH
        # Prinsip:
        #   sisa == 0 → semua hari HARUS persis rata_bawah
        #               (tidak perlu toleransi, malah toleransi
        #                yang bikin infeasible)
        #   sisa >  0 → ada hari "kurang" (dapat rata_bawah) dan
        #               hari "lebih" (dapat rata_atas).
        #               Beri kelonggaran -1 agar solver bebas
        #               memilih mana hari lebih/kurang.
        #   total_jam < n → guru ini memang punya hari kosong,
        #                    biarkan limit_min = 0.
        # ----------------------------------------------------------
        if total_jam < n:
            limit_min = 0          # boleh ada hari kosong
        elif sisa == 0:
            limit_min = rata_bawah # semua hari harus sama persis
        else:
            limit_min = max(0, rata_bawah - 1)

        max_jam_dinamis[g_id] = limit_max
        min_jam_dinamis[g_id] = limit_min

    # ==========================================
    # 2. MEMBANGUN MODEL CP-SAT
    # ==========================================
    model = cp_model.CpModel()

    starts, presences = {}, {}
    all_start_vars = []

    intervals_per_kelas      = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru       = {g['id']: {h: [] for h in hari_list} for g in gurus}
    durasi_per_kelas_harian  = {k['id']: {h: [] for h in hari_list} for k in kelass}
    durasi_per_guru_harian   = {g['id']: {h: [] for h in hari_list} for g in gurus}

    tasks_per_mapel_group = {}
    tasks_metadata        = []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0:
            continue

        t_id = t['id']
        g_id = t['guru_id']
        k_id = t['kelas_id']
        m_id = t.get('mapel_id')
        batas_maks_jam = t.get('batas_maksimal_jam')

        tasks_metadata.append({'id': t_id})

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        tasks_per_mapel_group.setdefault(group_key, []).append(t_id)

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

            start_var  = model.NewIntVar(1, max_start,            f'start_{t_id}_{h}')
            end_var    = model.NewIntVar(1 + durasi, batas_jam + 1, f'end_{t_id}_{h}')
            is_present = model.NewBoolVar(f'present_{t_id}_{h}')

            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'interval_{t_id}_{h}'
            )

            if batas_maks_jam is not None:
                model.Add(end_var <= int(batas_maks_jam) + 1).OnlyEnforceIf(is_present)

            starts[(t_id, h)]    = start_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            durasi_per_kelas_harian[k_id][h].append(is_present * durasi)
            durasi_per_guru_harian[g_id][h].append(is_present * durasi)

            # Blokir jam mapel yang sudah dikunci manual
            if m_id in mapel_busy:
                for blocked in mapel_busy[m_id]:
                    if blocked['hari'] == h:
                        jam_blok = int(blocked['jam'])
                        if jam_blok <= batas_jam:
                            blocked_interval = model.NewIntervalVar(
                                jam_blok, 1, jam_blok + 1,
                                f'block_{m_id}_{h}_{jam_blok}'
                            )
                            model.AddNoOverlap([interval_var, blocked_interval])

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({
                "status":  "INFEASIBLE",
                "message": f"Bentrok fatal! Mapel ID {t_id} tidak punya pilihan hari sama sekali."
            }))
            return

    # ==========================================
    # 3. KENDALA HARGA MATI: TOTAL JAM KELAS PER HARI
    # Senin–Kamis harus penuh = limit_harian
    # Jumat       harus penuh = limit_jumat
    # ==========================================
    for k in kelass:
        k_id        = k['id']
        batas_jumat = kelas_limits[k_id]['jumat']
        batas_harian= kelas_limits[k_id]['harian']

        for h in hari_list:
            beban_harian = durasi_per_kelas_harian[k_id][h]
            if not beban_harian:
                continue

            if h in ['Senin', 'Selasa', 'Rabu', 'Kamis']:
                model.Add(sum(beban_harian) == batas_harian)
            elif h == 'Jumat':
                model.Add(sum(beban_harian) == batas_jumat)

    # ==========================================
    # 3.5 KENDALA RENTANG JAM GURU (SPREAD ADAPTIF)
    #
    # Tiga prinsip:
    #   1. sisa == 0  → limit_min == limit_max == rata  (semua hari sama)
    #   2. sisa >  0  → limit_min = rata_bawah-1,
    #                   limit_max = rata_atas + toleransi_blok
    #   3. Jumat      → diskon 1 dari batas bawah (jam lebih pendek)
    # ==========================================
    for g in gurus:
        g_id       = g['id']
        batas_atas  = max_jam_dinamis[g_id]
        batas_bawah = min_jam_dinamis[g_id]
        hari_aktif  = guru_hari_map[g_id]

        for h in hari_list:
            beban_guru = durasi_per_guru_harian[g_id][h]
            if not beban_guru:
                continue

            # --- Batas Atas ---
            model.Add(sum(beban_guru) <= batas_atas)

            # --- Batas Bawah ---
            if h in hari_aktif and batas_bawah > 0:
                # Jumat: slot fisik lebih pendek → diskon 1
                bawah_efektif = max(0, batas_bawah - 1) if h == 'Jumat' else batas_bawah
                if bawah_efektif > 0:
                    model.Add(sum(beban_guru) >= bawah_efektif)

    # ==========================================
    # 4. KENDALA DASAR: TIDAK BOLEH BENTROK
    # ==========================================

    # Kelas tidak boleh dua pelajaran bersamaan
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    # Guru tidak boleh mengajar dua kelas bersamaan
    # + blokir jam kosong guru
    for g in gurus:
        g_id        = g['id']
        waktu_kosong= g.get('waktu_kosong', [])

        for h in hari_list:
            intervals = list(intervals_per_guru[g_id][h])  # copy list

            for wk in waktu_kosong:
                if wk['hari'] == h:
                    jam_libur = int(wk['jam'])
                    dummy = model.NewIntervalVar(
                        jam_libur, 1, jam_libur + 1,
                        f'libur_guru_{g_id}_{h}_{jam_libur}'
                    )
                    intervals.append(dummy)

            if intervals:
                model.AddNoOverlap(intervals)

    # Mapel yang sama di kelas yang sama tidak boleh di hari yang sama
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in hari_list:
                daily_presence = [
                    presences[(tid, h)]
                    for tid in task_ids
                    if (tid, h) in presences
                ]
                if daily_presence:
                    model.AddAtMostOne(daily_presence)

    # ==========================================
    # 5. HINT PENCARIAN: MULAI DARI JAM PALING AWAL
    # ==========================================
    if all_start_vars:
        model.AddDecisionStrategy(
            all_start_vars,
            cp_model.CHOOSE_FIRST,
            cp_model.SELECT_MIN_VALUE
        )

    # ==========================================
    # 6. EKSEKUSI SOLVER
    # ==========================================
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300
    solver.parameters.num_search_workers  = 8

    status           = solver.Solve(model)
    waktu_komputasi  = time.time() - start_time

    if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        final_solution = []
        for t in tasks_metadata:
            t_id = t['id']
            for h in hari_list:
                if (t_id, h) in presences and solver.Value(presences[(t_id, h)]) == 1:
                    final_solution.append({
                        'id':  t_id,
                        'hari': h,
                        'jam':  solver.Value(starts[(t_id, h)])
                    })
                    break

        print(json.dumps({
            "status":   "OPTIMAL",
            "solution": final_solution,
            "message":  (
                f"MANTAP! Jadwal berhasil dibuat merata dengan Spread Adaptif "
                f"dalam {waktu_komputasi:.2f} detik!"
            )
        }))
    else:
        print(json.dumps({
            "status":  "INFEASIBLE",
            "message": (
                f"Gagal menyusun jadwal (Waktu: {waktu_komputasi:.2f} detik). "
                f"Cek kembali data input atau longgarkan constraint kelas/guru."
            )
        }))


if __name__ == '__main__':
    main()