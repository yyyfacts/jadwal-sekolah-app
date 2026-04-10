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
            'jam_ke'        => 'required|integer|min:1',
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

        MasterWaktu::create($request->all());
        return redirect()->route('master-waktu.index')->with('success', "Jam ke-{$request->jam_ke} berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'jam_ke'        => 'required|integer|min:1',
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
        
        $waktu->update($request->all());
        return redirect()->route('master-waktu.index')->with('success', "Data jam pelajaran diperbarui.");
    }

    public function destroy($id)
    {
        $waktu = MasterWaktu::find($id);
        if ($waktu) $waktu->delete();

        return redirect()->route('master-waktu.index')->with('success', 'Data jam pelajaran dihapus.');
    }
}