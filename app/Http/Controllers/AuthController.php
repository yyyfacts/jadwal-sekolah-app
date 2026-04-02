<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Tampilkan Halaman Login
    public function showLoginForm()
    {
        return view('auth.login'); 
    }

    // Proses Login
    public function login(Request $request)
    {
        // 1. Validasi input: Ganti 'email' jadi 'username' dan tambahkan pesan kustom Bahasa Indonesia
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ], [
            // Pesan error kustom biar nggak muncul bahasa Inggris lagi
            'username.required' => 'Nama Pengguna wajib diisi.',
            'password.required' => 'Kata Sandi wajib diisi.',
        ]);

        // 2. Coba login (dengan fitur Remember Me)
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Redirect ke halaman utama jika sukses
            return redirect()->intended(route('guru.index'));
        }

        // 3. Kembali ke login jika gagal (Email diganti Username)
        return back()->withErrors([
            'username' => 'Nama Pengguna atau Kata Sandi salah.',
        ])->onlyInput('username');
    }

    // Proses Logout
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}