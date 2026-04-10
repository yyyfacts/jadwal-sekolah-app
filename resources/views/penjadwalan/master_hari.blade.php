@extends('layouts.app')

@section('content')
{{-- FIX: Hapus x-data dari div luar agar tidak bentrok --}}
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Header Halaman --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Master Hari</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola data hari aktif dan batas maksimal jam mengajar.</p>
        </div>
    </div>

    {{-- Flash Message (Success) --}}
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

    {{-- Flash Message (Error) --}}
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms
        class="mb-6 flex items-center justify-between p-4 bg-red-50 border border-red-100 rounded-xl shadow-sm text-red-800">
        <div class="flex items-center gap-3">
            <div class="p-1.5 bg-red-100 rounded-full text-red-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
            </div>
            <span class="font-medium text-sm">{{ session('error') }}</span>
        </div>
        <button @click="show = false" class="text-red-400 hover:text-red-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    @endif

    {{-- CARD 1: FORM TAMBAH --}}
    <div class="bg-white rounded-xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6">
                    </path>
                </svg>
                Tambah Hari Aktif
            </h3>
        </div>

        <form action="{{ route('master-hari.store') }}" method="POST" class="p-6">
            @csrf
            <div class="flex flex-col md:flex-row gap-5 items-end">
                {{-- Input Nama Hari --}}
                <div class="w-full md:w-1/2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama
                        Hari</label>
                    <div class="relative">
                        <select name="nama_hari" required
                            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-medium text-slate-700 appearance-none cursor-pointer">
                            <option value="" disabled selected>-- Pilih Hari --</option>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                        <div
                            class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Input Max Jam --}}
                <div class="w-full md:w-1/2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Batas Jam
                        Mengajar</label>
                    <input type="number" name="max_jam" placeholder="Contoh: 10" min="1" required
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-medium text-slate-700 placeholder-slate-400">
                </div>

                {{-- Tombol Simpan --}}
                <div class="w-full md:w-auto">
                    <button type="submit"
                        class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold uppercase tracking-wide rounded-lg shadow-md hover:shadow-lg hover:shadow-indigo-500/30 transition-all duration-200 transform active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4">
                            </path>
                        </svg>
                        Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- CARD 2: TABEL DATA --}}
    <div class="bg-white rounded-xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                Daftar Hari Aktif
            </h3>
            <span class="px-2.5 py-1 rounded-md bg-slate-200 text-slate-600 text-[10px] font-bold">Total:
                {{ $haris->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead
                    class="bg-white text-slate-500 border-b border-slate-100 uppercase font-bold text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Nama Hari</th>
                        <th class="px-6 py-4 text-center">Batas Jam</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($haris as $h)
                    <tr class="hover:bg-indigo-50/30 transition duration-150">
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-700 text-sm">{{ $h->nama_hari }}</span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-50 text-blue-600 border border-blue-100 text-xs font-bold">
                                Maks: {{ $h->max_jam }} Jam
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if($h->is_active)
                            <div
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 shadow-sm">
                                <span class="relative flex h-2 w-2">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                AKTIF
                            </div>
                            @else
                            <span
                                class="text-slate-400 font-semibold text-xs bg-slate-100 px-2 py-1 rounded">Libur</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- FIX: Tombol Edit Pakai $dispatch --}}
                                <button type="button"
                                    @click="$dispatch('buka-modal-edit-hari', { id: '{{ $h->id }}', nama: '{{ $h->nama_hari }}', max: '{{ $h->max_jam }}', active: '{{ $h->is_active ? 1 : 0 }}' })"
                                    class="text-xs font-bold text-amber-600 hover:text-amber-800 hover:bg-amber-50 px-2 py-1.5 rounded transition border border-transparent hover:border-amber-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4L16.5 3.5z">
                                        </path>
                                    </svg>
                                </button>

                                <div class="w-px h-4 bg-slate-200"></div>

                                {{-- Tombol Delete --}}
                                <form action="{{ route('master-hari.destroy', $h->id) }}" method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus konfigurasi hari {{ $h->nama_hari }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="text-xs font-bold text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1.5 rounded transition"
                                        title="Hapus Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-400 font-medium">Belum ada data hari
                            aktif.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- FIX: MODAL EDIT HARI DENGAN SISTEM $dispatch EVENT --}}
    <div x-data="{ openEdit: false, id: '', nama: '', max: '', active: 1 }" @buka-modal-edit-hari.window="
            openEdit = true;
            id = $event.detail.id;
            nama = $event.detail.nama;
            max = $event.detail.max;
            active = $event.detail.active;
         ">

        <div x-show="openEdit" style="display: none;"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
            x-transition.opacity>
            <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 overflow-hidden"
                @click.away="openEdit = false">

                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800">Edit Konfigurasi Hari</h3>
                    <button type="button" @click="openEdit = false"
                        class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form :action="'{{ url('master-hari') }}/' + id" method="POST" class="p-6 space-y-5">
                    @csrf @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Hari
                            (Read Only)</label>
                        <input type="text" name="nama_hari" x-model="nama" readonly
                            class="w-full border border-slate-200 rounded-lg px-4 py-2.5 bg-slate-50 text-slate-400 font-medium outline-none cursor-not-allowed">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Batas Jam
                            Mengajar</label>
                        <input type="number" name="max_jam" x-model="max" required min="1"
                            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm font-medium text-slate-700">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Status
                            Hari</label>
                        <select name="is_active" x-model="active"
                            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm font-medium text-slate-700 appearance-none">
                            <option value="1">Aktif / Masuk</option>
                            <option value="0">Libur</option>
                        </select>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" @click="openEdit = false"
                            class="flex-1 px-4 py-2.5 border border-slate-300 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Batal</button>
                        <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-bold uppercase hover:bg-indigo-700 transition shadow-md">Simpan
                            Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection