@extends('layouts.app')

@php
$hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

// Definisi Waktu (Sama seperti sebelumnya)
$jadwalWaktu = [
'Senin' => [
0=>'07.00-07.45', 1=>'07.45-08.25', 2=>'08.25-09.05', 3=>'09.05-09.45',
4=>'10.00-10.40', 5=>'10.40-11.20', 6=>'11.20-12.00', 7=>'12.50-13.30',
8=>'13.30-14.10', 9=>'14.10-14.50', 10=>'14.50-15.30'
],
'Jumat' => [
0=>'07.00-07.45', 1=>'07.45-08.20', 2=>'08.20-08.55', 3=>'08.55-09.30',
4=>'09.30-10.00', 5=>'10.15-10.45', 6=>'10.45-11.15', 7=>'11.15-11.45',
8=>'12.45-13.15', 9=>'13.15-13.45', 10=>'13.45-14.15'
],
'Default' => [
0=>'07.00-07.40', 1=>'07.40-08.20', 2=>'08.20-09.00', 3=>'09.00-09.40',
4=>'09.50-10.30', 5=>'10.30-11.10', 6=>'11.10-11.50', 7=>'12.35-13.15',
8=>'13.15-13.55', 9=>'13.55-14.35', 10=>'14.35-15.15'
]
];

function getWaktu($hari, $jam, $jadwalWaktu) {
if ($hari == 'Senin') return $jadwalWaktu['Senin'][$jam] ?? '-';
if ($hari == 'Jumat') return $jadwalWaktu['Jumat'][$jam] ?? '-';
return $jadwalWaktu['Default'][$jam] ?? '-';
}
@endphp

@section('content')

{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-slate-100 to-slate-50"></div>
</div>

<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 pb-10 pt-4 flex flex-col h-[calc(100vh-6rem)]">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                <span class="w-2 h-6 bg-blue-600 rounded-full"></span>
                Jadwal Pelajaran Terpadu
            </h1>
            <p class="text-slate-500 text-sm mt-1 ml-4">Tahun Ajaran {{ date('Y') }}/{{ date('Y')+1 }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('jadwal.export') }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export Excel
            </a>
            <form action="{{ route('jadwal.generate') }}" method="POST" onsubmit="showLoading()">
                @csrf
                <button type="button" onclick="if(confirm('Generate ulang?')) this.form.submit()" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                        </path>
                    </svg>
                    Jalankan AI Solver
                </button>
            </form>
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show"
        class="mb-4 bg-emerald-50 border border-emerald-200 rounded-lg p-3 flex items-center gap-3 shrink-0">
        <span class="text-emerald-600 font-bold">✓</span>
        <div class="flex-1 text-sm text-emerald-800">{{ session('success') }}</div>
        <button @click="show = false" class="text-emerald-500">&times;</button>
    </div>
    @endif

    {{-- TABEL CONTAINER --}}
    <div class="bg-white border border-slate-300 rounded-xl shadow-lg flex flex-col flex-1 overflow-hidden relative">
        <div class="overflow-auto custom-scrollbar flex-1 w-full h-full relative">
            <table class="w-full text-xs border-separate border-spacing-0 min-w-[1200px]">

                {{-- HEADER UTAMA --}}
                <thead>
                    <tr>
                        {{-- Sticky Corners (Z-Index 50) --}}
                        <th
                            class="sticky top-0 left-0 z-50 bg-slate-100 border-b border-r border-slate-300 p-3 w-12 text-center text-slate-700 font-bold shadow-sm">
                            HARI</th>
                        <th
                            class="sticky top-0 left-12 z-50 bg-slate-100 border-b border-r border-slate-300 p-3 w-10 text-center text-slate-700 font-bold shadow-sm">
                            JAM</th>
                        <th
                            class="sticky top-0 left-[5.5rem] z-50 bg-slate-100 border-b border-r border-slate-300 p-3 w-24 text-center text-slate-700 font-bold shadow-sm">
                            WAKTU</th>

                        {{-- Header Kelas (Sticky Top - Z-Index 40) --}}
                        @foreach($kelass as $kelas)
                        <th
                            class="sticky top-0 z-40 bg-slate-50 border-b border-r border-slate-300 p-3 min-w-[140px] text-slate-700 font-bold shadow-sm">
                            {{ $kelas->nama_kelas }}
                        </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach($hariList as $hari)
                    @php
                    $maxJam = 10;
                    $startJam = ($hari == 'Senin' || $hari == 'Jumat') ? 0 : 1;
                    // +1 baris istirahat jika bukan Jumat
                    $rowSpan = ($maxJam - $startJam) + 1 + (($hari != 'Jumat') ? 1 : 0);
                    @endphp

                    @for($jam = $startJam; $jam <= $maxJam; $jam++) <tr
                        class="group hover:bg-blue-50/30 transition-colors">

                        {{-- KOLOM HARI (Sticky Left - Merged) --}}
                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpan }}"
                            class="sticky left-0 z-30 bg-white border-r border-b border-slate-300 p-0 text-center align-middle font-bold text-slate-600 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                            <div
                                class="writing-mode-vertical transform -rotate-180 py-6 uppercase tracking-widest text-[11px]">
                                {{ $hari }}</div>
                        </td>
                        @endif

                        {{-- KOLOM JAM (Sticky Left) --}}
                        <td
                            class="sticky left-12 z-20 bg-slate-50 border-r border-b border-slate-200 text-center font-bold text-slate-500 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                            {{ $jam }}
                        </td>

                        {{-- KOLOM WAKTU (Sticky Left) --}}
                        <td
                            class="sticky left-[5.5rem] z-20 bg-slate-50 border-r border-b border-slate-200 text-center font-mono text-[10px] text-slate-500 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                            {{ getWaktu($hari, $jam, $jadwalWaktu) }}
                        </td>

                        {{-- KONTEN UTAMA --}}
                        @if($jam == 0)
                        {{-- Baris Khusus --}}
                        <td colspan="{{ $kelass->count() }}"
                            class="p-2 border-b border-r border-slate-200 bg-slate-100 text-center font-bold text-slate-500 uppercase tracking-widest text-[11px]">
                            @if($hari == 'Senin') 🇮🇩 UPACARA BENDERA
                            @elseif($hari == 'Jumat') 🏃 SENAM PAGI / IMTAQ
                            @else 📖 LITERASI
                            @endif
                        </td>
                        @else
                        {{-- Loop Kelas --}}
                        @foreach($kelass as $kelas)
                        @php $data = $jadwals[$kelas->id][$hari][$jam] ?? null; @endphp

                        <td class="h-14 border-b border-r border-slate-200 relative group/cell p-1 align-middle transition-colors
                                            {{ $data ? 'bg-white hover:bg-blue-50' : 'bg-slate-50 bg-striped' }}">

                            @if($data)
                            <div class="flex flex-col justify-center h-full w-full">
                                <span
                                    class="font-bold text-slate-800 text-[11px] leading-tight line-clamp-2 text-center mb-0.5">
                                    {{ $data['mapel'] }}
                                </span>
                                <span class="text-[9px] text-slate-500 text-center truncate px-1">
                                    {{ $data['guru'] }}
                                </span>

                                {{-- Tooltip Modern --}}
                                <div
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/cell:block z-[100] w-max max-w-[200px]">
                                    <div
                                        class="bg-slate-800 text-white text-[10px] py-1.5 px-3 rounded shadow-xl text-center">
                                        <div class="font-bold">{{ $data['kode_mapel'] }}</div>
                                        <div class="opacity-80">{{ $data['kode_guru'] }}</div>
                                        {{-- Arrow --}}
                                        <div
                                            class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-800">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        {{-- BARIS ISTIRAHAT --}}
                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr class="bg-orange-50">
                            {{-- Sticky Placeholders --}}
                            <td
                                class="sticky left-12 z-20 bg-orange-100 border-r border-b border-orange-200 text-center font-bold text-orange-800 text-[10px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                IST</td>
                            <td
                                class="sticky left-[5.5rem] z-20 bg-orange-100 border-r border-b border-orange-200 text-center font-mono text-[10px] text-orange-800 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                {{ $jam == 4 ? '10.30-10.45' : '13.30-13.50' }}
                            </td>
                            {{-- Label Istirahat --}}
                            <td colspan="{{ $kelass->count() }}"
                                class="border-b border-r border-orange-200 p-1 text-center font-bold text-orange-700 text-[11px] tracking-[0.3em] uppercase">
                                ☕ ISTIRAHAT
                            </td>
                        </tr>
                        @endif
                        @endfor

                        {{-- Separator Hari --}}
                        <tr class="h-2 bg-slate-300 border-t border-b border-slate-300">
                            <td colspan="{{ 3 + $kelass->count() }}"></td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Footer --}}
    <div class="mt-2 text-center text-[10px] text-slate-400">© {{ date('Y') }} Sistem Penjadwalan</div>
</div>

{{-- Loading & Styles --}}
<div id="loading-overlay"
    class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm">
    <div class="bg-white p-6 rounded-xl shadow-2xl animate-bounce">🤖</div>
</div>
@endsection

@push('scripts')
<style>
.writing-mode-vertical {
    writing-mode: vertical-rl;
    text-orientation: mixed;
}

/* Pattern garis miring untuk sel kosong */
.bg-striped {
    background-image: linear-gradient(45deg, #f1f5f9 25%, #f8fafc 25%, #f8fafc 50%, #f1f5f9 50%, #f1f5f9 75%, #f8fafc 75%, #f8fafc 100%);
    background-size: 10px 10px;
}

/* Style Tombol Helper */
.btn-primary {
    @apply bg-indigo-600 hover: bg-indigo-700 text-white px-4 py-2 rounded-lg shadow font-bold flex items-center gap-2 text-sm transition;
}

.btn-secondary {
    @apply bg-emerald-600 hover: bg-emerald-700 text-white px-4 py-2 rounded-lg shadow font-bold flex items-center gap-2 text-sm transition;
}

/* Custom Scrollbar */
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
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
<script>
function showLoading() {
    document.getElementById('loading-overlay').classList.remove('hidden');
}
</script>
@endpush