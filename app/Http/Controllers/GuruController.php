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
    // FUNGSI INI WAJIB ADA AGAR TIDAK ERROR SAAT DIPANGGIL DI BAWAH
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
        $request->validate([
            'nama_guru' => 'required|string',
            'kode_guru' => 'required|string|unique:gurus',
            'hari_mengajar' => 'nullable|array'
        ]);

        $data = $request->only('nama_guru', 'kode_guru');
        $data['hari_mengajar'] = $request->hari_mengajar ?? [];

        Guru::create($data);
        return redirect()->route('guru.index')->with('success', 'Guru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_guru' => 'required|string',
            'kode_guru' => 'required|string|unique:gurus,kode_guru,' . $id,
            'hari_mengajar' => 'nullable|array'
        ]);

        $data = $request->only('nama_guru', 'kode_guru');
        $data['hari_mengajar'] = json_encode($request->hari_mengajar ?? []);

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
            
            // PERBAIKAN: Validasi butuh kelas_id, BUKAN guru_id (karena guru_id diambil dari $id URL)
            $request->validate([
                'kelas_id'   => 'required|exists:kelas,id',
                'mapel_id'   => 'required|exists:mapels,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam'   => 'required|in:single,double,triple',
                'status'     => 'required|in:offline,online', 
            ]);

            // PERBAIKAN: Cari kelas berdasarkan inputan kelas_id dari form
            $kelas = Kelas::with('jadwals')->findOrFail($request->kelas_id);
            
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
            $jadwal->kelas_id   = $request->kelas_id; // Mengambil dari form
            $jadwal->mapel_id   = $request->mapel_id; // Mengambil dari form
            $jadwal->guru_id    = $id;                // Mengambil dari parameter URL
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam   = $request->tipe_jam;
            $jadwal->status     = $request->status; 
            $jadwal->master_hari_id = null;
            $jadwal->jam        = null; 
            $jadwal->save();

            $jadwal->load(['mapel', 'kelas']); // Load relasi kelas, bukan guru, untuk ditampilkan di tabel

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

            // PERBAIKAN: Request ini dari form edit jadwal guru
            $request->validate([
                'kelas_id'   => 'required|exists:kelas,id',
                'mapel_id'   => 'required|exists:mapels,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam'   => 'required|in:single,double,triple',
                'status'     => 'required|in:offline,online', 
            ]);

            // Cek limit kelas menggunakan ID kelas yang baru dipilih
            $kelas = Kelas::with('jadwals')->findOrFail($request->kelas_id);
            
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

            $jadwal->kelas_id   = $request->kelas_id;
            $jadwal->mapel_id   = $request->mapel_id;
            // $jadwal->guru_id tidak diubah karena masih diedit dari profil guru yang sama
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