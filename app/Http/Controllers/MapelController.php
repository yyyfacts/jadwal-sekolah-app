<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class MapelController extends Controller
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

        // TAMBAHAN: Otomatis bikin kolom batas_maksimal_jam kalau belum ada
        if (Schema::hasTable('mapels') && !Schema::hasColumn('mapels', 'batas_maksimal_jam')) {
            Schema::table('mapels', function (Blueprint $table) {
                $table->integer('batas_maksimal_jam')->nullable()->after('status');
            });
        }
    }

    public function index()
    {
        $this->checkAndFixDatabase(); // Cek DB saat memuat halaman

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
        // TAMBAHAN: Validasi batas_maksimal_jam
        $request->validate([
            'nama_mapel' => 'required|string',
            'kode_mapel' => 'required|string|unique:mapels',
            'kelompok' => 'nullable|string',
            'batas_maksimal_jam' => 'nullable|integer|min:1|max:15',
        ]);
        
        Mapel::create($request->all());
        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        // TAMBAHAN: Validasi batas_maksimal_jam
        $request->validate([
            'nama_mapel' => 'required|string',
            'kode_mapel' => 'required|string|unique:mapels,kode_mapel,' . $id,
            'batas_maksimal_jam' => 'nullable|integer|min:1|max:15',
        ]);
        
        Mapel::findOrFail($id)->update($request->all());
        return redirect()->route('mapel.index')->with('success', 'Data Mapel diperbarui.');
    }

    public function destroy($id)
    {
        $mapel = Mapel::findOrFail($id);
        $mapel->jadwals()->delete();
        $mapel->delete();
        return redirect()->route('mapel.index')->with('success', 'Mapel dihapus.');
    }

    public function updateMode(Request $request, $id)
    {
        try {
            $mapel = Mapel::findOrFail($id);
            $request->validate(['mode' => 'required|in:offline,online']);
            $mapel->mode = $request->mode;
            $mapel->save();

            return response()->json(['success' => true, 'message' => 'Mode berhasil diubah', 'mode' => $mapel->mode]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function simpanJadwal(Request $request, $id)
    {
        try {
            $this->checkAndFixDatabase();
            // Pada halaman Mapel, form harus mengirimkan kelas_id dan guru_id
            $request->validate([
                'kelas_id'   => 'required|exists:kelas,id',
                'guru_id'    => 'required|exists:gurus,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam'   => 'required|in:single,double,triple',
                'status'     => 'required|in:offline,online', 
            ]);

            $kelas = Kelas::with('jadwals')->findOrFail($request->kelas_id);
            
            $currentTotalOffline = $kelas->jadwals->where('status', 'offline')->sum('jumlah_jam');
            $maxJam = $kelas->max_jam; 
            
            $tambahanBeban = ($request->status == 'offline') ? $request->jumlah_jam : 0;

            if (($currentTotalOffline + $tambahanBeban) > $maxJam) {
                return response()->json([
                    'success' => false, 
                    'message' => "Gagal! Slot Fisik (Offline) Kelas {$kelas->nama_kelas} penuh. Terisi: $currentTotalOffline JP, Maks: $maxJam JP."
                ], 422);
            }

            $jadwal = new Jadwal();
            $jadwal->kelas_id   = $request->kelas_id;
            $jadwal->mapel_id   = $id; // Menggunakan ID mapel dari URL
            $jadwal->guru_id    = $request->guru_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam   = $request->tipe_jam;
            $jadwal->status     = $request->status; 
            $jadwal->save();

            $jadwal->load(['guru', 'kelas']);

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
                'kelas_id'   => 'required|exists:kelas,id',
                'guru_id'    => 'required|exists:gurus,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam'   => 'required|in:single,double,triple',
                'status'     => 'required|in:offline,online', 
            ]);

            $kelas = Kelas::with('jadwals')->findOrFail($request->kelas_id);
            
            $currentTotalOthersOffline = $kelas->jadwals->where('id', '!=', $id)->where('status', 'offline')->sum('jumlah_jam');
            $maxJam = $kelas->max_jam;
            
            $tambahanBeban = ($request->status == 'offline') ? $request->jumlah_jam : 0;
            $newTotal = $currentTotalOthersOffline + $tambahanBeban;

            if ($newTotal > $maxJam) {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal! Total Fisik Kelas {$kelas->nama_kelas} ($newTotal JP) melebihi batas ($maxJam JP)."
                ], 422);
            }

            $jadwal->kelas_id   = $request->kelas_id;
            $jadwal->guru_id    = $request->guru_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam   = $request->tipe_jam;
            $jadwal->status     = $request->status; 
            $jadwal->save();

            $jadwal->load(['guru', 'kelas']);

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
            Jadwal::destroy($id);
            return response()->json(['success' => true, 'message' => 'Distribusi dihapus.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}