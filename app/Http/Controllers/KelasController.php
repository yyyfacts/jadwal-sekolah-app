<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Jadwal;
use App\Models\WaktuKosong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class KelasController extends Controller
{
    // ================================================================
    // 1. FITUR AUTO-FIX DATABASE (ANTI ERROR)
    // ================================================================
    private function checkAndFixDatabase()
    {
        if (Schema::hasTable('jadwals') && !Schema::hasColumn('jadwals', 'tipe_jam')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->string('tipe_jam')->default('single')->after('jumlah_jam');
            });
        }
    }

    // ================================================================
    // MANAJEMEN DATA KELAS (CRUD UTAMA)
    // ================================================================

    public function index()
    {
        $kelass = Kelas::with(['jadwals.mapel', 'jadwals.guru', 'waktuKosong'])
            ->orderBy('nama_kelas')
            ->get();

        foreach ($kelass as $k) {
            $k->total_jam = $k->jadwals->sum('jumlah_jam');
        }

        $mapels = Mapel::all();
        $gurus = Guru::all();

        return view('penjadwalan.kelas', compact('kelass', 'mapels', 'gurus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas'   => 'required|string',
            'kode_kelas'   => 'required|string|unique:kelas',
            'max_jam'      => 'required|integer|min:1',
            // Tambahan Validasi Limit Harian
            'limit_harian' => 'required|integer|min:1|max:15',
            'limit_jumat'  => 'required|integer|min:0|max:10',
        ]);

        Kelas::create($request->only('nama_kelas', 'kode_kelas', 'max_jam', 'limit_harian', 'limit_jumat'));
        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_kelas'   => 'required|string',
            'kode_kelas'   => 'required|string|unique:kelas,kode_kelas,' . $id,
            'max_jam'      => 'required|integer|min:1',
            // Tambahan Validasi Limit Harian
            'limit_harian' => 'required|integer|min:1|max:15',
            'limit_jumat'  => 'required|integer|min:0|max:10',
        ]);

        $kelas = Kelas::findOrFail($id);
        $kelas->update($request->only('nama_kelas', 'kode_kelas', 'max_jam', 'limit_harian', 'limit_jumat'));
        return redirect()->route('kelas.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $kelas = Kelas::findOrFail($id);
        $kelas->jadwals()->delete();
        $kelas->waktuKosong()->delete();
        $kelas->delete();
        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }

    // ================================================================
    // MANAJEMEN WAKTU KOSONG
    // ================================================================
    public function waktuKosongForm($id)
    {
        $kelas = Kelas::findOrFail($id);
        $hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $jam = range(1, 10);
        $selected = WaktuKosong::where('kelas_id', $id)->get()->map(fn($w) => "$w->hari|$w->jam")->toArray();
        return view('penjadwalan.waktu_kosong', ['entity' => $kelas, 'url' => route('kelas.waktuKosong.simpan', $id), 'selected' => $selected, 'hari' => $hari, 'jam' => $jam]);
    }

    public function simpanWaktuKosong(Request $request, $id)
    {
        $kelas = Kelas::findOrFail($id);
        $kelas->waktuKosong()->delete();
        foreach ($request->input('waktu_kosong', []) as $hari => $jam_arr) {
            foreach ($jam_arr as $jk => $val) {
                if ($val == 1) $kelas->waktuKosong()->create(['hari' => $hari, 'jam' => $jk]);
            }
        }
        return back()->with('success', 'Waktu kosong diperbarui.');
    }

    // ================================================================
    // MANAJEMEN BEBAN BELAJAR (JADWAL)
    // ================================================================

    public function simpanJadwal(Request $request, $id)
    {
        try {
            $this->checkAndFixDatabase();

            $request->validate([
                'mapel_id'   => 'required|exists:mapels,id',
                'guru_id'    => 'required|exists:gurus,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam'   => 'required|in:single,double,triple',
            ]);

            $kelas = Kelas::with('jadwals')->findOrFail($id);
            
            $currentTotal = $kelas->jadwals->sum('jumlah_jam');
            $maxJam = $kelas->max_jam ?? 50; 
            
            if (($currentTotal + $request->jumlah_jam) > $maxJam) {
                return response()->json([
                    'success' => false, 
                    'message' => "Gagal! Kapasitas penuh. Maks $maxJam jam."
                ], 422);
            }

            $jadwal = new Jadwal();
            $jadwal->kelas_id = $id;
            $jadwal->mapel_id = $request->mapel_id;
            $jadwal->guru_id = $request->guru_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam = $request->tipe_jam;
            $jadwal->hari = null; 
            $jadwal->jam = null; 
            $jadwal->save();

            $jadwal->load(['mapel', 'guru']);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Disimpan!',
                'jadwal' => $jadwal
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
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
            ]);

            $kelas = Kelas::with('jadwals')->findOrFail($jadwal->kelas_id);
            $currentTotalOthers = $kelas->jadwals->where('id', '!=', $id)->sum('jumlah_jam');
            $maxJam = $kelas->max_jam ?? 50;
            
            $newTotal = $currentTotalOthers + $request->jumlah_jam;

            if ($newTotal > $maxJam) {
                $sisa = $maxJam - $currentTotalOthers;
                return response()->json([
                    'success' => false,
                    'message' => "Gagal! Total jam ($newTotal) melebihi batas ($maxJam). Sisa slot: $sisa jam."
                ], 422);
            }

            $jadwal->mapel_id = $request->mapel_id;
            $jadwal->guru_id = $request->guru_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam = $request->tipe_jam;
            $jadwal->save();

            $jadwal->load(['mapel', 'guru']);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil Diupdate!',
                'jadwal' => $jadwal
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function hapusJadwal($id)
    {
        try {
            $jadwal = Jadwal::findOrFail($id);
            $jadwal->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Berhasil Dihapus!'
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}