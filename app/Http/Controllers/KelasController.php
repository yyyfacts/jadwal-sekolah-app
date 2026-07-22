<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Jadwal;
use App\Models\MasterHari;
use App\Models\KelasWaktuKhusus;
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

        // [LEGACY - GAK DIPAKE LAGI] Kolom ini dulu buat Jam Kosong/Blokir versi text bebas,
        // sekarang udah digantiin tabel kelas_waktu_khusus. Dibiarin di sini (gak dihapus)
        // biar gak ganggu kalau kolomnya udah kadung ada di DB, tapi gak dibaca/ditulis lagi
        // di controller manapun.
        if (Schema::hasTable('kelas') && !Schema::hasColumn('kelas', 'blocked_slots')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->string('blocked_slots', 255)->nullable()->after('limit_jumat');
            });
        }
    }

    /**
     * [DIUBAH] Hitung kapasitas fisik mingguan kelas (dalam JP) berdasarkan
     * limit_harian, limit_jumat, dan jumlah jam yang dikecualikan (bukan 'Belajar')
     * di tabel kelas_waktu_khusus. Senin-Kamis pakai limit_harian, Jumat pakai limit_jumat.
     *
     * $kelasId null artinya kelas baru (belum punya pengecualian sama sekali).
     */
    private function hitungKapasitasMingguan($limitHarian, $limitJumat, $kelasId = null)
    {
        $limitHarian = (int) $limitHarian;
        $limitJumat = (int) $limitJumat;

        $hariBiasa = ['Senin', 'Selasa', 'Rabu', 'Kamis']; // pakai limit_harian
        $totalKapasitas = (count($hariBiasa) * $limitHarian) + $limitJumat;

        if (!$kelasId) {
            return $totalKapasitas;
        }

        // Hitung berapa jam dikecualikan per hari dari tabel kelas_waktu_khusus
        $blokirPerHari = KelasWaktuKhusus::where('kelas_id', $kelasId)
            ->join('master_haris', 'kelas_waktu_khusus.master_hari_id', '=', 'master_haris.id')
            ->selectRaw('master_haris.nama_hari as nama_hari, COUNT(*) as jumlah')
            ->groupBy('master_haris.nama_hari')
            ->pluck('jumlah', 'nama_hari');

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

        $kelass = Kelas::with(['jadwals.mapel', 'jadwals.guru', 'waliKelas', 'waktuKhusus'])
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
        ]);

        // Kelas baru belum punya pengecualian jam (kelas_waktu_khusus) sama sekali,
        // jadi kapasitasnya cuma dihitung dari limit_harian & limit_jumat murni.
        $kapasitasBaru = $this->hitungKapasitasMingguan(
            $request->limit_harian,
            $request->limit_jumat,
            null
        );

        if ($kapasitasBaru < 0) {
            return redirect()->back()->withInput()->with(
                'error',
                "Gagal! Kombinasi Limit Harian/Jumat yang lu isi gak masuk akal (kapasitas mingguan jadi negatif)."
            );
        }

        Kelas::create($request->only('nama_kelas', 'kode_kelas', 'max_jam', 'wali_guru_id', 'limit_harian', 'limit_jumat'));
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
        ]);

        $kelas = Kelas::findOrFail($id);

        // Validasi kapasitas mingguan (limit harian/jumat dikurangi jam yang dikecualikan
        // di kelas_waktu_khusus) vs total jam yang UDAH didistribusikan.
        // Ini yang bikin kegagalan "Solusi mustahil ditemukan" kejadian kalau gak dicek dari awal.
        $kapasitasBaru = $this->hitungKapasitasMingguan(
            $request->limit_harian,
            $request->limit_jumat,
            $id
        );

        $totalOfflineSaatIni = $kelas->jadwals()->where('status', 'offline')->sum('jumlah_jam');

        if ($totalOfflineSaatIni > $kapasitasBaru) {
            $selisih = $totalOfflineSaatIni - $kapasitasBaru;
            return redirect()->back()->withInput()->with(
                'error',
                "Gagal! Total jam yang sudah didistribusikan ke {$kelas->nama_kelas} adalah {$totalOfflineSaatIni} JP, " .
                "tapi kapasitas mingguan setelah pengaturan Limit Harian/Jumat & Jam Kosong/Blokir ini cuma {$kapasitasBaru} JP. " .
                "Kurangi distribusi mapel kelas ini sebanyak {$selisih} JP, atau longgarkan blokir/limit hariannya. " .
                "Kalau ini gak dibenerin dulu, tombol Jalankan Solver bakal selalu gagal (Infeasible)."
            );
        }

        $kelas->update($request->only('nama_kelas', 'kode_kelas', 'max_jam', 'wali_guru_id', 'limit_harian', 'limit_jumat'));

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

    /**
     * [BARU] Ambil daftar slot 'Belajar' (global) buat semua hari aktif, dibarengin
     * sama tipe khusus punya kelas ini (kalau ada override di kelas_waktu_khusus).
     * Dipake buat isi modal "Jam Kosong / Blokir Kelas".
     */
    public function getWaktuKhusus($id)
    {
        $kelas = Kelas::findOrFail($id);

        $dataHari = MasterHari::with(['waktuHaris' => function ($q) {
            $q->where('tipe', 'Belajar')->orderBy('waktu_mulai');
        }])->where('is_active', true)->orderBy('id')->get();

        $existing = KelasWaktuKhusus::where('kelas_id', $id)
            ->get()
            ->keyBy(fn($row) => $row->master_hari_id . '_' . $row->jam_ke);

        $hariResult = [];
        foreach ($dataHari as $hari) {
            $slots = [];
            foreach ($hari->waktuHaris as $w) {
                $override = $existing->get($hari->id . '_' . $w->jam_ke);
                $slots[] = [
                    'jam_ke'        => $w->jam_ke,
                    'waktu_mulai'   => $w->waktu_mulai,
                    'waktu_selesai' => $w->waktu_selesai,
                    'tipe_khusus'   => $override->tipe ?? 'Belajar',
                    'keterangan'    => $override->keterangan ?? null,
                ];
            }
            $hariResult[] = [
                'master_hari_id' => $hari->id,
                'nama_hari'      => $hari->nama_hari,
                'slots'          => $slots,
            ];
        }

        return response()->json([
            'kelas' => ['id' => $kelas->id, 'nama_kelas' => $kelas->nama_kelas],
            'hari'  => $hariResult,
        ]);
    }

    /**
     * [BARU] Simpan pengecualian jam kelas. Slot bertipe 'Belajar' gak disimpan
     * (artinya normal, jadwal boleh diisi solver). Slot selain 'Belajar' disimpan
     * sebagai row baru dan otomatis di-skip pas generate jadwal.
     */
    public function simpanWaktuKhusus(Request $request, $id)
    {
        Kelas::findOrFail($id);

        $request->validate([
            'items'                  => 'array',
            'items.*.master_hari_id' => 'required|exists:master_haris,id',
            'items.*.jam_ke'         => 'required|integer',
            'items.*.tipe'           => 'required|string|in:Belajar,Kosong,Ujian,Ekstrakurikuler,Kegiatan Khusus',
            'items.*.keterangan'     => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Hapus semua pengecualian lama punya kelas ini, insert ulang yang bukan 'Belajar'.
            // Lebih simpel & aman daripada nyari row satu-satu buat di-upsert/dihapus.
            KelasWaktuKhusus::where('kelas_id', $id)->delete();

            foreach ($request->input('items', []) as $item) {
                if ($item['tipe'] === 'Belajar') {
                    continue;
                }
                KelasWaktuKhusus::create([
                    'kelas_id'       => $id,
                    'master_hari_id' => $item['master_hari_id'],
                    'jam_ke'         => $item['jam_ke'],
                    'tipe'           => $item['tipe'],
                    'keterangan'     => $item['keterangan'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Jam kosong/blokir kelas berhasil disimpan!']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
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