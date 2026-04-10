<?php

namespace App\Http\Controllers;

use App\Models\MasterWaktu;
use Illuminate\Http\Request;

class MasterWaktuController extends Controller
{
    /**
     * Menampilkan Daftar Jam / Waktu Pembelajaran
     */
    public function index()
    {
        // Mengambil data urut berdasarkan jam_ke (memanfaatkan helper dari Model)
        $waktus = MasterWaktu::getOrdered();

        return view('penjadwalan.master_waktu.index', compact('waktus'));
    }

    /**
     * Menyimpan Data Jam Pelajaran Baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'jam_ke' => 'required|integer|min:1',
            'waktu_mulai' => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
            'tipe' => 'required|string|max:50',
        ]);

        try {
            MasterWaktu::create([
                'jam_ke' => $request->jam_ke,
                'waktu_mulai' => $request->waktu_mulai,
                'waktu_selesai' => $request->waktu_selesai,
                'tipe' => $request->tipe,
            ]);

            return redirect()->route('master-waktu.index')
                             ->with('success', "Jam ke-{$request->jam_ke} berhasil ditambahkan.");

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Gagal menyimpan data waktu: ' . $e->getMessage());
        }
    }

    /**
     * Mengupdate Data Jam Pelajaran
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'jam_ke' => 'required|integer|min:1',
            'waktu_mulai' => 'required|date_format:H:i', // Format jam dan menit
            'waktu_selesai' => 'required|date_format:H:i|after:waktu_mulai',
            'tipe' => 'required|string|max:50',
        ]);

        try {
            $waktu = MasterWaktu::findOrFail($id);
            $waktu->update([
                'jam_ke' => $request->jam_ke,
                'waktu_mulai' => $request->waktu_mulai,
                'waktu_selesai' => $request->waktu_selesai,
                'tipe' => $request->tipe,
            ]);

            return redirect()->route('master-waktu.index')
                             ->with('success', "Data jam ke-{$waktu->jam_ke} berhasil diperbarui.");

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Gagal memperbarui data waktu: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus Data Jam Pelajaran
     */
    public function destroy($id)
    {
        try {
            $waktu = MasterWaktu::findOrFail($id);
            $waktu->delete();

            return redirect()->route('master-waktu.index')
                             ->with('success', 'Data jam pelajaran berhasil dihapus.');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}