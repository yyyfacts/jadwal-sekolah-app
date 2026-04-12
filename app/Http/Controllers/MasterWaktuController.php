<?php

namespace App\Http\Controllers;

use App\Models\MasterWaktu;
use Illuminate\Http\Request;

class MasterWaktuController extends Controller
{
    public function index()
    {
        $waktus = MasterWaktu::getOrdered();
        return view('penjadwalan.master_waktu', compact('waktus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // Diubah jadi nullable agar boleh dikosongkan saat ngisi Istirahat/Upacara
            'jam_ke'        => 'nullable|integer|min:0', 
            'waktu_mulai'   => 'required|date_format:H:i',
            'waktu_selesai' => 'required|date_format:H:i',
            'tipe'          => 'required|string|max:50',
            'mulai_senin'   => 'nullable|date_format:H:i',
            'selesai_senin' => 'nullable|date_format:H:i',
            'tipe_senin'    => 'nullable|string|max:50',
            'mulai_jumat'   => 'nullable|date_format:H:i',
            'selesai_jumat' => 'nullable|date_format:H:i',
            'tipe_jumat'    => 'nullable|string|max:50',
        ]);

        $data = $request->all();
        
        // Jika inputan jam_ke kosong, kita paksa jadi null untuk masuk ke database
        if (!isset($data['jam_ke']) || $data['jam_ke'] === '') {
            $data['jam_ke'] = null;
        }

        MasterWaktu::create($data);
        
        // Pesan sukses disesuaikan biar nggak error kalau jam_ke nya null
        return redirect()->route('master-waktu.index')->with('success', "Waktu kegiatan berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            // Diubah jadi nullable agar boleh dikosongkan saat diedit
            'jam_ke'        => 'nullable|integer|min:0', 
            'waktu_mulai'   => 'required|date_format:H:i', 
            'waktu_selesai' => 'required|date_format:H:i',
            'tipe'          => 'required|string|max:50',
            'mulai_senin'   => 'nullable|date_format:H:i',
            'selesai_senin' => 'nullable|date_format:H:i',
            'tipe_senin'    => 'nullable|string|max:50',
            'mulai_jumat'   => 'nullable|date_format:H:i',
            'selesai_jumat' => 'nullable|date_format:H:i',
            'tipe_jumat'    => 'nullable|string|max:50',
        ]);

        $waktu = MasterWaktu::find($id);
        if (!$waktu) return redirect()->back()->with('error', 'Data tidak ditemukan.');
        
        $data = $request->all();
        
        // Jika inputan jam_ke dihapus/dikosongin saat edit, kita set jadi null
        if (!isset($data['jam_ke']) || $data['jam_ke'] === '') {
            $data['jam_ke'] = null;
        }

        $waktu->update($data);
        return redirect()->route('master-waktu.index')->with('success', "Data jam pelajaran diperbarui.");
    }

    public function destroy($id)
    {
        $waktu = MasterWaktu::find($id);
        if ($waktu) $waktu->delete();

        return redirect()->route('master-waktu.index')->with('success', 'Data jam pelajaran dihapus.');
    }
}