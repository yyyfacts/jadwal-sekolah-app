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
        // Ambil semua user kecuali user yang sedang login (biar ga hapus diri sendiri)
        $users = User::all();
        return view('pengaturan.user', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('user.index')->with('success', 'User asisten berhasil ditambahkan!');
    }

    public function destroy($id)
    {
        if (Auth::id() == $id) {
            return redirect()->route('user.index')->with('error', 'Anda tidak bisa menghapus akun sendiri!');
        }

        User::findOrFail($id)->delete();
        return redirect()->route('user.index')->with('success', 'User berhasil dihapus.');
    }
}