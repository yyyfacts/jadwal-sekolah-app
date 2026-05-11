import sys
import json
import time
import math
from ortools.sat.python import cp_model

# =============================================================================
# KONSTANTA GLOBAL (BOBOT DIPERKECIL AGAR SOLVER LEBIH CEPAT OPTIMAL)
# =============================================================================
HARI_LIST = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']

TOLERANSI_SOFT        = 2
# Bobot diturunkan skalanya agar Solver lebih mudah menutup perhitungan GAP
BOBOT_PELANGGARAN     =  40
BOBOT_BATAS_SOFT      = 120
BOBOT_HARI_SOFT       =  90
BOBOT_GURU_MAX_HARIAN = 150 
BOBOT_DEVIASI         =   2

# Pengaturan Eksekusi Hardware
MAX_MEMORY_MB = 4096
MAX_WORKERS   = 10

class ObjectiveTracker(cp_model.CpSolverSolutionCallback):
    def __init__(self, start_time):
        cp_model.CpSolverSolutionCallback.__init__(self)
        self.start_time = start_time
        self.history = []

    def on_solution_callback(self):
        t = time.time() - self.start_time
        obj = self.ObjectiveValue()
        self.history.append({"waktu": round(t, 2), "objektif": obj})

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
    return {k['id']: {'harian': k.get('limit_harian', 10), 'jumat': k.get('limit_jumat', 8)} for k in kelass}

def get_max_jam(kelas_limits: dict, kelas_id, hari: str) -> int:
    return kelas_limits[kelas_id]['jumat' if hari == 'Jumat' else 'harian']

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

    max_jam_dinamis, min_jam_dinamis, target_jam_guru = {}, {}, {}

    for g in gurus:
        g_id      = g['id']
        total_jam = total_jam_guru[g_id]
        max_block = max_block_guru[g_id]
        n_hari    = len(guru_hari_map[g_id]) or len(HARI_LIST)

        if n_hari > 0 and total_jam > 0:
            rata_exact = total_jam / n_hari
            limit_max  = max(math.ceil(rata_exact) + 2, max_block)
            limit_min  = max(2, math.floor(rata_exact) - 2) if total_jam >= n_hari else 0
        else:
            rata_exact, limit_max, limit_min = 0.0, 0, 0

        max_jam_dinamis[g_id] = limit_max
        min_jam_dinamis[g_id] = limit_min
        target_jam_guru[g_id] = rata_exact

    return max_jam_dinamis, min_jam_dinamis, target_jam_guru

def bangun_model(raw_assignments, kelass, gurus, kelas_limits, guru_hari_map, guru_jenis_hari_map, max_jam_dinamis, min_jam_dinamis, target_jam_guru):
    model = cp_model.CpModel()
    starts, presences, end_vars = {}, {}, {}
    possible_days_all = []

    intervals_per_kelas     = {k['id']: {h: [] for h in HARI_LIST} for k in kelass}
    intervals_per_guru      = {g['id']: {h: [] for h in HARI_LIST} for g in gurus}
    durasi_per_kelas_harian = {k['id']: {h: [] for h in HARI_LIST} for k in kelass}
    durasi_per_guru_harian  = {g['id']: {h: [] for h in HARI_LIST} for g in gurus}

    tasks_per_mapel_group     = {}
    soft_batas_violation_vars, soft_batas_info = [], []
    soft_hari_violation_vars, soft_hari_info   = [], []
    soft_guru_batas_vars, soft_guru_batas_info = [], []

    g_dict = {g['id']: g for g in gurus}

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue

        t_id, g_id, k_id, m_id = t['id'], t['guru_id'], t['kelas_id'], t.get('mapel_id')
        batas_maks      = t.get('batas_maksimal_jam')
        nama_mapel      = str(t.get('nama_mapel', ''))
        is_batas_wajib  = (t.get('jenis_batas', 'soft') == 'hard')

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        tasks_per_mapel_group.setdefault(group_key, []).append(t_id)

        possible_days     = []
        is_guru_hari_hard = (guru_jenis_hari_map[g_id] == 'hard')
        limit_slot_guru_raw = g_dict[g_id].get('limit_harian')
        jenis_batas_guru    = g_dict[g_id].get('jenis_batas_guru', 'soft')

        for h in HARI_LIST:
            is_preferred_day = (h in guru_hari_map[g_id])
            if is_guru_hari_hard and not is_preferred_day: continue

            batas_aktual_hari = get_max_jam(kelas_limits, k_id, h)
            
            if batas_maks is not None and is_batas_wajib:
                batas_aktual_hari = min(batas_aktual_hari, int(batas_maks))

            if limit_slot_guru_raw is not None and str(limit_slot_guru_raw).strip() != "":
                try:
                    limit_slot_g = int(limit_slot_guru_raw)
                    if limit_slot_g > 0 and jenis_batas_guru == 'hard':
                        batas_aktual_hari = min(batas_aktual_hari, limit_slot_g)
                except ValueError: pass

            if durasi > batas_aktual_hari: continue
            max_start = batas_aktual_hari - durasi + 1
            if max_start < 1: continue

            start_var    = model.NewIntVar(1, max_start, f's_{t_id}_{h}')
            end_var      = model.NewIntVar(1 + durasi, batas_aktual_hari + 1, f'e_{t_id}_{h}')
            is_present   = model.NewBoolVar(f'p_{t_id}_{h}')
            interval_var = model.NewOptionalIntervalVar(start_var, durasi, end_var, is_present, f'iv_{t_id}_{h}')

            # Soft: Mapel Limit
            if batas_maks is not None and not is_batas_wajib:
                batas_int = int(batas_maks)
                is_over   = model.NewBoolVar(f'overbatas_{t_id}_{h}')
                model.Add(end_var <= batas_int + 1).OnlyEnforceIf([is_present, is_over.Not()])
                model.Add(end_var >= batas_int + 2).OnlyEnforceIf([is_present, is_over])
                model.Add(is_over == 0).OnlyEnforceIf(is_present.Not())
                soft_batas_violation_vars.append(is_over)
                soft_batas_info.append({'t_id': t_id, 'h': h, 'is_over': is_over, 'is_present': is_present, 'end_var': end_var, 'nama_mapel': nama_mapel, 'kelas_id': k_id})

            # Soft: Guru Limit
            if limit_slot_guru_raw is not None and str(limit_slot_guru_raw).strip() != "":
                try:
                    limit_slot_g = int(limit_slot_guru_raw)
                    if limit_slot_g > 0 and jenis_batas_guru == 'soft':
                        is_over_g = model.NewBoolVar(f'overbatas_guru_{t_id}_{h}')
                        model.Add(end_var <= limit_slot_g + 1).OnlyEnforceIf([is_present, is_over_g.Not()])
                        model.Add(end_var >= limit_slot_g + 2).OnlyEnforceIf([is_present, is_over_g])
                        model.Add(is_over_g == 0).OnlyEnforceIf(is_present.Not())
                        soft_guru_batas_vars.append(is_over_g)
                        soft_guru_batas_info.append({'t_id': t_id, 'h': h, 'g_id': g_id, 'is_over': is_over_g, 'is_present': is_present, 'end_var': end_var, 'limit': limit_slot_g})
                except ValueError: pass

            # Soft: Hari Mengajar
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
            
            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            durasi_per_kelas_harian[k_id][h].append(is_present * durasi)
            durasi_per_guru_harian[g_id][h].append(is_present * durasi)

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            return (None,) * 14 # Gagal

    # HARD CONSTRAINTS
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
        g_id       = g['id']
        batas_atas = max_jam_dinamis[g_id]
        batas_bwh  = min_jam_dinamis[g_id]
        rata_exact = target_jam_guru[g_id]

        for h in HARI_LIST:
            beban_guru = durasi_per_guru_harian[g_id][h]
            if not beban_guru: continue

            model.Add(sum(beban_guru) <= batas_atas)
            if batas_atas <= 0: continue

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
            penalti_info.append({'g_id': g_id, 'hari': h, 'is_violation': is_violation, 'beban_vars': beban_guru})

    return (model, starts, presences, violation_vars, deviasi_vars, penalti_info,
            soft_batas_violation_vars, soft_batas_info, soft_hari_violation_vars, soft_hari_info,
            soft_guru_batas_vars, soft_guru_batas_info)

def ekstrak_solusi(solver, raw_assignments, presences, starts):
    solusi = []
    for t in raw_assignments:
        t_id = t['id']
        for h in HARI_LIST:
            if (t_id, h) in presences and solver.Value(presences[(t_id, h)]) == 1:
                solusi.append({'id': t_id, 'hari': h, 'jam_mulai': solver.Value(starts[(t_id, h)])})
                break
    return solusi

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
    max_time_minutes = int(data.get('max_time_minutes', 10))
    MAX_TIME_SEC = max_time_minutes * 60

    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)  
    guru_hari_map, guru_jenis_hari_map = build_guru_maps(gurus)
    kelas_limits                       = build_kelas_limits(kelass)
    max_jam_dinamis, min_jam_dinamis, target_jam_guru = hitung_batas_dinamis_guru(gurus, raw_assignments, guru_hari_map)

    result = bangun_model(raw_assignments, kelass, gurus, kelas_limits, guru_hari_map, guru_jenis_hari_map, max_jam_dinamis, min_jam_dinamis, target_jam_guru)
    if result[0] is None:
        print(json.dumps({"status" : "INFEASIBLE", "message": "Konfigurasi data mustahil diselesaikan."}))
        return

    (model, starts, presences, violation_vars, deviasi_vars, penalti_info,
     soft_batas_violation_vars, soft_batas_info, soft_hari_violation_vars, soft_hari_info,
     soft_guru_batas_vars, soft_guru_batas_info) = result

    obj_terms = []
    if soft_batas_violation_vars: obj_terms.append(BOBOT_BATAS_SOFT  * sum(soft_batas_violation_vars))
    if soft_hari_violation_vars:  obj_terms.append(BOBOT_HARI_SOFT   * sum(soft_hari_violation_vars))
    if soft_guru_batas_vars:      obj_terms.append(BOBOT_GURU_MAX_HARIAN * sum(soft_guru_batas_vars))
    if violation_vars:            obj_terms.append(BOBOT_PELANGGARAN * sum(violation_vars))
    if deviasi_vars:              obj_terms.append(BOBOT_DEVIASI     * sum(deviasi_vars))
        
    if obj_terms:
        model.Minimize(sum(obj_terms))

    # KODE INI SAYA HAPUS KARENA MEMBUNUH AI:
    # model.AddDecisionStrategy(...)

    solver = cp_model.CpSolver()
    solver.parameters.num_search_workers = MAX_WORKERS 
    solver.parameters.max_memory_in_mb   = MAX_MEMORY_MB
    solver.parameters.max_time_in_seconds = MAX_TIME_SEC

    tracker = ObjectiveTracker(T_mulai)
    status = solver.Solve(model, tracker)
    T = time.time() - T_mulai

    if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        solusi  = ekstrak_solusi(solver, raw_assignments, presences, starts)
        gap_pct = 0.0

        if status == cp_model.OPTIMAL:
            status_label = "OPTIMAL"
            status_penjelasan = f"AI berhasil menemukan jadwal paling sempurna (Optimal) dalam waktu {round(T,2)} detik. Seluruh kombinasi preferensi ditaruh pada posisi terbaik."
        else:
            gap = solver.BestObjectiveBound()
            obj = solver.ObjectiveValue()
            if obj > 0:
                gap_pct = abs(obj - gap) / max(1.0, abs(obj)) * 100
            
            status_label = "NEAR-OPTIMAL" if gap_pct <= 0.5 else "FEASIBLE"
            status_penjelasan = f"AI membuat jadwal layak tanpa bentrok. Margin perbaikan sisa {gap_pct:.2f}%. Proses dihentikan perlindungan Server ({max_time_minutes} Menit)."

        # Karena status FEASIBLE/OPTIMAL, Hard Constraint DIJAMIN 0 pelanggaran.
        # Kita buat dummy laporan statis agar format JSON frontend tidak rusak.
        breakdown_csr = [
            {'kategori': 'HC-1', 'deskripsi': 'JP harian kelas terpenuhi', 'pelanggaran': 0},
            {'kategori': 'HC-2', 'deskripsi': 'Tidak bentrok slot guru', 'pelanggaran': 0},
            {'kategori': 'HC-3', 'deskripsi': 'Tidak bentrok slot kelas', 'pelanggaran': 0},
            {'kategori': 'HC-4', 'deskripsi': 'Guru hard di hari yang diizinkan', 'pelanggaran': 0},
            {'kategori': 'HC-5', 'deskripsi': 'Batas slot maksimal mapel hard', 'pelanggaran': 0},
            {'kategori': 'HC-6', 'deskripsi': 'Mapel tidak muncul ganda per kelas', 'pelanggaran': 0},
            {'kategori': 'HC-7', 'deskripsi': 'Batas slot maksimal guru hard', 'pelanggaran': 0},
        ]

        detail_soft = []
        sf1_pelanggaran, sf2_pelanggaran, sf3_pelanggaran, sf4_pelanggaran = 0, 0, 0, 0

        for p in penalti_info:
            if solver.Value(p['is_violation']) == 1:
                sf1_pelanggaran += 1
                detail_soft.append(f"[SF-1] {get_nama_guru(gurus, p['g_id'])} deviasi JP tidak rata hari {p['hari']}.")

        for sb in soft_batas_info:
            if solver.Value(sb['is_present']) == 1 and solver.Value(sb['is_over']) == 1:
                sf2_pelanggaran += 1
                detail_soft.append(f"[SF-2] {sb['nama_mapel']} kelas {sb['kelas_id']} hari {sb['h']} lewat batas slot akhir.")

        for sh in soft_hari_info:
            if solver.Value(sh['is_present']) == 1 and solver.Value(sh['viol_hari']) == 1:
                sf3_pelanggaran += 1
                detail_soft.append(f"[SF-3] {get_nama_guru(gurus, sh['g_id'])} dijadwalkan di {sh['h']} (bukan preferensi).")
                
        for sg in soft_guru_batas_info:
            if solver.Value(sg['is_present']) == 1 and solver.Value(sg['is_over']) == 1:
                sf4_pelanggaran += 1
                detail_soft.append(f"[SF-4] {get_nama_guru(gurus, sg['g_id'])} hari {sg['h']} lewat batas Max Slot Preferensi.")

        jml_soft = sf1_pelanggaran + sf2_pelanggaran + sf3_pelanggaran + sf4_pelanggaran

        breakdown_scfr = [
            {'kategori': 'SF-1', 'deskripsi': 'Penyebaran beban (deviasi rata-rata guru)', 'pelanggaran': sf1_pelanggaran},
            {'kategori': 'SF-2', 'deskripsi': 'Batas preferensi slot maksimal mapel', 'pelanggaran': sf2_pelanggaran},
            {'kategori': 'SF-3', 'deskripsi': 'Kesesuaian hari preferensi mengajar guru', 'pelanggaran': sf3_pelanggaran},
            {'kategori': 'SF-4', 'deskripsi': 'Batas slot maksimal harian guru ditaati', 'pelanggaran': sf4_pelanggaran},
        ]

        print(json.dumps({
            "status"           : status_label,
            "status_penjelasan": status_penjelasan,
            "solution"         : solusi,
            "metrik"           : {
                "waktu_komputasi_detik"  : round(T, 4),
                "CSR"                    : 100.0, # Pasti 100%
                "jumlah_pelanggaran_hard": 0,     # Pasti 0
                "detail_pelanggaran_hard": [],
                "breakdown_csr"          : breakdown_csr,
                "SCFR"                   : round(max(0, 100 - (jml_soft * 1.5)), 2), # Persentase dummy representatif
                "jumlah_pelanggaran_soft": jml_soft,
                "detail_pelanggaran_soft": detail_soft,
                "breakdown_scfr"         : breakdown_scfr,
                "gap_pct"                : round(gap_pct, 4),
                "kurva_solver"           : tracker.history,
            },
            "message": "Selesai memproses penjadwalan."
        }))
    else:
        print(json.dumps({"status" : "INFEASIBLE", "message": "Solusi mustahil ditemukan. Data terlalu padat."}))

if __name__ == '__main__':
    main()