<?php

namespace App\Http\Controllers;

use App\Models\MasterHari;
use Illuminate\Http\Request;

class MasterHariController extends Controller
{
    public function index()
    {
        $haris = MasterHari::all();
        return view('penjadwalan.master_hari', compact('haris'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_hari' => 'required|string|max:20',
        ]);

        $data = $request->only('nama_hari');
        $data['is_active'] = true; // Default aktif saat ditambah

        MasterHari::create($data);
        return redirect()->route('master-hari.index')->with('success', 'Data Hari berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_hari' => 'required|string|max:20',
            'is_active' => 'required|boolean',
        ]);

        MasterHari::findOrFail($id)->update($request->only('nama_hari', 'is_active'));
        return redirect()->route('master-hari.index')->with('success', 'Konfigurasi hari diperbarui.');
    }

    public function destroy($id)
    {
        $hari = MasterHari::find($id);
        if ($hari) $hari->delete(); // Cek pakai IF biar anti-error kalau kepencet 2x

        return redirect()->route('master-hari.index')->with('success', 'Data Hari berhasil dihapus.');
    }
}