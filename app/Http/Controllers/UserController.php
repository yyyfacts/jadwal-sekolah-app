<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule; // Tambahkan ini untuk validasi unik saat update

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
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        // 2. Simpan Data
        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('user.index')->with('success', 'User asisten berhasil ditambahkan!');
    }

    // FUNGSI BARU: Menampilkan halaman edit
    public function edit($id)
    {
        $user = User::findOrFail($id);
        // Pastikan path view di bawah ini sesuai dengan letak file edit.blade.php Anda
        // Misalnya jika di dalam folder resources/views/pengaturan/
        return view('pengaturan.edit', compact('user')); 
    }

    // FUNGSI BARU: Menyimpan perubahan data edit
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            // Pengecualian unique untuk user yang sedang diedit
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            // Password dibikin nullable, dan wajib sama dengan password_confirmation jika diisi
            'password' => 'nullable|string|min:8|confirmed', 
        ]);

        $user->name = $request->name;
        $user->username = $request->username;

        // Cek apakah kolom password diisi. Jika diisi, update passwordnya
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('user.index')->with('success', 'Data user berhasil diperbarui!');
    }

    public function destroy($id)
    {
        // Proteksi: Biar ga hapus akun yang lagi dipake login
        if (Auth::id() == $id) {
            return redirect()->route('user.index')->with('error', 'Anda tidak bisa menghapus akun Anda sendiri!');
        }

        User::findOrFail($id)->delete();
        return redirect()->route('user.index')->with('success', 'User berhasil dihapus.');
    }
}