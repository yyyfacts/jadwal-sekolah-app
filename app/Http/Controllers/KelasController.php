<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    private function checkAndFixDatabase()
    {
        if (Schema::hasTable('jadwals') && !Schema::hasColumn('jadwals', 'tipe_jam')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->string('tipe_jam')->default('single')->after('jumlah_jam');
            });
        }

        if (Schema::hasTable('kelas') && !Schema::hasColumn('kelas', 'limit_harian')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->integer('limit_harian')->default(10)->after('max_jam');
                $table->integer('limit_jumat')->default(7)->after('limit_harian');
            });
        }

        // [FITUR BARU] Buat kolom otomatis untuk Jam Kosong / Blokir Kelas
        if (Schema::hasTable('kelas') && !Schema::hasColumn('kelas', 'blocked_slots')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->string('blocked_slots', 255)->nullable()->after('limit_jumat');
            });
        }
    }

    /**
     * [BARU] Hitung kapasitas fisik mingguan kelas (dalam JP) berdasarkan
     * limit_harian, limit_jumat, dan jumlah slot yang diblokir.
     * Senin-Kamis pakai limit_harian, Jumat pakai limit_jumat.
     * Logikanya disamakan persis dengan build_kelas_limits() di scheduler.py
     * biar hasil hitungannya konsisten sama yang dipakai solver.
     */
    private function hitungKapasitasMingguan($limitHarian, $limitJumat, $blockedSlotsRaw)
    {
        $limitHarian = (int) $limitHarian;
        $limitJumat = (int) $limitJumat;

        $hariBiasa = ['Senin', 'Selasa', 'Rabu', 'Kamis']; // pakai limit_harian
        $totalKapasitas = (count($hariBiasa) * $limitHarian) + $limitJumat;

        if (!$blockedSlotsRaw) {
            return $totalKapasitas;
        }

        // Hitung berapa slot diblokir per hari, format: "Selasa:3, Rabu:3"
        $blokirPerHari = [];
        $parts = explode(',', str_replace(';', ',', $blockedSlotsRaw));
        foreach ($parts as $p) {
            if (strpos($p, ':') === false)
                continue;
            [$hari, $jam] = explode(':', $p, 2);
            $hari = ucfirst(strtolower(trim($hari)));
            $jam = trim($jam);
            if ($jam === '' || !is_numeric($jam))
                continue;
            $blokirPerHari[$hari] = ($blokirPerHari[$hari] ?? 0) + 1;
        }

        foreach ($blokirPerHari as $hari => $jumlahBlokir) {
            $batasHari = ($hari === 'Jumat') ? $limitJumat : $limitHarian;
            // Gak boleh minus di bawah 0 untuk hari itu
            $totalKapasitas -= min($jumlahBlokir, $batasHari);
        }

        return $totalKapasitas;
    }

    public function index()
    {
        $this->checkAndFixDatabase();

        $kelass = Kelas::with(['jadwals.mapel', 'jadwals.guru', 'waliKelas'])
            ->orderBy('nama_kelas')
            ->get();

        foreach ($kelass as $k) {
            $k->jam_offline = $k->jadwals->where('status', 'offline')->sum('jumlah_jam');
            $k->jam_online = $k->jadwals->where('status', 'online')->sum('jumlah_jam');
            $k->total_jam = $k->jadwals->sum('jumlah_jam');
        }

        $mapels = Mapel::all();
        $gurus = Guru::orderBy('nama_guru')->get();

        return view('penjadwalan.kelas', compact('kelass', 'mapels', 'gurus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas' => 'required|string',
            'kode_kelas' => 'required|string|unique:kelas',
            'max_jam' => 'required|integer|min:1',
            'wali_guru_id' => 'nullable|exists:gurus,id',
            'limit_harian' => 'required|integer|min:1',
            'limit_jumat' => 'required|integer|min:1',
            'blocked_slots' => 'nullable|string|max:255', // <-- Pastikan ini ada
        ]);

        // [BARU] Kelas baru belum punya distribusi jadwal (0 JP), tapi tetap
        // dicek jaga-jaga kalau blocked_slots yang diisi bikin kapasitas jadi negatif/aneh.
        $kapasitasBaru = $this->hitungKapasitasMingguan(
            $request->limit_harian,
            $request->limit_jumat,
            $request->blocked_slots
        );

        if ($kapasitasBaru < 0) {
            return redirect()->back()->withInput()->with(
                'error',
                "Gagal! Kombinasi Limit Harian/Jumat dan Jam Kosong/Blokir yang lu isi gak masuk akal (kapasitas mingguan jadi negatif)."
            );
        }

        // <-- Pastikan 'blocked_slots' ada di dalam array only()
        Kelas::create($request->only('nama_kelas', 'kode_kelas', 'max_jam', 'wali_guru_id', 'limit_harian', 'limit_jumat', 'blocked_slots'));
        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_kelas' => 'required|string',
            'kode_kelas' => 'required|string|unique:kelas,kode_kelas,' . $id,
            'max_jam' => 'required|integer|min:1',
            'wali_guru_id' => 'nullable|exists:gurus,id',
            'limit_harian' => 'required|integer|min:1',
            'limit_jumat' => 'required|integer|min:1',
            'blocked_slots' => 'nullable|string|max:255', // <-- Pastikan ini ada
        ]);

        $kelas = Kelas::findOrFail($id);

        // [BARU] Validasi kapasitas mingguan vs total jam yang UDAH didistribusikan.
        // Ini yang bikin kegagalan "Solusi mustahil ditemukan" kejadian kalau gak dicek dari awal.
        $kapasitasBaru = $this->hitungKapasitasMingguan(
            $request->limit_harian,
            $request->limit_jumat,
            $request->blocked_slots
        );

        $totalOfflineSaatIni = $kelas->jadwals()->where('status', 'offline')->sum('jumlah_jam');

        if ($totalOfflineSaatIni > $kapasitasBaru) {
            $selisih = $totalOfflineSaatIni - $kapasitasBaru;
            return redirect()->back()->withInput()->with(
                'error',
                "Gagal! Total jam yang sudah didistribusikan ke {$kelas->nama_kelas} adalah {$totalOfflineSaatIni} JP, " .
                "tapi kapasitas mingguan setelah pengaturan Limit Harian/Jumat & Blokir ini cuma {$kapasitasBaru} JP. " .
                "Kurangi distribusi mapel kelas ini sebanyak {$selisih} JP, atau longgarkan blokir/limit hariannya. " .
                "Kalau ini gak dibenerin dulu, tombol Jalankan Solver bakal selalu gagal (Infeasible)."
            );
        }

        // <-- Pastikan 'blocked_slots' ada di dalam array only()
        $kelas->update($request->only('nama_kelas', 'kode_kelas', 'max_jam', 'wali_guru_id', 'limit_harian', 'limit_jumat', 'blocked_slots'));

        return redirect()->route('kelas.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $kelas = Kelas::findOrFail($id);
        $kelas->jadwals()->delete();
        $kelas->delete();
        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }

    public function simpanJadwal(Request $request, $id)
    {
        try {
            $this->checkAndFixDatabase();
            $request->validate([
                'mapel_id' => 'required|exists:mapels,id',
                'guru_id' => 'required|exists:gurus,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam' => 'required|in:single,double,triple',
                'status' => 'required|in:offline,online',
            ]);

            $kelas = Kelas::with('jadwals')->findOrFail($id);

            $currentTotalOffline = $kelas->jadwals->where('status', 'offline')->sum('jumlah_jam');
            $maxJam = $kelas->max_jam;

            $tambahanBeban = ($request->status == 'offline') ? $request->jumlah_jam : 0;

            if (($currentTotalOffline + $tambahanBeban) > $maxJam) {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal! Slot Fisik (Offline) penuh. Terisi: $currentTotalOffline JP, Maks: $maxJam JP."
                ], 422);
            }

            $jadwal = new Jadwal();
            $jadwal->kelas_id = $id;
            $jadwal->mapel_id = $request->mapel_id;
            $jadwal->guru_id = $request->guru_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam = $request->tipe_jam;
            $jadwal->status = $request->status;
            $jadwal->master_hari_id = null;
            $jadwal->jam = null;
            $jadwal->save();

            $jadwal->load(['mapel', 'guru']);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Disimpan!',
                'jadwal' => $jadwal
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateJadwal(Request $request, $id)
    {
        try {
            $this->checkAndFixDatabase();
            $jadwal = Jadwal::findOrFail($id);

            $request->validate([
                'mapel_id' => 'required|exists:mapels,id',
                'guru_id' => 'required|exists:gurus,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam' => 'required|in:single,double,triple',
                'status' => 'required|in:offline,online',
            ]);

            $kelas = Kelas::with('jadwals')->findOrFail($jadwal->kelas_id);

            $currentTotalOthersOffline = $kelas->jadwals->where('id', '!=', $id)->where('status', 'offline')->sum('jumlah_jam');
            $maxJam = $kelas->max_jam;

            $tambahanBeban = ($request->status == 'offline') ? $request->jumlah_jam : 0;
            $newTotal = $currentTotalOthersOffline + $tambahanBeban;

            if ($newTotal > $maxJam) {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal! Total Fisik ($newTotal JP) melebihi batas ($maxJam JP)."
                ], 422);
            }

            $jadwal->mapel_id = $request->mapel_id;
            $jadwal->guru_id = $request->guru_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam = $request->tipe_jam;
            $jadwal->status = $request->status;
            $jadwal->save();

            $jadwal->load(['mapel', 'guru']);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Diupdate!',
                'jadwal' => $jadwal
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function hapusJadwal($id)
    {
        try {
            $jadwal = Jadwal::findOrFail($id);
            $jadwal->delete();
            return response()->json(['success' => true, 'message' => 'Berhasil Dihapus!']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function sinkronisasiMaxJam()
    {
        try {
            DB::statement("
                UPDATE kelas k
                SET 
                    max_jam = (
                        SELECT COALESCE(SUM(jumlah_jam), 0)
                        FROM jadwals j
                        WHERE j.kelas_id = k.id
                          AND j.status = 'offline'
                    ),
                    limit_harian = 10,
                    limit_jumat = GREATEST((
                        SELECT COALESCE(SUM(jumlah_jam), 0)
                        FROM jadwals j
                        WHERE j.kelas_id = k.id
                          AND j.status = 'offline'
                    ) - 40, 0)
            ");

            return redirect()->back()->with('success', 'Max jam, limit harian, dan limit jumat berhasil disesuaikan dengan total offline!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }
}