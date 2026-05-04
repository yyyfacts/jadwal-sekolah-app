import sys
import json
import time
import math
from ortools.sat.python import cp_model

# =============================================================================
# KONSTANTA GLOBAL
# =============================================================================
HARI_LIST = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']

TOLERANSI_SOFT = 2
BOBOT_PELANGGARAN  =  4_000
BOBOT_BATAS_SOFT   = 12_000
BOBOT_HARI_SOFT    =  9_000
BOBOT_DEVIASI      =    200

MAX_WAKTU_SOLVER = 300   # total budget waktu: 5 menit

# =============================================================================
# FUNGSI UTILITAS
# =============================================================================
def load_json(path: str) -> dict:
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)

def build_guru_maps(gurus: list) -> tuple[dict, dict]:
    guru_hari_map = {}
    guru_jenis_hari_map = {}
    for g in gurus:
        g_id = g['id']
        allowed_days = g.get('hari_mengajar', [])
        if not allowed_days:
            allowed_days = HARI_LIST[:]
        guru_hari_map[g_id] = allowed_days
        guru_jenis_hari_map[g_id] = g.get('jenis_hari', 'hard')
    return guru_hari_map, guru_jenis_hari_map

def build_kelas_limits(kelass: list) -> dict:
    return {
        k['id']: {
            'harian': k.get('limit_harian', 10),
            'jumat':  k.get('limit_jumat', 8)
        }
        for k in kelass
    }

def get_max_jam(kelas_limits: dict, kelas_id, hari: str) -> int:
    if hari == 'Jumat':
        return kelas_limits[kelas_id]['jumat']
    return kelas_limits[kelas_id]['harian']

def get_nama_guru(gurus: list, g_id) -> str:
    return next((g.get('nama_guru', g.get('nama', f"Guru {g_id}")) for g in gurus if g['id'] == g_id), f"Guru {g_id}")

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
        g_id       = g['id']
        total_jam  = total_jam_guru[g_id]
        max_block  = max_block_guru[g_id]
        n_hari     = len(guru_hari_map[g_id]) or len(HARI_LIST)

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
def bangun_model(raw_assignments, kelass, gurus, kelas_limits, guru_hari_map, guru_jenis_hari_map, max_jam_dinamis, min_jam_dinamis, target_jam_guru):
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
        if durasi <= 0: continue

        t_id        = t['id']
        g_id        = t['guru_id']
        k_id        = t['kelas_id']
        m_id        = t.get('mapel_id')
        batas_maks  = t.get('batas_maksimal_jam')
        nama_mapel  = str(t.get('nama_mapel', ''))
        jenis_batas_jam = t.get('jenis_batas', 'soft')
        is_batas_wajib  = (jenis_batas_jam == 'hard')

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        tasks_per_mapel_group.setdefault(group_key, []).append(t_id)

        possible_days = []
        is_guru_hari_hard = (guru_jenis_hari_map[g_id] == 'hard')

        for h in HARI_LIST:
            is_preferred_day = (h in guru_hari_map[g_id])
            if is_guru_hari_hard and not is_preferred_day: continue

            batas_jam         = get_max_jam(kelas_limits, k_id, h)
            batas_aktual_hari = batas_jam
            if batas_maks is not None and is_batas_wajib:
                batas_aktual_hari = min(batas_jam, int(batas_maks))

            if durasi > batas_aktual_hari: continue
            max_start = batas_aktual_hari - durasi + 1
            if max_start < 1: continue

            start_var    = model.NewIntVar(1, max_start, f's_{t_id}_{h}')
            end_var      = model.NewIntVar(1 + durasi, batas_aktual_hari + 1, f'e_{t_id}_{h}')
            is_present   = model.NewBoolVar(f'p_{t_id}_{h}')
            interval_var = model.NewOptionalIntervalVar(start_var, durasi, end_var, is_present, f'iv_{t_id}_{h}')

            if batas_maks is not None and not is_batas_wajib:
                batas_int = int(batas_maks)
                is_over = model.NewBoolVar(f'overbatas_{t_id}_{h}')
                model.Add(end_var <= batas_int + 1).OnlyEnforceIf([is_present, is_over.Not()])
                model.Add(end_var >= batas_int + 2).OnlyEnforceIf([is_present, is_over])
                model.Add(is_over == 0).OnlyEnforceIf(is_present.Not())
                soft_batas_violation_vars.append(is_over)
                soft_batas_info.append({'t_id': t_id, 'h': h, 'is_over': is_over, 'is_present': is_present, 'end_var': end_var, 'batas_maks': batas_int, 'nama_mapel': nama_mapel, 'kelas_id': k_id, 'mapel_id': m_id})

            if not is_preferred_day:
                viol_hari = model.NewBoolVar(f'viol_hari_{t_id}_{h}')
                model.Add(viol_hari == 1).OnlyEnforceIf(is_present)
                model.Add(viol_hari == 0).OnlyEnforceIf(is_present.Not())
                soft_hari_violation_vars.append(viol_hari)
                soft_hari_info.append({'t_id': t_id, 'h': h, 'g_id': g_id, 'viol_hari': viol_hari, 'is_present': is_present})

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

        if possible_days: model.AddExactlyOne(possible_days)
        else: return (None,) * 13

    for k in kelass:
        k_id = k['id']
        for h in HARI_LIST:
            beban = durasi_per_kelas_harian[k_id][h]
            if beban: model.Add(sum(beban) == get_max_jam(kelas_limits, k_id, h))

    for k_id in intervals_per_kelas:
        for h in HARI_LIST:
            if intervals_per_kelas[k_id][h]: model.AddNoOverlap(intervals_per_kelas[k_id][h])

    for g_id in intervals_per_guru:
        for h in HARI_LIST:
            if intervals_per_guru[g_id][h]: model.AddNoOverlap(intervals_per_guru[g_id][h])

    for group_key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in HARI_LIST:
                presences_for_h = [presences[(t, h)] for t in task_ids if (t, h) in presences]
                if len(presences_for_h) > 1: model.Add(sum(presences_for_h) <= 1)

    violation_vars, deviasi_vars, penalti_info = [], [], []

    for g in gurus:
        g_id = g['id']
        batas_atas, batas_bwh, rata_exact = max_jam_dinamis[g_id], min_jam_dinamis[g_id], target_jam_guru[g_id]

        for h in HARI_LIST:
            beban_guru = durasi_per_guru_harian[g_id][h]
            if not beban_guru: continue

            model.Add(sum(beban_guru) <= batas_atas)
            if batas_atas <= 0: continue

            target_int = max(batas_bwh, min(batas_atas, round(rata_exact)))
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
            penalti_info.append({'g_id': g_id, 'hari': h, 'is_violation': is_violation, 'deviasi': deviasi, 'target': target_int, 'toleransi': TOLERANSI_SOFT, 'beban_vars': beban_guru})

    return (model, starts, presences, end_vars, all_start_vars, all_presence_vars, violation_vars, deviasi_vars, penalti_info, tasks_per_mapel_group, soft_batas_violation_vars, soft_batas_info, soft_hari_violation_vars, soft_hari_info)

# =============================================================================
# VERIFIKASI HARD CONSTRAINT (POST-SOLVE)
# =============================================================================
def hitung_csr(solver, raw_assignments, kelass, gurus, kelas_limits, guru_hari_map, guru_jenis_hari_map, presences, starts, tasks_per_mapel_group):
    detail = []
    total  = 0
    count_hc = {f'HC-{i}': {'total': 0, 'pelanggaran': 0} for i in range(1, 7)}
    solusi_map = {}
    
    for t in raw_assignments:
        t_id, durasi = t['id'], int(t['jumlah_jam'])
        for h in HARI_LIST:
            if (t_id, h) in presences and solver.Value(presences[(t_id, h)]) == 1:
                solusi_map[t_id] = (h, solver.Value(starts[(t_id, h)]), durasi)
                break

    for k in kelass:
        k_id = k['id']
        for h in HARI_LIST:
            total += 1; count_hc['HC-1']['total'] += 1
            target = get_max_jam(kelas_limits, k_id, h)
            aktual = sum(int(t['jumlah_jam']) for t in raw_assignments if t['kelas_id'] == k_id and solusi_map.get(t['id'], (None,))[0] == h)
            if aktual != target:
                count_hc['HC-1']['pelanggaran'] += 1
                detail.append(f"[HC-1] Kelas {k_id} hari {h}: terisi {aktual} JP, seharusnya tepat {target} JP.")

    for g in gurus:
        g_id = g['id']
        for h in HARI_LIST:
            total += 1; count_hc['HC-2']['total'] += 1
            intervals = sorted((solusi_map[t['id']][1], solusi_map[t['id']][1] + int(t['jumlah_jam'])) for t in raw_assignments if t['guru_id'] == g_id and solusi_map.get(t['id'], (None,))[0] == h)
            for i in range(len(intervals) - 1):
                if intervals[i][1] > intervals[i + 1][0]:
                    count_hc['HC-2']['pelanggaran'] += 1; detail.append(f"[HC-2] {get_nama_guru(gurus, g_id)} jadwal bertabrakan di hari {h}."); break

    for k in kelass:
        k_id = k['id']
        for h in HARI_LIST:
            total += 1; count_hc['HC-3']['total'] += 1
            intervals = sorted((solusi_map[t['id']][1], solusi_map[t['id']][1] + int(t['jumlah_jam'])) for t in raw_assignments if t['kelas_id'] == k_id and solusi_map.get(t['id'], (None,))[0] == h)
            for i in range(len(intervals) - 1):
                if intervals[i][1] > intervals[i + 1][0]:
                    count_hc['HC-3']['pelanggaran'] += 1; detail.append(f"[HC-3] Kelas {k_id} jadwal bertabrakan di hari {h}."); break

    for t in raw_assignments:
        t_id, g_id = t['id'], t['guru_id']
        if guru_jenis_hari_map[g_id] != 'hard': continue
        total += 1; count_hc['HC-4']['total'] += 1
        if t_id in solusi_map:
            hari_terjadwal = solusi_map[t_id][0]
            if hari_terjadwal not in guru_hari_map[g_id]:
                count_hc['HC-4']['pelanggaran'] += 1; detail.append(f"[HC-4] {get_nama_guru(gurus, g_id)} dijadwalkan di hari {hari_terjadwal} (bukan hari mengajarnya).")

    for t in raw_assignments:
        if t.get('jenis_batas', 'soft') != 'hard': continue
        batas_maks = t.get('batas_maksimal_jam')
        if batas_maks is None: continue
        total += 1; count_hc['HC-5']['total'] += 1
        if t['id'] in solusi_map:
            h, jam_mulai, durasi = solusi_map[t['id']]
            if (jam_mulai + durasi - 1) > int(batas_maks):
                count_hc['HC-5']['pelanggaran'] += 1; detail.append(f"[HC-5] Melewati batas slot maksimal hard mapel.")

    for group_key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in HARI_LIST:
                total += 1; count_hc['HC-6']['total'] += 1
                if sum(1 for t_id in task_ids if t_id in solusi_map and solusi_map[t_id][0] == h) > 1:
                    count_hc['HC-6']['pelanggaran'] += 1; detail.append(f"[HC-6] Mapel muncul > 1 kali sehari per kelas.")

    jml = len(detail)
    CSR = 100.0 * (total - jml) / total if total > 0 else 100.0
    
    breakdown_csr = []
    labels = {
        'HC-1': 'JP harian kelas terpenuhi', 'HC-2': 'Tidak bentrok slot guru', 'HC-3': 'Tidak bentrok slot kelas',
        'HC-4': 'Guru hard di hari diizinkan', 'HC-5': 'Batas slot maksimal mapel', 'HC-6': 'Mapel 1x sehari per kelas'
    }
    for key in ['HC-1', 'HC-2', 'HC-3', 'HC-4', 'HC-5', 'HC-6']:
        hc, t_hc, p_hc = count_hc[key], count_hc[key]['total'], count_hc[key]['pelanggaran']
        pct = round(100.0 * (t_hc - p_hc) / t_hc, 2) if t_hc > 0 else 100.0
        breakdown_csr.append({'kategori': key, 'deskripsi': labels[key], 'total': t_hc, 'pelanggaran': p_hc, 'terpenuhi': t_hc - p_hc, 'persen': pct})

    return CSR, total, jml, detail, breakdown_csr

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
    return {"waktu_komputasi_detik": round(waktu, 4), "CSR": 0, "total_hard_constraints": 0, "jumlah_pelanggaran_hard": 0, "detail_pelanggaran_hard": [], "breakdown_csr": [], "SCFR": 0, "total_preferensi": 0, "jumlah_pelanggaran_soft": 0, "toleransi_soft": TOLERANSI_SOFT, "detail_pelanggaran_soft": [], "breakdown_scfr": []}

# =============================================================================
# FUNGSI UTAMA (MAIN)
# =============================================================================
def main():
    T_mulai = time.time()
    if len(sys.argv) < 2: return print(json.dumps({"status": "ERROR"}))

    try: data = load_json(sys.argv[1])
    except Exception as e: return print(json.dumps({"status": "ERROR"}))

    raw_assignments, kelass, gurus = data.get('assignments', []), data.get('kelass', []), data.get('gurus', [])

    # TAHAP 1: INPUT MASUKAN (Mencatat Total Awal)
    total_assignments_input = len(raw_assignments)
    total_kelas_input = len(kelass)
    total_guru_input = len(gurus)
    total_kemungkinan_awal = total_assignments_input * 5 # Assignment dikali 5 hari (Senin-Jumat)

    # TAHAP 2: PRA-PEMROSESAN (Sorting & Pruning)
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)
    guru_hari_map, guru_jenis_hari_map = build_guru_maps(gurus)
    kelas_limits = build_kelas_limits(kelass)
    max_jam_dinamis, min_jam_dinamis, target_jam_guru = hitung_batas_dinamis_guru(gurus, raw_assignments, guru_hari_map)

    # TAHAP 3: PEMODELAN CSP (Mendeklarasikan Variabel dan Constraint)
    result = bangun_model(raw_assignments, kelass, gurus, kelas_limits, guru_hari_map, guru_jenis_hari_map, max_jam_dinamis, min_jam_dinamis, target_jam_guru)
    (model, starts, presences, end_vars, all_start_vars, all_presence_vars, violation_vars, deviasi_vars, penalti_info, tasks_per_mapel_group, soft_batas_violation_vars, soft_batas_info, soft_hari_violation_vars, soft_hari_info) = result

    if model is None: return print(json.dumps({"status": "INFEASIBLE", "metrik": _empty_metrik(time.time() - T_mulai)}))

    # Merekam kalkulasi hasil pemangkasan (Pruning)
    total_kombinasi_valid = len(all_presence_vars) # Jumlah variabel is_present yang selamat dimasukkan ke model
    total_dipangkas = total_kemungkinan_awal - total_kombinasi_valid

    # Objektif C-Soft
    obj_terms = []
    if soft_batas_violation_vars: obj_terms.append(BOBOT_BATAS_SOFT  * sum(soft_batas_violation_vars))
    if soft_hari_violation_vars: obj_terms.append(BOBOT_HARI_SOFT   * sum(soft_hari_violation_vars))
    if violation_vars: obj_terms.append(BOBOT_PELANGGARAN * sum(violation_vars))
    if deviasi_vars: obj_terms.append(BOBOT_DEVIASI     * sum(deviasi_vars))
    if obj_terms: model.Minimize(sum(obj_terms))

    if all_presence_vars: model.AddDecisionStrategy(all_presence_vars, cp_model.CHOOSE_HIGHEST_MAX, cp_model.SELECT_MAX_VALUE)
    if all_start_vars: model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    # TAHAP 4: FASE 1 (25% Time)
    WAKTU_P1  = max(20, MAX_WAKTU_SOLVER // 4)   
    solver_p1 = cp_model.CpSolver()
    solver_p1.parameters.max_time_in_seconds = WAKTU_P1
    solver_p1.parameters.num_search_workers  = 4
    solver_p1.parameters.interleave_search   = True
    solver_p1.parameters.max_memory_in_mb    = 2048
    solver_p1.parameters.relative_gap_limit  = 1.0

    status_p1 = solver_p1.Solve(model)
    T_p1      = time.time() - T_mulai
    hint_ok   = status_p1 in (cp_model.OPTIMAL, cp_model.FEASIBLE)

    # TAHAP 5: FASE 2 (75% Time) + AddHint
    if hint_ok:
        for var_dict in [presences, starts, end_vars]:
            for v in var_dict.values():
                try: model.AddHint(v, solver_p1.Value(v))
                except: pass   

    sisa_waktu = max(30, MAX_WAKTU_SOLVER - int(T_p1))
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = sisa_waktu
    solver.parameters.num_search_workers  = 4
    solver.parameters.interleave_search   = True
    solver.parameters.max_memory_in_mb    = 2048
    solver.parameters.relative_gap_limit  = 0.005

    status = solver.Solve(model)
    T = time.time() - T_mulai

    if status not in (cp_model.OPTIMAL, cp_model.FEASIBLE) and hint_ok:
        solver, status = solver_p1, status_p1

    if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        solusi = ekstrak_solusi(solver, raw_assignments, presences, starts)

        gap_pct = 0.0
        if status == cp_model.OPTIMAL: status_label = "OPTIMAL"
        else:
            gap, obj = solver.BestObjectiveBound(), solver.ObjectiveValue()
            gap_pct = abs(obj - gap) / max(1, abs(obj)) * 100
            status_label = "NEAR-OPTIMAL" if gap_pct <= 0.5 else "FEASIBLE"

        # TAHAP 6: VERIFIKASI CSR & SCFR
        CSR, total_hard, jml_hard, detail_hard, breakdown_csr = hitung_csr(solver, raw_assignments, kelass, gurus, kelas_limits, guru_hari_map, guru_jenis_hari_map, presences, starts, tasks_per_mapel_group)

        detail_soft, sf1_jml, sf2_jml, sf3_jml = [], 0, 0, 0
        for p in penalti_info:
            if solver.Value(p['is_violation']) == 1:
                sf1_jml += 1
        for sb in soft_batas_info:
            if solver.Value(sb['is_present']) == 1 and solver.Value(sb['is_over']) == 1:
                sf2_jml += 1
        for sh in soft_hari_info:
            if solver.Value(sh['is_present']) == 1 and solver.Value(sh['viol_hari']) == 1:
                sf3_jml += 1

        total_soft = len(penalti_info) + len(soft_batas_info) + len(soft_hari_info)
        jml_soft = sf1_jml + sf2_jml + sf3_jml
        SCFR = 100.0 * (total_soft - jml_soft) / total_soft if total_soft > 0 else 100.0

        breakdown_scfr = [
            {'kategori': 'SF-1', 'deskripsi': 'Penyebaran beban guru', 'total': len(penalti_info), 'pelanggaran': sf1_jml, 'terpenuhi': len(penalti_info) - sf1_jml, 'persen': round(100.0*(len(penalti_info)-sf1_jml)/max(1,len(penalti_info)), 2)},
            {'kategori': 'SF-2', 'deskripsi': 'Batas slot preferensi', 'total': len(soft_batas_info), 'pelanggaran': sf2_jml, 'terpenuhi': len(soft_batas_info) - sf2_jml, 'persen': round(100.0*(len(soft_batas_info)-sf2_jml)/max(1,len(soft_batas_info)), 2)},
            {'kategori': 'SF-3', 'deskripsi': 'Guru di hari preferensi', 'total': len(soft_hari_info), 'pelanggaran': sf3_jml, 'terpenuhi': len(soft_hari_info) - sf3_jml, 'persen': round(100.0*(len(soft_hari_info)-sf3_jml)/max(1,len(soft_hari_info)), 2)}
        ]

        # DATA LOG LENGKAP YANG AKAN DIKIRIM KE WEB UNTUK DIVISUALISASIKAN
        print(json.dumps({
            "status"  : status_label,
            "solution": solusi,
            "metrik"  : {
                "waktu_komputasi_detik": round(T, 4), "CSR": round(CSR, 2), "total_hard_constraints": total_hard, "jumlah_pelanggaran_hard": jml_hard, "detail_pelanggaran_hard": detail_hard, "breakdown_csr": breakdown_csr, "SCFR": round(SCFR, 2), "total_preferensi": total_soft, "jumlah_pelanggaran_soft": jml_soft, "toleransi_soft": TOLERANSI_SOFT, "detail_pelanggaran_soft": detail_soft, "breakdown_scfr": breakdown_scfr,
            },
            "tahapan_proses": {
                "tahap_1": {
                    "total_assignment": total_assignments_input,
                    "total_kelas": total_kelas_input,
                    "total_guru": total_guru_input,
                    "ket": "Total blok penugasan dan entitas mentah yang ditarik dari database sekolah (JSON)."
                },
                "tahap_2": {
                    "total_kemungkinan_awal": total_kemungkinan_awal,
                    "total_kombinasi_valid": total_kombinasi_valid,
                    "total_dipangkas": total_dipangkas,
                    "ket": f"Asumsi dasar: {total_assignments_input} assignment dikali 5 hari menghasilkan {total_kemungkinan_awal} kemungkinan jadwal. Sistem memangkas (Pruning) {total_dipangkas} jadwal yang mustahil (hari libur guru/batas jam), sehingga tersisa {total_kombinasi_valid} kemungkinan."
                },
                "tahap_3": {
                    "total_variabel": len(all_presence_vars) + len(all_start_vars) + len(end_vars),
                    "total_is_present": len(all_presence_vars),
                    "total_start": len(all_start_vars),
                    "ket": "Total variabel keputusan matematis (Boolean & Integer) yang dideklarasikan ke dalam model Constraint Solver."
                },
                "tahap_4": {
                    "status": solver_p1.StatusName(status_p1),
                    "waktu": round(T_p1, 2),
                    "ket": "Fase pencarian solusi dasar (Feasible) yang memenuhi aturan mutlak (Hard Constraints)."
                },
                "tahap_5": {
                    "status": status_label,
                    "waktu": round(T - T_p1, 2),
                    "gap": round(gap_pct, 4),
                    "ket": "Fase lanjutan (Branch & Bound) dengan injeksi nilai Hint untuk meminimalkan nilai penalti preferensi."
                },
                "tahap_6": {
                    "csr": round(CSR, 2),
                    "scfr": round(SCFR, 2),
                    "total_evaluasi": total_hard + total_soft,
                    "ket": f"Hasil akhir: Sistem melakukan total {total_hard + total_soft} evaluasi pengecekan jadwal secara independen untuk memastikan kualitas jadwal."
                }
            },
            "message": f"Solusi {status_label} ditemukan dalam {T:.2f} detik."
        }))

    else:
        print(json.dumps({"status": "INFEASIBLE", "metrik": _empty_metrik(time.time() - T_mulai)}))

if __name__ == '__main__':
    main()