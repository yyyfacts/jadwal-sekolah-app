@extends('layouts.app')

@section('content')
{{-- BACKGROUND AMBIENT --}}
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f8fafc]">
    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-slate-200/50 to-slate-50"></div>
</div>

<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 h-[calc(100vh-6rem)] pb-4 pt-4 flex flex-col">

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800 shrink-0 relative z-[90]">
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

    {{-- MAIN CARD UI (Sesuai Referensi Gambar) --}}
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl flex flex-col flex-1 overflow-hidden">

        {{-- 1. HEADER SECTION --}}
        <div class="p-6 md:px-8 border-b border-slate-100 shrink-0">

            {{-- Top: Judul & Action Buttons --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-1.5 h-8 bg-slate-800 rounded-full"></div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-extrabold text-slate-800 tracking-tight">
                            Jadwal Pelajaran Terpadu
                        </h1>
                        <p class="text-slate-500 text-xs md:text-sm mt-0.5 font-medium">
                            Tahun Ajaran {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }} Semester Genap
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('jadwal.export') }}"
                        class="flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-slate-300 rounded-xl text-slate-700 hover:bg-slate-50 font-bold text-xs uppercase tracking-wider transition-all shadow-sm">
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
                            class="relative inline-flex items-center justify-center px-6 py-2.5 overflow-hidden font-bold text-white transition-all duration-300 bg-slate-800 rounded-xl hover:bg-slate-900 shadow-md text-xs uppercase tracking-wider">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            {{-- Bottom: Filters (Desain Outline Clean) --}}
            <form action="{{ route('jadwal.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-[35%] relative">
                    <label
                        class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider ml-1">Filter
                        Guru</label>
                    <select name="guru_id" id="filter-guru" placeholder="Semua Guru" autocomplete="off">
                        <option value="">Semua Guru</option>
                        @foreach($gurusList as $g)
                        <option value="{{ $g->id }}" {{ $reqGuru == $g->id ? 'selected' : '' }}>{{ $g->nama_guru }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full md:w-[35%] relative">
                    <label
                        class="block text-[10px] font-bold text-slate-500 mb-1.5 uppercase tracking-wider ml-1">Filter
                        Kelas</label>
                    <select name="kelas_id" id="filter-kelas" placeholder="Semua Kelas" autocomplete="off">
                        <option value="">Semua Kelas</option>
                        @foreach($kelassList as $k)
                        <option value="{{ $k->id }}" {{ $reqKelas == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full md:w-[30%] flex gap-2">
                    {{-- Tombol Cari didesain putih outline dengan text abu-abu seperti gambar --}}
                    <button type="submit"
                        class="flex-1 bg-white border border-slate-300 hover:bg-slate-50 text-slate-500 rounded-xl text-xs font-semibold shadow-sm transition flex items-center justify-start px-4 gap-2 h-[42px]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Cari
                    </button>
                    @if($reqGuru || $reqKelas)
                    <a href="{{ route('jadwal.index') }}" title="Reset Filter"
                        class="bg-red-50 hover:bg-red-100 text-red-500 border border-red-100 px-4 rounded-xl text-xs font-bold shadow-sm transition flex items-center justify-center h-[42px]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- 2. TABLE SECTION (Desain Dua Lapis Header & Block Cell) --}}
        <div class="flex-1 overflow-auto custom-scrollbar relative bg-white">
            @if($kelass->isEmpty())
            <div class="flex flex-col items-center justify-center h-full py-20 text-center">
                <div class="text-6xl mb-4 opacity-50">🗂️</div>
                <h3 class="text-lg font-bold text-slate-600">Data Tidak Ditemukan</h3>
                <p class="text-slate-400 text-sm mt-1">Silakan sesuaikan filter pencarian Anda.</p>
            </div>
            @else
            <table class="w-full border-collapse min-w-[900px]">
                <thead class="sticky top-0 z-[40] shadow-sm">
                    {{-- Row 1: Biru Gelap --}}
                    <tr class="bg-slate-800 text-white">
                        <th colspan="3" class="h-10 border-r border-slate-700/50 bg-[#1e293b]"></th>
                        @foreach($kelass as $kelas)
                        <th class="h-10 px-2 border-r border-slate-700/50 text-center font-bold text-xs tracking-wider">
                            {{ $kelas->nama_kelas }}
                        </th>
                        @endforeach
                    </tr>
                    {{-- Row 2: Putih Tulisan Abu-Abu --}}
                    <tr class="bg-white text-slate-500 border-b border-slate-200">
                        <th
                            class="h-10 w-16 border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest">
                            HARI</th>
                        <th
                            class="h-10 w-12 border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest">
                            JP</th>
                        <th
                            class="h-10 w-28 border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest">
                            WAKTU</th>
                        @foreach($kelass as $kelas)
                        <th
                            class="h-10 min-w-[150px] border-r border-b border-slate-200 text-center font-bold text-[10px] tracking-widest">
                            {{ $kelas->nama_kelas }}
                        </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="bg-slate-50/30">
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
                        class="hover:bg-white transition-colors duration-150">
                        {{-- Kolom Hari (Sticky) --}}
                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="sticky left-0 z-[20] p-0 bg-white border-r border-b border-slate-200 align-middle text-center">
                            <div
                                class="font-bold text-slate-500 uppercase tracking-widest text-[11px] h-full flex items-center justify-center py-4 px-2">
                                {{ $hari }}
                            </div>
                        </td>
                        @endif

                        {{-- Kolom JP & Waktu --}}
                        <td
                            class="sticky left-16 z-[10] p-2 bg-white border-r border-b border-slate-100 text-center font-semibold text-slate-500 text-[10px]">
                            {{ $jam }}
                        </td>
                        <td
                            class="sticky left-[7rem] z-[10] p-2 bg-white border-r border-b border-slate-100 text-center text-[10px] font-mono font-medium text-slate-500">
                            @php $w = ($hari == 'Senin') ? $waktu['Senin'][$jam] : (($hari == 'Jumat') ?
                            $waktu['Jumat'][$jam] : $waktu['Default'][$jam]); @endphp
                            {{ $w }}
                        </td>

                        {{-- Kolom Kelas / Sel Jadwal --}}
                        @if($jam == 0)
                        <td colspan="{{ $kelass->count() }}"
                            class="p-1 border-b border-slate-100 align-middle bg-slate-50">
                            <div
                                class="w-full h-full bg-white border border-slate-200 rounded-lg flex items-center justify-center p-2">
                                <span class="text-xs font-bold text-slate-600 uppercase tracking-widest">
                                    @if($hari == 'Senin') 🇮🇩 UPACARA BENDERA @else 📖 IMTAQ / SENAM @endif
                                </span>
                            </div>
                        </td>
                        @else
                        @foreach($kelass as $kelas)
                        @php $data = $jadwals[$kelas->id][$hari][$jam] ?? null; @endphp
                        <td class="p-1.5 border-r border-b border-slate-100 text-center align-middle h-16">
                            @if($data)
                            {{-- Desain Inner Block (Rounded) --}}
                            <div
                                class="w-full h-full rounded-md flex flex-col justify-center items-center p-1.5 shadow-sm border border-black/5 {{ $data['color'] ?? 'bg-indigo-600 text-white' }} hover:brightness-95 transition-all">
                                <span
                                    class="font-bold text-[11px] leading-tight line-clamp-1 mb-0.5">{{ $data['mapel'] }}</span>
                                <span
                                    class="text-[9px] font-medium opacity-90 line-clamp-1 truncate w-full">{{ $data['guru'] }}</span>
                            </div>
                            @else
                            {{-- Sel Kosong --}}
                            <div
                                class="w-full h-full rounded-md bg-transparent border border-dashed border-slate-200 flex items-center justify-center">
                                <span class="text-slate-300 text-[9px] font-light">-</span>
                            </div>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        {{-- Row Istirahat --}}
                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr>
                            <td
                                class="sticky left-16 z-[10] p-1 border-r border-b border-slate-200 bg-slate-100/50 text-center font-bold text-slate-400 text-[10px]">
                                IST</td>
                            <td
                                class="sticky left-[7rem] z-[10] p-1 border-r border-b border-slate-200 bg-slate-100/50 text-center text-[10px] font-mono font-medium text-slate-400">
                                {{ $jam==4 ? '10.30-10.45' : '13.30-13.50' }}
                            </td>
                            <td colspan="{{ $kelass->count() }}"
                                class="p-1.5 border-b border-slate-200 bg-slate-100/50 align-middle">
                                <div
                                    class="w-full h-full bg-slate-200/50 rounded-lg flex items-center justify-center p-1">
                                    <span class="font-bold text-slate-400 text-[10px] tracking-[0.4em] uppercase">☕
                                        Istirahat</span>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endfor
                        {{-- Pemisah Antar Hari --}}
                        <tr>
                            <td colspan="{{ $kelass->count() + 3 }}"
                                class="bg-slate-200/80 h-1.5 border-b border-slate-300"></td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- 3. FOOTER SECTION --}}
        <div class="bg-white border-t border-slate-200 px-6 py-3.5 flex justify-between items-center shrink-0">
            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">SISTEM PENJADWALAN
                TERINTEGRASI</span>
            <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                    </path>
                </svg>
                SECURE DATA
            </span>
        </div>
    </div>
</div>

{{-- LOADING OVERLAY --}}
<div id="loading-overlay"
    class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white p-8 rounded-3xl shadow-2xl text-center max-w-sm mx-4 animate-scale-in border border-white/20">
        <div class="relative w-20 h-20 mx-auto mb-6">
            <div class="absolute inset-0 border-4 border-slate-100 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-slate-800 border-t-transparent rounded-full animate-spin">
            </div>
            <div class="absolute inset-0 flex items-center justify-center text-3xl">⚙️</div>
        </div>
        <h3 class="text-xl font-extrabold text-slate-800">Menyusun Jadwal...</h3>
        <p class="text-slate-500 text-sm mt-2 font-medium">Sistem sedang mendistribusikan durasi mengajar agar tidak
            terjadi bentrok.</p>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<style>
/* Tabel Setup Minimalis */
table {
    border-collapse: separate;
    border-spacing: 0;
}

.vertical-text {
    writing-mode: vertical-lr;
    transform: rotate(180deg);
    white-space: nowrap;
}

/* Custom Scrollbar Elegan */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 8px;
    border: 2px solid #f8fafc;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* =========================================================
   STYLE TOMSELECT (Bypass Overflow + Desain Clean)
   ========================================================= */
.ts-control {
    border: 1px solid #e2e8f0 !important;
    border-radius: 0.5rem !important;
    /* rounded-lg */
    padding: 0.5rem 1rem !important;
    min-height: 42px !important;
    font-size: 0.875rem !important;
    background-color: #ffffff !important;
    color: #334155 !important;
    box-shadow: none !important;
    transition: all 0.2s ease !important;
}

.ts-control.focus {
    border-color: #94a3b8 !important;
    box-shadow: 0 0 0 2px rgba(241, 245, 249, 1) !important;
    outline: none !important;
}

/* MASTER KEY: Mengatur Dropdown agar melayang mutlak di body */
.ts-dropdown {
    border-radius: 0.5rem !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
    font-size: 0.875rem !important;
    z-index: 99999 !important;
    /* MENGALAHKAN SEGALA ELEMEN DI LAYAR */
    margin-top: 4px !important;
}

.ts-dropdown .active {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
}

.ts-control input::placeholder {
    color: #94a3b8 !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tsConfig = {
        create: false,
        dropdownParent: "body", // <--- OBAT SAKIT KEPALA 5 JAM: Paksa dropdown render di luar Card!
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