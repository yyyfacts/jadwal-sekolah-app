@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-blue-50/50 to-white"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-300/10 rounded-full blur-3xl opacity-70"></div>
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

    {{-- UNIFIED CARD --}}
    <div
        class="bg-white rounded-[2rem] border border-slate-100 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.08)] flex flex-col flex-1 overflow-hidden">

        {{-- 1. HEADER SECTION --}}
        <div class="px-8 pt-8 pb-6 bg-white shrink-0 z-20">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                <div class="flex gap-3 items-start">
                    <div class="w-2.5 h-8 bg-indigo-600 rounded-full mt-0.5"></div>
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Bank Data Guru</h1>
                        <p class="text-slate-500 text-sm mt-1 font-medium">Manajemen profil pengajar, NIP, dan beban
                            jam.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div
                        class="hidden md:flex items-center px-5 py-2.5 bg-white border border-slate-200 rounded-full shadow-sm">
                        <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">
                            Total: <span
                                class="text-indigo-600 text-sm ml-1 font-extrabold">{{ $gurus->count() }}</span>
                        </span>
                    </div>

                    <button type="button" onclick="openModal('modaltambah')"
                        class="px-6 py-2.5 font-bold text-white transition-all duration-300 bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-md flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        <span class="text-sm uppercase tracking-wide">Tambah</span>
                    </button>
                </div>
            </div>

            {{-- Search Bar --}}
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-indigo-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="search-guru-main" oninput="searchMainTable()"
                    class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl leading-5 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm transition shadow-sm"
                    placeholder="Cari Nama Guru atau NIP...">
            </div>
        </div>

        {{-- 2. TABLE SECTION --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white px-2">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-white sticky top-0 z-10">
                    <tr>
                        <th
                            class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-16 border-b-2 border-slate-100">
                            No</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider w-[40%] border-b-2 border-slate-100">
                            Profil Guru</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-[25%] border-b-2 border-slate-100">
                            Beban Mengajar</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-[35%] border-b-2 border-slate-100">
                            Aksi & Jadwal</th>
                    </tr>
                </thead>

                <tbody id="tbody-guru-main" class="divide-y divide-slate-100/80">
                    @php
                    $themes = [
                    ['avatar' => 'bg-pink-600', 'pillBg' => 'bg-pink-50', 'pillText' => 'text-pink-700', 'dot' =>
                    'bg-pink-500'],
                    ['avatar' => 'bg-cyan-500', 'pillBg' => 'bg-cyan-50', 'pillText' => 'text-cyan-700', 'dot' =>
                    'bg-cyan-500'],
                    ['avatar' => 'bg-blue-800', 'pillBg' => 'bg-blue-50', 'pillText' => 'text-blue-800', 'dot' =>
                    'bg-blue-600'],
                    ['avatar' => 'bg-indigo-500', 'pillBg' => 'bg-indigo-50', 'pillText' => 'text-indigo-700', 'dot' =>
                    'bg-indigo-500'],
                    ];
                    @endphp

                    @forelse($gurus as $index => $g)
                    @php $theme = $themes[$index % 4]; @endphp
                    <tr class="group hover:bg-slate-50/50 transition-colors duration-200"
                        data-filter="{{ strtolower($g->nama_guru) }} {{ strtolower($g->kode_guru) }}">
                        <td class="px-8 py-5 text-center">
                            <span
                                class="font-medium text-slate-400 text-sm group-hover:text-slate-600 transition-colors">{{ $index + 1 }}</span>
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-10 w-10 shrink-0 rounded-full {{ $theme['avatar'] }} text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                    {{ substr($g->nama_guru, 0, 1) }}
                                </div>
                                <div>
                                    <div
                                        class="font-bold text-slate-800 text-sm group-hover:text-indigo-600 transition-colors flex items-center gap-2">
                                        {{ $g->nama_guru }}
                                        @if(!empty($g->hari_array))
                                        @if($g->jenis_hari == 'hard')
                                        <span
                                            class="px-1.5 py-0.5 bg-rose-100 text-rose-600 text-[9px] rounded font-bold uppercase"
                                            title="Hard Constraint">Hari: Strict</span>
                                        @else
                                        <span
                                            class="px-1.5 py-0.5 bg-emerald-100 text-emerald-600 text-[9px] rounded font-bold uppercase"
                                            title="Soft Constraint">Hari: Soft</span>
                                        @endif
                                        @endif
                                    </div>
                                    <div
                                        class="inline-block px-2 py-0.5 mt-1 rounded bg-slate-100 border border-slate-200 text-slate-500 font-bold text-[10px] tracking-wide">
                                        {{ $g->kode_guru }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            @if($g->total_jam_mengajar > 0)
                            <div
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full {{ $theme['pillBg'] }} {{ $theme['pillText'] }}">
                                <div class="w-1.5 h-1.5 rounded-full {{ $theme['dot'] }}"></div>
                                <span class="text-xs font-bold">{{ $g->total_jam_mengajar }} Jam</span>
                            </div>
                            @else
                            <div
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-50 text-slate-400 border border-slate-100">
                                <div class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                                <span class="text-xs font-bold">0 Jam</span>
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" onclick="openModal('modaljadwal{{ $g->id }}')"
                                    class="flex items-center gap-2 px-4 py-2 border border-slate-200 text-slate-700 hover:border-indigo-400 hover:text-indigo-600 text-xs font-bold rounded-full transition-colors bg-white">
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg> Jadwal
                                </button>
                                <button type="button" onclick="openModal('edit{{ $g->id }}')"
                                    class="p-2 border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-300 rounded-lg transition-colors bg-white"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>
                                <form action="{{ route('guru.destroy', $g->id) }}" method="POST"
                                    onsubmit="return confirm('Hapus data {{ $g->nama_guru }}?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-2 border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-300 rounded-lg transition-colors bg-white"
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
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                    </path>
                                </svg>
                                <span class="text-sm font-medium">Belum ada data guru.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse

                    <tr id="search-no-result" class="hidden">
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <p>Guru tidak ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bg-white border-t border-slate-100 px-8 py-4 flex justify-between items-center shrink-0">
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Sistem Penjadwalan</span>
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Secure Data</span>
        </div>
    </div>
</div>

{{-- MODALS AREA --}}

{{-- 1. Modal Tambah Guru --}}
<div id="modaltambah"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-scale-in">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <span class="w-1.5 h-5 bg-indigo-600 rounded-full"></span> Tambah Guru
            </h3>
            <button type="button" onclick="closeModal('modaltambah')"
                class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('guru.store') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Lengkap</label>
                <input type="text" name="nama_guru"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition"
                    placeholder="Contoh: Budi Santoso, S.Pd" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">NIP / Kode Guru</label>
                <input type="text" name="kode_guru"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none font-mono uppercase text-sm transition"
                    placeholder="GR-01" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Hari Bersedia Mengajar <span
                        class="text-[10px] font-normal italic lowercase">(Kosongkan jika bebas)</span></label>
                <div class="flex flex-wrap gap-3 mb-3">
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari)
                    <label
                        class="inline-flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:bg-indigo-50 transition">
                        <input type="checkbox" name="hari_mengajar[]" value="{{ $hari }}"
                            class="rounded text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-bold text-slate-600">{{ $hari }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- PERUBAHAN: Tambahan Input Hard/Soft Constraint --}}
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Sifat Hari Mengajar</label>
                <select name="jenis_hari"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 outline-none text-sm transition">
                    <option value="soft" {{ $g->jenis_hari == 'soft' ? 'selected' : '' }}>Fleksibel (Prioritas, Tapi
                        Boleh Digeser)</option>
                    <option value="hard" {{ $g->jenis_hari == 'hard' ? 'selected' : '' }}>Mutlak (Wajib / Tidak Boleh
                        Hari Lain)</option>
                </select>
            </div>

            <button type="submit"
                class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold py-3.5 rounded-xl shadow-lg transition duration-300 uppercase tracking-wider text-xs">SIMPAN
                DATA</button>
        </form>
    </div>
</div>

@foreach($gurus as $g)
{{-- 2. Modal Edit Guru --}}
<div id="edit{{ $g->id }}"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden border border-white/20">
        <div class="px-6 py-4 border-b border-amber-100 bg-amber-50 flex justify-between items-center">
            <h3 class="font-bold text-amber-800 flex items-center gap-2"><span
                    class="w-1.5 h-5 bg-amber-500 rounded-full"></span> Edit Guru</h3>
            <button type="button" onclick="closeModal('edit{{ $g->id }}')"
                class="text-amber-400 hover:text-amber-600 text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('guru.update', $g->id) }}" method="POST" class="p-6 space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Guru</label>
                <input type="text" name="nama_guru" value="{{ $g->nama_guru }}"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 outline-none text-sm transition"
                    required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Kode / NIP</label>
                <input type="text" name="kode_guru" value="{{ $g->kode_guru }}"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 outline-none font-mono uppercase text-sm transition"
                    required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Hari Bersedia Mengajar <span
                        class="text-[10px] font-normal italic lowercase">(Kosongkan jika bebas)</span></label>
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari)
                    <label
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg cursor-pointer hover:bg-amber-50 transition">
                        <input type="checkbox" name="hari_mengajar[]" value="{{ $hari }}"
                            {{ in_array($hari, $g->hari_array) ? 'checked' : '' }}
                            class="rounded text-amber-500 focus:ring-amber-500">
                        <span class="text-xs font-bold text-slate-600">{{ $hari }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- PERUBAHAN: Tambahan Input Hard/Soft Constraint --}}
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Sifat Hari Mengajar</label>
                <select name="jenis_hari"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 outline-none text-sm transition">
                    <option value="soft" {{ $g->jenis_hari == 'soft' ? 'selected' : '' }}>Fleksibel (Prioritas, Tapi
                        Boleh Digeser)</option>
                    <option value="hard" {{ $g->jenis_hari == 'hard' ? 'selected' : '' }}>Mutlak (Wajib / Tidak Boleh
                        Hari Lain)</option>
                </select>

                <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3.5 rounded-xl shadow-lg transition duration-300 uppercase tracking-wider text-xs">UPDATE</button>
        </form>
    </div>
</div>

{{-- 3. Modal Jadwal Mengajar (Distribusi/Plotting) --}}
<div id="modaljadwal{{ $g->id }}"
    class="fixed inset-0 bg-slate-900/80 z-[99] hidden items-center justify-center p-2 sm:p-4 transition-opacity duration-300">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl h-[90vh] flex flex-col border border-slate-200 overflow-hidden animate-scale-in">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-600 text-white rounded-lg shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-slate-800">{{ $g->nama_guru }}</h3>
                    <p class="text-xs text-slate-500">{{ $g->kode_guru }}</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('modaljadwal{{ $g->id }}')"
                class="text-slate-400 hover:text-red-500 text-3xl leading-none transition-colors">&times;</button>
        </div>

        <div class="flex flex-col lg:flex-row h-full overflow-hidden">
            {{-- Kiri: Area Tabel Distribusi --}}
            <div class="flex-1 flex flex-col h-full border-r border-slate-100 bg-white relative min-w-0">
                <div class="p-4 border-b border-slate-100 bg-white z-20 shrink-0">
                    <div class="relative group">
                        <span
                            class="absolute left-3 top-2.5 text-slate-400 group-focus-within:text-indigo-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input type="text" id="search-{{ $g->id }}" oninput="searchTable({{ $g->id }})"
                            placeholder="Cari Mapel atau Kelas..."
                            class="w-full border border-slate-200 bg-slate-50/50 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/50 focus:bg-white focus:border-indigo-500 outline-none transition-all">
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar p-0 bg-white">
                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 text-left border-b border-slate-100 w-[40%]">Mata Pelajaran</th>
                                <th class="px-4 py-3 text-left border-b border-slate-100 w-[20%]">Kelas</th>
                                <th class="px-4 py-3 text-center border-b border-slate-100 w-[20%]">Jam</th>
                                <th class="px-4 py-3 text-right border-b border-slate-100 w-[20%]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-guru-{{ $g->id }}" class="divide-y divide-slate-50">
                            @foreach($g->jadwals as $jadwal)
                            <tr id="row-jadwal-{{ $jadwal->id }}"
                                class="hover:bg-indigo-50/50 transition duration-150 group">
                                <td class="px-4 py-3 font-bold text-slate-700 align-middle mapel-text">
                                    {{ $jadwal->mapel->nama_mapel ?? '-' }}
                                    <div class="text-[10px] font-normal text-slate-400 mt-0.5">
                                        {{ $jadwal->mapel->kode_mapel ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 align-middle kelas-text font-medium">
                                    {{ $jadwal->kelas->nama_kelas ?? '-' }}</td>
                                <td class="px-4 py-3 text-center align-middle">
                                    <div class="flex flex-col items-center">
                                        <span
                                            class="bg-white text-indigo-700 px-2 py-0.5 rounded text-[10px] font-bold jam-text border border-indigo-100 shadow-sm">{{ $jadwal->jumlah_jam }}
                                            JP</span>
                                        @if($jadwal->status == 'online')
                                        <span
                                            class="status-badge mt-1 bg-amber-100 text-amber-700 px-2 rounded text-[9px] font-bold tracking-wider">ONLINE</span>
                                        @else
                                        <span
                                            class="status-badge mt-1 bg-emerald-100 text-emerald-700 px-2 rounded text-[9px] font-bold tracking-wider">OFFLINE</span>
                                        @endif
                                        <span
                                            class="text-[9px] text-slate-400 mt-0.5 tipe-text uppercase font-semibold tracking-wider">{{ $jadwal->tipe_jam }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right align-middle">
                                    <div
                                        class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button type="button"
                                            onclick="editJadwalInline('{{ $g->id }}', '{{ $jadwal->id }}', '{{ $jadwal->mapel_id }}', '{{ $jadwal->kelas_id }}', '{{ $jadwal->jumlah_jam }}', '{{ $jadwal->tipe_jam }}', '{{ $jadwal->status ?? 'offline' }}')"
                                            class="p-1.5 text-indigo-600 hover:bg-indigo-100 rounded-lg transition"
                                            title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                </path>
                                            </svg>
                                        </button>
                                        <button type="button" onclick="hapusJadwal('{{ $jadwal->id }}', this)"
                                            class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition"
                                            title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                            @if($g->jadwals->isEmpty())
                            <tr class="empty-row">
                                <td colspan="4" class="py-12 text-center text-slate-400 italic bg-slate-50/30">Belum ada
                                    beban mengajar.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Kanan: Form Input Plotting --}}
            <div
                class="w-full lg:w-[380px] bg-slate-50 border-t lg:border-t-0 lg:border-l border-slate-200 flex flex-col h-[40vh] lg:h-full">
                <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                    <div id="form-container-{{ $g->id }}"
                        class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm transition-all duration-300">
                        <div class="flex justify-between items-center mb-5 pb-3 border-b border-slate-100">
                            <h4 id="form-title-{{ $g->id }}"
                                class="font-extrabold text-slate-700 text-xs uppercase tracking-widest flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Input Jadwal
                            </h4>
                            <button id="btn-batal-{{ $g->id }}" type="button" onclick="resetFormJadwal('{{ $g->id }}')"
                                class="hidden text-[10px] font-bold text-red-500 hover:bg-red-50 px-2 py-1 rounded transition uppercase">Batal</button>
                        </div>
                        <form id="form-jadwal-{{ $g->id }}" action="{{ route('guru.simpanJadwal', $g->id) }}"
                            method="POST" onsubmit="handleFormJadwal(event, this, '{{ $g->id }}')">
                            <div id="method-spoof-{{ $g->id }}"></div>
                            <div class="space-y-5">
                                {{-- Mapel --}}
                                <div class="relative custom-select-wrapper" id="wrapper-mapel-{{ $g->id }}">
                                    <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block">Mata
                                        Pelajaran</label>
                                    <input type="hidden" name="mapel_id" id="real-input-mapel-{{ $g->id }}" required>
                                    <button type="button" onclick="toggleCustomDropdown('mapel', '{{ $g->id }}')"
                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-left text-sm flex justify-between items-center hover:bg-white hover:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all">
                                        <span id="display-mapel-{{ $g->id }}" class="text-slate-500 font-medium">Pilih
                                            Mapel...</span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <div id="dropdown-mapel-{{ $g->id }}"
                                        class="hidden absolute z-50 w-full bg-white border border-slate-200 rounded-xl shadow-xl mt-1 max-h-56 overflow-y-auto animate-scale-in">
                                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                                            <input type="text" placeholder="Cari..."
                                                onkeyup="filterCustomDropdown('mapel', '{{ $g->id }}', this)"
                                                class="w-full p-2 text-xs border border-slate-200 rounded-lg bg-slate-50 focus:border-indigo-500 outline-none">
                                        </div>
                                        <div id="list-mapel-{{ $g->id }}" class="p-1">
                                            @foreach($mapels as $m)
                                            <div class="option-item p-2.5 hover:bg-indigo-50 rounded-lg cursor-pointer text-sm border-b border-slate-50 last:border-0 transition-colors"
                                                data-value="{{ $m->id }}" data-label="{{ $m->nama_mapel }}"
                                                onclick="selectCustomOption('mapel', '{{ $g->id }}', '{{ $m->id }}', '{{ $m->nama_mapel }}')">
                                                <div class="font-bold text-slate-700">{{ $m->nama_mapel }}</div>
                                                <div class="text-[10px] text-slate-400">{{ $m->kode_mapel }}</div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Kelas --}}
                                <div class="relative custom-select-wrapper" id="wrapper-kelas-{{ $g->id }}">
                                    <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block">Kelas
                                        Tujuan</label>
                                    <input type="hidden" name="kelas_id" id="real-input-kelas-{{ $g->id }}" required>
                                    <button type="button" onclick="toggleCustomDropdown('kelas', '{{ $g->id }}')"
                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-left text-sm flex justify-between items-center hover:bg-white hover:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all">
                                        <span id="display-kelas-{{ $g->id }}" class="text-slate-500 font-medium">Pilih
                                            Kelas...</span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <div id="dropdown-kelas-{{ $g->id }}"
                                        class="hidden absolute z-50 w-full bg-white border border-slate-200 rounded-xl shadow-xl mt-1 max-h-56 overflow-y-auto animate-scale-in">
                                        <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                                            <input type="text" placeholder="Cari..."
                                                onkeyup="filterCustomDropdown('kelas', '{{ $g->id }}', this)"
                                                class="w-full p-2 text-xs border border-slate-200 rounded-lg bg-slate-50 focus:border-indigo-500 outline-none">
                                        </div>
                                        <div id="list-kelas-{{ $g->id }}" class="p-1 grid grid-cols-2 gap-1">
                                            @foreach($kelases as $k)
                                            <div class="option-item p-2 hover:bg-indigo-50 rounded-lg cursor-pointer text-xs font-bold text-slate-700 text-center border border-slate-100 transition-colors"
                                                data-value="{{ $k->id }}" data-label="{{ $k->nama_kelas }}"
                                                onclick="selectCustomOption('kelas', '{{ $g->id }}', '{{ $k->id }}', '{{ $k->nama_kelas }}')">
                                                {{ $k->nama_kelas }}
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Jam & Tipe --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block">Total
                                            Jam</label>
                                        <div class="relative">
                                            <input type="number" name="jumlah_jam" id="input-jam-{{ $g->id }}"
                                                class="w-full pl-4 pr-8 py-3 bg-slate-50 border border-slate-200 rounded-xl text-center font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 outline-none transition"
                                                min="1" max="10" required>
                                            <span
                                                class="absolute right-3 top-3.5 text-[10px] text-slate-400 font-bold">JP</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block">Tipe</label>
                                        <select name="tipe_jam" id="select-tipe-{{ $g->id }}"
                                            class="w-full px-3 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 outline-none appearance-none cursor-pointer transition">
                                            <option value="single">Single (1x)</option>
                                            <option value="double">Double (2x)</option>
                                            <option value="triple">Triple (3x)</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Status --}}
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-400 uppercase mb-1.5">Pelaksanaan
                                        Kelas</label>
                                    <div class="relative">
                                        <select name="status" id="select-status-{{ $g->id }}"
                                            class="w-full pl-3 pr-8 py-3 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500/20 outline-none appearance-none cursor-pointer transition shadow-sm">
                                            <option value="offline">🏫 OFFLINE (Masuk Jadwal Besar)</option>
                                            <option value="online">💻 ONLINE (Bebas Penjadwalan)</option>
                                        </select>
                                        <div
                                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" id="btn-submit-{{ $g->id }}"
                                    class="w-full py-3.5 bg-slate-900 hover:bg-indigo-600 text-white rounded-xl font-bold text-xs tracking-widest uppercase shadow-lg transition-all duration-300 mt-2">Simpan
                                    Jadwal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection

@push('scripts')
<script>
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const CSRF_TOKEN = csrfMeta ? csrfMeta.content : '';

function searchMainTable() {
    const input = document.getElementById('search-guru-main').value.toLowerCase();
    const rows = document.querySelectorAll('#tbody-guru-main tr[data-filter]');
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
        if (!hasResult && input.length > 0) noResultRow.classList.remove('hidden');
        else noResultRow.classList.add('hidden');
    }
}

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
        if (modalID.includes('modaljadwal')) resetFormJadwal(modalID.replace('modaljadwal', ''));
    }
}

document.addEventListener('click', function(event) {
    if (event.target.classList.contains('fixed') && event.target.id) {
        closeModal(event.target.id);
    }

    if (!event.target.closest('.custom-select-wrapper')) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.custom-select-wrapper').forEach(el => el.style.zIndex = "0");
    }
});

function searchTable(guruId) {
    const filter = document.getElementById('search-' + guruId).value.toLowerCase();
    const rows = document.getElementById('tbody-guru-' + guruId).getElementsByTagName('tr');
    for (let row of rows) {
        if (row.classList.contains('empty-row')) continue;
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    }
}

function toggleCustomDropdown(type, guruId) {
    const wrapper = document.getElementById(`wrapper-${type}-${guruId}`);
    const dropdown = document.getElementById(`dropdown-${type}-${guruId}`);
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

function filterCustomDropdown(type, guruId, input) {
    const filter = input.value.toLowerCase();
    const items = document.getElementById(`list-${type}-${guruId}`).children;
    for (let item of items) {
        const label = item.getAttribute('data-label') || '';
        item.style.display = label.toLowerCase().includes(filter) ? "" : "none";
    }
}

function selectCustomOption(type, guruId, value, label) {
    document.getElementById(`real-input-${type}-${guruId}`).value = value;
    const display = document.getElementById(`display-${type}-${guruId}`);
    display.innerText = label;
    display.classList.remove('text-slate-500');
    display.classList.add('text-slate-800');
    document.getElementById(`dropdown-${type}-${guruId}`).classList.add('hidden');
    document.getElementById(`wrapper-${type}-${guruId}`).style.zIndex = "0";
}

function setCustomDropdownValue(type, guruId, value) {
    const list = document.getElementById(`list-${type}-${guruId}`);
    if (!list) return;
    const option = list.querySelector(`.option-item[data-value="${value}"]`);
    if (option) selectCustomOption(type, guruId, value, option.getAttribute('data-label'));
}

function resetCustomDropdown(type, guruId) {
    const input = document.getElementById(`real-input-${type}-${guruId}`);
    if (input) input.value = '';
    const display = document.getElementById(`display-${type}-${guruId}`);
    if (display) {
        display.innerText = type === 'mapel' ? 'Pilih Mapel...' : 'Pilih Kelas...';
        display.classList.add('text-slate-500');
        display.classList.remove('text-slate-800');
    }
}

function editJadwalInline(guruId, jadwalId, mapelId, kelasId, jam, tipe, status) {
    const container = document.getElementById(`form-container-${guruId}`);
    const title = document.getElementById(`form-title-${guruId}`);
    const form = document.getElementById(`form-jadwal-${guruId}`);
    const btnSubmit = document.getElementById(`btn-submit-${guruId}`);

    container.classList.add('ring-2', 'ring-amber-200');
    title.innerHTML = `<span class="text-amber-600">EDIT DISTRIBUSI</span>`;
    document.getElementById(`btn-batal-${guruId}`).classList.remove('hidden');

    btnSubmit.className =
        "w-full py-3.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold shadow-lg transition mt-2";
    btnSubmit.innerHTML = "UPDATE";

    setCustomDropdownValue('mapel', guruId, mapelId);
    setCustomDropdownValue('kelas', guruId, kelasId);
    document.getElementById(`input-jam-${guruId}`).value = jam;
    document.getElementById(`select-tipe-${guruId}`).value = tipe;

    const selectStatus = document.getElementById(`select-status-${guruId}`);
    if (selectStatus) selectStatus.value = status;

    form.action = `/guru/jadwal/${jadwalId}`;
    form.dataset.mode = 'edit';
    document.getElementById(`method-spoof-${guruId}`).innerHTML = `<input type="hidden" name="_method" value="PUT">`;
}

function resetFormJadwal(guruId) {
    const container = document.getElementById(`form-container-${guruId}`);
    const form = document.getElementById(`form-jadwal-${guruId}`);
    if (!container || !form) return;

    container.classList.remove('ring-2', 'ring-amber-200');
    document.getElementById(`form-title-${guruId}`).innerHTML =
        `<span class="w-2 h-2 rounded-full bg-indigo-500"></span> INPUT JADWAL`;
    document.getElementById(`btn-batal-${guruId}`).classList.add('hidden');

    const btnSubmit = document.getElementById(`btn-submit-${guruId}`);
    btnSubmit.className =
        "w-full py-3.5 bg-slate-900 hover:bg-indigo-600 text-white rounded-xl font-bold text-xs tracking-widest uppercase shadow-lg transition mt-2";
    btnSubmit.innerHTML = "SIMPAN JADWAL";

    form.reset();
    resetCustomDropdown('mapel', guruId);
    resetCustomDropdown('kelas', guruId);
    form.action = `/guru/${guruId}/jadwal`;
    delete form.dataset.mode;
    document.getElementById(`method-spoof-${guruId}`).innerHTML = '';
}

async function handleFormJadwal(e, form, guruId) {
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
            updateTableUI(guruId, json.jadwal, form.dataset.mode === 'edit');
            resetFormJadwal(guruId);
        } else {
            alert(json.message || "Gagal menyimpan.");
        }
    } catch (err) {
        alert("Terjadi kesalahan sistem.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = oldText;
    }
}

function updateTableUI(guruId, jadwal, isEdit) {
    const tbody = document.getElementById(`tbody-guru-${guruId}`);
    const namaMapel = jadwal.mapel?.nama_mapel || '-';
    const kodeMapel = jadwal.mapel?.kode_mapel || '';
    const namaKelas = jadwal.kelas?.nama_kelas || '-';
    const jadwalStatus = jadwal.status || 'offline';

    const badgeHTML = jadwalStatus === 'online' ?
        '<span class="status-badge mt-1 bg-amber-100 text-amber-700 px-2 rounded text-[9px] font-bold tracking-wider">ONLINE</span>' :
        '<span class="status-badge mt-1 bg-emerald-100 text-emerald-700 px-2 rounded text-[9px] font-bold tracking-wider">OFFLINE</span>';

    if (isEdit) {
        const row = document.getElementById(`row-jadwal-${jadwal.id}`);
        if (row) {
            row.querySelector('.mapel-text').innerHTML =
                `${namaMapel} <div class="text-[10px] font-normal text-slate-400 mt-0.5">${kodeMapel}</div>`;
            row.querySelector('.kelas-text').innerText = namaKelas;

            const jamSpan = row.querySelector('.jam-text');
            jamSpan.innerText = jadwal.jumlah_jam + ' JP';
            jamSpan.setAttribute('data-jam', jadwal.jumlah_jam);

            row.querySelector('.tipe-text').innerText = jadwal.tipe_jam;

            const oldBadge = row.querySelector('.status-badge');
            if (oldBadge) oldBadge.outerHTML = badgeHTML;

            const btnEdit = row.querySelector('button[onclick^="editJadwalInline"]');
            btnEdit.setAttribute('onclick',
                `editJadwalInline('${guruId}', '${jadwal.id}', '${jadwal.mapel_id}', '${jadwal.kelas_id}', '${jadwal.jumlah_jam}', '${jadwal.tipe_jam}', '${jadwalStatus}')`
            );

            row.classList.add('bg-amber-100');
            setTimeout(() => row.classList.remove('bg-amber-100'), 1500);
        }
    } else {
        const tr = document.createElement('tr');
        tr.id = `row-jadwal-${jadwal.id}`;
        tr.className = "hover:bg-indigo-50/50 transition duration-150 group";
        tr.innerHTML = `
            <td class="px-4 py-3 font-bold text-slate-700 align-middle mapel-text">${namaMapel}<div class="text-[10px] font-normal text-slate-400 mt-0.5">${kodeMapel}</div></td>
            <td class="px-4 py-3 text-slate-600 align-middle kelas-text font-medium">${namaKelas}</td>
            <td class="px-4 py-3 text-center align-middle">
                <div class="flex flex-col items-center">
                    <span class="bg-white text-indigo-700 px-2 py-0.5 rounded text-[10px] font-bold jam-text border border-indigo-100 shadow-sm" data-jam="${jadwal.jumlah_jam}">${jadwal.jumlah_jam} JP</span>
                    ${badgeHTML}
                    <span class="text-[9px] text-slate-400 mt-0.5 tipe-text uppercase font-semibold tracking-wider">${jadwal.tipe_jam}</span>
                </div>
            </td>
            <td class="px-4 py-3 text-right align-middle">
                <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button type="button" onclick="editJadwalInline('${guruId}', '${jadwal.id}', '${jadwal.mapel_id}', '${jadwal.kelas_id}', '${jadwal.jumlah_jam}', '${jadwal.tipe_jam}', '${jadwalStatus}')" class="p-1.5 text-indigo-600 hover:bg-indigo-100 rounded-lg transition" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </button>
                    <button type="button" onclick="hapusJadwal('${jadwal.id}', this)" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition" title="Hapus">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
            </td>`;
        tbody.appendChild(tr);

        const emptyRow = tbody.querySelector('.empty-row');
        if (emptyRow) emptyRow.remove();
    }
}

async function hapusJadwal(id, btn) {
    if (!confirm("Hapus distribusi ini?")) return;
    try {
        const res = await fetch(`/guru/jadwal/${id}`, {
            method: 'POST',
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