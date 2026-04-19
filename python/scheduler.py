import sys
import json
import time
import math
from ortools.sat.python import cp_model

# =============================================================================
# KONSTANTA GLOBAL
# =============================================================================
HARI_LIST = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']
HARI_PENDEK = ['Senin', 'Selasa', 'Rabu', 'Kamis']


# =============================================================================
# FUNGSI UTILITAS
# =============================================================================

def load_json(path: str) -> dict:
    """Membaca dan memvalidasi file JSON masukan."""
    with open(path, 'r', encoding='utf-8') as f:
        return json.load(f)


def build_guru_maps(gurus: list) -> tuple[dict, dict]:
    """
    Membangun dua kamus indeks guru sekaligus dalam satu iterasi:
      - guru_hari_map    : { guru_id -> [hari aktif] }
      - guru_waktu_map   : { guru_id -> [{'hari':..., 'jam':...}] }

    Perbaikan dari versi sebelumnya:
      Waktu kosong guru di-cache di sini sehingga pencarian berikutnya O(1),
      bukan O(n) melalui next() di dalam loop bersarang.
    """
    guru_hari_map = {}
    guru_waktu_map = {}

    for g in gurus:
        g_id = g['id']
        allowed_days = g.get('hari_mengajar', [])
        if not allowed_days:
            allowed_days = HARI_LIST[:]
        guru_hari_map[g_id] = allowed_days
        guru_waktu_map[g_id] = g.get('waktu_kosong', [])

    return guru_hari_map, guru_waktu_map


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
    """
    Menghitung batas atas dan batas bawah jam mengajar guru per hari
    menggunakan heuristik distribusi rata-rata (load balancing).

    Toleransi +2 pada batas atas mengakomodasi blok pelajaran panjang.
    Toleransi -1 pada batas bawah mencegah constraint terlalu ketat
    yang dapat menyebabkan status INFEASIBLE.
    """
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
# FUNGSI DIAGNOSTIK INFEASIBILITY
# =============================================================================

def diagnosa_infeasible(
    raw_assignments: list,
    kelass: list,
    gurus: list,
    kelas_limits: dict,
    guru_hari_map: dict,
    max_jam_dinamis: dict,
    min_jam_dinamis: dict
) -> list[str]:
    """
    Melakukan analisis pre-solver untuk mendeteksi potensi penyebab
    kegagalan (INFEASIBLE) sebelum solver resmi dijalankan.

    Mengembalikan daftar pesan diagnostik yang dapat membantu administrator
    jadwal mengidentifikasi kendala yang saling berkonflik.
    """
    laporan = []

    # --- Cek 1: Tugas tanpa pilihan hari valid ---
    for t in raw_assignments:
        durasi   = int(t['jumlah_jam'])
        g_id     = t['guru_id']
        k_id     = t['kelas_id']
        hari_ok  = []

        for h in HARI_LIST:
            if h not in guru_hari_map.get(g_id, HARI_LIST):
                continue
            batas = get_max_jam(kelas_limits, k_id, h)
            if durasi <= batas:
                hari_ok.append(h)

        if not hari_ok:
            laporan.append(
                f"[FATAL] Tugas ID={t['id']} (guru={g_id}, kelas={k_id}, "
                f"durasi={durasi} jam) tidak memiliki satu pun hari yang valid. "
                f"Durasi mungkin melebihi semua batas jam harian."
            )

    # --- Cek 2: Total jam kelas tidak memenuhi kapasitas harian ---
    for k in kelass:
        k_id = k['id']
        tugas_kelas = [t for t in raw_assignments if t['kelas_id'] == k_id]

        for h in HARI_LIST:
            batas = get_max_jam(kelas_limits, k_id, h)
            # Estimasi kasar: berapa jam maksimal yang bisa diisi pada hari ini
            jam_tersedia = sum(
                int(t['jumlah_jam'])
                for t in tugas_kelas
                if h in guru_hari_map.get(t['guru_id'], HARI_LIST)
                and int(t['jumlah_jam']) <= batas
            )
            if jam_tersedia < batas:
                laporan.append(
                    f"[PERINGATAN] Kelas ID={k_id} pada hari {h}: "
                    f"estimasi jam tersedia ({jam_tersedia}) "
                    f"lebih kecil dari kapasitas wajib ({batas}). "
                    f"Jadwal mungkin tidak dapat dipenuhi."
                )

    # --- Cek 3: Guru dengan batas atas lebih kecil dari blok terpanjang ---
    for g in gurus:
        g_id      = g['id']
        batas_max = max_jam_dinamis.get(g_id, 0)
        for t in raw_assignments:
            if t['guru_id'] == g_id and int(t['jumlah_jam']) > batas_max:
                laporan.append(
                    f"[PERINGATAN] Guru ID={g_id}: blok durasi {t['jumlah_jam']} jam "
                    f"melebihi batas dinamis harian ({batas_max}). "
                    f"Batas atas akan disesuaikan otomatis."
                )

    return laporan


# =============================================================================
# FUNGSI MEMBANGUN MODEL CP-SAT
# =============================================================================

def bangun_model(
    raw_assignments: list,
    kelass: list,
    gurus: list,
    kelas_limits: dict,
    guru_hari_map: dict,
    guru_waktu_map: dict,
    max_jam_dinamis: dict,
    min_jam_dinamis: dict,
    mapel_busy: dict
) -> tuple[cp_model.CpModel, dict, dict, list, list]:
    """
    Membangun model CP-SAT secara lengkap:
      - Variabel keputusan (start, presence, interval)
      - Hard constraints (no-overlap, exactly-one, beban kelas)
      - Soft constraints (load balancing guru, penalti ketidakmerataan)
      - Fungsi objektif eksplisit (minimasi varians beban)

    Mengembalikan:
      model, starts, presences, all_start_vars, penalti_vars
    """
    model = cp_model.CpModel()

    starts    = {}
    presences = {}
    all_start_vars = []

    intervals_per_kelas    = {k['id']: {h: [] for h in HARI_LIST} for k in kelass}
    intervals_per_guru     = {g['id']: {h: [] for h in HARI_LIST} for g in gurus}
    durasi_per_kelas_harian = {k['id']: {h: [] for h in HARI_LIST} for k in kelass}
    durasi_per_guru_harian  = {g['id']: {h: [] for h in HARI_LIST} for g in gurus}

    tasks_per_mapel_group = {}

    # -----------------------------------------------------------------------
    # Perbaikan Bug Penamaan: counter unik untuk interval blocker
    # -----------------------------------------------------------------------
    blocker_counter = {}

    # =========================================================================
    # BAGIAN A: PEMBUATAN VARIABEL KEPUTUSAN
    # =========================================================================
    for t in raw_assignments:
        durasi  = int(t['jumlah_jam'])
        if durasi <= 0:
            continue

        t_id  = t['id']
        g_id  = t['guru_id']
        k_id  = t['kelas_id']
        m_id  = t.get('mapel_id')
        batas_maks_jam = t.get('batas_maksimal_jam')

        # Pengelompokan mapel yang sama dalam satu kelas (untuk spread constraint)
        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        tasks_per_mapel_group.setdefault(group_key, []).append(t_id)

        possible_days = []

        for h in HARI_LIST:
            # --- Domain pruning: hari tidak aktif guru ---
            if h not in guru_hari_map[g_id]:
                continue

            batas_jam = get_max_jam(kelas_limits, k_id, h)
            if durasi > batas_jam:
                continue

            max_start = batas_jam - durasi + 1
            if max_start < 1:
                continue

            # Pembuatan variabel keputusan per (tugas, hari)
            start_var   = model.NewIntVar(1, max_start, f's_{t_id}_{h}')
            end_var     = model.NewIntVar(1 + durasi, batas_jam + 1, f'e_{t_id}_{h}')
            is_present  = model.NewBoolVar(f'p_{t_id}_{h}')

            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'iv_{t_id}_{h}'
            )

            # Kendala batas maksimal jam akhir (jika ada)
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

            # --- Pemblokiran jam manual mapel ---
            if m_id in mapel_busy:
                for blocked in mapel_busy[m_id]:
                    if blocked['hari'] == h:
                        jam_blok = int(blocked['jam'])
                        if jam_blok <= batas_jam:
                            # PERBAIKAN: nama variabel blocker unik per (t_id, m_id, h, jam)
                            blk_key = (t_id, m_id, h, jam_blok)
                            blocker_counter[blk_key] = blocker_counter.get(blk_key, 0) + 1
                            nama_blk = (
                                f'blk_{t_id}_{m_id}_{h}_{jam_blok}'
                                f'_{blocker_counter[blk_key]}'
                            )
                            blocked_iv = model.NewIntervalVar(
                                jam_blok, 1, jam_blok + 1, nama_blk
                            )
                            model.AddNoOverlap([interval_var, blocked_iv])

        # Setiap tugas wajib dijadwalkan tepat satu kali
        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            # Pre-solver feasibility check — kembalikan sinyal error lebih awal
            return None, None, None, None, None

    # =========================================================================
    # BAGIAN B: HARD CONSTRAINTS — BEBAN KELAS HARIAN
    # =========================================================================
    for k in kelass:
        k_id = k['id']
        batas_jumat  = kelas_limits[k_id]['jumat']
        batas_harian = kelas_limits[k_id]['harian']

        for h in HARI_LIST:
            beban = durasi_per_kelas_harian[k_id][h]
            if not beban:
                continue
            target = batas_jumat if h == 'Jumat' else batas_harian
            model.Add(sum(beban) == target)

    # =========================================================================
    # BAGIAN C: HARD CONSTRAINTS — ANTI-TABRAKAN
    # =========================================================================
    # Anti-tabrakan kelas
    for k_id in intervals_per_kelas:
        for h in HARI_LIST:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    # Anti-tabrakan guru + jam kosong guru (cache O(1))
    for g_id in intervals_per_guru:
        waktu_kosong = guru_waktu_map[g_id]          # O(1) lookup

        for h in HARI_LIST:
            ivs = list(intervals_per_guru[g_id][h])  # salin agar tidak mutasi asli

            for wk in waktu_kosong:
                if wk['hari'] == h:
                    jam_libur = int(wk['jam'])
                    dummy = model.NewIntervalVar(
                        jam_libur, 1, jam_libur + 1,
                        f'libur_{g_id}_{h}_{jam_libur}'
                    )
                    ivs.append(dummy)

            if ivs:
                model.AddNoOverlap(ivs)

    # Satu mapel tidak boleh dijadwalkan dua kali pada hari yang sama (spread constraint)
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in HARI_LIST:
                daily_presence = [
                    presences[(tid, h)]
                    for tid in task_ids
                    if (tid, h) in presences
                ]
                if daily_presence:
                    model.AddAtMostOne(daily_presence)

    # =========================================================================
    # BAGIAN D: SOFT CONSTRAINTS — LOAD BALANCING GURU (dengan variabel penalti)
    # =========================================================================
    # Batas atas dan bawah beban guru per hari tetap dipertahankan sebagai
    # hard constraint. Sebagai tambahan, varians beban antar hari dihitung
    # melalui variabel penalti untuk dimasukkan ke fungsi objektif.
    penalti_vars = []

    for g in gurus:
        g_id      = g['id']
        batas_atas = max_jam_dinamis[g_id]
        batas_bwh  = min_jam_dinamis[g_id]
        hari_aktif = guru_hari_map[g_id]

        for h in HARI_LIST:
            beban_guru = durasi_per_guru_harian[g_id][h]
            if not beban_guru:
                continue

            # Hard constraint: batas atas
            model.Add(sum(beban_guru) <= batas_atas)

            # Hard constraint: batas bawah (dengan diskon Jumat)
            if h in hari_aktif and batas_bwh > 0:
                batas_efektif = max(1, batas_bwh - 1) if h == 'Jumat' else batas_bwh
                model.Add(sum(beban_guru) >= batas_efektif)

            # Soft constraint: penalti deviasi dari rata-rata
            # Menggunakan variabel bantu untuk | beban - rata-rata |
            if batas_atas > 0:
                rata_rata_target = (batas_atas + batas_bwh) // 2
                deviasi = model.NewIntVar(0, batas_atas, f'dev_{g_id}_{h}')
                # Linearisasi nilai absolut: deviasi >= beban - target
                #                           deviasi >= target - beban
                model.Add(deviasi >= sum(beban_guru) - rata_rata_target)
                model.Add(deviasi >= rata_rata_target - sum(beban_guru))
                penalti_vars.append(deviasi)

    # Soft constraint: guru tidak lebih dari 3 jam berturut-turut
    # Diimplementasikan sebagai penalti kelebihan pada slot berurutan
    # (Pendekatan sederhana: jika sum jam per hari guru > threshold, tambah penalti)
    MAKS_JAM_BERTURUT = 3
    for g in gurus:
        g_id = g['id']
        for h in HARI_LIST:
            beban_guru = durasi_per_guru_harian[g_id][h]
            if not beban_guru:
                continue
            kelebihan = model.NewIntVar(0, max_jam_dinamis[g_id], f'lebih_{g_id}_{h}')
            # kelebihan = max(0, total_jam - MAKS_JAM_BERTURUT)
            model.Add(kelebihan >= sum(beban_guru) - MAKS_JAM_BERTURUT)
            model.Add(kelebihan >= 0)
            # Bobot penalti lebih tinggi untuk mendorong distribusi
            penalti_vars.append(kelebihan * 2)

    return model, starts, presences, all_start_vars, penalti_vars


# =============================================================================
# FUNGSI OBJEKTIF DAN STRATEGI PENCARIAN
# =============================================================================

def konfigurasi_solver_dan_objektif(
    model: cp_model.CpModel,
    all_start_vars: list,
    penalti_vars: list
) -> cp_model.CpSolver:
    """
    Menetapkan fungsi objektif eksplisit (minimasi total penalti distribusi)
    dan strategi pencarian (decision strategy).

    Perbaikan dari versi sebelumnya:
      - Versi lama hanya mencari solusi FEASIBLE (tidak ada fungsi objektif).
      - Versi baru meminimalkan total deviasi beban guru agar jadwal lebih merata.
    """
    # Fungsi objektif: minimasi total penalti (deviasi beban + kelebihan jam berturut)
    if penalti_vars:
        model.Minimize(sum(penalti_vars))

    # Decision strategy: tempatkan di jam terkecil yang tersedia (left-to-right)
    if all_start_vars:
        model.AddDecisionStrategy(
            all_start_vars,
            cp_model.CHOOSE_FIRST,
            cp_model.SELECT_MIN_VALUE
        )

    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300
    solver.parameters.num_search_workers  = 8

    return solver


# =============================================================================
# FUNGSI UTAMA
# =============================================================================

def main():
    start_time = time.time()

    # -------------------------------------------------------------------------
    # 1. Validasi argumen dan baca input
    # -------------------------------------------------------------------------
    if len(sys.argv) < 2:
        print(json.dumps({
            "status": "ERROR",
            "message": "Path file JSON diperlukan sebagai argumen."
        }))
        return

    try:
        data = load_json(sys.argv[1])
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": str(e)}))
        return

    raw_assignments = data.get('assignments', [])
    kelass          = data.get('kelass', [])
    gurus           = data.get('gurus', [])

    # Heuristik MCV: proses blok berdurasi panjang terlebih dahulu
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    # -------------------------------------------------------------------------
    # 2. Bangun struktur data pendukung
    # -------------------------------------------------------------------------
    # PERBAIKAN: kedua map dibangun sekaligus dalam satu iterasi
    guru_hari_map, guru_waktu_map = build_guru_maps(gurus)
    kelas_limits                  = build_kelas_limits(kelass)

    # Kendala pemblokiran jam manual per mapel
    mapel_busy = {}
    for m in data.get('mapel_constraints', []):
        mapel_busy[m['id']] = m['waktu_kosong']

    # Hitung batas dinamis beban guru
    max_jam_dinamis, min_jam_dinamis = hitung_batas_dinamis_guru(
        gurus, raw_assignments, guru_hari_map
    )

    # -------------------------------------------------------------------------
    # 3. Diagnosa pre-solver
    # -------------------------------------------------------------------------
    laporan_diagnostik = diagnosa_infeasible(
        raw_assignments, kelass, gurus,
        kelas_limits, guru_hari_map,
        max_jam_dinamis, min_jam_dinamis
    )

    # Laporkan peringatan ke stderr agar tidak mencemari output JSON utama
    for pesan in laporan_diagnostik:
        print(pesan, file=sys.stderr)

    # -------------------------------------------------------------------------
    # 4. Bangun model CP-SAT
    # -------------------------------------------------------------------------
    model, starts, presences, all_start_vars, penalti_vars = bangun_model(
        raw_assignments, kelass, gurus,
        kelas_limits, guru_hari_map, guru_waktu_map,
        max_jam_dinamis, min_jam_dinamis,
        mapel_busy
    )

    # Deteksi kegagalan fatal pra-model (tugas tanpa hari valid)
    if model is None:
        waktu = time.time() - start_time
        print(json.dumps({
            "status": "INFEASIBLE",
            "message": (
                f"Bentrok fatal terdeteksi sebelum solver dijalankan "
                f"({waktu:.2f} detik). "
                f"Periksa log diagnostik untuk detail."
            ),
            "diagnostik": laporan_diagnostik
        }))
        return

    # -------------------------------------------------------------------------
    # 5. Konfigurasi solver dan fungsi objektif
    # -------------------------------------------------------------------------
    solver = konfigurasi_solver_dan_objektif(model, all_start_vars, penalti_vars)

    # -------------------------------------------------------------------------
    # 6. Eksekusi solver
    # -------------------------------------------------------------------------
    status = solver.Solve(model)
    waktu_komputasi = time.time() - start_time

    # -------------------------------------------------------------------------
    # 7. Ekstraksi dan output solusi
    # -------------------------------------------------------------------------
    # PERBAIKAN: tasks_metadata dihapus; gunakan raw_assignments langsung
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

        print(json.dumps({
            "status":        status_label,
            "solution":      solusi,
            "total_penalti": int(total_penalti),
            "waktu_detik":   round(waktu_komputasi, 2),
            "message": (
                f"Jadwal berhasil disusun ({status_label}) dalam "
                f"{waktu_komputasi:.2f} detik. "
                f"Total penalti distribusi: {int(total_penalti)}."
            )
        }))

    else:
        # Status INFEASIBLE: sertakan laporan diagnostik untuk membantu debugging
        print(json.dumps({
            "status":     "INFEASIBLE",
            "waktu_detik": round(waktu_komputasi, 2),
            "message": (
                f"Solver tidak dapat menemukan solusi dalam "
                f"{waktu_komputasi:.2f} detik. "
                f"Periksa bagian 'diagnostik' untuk kemungkinan penyebab."
            ),
            "diagnostik": laporan_diagnostik
        }))


if __name__ == '__main__':
    main()