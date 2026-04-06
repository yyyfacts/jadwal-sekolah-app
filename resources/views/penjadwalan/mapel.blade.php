@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-blue-50/50 to-white"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-blue-300/10 rounded-full blur-3xl opacity-70"></div>
    <div class="absolute top-20 left-10 w-72 h-72 bg-cyan-300/10 rounded-full blur-3xl opacity-70"></div>
</div>

{{-- CONTAINER UTAMA --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-[calc(100vh-6rem)] pb-4 pt-6 flex flex-col">

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800 shrink-0">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-100 rounded-full text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <span class="font-semibold text-sm">{{ session('success') }}</span>
        </div>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700">&times;</button>
    </div>
    @endif

    {{-- UNIFIED CARD (Sesuai Gambar Referensi) --}}
    <div
        class="bg-white rounded-[2rem] border border-slate-100 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.08)] flex flex-col flex-1 overflow-hidden">

        {{-- 1. HEADER SECTION --}}
        <div class="px-8 pt-8 pb-6 bg-white shrink-0 z-20">

            {{-- Baris 1: Judul & Tombol Tambah --}}
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                <div class="flex gap-3 items-start">
                    <div class="w-2.5 h-8 bg-blue-600 rounded-full mt-0.5"></div>
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">
                            Bank Mata Pelajaran
                        </h1>
                        <p class="text-slate-500 text-sm mt-1 font-medium">
                            Manajemen kurikulum dan distribusi pengajar.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div
                        class="hidden md:flex items-center px-5 py-2.5 bg-white border border-slate-200 rounded-full shadow-sm">
                        <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">
                            Total: <span class="text-blue-600 text-sm ml-1 font-extrabold">{{ $mapels->count() }}</span>
                        </span>
                    </div>

                    <button onclick="openModal('modaltambah')"
                        class="px-6 py-2.5 font-bold text-white transition-all duration-300 bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-500/20 hover:shadow-blue-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        <span class="text-sm uppercase tracking-wide">Tambah</span>
                    </button>
                </div>
            </div>

            {{-- Baris 2: Search Bar --}}
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="search-mapel-main" oninput="searchMainTable()"
                    class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl leading-5 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm transition shadow-sm"
                    placeholder="Cari Nama Mapel atau Kode...">
            </div>
        </div>

        {{-- 2. TABLE SECTION (SCROLLABLE AREA) --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white px-2">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-white sticky top-0 z-10">
                    <tr>
                        <th
                            class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-left w-16 border-b-2 border-slate-100">
                            No</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider w-[35%] border-b-2 border-slate-100">
                            Identitas Mapel</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider w-[30%] border-b-2 border-slate-100">
                            Status Distribusi</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right w-[25%] pr-12 border-b-2 border-slate-100">
                            Aksi</th>
                    </tr>
                </thead>

                <tbody id="tbody-mapel-main" class="divide-y divide-slate-100/80">
                    @php
                    // Tema warna dinamis agar persis seperti gambar
                    $themes = [
                    ['avatar' => 'bg-teal-100', 'text' => 'text-teal-600', 'btn' => 'bg-[#459a96] hover:bg-teal-700',
                    'chart' => 'text-[#459a96]'],
                    ['avatar' => 'bg-purple-100', 'text' => 'text-purple-600', 'btn' => 'bg-[#9858a3]
                    hover:bg-purple-700', 'chart' => 'text-[#9858a3]'],
                    ['avatar' => 'bg-slate-200', 'text' => 'text-slate-600', 'btn' => 'bg-[#7a818c] hover:bg-slate-600',
                    'chart' => 'text-[#7a818c]'],
                    ['avatar' => 'bg-blue-100', 'text' => 'text-blue-800', 'btn' => 'bg-[#294269] hover:bg-blue-900',
                    'chart' => 'text-[#294269]'],
                    ];
                    @endphp

                    @forelse($mapels as $index => $m)
                    @php
                    $theme = $themes[$index % 4];
                    // Kalkulasi visual progress (contoh visualisasi donat)
                    $percentage = $m->total_jam_terdistribusi > 0 ? min(100, 60 + ($index * 10)) : 0;
                    @endphp
                    <tr class="group hover:bg-slate-50/50 transition-colors duration-200"
                        data-filter="{{ strtolower($m->nama_mapel) }} {{ strtolower($m->kode_mapel) }}">

                        {{-- NOMOR / HARI --}}
                        <td class="px-8 py-5 text-left">
                            <span class="font-medium text-slate-500 text-sm">{{ $index + 1 }}</span>
                        </td>

                        {{-- IDENTITAS --}}
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-12 w-12 shrink-0 rounded-full {{ $theme['avatar'] }} {{ $theme['text'] }} flex items-center justify-center font-bold text-lg border-2 border-white shadow-sm">
                                    {{ substr($m->nama_mapel, 0, 1) }}
                                </div>
                                <div>
                                    <div
                                        class="font-bold text-slate-800 text-[15px] group-hover:text-blue-600 transition-colors">
                                        {{ $m->nama_mapel }}
                                    </div>
                                    <div
                                        class="inline-block px-2 py-0.5 mt-1 rounded-md bg-slate-100 text-slate-500 font-bold text-[10px] tracking-wide uppercase">
                                        {{ substr($m->kode_mapel, 0, 3) }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- STATUS DISTRIBUSI (Donut Chart Icon) --}}
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <svg class="w-8 h-8 {{ $theme['chart'] }} transform -rotate-90" viewBox="0 0 36 36">
                                    <path class="text-slate-200" stroke-width="4.5" stroke="currentColor" fill="none"
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    @if($m->total_jam_terdistribusi > 0)
                                    <path class="currentColor" stroke-dasharray="{{ $percentage }}, 100"
                                        stroke-width="4.5" stroke-linecap="round" stroke="currentColor" fill="none"
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    @endif
                                </svg>
                                <span class="font-bold text-sm text-slate-800">
                                    {{ $m->total_jam_terdistribusi > 0 ? $m->total_jam_terdistribusi . ' JP Total' : '0 JP Total' }}
                                </span>
                            </div>
                        </td>

                        {{-- AKSI --}}
                        <td class="px-6 py-5 text-right pr-12">
                            <div class="flex items-center justify-end gap-2.5">
                                {{-- Tombol Distribusi Berwarna Sesuai Tema --}}
                                <button onclick="openModal('modaljadwal{{ $m->id }}')"
                                    class="flex items-center gap-2 px-4 py-2 {{ $theme['btn'] }} text-white text-xs font-bold rounded-lg shadow-sm transition-all hover:-translate-y-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                        </path>
                                    </svg>
                                    Distribusi
                                </button>

                                {{-- Tombol Edit (Outline) --}}
                                <button onclick="openModal('edit{{ $m->id }}')"
                                    class="p-2 text-slate-400 bg-white border-2 border-slate-200 hover:border-slate-400 hover:text-slate-600 rounded-lg transition"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>

                                {{-- Tombol Hapus (Outline) --}}
                                <form action="{{ route('mapel.destroy', $m->id) }}" method="POST"
                                    onsubmit="return confirm('Hapus permanen mapel ini?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-2 text-slate-400 bg-white border-2 border-slate-200 hover:border-red-400 hover:text-red-500 rounded-lg transition"
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
                    <tr id="no-data-row">
                        <td colspan="4" class="px-6 py-20 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center opacity-50">
                                <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                                <span class="text-sm font-medium">Belum ada mata pelajaran.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse

                    {{-- Row Not Found --}}
                    <tr id="search-no-result" class="hidden">
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <p>Mapel tidak ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- 3. FOOTER SECTION CARD --}}
        <div class="bg-white border-t border-slate-100 px-8 py-4 flex justify-between items-center shrink-0">
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Sistem Penjadwalan</span>
            <span class="text-[11px] font-bold text-emerald-500 flex items-center gap-1.5 uppercase tracking-wider">
                <svg class="w-4 h-4 bg-emerald-500 text-white rounded-full p-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
                Secure Data
            </span>
        </div>
    </div>

    {{-- COPYRIGHT TEXT DI LUAR CARD --}}
    <div class="text-center mt-6 text-slate-500 text-[11px] font-medium tracking-wide">
        &copy; 2026 SMAN 1 SAMPANG. Sistem Penjadwalan Terintegrasi.
    </div>

    {{-- ========================================================= --}}
    {{-- MODALS AREA (Dibiarkan tetap sama sesuai logika Anda) --}}
    {{-- ========================================================= --}}

    {{-- 1. Modal Tambah --}}
    <div id="modaltambah"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99] hidden flex items-center justify-center p-4">
        <div
            class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-scale-in border border-white/20">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <span class="w-1.5 h-5 bg-blue-600 rounded-full"></span>
                    Tambah Mapel
                </h3>
                <button onclick="closeModal('modaltambah')"
                    class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('mapel.store') }}" method="POST" class="p-6 space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Mapel</label>
                    <input type="text" name="nama_mapel"
                        class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none text-sm transition"
                        placeholder="Contoh: Matematika" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Kode Mapel</label>
                    <input type="text" name="kode_mapel"
                        class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none font-mono uppercase text-sm transition"
                        placeholder="MTK" required>
                </div>
                <button type="submit"
                    class="w-full bg-slate-900 hover:bg-blue-600 text-white font-bold py-3.5 rounded-xl shadow-lg transition duration-300 uppercase tracking-wider text-xs">SIMPAN
                    DATA</button>
            </form>
        </div>
    </div>

    @foreach($mapels as $m)
    {{-- 2. Modal Edit --}}
    <div id="edit{{ $m->id }}"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden border border-white/20">
            <div class="px-6 py-4 border-b border-amber-100 bg-amber-50 flex justify-between items-center">
                <h3 class="font-bold text-amber-800 flex items-center gap-2">
                    <span class="w-1.5 h-5 bg-amber-500 rounded-full"></span>
                    Edit Mapel
                </h3>
                <button onclick="closeModal('edit{{ $m->id }}')"
                    class="text-amber-400 hover:text-amber-600 text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('mapel.update', $m->id) }}" method="POST" class="p-6 space-y-5">
                @csrf @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Mapel</label>
                    <input type="text" name="nama_mapel" value="{{ $m->nama_mapel }}"
                        class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 outline-none text-sm transition"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Kode</label>
                    <input type="text" name="kode_mapel" value="{{ $m->kode_mapel }}"
                        class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 outline-none font-mono uppercase text-sm transition"
                        required>
                </div>
                <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3.5 rounded-xl shadow-lg transition duration-300 uppercase tracking-wider text-xs">UPDATE</button>
            </form>
        </div>
    </div>

    {{-- 3. Modal Distribusi --}}
    <div id="modaljadwal{{ $m->id }}"
        class="fixed inset-0 bg-slate-900/80 z-[99] hidden flex items-center justify-center p-2 sm:p-4 transition-opacity duration-300">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl h-[90vh] flex flex-col border border-slate-200 overflow-hidden animate-scale-in">
            {{-- Header Modal --}}
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-600 text-white rounded-lg shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-slate-800">{{ $m->nama_mapel }}</h3>
                        <p class="text-xs text-slate-500">{{ $m->kode_mapel }}</p>
                    </div>
                </div>
                <button onclick="closeModal('modaljadwal{{ $m->id }}')"
                    class="text-slate-400 hover:text-red-500 text-3xl leading-none transition-colors">&times;</button>
            </div>

            {{-- Body Modal --}}
            <div class="flex flex-col lg:flex-row h-full overflow-hidden">
                {{-- Kiri: Area Tabel --}}
                <div class="flex-1 flex flex-col h-full border-r border-slate-100 bg-white relative min-w-0">
                    <div class="p-4 border-b border-slate-100 bg-white z-20 shrink-0">
                        <div class="relative group">
                            <span
                                class="absolute left-3 top-2.5 text-slate-400 group-focus-within:text-blue-500 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </span>
                            <input type="text" id="search-{{ $m->id }}" oninput="searchTable({{ $m->id }})"
                                placeholder="Cari Kelas atau Guru..."
                                class="w-full border border-slate-200 bg-slate-50/50 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500/50 focus:bg-white focus:border-blue-500 outline-none transition-all">
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-0 relative bg-white">
                        <table class="w-full text-xs border-collapse">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-4 py-3 text-left border-b border-slate-100 w-[20%]">Kelas</th>
                                    <th class="px-4 py-3 text-left border-b border-slate-100 w-[40%]">Guru Pengampu</th>
                                    <th class="px-4 py-3 text-center border-b border-slate-100 w-[20%]">Jam</th>
                                    <th class="px-4 py-3 text-right border-b border-slate-100 w-[20%]">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-mapel-{{ $m->id }}" class="divide-y divide-slate-50">
                                @foreach($m->jadwals as $jadwal)
                                <tr id="row-jadwal-{{ $jadwal->id }}"
                                    class="hover:bg-blue-50/50 transition duration-150 group">
                                    <td class="px-4 py-3 font-bold text-slate-700 align-middle kelas-text">
                                        {{ $jadwal->kelas->nama_kelas ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600 align-middle guru-text">
                                        {{ $jadwal->guru->nama_guru ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center align-middle">
                                        <div class="flex flex-col items-center">
                                            <span
                                                class="bg-white text-blue-700 px-2 py-0.5 rounded text-[10px] font-bold jam-text border border-blue-100 shadow-sm">{{ $jadwal->jumlah_jam }}
                                                JP</span>
                                            <span
                                                class="text-[9px] text-slate-400 mt-0.5 tipe-text uppercase font-semibold tracking-wider">{{ $jadwal->tipe_jam }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right align-middle">
                                        <div
                                            class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button
                                                onclick="editJadwalInline({{ $m->id }}, {{ $jadwal->id }}, {{ $jadwal->kelas_id }}, {{ $jadwal->guru_id }}, {{ $jadwal->jumlah_jam }}, '{{ $jadwal->tipe_jam }}')"
                                                class="p-1.5 text-blue-600 hover:bg-blue-100 rounded-lg transition"
                                                title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                    </path>
                                                </svg>
                                            </button>
                                            <button onclick="hapusJadwal({{ $jadwal->id }}, this)"
                                                class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition"
                                                title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @if($m->jadwals->isEmpty())
                                <tr class="empty-row">
                                    <td colspan="4" class="py-12 text-center text-slate-400 italic bg-slate-50/30">Belum
                                        ada data distribusi.</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Kanan: Form Input --}}
                <div
                    class="w-full lg:w-[380px] bg-slate-50 border-t lg:border-t-0 lg:border-l border-slate-200 flex flex-col h-[40vh] lg:h-full">
                    <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                        <div id="form-container-{{ $m->id }}"
                            class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm transition-all duration-300">
                            <div class="flex justify-between items-center mb-5 pb-3 border-b border-slate-100">
                                <h4 id="form-title-{{ $m->id }}"
                                    class="font-extrabold text-slate-700 text-xs uppercase tracking-widest flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span> Input Distribusi
                                </h4>
                                <button id="btn-batal-{{ $m->id }}" type="button"
                                    onclick="resetFormJadwal({{ $m->id }})"
                                    class="hidden text-[10px] font-bold text-red-500 hover:bg-red-50 px-2 py-1 rounded transition uppercase">Batal</button>
                            </div>
                            <form id="form-jadwal-{{ $m->id }}" action="{{ route('mapel.simpanJadwal', $m->id) }}"
                                method="POST" onsubmit="handleFormJadwal(event, this, {{ $m->id }})">
                                <div id="method-spoof-{{ $m->id }}"></div>
                                <div class="space-y-5">
                                    <div class="relative custom-select-wrapper" id="wrapper-kelas-{{ $m->id }}">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block">Kelas
                                            Tujuan</label>
                                        <input type="hidden" name="kelas_id" id="real-input-kelas-{{ $m->id }}"
                                            required>
                                        <button type="button" onclick="toggleCustomDropdown('kelas', {{ $m->id }})"
                                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-left text-sm flex justify-between items-center hover:bg-white hover:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all">
                                            <span id="display-kelas-{{ $m->id }}"
                                                class="text-slate-500 font-medium">Pilih Kelas...</span>
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <div id="dropdown-kelas-{{ $m->id }}"
                                            class="hidden absolute z-50 w-full bg-white border border-slate-200 rounded-xl shadow-xl mt-1 max-h-56 overflow-y-auto animate-scale-in">
                                            <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                                                <input type="text" placeholder="Cari..."
                                                    onkeyup="filterCustomDropdown('kelas', {{ $m->id }}, this)"
                                                    class="w-full p-2 text-xs border border-slate-200 rounded-lg bg-slate-50 focus:border-blue-500 outline-none">
                                            </div>
                                            <div id="list-kelas-{{ $m->id }}" class="p-1 grid grid-cols-2 gap-1">
                                                @foreach($kelases as $k)
                                                <div class="option-item p-2 hover:bg-blue-50 rounded-lg cursor-pointer text-xs font-bold text-slate-700 text-center border border-slate-100 transition-colors"
                                                    data-value="{{ $k->id }}" data-label="{{ $k->nama_kelas }}"
                                                    onclick="selectCustomOption('kelas', {{ $m->id }}, '{{ $k->id }}', '{{ $k->nama_kelas }}')">
                                                    {{ $k->nama_kelas }}
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="relative custom-select-wrapper" id="wrapper-guru-{{ $m->id }}">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block">Guru
                                            Pengampu</label>
                                        <input type="hidden" name="guru_id" id="real-input-guru-{{ $m->id }}" required>
                                        <button type="button" onclick="toggleCustomDropdown('guru', {{ $m->id }})"
                                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-left text-sm flex justify-between items-center hover:bg-white hover:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all">
                                            <span id="display-guru-{{ $m->id }}"
                                                class="text-slate-500 font-medium">Pilih Guru...</span>
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <div id="dropdown-guru-{{ $m->id }}"
                                            class="hidden absolute z-50 w-full bg-white border border-slate-200 rounded-xl shadow-xl mt-1 max-h-56 overflow-y-auto animate-scale-in">
                                            <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                                                <input type="text" placeholder="Cari..."
                                                    onkeyup="filterCustomDropdown('guru', {{ $m->id }}, this)"
                                                    class="w-full p-2 text-xs border border-slate-200 rounded-lg bg-slate-50 focus:border-blue-500 outline-none">
                                            </div>
                                            <div id="list-guru-{{ $m->id }}" class="p-1">
                                                @foreach($gurus as $g)
                                                <div class="option-item p-2.5 hover:bg-blue-50 rounded-lg cursor-pointer text-sm border-b border-slate-50 last:border-0 transition-colors"
                                                    data-value="{{ $g->id }}" data-label="{{ $g->nama_guru }}"
                                                    onclick="selectCustomOption('guru', {{ $m->id }}, '{{ $g->id }}', '{{ $g->nama_guru }}')">
                                                    <div class="font-bold text-slate-700">{{ $g->nama_guru }}</div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block">Total
                                                Jam</label>
                                            <div class="relative">
                                                <input type="number" name="jumlah_jam" id="input-jam-{{ $m->id }}"
                                                    class="w-full pl-4 pr-8 py-3 bg-slate-50 border border-slate-200 rounded-xl text-center font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition"
                                                    min="1" max="10" required>
                                                <span
                                                    class="absolute right-3 top-3.5 text-[10px] text-slate-400 font-bold">JP</span>
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block">Tipe</label>
                                            <div class="relative">
                                                <select name="tipe_jam" id="select-tipe-{{ $m->id }}"
                                                    class="w-full pl-3 pr-8 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none appearance-none cursor-pointer transition">
                                                    <option value="single">Single (1x)</option>
                                                    <option value="double">Double (2x)</option>
                                                    <option value="triple">Triple (3x)</option>
                                                </select>
                                                <div
                                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" id="btn-submit-{{ $m->id }}"
                                        class="w-full py-3.5 bg-slate-900 hover:bg-blue-600 text-white rounded-xl font-bold text-xs tracking-widest uppercase shadow-lg hover:shadow-blue-500/30 transform active:scale-95 transition-all duration-300 mt-2">Simpan
                                        Distribusi</button>
                                </div>
                            </form>
                        </div>
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
// 1. SEARCH LOGIC (OPTIMIZED & FAST)
// ==========================================================
function searchMainTable() {
    const input = document.getElementById('search-mapel-main').value.toLowerCase();
    const rows = document.querySelectorAll('#tbody-mapel-main tr[data-filter]');
    const noResultRow = document.getElementById('search-no-result');
    let hasResult = false;

    rows.forEach(row => {
        const filterText = row.getAttribute('data-filter');
        if (filterText && filterText.includes(input)) {
            row.style.display = "";
            hasResult = true;
        } else {
            row.style.display = "none";
        }
    });

    if (noResultRow) {
        if (!hasResult && input.length > 0) {
            noResultRow.classList.remove('hidden');
        } else {
            noResultRow.classList.add('hidden');
        }
    }
}

// ==========================================================
// 2. MODAL & DROPDOWN UTILS
// ==========================================================
const csrfNode = document.querySelector('meta[name="csrf-token"]');
const CSRF_TOKEN = csrfNode ? csrfNode.content : '';

function openModal(modalID) {
    const modal = document.getElementById(modalID);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
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

// ==========================================================
// 3. SEARCH INTERNAL TABLE (MODAL)
// ==========================================================
function searchTable(mapelId) {
    const filter = document.getElementById('search-' + mapelId).value.toLowerCase();
    const rows = document.getElementById('tbody-mapel-' + mapelId).getElementsByTagName('tr');

    for (let row of rows) {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    }
}

// ==========================================================
// 4. CUSTOM DROPDOWN LOGIC
// ==========================================================
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
        const label = item.getAttribute('data-label').toLowerCase();
        item.style.display = label.includes(filter) ? "" : "none";
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

// ==========================================================
// 5. AJAX (EDIT & DELETE)
// ==========================================================
function editJadwalInline(mapelId, jadwalId, kelasId, guruId, jam, tipe) {
    const container = document.getElementById(`form-container-${mapelId}`);
    const title = document.getElementById(`form-title-${mapelId}`);
    const form = document.getElementById(`form-jadwal-${mapelId}`);
    const btnSubmit = document.getElementById(`btn-submit-${mapelId}`);

    container.classList.add('ring-2', 'ring-amber-200');
    title.innerHTML = `<span class="text-amber-600">EDIT DISTRIBUSI</span>`;
    document.getElementById(`btn-batal-${mapelId}`).classList.remove('hidden');

    btnSubmit.className =
        "w-full py-3.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold shadow-lg transition mt-2";
    btnSubmit.innerHTML = "UPDATE";

    setCustomDropdownValue('kelas', mapelId, kelasId);
    setCustomDropdownValue('guru', mapelId, guruId);
    document.getElementById(`input-jam-${mapelId}`).value = jam;
    document.getElementById(`select-tipe-${mapelId}`).value = tipe;

    form.action = `/mapel/jadwal/${jadwalId}`;
    form.dataset.mode = 'edit';
    document.getElementById(`method-spoof-${mapelId}`).innerHTML = `<input type="hidden" name="_method" value="PUT">`;
}

function resetFormJadwal(mapelId) {
    const container = document.getElementById(`form-container-${mapelId}`);
    const form = document.getElementById(`form-jadwal-${mapelId}`);
    if (!container || !form) return;

    container.classList.remove('ring-2', 'ring-amber-200');
    document.getElementById(`form-title-${mapelId}`).innerHTML =
        `<span class="w-2 h-2 rounded-full bg-blue-500"></span> Input Distribusi`;
    document.getElementById(`btn-batal-${mapelId}`).classList.add('hidden');

    const btnSubmit = document.getElementById(`btn-submit-${mapelId}`);
    btnSubmit.className =
        "w-full py-3.5 bg-slate-900 hover:bg-blue-600 text-white rounded-xl font-bold text-xs tracking-widest uppercase shadow-lg transition mt-2";
    btnSubmit.innerHTML = "SIMPAN";

    form.reset();
    resetCustomDropdown('kelas', mapelId);
    resetCustomDropdown('guru', mapelId);

    form.action = `/mapel/${mapelId}/jadwal`;
    delete form.dataset.mode;
    document.getElementById(`method-spoof-${mapelId}`).innerHTML = '';
}

async function handleFormJadwal(e, form, mapelId) {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const oldText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = "Loading...";

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
        alert("Terjadi kesalahan sistem.");
        console.error(err);
    } finally {
        btn.disabled = false;
        btn.innerHTML = oldText;
    }
}

function updateTableUI(mapelId, jadwal, isEdit) {
    const tbody = document.getElementById(`tbody-mapel-${mapelId}`);
    const namaKelas = jadwal.kelas?.nama_kelas || '-';
    const namaGuru = jadwal.guru?.nama_guru || '-';

    if (isEdit) {
        const row = document.getElementById(`row-jadwal-${jadwal.id}`);
        if (row) {
            row.querySelector('.kelas-text').innerText = namaKelas;
            row.querySelector('.guru-text').innerText = namaGuru;
            row.querySelector('.jam-text').innerText = jadwal.jumlah_jam + ' JP';
            row.querySelector('.tipe-text').innerText = jadwal.tipe_jam;

            const btnEdit = row.querySelector('button[onclick^="editJadwalInline"]');
            btnEdit.setAttribute('onclick',
                `editJadwalInline(${mapelId}, ${jadwal.id}, ${jadwal.kelas_id}, ${jadwal.guru_id}, ${jadwal.jumlah_jam}, '${jadwal.tipe_jam}')`
            );

            row.classList.add('bg-amber-100');
            setTimeout(() => row.classList.remove('bg-amber-100'), 1500);
        }
    } else {
        const tr = document.createElement('tr');
        tr.id = `row-jadwal-${jadwal.id}`;
        tr.className = "hover:bg-blue-50 transition group";
        tr.innerHTML = `
                <td class="px-4 py-3 font-bold text-slate-700 align-middle kelas-text">${namaKelas}</td>
                <td class="px-4 py-3 text-slate-600 align-middle guru-text">${namaGuru}</td>
                <td class="px-4 py-3 text-center align-middle">
                    <div class="flex flex-col items-center">
                        <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-[10px] font-bold jam-text border border-blue-100 shadow-sm">${jadwal.jumlah_jam} JP</span>
                        <span class="text-[9px] text-slate-400 mt-0.5 tipe-text uppercase font-semibold tracking-wider">${jadwal.tipe_jam}</span>
                    </div>
                </td>
                <td class="px-4 py-3 text-right align-middle">
                    <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick="editJadwalInline(${mapelId}, ${jadwal.id}, ${jadwal.kelas_id}, ${jadwal.guru_id}, ${jadwal.jumlah_jam}, '${jadwal.tipe_jam}')" class="p-1.5 text-blue-600 hover:bg-blue-100 rounded-lg transition" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        </button>
                        <button onclick="hapusJadwal(${jadwal.id}, this)" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition" title="Hapus">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </td>`;

        const tbody = document.getElementById(`tbody-mapel-${mapelId}`);
        tbody.appendChild(tr);

        const emptyRow = tbody.querySelector('.empty-row');
        if (emptyRow) emptyRow.remove();
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