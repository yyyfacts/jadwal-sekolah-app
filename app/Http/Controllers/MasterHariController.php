<?php

namespace App\Http\Controllers;

use App\Models\MasterHari;
use Illuminate\Http\Request;

class MasterHariController extends Controller
{
    /**
     * Menampilkan Daftar Hari
     */
    public function index()
    {
        // Mengambil semua data hari
        $haris = MasterHari::all();

        return view('penjadwalan.master_hari', compact('haris'));
    }

    /**
     * Menyimpan Data Hari Baru (Jika sekolah nambah hari khusus)
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_hari' => 'required|string|max:20',
            'max_jam' => 'required|integer|min:0',
        ]);

        try {
            MasterHari::create([
                'nama_hari' => $request->nama_hari,
                'max_jam' => $request->max_jam,
                'is_active' => true // Default aktif
            ]);

            return redirect()->route('master-hari.index')
                             ->with('success', 'Data Hari berhasil ditambahkan.');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Mengupdate Data Hari (Misal mengubah batas max jam atau meliburkan hari)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_hari' => 'required|string|max:20',
            'max_jam' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        try {
            $hari = MasterHari::findOrFail($id);
            $hari->update([
                'nama_hari' => $request->nama_hari,
                'max_jam' => $request->max_jam,
                'is_active' => $request->is_active,
            ]);

            return redirect()->route('master-hari.index')
                             ->with('success', "Konfigurasi hari {$hari->nama_hari} berhasil diperbarui.");

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Gagal memperbarui data hari: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus Data Hari
     */
    public function destroy($id)
    {
        try {
            $hari = MasterHari::findOrFail($id);
            $hari->delete();

            return redirect()->route('master-hari.index')
                             ->with('success', 'Data Hari berhasil dihapus.');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}