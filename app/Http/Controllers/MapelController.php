<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\Jadwal;
use Illuminate\Http\Request;

class MapelController extends Controller
{
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
            Jadwal::destroy($id);
            return response()->json(['success' => true, 'message' => 'Distribusi dihapus.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}