<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        // Ambil semua user untuk ditampilkan di daftar pengaturan
        $users = User::all();
        return view('pengaturan.user', compact('users'));
    }

    public function store(Request $request)
    {
        // 1. Validasi: Email diganti jadi username, rule 'email' dihapus
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users', // Ganti disini
            'password' => 'required|string|min:8',
        ]);

        // 2. Simpan Data: Kolom email diganti jadi username
        User::create([
            'name' => $request->name,
            'username' => $request->username, // Ganti disini
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('user.index')->with('success', 'User asisten berhasil ditambahkan!');
    }

    public function destroy($id)
    {
        // Proteksi: Biar ga hapus akun yang lagi dipake login
        if (Auth::id() == $id) {
            return redirect()->route('user.index')->with('error', 'Anda tidak bisa menghapus akun sendiri!');
        }

        User::findOrFail($id)->delete();
        return redirect()->route('user.index')->with('success', 'User berhasil dihapus.');
    }
}