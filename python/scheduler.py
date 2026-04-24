import sys
import json
import time
import math
from ortools.sat.python import cp_model

# =============================================================================
# KONSTANTA GLOBAL
# =============================================================================
HARI_LIST = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']

# Toleransi ±JP untuk SC-1 (distribusi harian guru)
TOLERANSI_SOFT = 1

# Bobot objektif — pelanggaran biner jauh lebih mahal dari magnitudo.
# Urutan prioritas solver: kurangi JUMLAH pelanggaran dulu, baru halus-kan magnitudo.
BOBOT_PELANGGARAN   = 10_000   # SC-1: distribusi JP guru
BOBOT_BATAS_MAPEL   =  8_000   # SC-2: preferensi batas jam mapel non-PJOK


# =============================================================================
# RINGKASAN CONSTRAINT
# =============================================================================
#
# ── HARD CONSTRAINTS (wajib, infeasible jika dilanggar) ──────────────────────
#
#   HC-1  Exactly-One Assignment
#         Setiap blok pelajaran dijadwalkan tepat satu hari.
#
#   HC-2  Daily Classroom Load Equality
#         Total JP kelas k di hari h == limit konfigurasi (exact, bukan upper bound).
#
#   HC-3  No-Overlap Kelas & Guru
#         Tidak ada tabrakan jadwal pada kelas maupun guru yang sama.
#
#   HC-4  Spread — AtMostOne per Mapel per Hari
#         Mapel yang sama max 1x sehari untuk kelas yang sama.
#
#   HC-5  Hari Mengajar Guru (hari_mengajar)
#         Guru yang punya konfigurasi hari_mengajar hanya boleh dijadwalkan
#         pada hari-hari yang diizinkan. (Implementasi: hari lain di-skip total.)
#         → Aman sebagai hard karena hanya berlaku pada guru dengan beban ringan
#           (3 guru), sehingga tidak memicu infeasible.
#
#   HC-6  Batas Jam Akhir PJOK (batas_wajib = True)
#         PJOK harus selesai sebelum/pada jam ke-N sesuai regulasi sekolah.
#         → Wajib karena ada dasar keselamatan fisik siswa.
#
# ── SOFT CONSTRAINTS (preferensi, dioptimalkan tapi boleh dilanggar) ─────────
#
#   SC-1  Distribusi JP Harian Guru
#         JP harian guru diusahakan mendekati rata-rata ± TOLERANSI_SOFT.
#         Pelanggaran diukur sebagai SCFR_distribusi.
#
#   SC-2  Preferensi Batas Jam Mapel Non-PJOK (batas_wajib = False)
#         Mapel seperti Fisika/MTK/Kimia sebaiknya tidak melebihi jam ke-N
#         (agar siswa tidak kelelahan di jam akhir). Dijadikan soft agar solver
#         tidak langsung infeasible ketika banyak mapel punya preferensi ini.
#         Pelanggaran diukur sebagai SCFR_batas_mapel.
#
# =============================================================================


# =============================================================================
# FUNGSI UTILITAS
# =============================================================================

def load_json(path: str) -> dict:
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


def build_guru_maps(gurus: list) -> dict:
    """HC-5: Bangun peta hari mengajar per guru."""
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
    """Hitung target dan batas dinamis distribusi JP per guru (untuk SC-1)."""
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
            limit_min  = max(2, rata_bawah - 2) if total_jam >= n_hari else 0
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

    # Kumpulan variabel pelanggaran soft constraint
    sc1_penalti_info  = []   # SC-1: distribusi JP guru
    sc2_penalti_info  = []   # SC-2: preferensi batas jam mapel non-PJOK

    # =========================================================================
    # A: VARIABEL KEPUTUSAN + HC-5 (hari_mengajar) + HC-6 / SC-2 (batas_maks_jam)
    # =========================================================================
    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0:
            continue

        t_id           = t['id']
        g_id           = t['guru_id']
        k_id           = t['kelas_id']
        m_id           = t.get('mapel_id')
        nama_mapel     = t.get('nama_mapel', str(m_id))
        batas_maks_jam = t.get('batas_maksimal_jam')
        # batas_wajib = True  → PJOK / regulasi → HARD (HC-6)
        # batas_wajib = False → preferensi admin → SOFT (SC-2)
        batas_wajib    = t.get('batas_wajib', False)

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        tasks_per_mapel_group.setdefault(group_key, []).append(t_id)

        possible_days    = []
        lewat_vars_task  = []   # kumpul lewat_batas per hari untuk task ini (SC-2)

        for h in HARI_LIST:
            # HC-5: blok total hari yang tidak ada dalam hari_mengajar guru
            if h not in guru_hari_map[g_id]:
                continue

            batas_jam = get_max_jam(kelas_limits, k_id, h)
            if durasi > batas_jam:
                continue

            max_start = batas_jam - durasi + 1
            if max_start < 1:
                continue

            start_var    = model.NewIntVar(1, max_start,          f's_{t_id}_{h}')
            end_var      = model.NewIntVar(1 + durasi, batas_jam + 1, f'e_{t_id}_{h}')
            is_present   = model.NewBoolVar(f'p_{t_id}_{h}')
            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'iv_{t_id}_{h}'
            )

            # ── Penanganan batas_maksimal_jam ──────────────────────────────
            if batas_maks_jam is not None:
                batas = int(batas_maks_jam)
                if batas_wajib:
                    # HC-6: PJOK — hard, mutlak
                    model.Add(end_var <= batas + 1).OnlyEnforceIf(is_present)
                else:
                    # SC-2: preferensi — soft, boleh dilanggar dengan penalti
                    lewat = model.NewBoolVar(f'lewat_{t_id}_{h}')
                    # lewat hanya bisa 1 jika is_present juga 1
                    model.AddImplication(lewat, is_present)
                    # Jika hadir DAN tidak lewat → end harus dalam batas
                    model.Add(end_var <= batas + 1).OnlyEnforceIf([is_present, lewat.Not()])
                    # Jika lewat → end memang melebihi batas (agar solver jujur)
                    model.Add(end_var >= batas + 2).OnlyEnforceIf(lewat)
                    lewat_vars_task.append((lewat, h))

            starts[(t_id, h)]    = start_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            durasi_per_kelas_harian[k_id][h].append(is_present * durasi)
            durasi_per_guru_harian[g_id][h].append(is_present * durasi)

        # HC-1: Exactly-One Assignment
        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            return (None,) * 6

        # SC-2: buat satu variabel pelanggaran per task (bukan per task×hari)
        # Karena tepat satu hari aktif, sum(lewat_vars) ∈ {0, 1}
        if lewat_vars_task and batas_maks_jam is not None and not batas_wajib:
            lewat_task = model.NewBoolVar(f'lewat_task_{t_id}')
            all_lewat  = [lv for (lv, _) in lewat_vars_task]
            # lewat_task = max(all_lewat) = OR semua hari
            model.AddMaxEquality(lewat_task, all_lewat)
            sc2_penalti_info.append({
                't_id'         : t_id,
                'g_id'         : g_id,
                'k_id'         : k_id,
                'm_id'         : m_id,
                'nama_mapel'   : nama_mapel,
                'batas_maks_jam': batas_maks_jam,
                'lewat_task'   : lewat_task,
                'lewat_per_hari': lewat_vars_task,  # [(var, h), ...]
            })

    # =========================================================================
    # B: HARD CONSTRAINTS
    # =========================================================================

    # HC-2: Daily Classroom Load Equality
    for k in kelass:
        k_id = k['id']
        for h in HARI_LIST:
            beban = durasi_per_kelas_harian[k_id][h]
            if beban:
                target = get_max_jam(kelas_limits, k_id, h)
                model.Add(sum(beban) == target)

    # HC-3: No-Overlap Kelas & Guru
    for k_id in intervals_per_kelas:
        for h in HARI_LIST:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    for g_id in intervals_per_guru:
        for h in HARI_LIST:
            if intervals_per_guru[g_id][h]:
                model.AddNoOverlap(intervals_per_guru[g_id][h])

    # HC-4: Spread — Mapel sama maks 1x sehari per kelas
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in HARI_LIST:
                daily_presence = [
                    presences[(tid, h)] for tid in task_ids if (tid, h) in presences
                ]
                if daily_presence:
                    model.AddAtMostOne(daily_presence)

    # =========================================================================
    # C: SOFT CONSTRAINTS
    # =========================================================================

    sc1_violation_vars = []
    sc1_deviasi_vars   = []

    # SC-1: Distribusi JP Harian Guru
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

            # Batas atas absolut (tetap dipaksakan meski soft di bawahnya)
            model.Add(sum(beban_guru) <= batas_atas)

            if batas_atas <= 0:
                continue

            target_int = round(rata_exact)
            target_int = max(batas_bwh, min(batas_atas, target_int))

            lower = max(0, target_int - TOLERANSI_SOFT)
            upper = min(batas_atas, target_int + TOLERANSI_SOFT)

            is_violation = model.NewBoolVar(f'viol_dist_{g_id}_{h}')
            model.Add(sum(beban_guru) >= lower).OnlyEnforceIf(is_violation.Not())
            model.Add(sum(beban_guru) <= upper).OnlyEnforceIf(is_violation.Not())

            deviasi = model.NewIntVar(0, batas_atas, f'dev_{g_id}_{h}')
            model.Add(deviasi >= sum(beban_guru) - target_int)
            model.Add(deviasi >= target_int - sum(beban_guru))

            sc1_violation_vars.append(is_violation)
            sc1_deviasi_vars.append(deviasi)
            sc1_penalti_info.append({
                'g_id'        : g_id,
                'hari'        : h,
                'is_violation': is_violation,
                'deviasi'     : deviasi,
                'target'      : target_int,
                'toleransi'   : TOLERANSI_SOFT,
                'beban_vars'  : beban_guru,
            })

    # SC-2: variabel pelanggaran sudah terkumpul di sc2_penalti_info
    sc2_violation_vars = [p['lewat_task'] for p in sc2_penalti_info]

    return (
        model, starts, presences, all_start_vars,
        sc1_violation_vars, sc1_deviasi_vars, sc1_penalti_info,
        sc2_violation_vars, sc2_penalti_info
    )


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

    (model, starts, presences, all_start_vars,
     sc1_violation_vars, sc1_deviasi_vars, sc1_penalti_info,
     sc2_violation_vars, sc2_penalti_info) = result

    if model is None:
        waktu = time.time() - T_mulai
        print(json.dumps({
            "status" : "INFEASIBLE",
            "message": "Bentrok fatal terdeteksi sebelum solver dijalankan.",
            "metrik" : {
                "waktu_komputasi_detik": round(waktu, 4),
                "CSR": 0, "SCFR": 0,
                "SCFR_distribusi_guru" : 0,
                "SCFR_batas_mapel"     : 0,
            }
        }))
        return

    # =========================================================================
    # OBJEKTIF GABUNGAN
    #   Prioritas 1 → minimasi jumlah pelanggaran SC-1 (distribusi JP guru)
    #   Prioritas 2 → minimasi jumlah pelanggaran SC-2 (batas jam mapel)
    #   Prioritas 3 → minimasi total magnitudo deviasi SC-1 (halus-kan distribusi)
    # =========================================================================
    obj_terms = []
    if sc1_violation_vars:
        obj_terms.append(BOBOT_PELANGGARAN * sum(sc1_violation_vars))
    if sc2_violation_vars:
        obj_terms.append(BOBOT_BATAS_MAPEL * sum(sc2_violation_vars))
    if sc1_deviasi_vars:
        obj_terms.append(sum(sc1_deviasi_vars))
    if obj_terms:
        model.Minimize(sum(obj_terms))

    if all_start_vars:
        model.AddDecisionStrategy(
            all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE
        )

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300
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
                        'id'  : t_id,
                        'hari': h,
                        'jam' : solver.Value(starts[(t_id, h)])
                    })
                    break

        status_label = "OPTIMAL" if status == cp_model.OPTIMAL else "FEASIBLE"

        # ── Evaluasi SC-1: distribusi JP guru ─────────────────────────────
        detail_sc1 = []
        for p in sc1_penalti_info:
            if solver.Value(p['is_violation']) == 1:
                nama_guru = next(
                    (g.get('nama_guru', g.get('nama', f"Guru {p['g_id']}"))
                     for g in gurus if g['id'] == p['g_id']),
                    f"Guru {p['g_id']}"
                )
                jp_aktual = sum(solver.Value(v) for v in p['beban_vars'])
                detail_sc1.append({
                    'guru'     : nama_guru,
                    'hari'     : p['hari'],
                    'jp_aktual': jp_aktual,
                    'target'   : p['target'],
                    'selisih'  : jp_aktual - p['target'],
                })

        # ── Evaluasi SC-2: preferensi batas jam mapel ─────────────────────
        detail_sc2 = []
        for p in sc2_penalti_info:
            if solver.Value(p['lewat_task']) == 1:
                # Cari hari mana yang aktif dan melanggar
                hari_aktual = next(
                    (h for (lv, h) in p['lewat_per_hari'] if solver.Value(lv) == 1),
                    '?'
                )
                detail_sc2.append({
                    'assignment_id' : p['t_id'],
                    'mapel'         : p['nama_mapel'],
                    'kelas_id'      : p['k_id'],
                    'hari'          : hari_aktual,
                    'batas_maks_jam': p['batas_maks_jam'],
                    'pesan'         : (
                        f"{p['nama_mapel']} di kelas {p['k_id']} pada hari {hari_aktual} "
                        f"melebihi preferensi batas jam ke-{p['batas_maks_jam']}."
                    ),
                })

        # ── Hitung metrik ──────────────────────────────────────────────────
        CSR = 100   # selalu 100% jika solver menemukan solusi feasible

        total_eval_sc1  = len(sc1_penalti_info)
        total_eval_sc2  = len(sc2_penalti_info)
        total_eval      = total_eval_sc1 + total_eval_sc2

        jumlah_viol_sc1 = len(detail_sc1)
        jumlah_viol_sc2 = len(detail_sc2)
        jumlah_viol     = jumlah_viol_sc1 + jumlah_viol_sc2

        def scfr(total, viol):
            return round(100.0 * (total - viol) / total, 2) if total > 0 else 100.0

        SCFR_distribusi = scfr(total_eval_sc1, jumlah_viol_sc1)
        SCFR_batas      = scfr(total_eval_sc2, jumlah_viol_sc2)
        SCFR_gabungan   = scfr(total_eval,     jumlah_viol)

        print(json.dumps({
            "status"  : status_label,
            "solution": solusi,
            "metrik"  : {
                "waktu_komputasi_detik": round(T, 4),
                "CSR"                  : CSR,
                "SCFR_gabungan"        : SCFR_gabungan,
                "SCFR_distribusi_guru" : SCFR_distribusi,
                "SCFR_batas_mapel"     : SCFR_batas,
                "detail": {
                    "sc1_distribusi_guru": {
                        "total_evaluasi"    : total_eval_sc1,
                        "jumlah_pelanggaran": jumlah_viol_sc1,
                        "toleransi_jp"      : TOLERANSI_SOFT,
                        "pelanggaran"       : detail_sc1,
                    },
                    "sc2_batas_jam_mapel": {
                        "total_evaluasi"    : total_eval_sc2,
                        "jumlah_pelanggaran": jumlah_viol_sc2,
                        "pelanggaran"       : detail_sc2,
                    },
                }
            },
            "message": f"Solusi {status_label} ditemukan dalam {T:.2f} detik."
        }, ensure_ascii=False, indent=2))

    else:
        print(json.dumps({
            "status" : "INFEASIBLE",
            "metrik" : {
                "waktu_komputasi_detik": round(T, 4),
                "CSR"                  : 0,
                "SCFR_gabungan"        : 0,
                "SCFR_distribusi_guru" : 0,
                "SCFR_batas_mapel"     : 0,
            },
            "message": f"Solver gagal menemukan solusi dalam {T:.2f} detik."
        }))


if __name__ == '__main__':
    main()