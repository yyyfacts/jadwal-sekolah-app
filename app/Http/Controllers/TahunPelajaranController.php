<?php

namespace App\Http\Controllers;

use App\Models\TahunPelajaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TahunPelajaranController extends Controller
{
    /**
     * Menampilkan Daftar Tahun Pelajaran
     */
    public function index()
    {
        // Mengambil data urut dari yang terbaru
        $tahuns = TahunPelajaran::orderBy('created_at', 'desc')->get();

        return view('penjadwalan.tahun_pelajaran', compact('tahuns'));
    }

    /**
     * Menyimpan Data Tahun Pelajaran Baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'tahun' => 'required|string',
            'semester' => 'required|in:Ganjil,Genap',
        ]);

        try {
            TahunPelajaran::create([
                'tahun' => $request->tahun,
                'semester' => $request->semester,
                'is_active' => false // Default tidak aktif
            ]);

            return redirect()->route('tahun-pelajaran.index')
                             ->with('success', 'Tahun Pelajaran berhasil ditambahkan.');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    /**
     * Memperbarui Data Tahun Pelajaran (Edit)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tahun' => 'required|string',
            'semester' => 'required|in:Ganjil,Genap',
        ]);

        try {
            $tahunPelajaran = TahunPelajaran::findOrFail($id);
            $tahunPelajaran->update([
                'tahun' => $request->tahun,
                'semester' => $request->semester,
            ]);

            return redirect()->route('tahun-pelajaran.index')
                             ->with('success', 'Data Tahun Pelajaran berhasil diperbarui.');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * Mengaktifkan Tahun Pelajaran (Set Active)
     */
    public function activate($id)
    {
        DB::beginTransaction();
        try {
            $target = TahunPelajaran::findOrFail($id);

            // 1. Non-aktifkan semua tahun pelajaran
            TahunPelajaran::query()->update(['is_active' => false]);

            // 2. Aktifkan tahun yang dipilih
            $target->update(['is_active' => true]);

            DB::commit();

            return redirect()->route('tahun-pelajaran.index')
                             ->with('success', "Tahun Ajaran {$target->tahun} ({$target->semester}) sekarang AKTIF.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                             ->with('error', 'Gagal mengaktifkan tahun ajaran: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus Data Tahun Pelajaran
     */
    public function destroy($id)
    {
        try {
            $tahun = TahunPelajaran::findOrFail($id);

            // Cegah penghapusan jika tahun sedang aktif
            if ($tahun->is_active) {
                return redirect()->back()->with('error', 'Tidak dapat menghapus Tahun Pelajaran yang sedang AKTIF.');
            }

            $tahun->delete();

            return redirect()->route('tahun-pelajaran.index')
                             ->with('success', 'Data Tahun Pelajaran berhasil dihapus.');

        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}