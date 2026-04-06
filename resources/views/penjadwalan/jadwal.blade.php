@extends('layouts.app')

@section('content')
{{-- BACKGROUND AMBIENT --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-blue-50 to-slate-50"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-indigo-300/20 rounded-full blur-3xl mix-blend-multiply opacity-70">
    </div>
    <div class="absolute top-20 left-20 w-72 h-72 bg-cyan-300/20 rounded-full blur-3xl mix-blend-multiply opacity-70">
    </div>
</div>

{{-- CONTAINER UTAMA (Lebih fleksibel di mobile) --}}
<div
    class="w-full mx-auto px-3 sm:px-6 lg:px-8 min-h-[calc(100vh-5rem)] md:h-[calc(100vh-7rem)] pb-4 pt-4 flex flex-col">

    {{-- FLASH MESSAGE (SUCCESS) --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800 shrink-0 relative z-50 gap-3 sm:gap-0">
        <div class="flex items-start sm:items-center gap-3">
            <div class="p-2 bg-emerald-100 rounded-full text-emerald-600 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                <span class="font-bold text-sm">{{ session('success') }}</span>
                @if(session('waktu_komputasi'))
                <span
                    class="inline-flex items-center px-2.5 py-1 rounded-md bg-emerald-200/50 text-emerald-700 text-[10px] font-bold uppercase tracking-wider w-fit">
                    ⏱️ {{ session('waktu_komputasi') }} Detik
                </span>
                @endif
            </div>
        </div>
        <button @click="show = false"
            class="text-emerald-400 hover:text-emerald-700 transition absolute top-4 right-4 sm:static">&times;</button>
    </div>
    @endif

    {{-- FLASH MESSAGE (ERROR) --}}
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-center justify-between p-4 bg-red-50 border border-red-100 rounded-xl shadow-sm text-red-800 shrink-0 relative z-50">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-red-100 rounded-full text-red-600 shrink-0">
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
        class="bg-white/90 backdrop-blur-xl rounded-2xl md:rounded-3xl border border-slate-200/60 shadow-xl flex flex-col flex-1 overflow-hidden relative z-10">

        {{-- 1. HEADER & FILTER SECTION --}}
        <div class="p-4 md:p-6 border-b border-slate-100 bg-white/50 shrink-0 z-20">

            {{-- Top Header (Responsive Flex) --}}
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 md:gap-6">
                <div>
                    <h1
                        class="text-xl md:text-2xl font-extrabold text-slate-800 tracking-tight flex items-center gap-2">
                        <span class="w-1.5 md:w-2 h-5 md:h-6 bg-indigo-600 rounded-full"></span>
                        Jadwal Pelajaran Terpadu
                    </h1>
                    <p class="text-slate-500 text-xs md:text-sm mt-1 font-medium ml-3.5 md:ml-4">
                        Tahun Ajaran {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }}
                    </p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full lg:w-auto">
                    <a href="{{ route('jadwal.export') }}"
                        class="justify-center bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 px-4 py-2.5 rounded-xl shadow-sm font-bold transition flex items-center gap-2 text-xs uppercase tracking-wider w-full sm:w-auto">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Export Excel
                    </a>

                    <form action="{{ route('jadwal.generate') }}" method="POST" onsubmit="showLoading()"
                        class="w-full sm:w-auto">
                        @csrf
                        <button type="button"
                            onclick="if(confirm('Generate ulang akan menimpa jadwal lama. Lanjut?')) this.form.submit()"
                            class="w-full sm:w-auto relative inline-flex items-center justify-center px-6 py-2.5 overflow-hidden font-bold text-white transition-all duration-300 bg-indigo-600 rounded-xl group hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:-translate-y-0.5 text-xs uppercase tracking-wider">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin-slow shrink-0" fill="none" stroke="currentColor"
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

            {{-- Bottom Header: Searchable Filters --}}
            <div class="mt-4 md:mt-5 pt-4 md:pt-5 border-t border-slate-100 w-full lg:max-w-4xl">
                <form action="{{ route('jadwal.index') }}" method="GET"
                    class="flex flex-col sm:flex-row gap-3 md:gap-4 items-end">

                    <div class="w-full sm:w-1/3">
                        <label
                            class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider ml-1">Filter
                            Guru</label>
                        <select name="guru_id" id="filter-guru" placeholder="Cari Guru..." autocomplete="off">
                            <option value="">Semua Guru</option>
                            @foreach($gurusList as $g)
                            <option value="{{ $g->id }}" {{ $reqGuru == $g->id ? 'selected' : '' }}>{{ $g->nama_guru }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full sm:w-1/3">
                        <label
                            class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider ml-1">Filter
                            Kelas</label>
                        <select name="kelas_id" id="filter-kelas" placeholder="Cari Kelas..." autocomplete="off">
                            <option value="">Semua Kelas</option>
                            @foreach($kelassList as $k)
                            <option value="{{ $k->id }}" {{ $reqKelas == $k->id ? 'selected' : '' }}>
                                {{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="w-full sm:w-1/3 flex gap-2">
                        <button type="submit"
                            class="flex-1 bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-sm transition flex items-center justify-center gap-2 uppercase tracking-wider h-[42px]">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Cari
                        </button>

                        @if($reqGuru || $reqKelas)
                        <a href="{{ route('jadwal.index') }}"
                            class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 px-4 py-2.5 rounded-xl text-xs font-bold shadow-sm transition flex items-center justify-center h-[42px] uppercase tracking-wider">
                            Reset
                        </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- 2. TABLE SECTION --}}
        @if($kelass->isEmpty())
        <div class="flex-1 flex flex-col items-center justify-center py-12 md:py-20 bg-slate-50/50 px-4 text-center">
            <div class="text-5xl md:text-6xl mb-4 opacity-50">🗂️</div>
            <h3 class="text-base md:text-lg font-bold text-slate-600">Data Tidak Ditemukan</h3>
            <p class="text-slate-400 text-xs md:text-sm mt-1">Silakan sesuaikan filter pencarian Anda.</p>
        </div>
        @else
        <div class="flex-1 overflow-auto custom-scrollbar relative bg-white">
            <table class="w-full text-xs border-separate border-spacing-0 min-w-[600px] md:min-w-[800px]">
                <thead>
                    <tr>
                        <th colspan="{{ 3 + $kelass->count() }}"
                            class="sticky top-0 left-0 z-[30] h-10 md:h-12 bg-slate-800 text-white font-bold text-center uppercase tracking-widest border-b border-slate-700 shadow-md text-[10px] md:text-xs">
                            Data Jadwal Pelajaran
                            @if($reqGuru || $reqKelas) <span class="text-indigo-300 ml-1">(Hasil Filter)</span> @endif
                        </th>
                    </tr>
                    <tr>
                        <th
                            class="sticky top-10 md:top-12 left-0 z-[25] w-8 md:w-12 p-2 md:p-3 bg-slate-100/95 backdrop-blur text-slate-600 font-bold border-r border-b border-slate-200 shadow-[2px_0_5px_rgba(0,0,0,0.05)] uppercase text-[10px] md:text-xs">
                            HARI</th>
                        <th
                            class="sticky top-10 md:top-12 left-8 md:left-12 z-[25] w-8 md:w-10 p-2 md:p-3 bg-slate-100/95 backdrop-blur text-slate-600 font-bold border-r border-b border-slate-200 shadow-[2px_0_5px_rgba(0,0,0,0.05)] uppercase text-[10px] md:text-xs">
                            JP</th>
                        <th
                            class="sticky top-10 md:top-12 left-[4rem] md:left-[5.5rem] z-[25] w-20 md:w-28 p-2 md:p-3 bg-slate-100/95 backdrop-blur text-slate-600 font-bold border-r border-b border-slate-200 shadow-[2px_0_5px_rgba(0,0,0,0.05)] uppercase text-[10px] md:text-xs">
                            WAKTU</th>

                        @foreach($kelass as $kelas)
                        <th
                            class="sticky top-10 md:top-12 z-[20] min-w-[120px] md:min-w-[140px] p-2 md:p-3 bg-slate-50/95 backdrop-blur text-slate-800 font-extrabold border-r border-b border-slate-200 text-center tracking-wider text-[10px] md:text-xs">
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

                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="sticky left-0 z-[15] p-0 bg-white border-r border-b border-slate-200 align-middle text-center shadow-[2px_0_5px_rgba(0,0,0,0.03)]">
                            <div
                                class="vertical-text font-black text-slate-400 uppercase tracking-[0.2em] md:tracking-[0.3em] text-[10px] md:text-xs h-full flex items-center justify-center bg-slate-50/50 w-full py-4 group-hover:text-indigo-500 transition-colors">
                                {{ $hari }}
                            </div>
                        </td>
                        @endif

                        <td
                            class="sticky left-8 md:left-12 z-[10] p-1.5 md:p-2 bg-slate-50 border-r border-b border-slate-100 text-center font-bold text-slate-400 text-[9px] md:text-[10px] shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                            {{ $jam }}
                        </td>

                        <td
                            class="sticky left-[4rem] md:left-[5.5rem] z-[10] p-1 md:p-2 bg-white border-r border-b border-slate-100 text-center text-[9px] md:text-[10px] font-mono font-medium text-slate-500 shadow-[4px_0_5px_rgba(0,0,0,0.03)]">
                            @php
                            if($hari == 'Senin') $w = $waktu['Senin'][$jam] ?? '-';
                            elseif($hari == 'Jumat') $w = $waktu['Jumat'][$jam] ?? '-';
                            else $w = $waktu['Default'][$jam] ?? '-';
                            @endphp
                            {{ $w }}
                        </td>

                        @if($jam == 0)
                        <td colspan="{{ $kelass->count() }}"
                            class="p-2 border-b border-slate-100 bg-indigo-50/60 text-center text-[10px] md:text-xs font-bold text-indigo-600 tracking-widest uppercase">
                            <div class="flex items-center justify-center gap-2">
                                @if($hari == 'Senin') 🇮🇩 <span>Upacara Bendera</span>
                                @elseif($hari == 'Jumat') 📖 <span>IMTAQ / Senam</span>
                                @else 📖 <span>Literasi</span> @endif
                            </div>
                        </td>
                        @else
                        @foreach($kelass as $kelas)
                        @php $data = $jadwals[$kelas->id][$hari][$jam] ?? null; @endphp
                        <td
                            class="p-1 border-r border-b border-slate-100 text-center align-middle h-14 md:h-16 relative {{ $data ? $data['color'] : 'bg-transparent' }} hover:brightness-95 transition-all">
                            @if($data)
                            <div class="flex flex-col justify-center items-center h-full w-full p-1 rounded-md">
                                <span
                                    class="font-bold text-slate-800 text-[10px] md:text-[11px] leading-tight line-clamp-1 mb-0.5">{{ $data['mapel'] }}</span>
                                <span
                                    class="text-[8px] md:text-[9px] text-slate-500 font-medium leading-tight line-clamp-1 italic bg-white/50 px-1.5 py-0.5 rounded">{{ Str::limit($data['guru'], 18) }}</span>
                            </div>
                            @else
                            <span class="text-slate-200 text-[8px] font-light">-</span>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr class="bg-amber-50/50">
                            <td
                                class="sticky left-8 md:left-12 z-[10] p-1 border-r border-b border-amber-100 bg-amber-50/90 text-center font-bold text-amber-600 text-[9px] md:text-[10px] shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                                IST</td>
                            <td
                                class="sticky left-[4rem] md:left-[5.5rem] z-[10] p-1 border-r border-b border-amber-100 bg-amber-50/90 text-center text-[9px] md:text-[10px] font-mono font-medium text-amber-600 shadow-[4px_0_5px_rgba(0,0,0,0.03)]">
                                {{ $jam==4 ? '10.30-10.45' : '13.30-13.50' }}
                            </td>
                            <td colspan="{{ $kelass->count() }}"
                                class="p-1 border-b border-amber-100 text-center font-bold text-amber-500 text-[9px] md:text-[10px] tracking-[0.2em] md:tracking-[0.3em] uppercase">
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
        </div>
        @endif

        {{-- 3. FOOTER SECTION --}}
        <div
            class="bg-slate-50/80 border-t border-slate-200 px-4 md:px-6 py-3 flex flex-col sm:flex-row justify-between items-center shrink-0 rounded-b-2xl md:rounded-b-3xl gap-2 sm:gap-0">
            <span
                class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center sm:text-left">
                Sistem Penjadwalan Terintegrasi
            </span>
            <span
                class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
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
    class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300 px-4">
    <div
        class="bg-white p-6 md:p-8 rounded-3xl shadow-2xl text-center max-w-sm w-full mx-auto animate-scale-in border border-white/20">
        <div class="relative w-16 h-16 md:w-20 md:h-20 mx-auto mb-5 md:mb-6">
            <div class="absolute inset-0 border-4 border-indigo-50 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin">
            </div>
            <div class="absolute inset-0 flex items-center justify-center text-2xl md:text-3xl">⚙️</div>
        </div>
        <h3 class="text-lg md:text-xl font-extrabold text-slate-800">Menyusun Jadwal...</h3>
        <p class="text-slate-500 text-xs md:text-sm mt-2 font-medium">
            Sistem solver sedang menghitung dan mendistribusikan durasi mengajar agar tidak terjadi bentrok.
        </p>
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

/* Custom Scrollbar Mobile Friendly */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

@media (min-width: 768px) {
    .custom-scrollbar::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
    border-radius: 8px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 8px;
    border: 2px solid #f8fafc;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Biarkan teks hari vertikal di desktop, ubah horizontal hanya jika layarnya sangaaaat kecil jika diperlukan, 
   tapi karena kolom kiri kita persempit, vertikal tetap bagus untuk mobile. */

/* =========================================================
   CSS TOMSELECT (BERSIH, AMAN, DAN RAPI)
   ========================================================= */
.ts-control {
    padding: 0.5rem 0.8rem !important;
    border-radius: 0.75rem !important;
    border-color: #e2e8f0 !important;
    background-color: #f8fafc !important;
    min-height: 40px !important;
    font-size: 12px !important;
}

@media (min-width: 768px) {
    .ts-control {
        padding: 0.6rem 1rem !important;
        min-height: 42px !important;
        font-size: 14px !important;
    }
}

.ts-control.focus {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2) !important;
    background-color: #ffffff !important;
}

.ts-dropdown {
    border-radius: 0.75rem !important;
    border-color: #e2e8f0 !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
    font-size: 13px !important;
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