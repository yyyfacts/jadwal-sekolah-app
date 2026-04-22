import sys
import json
import time
import math
from ortools.sat.python import cp_model

# =============================================================================
# KONSTANTA GLOBAL
# =============================================================================
HARI_LIST = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']

# =============================================================================
# FUNGSI UTILITAS
# =============================================================================

def load_json(path: str) -> dict:
    """Membaca file JSON masukan."""
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)

def build_guru_maps(gurus: list) -> dict:
    """
    Membangun kamus indeks guru untuk hari aktif.
    Waktu kosong di tengah hari telah dihapus sesuai instruksi.
    """
    guru_hari_map = {}
    for g in gurus:
        g_id = g['id']
        allowed_days = g.get('hari_mengajar', [])
        # Jika array kosong atau tidak ada, asumsikan aktif semua hari
        if not allowed_days:
            allowed_days = HARI_LIST[:]
        guru_hari_map[g_id] = allowed_days

    return guru_hari_map

def build_kelas_limits(kelass: list) -> dict:
    """Membangun kamus batas jam harian dan Jumat per kelas."""
    return {
        k['id']: {
            'harian': k.get('limit_harian', 10),
            'jumat':  k.get('limit_jumat', 8)
        }
        for k in kelass
    }

def get_max_jam(kelas_limits: dict, kelas_id: str, hari: str) -> int:
    """Mengembalikan batas jam maksimal kelas pada hari tertentu."""
    if hari == 'Jumat':
        return kelas_limits[kelas_id]['jumat']
    return kelas_limits[kelas_id]['harian']

def hitung_batas_dinamis_guru(
    gurus: list,
    raw_assignments: list,
    guru_hari_map: dict
) -> tuple[dict, dict]:
    """Menghitung batas atas dan bawah jam mengajar guru per hari."""
    total_jam_guru = {g['id']: 0 for g in gurus}
    max_block_guru = {g['id']: 0 for g in gurus}

    for t in raw_assignments:
        g_id = t['guru_id']
        durasi = int(t['jumlah_jam'])
        if g_id in total_jam_guru:
            total_jam_guru[g_id] += durasi
            if durasi > max_block_guru[g_id]:
                max_block_guru[g_id] = durasi

    max_jam_dinamis = {}
    min_jam_dinamis = {}

    for g in gurus:
        g_id = g['id']
        total_jam = total_jam_guru[g_id]
        max_block = max_block_guru[g_id]

        hari_aktif = guru_hari_map[g_id]
        jumlah_hari_aktif = len(hari_aktif) if hari_aktif else len(HARI_LIST)

        if jumlah_hari_aktif > 0:
            rata_atas  = math.ceil(total_jam / jumlah_hari_aktif)
            rata_bawah = math.floor(total_jam / jumlah_hari_aktif)
            limit_max  = max(rata_atas + 2, max_block)

            if total_jam >= jumlah_hari_aktif * 2:
                limit_min = max(1, rata_bawah - 1)
            else:
                limit_min = 0
        else:
            limit_max = 0
            limit_min = 0

        max_jam_dinamis[g_id] = limit_max
        min_jam_dinamis[g_id] = limit_min

    return max_jam_dinamis, min_jam_dinamis


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
    min_jam_dinamis: dict
) -> tuple[cp_model.CpModel, dict, dict, list, list, int]:
    
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
        durasi  = int(t['jumlah_jam'])
        if durasi <= 0: continue

        t_id  = t['id']
        g_id  = t['guru_id']
        k_id  = t['kelas_id']
        m_id  = t.get('mapel_id')
        batas_maks_jam = t.get('batas_maksimal_jam')

        # Pengelompokan untuk memastikan 1 mapel tidak muncul 2x di hari yang sama
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

            start_var   = model.NewIntVar(1, max_start, f's_{t_id}_{h}')
            end_var     = model.NewIntVar(1 + durasi, batas_jam + 1, f'e_{t_id}_{h}')
            is_present  = model.NewBoolVar(f'p_{t_id}_{h}')

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

        # Hard Constraint: Tiap blok wajib dijadwalkan tepat 1 kali
        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            return None, None, None, None, None, 0

    # =========================================================================
    # B: HARD CONSTRAINTS
    # =========================================================================
    # 1. Batas Jam Harian Kelas
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

    # 3. Spread Constraint (Mapel yang sama tidak boleh 2x sehari)
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in HARI_LIST:
                daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_presence:
                    model.AddAtMostOne(daily_presence)

    # =========================================================================
    # C: SOFT CONSTRAINTS (PENALTI PEMERATAAN GURU)
    # =========================================================================
    penalti_vars = []
    max_possible_penalti = 0

    for g in gurus:
        g_id      = g['id']
        batas_atas = max_jam_dinamis[g_id]
        batas_bwh  = min_jam_dinamis[g_id]

        for h in HARI_LIST:
            beban_guru = durasi_per_guru_harian[g_id][h]
            if not beban_guru: continue

            # Batas absolut agar solver tidak error
            model.Add(sum(beban_guru) <= batas_atas)

            if batas_atas > 0:
                rata_rata_target = (batas_atas + batas_bwh) // 2
                deviasi = model.NewIntVar(0, batas_atas, f'dev_{g_id}_{h}')
                
                # Linearisasi nilai absolut
                model.Add(deviasi >= sum(beban_guru) - rata_rata_target)
                model.Add(deviasi >= rata_rata_target - sum(beban_guru))
                
                penalti_vars.append(deviasi)
                max_possible_penalti += batas_atas

    return model, starts, presences, all_start_vars, penalti_vars, max_possible_penalti


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

    # PENTING: Pengurutan blok berdasarkan durasi terbesar (MCV Heuristic)
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    guru_hari_map = build_guru_maps(gurus)
    kelas_limits  = build_kelas_limits(kelass)

    max_jam_dinamis, min_jam_dinamis = hitung_batas_dinamis_guru(
        gurus, raw_assignments, guru_hari_map
    )

    model, starts, presences, all_start_vars, penalti_vars, max_penalti = bangun_model(
        raw_assignments, kelass, gurus,
        kelas_limits, guru_hari_map,
        max_jam_dinamis, min_jam_dinamis
    )

    if model is None:
        waktu = time.time() - T_mulai
        print(json.dumps({
            "status": "INFEASIBLE",
            "message": "Bentrok fatal terdeteksi sebelum solver dijalankan.",
            "metrik": {
                "waktu_komputasi_detik": round(waktu, 4),
                "CSR": 0,
                "SCFR": 0
            }
        }))
        return

    # Set Objektif: Minimalkan ketidakmerataan
    if penalti_vars:
        model.Minimize(sum(penalti_vars))

    if all_start_vars:
        model.AddDecisionStrategy(
            all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE
        )

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300
    solver.parameters.num_search_workers  = 8

    # Eksekusi Solver
    status = solver.Solve(model)
    T_selesai = time.time()
    
    # 1. Waktu Komputasi (T)
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
        total_penalti = solver.ObjectiveValue() if penalti_vars else 0
        
        # 2. Constraint Satisfaction Rate (CSR)
        # Jika CP-SAT menghasilkan solusi, berarti 100% hard constraint terpenuhi.
        CSR = 100 

        # 3. Soft Constraint Fulfillment Rate (SCFR)
        if max_penalti > 0:
            SCFR = 100 * (1 - (total_penalti / max_penalti))
        else:
            SCFR = 100

        print(json.dumps({
            "status": status_label,
            "solution": solusi,
            "metrik": {
                "waktu_komputasi_detik": round(T, 4),
                "CSR": CSR,
                "SCFR": round(SCFR, 2)
            },
            "message": f"Solusi {status_label} ditemukan dalam {T:.2f} detik."
        }))

    else:
        print(json.dumps({
            "status": "INFEASIBLE",
            "metrik": {
                "waktu_komputasi_detik": round(T, 4),
                "CSR": 0,
                "SCFR": 0
            },
            "message": f"Solver gagal menemukan solusi dalam {T:.2f} detik."
        }))

if __name__ == '__main__':
    main()