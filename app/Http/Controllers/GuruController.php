<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class GuruController extends Controller
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

        if (Schema::hasTable('gurus') && !Schema::hasColumn('gurus', 'jenis_hari')) {
            Schema::table('gurus', function (Blueprint $table) {
                $table->enum('jenis_hari', ['hard', 'soft'])->default('hard')->after('hari_mengajar');
            });
        }

        if (Schema::hasTable('gurus') && !Schema::hasColumn('gurus', 'status_pegawai')) {
            Schema::table('gurus', function (Blueprint $table) {
                $table->enum('status_pegawai', ['PNS/P3K', 'Guru Tamu', 'Guru Ngamen'])->default('PNS/P3K')->after('jenis_hari');
            });
        }

        // PENGECEKAN KOLOM BARU: limit_harian (Untuk SF-4)
        if (Schema::hasTable('gurus') && !Schema::hasColumn('gurus', 'limit_harian')) {
            Schema::table('gurus', function (Blueprint $table) {
                $table->integer('limit_harian')->default(8)->after('status_pegawai');
            });
        }
    }

    public function index()
    {
        $this->checkAndFixDatabase();

        $gurus = Guru::with(['jadwals.mapel', 'jadwals.kelas'])
            ->orderBy('nama_guru')
            ->get();

        foreach ($gurus as $g) {
            $g->total_jam_mengajar = $g->jadwals->sum('jumlah_jam');
            $g->hari_array = is_array($g->hari_mengajar) ? $g->hari_mengajar : [];
        }

        $mapels = Mapel::orderBy('nama_mapel')->get();
        $kelases = Kelas::orderBy('nama_kelas')->get();

        return view('penjadwalan.guru', compact('gurus', 'mapels', 'kelases'));
    }

    public function store(Request $request)
    {
        $this->checkAndFixDatabase();

        $request->validate([
            'nama_guru'      => 'required|string',
            'kode_guru'      => 'required|string|unique:gurus',
            'hari_mengajar'  => 'nullable|array',
            'jenis_hari'     => 'nullable|in:hard,soft',
            'status_pegawai' => 'nullable|in:PNS/P3K,Guru Tamu,Guru Ngamen',
            'limit_harian'   => 'nullable|integer|min:1|max:20' // Validasi tambahan
        ]);

        $data = $request->only('nama_guru', 'kode_guru', 'jenis_hari', 'status_pegawai', 'limit_harian');
        $data['hari_mengajar'] = $request->hari_mengajar ?? [];
        $data['limit_harian'] = $request->limit_harian ?? 8; // Default 8 JP

        Guru::create($data);
        return redirect()->route('guru.index')->with('success', 'Guru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $this->checkAndFixDatabase();

        $request->validate([
            'nama_guru'      => 'required|string',
            'kode_guru'      => 'required|string|unique:gurus,kode_guru,' . $id,
            'hari_mengajar'  => 'nullable|array',
            'jenis_hari'     => 'nullable|in:hard,soft',
            'status_pegawai' => 'nullable|in:PNS/P3K,Guru Tamu,Guru Ngamen',
            'limit_harian'   => 'nullable|integer|min:1|max:20' // Validasi tambahan
        ]);

        $data = $request->only('nama_guru', 'kode_guru', 'jenis_hari', 'status_pegawai', 'limit_harian');
        $data['hari_mengajar'] = $request->hari_mengajar ?? [];
        $data['limit_harian'] = $request->limit_harian ?? 8; // Default 8 JP

        Guru::findOrFail($id)->update($data);
        return redirect()->route('guru.index')->with('success', 'Data guru diperbarui.');
    }

    public function destroy($id)
    {
        $guru = Guru::findOrFail($id);
        $guru->jadwals()->delete();
        $guru->delete();
        return redirect()->route('guru.index')->with('success', 'Guru dihapus.');
    }

    public function simpanJadwal(Request $request, $id)
    {
        try {
            $this->checkAndFixDatabase();
            
            $request->validate([
                'kelas_id'   => 'required|exists:kelas,id',
                'mapel_id'   => 'required|exists:mapels,id',
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
                    'message' => "Gagal! Slot Fisik (Offline) penuh. Terisi: $currentTotalOffline JP, Maks: $maxJam JP."
                ], 422);
            }

            $jadwal = new Jadwal();
            $jadwal->kelas_id   = $request->kelas_id;
            $jadwal->mapel_id   = $request->mapel_id;
            $jadwal->guru_id    = $id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam   = $request->tipe_jam;
            $jadwal->status     = $request->status; 
            $jadwal->master_hari_id = null;
            $jadwal->jam        = null; 
            $jadwal->save();

            $jadwal->load(['mapel', 'kelas']);

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
                'mapel_id'   => 'required|exists:mapels,id',
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
                    'message' => "Gagal! Total Fisik ($newTotal JP) melebihi batas ($maxJam JP)."
                ], 422);
            }

            $jadwal->kelas_id   = $request->kelas_id;
            $jadwal->mapel_id   = $request->mapel_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam   = $request->tipe_jam;
            $jadwal->status     = $request->status; 
            $jadwal->save();

            $jadwal->load(['mapel', 'kelas']);

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
            $jadwal = Jadwal::find($id);
            if ($jadwal) $jadwal->delete();
            return response()->json(['success' => true, 'message' => 'Jadwal Dihapus!']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}