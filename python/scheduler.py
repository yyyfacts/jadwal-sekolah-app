import sys
import json
import time  
from ortools.sat.python import cp_model

def main():
    # Mulai menghitung waktu komputasi AI
    start_time = time.time()  

    # ==========================================
    # 1. PERSIAPAN & BACA DATA JSON
    # ==========================================
    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "message": "Path file JSON tidak ditemukan!"}))
        return

    json_path = sys.argv[1]
    try:
        with open(json_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": f"Gagal membaca JSON: {str(e)}"}))
        return

    # Ambil data dari Laravel
    raw_assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    hari_info = data.get('hari_aktif', [])
    
    # --- LOGIKA BLOK SUSUN (HEURISTIK) ---
    # Urutkan dari SKS terbesar (misal 3 JP atau 4 JP) agar diprioritaskan duluan masuk ke jadwal
    raw_assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    hari_list = [h['nama'] for h in hari_info]
    
    # Mapping batas maksimal jam per hari secara GLOBAL (dari Master Waktu)
    global_max_jam_map = {h['nama']: int(h['max_jam']) for h in hari_info}
    
    # Mapping Limit Harian KHUSUS KELAS
    kelas_limits = {}
    for k in kelass:
        kelas_limits[k['id']] = {
            'normal': int(k.get('limit_harian', 10)),
            'jumat': int(k.get('limit_jumat', 9))
        }

    # FUNGSI SAKTI: Mencari batas paling ketat antara limit kelas vs limit fisik sekolah (global)
    def get_max_jam(kelas_id, hari):
        limit_kelas = kelas_limits.get(kelas_id, {'normal': 10, 'jumat': 9})
        batas_kelas = limit_kelas['jumat'] if hari == 'Jumat' else limit_kelas['normal']
        batas_global = global_max_jam_map.get(hari, 10)
        return min(batas_kelas, batas_global)

    # Mapping Pilihan Hari Mengajar Tiap Guru
    guru_hari_map = {}
    for g in gurus:
        allowed_days = g.get('hari_mengajar', [])
        # Jika Waka tidak memilih hari, berarti guru tersebut bersedia SEMUA HARI
        if not allowed_days:
            allowed_days = hari_list
        guru_hari_map[g['id']] = allowed_days

    # ==========================================
    # 2. MEMBANGUN MODEL MATEMATIKA (OR-TOOLS)
    # ==========================================
    model = cp_model.CpModel()

    starts = {}      
    presences = {}   
    all_start_vars = [] 

    intervals_per_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    
    # Wadah untuk menghitung beban guru per hari
    presences_per_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    
    tasks_per_mapel_group = {} 
    tasks_metadata = []

    for t in raw_assignments:
        durasi = int(t['jumlah_jam'])
        if durasi <= 0: continue
        
        t_id = t['id']
        g_id = t['guru_id']
        k_id = t['kelas_id']
        m_id = t.get('mapel_id')
        
        tasks_metadata.append({'id': t_id})

        group_key = (k_id, m_id if m_id else f"guru_{g_id}")
        if group_key not in tasks_per_mapel_group:
            tasks_per_mapel_group[group_key] = []
        tasks_per_mapel_group[group_key].append(t_id)

        possible_days = [] 

        for h in hari_list:
            # FILTER 1: Tolak jadwal jika hari ini TIDAK DICENTANG oleh Guru tersebut
            if h not in guru_hari_map.get(g_id, hari_list):
                continue 

            # FILTER 2: Cek batas jam maksimal hari ini untuk kelas ini
            batas_jam = get_max_jam(k_id, h)
            if durasi > batas_jam:
                continue # Kalau durasi mapel lebih panjang dari jam buka sekolah, buang!

            max_start = batas_jam - durasi + 1
            
            # Pembentukan Variabel Ruang/Waktu
            start_var = model.NewIntVar(1, max_start, f'start_{t_id}_{h}')
            end_var = model.NewIntVar(1 + durasi, batas_jam + 1, f'end_{t_id}_{h}')
            is_present = model.NewBoolVar(f'present_{t_id}_{h}')
            
            interval_var = model.NewOptionalIntervalVar(
                start_var, durasi, end_var, is_present, f'interval_{t_id}_{h}'
            )

            starts[(t_id, h)] = start_var
            presences[(t_id, h)] = is_present
            possible_days.append(is_present)
            all_start_vars.append(start_var)

            intervals_per_kelas[k_id][h].append(interval_var)
            intervals_per_guru[g_id][h].append(interval_var)
            
            presences_per_guru[g_id][h].append((is_present, durasi))

        # Wajibkan AI menaruh tugas ini TEPAT 1 KALI di salah satu hari yang diizinkan
        if possible_days:
            model.Add(sum(possible_days) == 1)
        else:
            print(json.dumps({
                "status": "INFEASIBLE", 
                "message": f"Bentrok Fatal! Tugas ID {t_id} (Guru ID {g_id}) tidak bisa masuk ke jadwal. Cek kecocokan hari mengajar dan SKS."
            }))
            return

    # ==========================================
    # 3. MEMASANG ATURAN (CONSTRAINTS)
    # ==========================================
    
    # A. Anti-Bentrok: Satu kelas tidak boleh diajar 2 guru bersamaan
    for k_id in intervals_per_kelas:
        for h in hari_list:
            if intervals_per_kelas[k_id][h]:
                model.AddNoOverlap(intervals_per_kelas[k_id][h])

    # B. Anti-Bentrok: Satu guru tidak boleh mengajar 2 kelas bersamaan
    for g in gurus:
        g_id = g['id']
        waktu_kosong = g.get('waktu_kosong', [])
        for h in hari_list:
            intervals = intervals_per_guru[g_id][h]
            # Masukkan dummy interval jika ada jam di mana guru ini berhalangan (Waktu Kosong)
            for wk in waktu_kosong:
                if wk['hari'] == h:
                    jam_libur = int(wk['jam'])
                    dummy = model.NewIntervalVar(jam_libur, 1, jam_libur+1, 'libur_guru')
                    intervals.append(dummy)
            if intervals:
                model.AddNoOverlap(intervals)

    # C. Distribusi Mapel: Jangan ada mapel yang sama muncul 2 kali di hari yang sama untuk 1 kelas
    for key, task_ids in tasks_per_mapel_group.items():
        if len(task_ids) > 1:
            for h in hari_list:
                daily_presence = [presences[(tid, h)] for tid in task_ids if (tid, h) in presences]
                if daily_presence:
                    model.Add(sum(daily_presence) <= 1)

    # D. Pemerataan Beban Harian Guru (SANGAT CERDAS)
    for g in gurus:
        g_id = g['id']
        total_sks_guru = sum(int(t['jumlah_jam']) for t in raw_assignments if t['guru_id'] == g_id)
        
        # Cari tahu berapa hari guru ini mengajar
        hari_aktif_guru = len(guru_hari_map.get(g_id, hari_list))
        if hari_aktif_guru == 0:
            hari_aktif_guru = 5 # Safety fallback (jangan sampai error dibagi 0)
            
        # Beri toleransi +2 agar jadwal tidak terlalu kaku
        batas_dinamis_harian = int(total_sks_guru / hari_aktif_guru) + 2
        
        for h in hari_list:
            beban_harian = []
            for is_present, durasi in presences_per_guru[g_id][h]:
                beban_harian.append(is_present * durasi)
            
            if beban_harian:
                model.Add(sum(beban_harian) <= batas_dinamis_harian)

    # ==========================================
    # 4. STRATEGI "BLOK SUSUN" / RATA KIRI
    # ==========================================
    # Paksa AI untuk menyusun jadwal dari jam paling pagi (SELECT_MIN_VALUE)
    # Ini yang membuat jadwal menjadi padat, rapat, dan tidak ada lubang di tengah.
    if all_start_vars:
        model.AddDecisionStrategy(all_start_vars, cp_model.CHOOSE_FIRST, cp_model.SELECT_MIN_VALUE)

    # ==========================================
    # 5. EKSEKUSI & CARI SOLUSI
    # ==========================================
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 300 # Limit waktu berpikir AI (5 Menit)
    solver.parameters.num_search_workers = 8    # Pakai 8 Core CPU biar ngebut
    
    status = solver.Solve(model)

    end_time = time.time()  
    waktu_komputasi = end_time - start_time  

    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        final_solution = []
        for t in tasks_metadata:
            t_id = t['id']
            for h in hari_list:
                if (t_id, h) in presences:
                    if solver.Value(presences[(t_id, h)]) == 1:
                        jam_mulai = solver.Value(starts[(t_id, h)])
                        final_solution.append({
                            'id': t_id,
                            'hari': h,
                            'jam': jam_mulai
                        })
                        break
        
        print(json.dumps({
            "status": "OPTIMAL",
            "solution": final_solution,
            "waktu_komputasi_detik": round(waktu_komputasi, 2), 
            "message": f"AI berhasil menyusun jadwal super padat dalam {waktu_komputasi:.2f} detik tanpa melanggar aturan!"
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE", 
            "waktu_komputasi_detik": round(waktu_komputasi, 2),
            "message": f"Gagal menyusun (Waktu: {waktu_komputasi:.2f} detik). Kombinasi jadwal mustahil disatukan. Cek beban guru atau batasan jam kelas."
        }))

if __name__ == '__main__':
    main()