@extends('layouts.app')

@section('content')
{{-- DECORATIVE BACKGROUND (Optimized: Reduced Blur for Performance) --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-blue-50 to-slate-50"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-blue-300/20 rounded-full blur-3xl mix-blend-multiply opacity-70">
    </div>
    <div class="absolute top-20 left-20 w-72 h-72 bg-cyan-300/20 rounded-full blur-3xl mix-blend-multiply opacity-70">
    </div>
</div>

<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 pb-20 pt-6">

    {{-- HEADER CARD --}}
    <div
        class="relative z-10 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6 p-6 bg-white/80 backdrop-blur-md rounded-2xl border border-white/50 shadow-sm transition-all duration-500">
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
            <div class="hidden md:flex items-center px-4 py-2 bg-white border border-slate-200 rounded-xl shadow-sm">
                <div class="flex h-2.5 w-2.5 relative mr-3">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                </div>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Total: <span
                        class="font-bold text-slate-800 text-sm ml-1">{{ $mapels->count() }}</span></span>
            </div>

            <button onclick="openModal('modaltambah')"
                class="relative inline-flex items-center justify-center px-6 py-2.5 overflow-hidden font-bold text-white transition-all duration-300 bg-blue-600 rounded-xl group hover:bg-blue-700 shadow-md hover:shadow-lg hover:-translate-y-0.5">
                <span class="flex items-center gap-2">
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
    <div x-data="{ show: true }" x-show="show"
        class="mb-6 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800">
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

    {{-- MAIN CONTENT --}}
    <div class="relative z-10 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- SEARCH BAR AREA --}}
        <div
            class="p-4 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
            <div class="relative w-full sm:w-96 group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                {{-- OPTIMIZATION: Added oninput instead of onkeyup for faster response --}}
                <input type="text" id="search-mapel-main" oninput="searchMainTable()"
                    class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl leading-5 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-sm transition shadow-sm"
                    placeholder="Cari Nama Mapel atau Kode...">
            </div>

            <div class="flex items-center gap-2.5 px-4 py-2 bg-blue-50 border border-blue-100 rounded-lg">
                <span class="text-[11px] text-blue-900 font-bold uppercase tracking-widest">
                    Tahun Ajaran {{ date('Y') }}/{{ date('Y')+1 }}
                </span>
            </div>
        </div>

        {{-- TABLE CONTAINER --}}
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead class="bg-slate-50 sticky top-0 z-10 border-b border-slate-200">
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
                            Aksi</th>
                    </tr>
                </thead>

                <tbody id="tbody-mapel-main" class="divide-y divide-slate-100 bg-white">
                    @forelse($mapels as $index => $m)
                    {{-- OPTIMIZATION: Added data-filter attribute for instant search --}}
                    <tr class="group hover:bg-slate-50 transition-colors duration-200"
                        data-filter="{{ strtolower($m->nama_mapel) }} {{ strtolower($m->kode_mapel) }}">

                        {{-- NOMOR --}}
                        <td class="px-6 py-4 text-center">
                            <span class="font-mono text-slate-400 text-sm">{{ $index + 1 }}</span>
                        </td>

                        {{-- IDENTITAS MAPEL --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-10 w-10 shrink-0 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm border border-blue-200">
                                    {{ substr($m->nama_mapel, 0, 1) }}
                                </div>
                                <div>
                                    <div
                                        class="font-bold text-slate-800 text-sm group-hover:text-blue-700 transition-colors">
                                        {{ $m->nama_mapel }}
                                    </div>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200 font-mono">
                                        {{ $m->kode_mapel }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- STATUS DISTRIBUSI --}}
                        <td class="px-6 py-4 text-center">
                            @if($m->total_jam_terdistribusi > 0)
                            <div
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100">
                                <span class="text-xs font-bold text-blue-700">{{ $m->total_jam_terdistribusi }} JP
                                    Total</span>
                            </div>
                            @else
                            <div
                                class="inline-flex items-center px-3 py-1.5 rounded-full bg-slate-50 border border-slate-200 text-slate-400 text-xs font-medium">
                                -
                            </div>
                            @endif
                        </td>

                        {{-- AKSI --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openModal('modaljadwal{{ $m->id }}')"
                                    class="px-3 py-1.5 bg-slate-800 hover:bg-blue-600 text-white text-xs font-bold rounded-lg transition-colors shadow-sm">
                                    Distribusi
                                </button>

                                <a href="{{ route('mapel.waktuKosong', $m->id) }}"
                                    class="p-1.5 text-rose-500 bg-rose-50 hover:bg-rose-100 border border-rose-100 rounded-lg transition-colors"
                                    title="Blok Jam">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </a>

                                <button onclick="openModal('edit{{ $m->id }}')"
                                    class="p-1.5 text-amber-500 bg-amber-50 hover:bg-amber-100 border border-amber-100 rounded-lg transition-colors"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>

                                <form action="{{ route('mapel.destroy', $m->id) }}" method="POST"
                                    onsubmit="return confirm('Hapus permanen mapel ini?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-1.5 text-slate-400 hover:text-red-500 bg-slate-50 hover:bg-red-50 border border-slate-200 rounded-lg transition-colors"
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
                        <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                            Belum ada data mata pelajaran.
                        </td>
                    </tr>
                    @endforelse

                    {{-- Row untuk pesan "Tidak Ditemukan" saat search --}}
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

        <div class="bg-slate-50 border-t border-slate-200 px-6 py-4 flex justify-between items-center">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sistem Penjadwalan</span>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">v2.1 Optimized</span>
        </div>
    </div>

    {{-- MODALS AREA --}}
    {{-- 1. Modal Tambah Mapel --}}
    <div id="modaltambah"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-scale-in">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800">Tambah Mapel</h3>
                <button onclick="closeModal('modaltambah')"
                    class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
            </div>
            <form action="{{ route('mapel.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Mapel</label>
                    <input type="text" name="nama_mapel"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Kode Mapel</label>
                    <input type="text" name="kode_mapel"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none font-mono uppercase text-sm"
                        required>
                </div>
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg shadow-md transition">SIMPAN</button>
            </form>
        </div>
    </div>

    {{-- Modal Edit dan Modal Jadwal tetap di-include seperti logika Anda sebelumnya --}}
    {{-- NOTE: Untuk performa maksimal, Modal Edit & Jadwal sebaiknya tidak di-loop, tapi di sini kita pertahankan struktur Anda --}}
    {{-- Namun saya optimalkan agar tidak render berlebihan --}}

    @foreach($mapels as $m)
    {{-- 2. Modal Edit --}}
    <div id="edit{{ $m->id }}"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-amber-100 bg-amber-50 flex justify-between items-center">
                <h3 class="font-bold text-amber-800">Edit Mapel</h3>
                <button onclick="closeModal('edit{{ $m->id }}')"
                    class="text-amber-400 hover:text-amber-600 text-2xl">&times;</button>
            </div>
            <form action="{{ route('mapel.update', $m->id) }}" method="POST" class="p-6 space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Mapel</label>
                    <input type="text" name="nama_mapel" value="{{ $m->nama_mapel }}"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 outline-none text-sm"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Kode</label>
                    <input type="text" name="kode_mapel" value="{{ $m->kode_mapel }}"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 outline-none font-mono uppercase text-sm"
                        required>
                </div>
                <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-lg shadow-md transition">UPDATE</button>
            </form>
        </div>
    </div>

    {{-- 3. Modal Distribusi (Menggunakan include Anda sebelumnya, disederhanakan visualnya agar ringan) --}}
    {{-- Pastikan struktur HTML modal jadwal Anda mengikuti style yang lebih clean seperti di atas --}}
    {{-- Copy Paste isi Modal Jadwal Anda disini, tapi HAPUS backdrop-blur yang berlebihan --}}
    <div id="modaljadwal{{ $m->id }}"
        class="fixed inset-0 bg-slate-900/80 z-[99] hidden flex items-center justify-center p-2 sm:p-4">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl h-[90vh] flex flex-col border border-slate-200 overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-lg text-slate-800">{{ $m->nama_mapel }} <span
                        class="text-sm font-normal text-slate-500">({{ $m->kode_mapel }})</span></h3>
                <button onclick="closeModal('modaljadwal{{ $m->id }}')"
                    class="text-slate-400 hover:text-red-500 text-3xl leading-none">&times;</button>
            </div>

            {{-- Body (Split View) --}}
            <div class="flex flex-col lg:flex-row h-full overflow-hidden">
                {{-- Kiri: Tabel --}}
                <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-white border-r border-slate-100 custom-scrollbar">
                    {{-- SEARCH INTERNAL MODAL --}}
                    <div class="mb-4 sticky top-0 bg-white z-10 py-2">
                        <input type="text" id="search-{{ $m->id }}" oninput="searchTable({{ $m->id }})"
                            placeholder="Cari Kelas/Guru di tabel..."
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-600 font-bold uppercase sticky top-12 z-10">
                            <tr>
                                <th class="px-3 py-2 text-left rounded-l-md">Kelas</th>
                                <th class="px-3 py-2 text-left">Guru</th>
                                <th class="px-3 py-2 text-center">Jam</th>
                                <th class="px-3 py-2 text-right rounded-r-md">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-mapel-{{ $m->id }}" class="divide-y divide-slate-100">
                            @foreach($m->jadwals as $jadwal)
                            <tr id="row-jadwal-{{ $jadwal->id }}" class="hover:bg-slate-50">
                                <td class="px-3 py-2 font-bold text-slate-700 align-middle kelas-text">
                                    {{ $jadwal->kelas->nama_kelas ?? '-' }}</td>
                                <td class="px-3 py-2 text-slate-600 align-middle guru-text">
                                    {{ $jadwal->guru->nama_guru ?? '-' }}</td>
                                <td class="px-3 py-2 text-center align-middle">
                                    <span
                                        class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-[10px] font-bold jam-text">{{ $jadwal->jumlah_jam }}
                                        JP</span>
                                    <div class="text-[9px] text-slate-400 uppercase tipe-text">{{ $jadwal->tipe_jam }}
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right align-middle">
                                    <button
                                        onclick="editJadwalInline({{ $m->id }}, {{ $jadwal->id }}, {{ $jadwal->kelas_id }}, {{ $jadwal->guru_id }}, {{ $jadwal->jumlah_jam }}, '{{ $jadwal->tipe_jam }}')"
                                        class="text-blue-600 hover:text-blue-800 mr-2">Edit</button>
                                    <button onclick="hapusJadwal({{ $jadwal->id }}, this)"
                                        class="text-red-500 hover:text-red-700">Hapus</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Kanan: Form --}}
                <div
                    class="w-full lg:w-[350px] bg-slate-50 p-6 overflow-y-auto border-t lg:border-t-0 lg:border-l border-slate-200">
                    {{-- Container Form Anda (Simpan disini, struktur sama, CSS disederhanakan) --}}
                    <div id="form-container-{{ $m->id }}"
                        class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                        <div class="flex justify-between items-center mb-4 pb-2 border-b border-slate-100">
                            <h4 id="form-title-{{ $m->id }}" class="font-bold text-slate-700 text-sm uppercase">Input
                                Data</h4>
                            <button id="btn-batal-{{ $m->id }}" type="button" onclick="resetFormJadwal({{ $m->id }})"
                                class="hidden text-xs text-red-500 font-bold">Batal</button>
                        </div>

                        <form id="form-jadwal-{{ $m->id }}" action="{{ route('mapel.simpanJadwal', $m->id) }}"
                            method="POST" onsubmit="handleFormJadwal(event, this, {{ $m->id }})">
                            <div id="method-spoof-{{ $m->id }}"></div>
                            <div class="space-y-4">
                                {{-- Custom Dropdown Kelas --}}
                                <div class="relative custom-select-wrapper" id="wrapper-kelas-{{ $m->id }}">
                                    <label class="text-[10px] font-bold text-slate-400 uppercase">Kelas</label>
                                    <input type="hidden" name="kelas_id" id="real-input-kelas-{{ $m->id }}" required>
                                    <button type="button" onclick="toggleCustomDropdown('kelas', {{ $m->id }})"
                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-left text-sm flex justify-between items-center">
                                        <span id="display-kelas-{{ $m->id }}" class="text-slate-500">Pilih
                                            Kelas...</span>
                                        <span class="text-xs">▼</span>
                                    </button>
                                    {{-- Dropdown Content --}}
                                    <div id="dropdown-kelas-{{ $m->id }}"
                                        class="hidden absolute z-50 w-full bg-white border border-slate-300 rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto">
                                        <div class="sticky top-0 bg-white p-2 border-b">
                                            <input type="text" placeholder="Cari..."
                                                onkeyup="filterCustomDropdown('kelas', {{ $m->id }}, this)"
                                                class="w-full p-1 text-xs border rounded">
                                        </div>
                                        <div id="list-kelas-{{ $m->id }}">
                                            @foreach($kelases as $k)
                                            <div class="option-item p-2 hover:bg-blue-50 cursor-pointer text-sm"
                                                data-value="{{ $k->id }}" data-label="{{ $k->nama_kelas }}"
                                                onclick="selectCustomOption('kelas', {{ $m->id }}, '{{ $k->id }}', '{{ $k->nama_kelas }}')">
                                                {{ $k->nama_kelas }}
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Custom Dropdown Guru --}}
                                <div class="relative custom-select-wrapper" id="wrapper-guru-{{ $m->id }}">
                                    <label class="text-[10px] font-bold text-slate-400 uppercase">Guru</label>
                                    <input type="hidden" name="guru_id" id="real-input-guru-{{ $m->id }}" required>
                                    <button type="button" onclick="toggleCustomDropdown('guru', {{ $m->id }})"
                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-left text-sm flex justify-between items-center">
                                        <span id="display-guru-{{ $m->id }}" class="text-slate-500">Pilih Guru...</span>
                                        <span class="text-xs">▼</span>
                                    </button>
                                    <div id="dropdown-guru-{{ $m->id }}"
                                        class="hidden absolute z-50 w-full bg-white border border-slate-300 rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto">
                                        <div class="sticky top-0 bg-white p-2 border-b">
                                            <input type="text" placeholder="Cari..."
                                                onkeyup="filterCustomDropdown('guru', {{ $m->id }}, this)"
                                                class="w-full p-1 text-xs border rounded">
                                        </div>
                                        <div id="list-guru-{{ $m->id }}">
                                            @foreach($gurus as $g)
                                            <div class="option-item p-2 hover:bg-blue-50 cursor-pointer text-sm border-b border-slate-50"
                                                data-value="{{ $g->id }}" data-label="{{ $g->nama_guru }}"
                                                onclick="selectCustomOption('guru', {{ $m->id }}, '{{ $g->id }}', '{{ $g->nama_guru }}')">
                                                <div class="font-bold">{{ $g->nama_guru }}</div>
                                                <div class="text-[10px] text-slate-400">{{ $g->kode_guru }}</div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Input Jam & Tipe (Compact Grid) --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 uppercase">Total Jam</label>
                                        <input type="number" name="jumlah_jam" id="input-jam-{{ $m->id }}"
                                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-center font-bold"
                                            min="1" max="10" required>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 uppercase">Tipe</label>
                                        <select name="tipe_jam" id="select-tipe-{{ $m->id }}"
                                            class="w-full border border-slate-300 rounded-lg px-2 py-2 text-xs">
                                            <option value="single">Single (1 Pertemuan)</option>
                                            <option value="double">Double (2 Pertemuan)</option>
                                            <option value="triple">Triple (3 Pertemuan)</option>
                                        </select>
                                    </div>
                                </div>

                                <button type="submit" id="btn-submit-{{ $m->id }}"
                                    class="w-full py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-lg font-bold text-sm transition">SIMPAN</button>
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
// 1. SEARCH LOGIC OPTIMIZED (FASTEST)
// ==========================================================
function searchMainTable() {
    // Ambil value input dan jadikan lowercase
    const input = document.getElementById('search-mapel-main').value.toLowerCase();
    const rows = document.querySelectorAll('#tbody-mapel-main tr[data-filter]');
    const noResultRow = document.getElementById('search-no-result');
    let hasResult = false;

    // Loop menggunakan Dataset (Jauh lebih cepat daripada innerText)
    rows.forEach(row => {
        const filterText = row.getAttribute('data-filter');
        // Cek apakah string filter mengandung input
        if (filterText && filterText.includes(input)) {
            row.style.display = ""; // Tampilkan
            hasResult = true;
        } else {
            row.style.display = "none"; // Sembunyikan
        }
    });

    // Toggle pesan "Tidak Ditemukan"
    if (noResultRow) {
        if (!hasResult && input.length > 0) {
            noResultRow.classList.remove('hidden');
        } else {
            noResultRow.classList.add('hidden');
        }
    }
}

// ==========================================================
// 2. MODAL & UI UTILITIES
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
        // Reset form jika itu modal jadwal
        if (modalID.includes('modaljadwal')) {
            const id = modalID.replace('modaljadwal', '');
            resetFormJadwal(id);
        }
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.add('hidden');
        event.target.classList.remove('flex');
    }
    // Close dropdowns
    if (!event.target.closest('.custom-select-wrapper')) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.custom-select-wrapper').forEach(el => el.style.zIndex = "0");
    }
}

// ==========================================================
// 3. INTERNAL TABLE SEARCH (Inside Modal)
// ==========================================================
function searchTable(mapelId) {
    const filter = document.getElementById('search-' + mapelId).value.toLowerCase();
    const tbody = document.getElementById('tbody-mapel-' + mapelId);
    const rows = tbody.getElementsByTagName('tr');

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

    // Tutup dropdown lain
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
// 5. EDIT & DELETE LOGIC (AJAX)
// ==========================================================
function editJadwalInline(mapelId, jadwalId, kelasId, guruId, jam, tipe) {
    const container = document.getElementById(`form-container-${mapelId}`);
    const title = document.getElementById(`form-title-${mapelId}`);
    const form = document.getElementById(`form-jadwal-${mapelId}`);
    const btnSubmit = document.getElementById(`btn-submit-${mapelId}`);

    // UI Change
    container.classList.add('ring-2', 'ring-amber-200');
    title.innerHTML = `<span class="text-amber-600">EDIT DISTRIBUSI</span>`;
    document.getElementById(`btn-batal-${mapelId}`).classList.remove('hidden');

    btnSubmit.className =
        "w-full py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-bold text-sm transition";
    btnSubmit.innerHTML = "UPDATE";

    // Set Values
    setCustomDropdownValue('kelas', mapelId, kelasId);
    setCustomDropdownValue('guru', mapelId, guruId);
    document.getElementById(`input-jam-${mapelId}`).value = jam;
    document.getElementById(`select-tipe-${mapelId}`).value = tipe;

    // Form Action Override
    form.action = `/mapel/jadwal/${jadwalId}`;
    form.dataset.mode = 'edit';
    document.getElementById(`method-spoof-${mapelId}`).innerHTML = `<input type="hidden" name="_method" value="PUT">`;
}

function resetFormJadwal(mapelId) {
    const container = document.getElementById(`form-container-${mapelId}`);
    const form = document.getElementById(`form-jadwal-${mapelId}`);
    if (!container || !form) return;

    container.classList.remove('ring-2', 'ring-amber-200');
    document.getElementById(`form-title-${mapelId}`).innerHTML = `INPUT DATA`;
    document.getElementById(`btn-batal-${mapelId}`).classList.add('hidden');

    const btnSubmit = document.getElementById(`btn-submit-${mapelId}`);
    btnSubmit.className =
        "w-full py-3 bg-slate-800 hover:bg-slate-700 text-white rounded-lg font-bold text-sm transition";
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

            // Update onclick params
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
        tr.className = "hover:bg-slate-50";
        tr.innerHTML = `
            <td class="px-3 py-2 font-bold text-slate-700 align-middle kelas-text">${namaKelas}</td>
            <td class="px-3 py-2 text-slate-600 align-middle guru-text">${namaGuru}</td>
            <td class="px-3 py-2 text-center align-middle">
                <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-[10px] font-bold jam-text">${jadwal.jumlah_jam} JP</span>
                <div class="text-[9px] text-slate-400 uppercase tipe-text">${jadwal.tipe_jam}</div>
            </td>
            <td class="px-3 py-2 text-right align-middle">
                <button onclick="editJadwalInline(${mapelId}, ${jadwal.id}, ${jadwal.kelas_id}, ${jadwal.guru_id}, ${jadwal.jumlah_jam}, '${jadwal.tipe_jam}')" class="text-blue-600 hover:text-blue-800 mr-2">Edit</button>
                <button onclick="hapusJadwal(${jadwal.id}, this)" class="text-red-500 hover:text-red-700">Hapus</button>
            </td>`;
        tbody.appendChild(tr);
    }
}

async function hapusJadwal(id, btn) {
    if (!confirm("Hapus data ini?")) return;
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