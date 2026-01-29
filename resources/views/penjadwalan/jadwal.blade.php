@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-slate-100 to-slate-50"></div>
</div>

{{-- CONTAINER UTAMA (Fixed Height = Layar Penuh dikurangi Navbar) --}}
<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 h-[calc(100vh-6rem)] pb-6 pt-4 flex flex-col gap-4">

    {{-- CARD 1: HEADER (JUDUL & TOMBOL) - FIXED --}}
    <div
        class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 shrink-0 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight flex items-center gap-3">
                <span class="w-1.5 h-8 bg-blue-600 rounded-full"></span>
                Jadwal Pelajaran Terpadu
            </h1>
            <p class="text-slate-500 text-sm mt-1 font-medium ml-4.5">
                Tahun Ajaran {{ date('Y') }}/{{ date('Y')+1 }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Tombol Export --}}
            <a href="{{ route('jadwal.export') }}"
                class="bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export Excel
            </a>

            {{-- Tombol Generate --}}
            <form action="{{ route('jadwal.generate') }}" method="POST" onsubmit="showLoading()">
                @csrf
                <button type="button"
                    onclick="if(confirm('Generate ulang akan menimpa jadwal lama. Lanjut?')) this.form.submit()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-indigo-500/30 flex items-center gap-2">
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
        class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex items-center gap-3 text-emerald-800 shrink-0">
        <div class="bg-emerald-100 p-1.5 rounded-full text-emerald-600"><svg class="w-4 h-4" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg></div>
        <span class="text-sm font-bold">{{ session('success') }}</span>
        <button @click="show = false" class="ml-auto text-emerald-500 hover:text-emerald-700">&times;</button>
    </div>
    @endif

    {{-- CARD 2: TABEL (SCROLLABLE) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 flex-1 overflow-hidden relative">

        {{-- WRAPPER SCROLL (Kunci agar header diam) --}}
        <div class="absolute inset-0 overflow-auto custom-scrollbar">
            <table class="w-full text-xs border-collapse relative min-w-[1500px]">

                {{-- HEADER TABEL (STICKY TOP) --}}
                <thead class="bg-slate-50 text-slate-700 font-extrabold uppercase sticky top-0 z-40 shadow-sm">
                    <tr>
                        {{-- Kolom Sticky Kiri --}}
                        <th
                            class="p-4 border-b border-r border-slate-300 w-16 text-center sticky left-0 top-0 z-50 bg-slate-100">
                            Hari</th>
                        <th
                            class="p-4 border-b border-r border-slate-300 w-12 text-center sticky left-16 top-0 z-50 bg-slate-100">
                            Jam</th>
                        <th
                            class="p-4 border-b border-r border-slate-300 w-28 text-center sticky left-28 top-0 z-50 bg-slate-100">
                            Waktu</th>

                        {{-- Header Kelas --}}
                        @foreach($kelass as $kelas)
                        <th
                            class="p-4 border-b border-r border-slate-200 min-w-[140px] text-center bg-slate-50 text-slate-800">
                            {{ $kelas->nama_kelas }}
                        </th>
                        @endforeach
                    </tr>
                </thead>

                {{-- BODY TABEL --}}
                <tbody class="divide-y divide-slate-200 bg-white">
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
                    $rowSpan = ($maxJam - $startJam) + 1 + (($hari != 'Jumat') ? 1 : 0);
                    @endphp

                    @for($jam = $startJam; $jam <= $maxJam; $jam++) <tr class="hover:bg-slate-50 transition group">

                        {{-- 1. Kolom HARI (Sticky Left) --}}
                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpan }}"
                            class="p-0 border-r border-b border-slate-300 bg-white font-bold text-center align-middle text-slate-800 sticky left-0 z-30 shadow-[4px_0_24px_rgba(0,0,0,0.02)]">
                            <div
                                class="writing-mode-vertical md:writing-mode-horizontal transform md:rotate-0 -rotate-180 py-6 uppercase tracking-widest text-xs md:text-sm">
                                {{ $hari }}
                            </div>
                        </td>
                        @endif

                        {{-- 2. Kolom JAM (Sticky Left) --}}
                        <td
                            class="p-2 border-r border-slate-200 text-center font-bold text-slate-500 bg-slate-50 sticky left-16 z-30 w-12 shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                            {{ $jam }}
                        </td>

                        {{-- 3. Kolom WAKTU (Sticky Left) --}}
                        <td
                            class="p-2 border-r border-slate-300 text-center text-[10px] font-mono text-slate-600 bg-slate-50 sticky left-28 z-30 w-28 shadow-[4px_0_10px_rgba(0,0,0,0.05)]">
                            @php
                            if($hari == 'Senin') $w = $waktu['Senin'][$jam] ?? '-';
                            elseif($hari == 'Jumat') $w = $waktu['Jumat'][$jam] ?? '-';
                            else $w = $waktu['Default'][$jam] ?? '-';
                            @endphp
                            {{ $w }}
                        </td>

                        {{-- 4. Kolom DATA KELAS --}}
                        @if($jam == 0)
                        <td colspan="{{ $kelass->count() }}"
                            class="p-3 bg-slate-100 text-center font-bold text-slate-500 uppercase text-xs border-r border-slate-200 tracking-widest shadow-inner">
                            @if($hari == 'Senin') 🇮🇩 UPACARA BENDERA
                            @elseif($hari == 'Jumat') 🏃 SENAM / IMTAQ / JALAN SEHAT
                            @else 📖 LITERASI @endif
                        </td>
                        @else
                        @foreach($kelass as $kelas)
                        @php $data = $jadwals[$kelas->id][$hari][$jam] ?? null; @endphp
                        <td
                            class="p-1 border-r border-slate-200 text-center align-middle h-16 relative group/cell {{ $data ? 'bg-white hover:bg-blue-50' : 'bg-slate-50/20' }}">
                            @if($data)
                            <div class="flex flex-col justify-center h-full w-full px-2 py-1">
                                <span class="font-bold text-slate-900 text-[10px] leading-snug line-clamp-2">
                                    {{ $data['mapel'] }}
                                </span>
                                <span class="text-[9px] text-slate-500 leading-tight mt-0.5 truncate">
                                    {{ Str::limit($data['guru'], 15) }}
                                </span>

                                {{-- Tooltip --}}
                                <div
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/cell:block z-[60] bg-slate-800 text-white text-[10px] py-1.5 px-3 rounded-lg shadow-xl whitespace-nowrap border border-slate-700">
                                    <div class="font-bold text-amber-400">{{ $data['kode_mapel'] }}</div>
                                    <div>{{ $data['kode_guru'] }}</div>
                                    <div
                                        class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-slate-800">
                                    </div>
                                </div>
                            </div>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        {{-- Baris Istirahat --}}
                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr class="bg-orange-50 border-y border-orange-200">
                            <td
                                class="p-2 border-r border-orange-200 text-center font-black text-orange-800 text-[10px] sticky left-16 z-30 bg-orange-100">
                                IST</td>
                            <td
                                class="p-2 border-r border-slate-300 text-center font-mono text-orange-800 text-[10px] sticky left-28 z-30 bg-orange-100 shadow-[4px_0_10px_rgba(0,0,0,0.05)]">
                                {{ $jam==4 ? '10.30-10.45' : '13.30-13.50' }}
                            </td>
                            <td colspan="{{ $kelass->count() }}"
                                class="p-2 text-center font-bold text-orange-800 text-xs tracking-[0.3em] uppercase shadow-inner">
                                ☕ ISTIRAHAT
                            </td>
                        </tr>
                        @endif

                        @endfor

                        {{-- Spacer antar Hari --}}
                        <tr class="bg-slate-200 h-2 border-y border-slate-300">
                            <td colspan="{{ 3 + $kelass->count() }}"></td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Loading Overlay --}}
<div id="loading-overlay"
    class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/90 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white p-8 rounded-2xl shadow-2xl text-center animate-bounce">
        <span class="text-5xl">🤖</span>
        <p class="mt-4 font-extrabold text-slate-800 text-lg">AI Sedang Berpikir...</p>
        <p class="text-slate-500 text-sm">Mohon tunggu sebentar.</p>
    </div>
</div>

@endsection

@push('scripts')
<style>
.writing-mode-vertical {
    writing-mode: vertical-rl;
    text-orientation: mixed;
    transform: rotate(180deg);
}

@media (min-width: 768px) {
    .md\:writing-mode-horizontal {
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