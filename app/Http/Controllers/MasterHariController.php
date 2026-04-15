<?php

namespace App\Http\Controllers;

use App\Models\MasterHari;
use App\Models\WaktuHari;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterHariController extends Controller
{
    public function index()
    {
        // Tarik data hari beserta relasi waktu-nya
        $haris = MasterHari::with(['waktuHaris' => function($query) {
            $query->orderBy('waktu_mulai', 'asc');
        }])->get();
        
        return view('penjadwalan.master_hari', compact('haris'));
    }

    public function store(Request $request)
    {
        $request->validate(['nama_hari' => 'required|string|max:20']);
        $data = $request->only('nama_hari');
        $data['is_active'] = true;

        MasterHari::create($data);
        return redirect()->route('master-hari.index')->with('success', 'Data Hari berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_hari' => 'required|string|max:20',
            // Gunakan boolean untuk is_active
            'is_active' => 'required|boolean', 
        ]);

        MasterHari::findOrFail($id)->update($request->only('nama_hari', 'is_active'));
        return redirect()->route('master-hari.index')->with('success', 'Konfigurasi hari diperbarui.');
    }

    public function destroy($id)
    {
        $hari = MasterHari::findOrFail($id);
        // PERBAIKAN: Hapus anaknya dulu secara eksplisit biar tidak error constraint DB
        $hari->waktuHaris()->delete(); 
        $hari->delete(); 
        
        return redirect()->route('master-hari.index')->with('success', 'Data Hari berhasil dihapus.');
    }

    // --- FUNGSI UNTUK POP-UP WAKTU JAM KE- ---
    
    public function getWaktu($id)
    {
        $waktu = WaktuHari::where('master_hari_id', $id)->orderBy('waktu_mulai', 'asc')->get();
        return response()->json($waktu);
    }

    public function simpanWaktu(Request $request, $id)
    {
        // PERBAIKAN: Tambahkan validasi untuk ISI array (*), bukan cuma wadah array-nya saja
        $request->validate([
            'jam_ke'          => 'required|array',
            'jam_ke.*'        => 'nullable|integer', // Nullable: misal jam istirahat tidak diisi angka
            'tipe'            => 'required|array',
            'tipe.*'          => 'required|in:Belajar,Istirahat,Upacara,Sholat,Senam,Sholat Dhuha,Jumat Bersih,Pramuka',
            'waktu_mulai'     => 'required|array',
            'waktu_mulai.*'   => 'required|string', 
            'waktu_selesai'   => 'required|array',
            'waktu_selesai.*' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // 1. Hapus semua jadwal lama di hari ini
            WaktuHari::where('master_hari_id', $id)->delete();

            // 2. Insert jadwal baru dari input pop-up
            if ($request->jam_ke && is_array($request->jam_ke)) {
                foreach ($request->jam_ke as $index => $jam) {
                    WaktuHari::create([
                        'master_hari_id' => $id,
                        'jam_ke'         => $jam,
                        'waktu_mulai'    => $request->waktu_mulai[$index],
                        'waktu_selesai'  => $request->waktu_selesai[$index],
                        'tipe'           => $request->tipe[$index], 
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('master-hari.index')->with('success', 'Aturan Jam Ke- berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('master-hari.index')->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
}