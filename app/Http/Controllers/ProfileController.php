<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // 1. Update Nama
        $user->name = $request->name;

        // 2. Update Foto jika ada upload
        if ($request->hasFile('avatar')) {
            
            // Hapus foto lama jika ada & file fisiknya eksis
            if ($user->avatar) {
                // Cek di disk 'public'
                if (Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                    Storage::disk('public')->delete('avatars/' . $user->avatar);
                }
            }

            // Simpan foto baru
            $imageName = time() . '.' . $request->avatar->extension();
            
            // PENTING: Gunakan disk 'public' agar tersimpan di storage/app/public/avatars
            $request->avatar->storeAs('avatars', $imageName, 'public'); 
            
            $user->avatar = $imageName;
        }

        // 3. Update Password jika diisi
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui!');
    }
}