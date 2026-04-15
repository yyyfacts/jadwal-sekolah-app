import json
import sys
import os
from ortools.sat.python import cp_model

def solve():
    # 1. Load Data dari Laravel
    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "message": "No input file provided"}))
        return

    json_path = sys.argv[1]
    with open(json_path, 'r') as f:
        data = json.load(f)

    model = cp_model.CpModel()

    # --- Persiapan Data ---
    hari_aktif = data['hari_aktif'] # [{'nama': 'Senin', 'max_jam': 10}, ...]
    assignments = data['assignments'] # [{'id': 1, 'guru_id': 5, 'jumlah_jam': 2, ...}]
    gurus = {g['id']: g for g in data['gurus']}
    kelass = {k['id']: k for k in data['kelass']}

    # Mapping Hari ke Index
    hari_to_idx = {h['nama']: i for i, h in enumerate(hari_aktif)}
    idx_to_hari = {i: h['nama'] for i, h in enumerate(hari_aktif)}
    
    # Total slot sepanjang minggu (linear)
    # Misal: Senin 10 jam, Selasa 10 jam. Senin jam 1 = slot 0, Selasa jam 1 = slot 10.
    day_offsets = {}
    current_offset = 0
    total_slots = 0
    for h in hari_aktif:
        day_offsets[h['nama']] = current_offset
        current_offset += h['max_jam']
    total_slots = current_offset

    # --- Variabel Keputusan ---
    # task_vars[(assignment_id)] = interval_variable
    intervals_per_guru = {}
    intervals_per_kelas = {}
    
    results_vars = {} # Untuk menyimpan start_var guna mengambil hasil akhir

    for asm in assignments:
        asm_id = asm['id']
        duration = int(asm['jumlah_jam'])
        gid = asm['guru_id']
        kid = asm['kelas_id']
        
        # Variabel Start (Slot mana jadwal ini dimulai)
        start_var = model.NewIntVar(0, total_slots - duration, f'start_{asm_id}')
        end_var = model.NewIntVar(0, total_slots, f'end_{asm_id}')
        
        # Interval variable (menjamin durasi berurutan)
        interval_var = model.NewIntervalVar(start_var, duration, end_var, f'interval_{asm_id}')
        
        results_vars[asm_id] = start_var

        # Tambahkan ke koleksi untuk constraint No-Overlap
        intervals_per_guru.setdefault(gid, []).append(interval_var)
        intervals_per_kelas.setdefault(kid, []).append(interval_var)

        # --- Constraint 1: Cek Locked (Manual) ---
        if asm.get('locked_hari') and asm.get('locked_jam') is not None:
            day_idx = day_offsets[asm['locked_hari']]
            # Jam di sistem PHP mulai dari 1, konversi ke index 0
            target_start = day_idx + (int(asm['locked_jam']) - 1)
            model.Add(start_var == target_start)

        # --- Constraint 2: Tidak boleh melewati batas hari ---
        # (Jadwal tidak boleh mulai di Senin selesai di Selasa)
        for h_nama, offset in day_offsets.items():
            max_jam = next(x['max_jam'] for x in hari_aktif if x['nama'] == h_nama)
            day_end = offset + max_jam
            
            # Jika jadwal dimulai di hari ini, maka harus selesai di hari yang sama
            # Logic: If start >= offset AND start < day_end THEN end <= day_end
            starts_in_day = model.NewBoolVar(f'starts_in_{h_nama}_{asm_id}')
            model.AddLinearConstraint(start_var, offset, day_end - 1).OnlyEnforceIf(starts_in_day)
            model.Add(end_var <= day_end).OnlyEnforceIf(starts_in_day)

        # --- Constraint 3: Ketersediaan Guru (hari_mengajar) ---
        guru_data = gurus.get(gid)
        if guru_data and guru_data['hari_mengajar']:
            allowed_days = guru_data['hari_mengajar'] # ['Senin', 'Rabu']
            # Buat list bool: apakah start_var berada di hari yang diizinkan?
            day_literals = []
            for h_nama in allowed_days:
                lit = model.NewBoolVar(f'guru_{gid}_on_{h_nama}')
                offset = day_offsets[h_nama]
                max_j = next(x['max_jam'] for x in hari_aktif if x['nama'] == h_nama)
                model.AddLinearConstraint(start_var, offset, offset + max_j - duration).OnlyEnforceIf(lit)
                day_literals.append(lit)
            model.AddArcConsistentExactlyOne(day_literals)

    # --- Constraint 4: No Overlap (Guru & Kelas) ---
    for gid, intervals in intervals_per_guru.items():
        model.AddNoOverlap(intervals)
    
    for kid, intervals in intervals_per_kelas.items():
        model.AddNoOverlap(intervals)

    # --- Solver ---
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 60.0 # Limit 1 menit
    status = solver.Solve(model)

    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        output_solution = []
        for asm in assignments:
            asm_id = asm['id']
            start_val = solver.Value(results_vars[asm_id])
            
            # Konversi kembali slot linear ke (Hari, Jam)
            final_hari = ""
            final_jam = 0
            
            # Cari hari berdasarkan offset
            sorted_offsets = sorted(day_offsets.items(), key=lambda x: x[1], reverse=True)
            for h_nama, offset in sorted_offsets:
                if start_val >= offset:
                    final_hari = h_nama
                    final_jam = (start_val - offset) + 1 # Jam ke-1, 2, dst
                    break
            
            output_solution.append({
                "id": asm_id,
                "hari": final_hari,
                "jam": final_jam
            })

        print(json.dumps({
            "status": "OPTIMAL",
            "message": "Jadwal berhasil dibuat tanpa bentrok!",
            "solution": output_solution
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE",
            "message": "Tidak ditemukan solusi. Periksa kembali ketersediaan guru atau limit jam."
        }))

if __name__ == '__main__':
    solve()