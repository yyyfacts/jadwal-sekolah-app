@extends('layouts.app')

@section('content')
{{-- BACKGROUND AMBIENT (Sama dengan Guru) --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-blue-50 to-slate-50"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-indigo-300/20 rounded-full blur-3xl mix-blend-multiply opacity-70">
    </div>
    <div class="absolute top-20 left-20 w-72 h-72 bg-cyan-300/20 rounded-full blur-3xl mix-blend-multiply opacity-70">
    </div>
</div>

{{-- CONTAINER UTAMA (Full Height minus Navbar padding) --}}
<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 h-[calc(100vh-7rem)] pb-4 pt-2 flex flex-col">

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800 shrink-0 relative z-[70]">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-100 rounded-full text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                <span class="font-bold text-sm">{{ session('success') }}</span>
                @if(session('waktu_komputasi'))
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-md bg-emerald-200/50 text-emerald-700 text-[10px] font-bold uppercase tracking-wider">
                    ⏱️ {{ session('waktu_komputasi') }} Detik
                </span>
                @endif
            </div>
        </div>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700 transition">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-center justify-between p-4 bg-red-50 border border-red-100 rounded-xl shadow-sm text-red-800 shrink-0 relative z-[70]">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-red-100 rounded-full text-red-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </div>
            <span class="font-bold text-sm">{{ session('error') }}</span>
        </div>
        <button @click="show = false" class="text-red-400 hover:text-red-700 transition">&times;</button>
    </div>
    @endif

    {{-- UNIFIED CARD (Tema Persis Bank Data Guru) --}}
    <div
        class="bg-white/80 backdrop-blur-xl rounded-3xl border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col flex-1 overflow-hidden">

        {{-- 1. HEADER SECTION (Z-INDEX 60 AGAR DROPDOWN TIDAK KETUTUPAN TABEL) --}}
        <div class="p-6 border-b border-slate-100 bg-white/50 shrink-0 relative z-[60]">

            {{-- Baris 1: Judul & Action Buttons --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                        <span class="w-2 h-6 bg-indigo-600 rounded-full"></span>
                        Jadwal Pelajaran Terpadu
                    </h1>
                    <p class="text-slate-500 text-sm mt-1 font-medium ml-4">
                        Tahun Ajaran {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <a href="{{ route('jadwal.export') }}"
                        class="flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl shadow-sm text-slate-600 hover:bg-slate-50 hover:text-slate-800 font-bold text-xs uppercase tracking-wider transition-all flex-1 md:flex-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Export Excel
                    </a>

                    <form action="{{ route('jadwal.generate') }}" method="POST" onsubmit="showLoading()"
                        class="flex-1 md:flex-none flex">
                        @csrf
                        <button type="button"
                            onclick="if(confirm('Generate ulang akan menimpa jadwal lama. Lanjut?')) this.form.submit()"
                            class="w-full relative inline-flex items-center justify-center px-6 py-2.5 overflow-hidden font-bold text-white transition-all duration-300 bg-indigo-600 rounded-xl group hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:-translate-y-0.5 text-xs uppercase tracking-wider">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin-slow" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Generate Jadwal
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Baris 2: Search Bar / Filters (Didesain identik dengan input Guru) --}}
            <form action="{{ route('jadwal.index') }}" method="GET"
                class="flex flex-col md:flex-row gap-3 items-end max-w-4xl">
                <div class="w-full md:w-5/12">
                    <label
                        class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider ml-1">Filter
                        Guru</label>
                    <select name="guru_id" id="filter-guru" placeholder="Cari Nama Guru..." class="w-full">
                        <option value="">Semua Guru</option>
                        @foreach($gurusList as $g)
                        <option value="{{ $g->id }}" {{ $reqGuru == $g->id ? 'selected' : '' }}>{{ $g->nama_guru }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full md:w-5/12">
                    <label
                        class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider ml-1">Filter
                        Kelas</label>
                    <select name="kelas_id" id="filter-kelas" placeholder="Cari Kelas..." class="w-full">
                        <option value="">Semua Kelas</option>
                        @foreach($kelassList as $k)
                        <option value="{{ $k->id }}" {{ $reqKelas == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full md:w-2/12 flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-xs transition flex items-center justify-center gap-2 h-[46px] uppercase tracking-widest shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Cari
                    </button>
                    @if($reqGuru || $reqKelas)
                    <a href="{{ route('jadwal.index') }}"
                        class="px-4 bg-white hover:bg-red-50 text-red-500 rounded-xl font-bold text-xs transition border border-slate-200 hover:border-red-200 flex items-center justify-center h-[46px] uppercase tracking-widest shadow-sm">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- 2. TABLE SECTION (SCROLLABLE AREA - Z-INDEX LEBIH RENDAH DARI HEADER) --}}
        <div class="flex-1 overflow-auto custom-scrollbar relative bg-white z-10">
            @if($kelass->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="text-6xl mb-4 opacity-50">🗂️</div>
                <h3 class="text-lg font-bold text-slate-700">Data Tidak Ditemukan</h3>
                <p class="text-slate-400 text-sm mt-1">Silakan ubah filter pencarian Anda atau reset filter.</p>
            </div>
            @else
            <table class="w-full text-xs border-separate border-spacing-0 min-w-[800px]">
                <thead>
                    <tr>
                        <th colspan="{{ 3 + $kelass->count() }}"
                            class="sticky top-0 left-0 z-[50] h-10 bg-slate-800 text-white font-bold text-center uppercase tracking-widest border-b border-slate-700 shadow-md text-[10px]">
                            Data Jadwal Pelajaran @if($reqGuru || $reqKelas) <span class="text-indigo-300 ml-1">(Hasil
                                Filter)</span> @endif
                        </th>
                    </tr>
                    <tr>
                        <th
                            class="sticky top-10 left-0 z-[40] w-12 p-3 bg-slate-50/95 backdrop-blur text-slate-500 font-bold border-r border-b border-slate-200 shadow-[2px_2px_5px_rgba(0,0,0,0.03)] uppercase text-[10px] text-center">
                            HARI</th>
                        <th
                            class="sticky top-10 left-12 z-[40] w-10 p-3 bg-slate-50/95 backdrop-blur text-slate-500 font-bold border-r border-b border-slate-200 shadow-[2px_2px_5px_rgba(0,0,0,0.03)] uppercase text-[10px] text-center">
                            JP</th>
                        <th
                            class="sticky top-10 left-[5.5rem] z-[40] w-28 p-3 bg-slate-50/95 backdrop-blur text-slate-500 font-bold border-r border-b border-slate-200 shadow-[2px_2px_5px_rgba(0,0,0,0.03)] uppercase text-[10px] text-center">
                            WAKTU</th>

                        @foreach($kelass as $kelas)
                        <th
                            class="sticky top-10 z-[30] min-w-[140px] p-3 bg-slate-50/95 backdrop-blur text-slate-700 font-extrabold border-r border-b border-slate-200 text-center tracking-wider text-[11px]">
                            {{ $kelas->nama_kelas }}
                        </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="bg-white">
                    @php
                    $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                    $waktu = [
                    'Senin' => [0=>'07.00-07.45', 1=>'07.45-08.25', 2=>'08.25-09.05', 3=>'09.05-09.45',
                    4=>'10.00-10.40', 5=>'10.40-11.20', 6=>'11.20-12.00', 7=>'12.50-13.30', 8=>'13.30-14.10',
                    9=>'14.10-14.50', 10=>'14.50-15.30'],
                    'Jumat' => [0=>'07.00-07.45', 1=>'07.45-08.20', 2=>'08.20-08.55', 3=>'08.55-09.30',
                    4=>'09.30-10.00', 5=>'10.15-10.45', 6=>'10.45-11.15', 7=>'11.15-11.45', 8=>'12.45-13.15',
                    9=>'13.15-13.45', 10=>'13.45-14.15'],
                    'Default' => [0=>'07.00-07.40', 1=>'07.40-08.20', 2=>'08.20-09.00', 3=>'09.00-09.40',
                    4=>'09.50-10.30', 5=>'10.30-11.10', 6=>'11.10-11.50', 7=>'12.35-13.15', 8=>'13.15-13.55',
                    9=>'13.55-14.35', 10=>'14.35-15.15']
                    ];
                    @endphp

                    @foreach($hariList as $hari)
                    @php
                    $maxJam = 10;
                    $startJam = ($hari == 'Senin' || $hari == 'Jumat') ? 0 : 1;
                    $rowSpanTotal = ($maxJam - $startJam) + 1 + (($hari != 'Jumat') ? 2 : 0);
                    @endphp

                    @for($jam = $startJam; $jam <= $maxJam; $jam++) <tr
                        class="hover:bg-indigo-50/30 transition-colors duration-150 group bg-white">

                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="sticky left-0 z-[30] p-0 bg-white border-r border-b border-slate-200 align-middle text-center shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                            <div
                                class="vertical-text font-black text-slate-300 uppercase tracking-[0.3em] text-[11px] h-full flex items-center justify-center bg-slate-50/50 w-full py-4 group-hover:text-indigo-500 transition-colors">
                                {{ $hari }}
                            </div>
                        </td>
                        @endif

                        <td
                            class="sticky left-12 z-[20] p-2 bg-slate-50 border-r border-b border-slate-100 text-center font-bold text-slate-400 text-[10px] shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                            {{ $jam }}
                        </td>

                        <td
                            class="sticky left-[5.5rem] z-[20] p-2 bg-white border-r border-b border-slate-100 text-center text-[10px] font-mono font-medium text-slate-500 shadow-[4px_0_5px_rgba(0,0,0,0.02)]">
                            @php $w = ($hari == 'Senin') ? $waktu['Senin'][$jam] : (($hari == 'Jumat') ?
                            $waktu['Jumat'][$jam] : $waktu['Default'][$jam]); @endphp
                            {{ $w }}
                        </td>

                        @if($jam == 0)
                        <td colspan="{{ $kelass->count() }}"
                            class="p-2 border-b border-slate-100 bg-indigo-50/60 text-center">
                            <span
                                class="text-[10px] font-bold text-indigo-700 uppercase tracking-widest flex items-center justify-center gap-2">
                                @if($hari == 'Senin') 🇮🇩 UPACARA BENDERA @else 📖 IMTAQ / SENAM @endif
                            </span>
                        </td>
                        @else
                        @foreach($kelass as $kelas)
                        @php $data = $jadwals[$kelas->id][$hari][$jam] ?? null; @endphp
                        <td
                            class="p-1.5 border-r border-b border-slate-100 text-center align-middle h-16 relative {{ $data ? $data['color'] : 'bg-transparent' }} hover:brightness-95 transition-all">
                            @if($data)
                            <div class="flex flex-col justify-center items-center h-full w-full p-1 rounded-md">
                                <span
                                    class="font-extrabold text-slate-800 text-[11px] leading-tight line-clamp-2 mb-0.5">{{ $data['mapel'] }}</span>
                                <span
                                    class="text-[9px] text-slate-600 font-semibold italic bg-white/60 px-1.5 py-0.5 rounded shadow-sm line-clamp-1">{{ Str::limit($data['guru'], 18) }}</span>
                            </div>
                            @else
                            <span class="text-slate-200 text-[9px] font-light">-</span>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr class="bg-amber-50/40">
                            <td
                                class="sticky left-12 z-[20] p-1 border-r border-b border-amber-100 bg-amber-50/90 text-center font-black text-amber-500 text-[10px] shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                                IST</td>
                            <td
                                class="sticky left-[5.5rem] z-[20] p-1 border-r border-b border-amber-100 bg-amber-50/90 text-center text-[10px] font-mono font-medium text-amber-600 shadow-[4px_0_5px_rgba(0,0,0,0.02)]">
                                {{ $jam==4 ? '10.30-10.45' : '13.30-13.50' }}
                            </td>
                            <td colspan="{{ $kelass->count() }}"
                                class="p-1 border-b border-amber-100 text-center font-black text-amber-500 text-[10px] tracking-[0.4em] uppercase">
                                ☕ Istirahat
                            </td>
                        </tr>
                        @endif
                        @endfor
                        <tr>
                            <td colspan="{{ $kelass->count() + 3 }}"
                                class="bg-slate-200/50 h-1 border-b border-slate-200"></td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- 3. FOOTER SECTION (Persis Guru) --}}
        <div
            class="bg-slate-50 border-t border-slate-200 px-6 py-3 flex justify-between items-center shrink-0 rounded-b-3xl relative z-[60]">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sistem Penjadwalan
                Terintegrasi</span>
            <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                    </path>
                </svg>
                Secure Data
            </span>
        </div>
    </div>
</div>

{{-- LOADING OVERLAY (Disesuaikan Temanya) --}}
<div id="loading-overlay"
    class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white p-8 rounded-3xl shadow-2xl text-center max-w-sm mx-4 animate-scale-in border border-white/20">
        <div class="relative w-20 h-20 mx-auto mb-6">
            <div class="absolute inset-0 border-4 border-indigo-50 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin">
            </div>
            <div class="absolute inset-0 flex items-center justify-center text-3xl">⚙️</div>
        </div>
        <h3 class="text-xl font-extrabold text-slate-800">Menyusun Jadwal...</h3>
        <p class="text-slate-500 text-sm mt-2 font-medium">Sistem sedang menghitung dan mendistribusikan durasi mengajar
            agar tidak terjadi bentrok.</p>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<style>
/* Reset Tabel Sticky */
table {
    border-collapse: separate;
    border-spacing: 0;
}

.vertical-text {
    writing-mode: vertical-lr;
    transform: rotate(180deg);
    white-space: nowrap;
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
    border: 2px solid #fff;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* =========================================================
   STYLE TOMSELECT (Dibuat Mirip Input Form Guru)
   ========================================================= */
.ts-control {
    border: 1px solid #e2e8f0 !important;
    /* border-slate-200 */
    border-radius: 0.75rem !important;
    /* rounded-xl */
    padding: 0.75rem 1rem !important;
    /* pl-11 pr-4 py-3 */
    min-height: 46px !important;
    font-size: 0.875rem !important;
    /* text-sm */
    line-height: 1.25rem !important;
    /* leading-5 */
    background-color: #ffffff !important;
    /* bg-white */
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
    /* shadow-sm */
    color: #334155 !important;
    transition: all 0.2s ease !important;
}

.ts-control.focus {
    border-color: #6366f1 !important;
    /* focus:border-indigo-500 */
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2) !important;
    /* focus:ring-indigo-500/20 */
    outline: none !important;
}

.ts-dropdown {
    border-radius: 0.75rem !important;
    border-color: #e2e8f0 !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
    font-size: 0.875rem !important;
    margin-top: 4px !important;
    z-index: 9999 !important;
    /* Pastikan selalu teratas */
}

.ts-control input::placeholder {
    color: #94a3b8 !important;
    /* placeholder-slate-400 */
}

.ts-dropdown .active {
    background-color: #eef2ff !important;
    color: #4f46e5 !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tsConfig = {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    };
    new TomSelect("#filter-guru", tsConfig);
    new TomSelect("#filter-kelas", tsConfig);
});

function showLoading() {
    document.getElementById('loading-overlay').classList.remove('hidden');
}
</script>
@endpush