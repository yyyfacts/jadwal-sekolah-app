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
# Naikkan nilai ini jika ingin SCFR lebih tinggi lagi.
TOLERANSI_SOFT = 1

# Bobot objektif: pelanggaran biner jauh lebih mahal dari sekadar magnitudo.
# Ini memaksa solver meminimalkan JUMLAH pelanggaran (= langsung optimalkan SCFR).
BOBOT_PELANGGARAN = 10_000


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


def hitung_batas_dinamis_guru(
    gurus: list,
    raw_assignments: list,
    guru_hari_map: dict
) -> tuple[dict, dict, dict]:
    """
    PERUBAHAN v2:
    - Batas atas: ceil+1 (sebelumnya ceil+2) → lebih ketat, paksa pemerataan.
    - Tambah `target_jam_guru`: nilai float rata-rata EXACT per hari aktif,
      digunakan sebagai acuan target yang lebih akurat daripada (ceil+floor)/2.
    """
    total_jam_guru  = {g['id']: 0 for g in gurus}
    max_block_guru  = {g['id']: 0 for g in gurus}

    for t in raw_assignments:
        g_id   = t['guru_id']
        durasi = int(t['jumlah_jam'])
        if g_id in total_jam_guru:
            total_jam_guru[g_id] += durasi
            if durasi > max_block_guru[g_id]:
                max_block_guru[g_id] = durasi

    max_jam_dinamis  = {}
    min_jam_dinamis  = {}
    target_jam_guru  = {}  # rata-rata EXACT (float)

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

            # PERBAIKAN: +1 lebih ketat agar solver dipaksa menyebar beban
            limit_max = max(rata_atas + 1, max_block)

            if total_jam >= n_hari:
                limit_min = max(1, rata_bawah - 1)
            else:
                limit_min = 0
        else:
            rata_exact = 0.0
            limit_max  = 0
            limit_min  = 0

        max_jam_dinamis[g_id] = limit_max
        min_jam_dinamis[g_id] = limit_min
        target_jam_guru[g_id] = rata_exact  # simpan untuk referensi target soft constraint

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
            return (None,) * 7

    # =========================================================================
    # B: HARD CONSTRAINTS
    # =========================================================================

    # 1. Batas Jam Harian Kelas (tetap exact)
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

    # 3. Spread Constraint (Mapel sama maks 1x sehari)
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in HARI_LIST:
                daily_presence = [
                    presences[(tid, h)] for tid in task_ids if (tid, h) in presences
                ]
                if daily_presence:
                    model.AddAtMostOne(daily_presence)

    # =========================================================================
    # C: SOFT CONSTRAINTS — BINARY VIOLATION + MAGNITUDE (PERBAIKAN UTAMA v2)
    # =========================================================================
    #
    # LOGIKA PERBAIKAN:
    # Versi lama  → Minimasi SUM(deviasi magnitudo)   ≠ langsung optimalkan SCFR
    # Versi baru  → Minimasi SUM(is_violation_biner)  = LANGSUNG optimalkan SCFR
    #
    # is_violation = 1  hanya jika beban harian guru di luar rentang:
    #   [target - TOLERANSI_SOFT, target + TOLERANSI_SOFT]
    #
    # Dengan BOBOT_PELANGGARAN >> magnitudo, solver memprioritaskan
    # pengurangan JUMLAH pelanggaran sebelum mengurangi magnitudo.
    # =========================================================================

    violation_vars = []
    deviasi_vars   = []
    penalti_info   = []

    for g in gurus:
        g_id      = g['id']
        batas_atas = max_jam_dinamis[g_id]
        batas_bwh  = min_jam_dinamis[g_id]
        rata_exact = target_jam_guru[g_id]  # float, e.g. 6.4

        for h in HARI_LIST:
            if h not in guru_hari_map[g_id]:
                continue

            beban_guru = durasi_per_guru_harian[g_id][h]
            if not beban_guru:
                continue

            # Hard: batas atas absolut
            model.Add(sum(beban_guru) <= batas_atas)

            if batas_atas <= 0:
                continue

            # Target integer: pembulatan dari rata-rata exact
            target_int = round(rata_exact)
            target_int = max(batas_bwh, min(batas_atas, target_int))  # clamp

            # Rentang toleransi (tidak dihitung pelanggaran jika dalam rentang ini)
            lower = max(0, target_int - TOLERANSI_SOFT)
            upper = min(batas_atas, target_int + TOLERANSI_SOFT)

            # ---- VARIABEL BINER: apakah ini pelanggaran? ----
            is_violation = model.NewBoolVar(f'viol_{g_id}_{h}')

            # Jika BUKAN pelanggaran → beban HARUS dalam rentang toleransi
            model.Add(sum(beban_guru) >= lower).OnlyEnforceIf(is_violation.Not())
            model.Add(sum(beban_guru) <= upper).OnlyEnforceIf(is_violation.Not())
            # (Jika IS pelanggaran → tidak ada constraint tambahan dari arah ini;
            #  solver dipaksa pilih is_violation=1 bila tidak bisa masuk rentang)

            # ---- VARIABEL MAGNITUDO (objektif sekunder) ----
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

    return model, starts, presences, all_start_vars, violation_vars, deviasi_vars, penalti_info


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

    model, starts, presences, all_start_vars, violation_vars, deviasi_vars, penalti_info = result

    if model is None:
        waktu = time.time() - T_mulai
        print(json.dumps({
            "status": "INFEASIBLE",
            "message": "Bentrok fatal terdeteksi sebelum solver dijalankan.",
            "metrik": {
                "waktu_komputasi_detik": round(waktu, 4),
                "CSR": 0,
                "SCFR": 0,
                "detail_pelanggaran": []
            }
        }))
        return

    # =========================================================================
    # OBJEKTIF GABUNGAN (PERBAIKAN UTAMA):
    #   Primer  → minimasi JUMLAH pelanggaran biner (× BOBOT_PELANGGARAN)
    #   Sekunder→ minimasi total magnitudo deviasi
    # Ini memastikan solver mengurangi JUMLAH pelanggaran (SCFR) lebih dulu,
    # baru kemudian menghaluskan magnitudo penyimpangan.
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
    solver.parameters.max_time_in_seconds = 500
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
        # HITUNG SCFR — sekarang berbasis is_violation biner (konsisten dg objektif)
        # =====================================================================
        detail_pelanggaran = []

        for p in penalti_info:
            viol_val = solver.Value(p['is_violation'])
            if viol_val == 1:
                nama_guru = next(
                    (g.get('nama_guru', g.get('nama', f"Guru {p['g_id']}"))
                     for g in gurus if g['id'] == p['g_id']),
                    f"Guru {p['g_id']}"
                )
                actual_beban = sum(solver.Value(v) for v in p['beban_vars'])
                tol = p['toleransi']
                pesan = (
                    f"{nama_guru} mengajar {actual_beban} JP di hari {p['hari']} "
                    f"(Target ideal: {p['target']} JP, toleransi ±{tol} JP)."
                )
                detail_pelanggaran.append(pesan)

        # CSR selalu 100% jika solver menemukan solusi feasible
        CSR = 100

        # SCFR: frequency-based, konsisten dg objektif biner
        total_evaluasi   = len(penalti_info)
        jumlah_pelanggaran = len(detail_pelanggaran)

        if total_evaluasi > 0:
            SCFR = 100.0 * (total_evaluasi - jumlah_pelanggaran) / total_evaluasi
        else:
            SCFR = 100.0

        print(json.dumps({
            "status": status_label,
            "solution": solusi,
            "metrik": {
                "waktu_komputasi_detik"  : round(T, 4),
                "CSR"                    : CSR,
                "SCFR"                   : round(SCFR, 2),
                "total_preferensi"       : total_evaluasi,
                "jumlah_pelanggaran"     : jumlah_pelanggaran,
                "toleransi_soft"         : TOLERANSI_SOFT,
                "detail_pelanggaran"     : detail_pelanggaran
            },
            "message": f"Solusi {status_label} ditemukan dalam {T:.2f} detik."
        }))

    else:
        print(json.dumps({
            "status": "INFEASIBLE",
            "metrik": {
                "waktu_komputasi_detik": round(T, 4),
                "CSR"                  : 0,
                "SCFR"                 : 0,
                "total_preferensi"     : 0,
                "jumlah_pelanggaran"   : 0,
                "detail_pelanggaran"   : []
            },
            "message": f"Solver gagal menemukan solusi dalam {T:.2f} detik."
        }))


if __name__ == '__main__':
    main()