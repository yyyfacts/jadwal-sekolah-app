<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class MapelController extends Controller
{
    /**
     * Fungsi otomatis benerin struktur DB yang kurang.
     * Kolom batas_maksimal_jam DIHAPUS dari pengecekan.
     */
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
    }

    public function index()
    {
        $this->checkAndFixDatabase();

        $mapels = Mapel::with(['jadwals.kelas', 'jadwals.guru'])
            ->orderBy('nama_mapel')
            ->get();

        foreach ($mapels as $m) {
            $m->total_jam_terdistribusi = $m->jadwals->sum('jumlah_jam');
            $m->total_kelas_mengampu = $m->jadwals->unique('kelas_id')->count();
        }

        $kelases = Kelas::orderBy('nama_kelas')->get();
        $gurus = Guru::orderBy('nama_guru')->get();

        return view('penjadwalan.mapel', compact('mapels', 'kelases', 'gurus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_mapel' => 'required|string',
            'kode_mapel' => 'required|string|unique:mapels',
            'status'     => 'nullable|string',
        ]);
        
        // Simpan hanya field yang ada di database
        Mapel::create($request->only(['nama_mapel', 'kode_mapel', 'status']));
        
        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_mapel' => 'required|string',
            'kode_mapel' => 'required|string|unique:mapels,kode_mapel,' . $id,
            'status'     => 'nullable|string',
        ]);
        
        $mapel = Mapel::findOrFail($id);
        $mapel->update($request->only(['nama_mapel', 'kode_mapel', 'status']));
        
        return redirect()->route('mapel.index')->with('success', 'Data Mapel diperbarui.');
    }

    public function destroy($id)
    {
        $mapel = Mapel::findOrFail($id);
        // Hapus distribusi terkait sebelum hapus mapelnya
        $mapel->jadwals()->delete();
        $mapel->delete();
        
        return redirect()->route('mapel.index')->with('success', 'Mapel dan distribusi terkait dihapus.');
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $mapel = Mapel::findOrFail($id);
            $request->validate(['status' => 'required|string']);
            $mapel->status = $request->status;
            $mapel->save();

            return response()->json(['success' => true, 'message' => 'Status berhasil diubah', 'status' => $mapel->status]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function simpanJadwal(Request $request, $id)
    {
        try {
            $request->validate([
                'kelas_id'   => 'required|exists:kelas,id',
                'guru_id'    => 'required|exists:gurus,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam'   => 'required|in:single,double,triple',
                'status'     => 'required|in:offline,online', 
            ]);

            $kelas = Kelas::with('jadwals')->findOrFail($request->kelas_id);
            
            // Validasi sisa slot fisik agar tidak over-capacity di satu kelas
            $currentTotalOffline = $kelas->jadwals->where('status', 'offline')->sum('jumlah_jam');
            $maxJamKelas = $kelas->max_jam ?? 50; // Default 50 jika null
            
            $tambahanBeban = ($request->status == 'offline') ? $request->jumlah_jam : 0;

            if (($currentTotalOffline + $tambahanBeban) > $maxJamKelas) {
                return response()->json([
                    'success' => false, 
                    'message' => "Gagal! Slot Fisik Kelas {$kelas->nama_kelas} penuh. Terisi: $currentTotalOffline JP, Maks: $maxJamKelas JP."
                ], 422);
            }

            $jadwal = new Jadwal();
            $jadwal->kelas_id       = $request->kelas_id;
            $jadwal->mapel_id       = $id; 
            $jadwal->guru_id        = $request->guru_id;
            $jadwal->jumlah_jam     = $request->jumlah_jam;
            $jadwal->tipe_jam       = $request->tipe_jam;
            $jadwal->status         = $request->status; 
            $jadwal->master_hari_id = $request->master_hari_id ?? null;
            $jadwal->jam            = $request->jam ?? null; 
            $jadwal->save();

            $jadwal->load(['guru', 'kelas']);

            return response()->json([
                'success' => true,
                'message' => 'Distribusi SKS Berhasil!',
                'jadwal'  => $jadwal
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false,'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateJadwal(Request $request, $id)
    {
        try {
            $jadwal = Jadwal::findOrFail($id);

            $request->validate([
                'kelas_id'   => 'required|exists:kelas,id',
                'guru_id'    => 'required|exists:gurus,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam'   => 'required|in:single,double,triple',
                'status'     => 'required|in:offline,online', 
            ]);

            $kelas = Kelas::with('jadwals')->findOrFail($request->kelas_id);
            
            $currentTotalOthersOffline = $kelas->jadwals->where('id', '!=', $id)->where('status', 'offline')->sum('jumlah_jam');
            $maxJamKelas = $kelas->max_jam ?? 50;
            
            $tambahanBeban = ($request->status == 'offline') ? $request->jumlah_jam : 0;
            $newTotal = $currentTotalOthersOffline + $tambahanBeban;

            if ($newTotal > $maxJamKelas) {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal! Total Fisik Kelas {$kelas->nama_kelas} ($newTotal JP) melebihi batas."
                ], 422);
            }

            $jadwal->kelas_id   = $request->kelas_id;
            $jadwal->guru_id    = $request->guru_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam   = $request->tipe_jam;
            $jadwal->status     = $request->status; 
            
            // Pertahankan nilai lock manual jika ada
            if($request->has('master_hari_id')) $jadwal->master_hari_id = $request->master_hari_id;
            if($request->has('jam')) $jadwal->jam = $request->jam;

            $jadwal->save();
            $jadwal->load(['guru', 'kelas']);

            return response()->json(['success' => true, 'message' => 'Update Berhasil!', 'jadwal' => $jadwal]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false,'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function hapusJadwal($id)
    {
        try {
            Jadwal::destroy($id);
            return response()->json(['success' => true, 'message' => 'Distribusi dihapus.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}