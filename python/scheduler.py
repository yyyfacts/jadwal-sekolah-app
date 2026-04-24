import sys
import json
import time
import math
from ortools.sat.python import cp_model

# =============================================================================
# KONSTANTA GLOBAL
# =============================================================================
HARI_LIST = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']

# Toleransi ±JP agar selisih kecil tidak dihitung sebagai pelanggaran SCFR.
TOLERANSI_SOFT = 1

# Bobot objektif: pelanggaran biner jauh lebih mahal dari sekadar magnitudo.
BOBOT_PELANGGARAN = 10_000

# Batas waktu solver (detik)
MAX_WAKTU_SOLVER = 60


# =============================================================================
# FUNGSI UTILITAS
# =============================================================================

def load_json(path: str) -> dict:
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


def build_guru_maps(gurus: list) -> dict:
    guru_hari_map = {}
    for g in gurus:
        g_id = g['id']
        allowed_days = g.get('hari_mengajar', [])
        if not allowed_days:
            allowed_days = HARI_LIST[:]
        guru_hari_map[g_id] = allowed_days
    return guru_hari_map


def build_kelas_limits(kelass: list) -> dict:
    return {
        k['id']: {
            'harian': k.get('limit_harian', 10),
            'jumat':  k.get('limit_jumat', 8)
        }
        for k in kelass
    }


def get_max_jam(kelas_limits: dict, kelas_id: str, hari: str) -> int:
    if hari == 'Jumat':
        return kelas_limits[kelas_id]['jumat']
    return kelas_limits[kelas_id]['harian']


def get_nama_guru(gurus: list, g_id: str) -> str:
    return next(
        (g.get('nama_guru', g.get('nama', f"Guru {g_id}"))
         for g in gurus if g['id'] == g_id),
        f"Guru {g_id}"
    )


def hitung_batas_dinamis_guru(
    gurus: list,
    raw_assignments: list,
    guru_hari_map: dict
) -> tuple[dict, dict, dict]:
    total_jam_guru = {g['id']: 0 for g in gurus}
    max_block_guru = {g['id']: 0 for g in gurus}

    for t in raw_assignments:
        g_id   = t['guru_id']
        durasi = int(t['jumlah_jam'])
        if g_id in total_jam_guru:
            total_jam_guru[g_id] += durasi
            if durasi > max_block_guru[g_id]:
                max_block_guru[g_id] = durasi

    max_jam_dinamis = {}
    min_jam_dinamis = {}
    target_jam_guru = {}

    for g in gurus:
        g_id       = g['id']
        total_jam  = total_jam_guru[g_id]
        max_block  = max_block_guru[g_id]
        hari_aktif = guru_hari_map[g_id]
        n_hari     = len(hari_aktif) if hari_aktif else len(HARI_LIST)

        if n_hari > 0 and total_jam > 0:
            rata_exact = total_jam / n_hari
            rata_atas  = math.ceil(rata_exact)
            rata_bawah = math.floor(rata_exact)
            limit_max  = max(rata_atas + 2, max_block)
            if total_jam >= n_hari:
                limit_min = max(2, rata_bawah - 2)
            else:
                limit_min = 0
        else:
            rata_exact = 0.0
            limit_max  = 0
            limit_min  = 0

        max_jam_dinamis[g_id] = limit_max
        min_jam_dinamis[g_id] = limit_min
        target_jam_guru[g_id] = rata_exact

    return max_jam_dinamis, min_jam_dinamis, target_jam_guru


# =============================================================================
# FUNGSI MEMBANGUN MODEL CP-SAT
# =============================================================================

def bangun_model(
    raw_assignments: list,
    kelass: list,
    gurus: list,
    kelas_limits: dict,
    guru_hari_map: dict,
    max_jam_dinamis: dict,
    min_jam_dinamis: dict,
    target_jam_guru: dict
):
    model = cp_model.CpModel()
    starts    = {}
    presences = {}
    all_start_vars = []

    intervals_per_kelas     = {k['id']: {h: [] for h in HARI_LIST} for k in kelass}
    intervals_per_guru      = {g['id']: {h: [] for h in HARI_LIST} for g in gurus}
    durasi_per_kelas_harian = {k['id']: {h: [] for h in HARI_LIST} for k in kelass}
    durasi_per_guru_harian  = {g['id']: {h: [] for h in HARI_LIST} for g in gurus}

    tasks_per_mapel_group = {}

    # =========================================================================
    # A: VARIABEL KEPUTUSAN
    # =========================================================================
    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0:
            continue

        t_id           = t['id']
        g_id           = t['guru_id']
        k_id           = t['kelas_id']
        m_id           = t.get('mapel_id')
        batas_maks_jam = t.get('batas_maksimal_jam')

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        tasks_per_mapel_group.setdefault(group_key, []).append(t_id)

        possible_days = []

        for h in HARI_LIST:
            if h not in guru_hari_map[g_id]:
                continue

            batas_jam = get_max_jam(kelas_limits, k_id, h)
            if durasi > batas_jam:
                continue

            max_start = batas_jam - durasi + 1
            if max_start < 1:
                continue

            start_var    = model.NewIntVar(1, max_start, f's_{t_id}_{h}')
            end_var      = model.NewIntVar(1 + durasi, batas_jam + 1, f'e_{t_id}_{h}')
            is_present   = model.NewBoolVar(f'p_{t_id}_{h}')
            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'iv_{t_id}_{h}'
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

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            return (None,) * 8  # +1 karena sekarang return tasks_per_mapel_group

    # =========================================================================
    # B: HARD CONSTRAINTS
    # =========================================================================

    # 1. Batas Jam Harian Kelas (exact)
    for k in kelass:
        k_id = k['id']
        for h in HARI_LIST:
            beban = durasi_per_kelas_harian[k_id][h]
            if beban:
                target = get_max_jam(kelas_limits, k_id, h)
                model.Add(sum(beban) == target)

    # 2. Anti Tabrakan Kelas & Guru
    for k_id in intervals_per_kelas:
        for h in HARI_LIST:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    for g_id in intervals_per_guru:
        for h in HARI_LIST:
            if intervals_per_guru[g_id][h]:
                model.AddNoOverlap(intervals_per_guru[g_id][h])

    # 3. Spread Constraint (mapel sama maks 1x sehari)
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in HARI_LIST:
                daily_presence = [
                    presences[(tid, h)] for tid in task_ids if (tid, h) in presences
                ]
                if daily_presence:
                    model.AddAtMostOne(daily_presence)

    # =========================================================================
    # C: SOFT CONSTRAINTS — BINARY VIOLATION + MAGNITUDE
    # =========================================================================
    violation_vars = []
    deviasi_vars   = []
    penalti_info   = []

    for g in gurus:
        g_id       = g['id']
        batas_atas = max_jam_dinamis[g_id]
        batas_bwh  = min_jam_dinamis[g_id]
        rata_exact = target_jam_guru[g_id]

        for h in HARI_LIST:
            if h not in guru_hari_map[g_id]:
                continue

            beban_guru = durasi_per_guru_harian[g_id][h]
            if not beban_guru:
                continue

            model.Add(sum(beban_guru) <= batas_atas)

            if batas_atas <= 0:
                continue

            target_int = round(rata_exact)
            target_int = max(batas_bwh, min(batas_atas, target_int))

            lower = max(0, target_int - TOLERANSI_SOFT)
            upper = min(batas_atas, target_int + TOLERANSI_SOFT)

            is_violation = model.NewBoolVar(f'viol_{g_id}_{h}')
            model.Add(sum(beban_guru) >= lower).OnlyEnforceIf(is_violation.Not())
            model.Add(sum(beban_guru) <= upper).OnlyEnforceIf(is_violation.Not())

            deviasi = model.NewIntVar(0, batas_atas, f'dev_{g_id}_{h}')
            model.Add(deviasi >= sum(beban_guru) - target_int)
            model.Add(deviasi >= target_int - sum(beban_guru))

            violation_vars.append(is_violation)
            deviasi_vars.append(deviasi)

            penalti_info.append({
                'g_id'        : g_id,
                'hari'        : h,
                'is_violation': is_violation,
                'deviasi'     : deviasi,
                'target'      : target_int,
                'toleransi'   : TOLERANSI_SOFT,
                'beban_vars'  : beban_guru,
            })

    # tasks_per_mapel_group dikembalikan agar bisa dipakai verifikasi CSR
    return (
        model, starts, presences, all_start_vars,
        violation_vars, deviasi_vars, penalti_info,
        tasks_per_mapel_group
    )


# =============================================================================
# FUNGSI VERIFIKASI HARD CONSTRAINT (POST-SOLVE) → HITUNG CSR
# =============================================================================

def hitung_csr(
    solver,
    raw_assignments: list,
    kelass: list,
    gurus: list,
    kelas_limits: dict,
    guru_hari_map: dict,
    presences: dict,
    starts: dict,
    tasks_per_mapel_group: dict
) -> tuple[float, int, int, list]:
    """
    Verifikasi semua hard constraint setelah solver menemukan solusi.
    Mengembalikan (CSR, total_evaluasi, jumlah_pelanggaran, detail_pelanggaran_hard).

    Karena semua constraint ini bersifat HARD di dalam model CP-SAT,
    CSR seharusnya selalu 100% untuk solusi FEASIBLE/OPTIMAL.
    Fungsi ini memvalidasi secara eksplisit dan melaporkan detail
    dengan format yang konsisten dengan SCFR.
    """
    detail_pelanggaran_hard = []
    total_evaluasi = 0

    # ----------------------------------------------------------------
    # Bangun peta solusi: t_id → (hari, jam_mulai, durasi)
    # ----------------------------------------------------------------
    solusi_map = {}
    for t in raw_assignments:
        t_id   = t['id']
        durasi = int(t['jumlah_jam'])
        for h in HARI_LIST:
            if (t_id, h) in presences and solver.Value(presences[(t_id, h)]) == 1:
                jam_mulai       = solver.Value(starts[(t_id, h)])
                solusi_map[t_id] = (h, jam_mulai, durasi)
                break

    # ----------------------------------------------------------------
    # HC-1: Batas Jam Harian Kelas (setiap kelas×hari harus == target)
    # ----------------------------------------------------------------
    for k in kelass:
        k_id = k['id']
        for h in HARI_LIST:
            total_evaluasi += 1
            target = get_max_jam(kelas_limits, k_id, h)
            aktual = sum(
                int(t['jumlah_jam'])
                for t in raw_assignments
                if t['kelas_id'] == k_id and solusi_map.get(t['id'], (None,))[0] == h
            )
            if aktual != target:
                detail_pelanggaran_hard.append(
                    f"[HC-1] Kelas {k_id} di hari {h}: "
                    f"terisi {aktual} JP, seharusnya tepat {target} JP."
                )

    # ----------------------------------------------------------------
    # HC-2: Anti Tabrakan Guru (setiap guru×hari tidak boleh overlap)
    # ----------------------------------------------------------------
    for g in gurus:
        g_id = g['id']
        for h in HARI_LIST:
            if h not in guru_hari_map[g_id]:
                continue
            total_evaluasi += 1

            # Kumpulkan semua interval [start, end) guru di hari ini
            interval_hari = sorted(
                (solusi_map[t['id']][1],
                 solusi_map[t['id']][1] + int(t['jumlah_jam']))
                for t in raw_assignments
                if t['guru_id'] == g_id and solusi_map.get(t['id'], (None,))[0] == h
            )

            overlap = False
            for i in range(len(interval_hari) - 1):
                if interval_hari[i][1] > interval_hari[i + 1][0]:
                    overlap = True
                    break

            if overlap:
                nama = get_nama_guru(gurus, g_id)
                detail_pelanggaran_hard.append(
                    f"[HC-2] {nama} memiliki jadwal bertabrakan di hari {h}."
                )

    # ----------------------------------------------------------------
    # HC-3: Anti Tabrakan Kelas (setiap kelas×hari tidak boleh overlap)
    # ----------------------------------------------------------------
    for k in kelass:
        k_id = k['id']
        for h in HARI_LIST:
            total_evaluasi += 1

            interval_hari = sorted(
                (solusi_map[t['id']][1],
                 solusi_map[t['id']][1] + int(t['jumlah_jam']))
                for t in raw_assignments
                if t['kelas_id'] == k_id and solusi_map.get(t['id'], (None,))[0] == h
            )

            overlap = False
            for i in range(len(interval_hari) - 1):
                if interval_hari[i][1] > interval_hari[i + 1][0]:
                    overlap = True
                    break

            if overlap:
                detail_pelanggaran_hard.append(
                    f"[HC-3] Kelas {k_id} memiliki jadwal bertabrakan di hari {h}."
                )

    # ----------------------------------------------------------------
    # HC-4: Spread Constraint (mapel/grup sama maks 1x per hari)
    # ----------------------------------------------------------------
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) <= 1:
            continue
        k_id, m_id = key
        for h in HARI_LIST:
            total_evaluasi += 1
            jumlah_hari_ini = sum(
                1 for tid in task_ids
                if solusi_map.get(tid, (None,))[0] == h
            )
            if jumlah_hari_ini > 1:
                detail_pelanggaran_hard.append(
                    f"[HC-4] Kelas {k_id} mapel {m_id} terjadwal "
                    f"{jumlah_hari_ini}x di hari {h} (maks 1x per hari)."
                )

    # ----------------------------------------------------------------
    # HC-5: Ketersediaan Hari Guru
    # ----------------------------------------------------------------
    for t in raw_assignments:
        t_id = t['id']
        g_id = t['guru_id']
        total_evaluasi += 1
        if t_id in solusi_map:
            hari_terjadwal = solusi_map[t_id][0]
            if hari_terjadwal not in guru_hari_map[g_id]:
                nama = get_nama_guru(gurus, g_id)
                detail_pelanggaran_hard.append(
                    f"[HC-5] {nama} dijadwalkan di hari {hari_terjadwal} "
                    f"tetapi hari tersebut bukan hari mengajarnya."
                )

    jumlah_pelanggaran = len(detail_pelanggaran_hard)
    CSR = (
        100.0 * (total_evaluasi - jumlah_pelanggaran) / total_evaluasi
        if total_evaluasi > 0 else 100.0
    )

    return CSR, total_evaluasi, jumlah_pelanggaran, detail_pelanggaran_hard


# =============================================================================
# FUNGSI UTAMA
# =============================================================================

def main():
    T_mulai = time.time()

    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "message": "Path file JSON diperlukan."}))
        return

    try:
        data = load_json(sys.argv[1])
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": str(e)}))
        return

    raw_assignments = data.get('assignments', [])
    kelass          = data.get('kelass', [])
    gurus           = data.get('gurus', [])

    # MCV Heuristic: blok terpanjang diproses lebih dulu
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    guru_hari_map = build_guru_maps(gurus)
    kelas_limits  = build_kelas_limits(kelass)

    max_jam_dinamis, min_jam_dinamis, target_jam_guru = hitung_batas_dinamis_guru(
        gurus, raw_assignments, guru_hari_map
    )

    result = bangun_model(
        raw_assignments, kelass, gurus,
        kelas_limits, guru_hari_map,
        max_jam_dinamis, min_jam_dinamis,
        target_jam_guru
    )

    # Unpack 8 nilai (ditambah tasks_per_mapel_group)
    (model, starts, presences, all_start_vars,
     violation_vars, deviasi_vars, penalti_info,
     tasks_per_mapel_group) = result

    if model is None:
        waktu = time.time() - T_mulai
        print(json.dumps({
            "status": "INFEASIBLE",
            "message": "Bentrok fatal terdeteksi sebelum solver dijalankan.",
            "metrik": {
                "waktu_komputasi_detik": round(waktu, 4),
                # -- CSR (Hard Constraints) --
                "CSR": 0,
                "total_hard_constraints": 0,
                "jumlah_pelanggaran_hard": 0,
                "detail_pelanggaran_hard": [],
                # -- SCFR (Soft Constraints) --
                "SCFR": 0,
                "total_preferensi": 0,
                "jumlah_pelanggaran_soft": 0,
                "toleransi_soft": TOLERANSI_SOFT,
                "detail_pelanggaran_soft": []
            }
        }))
        return

    # =========================================================================
    # OBJEKTIF GABUNGAN:
    #   Primer  → minimasi JUMLAH pelanggaran biner (× BOBOT_PELANGGARAN)
    #   Sekunder→ minimasi total magnitudo deviasi
    # =========================================================================
    if violation_vars:
        obj_pelanggaran = BOBOT_PELANGGARAN * sum(violation_vars)
        obj_magnitudo   = sum(deviasi_vars)
        model.Minimize(obj_pelanggaran + obj_magnitudo)

    if all_start_vars:
        model.AddDecisionStrategy(
            all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE
        )

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = MAX_WAKTU_SOLVER   # FIX: nilai semula kosong
    solver.parameters.num_search_workers  = 8

    status = solver.Solve(model)

    T_selesai = time.time()
    T = T_selesai - T_mulai

    if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        solusi = []
        for t in raw_assignments:
            t_id = t['id']
            for h in HARI_LIST:
                if (t_id, h) in presences and solver.Value(presences[(t_id, h)]) == 1:
                    solusi.append({
                        'id':   t_id,
                        'hari': h,
                        'jam':  solver.Value(starts[(t_id, h)])
                    })
                    break

        status_label = "OPTIMAL" if status == cp_model.OPTIMAL else "FEASIBLE"

        # =====================================================================
        # HITUNG CSR — verifikasi post-solve semua hard constraint
        # =====================================================================
        CSR, total_hard, jml_pelanggaran_hard, detail_hard = hitung_csr(
            solver, raw_assignments, kelass, gurus,
            kelas_limits, guru_hari_map, presences, starts,
            tasks_per_mapel_group
        )

        # =====================================================================
        # HITUNG SCFR — berbasis is_violation biner (konsisten dg objektif)
        # =====================================================================
        detail_pelanggaran_soft = []

        for p in penalti_info:
            viol_val = solver.Value(p['is_violation'])
            if viol_val == 1:
                nama_guru    = get_nama_guru(gurus, p['g_id'])
                actual_beban = sum(solver.Value(v) for v in p['beban_vars'])
                tol          = p['toleransi']
                detail_pelanggaran_soft.append(
                    f"{nama_guru} mengajar {actual_beban} JP di hari {p['hari']} "
                    f"(Target ideal: {p['target']} JP, toleransi ±{tol} JP)."
                )

        total_soft        = len(penalti_info)
        jml_pelanggaran_soft = len(detail_pelanggaran_soft)
        SCFR = (
            100.0 * (total_soft - jml_pelanggaran_soft) / total_soft
            if total_soft > 0 else 100.0
        )

        print(json.dumps({
            "status"  : status_label,
            "solution": solusi,
            "metrik"  : {
                "waktu_komputasi_detik": round(T, 4),

                # ── Hard Constraints (CSR) ─────────────────────────────────
                "CSR"                    : round(CSR, 2),
                "total_hard_constraints" : total_hard,
                "jumlah_pelanggaran_hard": jml_pelanggaran_hard,
                "detail_pelanggaran_hard": detail_hard,

                # ── Soft Constraints (SCFR) ────────────────────────────────
                "SCFR"                   : round(SCFR, 2),
                "total_preferensi"       : total_soft,
                "jumlah_pelanggaran_soft": jml_pelanggaran_soft,
                "toleransi_soft"         : TOLERANSI_SOFT,
                "detail_pelanggaran_soft": detail_pelanggaran_soft
            },
            "message": f"Solusi {status_label} ditemukan dalam {T:.2f} detik."
        }))

    else:
        waktu = time.time() - T_mulai
        print(json.dumps({
            "status": "INFEASIBLE",
            "metrik": {
                "waktu_komputasi_detik": round(waktu, 4),

                # ── Hard Constraints (CSR) ─────────────────────────────────
                "CSR"                    : 0,
                "total_hard_constraints" : 0,
                "jumlah_pelanggaran_hard": 0,
                "detail_pelanggaran_hard": [],

                # ── Soft Constraints (SCFR) ────────────────────────────────
                "SCFR"                   : 0,
                "total_preferensi"       : 0,
                "jumlah_pelanggaran_soft": 0,
                "toleransi_soft"         : TOLERANSI_SOFT,
                "detail_pelanggaran_soft": []
            },
            "message": f"Solver gagal menemukan solusi dalam {T:.2f} detik."
        }))


if __name__ == '__main__':
    main()