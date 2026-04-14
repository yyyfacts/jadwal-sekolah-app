<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class KelasController extends Controller
{
    private function checkAndFixDatabase()
    {
        if (Schema::hasTable('jadwals') && !Schema::hasColumn('jadwals', 'tipe_jam')) {
            Schema::table('jadwals', function (Blueprint $table) {
                $table->string('tipe_jam')->default('single')->after('jumlah_jam');
            });
        }
    }

    public function index()
    {
        $kelass = Kelas::with(['jadwals.mapel', 'jadwals.guru', 'waliKelas'])
            ->orderBy('nama_kelas')
            ->get();

        foreach ($kelass as $k) {
            $k->total_jam = $k->jadwals->sum('jumlah_jam');
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
            'wali_guru_id' => 'nullable|exists:gurus,id'
        ]);

        Kelas::create($request->only('nama_kelas', 'kode_kelas', 'max_jam', 'wali_guru_id'));
        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_kelas'   => 'required|string',
            'kode_kelas'   => 'required|string|unique:kelas,kode_kelas,' . $id,
            'max_jam'      => 'required|integer|min:1',
            'wali_guru_id' => 'nullable|exists:gurus,id'
        ]);

        $kelas = Kelas::findOrFail($id);
        $kelas->update($request->only('nama_kelas', 'kode_kelas', 'max_jam', 'wali_guru_id'));
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
            $currentTotal = $kelas->jadwals->sum('jumlah_jam');
            
            $maxJam = $kelas->max_jam; 
            
            if (($currentTotal + $request->jumlah_jam) > $maxJam) {
                return response()->json([
                    'success' => false, 
                    'message' => "Gagal! Kapasitas penuh. Maks $maxJam jam."
                ], 422);
            }

            $jadwal = new Jadwal();
            $jadwal->kelas_id   = $id;
            $jadwal->mapel_id   = $request->mapel_id;
            $jadwal->guru_id    = $request->guru_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam   = $request->tipe_jam;
            $jadwal->status     = $request->status; 
            
            $jadwal->hari       = null; 
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
            $currentTotalOthers = $kelas->jadwals->where('id', '!=', $id)->sum('jumlah_jam');
            $maxJam = $kelas->max_jam;
            $newTotal = $currentTotalOthers + $request->jumlah_jam;

            if ($newTotal > $maxJam) {
                $sisa = $maxJam - $currentTotalOthers;
                return response()->json([
                    'success' => false,
                    'message' => "Gagal! Total jam ($newTotal) melebihi batas ($maxJam). Sisa slot: $sisa jam."
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
}