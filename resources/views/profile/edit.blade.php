@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Header Halaman --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Profil Saya</h1>
        <p class="text-slate-500 text-sm mt-1">Perbarui informasi akun dan kata sandi Anda.</p>
    </div>

    {{-- Flash Message --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms
        class="mb-6 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800">
        <div class="flex items-center gap-3">
            <div class="p-1.5 bg-emerald-100 rounded-full text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    @endif

    {{-- Form Card --}}
    <div class="bg-white rounded-xl shadow-lg shadow-slate-200/50 border border-slate-200 p-8">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="flex flex-col md:flex-row gap-10">

                {{-- KOLOM KIRI: FOTO PROFIL --}}
                <div
                    class="w-full md:w-1/3 flex flex-col items-center border-b md:border-b-0 md:border-r border-slate-100 pb-8 md:pb-0 md:pr-8">
                    <div class="relative group">
                        <div
                            class="w-40 h-40 rounded-full overflow-hidden border-4 border-white shadow-lg ring-1 ring-slate-100 mb-4 relative bg-slate-100">

                            {{-- Logic Gambar --}}
                            @if($user->avatar)
                            <img src="{{ asset('storage/avatars/' . $user->avatar) }}" id="preview-img"
                                alt="Foto Profil"
                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                            @else
                            <div id="avatar-placeholder"
                                class="w-full h-full flex items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600 text-white">
                                <span class="text-5xl font-bold">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                            <img id="preview-img" class="w-full h-full object-cover hidden">
                            @endif

                            {{-- Overlay Edit saat Hover --}}
                            <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 cursor-pointer"
                                onclick="document.getElementById('file-input').click()">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <label
                        class="cursor-pointer inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mt-2">
                        <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Upload Foto Baru
                        <input type="file" id="file-input" name="avatar" class="hidden" accept="image/*"
                            onchange="previewImage(this)">
                    </label>
                    <p class="text-[10px] text-slate-400 mt-2 text-center">Format: JPG, PNG. Maksimal 2MB.</p>
                </div>

                {{-- KOLOM KANAN: DATA FORM --}}
                <div class="w-full md:w-2/3 space-y-6">

                    {{-- Nama Lengkap --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama
                            Lengkap</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                            class="w-full max-w-xl border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-medium text-slate-700"
                            autocomplete="name">
                        @error('name') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Email (Read Only) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Email (Tidak
                            dapat diubah)</label>
                        <div class="relative max-w-xl">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207">
                                    </path>
                                </svg>
                            </div>
                            <input type="email" value="{{ $user->email }}" disabled
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-200 bg-slate-100 rounded-lg text-slate-500 cursor-not-allowed text-sm">
                        </div>
                    </div>

                    <div class="max-w-xl pt-2">
                        <div class="border-t border-slate-100"></div>
                    </div>

                    <div class="bg-yellow-50/50 border border-yellow-100 rounded-lg p-4 max-w-xl">
                        <h3 class="text-sm font-bold text-yellow-700 mb-1 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                </path>
                            </svg>
                            Ganti Password
                        </h3>
                        <p class="text-xs text-yellow-600">Isi kolom di bawah ini HANYA jika Anda ingin mengganti
                            password. Biarkan kosong jika tidak.</p>
                    </div>

                    {{-- Password Baru --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-xl">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Password
                                Baru</label>
                            <input type="password" name="password"
                                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-sm"
                                placeholder="Minimal 8 karakter" autocomplete="new-password">
                            @error('password') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Konfirmasi
                                Password</label>
                            <input type="password" name="password_confirmation"
                                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-sm"
                                placeholder="Ulangi password baru" autocomplete="new-password">
                        </div>
                    </div>

                    {{-- Tombol Simpan --}}
                    <div class="pt-4 max-w-xl flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold uppercase tracking-wide rounded-lg shadow-md hover:shadow-lg hover:shadow-indigo-500/30 transition-all duration-200 transform active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('preview-img');
            const placeholder = document.getElementById('avatar-placeholder');

            img.src = e.target.result;
            img.classList.remove('hidden');
            if (placeholder) {
                placeholder.classList.add('hidden');
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection