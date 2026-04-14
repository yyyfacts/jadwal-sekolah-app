<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Jadwal;
use Illuminate\Http\Request;

class GuruController extends Controller
{
    public function index()
    {
        $gurus = Guru::with(['jadwals.mapel', 'jadwals.kelas'])
            ->orderBy('nama_guru')
            ->get();

        foreach ($gurus as $g) {
            $g->total_jam_mengajar = $g->jadwals->sum('jumlah_jam');
            $g->hari_array = $g->hari_mengajar ? json_decode($g->hari_mengajar, true) : [];
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
        $data['hari_mengajar'] = json_encode($request->hari_mengajar ?? []);

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
            $jadwal = Jadwal::find($id);
            if ($jadwal) $jadwal->delete();
            return response()->json(['success' => true, 'message' => 'Jadwal Dihapus!']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}