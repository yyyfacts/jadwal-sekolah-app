<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapelController extends Controller
{
    private function getKapasitasKelas($nama_kelas)
    {
        $nama = strtoupper(str_replace(['-', '  '], [' ', ' '], $nama_kelas));
        if (str_contains($nama, 'XI') && !str_contains($nama, 'XII')) {
            if (str_contains($nama, 'F 4') || str_contains($nama, 'F4') || str_contains($nama, 'IV') ||
                str_contains($nama, 'F 5') || str_contains($nama, 'F5') || str_contains($nama, ' V')) {
                return 50;
            }
        }
        if ((str_contains($nama, 'X') || str_contains($nama, '10')) && !str_contains($nama, 'XI') && !str_contains($nama, 'XII')) {
            return 46;
        }
        return 47;
    }

    public function index()
    {
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
            'kelompok' => 'nullable|string',
        ]);
        Mapel::create($request->all());
        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_mapel' => 'required|string',
            'kode_mapel' => 'required|string|unique:mapels,kode_mapel,' . $id,
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

    // ================================
    // UPDATE MODE (ONLINE / OFFLINE)
    // ================================
    public function updateMode(Request $request, $id)
    {
        try {
            $mapel = Mapel::findOrFail($id);
            $request->validate([
                'mode' => 'required|in:offline,online'
            ]);

            $mapel->mode = $request->mode;
            $mapel->save();

            return response()->json([
                'success' => true,
                'message' => 'Mode berhasil diubah',
                'mode' => $mapel->mode
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // --- DISTRIBUSI (AJAX) ---
    public function simpanJadwal(Request $request, $id)
    {
        try {
            $request->validate([
                'kelas_id' => 'required|exists:kelas,id',
                'guru_id' => 'required|exists:gurus,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam' => 'required|in:single,double,triple',
            ]);

            $targetKelas = Kelas::with('jadwals')->findOrFail($request->kelas_id);
            $currentTotal = $targetKelas->jadwals->sum('jumlah_jam');
            $maxJam = $this->getKapasitasKelas($targetKelas->nama_kelas);

            if (($currentTotal + $request->jumlah_jam) > $maxJam) {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal! Kelas {$targetKelas->nama_kelas} penuh ($currentTotal/$maxJam)."
                ], 422);
            }

            $jadwal = new Jadwal();
            $jadwal->mapel_id = $id;
            $jadwal->kelas_id = $request->kelas_id;
            $jadwal->guru_id = $request->guru_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam = $request->tipe_jam;
            $jadwal->hari = 'Senin'; 
            $jadwal->jam = 1;      
            $jadwal->save();

            $jadwal->load(['kelas', 'guru']);

            return response()->json([
                'success' => true, 
                'message' => 'Berhasil didistribusikan!', 
                'jadwal' => $jadwal
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateJadwal(Request $request, $id)
    {
        try {
            $jadwal = Jadwal::findOrFail($id);
            $request->validate([
                'kelas_id' => 'required|exists:kelas,id',
                'guru_id' => 'required|exists:gurus,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam' => 'required|in:single,double,triple',
            ]);

            $targetKelas = Kelas::with('jadwals')->findOrFail($request->kelas_id);
            $currentTotalOthers = $targetKelas->jadwals->where('id', '!=', $id)->sum('jumlah_jam');
            $maxJam = $this->getKapasitasKelas($targetKelas->nama_kelas);
            $newTotal = $currentTotalOthers + $request->jumlah_jam;

            if ($newTotal > $maxJam) {
                return response()->json([
                    'success' => false,
                    'message' => "Gagal! Overload di kelas {$targetKelas->nama_kelas} ($newTotal/$maxJam)."
                ], 422);
            }

            $jadwal->update($request->only('kelas_id', 'guru_id', 'jumlah_jam', 'tipe_jam'));
            $jadwal->load(['kelas', 'guru']);

            return response()->json([
                'success' => true, 
                'message' => 'Update berhasil!', 
                'jadwal' => $jadwal
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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