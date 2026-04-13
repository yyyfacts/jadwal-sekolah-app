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
            // Decode array hari untuk ditampilkan di checkbox modal edit
            $g->hari_array = $g->hari_mengajar ? json_decode($g->hari_mengajar, true) : [];
        }

        $mapels = Mapel::orderBy('nama_mapel')->get();
        $kelases = Kelas::orderBy('nama_kelas')->get();

        return view('penjadwalan.guru', compact('gurus', 'mapels', 'kelases'));
    }

    // --- CRUD GURU ---
    public function store(Request $request)
    {
        $request->validate([
            'nama_guru' => 'required|string',
            'kode_guru' => 'required|string|unique:gurus',
            'hari_mengajar' => 'nullable|array' // Validasi array hari
        ]);

        $data = $request->only('nama_guru', 'kode_guru');
        // Simpan array hari menjadi format JSON
        $data['hari_mengajar'] = $request->hari_mengajar ? json_encode($request->hari_mengajar) : json_encode([]);

        Guru::create($data);
        return redirect()->route('guru.index')->with('success', 'Guru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_guru' => 'required|string',
            'kode_guru' => 'required|string|unique:gurus,kode_guru,' . $id,
            'hari_mengajar' => 'nullable|array' // Validasi array hari
        ]);

        $data = $request->only('nama_guru', 'kode_guru');
        // Simpan array hari menjadi format JSON
        $data['hari_mengajar'] = $request->hari_mengajar ? json_encode($request->hari_mengajar) : json_encode([]);

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

    // --- MANAJEMEN BEBAN MENGAJAR (AJAX) ---
    public function simpanJadwal(Request $request, $id)
    {
        try {
            $request->validate([
                'mapel_id'   => 'required|exists:mapels,id',
                'kelas_id'   => 'required|exists:kelas,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam'   => 'required|in:single,double,triple',
                'status'     => 'required|in:offline,online', // VALIDASI STATUS
            ]);

            $jadwal = new Jadwal();
            $jadwal->guru_id = $id; 
            $jadwal->mapel_id = $request->mapel_id;
            $jadwal->kelas_id = $request->kelas_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam = $request->tipe_jam;
            $jadwal->status = $request->status; // SIMPAN STATUS
            $jadwal->save();

            $jadwal->load(['mapel', 'kelas']);

            return response()->json(['success' => true, 'message' => 'Jadwal Disimpan!', 'jadwal' => $jadwal]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateJadwal(Request $request, $id)
    {
        try {
            $jadwal = Jadwal::findOrFail($id);
            $request->validate([
                'mapel_id'   => 'required|exists:mapels,id',
                'kelas_id'   => 'required|exists:kelas,id',
                'jumlah_jam' => 'required|numeric|min:1',
                'tipe_jam'   => 'required|in:single,double,triple',
                'status'     => 'required|in:offline,online', // VALIDASI STATUS
            ]);

            $jadwal->update([
                'mapel_id' => $request->mapel_id,
                'kelas_id' => $request->kelas_id,
                'jumlah_jam' => $request->jumlah_jam,
                'tipe_jam' => $request->tipe_jam,
                'status' => $request->status, // UPDATE STATUS
            ]);

            $jadwal->load(['mapel', 'kelas']);
            return response()->json(['success' => true, 'message' => 'Jadwal Updated!', 'jadwal' => $jadwal]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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