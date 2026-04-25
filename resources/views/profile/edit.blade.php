@extends('layouts.app')

@section('content')
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

<div class="w-full max-w-4xl mx-auto px-2 sm:px-4 pt-4 pb-2 flex flex-col">

    {{-- Header Halaman --}}
    <div class="mb-3">
        <h1 class="text-lg font-extrabold text-slate-800 leading-none">Ubah Data Pengguna</h1>
        <p class="text-slate-500 text-[10px] mt-1 font-medium">Perbarui informasi akun, nama pengguna, dan kata sandi.
        </p>
    </div>

    {{-- Pesan Berhasil --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-3 flex items-center justify-between p-2.5 bg-emerald-50 border border-emerald-100 rounded-lg shadow-sm text-emerald-800">
        <span class="font-bold text-[11px]">✅ {{ session('success') }}</span>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700">&times;</button>
    </div>
    @endif

    {{-- Kartu Form --}}
    <div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
        <form action="{{ route('user.update', $user->id) }}" method="POST" class="p-4 sm:p-5 space-y-4">
            @csrf
            @method('PUT')

            {{-- Nama Lengkap --}}
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama
                    Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-xs font-medium text-slate-700"
                    autocomplete="name" required>
                @error('name') <span class="text-[10px] text-red-500 block mt-1">{{ $message }}</span> @enderror
            </div>

            {{-- Nama Pengguna (Username) --}}
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Pengguna
                    (Login)</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}"
                        class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-xs font-mono text-slate-700"
                        required>
                </div>
                @error('username') <span class="text-[10px] text-red-500 block mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="border-t border-slate-100 my-2"></div>

            {{-- Info Ubah Kata Sandi --}}
            <div class="bg-amber-50 border border-amber-100 rounded-lg p-2.5">
                <h3 class="text-[11px] font-bold text-amber-800 flex items-center gap-1.5 mb-0.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                        </path>
                    </svg>
                    Ubah Kata Sandi (Opsional)
                </h3>
                <p class="text-[9px] text-amber-700/80">Biarkan kosong jika Anda tidak ingin mengubah sandi.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Sandi
                        Baru</label>
                    <input type="password" name="password"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-xs"
                        placeholder="Minimal 8 karakter" autocomplete="new-password">
                    @error('password') <span class="text-[10px] text-red-500 block mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Konfirmasi
                        Sandi</label>
                    <input type="password" name="password_confirmation"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-xs"
                        placeholder="Ulangi sandi baru" autocomplete="new-password">
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="pt-3 flex justify-between items-center">
                <a href="{{ route('user.index') }}"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-bold uppercase tracking-wider rounded-lg transition">Batal</a>
                <button type="submit"
                    class="flex items-center gap-1.5 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold uppercase tracking-wider rounded-lg shadow-sm transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection