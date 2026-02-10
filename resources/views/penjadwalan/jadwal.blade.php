@extends('layouts.app')

@section('content')
<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 h-[calc(100vh-6rem)] flex flex-col">

    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Jadwal Pelajaran Terpadu</h1>
            <p class="text-slate-500 text-sm mt-1">Tahun Ajaran {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('jadwal.export') }}"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg shadow-sm font-bold transition flex items-center gap-2 text-sm">
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
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow-sm font-bold transition flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                        </path>
                    </svg>
                    Auto Generate AI
                </button>
            </form>
        </div>
    </div>

    {{-- Loading Overlay --}}
    <div id="loading-overlay"
        class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/90 backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-white p-8 rounded-2xl shadow-2xl text-center max-w-sm mx-4 animate-scale-in">
            <div class="relative w-16 h-16 mx-auto mb-4">
                <div class="absolute inset-0 border-4 border-indigo-100 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin">
                </div>
                <div class="absolute inset-0 flex items-center justify-center text-2xl">🧠</div>
            </div>
            <h3 class="text-lg font-bold text-slate-800">Sedang Mengoptimasi...</h3>
            <p class="text-slate-500 text-sm mt-2">Mohon tunggu sebentar.</p>
        </div>
    </div>

    @if(session('success'))
    <div
        class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center gap-2 shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    @if($kelass->isEmpty())
    <div class="text-center py-20 bg-white rounded-xl border border-dashed border-slate-300">
        <div class="text-5xl mb-4">📂</div>
        <h3 class="text-lg font-bold text-slate-700">Data Kelas Kosong</h3>
    </div>
    @else

    {{-- CONTAINER TABEL UTAMA --}}
    <div class="bg-white shadow-xl rounded-xl border border-slate-200 overflow-hidden flex flex-col flex-1 min-h-0">

        {{-- WRAPPER SCROLL (Scroll di sini) --}}
        <div class="overflow-auto custom-scrollbar flex-1 relative w-full h-full">
            <table class="w-full text-xs border-separate border-spacing-0">
                <thead>
                    {{-- HEADER 1: JUDUL BESAR (Sticky Top L1) --}}
                    <tr>
                        <th colspan="{{ 3 + $kelass->count() }}"
                            class="sticky top-0 left-0 z-[60] h-12 bg-slate-800 text-white font-bold text-center uppercase tracking-wider border-b border-slate-700 shadow-md">
                            Jadwal Pelajaran
                        </th>
                    </tr>

                    {{-- HEADER 2: NAMA KELAS & KOLOM WAKTU (Sticky Top L2) --}}
                    <tr>
                        {{-- 1. HARI (Sticky Kiri & Atas = Pojok Mati) --}}
                        <th
                            class="sticky top-12 left-0 z-[55] w-12 p-3 bg-slate-100 text-slate-700 font-bold border-r border-b border-slate-300 shadow-[2px_2px_5px_rgba(0,0,0,0.05)]">
                            HARI
                        </th>
                        {{-- 2. JAM (Sticky Kiri & Atas) --}}
                        <th
                            class="sticky top-12 left-12 z-[55] w-10 p-3 bg-slate-100 text-slate-700 font-bold border-r border-b border-slate-300 shadow-[2px_2px_5px_rgba(0,0,0,0.05)]">
                            JP
                        </th>
                        {{-- 3. WAKTU (Sticky Kiri & Atas) --}}
                        <th
                            class="sticky top-12 left-[5.5rem] z-[55] w-28 p-3 bg-slate-100 text-slate-700 font-bold border-r border-b border-slate-300 shadow-[2px_2px_5px_rgba(0,0,0,0.05)]">
                            WAKTU
                        </th>

                        {{-- 4. KELAS (Scrollable Horizontal, Sticky Vertikal) --}}
                        @foreach($kelass as $kelas)
                        <th
                            class="sticky top-12 z-[50] min-w-[140px] p-3 bg-slate-50 text-slate-800 font-bold border-r border-b border-slate-300 text-center">
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
                        class="hover:bg-slate-50 transition-colors group">

                        {{-- KOLOM HARI (Sticky Horizontal - Fix Layering) --}}
                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="sticky left-0 z-[45] p-0 bg-white border-r border-b border-slate-300 align-middle text-center shadow-[2px_0_5px_rgba(0,0,0,0.05)]">
                            <div
                                class="vertical-text font-bold text-slate-700 uppercase tracking-widest text-xs h-full flex items-center justify-center bg-slate-50 w-full py-4">
                                {{ $hari }}
                            </div>
                        </td>
                        @endif

                        {{-- INFO JAM (Sticky Horizontal) --}}
                        <td
                            class="sticky left-12 z-[40] p-2 bg-slate-50 border-r border-b border-slate-200 text-center font-bold text-slate-500 text-[10px] shadow-[2px_0_5px_rgba(0,0,0,0.05)]">
                            {{ $jam }}
                        </td>

                        {{-- INFO WAKTU (Sticky Horizontal) --}}
                        <td
                            class="sticky left-[5.5rem] z-[40] p-2 bg-white border-r border-b border-slate-200 text-center text-[10px] font-mono text-slate-600 shadow-[4px_0_5px_rgba(0,0,0,0.05)]">
                            @php
                            if($hari == 'Senin') $w = $waktu['Senin'][$jam] ?? '-';
                            elseif($hari == 'Jumat') $w = $waktu['Jumat'][$jam] ?? '-';
                            else $w = $waktu['Default'][$jam] ?? '-';
                            @endphp
                            {{ $w }}
                        </td>

                        {{-- ISI JADWAL --}}
                        @if($jam == 0)
                        <td colspan="{{ $kelass->count() }}"
                            class="p-2 border-b border-slate-200 bg-indigo-50 text-center text-xs font-bold text-indigo-700 tracking-widest uppercase">
                            @if($hari == 'Senin') 🇮🇩 UPACARA BENDERA
                            @elseif($hari == 'Jumat') 📖 IMTAQ / SENAM PAGI
                            @else 📖 LITERASI @endif
                        </td>
                        @else
                        @foreach($kelass as $kelas)
                        @php $data = $jadwals[$kelas->id][$hari][$jam] ?? null; @endphp

                        <td class="p-1 border-r border-b border-slate-200 text-center align-middle h-14 relative 
                                        {{ $data ? $data['color'] : 'bg-white' }} 
                                        hover:brightness-95 transition-all">

                            @if($data)
                            <div class="flex flex-col justify-center h-full w-full">
                                <span class="font-bold text-slate-800 text-[10px] leading-tight line-clamp-1">
                                    {{ $data['mapel'] }}
                                </span>
                                <span class="text-[9px] text-slate-500 leading-tight mt-0.5 line-clamp-1 italic">
                                    {{ Str::limit($data['guru'], 15) }}
                                </span>
                            </div>
                            @else
                            <span class="text-slate-200 text-[8px]">-</span>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        {{-- BARIS ISTIRAHAT --}}
                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr class="bg-amber-50">
                            {{-- Sticky Jam Istirahat --}}
                            <td
                                class="sticky left-12 z-[40] p-1 border-r border-b border-amber-200 bg-amber-100 text-center font-bold text-amber-800 text-[10px] shadow-[2px_0_5px_rgba(0,0,0,0.05)]">
                                IST
                            </td>
                            {{-- Sticky Waktu Istirahat --}}
                            <td
                                class="sticky left-[5.5rem] z-[40] p-1 border-r border-b border-amber-200 bg-amber-50 text-center text-[10px] font-mono text-amber-700 shadow-[4px_0_5px_rgba(0,0,0,0.05)]">
                                {{ $jam==4 ? '10.30-10.45' : '13.30-13.50' }}
                            </td>
                            <td colspan="{{ $kelass->count() }}"
                                class="p-1 border-b border-amber-200 text-center font-bold text-amber-600 text-[10px] tracking-[0.2em] uppercase">
                                ☕ ISTIRAHAT
                            </td>
                        </tr>
                        @endif

                        @endfor

                        {{-- PEMBATAS HARI (GAP TEBAL) --}}
                        <tr>
                            <td colspan="{{ $kelass->count() + 3 }}" class="bg-slate-300 h-1 border-b border-slate-300">
                            </td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<style>
/* Reset Spesifik untuk Tabel Sticky agar tidak goyang */
table {
    border-collapse: separate;
    border-spacing: 0;
}

/* Tulisan Hari Vertikal */
.vertical-text {
    writing-mode: vertical-lr;
    transform: rotate(180deg);
    white-space: nowrap;
}

/* Custom Scrollbar Halus */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
    border: 2px solid #f1f5f9;
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
</style>
<script>
function showLoading() {
    document.getElementById('loading-overlay').classList.remove('hidden');
}
</script>
@endpush