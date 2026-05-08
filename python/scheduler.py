import sys
import json
import time
import math
from ortools.sat.python import cp_model

# =============================================================================
# KONSTANTA GLOBAL
# =============================================================================
HARI_LIST = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']

TOLERANSI_SOFT     = 2
BOBOT_PELANGGARAN  =  4_000
BOBOT_BATAS_SOFT   = 12_000
BOBOT_HARI_SOFT    =  9_000
BOBOT_DEVIASI      =    200

MAX_MEMORY_MB = 2048   # hentikan jika memori > 2 GB
MAX_WORKERS   = 8      # jumlah thread paralel

# =============================================================================
# CALLBACK – mencatat kurva konvergensi tiap solusi baru ditemukan
# =============================================================================

class FirstFeasibleCallback(cp_model.CpSolverSolutionCallback):
    """Berhenti begitu solusi pertama (feasible) ditemukan."""
    def __init__(self, t_start):
        super().__init__()
        self._t_start = t_start
        self.kurva = []

    def on_solution_callback(self):
        t   = time.time() - self._t_start
        obj = self.ObjectiveValue()
        bnd = self.BestObjectiveBound()
        gap = abs(obj - bnd) / max(1.0, abs(obj)) * 100
        self.kurva.append({'t': round(t, 2), 'obj': round(obj, 0), 'gap': round(gap, 2)})
        self.StopSearch()   # langsung hentikan setelah solusi pertama


class OptimizeCallback(cp_model.CpSolverSolutionCallback):
    """Merekam setiap solusi yang ditemukan selama fase optimasi."""
    def __init__(self, t_start):
        super().__init__()
        self._t_start = t_start
        self.kurva = []

    def on_solution_callback(self):
        t   = time.time() - self._t_start
        obj = self.ObjectiveValue()
        bnd = self.BestObjectiveBound()
        gap = abs(obj - bnd) / max(1.0, abs(obj)) * 100
        self.kurva.append({'t': round(t, 2), 'obj': round(obj, 0), 'gap': round(gap, 2)})


# =============================================================================
# FUNGSI UTILITAS
# =============================================================================

def load_json(path: str) -> dict:
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


def build_guru_maps(gurus: list) -> tuple[dict, dict]:
    guru_hari_map       = {}
    guru_jenis_hari_map = {}
    for g in gurus:
        g_id        = g['id']
        allowed     = g.get('hari_mengajar', [])
        if not allowed:
            allowed = HARI_LIST[:]
        guru_hari_map[g_id]       = allowed
        guru_jenis_hari_map[g_id] = g.get('jenis_hari', 'hard')
    return guru_hari_map, guru_jenis_hari_map


def build_kelas_limits(kelass: list) -> dict:
    return {
        k['id']: {
            'harian': k.get('limit_harian', 10),
            'jumat':  k.get('limit_jumat',  8)
        }
        for k in kelass
    }


def get_max_jam(kelas_limits: dict, kelas_id, hari: str) -> int:
    return kelas_limits[kelas_id]['jumat' if hari == 'Jumat' else 'harian']


def get_nama_guru(gurus: list, g_id) -> str:
    return next(
        (g.get('nama_guru', g.get('nama', f"Guru {g_id}"))
         for g in gurus if g['id'] == g_id),
        f"Guru {g_id}"
    )


def hitung_batas_dinamis_guru(gurus, raw_assignments, guru_hari_map):
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
        g_id      = g['id']
        total_jam = total_jam_guru[g_id]
        max_block = max_block_guru[g_id]
        n_hari    = len(guru_hari_map[g_id]) or len(HARI_LIST)

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

def bangun_model(raw_assignments, kelass, gurus,
                 kelas_limits, guru_hari_map, guru_jenis_hari_map,
                 max_jam_dinamis, min_jam_dinamis, target_jam_guru):

    model = cp_model.CpModel()
    starts    = {}
    presences = {}
    end_vars  = {}
    all_start_vars    = []
    all_presence_vars = []

    intervals_per_kelas     = {k['id']: {h: [] for h in HARI_LIST} for k in kelass}
    intervals_per_guru      = {g['id']: {h: [] for h in HARI_LIST} for g in gurus}
    durasi_per_kelas_harian = {k['id']: {h: [] for h in HARI_LIST} for k in kelass}
    durasi_per_guru_harian  = {g['id']: {h: [] for h in HARI_LIST} for g in gurus}

    tasks_per_mapel_group     = {}
    soft_batas_violation_vars = []
    soft_batas_info           = []
    soft_hari_violation_vars  = []
    soft_hari_info            = []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0:
            continue

        t_id            = t['id']
        g_id            = t['guru_id']
        k_id            = t['kelas_id']
        m_id            = t.get('mapel_id')
        batas_maks      = t.get('batas_maksimal_jam')
        nama_mapel      = str(t.get('nama_mapel', ''))
        jenis_batas_jam = t.get('jenis_batas', 'soft')
        is_batas_wajib  = (jenis_batas_jam == 'hard')

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        tasks_per_mapel_group.setdefault(group_key, []).append(t_id)

        possible_days     = []
        is_guru_hari_hard = (guru_jenis_hari_map[g_id] == 'hard')

        for h in HARI_LIST:
            is_preferred_day = (h in guru_hari_map[g_id])

            if is_guru_hari_hard and not is_preferred_day:
                continue                   # domain pruning HC-4

            batas_jam         = get_max_jam(kelas_limits, k_id, h)
            batas_aktual_hari = batas_jam
            if batas_maks is not None and is_batas_wajib:
                batas_aktual_hari = min(batas_jam, int(batas_maks))

            if durasi > batas_aktual_hari:
                continue

            max_start = batas_aktual_hari - durasi + 1
            if max_start < 1:
                continue

            start_var    = model.NewIntVar(1, max_start, f's_{t_id}_{h}')
            end_var      = model.NewIntVar(1 + durasi, batas_aktual_hari + 1, f'e_{t_id}_{h}')
            is_present   = model.NewBoolVar(f'p_{t_id}_{h}')
            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'iv_{t_id}_{h}'
            )

            # SF-2 – soft: penalti jika melewati batas slot preferensi
            if batas_maks is not None and not is_batas_wajib:
                batas_int = int(batas_maks)
                is_over   = model.NewBoolVar(f'overbatas_{t_id}_{h}')
                model.Add(end_var <= batas_int + 1).OnlyEnforceIf([is_present, is_over.Not()])
                model.Add(end_var >= batas_int + 2).OnlyEnforceIf([is_present, is_over])
                model.Add(is_over == 0).OnlyEnforceIf(is_present.Not())
                soft_batas_violation_vars.append(is_over)
                soft_batas_info.append({
                    't_id': t_id, 'h': h, 'is_over': is_over,
                    'is_present': is_present, 'end_var': end_var,
                    'batas_maks': batas_int, 'nama_mapel': nama_mapel,
                    'kelas_id': k_id, 'mapel_id': m_id,
                })

            # SF-3 – soft: penalti jika guru soft mengajar di luar hari preferensi
            if not is_preferred_day:
                viol_hari = model.NewBoolVar(f'viol_hari_{t_id}_{h}')
                model.Add(viol_hari == 1).OnlyEnforceIf(is_present)
                model.Add(viol_hari == 0).OnlyEnforceIf(is_present.Not())
                soft_hari_violation_vars.append(viol_hari)
                soft_hari_info.append({
                    't_id': t_id, 'h': h, 'g_id': g_id,
                    'viol_hari': viol_hari, 'is_present': is_present
                })

            starts[(t_id, h)]    = start_var
            end_vars[(t_id, h)]  = end_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)
            all_presence_vars.append(is_present)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            durasi_per_kelas_harian[k_id][h].append(is_present * durasi)
            durasi_per_guru_harian[g_id][h].append(is_present * durasi)

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            return (None,) * 13

    # HC-1 – JP harian kelas harus tepat
    for k in kelass:
        k_id = k['id']
        for h in HARI_LIST:
            beban = durasi_per_kelas_harian[k_id][h]
            if beban:
                model.Add(sum(beban) == get_max_jam(kelas_limits, k_id, h))

    # HC-3 – tidak bentrok jadwal kelas
    for k_id in intervals_per_kelas:
        for h in HARI_LIST:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    # HC-2 – tidak bentrok jadwal guru
    for g_id in intervals_per_guru:
        for h in HARI_LIST:
            if intervals_per_guru[g_id][h]:
                model.AddNoOverlap(intervals_per_guru[g_id][h])

    # HC-6 – mapel tidak muncul >1× per hari per kelas
    for group_key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in HARI_LIST:
                presences_for_h = [presences[(t, h)] for t in task_ids if (t, h) in presences]
                if len(presences_for_h) > 1:
                    model.Add(sum(presences_for_h) <= 1)

    # SF-1 – penyebaran beban guru (penalti deviasi + pelanggaran toleransi)
    violation_vars = []
    deviasi_vars   = []
    penalti_info   = []

    for g in gurus:
        g_id       = g['id']
        batas_atas = max_jam_dinamis[g_id]
        batas_bwh  = min_jam_dinamis[g_id]
        rata_exact = target_jam_guru[g_id]

        for h in HARI_LIST:
            beban_guru = durasi_per_guru_harian[g_id][h]
            if not beban_guru:
                continue

            model.Add(sum(beban_guru) <= batas_atas)
            if batas_atas <= 0:
                continue

            target_int   = max(batas_bwh, min(batas_atas, round(rata_exact)))
            lower        = max(0, target_int - TOLERANSI_SOFT)
            upper        = min(batas_atas, target_int + TOLERANSI_SOFT)
            is_violation = model.NewBoolVar(f'viol_{g_id}_{h}')

            model.Add(sum(beban_guru) >= lower).OnlyEnforceIf(is_violation.Not())
            model.Add(sum(beban_guru) <= upper).OnlyEnforceIf(is_violation.Not())

            deviasi = model.NewIntVar(0, batas_atas, f'dev_{g_id}_{h}')
            model.Add(deviasi >= sum(beban_guru) - target_int)
            model.Add(deviasi >= target_int - sum(beban_guru))

            violation_vars.append(is_violation)
            deviasi_vars.append(deviasi)
            penalti_info.append({
                'g_id': g_id, 'hari': h,
                'is_violation': is_violation, 'deviasi': deviasi,
                'target': target_int, 'toleransi': TOLERANSI_SOFT,
                'beban_vars': beban_guru,
            })

    return (
        model, starts, presences, end_vars,
        all_start_vars, all_presence_vars,
        violation_vars, deviasi_vars, penalti_info,
        tasks_per_mapel_group,
        soft_batas_violation_vars, soft_batas_info,
        soft_hari_violation_vars, soft_hari_info
    )


# =============================================================================
# VERIFIKASI HARD CONSTRAINT (POST-SOLVE) → CSR + BREAKDOWN
# =============================================================================

def hitung_csr(solver, raw_assignments, kelass, gurus,
               kelas_limits, guru_hari_map, guru_jenis_hari_map,
               presences, starts, tasks_per_mapel_group):
    detail   = []
    total    = 0
    count_hc = {f'HC-{i}': {'total': 0, 'pelanggaran': 0} for i in range(1, 7)}

    solusi_map = {}
    for t in raw_assignments:
        t_id   = t['id']
        durasi = int(t['jumlah_jam'])
        for h in HARI_LIST:
            if (t_id, h) in presences and solver.Value(presences[(t_id, h)]) == 1:
                solusi_map[t_id] = (h, solver.Value(starts[(t_id, h)]), durasi)
                break

    # HC-1
    for k in kelass:
        k_id = k['id']
        for h in HARI_LIST:
            total += 1
            count_hc['HC-1']['total'] += 1
            target = get_max_jam(kelas_limits, k_id, h)
            aktual = sum(
                int(t['jumlah_jam'])
                for t in raw_assignments
                if t['kelas_id'] == k_id and solusi_map.get(t['id'], (None,))[0] == h
            )
            if aktual != target:
                count_hc['HC-1']['pelanggaran'] += 1
                detail.append(
                    f"[HC-1] Kelas {k_id} hari {h}: terisi {aktual} JP, seharusnya {target} JP."
                )

    # HC-2
    for g in gurus:
        g_id = g['id']
        for h in HARI_LIST:
            total += 1
            count_hc['HC-2']['total'] += 1
            intervals = sorted(
                (solusi_map[t['id']][1], solusi_map[t['id']][1] + int(t['jumlah_jam']))
                for t in raw_assignments
                if t['guru_id'] == g_id and solusi_map.get(t['id'], (None,))[0] == h
            )
            for i in range(len(intervals) - 1):
                if intervals[i][1] > intervals[i + 1][0]:
                    count_hc['HC-2']['pelanggaran'] += 1
                    detail.append(
                        f"[HC-2] {get_nama_guru(gurus, g_id)} jadwal bertabrakan di hari {h}."
                    )
                    break

    # HC-3
    for k in kelass:
        k_id = k['id']
        for h in HARI_LIST:
            total += 1
            count_hc['HC-3']['total'] += 1
            intervals = sorted(
                (solusi_map[t['id']][1], solusi_map[t['id']][1] + int(t['jumlah_jam']))
                for t in raw_assignments
                if t['kelas_id'] == k_id and solusi_map.get(t['id'], (None,))[0] == h
            )
            for i in range(len(intervals) - 1):
                if intervals[i][1] > intervals[i + 1][0]:
                    count_hc['HC-3']['pelanggaran'] += 1
                    detail.append(f"[HC-3] Kelas {k_id} jadwal bertabrakan di hari {h}.")
                    break

    # HC-4
    for t in raw_assignments:
        t_id = t['id']
        g_id = t['guru_id']
        if guru_jenis_hari_map[g_id] != 'hard':
            continue
        total += 1
        count_hc['HC-4']['total'] += 1
        if t_id in solusi_map:
            hari_terjadwal = solusi_map[t_id][0]
            if hari_terjadwal not in guru_hari_map[g_id]:
                count_hc['HC-4']['pelanggaran'] += 1
                detail.append(
                    f"[HC-4] {get_nama_guru(gurus, g_id)} dijadwalkan di {hari_terjadwal} "
                    f"(bukan hari mengajarnya)."
                )

    # HC-5
    for t in raw_assignments:
        if t.get('jenis_batas', 'soft') != 'hard':
            continue
        batas_maks = t.get('batas_maksimal_jam')
        if batas_maks is None:
            continue
        t_id = t['id']
        total += 1
        count_hc['HC-5']['total'] += 1
        if t_id in solusi_map:
            h, jam_mulai, durasi = solusi_map[t_id]
            if (jam_mulai + durasi - 1) > int(batas_maks):
                count_hc['HC-5']['pelanggaran'] += 1
                detail.append(
                    f"[HC-5] {t.get('nama_mapel','Mapel')} kelas {t['kelas_id']} hari {h}: "
                    f"selesai slot {jam_mulai+durasi-1}, batas {int(batas_maks)}."
                )

    # HC-6
    for group_key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            k_id       = group_key[0]
            contoh_t   = next((t for t in raw_assignments if t['id'] == task_ids[0]), None)
            nama_mapel = contoh_t.get('nama_mapel', str(group_key[1])) if contoh_t else str(group_key[1])
            for h in HARI_LIST:
                total += 1
                count_hc['HC-6']['total'] += 1
                count_h = sum(
                    1 for t_id in task_ids
                    if t_id in solusi_map and solusi_map[t_id][0] == h
                )
                if count_h > 1:
                    count_hc['HC-6']['pelanggaran'] += 1
                    detail.append(
                        f"[HC-6] {nama_mapel} kelas {k_id} muncul {count_h}× di hari {h}."
                    )

    jml = len(detail)
    CSR = 100.0 * (total - jml) / total if total > 0 else 100.0

    labels = {
        'HC-1': 'JP harian kelas terpenuhi',
        'HC-2': 'Tidak bentrok slot guru',
        'HC-3': 'Tidak bentrok slot kelas',
        'HC-4': 'Guru hard di hari yang diizinkan',
        'HC-5': 'Batas slot maksimal mapel hard',
        'HC-6': 'Mapel tidak muncul 2× sehari per kelas',
    }
    breakdown_csr = []
    for key in ['HC-1', 'HC-2', 'HC-3', 'HC-4', 'HC-5', 'HC-6']:
        hc   = count_hc[key]
        t_hc = hc['total']
        p_hc = hc['pelanggaran']
        pct  = round(100.0 * (t_hc - p_hc) / t_hc, 2) if t_hc > 0 else 100.0
        breakdown_csr.append({
            'kategori'   : key,
            'deskripsi'  : labels[key],
            'pelanggaran': p_hc,
            'persen'     : pct,
        })

    return CSR, total, jml, detail, breakdown_csr


# =============================================================================
# HELPER: ekstrak solusi jadwal dari solver
# =============================================================================

def ekstrak_solusi(solver, raw_assignments, presences, starts):
    solusi = []
    for t in raw_assignments:
        t_id = t['id']
        for h in HARI_LIST:
            if (t_id, h) in presences and solver.Value(presences[(t_id, h)]) == 1:
                solusi.append({'id': t_id, 'hari': h, 'jam_mulai': solver.Value(starts[(t_id, h)])})
                break
    return solusi


def _empty_metrik(waktu: float) -> dict:
    return {
        "waktu_komputasi_detik"  : round(waktu, 4),
        "CSR"                    : 0,
        "jumlah_pelanggaran_hard": 0,
        "detail_pelanggaran_hard": [],
        "breakdown_csr"          : [],
        "SCFR"                   : 0,
        "jumlah_pelanggaran_soft": 0,
        "toleransi_soft"         : TOLERANSI_SOFT,
        "detail_pelanggaran_soft": [],
        "breakdown_scfr"         : [],
        "gap_pct"                : 0.0,
        "kurva_solver"           : [],
    }


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

    # ── Pra-pemrosesan ────────────────────────────────────────────────────────
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)
    guru_hari_map, guru_jenis_hari_map = build_guru_maps(gurus)
    kelas_limits                       = build_kelas_limits(kelass)
    max_jam_dinamis, min_jam_dinamis, target_jam_guru = hitung_batas_dinamis_guru(
        gurus, raw_assignments, guru_hari_map
    )

    # ── Bangun Model CSP ──────────────────────────────────────────────────────
    result = bangun_model(
        raw_assignments, kelass, gurus,
        kelas_limits, guru_hari_map, guru_jenis_hari_map,
        max_jam_dinamis, min_jam_dinamis, target_jam_guru
    )
    (model, starts, presences, end_vars,
     all_start_vars, all_presence_vars,
     violation_vars, deviasi_vars, penalti_info,
     tasks_per_mapel_group,
     soft_batas_violation_vars, soft_batas_info,
     soft_hari_violation_vars, soft_hari_info) = result

    if model is None:
        print(json.dumps({
            "status" : "INFEASIBLE",
            "message": "Tidak ada slot valid yang bisa dibentuk. Periksa data guru dan kapasitas kelas.",
            "metrik" : _empty_metrik(time.time() - T_mulai),
        }))
        return

    # ── Fungsi Objektif ───────────────────────────────────────────────────────
    obj_terms = []
    if soft_batas_violation_vars:
        obj_terms.append(BOBOT_BATAS_SOFT  * sum(soft_batas_violation_vars))
    if soft_hari_violation_vars:
        obj_terms.append(BOBOT_HARI_SOFT   * sum(soft_hari_violation_vars))
    if violation_vars:
        obj_terms.append(BOBOT_PELANGGARAN * sum(violation_vars))
    if deviasi_vars:
        obj_terms.append(BOBOT_DEVIASI     * sum(deviasi_vars))
    if obj_terms:
        model.Minimize(sum(obj_terms))

    # ── Decision Strategy ─────────────────────────────────────────────────────
    if all_presence_vars:
        model.AddDecisionStrategy(
            all_presence_vars, cp_model.CHOOSE_HIGHEST_MAX, cp_model.SELECT_MAX_VALUE
        )
    if all_start_vars:
        model.AddDecisionStrategy(
            all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE
        )

    # ── FASE 1 – cari solusi feasible pertama (tanpa batas waktu) ─────────────
    solver_p1 = cp_model.CpSolver()
    solver_p1.parameters.num_search_workers = MAX_WORKERS
    solver_p1.parameters.interleave_search  = True
    solver_p1.parameters.max_memory_in_mb   = MAX_MEMORY_MB

    cb_p1     = FirstFeasibleCallback(T_mulai)
    status_p1 = solver_p1.Solve(model, cb_p1)
    T_p1      = time.time() - T_mulai
    hint_ok   = status_p1 in (cp_model.OPTIMAL, cp_model.FEASIBLE)

    # ── AddHint dari hasil Fase 1 ke Fase 2 ──────────────────────────────────
    if hint_ok:
        for var_dict in [presences, starts, end_vars]:
            for v in var_dict.values():
                try:
                    model.AddHint(v, solver_p1.Value(v))
                except Exception:
                    pass

    # ── FASE 2 – optimasi penuh (tanpa batas waktu, berhenti jika gap ≤ 0.5% atau memori penuh) ──
    solver = cp_model.CpSolver()
    solver.parameters.num_search_workers = MAX_WORKERS
    solver.parameters.interleave_search  = True
    solver.parameters.max_memory_in_mb   = MAX_MEMORY_MB
    solver.parameters.relative_gap_limit = 0.005     # berhenti saat gap ≤ 0.5%

    cb_p2  = OptimizeCallback(T_mulai)
    status = solver.Solve(model, cb_p2)
    T      = time.time() - T_mulai

    # Gabungkan kurva fase 1 + fase 2
    kurva = cb_p1.kurva + cb_p2.kurva

    # Fallback: jika fase 2 tidak menghasilkan solusi, pakai fase 1
    if status not in (cp_model.OPTIMAL, cp_model.FEASIBLE) and hint_ok:
        solver = solver_p1
        status = status_p1

    # ── Proses hasil ─────────────────────────────────────────────────────────
    if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        solusi  = ekstrak_solusi(solver, raw_assignments, presences, starts)
        gap_pct = 0.0

        if status == cp_model.OPTIMAL:
            status_label = "OPTIMAL"
            status_penjelasan = (
                "Solver berhasil membuktikan bahwa jadwal yang dihasilkan adalah "
                "solusi terbaik secara matematis. Tidak ada susunan jadwal lain yang "
                "lebih baik dari ini. Semua aturan mutlak terpenuhi dan nilai penalti "
                "preferensi sudah diminimalkan hingga terbukti optimal."
            )
        else:
            gap    = solver.BestObjectiveBound()
            obj    = solver.ObjectiveValue()
            gap_pct = abs(obj - gap) / max(1.0, abs(obj)) * 100
            if gap_pct <= 0.5:
                status_label = "NEAR-OPTIMAL"
                status_penjelasan = (
                    f"Solver berhasil menemukan solusi dengan gap {gap_pct:.2f}% "
                    f"dari batas optimal matematis. Artinya solusi ini praktis sudah optimal — "
                    f"perbaikan maksimal yang masih mungkin hanya sebesar {gap_pct:.2f}% dari "
                    f"nilai penalti saat ini, yang secara praktis tidak berarti signifikan."
                )
            else:
                status_label = "FEASIBLE"
                status_penjelasan = (
                    f"Solver dihentikan karena batas memori ({MAX_MEMORY_MB} MB) tercapai "
                    f"sebelum optimasi selesai. Gap antara solusi terbaik yang ditemukan "
                    f"dengan batas bawah matematis masih {gap_pct:.2f}%. "
                    f"Ini terjadi karena ruang pencarian sangat besar akibat kompleksitas "
                    f"data (banyak assignment, kelas, guru, dan kombinasi hari). "
                    f"Meski belum terbukti optimal, seluruh aturan mutlak (hard constraint) "
                    f"tetap terpenuhi dan jadwal dapat langsung digunakan."
                )

        # Verifikasi CSR
        CSR, total_hard, jml_hard, detail_hard, breakdown_csr = hitung_csr(
            solver, raw_assignments, kelass, gurus,
            kelas_limits, guru_hari_map, guru_jenis_hari_map,
            presences, starts, tasks_per_mapel_group
        )

        # Verifikasi SCFR
        detail_soft = []

        sf1_total, sf1_pelanggaran = len(penalti_info), 0
        for p in penalti_info:
            if solver.Value(p['is_violation']) == 1:
                sf1_pelanggaran += 1
                nama         = get_nama_guru(gurus, p['g_id'])
                actual_beban = sum(solver.Value(v) for v in p['beban_vars'])
                detail_soft.append(
                    f"[SF-1] {nama} mengajar {actual_beban} JP hari {p['hari']} "
                    f"(target: {p['target']} JP, toleransi ±{p['toleransi']} JP)."
                )

        tasks_sf2_ids = {sb['t_id'] for sb in soft_batas_info}
        sf2_total, sf2_pelanggaran = len(tasks_sf2_ids), 0
        sf2_reported = set()
        for sb in soft_batas_info:
            if (solver.Value(sb['is_present']) == 1
                    and solver.Value(sb['is_over']) == 1
                    and sb['t_id'] not in sf2_reported):
                sf2_pelanggaran += 1
                sf2_reported.add(sb['t_id'])
                slot_akhir = solver.Value(sb['end_var']) - 1
                detail_soft.append(
                    f"[SF-2] {sb['nama_mapel']} kelas {sb['kelas_id']} hari {sb['h']}: "
                    f"selesai slot {slot_akhir}, batas preferensi slot {sb['batas_maks']}."
                )

        tasks_sf3_ids = {sh['t_id'] for sh in soft_hari_info}
        sf3_total, sf3_pelanggaran = len(tasks_sf3_ids), 0
        sf3_reported = set()
        for sh in soft_hari_info:
            if (solver.Value(sh['is_present']) == 1
                    and solver.Value(sh['viol_hari']) == 1
                    and sh['t_id'] not in sf3_reported):
                sf3_pelanggaran += 1
                sf3_reported.add(sh['t_id'])
                detail_soft.append(
                    f"[SF-3] {get_nama_guru(gurus, sh['g_id'])} dijadwalkan di hari {sh['h']} "
                    f"(bukan hari preferensi utamanya)."
                )

        total_soft = sf1_total + sf2_total + sf3_total
        jml_soft   = sf1_pelanggaran + sf2_pelanggaran + sf3_pelanggaran
        SCFR       = 100.0 * (total_soft - jml_soft) / total_soft if total_soft > 0 else 100.0

        breakdown_scfr = [
            {
                'kategori'   : 'SF-1',
                'deskripsi'  : 'Penyebaran beban guru per hari (toleransi ±2 JP)',
                'pelanggaran': sf1_pelanggaran,
                'persen'     : round(100.0*(sf1_total-sf1_pelanggaran)/sf1_total, 2) if sf1_total > 0 else 100.0,
            },
            {
                'kategori'   : 'SF-2',
                'deskripsi'  : 'Mapel selesai sebelum batas slot preferensi',
                'pelanggaran': sf2_pelanggaran,
                'persen'     : round(100.0*(sf2_total-sf2_pelanggaran)/sf2_total, 2) if sf2_total > 0 else 100.0,
            },
            {
                'kategori'   : 'SF-3',
                'deskripsi'  : 'Guru mengajar di hari preferensinya',
                'pelanggaran': sf3_pelanggaran,
                'persen'     : round(100.0*(sf3_total-sf3_pelanggaran)/sf3_total, 2) if sf3_total > 0 else 100.0,
            },
        ]

        # Potong kurva jika terlalu panjang (max 300 titik agar ringan di browser)
        if len(kurva) > 300:
            step   = len(kurva) // 300
            kurva  = kurva[::step]

        print(json.dumps({
            "status"           : status_label,
            "status_penjelasan": status_penjelasan,
            "solution"         : solusi,
            "metrik"           : {
                "waktu_komputasi_detik"  : round(T, 4),
                "CSR"                    : round(CSR, 2),
                "jumlah_pelanggaran_hard": jml_hard,
                "detail_pelanggaran_hard": detail_hard,
                "breakdown_csr"          : breakdown_csr,
                "SCFR"                   : round(SCFR, 2),
                "jumlah_pelanggaran_soft": jml_soft,
                "toleransi_soft"         : TOLERANSI_SOFT,
                "detail_pelanggaran_soft": detail_soft,
                "breakdown_scfr"         : breakdown_scfr,
                "gap_pct"                : round(gap_pct, 4),
                "kurva_solver"           : kurva,
            },
            "message": f"Solusi {status_label} ditemukan dalam {T:.2f} detik."
        }))

    else:
        msg = (
            "Solver tidak dapat menemukan solusi yang memenuhi semua aturan mutlak. "
            "Kemungkinan penyebab: data guru/kelas tidak memungkinkan semua assignment dijadwalkan, "
            "kapasitas JP harian kelas terlalu ketat, atau aturan hari mengajar guru terlalu membatasi."
        )
        print(json.dumps({
            "status" : "INFEASIBLE",
            "message": msg,
            "metrik" : _empty_metrik(time.time() - T_mulai),
        }))


if __name__ == '__main__':
    main()