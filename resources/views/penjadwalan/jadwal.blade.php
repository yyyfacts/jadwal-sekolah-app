@extends('layouts.app')

@section('content')
{{-- BACKGROUND AMBIENT (Sama seperti halaman Guru) --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-blue-50 to-slate-50"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-indigo-300/20 rounded-full blur-3xl mix-blend-multiply opacity-70">
    </div>
    <div class="absolute top-20 left-20 w-72 h-72 bg-cyan-300/20 rounded-full blur-3xl mix-blend-multiply opacity-70">
    </div>
</div>

<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 h-[calc(100vh-7rem)] pb-4 pt-2 flex flex-col">

    {{-- FLASH MESSAGE (Model Melayang) --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800 shrink-0 relative z-50">
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
        class="mb-4 flex items-center justify-between p-4 bg-red-50 border border-red-100 rounded-xl shadow-sm text-red-800 shrink-0 relative z-50">
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

    {{-- UNIFIED CARD BESAR --}}
    <div
        class="bg-white/80 backdrop-blur-xl rounded-3xl border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex flex-col flex-1 overflow-hidden relative z-10">

        {{-- 1. HEADER & FILTER SECTION (Digabung agar rapi) --}}
        <div class="p-6 border-b border-slate-100 bg-white/50 shrink-0 z-20">

            {{-- Top Header: Judul & Tombol Aksi --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                        <span class="w-2 h-6 bg-indigo-600 rounded-full"></span>
                        Jadwal Pelajaran Terpadu
                    </h1>
                    <p class="text-slate-500 text-sm mt-1 font-medium ml-4">
                        Tahun Ajaran {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('jadwal.export') }}"
                        class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 px-4 py-2.5 rounded-xl shadow-sm font-bold transition flex items-center gap-2 text-xs uppercase tracking-wider">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Export Excel
                    </a>

                    <form action="{{ route('jadwal.generate') }}" method="POST" onsubmit="showLoading()">
                        @csrf
                        <button type="button"
                            onclick="if(confirm('Generate ulang akan menimpa jadwal lama. Lanjut?')) this.form.submit()"
                            class="relative inline-flex items-center justify-center px-6 py-2.5 overflow-hidden font-bold text-white transition-all duration-300 bg-indigo-600 rounded-xl group hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:-translate-y-0.5 text-xs uppercase tracking-wider">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                    </path>
                                </svg>
                                Auto Generate AI
                            </span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Bottom Header: Searchable Filters --}}
            <div class="mt-5 pt-5 border-t border-slate-100/60">
                <form action="{{ route('jadwal.index') }}" method="GET"
                    class="flex flex-col sm:flex-row gap-3 items-end">
                    <div class="flex-1 w-full min-w-[200px]">
                        <label
                            class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider ml-1">Filter
                            Guru</label>
                        <select name="guru_id" id="filter-guru" class="w-full text-sm">
                            <option value="">-- Ketik & Cari Guru --</option>
                            @foreach($gurusList as $g)
                            <option value="{{ $g->id }}" {{ $reqGuru == $g->id ? 'selected' : '' }}>{{ $g->nama_guru }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1 w-full min-w-[200px]">
                        <label
                            class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider ml-1">Filter
                            Kelas</label>
                        <select name="kelas_id" id="filter-kelas" class="w-full text-sm">
                            <option value="">-- Ketik & Cari Kelas --</option>
                            @foreach($kelassList as $k)
                            <option value="{{ $k->id }}" {{ $reqKelas == $k->id ? 'selected' : '' }}>
                                {{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex gap-2 w-full sm:w-auto">
                        <button type="submit"
                            class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2 uppercase tracking-wider h-[42px]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Cari
                        </button>

                        @if($reqGuru || $reqKelas)
                        <a href="{{ route('jadwal.index') }}"
                            class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2.5 rounded-xl text-xs font-bold shadow-sm transition flex items-center h-[42px] uppercase tracking-wider">
                            Reset
                        </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- 2. TABLE SECTION (AREA SCROLL) --}}
        @if($kelass->isEmpty())
        <div class="flex-1 flex flex-col items-center justify-center py-20 bg-slate-50/50">
            <div class="text-6xl mb-4 opacity-50">🗂️</div>
            <h3 class="text-lg font-bold text-slate-600">Data Tidak Ditemukan</h3>
            <p class="text-slate-400 text-sm mt-1">Silakan sesuaikan filter pencarian Anda.</p>
        </div>
        @else
        <div class="flex-1 overflow-auto custom-scrollbar relative bg-white">
            <table class="w-full text-xs border-separate border-spacing-0 min-w-[800px]">
                <thead>
                    {{-- Header Row 1: Judul Tabel --}}
                    <tr>
                        <th colspan="{{ 3 + $kelass->count() }}"
                            class="sticky top-0 left-0 z-[30] h-12 bg-slate-800 text-white font-bold text-center uppercase tracking-widest border-b border-slate-700 shadow-md text-xs">
                            Data Jadwal Pelajaran
                            @if($reqGuru || $reqKelas) <span class="text-indigo-300 ml-1">(Hasil Filter)</span> @endif
                        </th>
                    </tr>

                    {{-- Header Row 2: Kolom --}}
                    <tr>
                        <th
                            class="sticky top-12 left-0 z-[25] w-12 p-3 bg-slate-100/90 backdrop-blur text-slate-600 font-bold border-r border-b border-slate-200 shadow-[2px_2px_5px_rgba(0,0,0,0.03)] uppercase">
                            HARI</th>
                        <th
                            class="sticky top-12 left-12 z-[25] w-10 p-3 bg-slate-100/90 backdrop-blur text-slate-600 font-bold border-r border-b border-slate-200 shadow-[2px_2px_5px_rgba(0,0,0,0.03)] uppercase">
                            JP</th>
                        <th
                            class="sticky top-12 left-[5.5rem] z-[25] w-28 p-3 bg-slate-100/90 backdrop-blur text-slate-600 font-bold border-r border-b border-slate-200 shadow-[2px_2px_5px_rgba(0,0,0,0.03)] uppercase">
                            WAKTU</th>

                        @foreach($kelass as $kelas)
                        <th
                            class="sticky top-12 z-[20] min-w-[160px] p-3 bg-slate-50/90 backdrop-blur text-slate-800 font-extrabold border-r border-b border-slate-200 text-center tracking-wider">
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
                    $baseRows = ($maxJam - $startJam) + 1;
                    $istirahatRows = ($hari != 'Jumat') ? 2 : 0;
                    $rowSpanTotal = $baseRows + $istirahatRows;
                    @endphp

                    @for($jam = $startJam; $jam <= $maxJam; $jam++) <tr
                        class="hover:bg-slate-50 transition-colors duration-150 group">

                        {{-- Kolom HARI (Pojok Kiri) --}}
                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="sticky left-0 z-[15] p-0 bg-white border-r border-b border-slate-200 align-middle text-center shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                            <div
                                class="vertical-text font-black text-slate-400 uppercase tracking-[0.3em] text-xs h-full flex items-center justify-center bg-slate-50/50 w-full py-4 group-hover:text-indigo-500 transition-colors">
                                {{ $hari }}
                            </div>
                        </td>
                        @endif

                        {{-- Kolom JP --}}
                        <td
                            class="sticky left-12 z-[10] p-2 bg-slate-50 border-r border-b border-slate-100 text-center font-bold text-slate-400 text-[10px] shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                            {{ $jam }}
                        </td>

                        {{-- Kolom WAKTU --}}
                        <td
                            class="sticky left-[5.5rem] z-[10] p-2 bg-white border-r border-b border-slate-100 text-center text-[10px] font-mono font-medium text-slate-500 shadow-[4px_0_5px_rgba(0,0,0,0.02)]">
                            @php
                            if($hari == 'Senin') $w = $waktu['Senin'][$jam] ?? '-';
                            elseif($hari == 'Jumat') $w = $waktu['Jumat'][$jam] ?? '-';
                            else $w = $waktu['Default'][$jam] ?? '-';
                            @endphp
                            {{ $w }}
                        </td>

                        {{-- ISI DATA KELAS --}}
                        @if($jam == 0)
                        <td colspan="{{ $kelass->count() }}"
                            class="p-2 border-b border-slate-100 bg-indigo-50/60 text-center text-xs font-bold text-indigo-600 tracking-widest uppercase">
                            <div class="flex items-center justify-center gap-2">
                                @if($hari == 'Senin') 🇮🇩 <span>Upacara Bendera</span>
                                @elseif($hari == 'Jumat') 📖 <span>IMTAQ / Senam Pagi</span>
                                @else 📖 <span>Literasi</span> @endif
                            </div>
                        </td>
                        @else
                        @foreach($kelass as $kelas)
                        @php $data = $jadwals[$kelas->id][$hari][$jam] ?? null; @endphp
                        <td
                            class="p-1 border-r border-b border-slate-100 text-center align-middle h-16 relative {{ $data ? $data['color'] : 'bg-transparent' }} hover:brightness-95 transition-all">
                            @if($data)
                            <div class="flex flex-col justify-center items-center h-full w-full p-1 rounded-md">
                                <span
                                    class="font-bold text-slate-800 text-[11px] leading-tight line-clamp-1 mb-0.5">{{ $data['mapel'] }}</span>
                                <span
                                    class="text-[9px] text-slate-500 font-medium leading-tight line-clamp-1 italic bg-white/50 px-1.5 py-0.5 rounded">{{ Str::limit($data['guru'], 18) }}</span>
                            </div>
                            @else
                            <span class="text-slate-200 text-[8px] font-light">-</span>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        {{-- Baris Istirahat --}}
                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr class="bg-amber-50/50">
                            <td
                                class="sticky left-12 z-[10] p-1 border-r border-b border-amber-100 bg-amber-50/80 text-center font-bold text-amber-600 text-[10px] shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                                IST</td>
                            <td
                                class="sticky left-[5.5rem] z-[10] p-1 border-r border-b border-amber-100 bg-amber-50/30 text-center text-[10px] font-mono font-medium text-amber-600 shadow-[4px_0_5px_rgba(0,0,0,0.02)]">
                                {{ $jam==4 ? '10.30-10.45' : '13.30-13.50' }}</td>
                            <td colspan="{{ $kelass->count() }}"
                                class="p-1 border-b border-amber-100 text-center font-bold text-amber-500 text-[10px] tracking-[0.3em] uppercase">
                                ☕ Istirahat</td>
                        </tr>
                        @endif
                        @endfor

                        {{-- Garis Pemisah Hari --}}
                        <tr>
                            <td colspan="{{ $kelass->count() + 3 }}"
                                class="bg-slate-200/50 h-1 border-b border-slate-200"></td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- 3. FOOTER SECTION --}}
        <div
            class="bg-slate-50/80 border-t border-slate-200 px-6 py-3 flex justify-between items-center shrink-0 rounded-b-3xl">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sistem Penjadwalan
                Terintegrasi</span>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                    </path>
                </svg>
                Secure Data
            </span>
        </div>

    </div>
</div>

{{-- LOADING OVERLAY --}}
<div id="loading-overlay"
    class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white p-8 rounded-3xl shadow-2xl text-center max-w-sm mx-4 animate-scale-in border border-white/20">
        <div class="relative w-20 h-20 mx-auto mb-6">
            <div class="absolute inset-0 border-4 border-indigo-50 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin">
            </div>
            <div class="absolute inset-0 flex items-center justify-center text-3xl">🤖</div>
        </div>
        <h3 class="text-xl font-extrabold text-slate-800">Menyusun Jadwal...</h3>
        <p class="text-slate-500 text-sm mt-2 font-medium">AI sedang memproses jutaan kombinasi agar tidak ada guru yang
            bentrok.</p>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<style>
/* Reset Spesifik untuk Tabel Sticky agar tidak goyang */
table {
    border-collapse: separate;
    border-spacing: 0;
}

.vertical-text {
    writing-mode: vertical-lr;
    transform: rotate(180deg);
    white-space: nowrap;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 8px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 8px;
    border: 3px solid #f8fafc;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

@media (min-width: 768px) {
    .vertical-text {
        writing-mode: horizontal-tb;
        transform: none;
    }
}

/* Modifikasi tampilan TomSelect agar mewah (Glassmorphism style) */
.ts-control {
    border-radius: 0.75rem !important;
    border-color: #e2e8f0 !important;
    background-color: #f8fafc !important;
    padding: 0.6rem 1rem !important;
    min-height: 42px;
    font-weight: 600;
}

.ts-control.focus {
    border-color: #818cf8 !important;
    background-color: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.2) !important;
}

.ts-dropdown {
    border-radius: 0.75rem !important;
    border-color: #e2e8f0 !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    overflow: hidden;
    font-weight: 500;
}

.ts-dropdown .active {
    background-color: #eef2ff !important;
    color: #4f46e5 !important;
    font-weight: 700;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect("#filter-guru", {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });
    new TomSelect("#filter-kelas", {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });
});

function showLoading() {
    document.getElementById('loading-overlay').classList.remove('hidden');
}
</script>
@endpush