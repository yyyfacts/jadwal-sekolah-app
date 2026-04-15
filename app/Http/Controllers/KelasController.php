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
        
        // Pengecekan otomatis untuk kolom limit_harian dan limit_jumat di tabel kelas
        if (Schema::hasTable('kelas') && !Schema::hasColumn('kelas', 'limit_harian')) {
            Schema::table('kelas', function (Blueprint $table) {
                $table->integer('limit_harian')->default(10)->after('max_jam');
                $table->integer('limit_jumat')->default(7)->after('limit_harian');
            });
        }
    }

    public function index()
    {
        $this->checkAndFixDatabase(); // Pastikan DB aman saat memuat halaman

        $kelass = Kelas::with(['jadwals.mapel', 'jadwals.guru', 'waliKelas'])
            ->orderBy('nama_kelas')
            ->get();

        foreach ($kelass as $k) {
         $k->jam_offline = $k->jadwals->where('status', 'offline')->sum('jumlah_jam');
    $k->jam_online  = $k->jadwals->where('status', 'online')->sum('jumlah_jam');
    $k->total_jam   = $k->jadwals->sum('jumlah_jam');
        }

        $mapels = Mapel::all();
        $gurus = Guru::orderBy('nama_guru')->get();

        return view('penjadwalan.kelas', compact('kelass', 'mapels', 'gurus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas'   => 'required|string',
            'kode_kelas'   => 'required|string|unique:kelas',
            'max_jam'      => 'required|integer|min:1',
            'wali_guru_id' => 'nullable|exists:gurus,id',
            'limit_harian' => 'required|integer|min:1',
            'limit_jumat'  => 'required|integer|min:1',
        ]);

        Kelas::create($request->only('nama_kelas', 'kode_kelas', 'max_jam', 'wali_guru_id', 'limit_harian', 'limit_jumat'));
        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_kelas'   => 'required|string',
            'kode_kelas'   => 'required|string|unique:kelas,kode_kelas,' . $id,
            'max_jam'      => 'required|integer|min:1',
            'wali_guru_id' => 'nullable|exists:gurus,id',
            'limit_harian' => 'required|integer|min:1',
            'limit_jumat'  => 'required|integer|min:1',
        ]);

        $kelas = Kelas::findOrFail($id);
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

    // --- BAGIAN MANAJEMEN PLOTTING JADWAL (AJAX) ---

   public function simpanJadwal(Request $request, $id)
    {
        try {
            $this->checkAndFixDatabase();
            $request->validate([
                'mapel_id'   => 'required|exists:mapels,id',
                'guru_id'    => 'required|exists:gurus,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam'   => 'required|in:single,double,triple',
                'status'     => 'required|in:offline,online', 
            ]);

            $kelas = Kelas::with('jadwals')->findOrFail($id);
            
            // HANYA HITUNG YANG OFFLINE
            $currentTotalOffline = $kelas->jadwals->where('status', 'offline')->sum('jumlah_jam');
            $maxJam = $kelas->max_jam; 
            
            // Jika jadwal ini OFFLINE, tambahkan ke perhitungan beban fisik
            $tambahanBeban = ($request->status == 'offline') ? $request->jumlah_jam : 0;

            if (($currentTotalOffline + $tambahanBeban) > $maxJam) {
                return response()->json([
                    'success' => false, 
                    'message' => "Gagal! Slot Fisik (Offline) penuh. Terisi: $currentTotalOffline JP, Maks: $maxJam JP."
                ], 422);
            }

            $jadwal = new Jadwal();
            $jadwal->kelas_id   = $id;
            $jadwal->mapel_id   = $request->mapel_id;
            $jadwal->guru_id    = $request->guru_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam   = $request->tipe_jam;
            $jadwal->status     = $request->status; 
            $jadwal->master_hari_id = null;
            $jadwal->jam        = null; 
            $jadwal->save();

            $jadwal->load(['mapel', 'guru']);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Disimpan!',
                'jadwal'  => $jadwal
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false,'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateJadwal(Request $request, $id)
    {
        try {
            $this->checkAndFixDatabase();
            $jadwal = Jadwal::findOrFail($id);

            $request->validate([
                'mapel_id'   => 'required|exists:mapels,id',
                'guru_id'    => 'required|exists:gurus,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam'   => 'required|in:single,double,triple',
                'status'     => 'required|in:offline,online', 
            ]);

            $kelas = Kelas::with('jadwals')->findOrFail($jadwal->kelas_id);
            
            // HANYA HITUNG YANG OFFLINE SELAIN JADWAL INI
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

            $jadwal->mapel_id   = $request->mapel_id;
            $jadwal->guru_id    = $request->guru_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam   = $request->tipe_jam;
            $jadwal->status     = $request->status; 
            $jadwal->save();

            $jadwal->load(['mapel', 'guru']);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Diupdate!',
                'jadwal'  => $jadwal
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false,'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function hapusJadwal($id)
    {
        try {
            $jadwal = Jadwal::findOrFail($id);
            $jadwal->delete();
            return response()->json(['success' => true,'message' => 'Berhasil Dihapus!']);
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