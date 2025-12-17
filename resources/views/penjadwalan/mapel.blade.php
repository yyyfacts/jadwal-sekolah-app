@extends('layouts.app')

@section('content')
{{-- DECORATIVE BACKGROUND (MESH GRADIENT - BLUE THEME) --}}
<div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-blue-50 to-slate-50 -z-10 pointer-events-none">
</div>
<div
    class="absolute top-0 right-0 w-72 h-72 bg-blue-300/20 rounded-full blur-3xl -z-10 pointer-events-none mix-blend-multiply animate-pulse-slow">
</div>
<div class="absolute top-20 left-20 w-72 h-72 bg-cyan-300/20 rounded-full blur-3xl -z-10 pointer-events-none mix-blend-multiply animate-pulse-slow"
    style="animation-delay: 1s;"></div>

<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 pb-20 pt-6">

    {{-- ================================================================= --}}
    {{-- 1. HEADER CARD (GLASSMORPHISM) --}}
    {{-- ================================================================= --}}
    <div
        class="relative z-10 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6 p-6 bg-white/60 backdrop-blur-xl rounded-2xl border border-white/50 shadow-[0_8px_30px_rgb(0,0,0,0.04)] group hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-500">

        <div>
            <h1
                class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-slate-800 to-blue-900 tracking-tight">
                Bank Mata Pelajaran
            </h1>
            <p class="text-slate-500 text-sm mt-1 font-medium">
                Manajemen kurikulum dan distribusi pengajar.
            </p>
        </div>

        <div class="flex items-center gap-4">
            {{-- Statistik Badge --}}
            <div
                class="hidden md:flex items-center px-4 py-2 bg-white/80 border border-white/60 rounded-xl shadow-sm backdrop-blur-sm">
                <div class="flex h-2.5 w-2.5 relative mr-3">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                </div>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total Mapel: <span
                        class="font-bold text-slate-800 text-sm ml-1">{{ $mapels->count() }}</span></span>
            </div>

            {{-- Tombol Tambah (Glowing Gradient) --}}
            <button onclick="openModal('modaltambah')"
                class="relative inline-flex items-center justify-center px-6 py-2.5 overflow-hidden font-bold text-white transition-all duration-300 bg-blue-600 rounded-xl group hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50">
                <span
                    class="absolute top-0 right-0 inline-block w-4 h-4 transition-all duration-500 ease-in-out bg-blue-800 rounded group-hover:-mr-4 group-hover:-mt-4">
                    <span
                        class="absolute top-0 right-0 w-5 h-5 rotate-45 translate-x-1/2 -translate-y-1/2 bg-white opacity-10"></span>
                </span>
                <span class="relative flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="text-sm uppercase tracking-wide">Tambah Mapel</span>
                </span>
            </button>
        </div>
    </div>

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition.duration.500ms
        class="mb-6 flex items-center justify-between p-4 bg-emerald-50/90 backdrop-blur-md border border-emerald-100 rounded-2xl shadow-sm text-emerald-800">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-100 rounded-full text-emerald-600 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <span class="font-semibold text-sm">{{ session('success') }}</span>
        </div>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700 transition"><svg class="w-5 h-5"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg></button>
    </div>
    @endif

    {{-- ================================================================= --}}
    {{-- 2. MAIN CONTENT CARD --}}
    {{-- ================================================================= --}}
    <div
        class="relative z-10 bg-white/60 backdrop-blur-xl rounded-3xl border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] overflow-hidden">

        {{-- SEARCH BAR --}}
        {{-- FIX: Z-Index 10 --}}
        <div
            class="p-6 border-b border-slate-200/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/40">
            <div class="relative w-full sm:w-80 group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors duration-300"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="search-mapel-main" onkeyup="searchMainTable()"
                    class="block w-full pl-11 pr-4 py-3 bg-white/50 border border-slate-200 rounded-xl leading-5 placeholder-slate-400 focus:bg-white focus:outline-none focus:placeholder-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-sm transition duration-300 shadow-sm"
                    placeholder="Cari Mapel atau Kode...">
            </div>

            <div class="flex items-center gap-2.5 px-4 py-2 bg-blue-50/50 border border-blue-100/50 rounded-lg">
                <span class="relative flex h-2.5 w-2.5">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                </span>
                <span class="text-[11px] text-blue-900 font-bold uppercase tracking-widest">
                    Tahun Ajaran {{ date('Y') }}/{{ date('Y')+1 }}
                </span>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="overflow-auto max-h-[70vh] custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead class="bg-slate-50/80 backdrop-blur sticky top-0 z-10 border-b border-slate-200">
                    <tr>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center w-16">
                            #</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider w-[35%]">
                            Identitas Mapel</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center w-[25%]">
                            Status Distribusi</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center w-[35%]">
                            Aksi & Distribusi</th>
                    </tr>
                </thead>

                <tbody id="tbody-mapel-main" class="divide-y divide-slate-100">
                    @forelse($mapels as $index => $m)
                    <tr class="group hover:bg-blue-50/30 transition-colors duration-300">

                        {{-- NOMOR --}}
                        <td class="px-6 py-4 text-center">
                            <span
                                class="font-mono text-slate-400 text-sm group-hover:text-blue-500 transition-colors">{{ $index + 1 }}</span>
                        </td>

                        {{-- IDENTITAS MAPEL --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-11 w-11 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 p-0.5 shadow-md shadow-blue-200">
                                    <div
                                        class="w-full h-full rounded-full bg-white/10 backdrop-blur-sm flex items-center justify-center text-white font-bold text-sm border border-white/20">
                                        {{ substr($m->nama_mapel, 0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="font-bold text-slate-800 text-sm group-hover:text-blue-700 transition-colors">
                                        {{ $m->nama_mapel }}</div>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200 font-mono">
                                        {{ $m->kode_mapel }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- STATUS DISTRIBUSI --}}
                        <td class="px-6 py-4 text-center align-middle">
                            @if($m->total_jam_terdistribusi > 0)
                            <div
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 shadow-sm">
                                <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                                <span class="text-xs font-bold text-blue-700">{{ $m->total_jam_terdistribusi }} Jam
                                    Total</span>
                            </div>
                            @else
                            <div
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-50 border border-slate-200 border-dashed opacity-70">
                                <span class="text-xs font-medium text-slate-400">Belum ada kelas</span>
                            </div>
                            @endif
                        </td>

                        {{-- AKSI & DISTRIBUSI --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Tombol Distribusi --}}
                                <button onclick="openModal('modaljadwal{{ $m->id }}')"
                                    class="flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-blue-600 text-white text-xs font-bold rounded-lg shadow-md hover:shadow-blue-500/30 transition-all duration-300 transform hover:-translate-y-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                        </path>
                                    </svg>
                                    Distribusi
                                </button>

                                <div class="w-px h-6 bg-slate-200 mx-2"></div>

                                {{-- Blok Jam --}}
                                <a href="{{ route('mapel.waktuKosong', $m->id) }}"
                                    class="p-2 text-rose-500 bg-rose-50 hover:bg-rose-100 border border-rose-100 rounded-lg transition-all duration-200 hover:scale-110"
                                    title="Blok Jam">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                                        </path>
                                    </svg>
                                </a>

                                {{-- Edit --}}
                                <button onclick="openModal('edit{{ $m->id }}')"
                                    class="p-2 text-amber-500 bg-amber-50 hover:bg-amber-100 border border-amber-100 rounded-lg transition-all duration-200 hover:scale-110"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>

                                {{-- Hapus --}}
                                <form action="{{ route('mapel.destroy', $m->id) }}" method="POST"
                                    onsubmit="return confirm('Hapus permanen mapel {{ $m->nama_mapel }}?')"
                                    class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-2 text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg transition-all duration-200 hover:scale-110"
                                        title="Hapus">
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
                        <td colspan="4" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center justify-center opacity-50">
                                <div class="bg-slate-100 p-4 rounded-full mb-3">
                                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                        </path>
                                    </svg>
                                </div>
                                <span class="text-base font-medium text-slate-600">Belum ada mata pelajaran.</span>
                                <p class="text-xs text-slate-400 mt-1">Mulai dengan menambahkan data baru.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div
            class="bg-slate-50/50 border-t border-slate-200 px-6 py-4 flex justify-between items-center backdrop-blur-sm">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bank Mata Pelajaran</span>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sistem Penjadwalan v2.0</span>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- MODALS AREA --}}
    {{-- ========================================================= --}}

    {{-- 1. Modal Tambah Mapel --}}
    <div id="modaltambah"
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[99] hidden flex items-center justify-center p-4 transition-opacity duration-300">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-md border border-white/20 overflow-hidden transform transition-all scale-100">
            <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <h3 class="font-bold text-lg text-slate-800">Tambah Mapel Baru</h3>
                <button onclick="closeModal('modaltambah')"
                    class="text-slate-400 hover:text-slate-600 transition text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('mapel.store') }}" method="POST" class="p-6 space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Mata
                        Pelajaran</label>
                    <input type="text" name="nama_mapel"
                        class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:bg-white focus:border-blue-500 outline-none transition text-sm"
                        placeholder="Contoh: Matematika Wajib" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Kode Mapel
                        (Singkatan)</label>
                    <input type="text" name="kode_mapel"
                        class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:bg-white focus:border-blue-500 outline-none uppercase font-mono transition text-sm"
                        placeholder="MTK-W" required>
                </div>
                <button type="submit"
                    class="w-full bg-gradient-to-r from-slate-800 to-slate-900 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-slate-300/50 text-sm tracking-wide">SIMPAN
                    DATA</button>
            </form>
        </div>
    </div>

    @foreach($mapels as $m)
    {{-- 2. Modal Edit Mapel --}}
    <div id="edit{{ $m->id }}"
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[99] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm border border-white/20 overflow-hidden">
            <div class="px-6 py-5 border-b border-amber-100 flex justify-between items-center bg-amber-50/50">
                <h3 class="font-bold text-amber-800">Edit Data Mapel</h3>
                <button onclick="closeModal('edit{{ $m->id }}')"
                    class="text-amber-400 hover:text-amber-600 text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('mapel.update', $m->id) }}" method="POST" class="p-6 space-y-5">
                @csrf @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama
                        Mapel</label>
                    <input type="text" name="nama_mapel" value="{{ $m->nama_mapel }}"
                        class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:bg-white outline-none text-sm"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Kode</label>
                    <input type="text" name="kode_mapel" value="{{ $m->kode_mapel }}"
                        class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:bg-white outline-none uppercase font-mono text-sm"
                        required>
                </div>
                <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-amber-200 transition text-sm tracking-wide">UPDATE
                    PERUBAHAN</button>
            </form>
        </div>
    </div>

    {{-- 3. Modal Atur Distribusi --}}
    <div id="modaljadwal{{ $m->id }}"
        class="fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[99] hidden flex items-center justify-center p-4">
        <div
            class="bg-white rounded-3xl shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col border border-slate-200 overflow-hidden">
            {{-- Header Modal --}}
            <div
                class="px-8 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/80 backdrop-blur-sm">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-600 text-white rounded-2xl shadow-lg shadow-blue-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Distribusi Pengajar</h3>
                        <p class="text-xs font-medium text-slate-500 mt-0.5">Mapel: <span
                                class="text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md border border-blue-100">{{ $m->nama_mapel }}</span>
                        </p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modaljadwal{{ $m->id }}')"
                    class="p-2 rounded-full hover:bg-slate-200 text-slate-400 hover:text-rose-500 transition"><svg
                        class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg></button>
            </div>

            {{-- Body Modal --}}
            <div class="flex flex-col lg:flex-row h-full overflow-hidden">
                {{-- Kiri: Tabel Distribusi --}}
                <div class="flex-1 overflow-y-auto p-8 border-r border-slate-100 custom-scrollbar bg-white relative">
                    <div
                        class="flex justify-between items-center mb-6 sticky top-0 bg-white z-20 pb-2 border-b border-slate-50">
                        <h4 class="font-bold text-slate-700 text-sm uppercase tracking-widest">Kelas Terdaftar</h4>
                        <div class="relative w-56">
                            <input type="text" id="search-{{ $m->id }}" onkeyup="searchTable({{ $m->id }})"
                                placeholder="Cari..."
                                class="w-full pl-9 pr-3 py-2 text-xs border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 focus:bg-white transition">
                            <span class="absolute left-3 top-2.5 text-slate-400 text-[10px]">🔍</span>
                        </div>
                    </div>

                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider sticky top-8 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left rounded-l-lg">Kelas</th>
                                <th class="px-4 py-3 text-left">Guru Pengampu</th>
                                <th class="px-4 py-3 text-center">Jam</th>
                                <th class="px-4 py-3 text-right rounded-r-lg">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-mapel-{{ $m->id }}" class="divide-y divide-slate-50">
                            @foreach($m->jadwals as $jadwal)
                            <tr id="row-jadwal-{{ $jadwal->id }}"
                                class="group hover:bg-blue-50/50 transition rounded-lg">
                                <td class="px-4 py-3 font-bold text-slate-700 kelas-text align-middle">
                                    {{ $jadwal->kelas->nama_kelas ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-slate-600 guru-text align-middle font-medium">
                                    {{ $jadwal->guru->nama_guru ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center align-middle">
                                    <div class="flex flex-col items-center">
                                        <span
                                            class="bg-white border border-slate-200 text-blue-600 py-0.5 px-2 rounded-full font-bold text-[10px] jam-text shadow-sm">{{ $jadwal->jumlah_jam }}
                                            Jam</span>
                                        <span
                                            class="text-[9px] text-slate-400 uppercase mt-1 font-bold tracking-wider tipe-text">{{ $jadwal->tipe_jam }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right align-middle">
                                    <div
                                        class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button type="button"
                                            onclick="editJadwalInline({{ $m->id }}, {{ $jadwal->id }}, {{ $jadwal->kelas_id }}, {{ $jadwal->guru_id }}, {{ $jadwal->jumlah_jam }}, '{{ $jadwal->tipe_jam }}')"
                                            class="p-1.5 bg-white border border-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg shadow-sm transition">✏️</button>
                                        <button type="button" onclick="hapusJadwal({{ $jadwal->id }}, this)"
                                            class="p-1.5 bg-white border border-red-100 text-red-600 hover:bg-red-600 hover:text-white rounded-lg shadow-sm transition">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            @if($m->jadwals->isEmpty())
                            <tr id="empty-mapel-{{ $m->id }}">
                                <td colspan="4"
                                    class="text-center py-12 text-slate-400 italic align-middle bg-slate-50/50 rounded-lg border-2 border-dashed border-slate-100 m-2">
                                    Belum ada kelas yang didistribusikan.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Kanan: Form --}}
                <div
                    class="w-full lg:w-[420px] bg-slate-50/80 p-8 shadow-[inset_10px_0_20px_-10px_rgba(0,0,0,0.02)] overflow-y-auto border-l border-slate-200/60 backdrop-blur-sm">
                    <div id="form-container-{{ $m->id }}"
                        class="bg-white p-6 rounded-2xl border border-white shadow-[0_4px_20px_rgba(0,0,0,0.03)] transition-all duration-300">
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-slate-100">
                            <h4 id="form-title-{{ $m->id }}"
                                class="font-bold text-slate-800 flex items-center gap-3 text-sm uppercase tracking-wider">
                                <span
                                    class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm font-bold shadow-lg shadow-blue-200">1</span>
                                Input Distribusi
                            </h4>
                            <button id="btn-batal-{{ $m->id }}" type="button" onclick="resetFormJadwal({{ $m->id }})"
                                class="hidden text-[10px] text-rose-500 font-bold hover:bg-rose-50 px-3 py-1.5 rounded-full transition uppercase tracking-wide border border-rose-100">Batal
                                Edit</button>
                        </div>

                        <form id="form-jadwal-{{ $m->id }}" action="{{ route('mapel.simpanJadwal', $m->id) }}"
                            method="POST" onsubmit="handleFormJadwal(event, this, {{ $m->id }})">
                            <div id="method-spoof-{{ $m->id }}"></div>
                            <div class="space-y-5">
                                {{-- Dropdown Kelas --}}
                                <div class="relative custom-select-wrapper" id="wrapper-kelas-{{ $m->id }}">
                                    <label
                                        class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-widest">Kelas
                                        Tujuan</label>
                                    <input type="hidden" name="kelas_id" id="real-input-kelas-{{ $m->id }}" required>
                                    <button type="button" onclick="toggleCustomDropdown('kelas', {{ $m->id }})"
                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-left flex justify-between items-center hover:border-blue-400 hover:bg-white focus:ring-2 focus:ring-blue-500/10 transition-all duration-200 text-sm font-medium shadow-sm">
                                        <span id="display-kelas-{{ $m->id }}" class="text-slate-500">Pilih
                                            Kelas...</span><span class="text-slate-400">▼</span>
                                    </button>
                                    <div id="dropdown-kelas-{{ $m->id }}"
                                        class="hidden absolute z-50 w-full bg-white border border-slate-200 rounded-xl shadow-2xl mt-2 max-h-56 overflow-y-auto p-2 animate-scale-in">
                                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100 mb-1">
                                            <input type="text" placeholder="Cari..."
                                                onkeyup="filterCustomDropdown('kelas', {{ $m->id }}, this)"
                                                class="w-full p-2 text-xs border border-slate-200 rounded-lg outline-none focus:border-blue-500 bg-slate-50">
                                        </div>
                                        <div id="list-kelas-{{ $m->id }}">
                                            @foreach($kelases as $k)
                                            <div class="option-item p-3 hover:bg-blue-50 cursor-pointer text-sm text-slate-700 rounded-lg transition-colors mb-1"
                                                data-value="{{ $k->id }}" data-label="{{ $k->nama_kelas }}"
                                                onclick="selectCustomOption('kelas', {{ $m->id }}, '{{ $k->id }}', '{{ $k->nama_kelas }}')">
                                                <div class="font-bold">{{ $k->nama_kelas }}</div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Dropdown Guru --}}
                                <div class="relative custom-select-wrapper" id="wrapper-guru-{{ $m->id }}">
                                    <label
                                        class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-widest">Guru
                                        Pengampu</label>
                                    <input type="hidden" name="guru_id" id="real-input-guru-{{ $m->id }}" required>
                                    <button type="button" onclick="toggleCustomDropdown('guru', {{ $m->id }})"
                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-left flex justify-between items-center hover:border-blue-400 hover:bg-white focus:ring-2 focus:ring-blue-500/10 transition-all duration-200 text-sm font-medium shadow-sm">
                                        <span id="display-guru-{{ $m->id }}" class="text-slate-500">Pilih
                                            Guru...</span><span class="text-slate-400">▼</span>
                                    </button>
                                    <div id="dropdown-guru-{{ $m->id }}"
                                        class="hidden absolute z-50 w-full bg-white border border-slate-200 rounded-xl shadow-2xl mt-2 max-h-56 overflow-y-auto p-2 animate-scale-in">
                                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100 mb-1">
                                            <input type="text" placeholder="Cari..."
                                                onkeyup="filterCustomDropdown('guru', {{ $m->id }}, this)"
                                                class="w-full p-2 text-xs border border-slate-200 rounded-lg outline-none focus:border-blue-500 bg-slate-50">
                                        </div>
                                        <div id="list-guru-{{ $m->id }}">
                                            @foreach($gurus as $g)
                                            <div class="option-item p-3 hover:bg-blue-50 cursor-pointer text-sm text-slate-700 rounded-lg transition-colors mb-1"
                                                data-value="{{ $g->id }}" data-label="{{ $g->nama_guru }}"
                                                onclick="selectCustomOption('guru', {{ $m->id }}, '{{ $g->id }}', '{{ $g->nama_guru }}')">
                                                <div class="font-bold">{{ $g->nama_guru }}</div>
                                                <div class="text-[10px] text-slate-400 font-mono">{{ $g->kode_guru }}
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-widest">Total
                                            Jam</label>
                                        <div class="relative">
                                            <input type="number" name="jumlah_jam" id="input-jam-{{ $m->id }}"
                                                class="w-full pl-4 pr-8 py-3 bg-slate-50 border border-slate-200 rounded-xl text-center font-bold text-lg focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition shadow-inner"
                                                min="1" max="10" required>
                                            <span
                                                class="absolute right-3 top-4 text-[10px] text-slate-400 font-bold">JP</span>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center justify-center text-[10px] text-slate-500 leading-tight bg-blue-50/50 p-3 rounded-xl border border-blue-100 text-center italic">
                                        "Masukkan total JP mingguan untuk mapel ini."
                                    </div>
                                </div>

                                <div>
                                    <label
                                        class="text-[10px] font-bold text-slate-400 uppercase mb-2 block tracking-widest">Tipe
                                        Blok</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach(['single' => '1', 'double' => '2', 'triple' => '3'] as $key => $val)
                                        <label class="cursor-pointer group">
                                            <input type="radio" name="tipe_jam" value="{{ $key }}" class="peer sr-only"
                                                id="radio-{{ $key }}-{{ $m->id }}"
                                                {{ $key == 'single' ? 'checked' : '' }}>
                                            <div
                                                class="p-2 rounded-xl border border-slate-200 bg-slate-50 text-center hover:border-blue-300 peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-checked:text-white transition-all duration-300 peer-checked:shadow-lg peer-checked:shadow-blue-500/30">
                                                <div
                                                    class="text-sm font-bold mb-0.5 group-hover:scale-110 transition-transform">
                                                    {{ $key == 'single' ? '☝️' : ($key == 'double' ? '✌️' : '🤟') }}
                                                </div>
                                                <div class="text-[9px] font-bold uppercase tracking-wide opacity-80">
                                                    {{ ucfirst($key) }}</div>
                                            </div>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>

                                <button type="submit" id="btn-submit-{{ $m->id }}"
                                    class="w-full py-4 bg-slate-900 hover:bg-blue-600 text-white rounded-xl font-bold shadow-lg hover:shadow-blue-500/40 transform active:scale-95 transition-all duration-300 flex items-center justify-center gap-2 mt-4 text-xs tracking-widest uppercase">
                                    <span>💾</span> Simpan Distribusi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
// ==========================================================
// SCRIPT KHUSUS MAPEL (SAMA DENGAN GURU/KELAS)
// ==========================================================
const csrfNode = document.querySelector('meta[name="csrf-token"]');
const CSRF_TOKEN = csrfNode ? csrfNode.content : '';

function openModal(modalID) {
    const modal = document.getElementById(modalID);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        const firstInput = modal.querySelector('input');
        if (firstInput) firstInput.focus();
    }
}

function closeModal(modalID) {
    const modal = document.getElementById(modalID);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (modalID.includes('modaljadwal')) {
            const id = modalID.replace('modaljadwal', '');
            resetFormJadwal(id);
        }
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.add('hidden');
        event.target.classList.remove('flex');
    }
    if (!event.target.closest('.custom-select-wrapper')) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.custom-select-wrapper').forEach(el => el.style.zIndex = "0");
    }
}

function searchMainTable() {
    const input = document.getElementById('search-mapel-main');
    const filter = input.value.toLowerCase();
    const tbody = document.getElementById('tbody-mapel-main');
    const rows = tbody.getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        if (rows[i].innerText.includes('Belum ada mata pelajaran')) continue;
        const colProfile = rows[i].getElementsByTagName('td')[1];

        if (colProfile) {
            const textValue = colProfile.textContent || colProfile.innerText;
            if (textValue.toLowerCase().indexOf(filter) > -1) {
                rows[i].style.display = "";
            } else {
                rows[i].style.display = "none";
            }
        }
    }
}

function searchTable(mapelId) {
    const filter = document.getElementById('search-' + mapelId).value.toLowerCase();
    const rows = document.getElementById('tbody-mapel-' + mapelId).getElementsByTagName('tr');
    for (let row of rows) {
        if (row.id.includes('empty')) continue;
        const txt = row.innerText.toLowerCase();
        row.style.display = txt.includes(filter) ? "" : "none";
    }
}

function toggleCustomDropdown(type, mapelId) {
    const wrapper = document.getElementById(`wrapper-${type}-${mapelId}`);
    const dropdown = document.getElementById(`dropdown-${type}-${mapelId}`);
    document.querySelectorAll('.custom-select-wrapper').forEach(el => {
        if (el !== wrapper) {
            el.style.zIndex = "0";
            el.querySelector('[id^="dropdown-"]')?.classList.add('hidden');
        }
    });
    if (dropdown.classList.contains('hidden')) {
        dropdown.classList.remove('hidden');
        wrapper.style.zIndex = "50";
        dropdown.querySelector('input').focus();
    } else {
        dropdown.classList.add('hidden');
        wrapper.style.zIndex = "0";
    }
}

function filterCustomDropdown(type, mapelId, input) {
    const filter = input.value.toLowerCase();
    const items = document.getElementById(`list-${type}-${mapelId}`).children;
    for (let item of items) {
        const label = item.getAttribute('data-label') || '';
        item.style.display = label.toLowerCase().includes(filter) ? "" : "none";
    }
}

function selectCustomOption(type, mapelId, value, label) {
    document.getElementById(`real-input-${type}-${mapelId}`).value = value;
    const display = document.getElementById(`display-${type}-${mapelId}`);
    display.innerText = label;
    display.classList.remove('text-slate-500');
    display.classList.add('text-slate-800');
    document.getElementById(`dropdown-${type}-${mapelId}`).classList.add('hidden');
    document.getElementById(`wrapper-${type}-${mapelId}`).style.zIndex = "0";
}

function setCustomDropdownValue(type, mapelId, value) {
    const list = document.getElementById(`list-${type}-${mapelId}`);
    if (!list) return;
    const option = list.querySelector(`.option-item[data-value="${value}"]`);
    if (option) selectCustomOption(type, mapelId, value, option.getAttribute('data-label'));
}

function resetCustomDropdown(type, mapelId) {
    const input = document.getElementById(`real-input-${type}-${mapelId}`);
    if (input) input.value = '';
    const display = document.getElementById(`display-${type}-${mapelId}`);
    if (display) {
        display.innerText = type === 'kelas' ? 'Pilih Kelas...' : 'Pilih Guru...';
        display.classList.add('text-slate-500');
        display.classList.remove('text-slate-800');
    }
}

function editJadwalInline(mapelId, jadwalId, kelasId, guruId, jam, tipe) {
    const container = document.getElementById(`form-container-${mapelId}`);
    const title = document.getElementById(`form-title-${mapelId}`);
    const form = document.getElementById(`form-jadwal-${mapelId}`);
    const btnSubmit = document.getElementById(`btn-submit-${mapelId}`);

    container.classList.remove('border-slate-200');
    container.classList.add('border-amber-400', 'ring-2', 'ring-amber-100');
    title.innerHTML = `<span class="text-amber-600">✏️</span> <span class="text-amber-700">EDIT MODE</span>`;
    document.getElementById(`btn-batal-${mapelId}`).classList.remove('hidden');

    btnSubmit.className =
        "w-full py-4 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold shadow-lg transition-all flex items-center justify-center gap-2 mt-4 tracking-widest uppercase";
    btnSubmit.innerHTML = "UPDATE DISTRIBUSI";

    setCustomDropdownValue('kelas', mapelId, kelasId);
    setCustomDropdownValue('guru', mapelId, guruId);
    document.getElementById(`input-jam-${mapelId}`).value = jam;

    const radioBtn = document.querySelector(`input[name="tipe_jam"][value="${tipe}"][id^="radio-${tipe}-${mapelId}"]`);
    if (radioBtn) radioBtn.checked = true;

    form.action = `/mapel/jadwal/${jadwalId}`;
    form.dataset.mode = 'edit';
    document.getElementById(`method-spoof-${mapelId}`).innerHTML = `<input type="hidden" name="_method" value="PUT">`;

    container.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });
}

function resetFormJadwal(mapelId) {
    const container = document.getElementById(`form-container-${mapelId}`);
    const form = document.getElementById(`form-jadwal-${mapelId}`);
    if (!container || !form) return;

    container.classList.remove('border-amber-400', 'ring-2', 'ring-amber-100');
    container.classList.add('border-slate-200');
    document.getElementById(`form-title-${mapelId}`).innerHTML =
        `<span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm font-bold shadow-md shadow-blue-200">1</span> Input Distribusi`;
    document.getElementById(`btn-batal-${mapelId}`).classList.add('hidden');

    const btnSubmit = document.getElementById(`btn-submit-${mapelId}`);
    btnSubmit.className =
        "w-full py-4 bg-slate-900 hover:bg-blue-600 text-white rounded-xl font-bold shadow-lg hover:shadow-blue-500/30 transform active:scale-95 transition-all duration-300 flex items-center justify-center gap-2 mt-4 tracking-widest uppercase";
    btnSubmit.innerHTML = "<span>💾</span> Simpan Distribusi";

    form.reset();
    resetCustomDropdown('kelas', mapelId);
    resetCustomDropdown('guru', mapelId);
    const radioDefault = document.querySelector(
        `input[name="tipe_jam"][value="single"][id^="radio-single-${mapelId}"]`);
    if (radioDefault) radioDefault.checked = true;

    form.action = `/mapel/${mapelId}/jadwal`;
    delete form.dataset.mode;
    document.getElementById(`method-spoof-${mapelId}`).innerHTML = '';
}

async function handleFormJadwal(e, form, mapelId) {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const oldText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = "Processing...";

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: new FormData(form)
        });
        const json = await res.json();
        if (res.ok && json.success) {
            updateTableUI(mapelId, json.jadwal, form.dataset.mode === 'edit');
            resetFormJadwal(mapelId);
        } else {
            alert(json.message || "Gagal menyimpan.");
        }
    } catch (err) {
        alert("System Error");
        console.error(err);
    } finally {
        btn.disabled = false;
        btn.innerHTML = oldText;
    }
}

function updateTableUI(mapelId, jadwal, isEdit) {
    const tbody = document.getElementById(`tbody-mapel-${mapelId}`);
    document.getElementById(`empty-mapel-${mapelId}`)?.remove();

    const namaKelas = jadwal.kelas?.nama_kelas || '-';
    const namaGuru = jadwal.guru?.nama_guru || '-';

    if (isEdit) {
        const row = document.getElementById(`row-jadwal-${jadwal.id}`);
        if (row) {
            row.querySelector('.kelas-text').innerText = namaKelas;
            row.querySelector('.guru-text').innerText = namaGuru;
            row.querySelector('.jam-text').innerText = jadwal.jumlah_jam + ' Jam';
            row.querySelector('.tipe-text').innerText = jadwal.tipe_jam;
            const btnEdit = row.querySelector('button[onclick^="editJadwalInline"]');
            btnEdit.setAttribute('onclick',
                `editJadwalInline(${mapelId}, ${jadwal.id}, ${jadwal.kelas_id}, ${jadwal.guru_id}, ${jadwal.jumlah_jam}, '${jadwal.tipe_jam}')`
            );
            row.classList.add('bg-amber-50');
            setTimeout(() => row.classList.remove('bg-amber-50'), 1500);
        }
    } else {
        const tr = document.createElement('tr');
        tr.id = `row-jadwal-${jadwal.id}`;
        tr.className = "group hover:bg-blue-50/50 transition rounded-lg";
        tr.innerHTML =
            `
                <td class="px-4 py-3 font-bold text-slate-700 kelas-text align-middle">${namaKelas}</td>
                <td class="px-4 py-3 text-slate-600 guru-text align-middle font-medium">${namaGuru}</td>
                <td class="px-4 py-3 text-center align-middle"><div class="flex flex-col items-center"><span class="bg-white border border-slate-200 text-blue-600 py-0.5 px-2 rounded-full font-bold text-[10px] jam-text shadow-sm">${jadwal.jumlah_jam} Jam</span><span class="text-[9px] text-slate-400 uppercase mt-1 font-bold tracking-wider tipe-text">${jadwal.tipe_jam}</span></div></td>
                <td class="px-4 py-3 text-right align-middle"><div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity"><button type="button" onclick="editJadwalInline(${mapelId}, ${jadwal.id}, ${jadwal.kelas_id}, ${jadwal.guru_id}, ${jadwal.jumlah_jam}, '${jadwal.tipe_jam}')" class="p-1.5 bg-white border border-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg shadow-sm transition">✏️</button><button type="button" onclick="hapusJadwal(${jadwal.id}, this)" class="p-1.5 bg-white border border-red-100 text-red-600 hover:bg-red-600 hover:text-white rounded-lg shadow-sm transition">🗑️</button></div></td>`;
        tbody.appendChild(tr);
        setTimeout(() => tr.classList.remove('bg-emerald-50'), 1500);
    }
}

async function hapusJadwal(id, btn) {
    if (!confirm("Hapus distribusi ini?")) return;
    try {
        const res = await fetch(`/mapel/jadwal/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                _method: 'DELETE'
            })
        });
        if (res.ok) {
            btn.closest('tr').remove();
        } else {
            alert('Gagal menghapus');
        }
    } catch (e) {
        alert("Error koneksi.");
    }
}
</script>
@endpush