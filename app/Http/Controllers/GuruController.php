<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Jadwal;
use App\Models\WaktuKosong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuruController extends Controller
{
    public function index()
    {
        $gurus = Guru::with(['jadwals.mapel', 'jadwals.kelas', 'waktuKosong'])
            ->orderBy('nama_guru')
            ->get();

        foreach ($gurus as $g) {
            $g->total_jam_mengajar = $g->jadwals->sum('jumlah_jam');
        }

        $mapels = Mapel::orderBy('nama_mapel')->get();
        $kelass = Kelas::orderBy('nama_kelas')->get();

        return view('penjadwalan.guru', compact('gurus', 'mapels', 'kelass'));
    }

    // --- CRUD GURU ---
    public function store(Request $request)
    {
        $request->validate([
            'nama_guru' => 'required|string',
            'kode_guru' => 'required|string|unique:gurus',
        ]);
        Guru::create($request->only('nama_guru', 'kode_guru'));
        return redirect()->route('guru.index')->with('success', 'Guru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_guru' => 'required|string',
            'kode_guru' => 'required|string|unique:gurus,kode_guru,' . $id,
        ]);
        Guru::findOrFail($id)->update($request->only('nama_guru', 'kode_guru'));
        return redirect()->route('guru.index')->with('success', 'Data guru diperbarui.');
    }

    public function destroy($id)
    {
        $guru = Guru::findOrFail($id);
        $guru->jadwals()->delete();
        $guru->waktuKosong()->delete();
        $guru->delete();
        return redirect()->route('guru.index')->with('success', 'Guru dihapus.');
    }

    // --- FITUR WAKTU KOSONG ---
    public function waktuKosongForm($id)
    {
        $guru = Guru::with('waktuKosong')->findOrFail($id);
        $selected = $guru->waktuKosong->map(function($wk) {
            return $wk->hari . '-' . $wk->jam;
        })->toArray();

        return view('penjadwalan.waktu_kosong', compact('guru', 'selected'));
    }

    public function simpanWaktuKosong(Request $request, $id)
    {
        $guru = Guru::findOrFail($id);
        DB::transaction(function () use ($guru, $request) {
            $guru->waktuKosong()->delete();
            if ($request->has('libur')) {
                foreach ($request->libur as $hari => $jamArray) {
                    foreach ($jamArray as $jam) {
                        $guru->waktuKosong()->create([
                            'hari' => $hari,
                            'jam' => (int)$jam
                        ]);
                    }
                }
            }
        });
        return redirect()->route('guru.index')->with('success', 'Jadwal libur guru berhasil diperbarui.');
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
            ]);

            $jadwal = new Jadwal();
            $jadwal->guru_id = $id; 
            $jadwal->mapel_id = $request->mapel_id;
            $jadwal->kelas_id = $request->kelas_id;
            $jadwal->jumlah_jam = $request->jumlah_jam;
            $jadwal->tipe_jam = $request->tipe_jam;
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
            ]);

            $jadwal->update([
                'mapel_id' => $request->mapel_id,
                'kelas_id' => $request->kelas_id,
                'jumlah_jam' => $request->jumlah_jam,
                'tipe_jam' => $request->tipe_jam
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