import sys
import json
import time
from ortools.sat.python import cp_model

def main():
    start_time = time.time()

    if len(sys.argv) < 2:
        print(json.dumps({"status": "ERROR", "message": "JSON path required"}))
        return

    json_path = sys.argv[1]

    try:
        with open(json_path, 'r') as f:
            data = json.load(f)
    except Exception as e:
        print(json.dumps({"status": "ERROR", "message": str(e)}))
        return

    assignments = data.get('assignments', [])
    kelass = data.get('kelass', [])
    gurus = data.get('gurus', [])
    hari_aktif = data.get('hari_aktif', [])

    # ==============================
    # 🔧 PREPROCESSING
    # ==============================

    # LJF (Longest Job First)
    assignments.sort(key=lambda x: int(x['jumlah_jam']), reverse=True)

    hari_list = [h['nama'] for h in hari_aktif]

    kapasitas_hari = {h['nama']: int(h['max_jam']) for h in hari_aktif}

    kelas_limit = {
        k['id']: {
            'normal': int(k.get('limit_harian', 10)),
            'jumat': int(k.get('limit_jumat', 7))
        } for k in kelass
    }

    def get_max_jam(kelas_id, hari):
        master = kapasitas_hari.get(hari, 10)
        limit_k = kelas_limit.get(kelas_id, {'normal': 10, 'jumat': 7})
        kelas_max = limit_k['jumat'] if hari == 'Jumat' else limit_k['normal']
        return min(master, kelas_max)

    # ==============================
    # 🚀 MODEL
    # ==============================
    model = cp_model.CpModel()

    starts = {}
    presences = {}

    intervals_kelas = {k['id']: {h: [] for h in hari_list} for k in kelass}
    intervals_guru = {g['id']: {h: [] for h in hari_list} for g in gurus}
    teacher_daily_hours = {g['id']: {h: [] for h in hari_list} for g in gurus}

    # ==============================
    # 🎯 VARIABLE CREATION
    # ==============================
    for t in assignments:
        durasi = int(t['jumlah_jam'])
        t_id = t['id']
        g_id = t['guru_id']
        k_id = t['kelas_id']

        possible_days = []

        for h in hari_list:

            # cek guru available
            guru_obj = next((g for g in gurus if g['id'] == g_id), None)
            allowed_days = guru_obj.get('hari_mengajar', []) if guru_obj else []

            if allowed_days and h not in allowed_days:
                continue

            batas = get_max_jam(k_id, h)

            if durasi > batas:
                continue

            max_start = batas - durasi + 1
            if max_start < 1:
                continue

            start = model.NewIntVar(1, max_start, f's_{t_id}_{h}')
            end = model.NewIntVar(1, batas, f'e_{t_id}_{h}')
            presence = model.NewBoolVar(f'p_{t_id}_{h}')

            interval = model.NewOptionalIntervalVar(start, durasi, end, presence, f'i_{t_id}_{h}')

            starts[(t_id, h)] = start
            presences[(t_id, h)] = presence
            possible_days.append(presence)

            intervals_kelas[k_id][h].append(interval)
            intervals_guru[g_id][h].append(interval)

            teacher_daily_hours[g_id][h].append(presence * durasi)

        if possible_days:
            model.AddExactlyOne(possible_days)
        else:
            print(json.dumps({
                "status": "INFEASIBLE",
                "message": f"Tidak bisa menjadwalkan ID {t_id}"
            }))
            return

    # ==============================
    # 🚫 CONSTRAINTS
    # ==============================

    # No overlap kelas
    for k in intervals_kelas:
        for h in hari_list:
            if intervals_kelas[k][h]:
                model.AddNoOverlap(intervals_kelas[k][h])

    # No overlap guru
    for g in intervals_guru:
        for h in hari_list:
            if intervals_guru[g][h]:
                model.AddNoOverlap(intervals_guru[g][h])

    # Max 8 jam guru per hari
    for g in teacher_daily_hours:
        for h in hari_list:
            if teacher_daily_hours[g][h]:
                model.Add(sum(teacher_daily_hours[g][h]) <= 8)

    # Mapel tidak numpuk
    group_mapel = {}
    for t in assignments:
        key = (t['kelas_id'], t['mapel_id'])
        group_mapel.setdefault(key, []).append(t['id'])

    for (k_id, m_id), t_ids in group_mapel.items():
        if len(t_ids) > 1:
            for h in hari_list:
                daily = [presences[(tid, h)] for tid in t_ids if (tid, h) in presences]
                if daily:
                    model.Add(sum(daily) <= 1)

    # ==============================
    # 🎯 OBJECTIVE
    # ==============================
    last_slots = []

    for k_id in intervals_kelas:
        for h in hari_list:
            if intervals_kelas[k_id][h]:
                finish = model.NewIntVar(0, 20, f'finish_{k_id}_{h}')

                for t in assignments:
                    if t['kelas_id'] == k_id and (t['id'], h) in starts:
                        dur = int(t['jumlah_jam'])
                        model.Add(
                            finish >= starts[(t['id'], h)] + dur
                        ).OnlyEnforceIf(presences[(t['id'], h)])

                last_slots.append(finish)

    model.Minimize(sum(last_slots))

    # ==============================
    # ⚡ SOLVE
    # ==============================
    solver = cp_model.CpSolver()
    solver.parameters.max_time_in_seconds = 120

    status = solver.Solve(model)

    waktu = round(time.time() - start_time, 2)

    if status in (cp_model.OPTIMAL, cp_model.FEASIBLE):
        solusi = []

        for t in assignments:
            tid = t['id']
            for h in hari_list:
                if (tid, h) in presences and solver.Value(presences[(tid, h)]):
                    solusi.append({
                        "id": tid,
                        "hari": h,
                        "jam": solver.Value(starts[(tid, h)])
                    })

        print(json.dumps({
            "status": "OPTIMAL",
            "solution": solusi,
            "waktu": waktu,
            "message": "Jadwal berhasil dibuat"
        }))
    else:
        print(json.dumps({
            "status": "INFEASIBLE",
            "message": "Tidak ditemukan solusi"
        }))


if __name__ == "__main__":
    main()