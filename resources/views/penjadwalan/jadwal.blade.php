@extends('layouts.app')

@section('content')
{{-- BACKGROUND AMBIENT --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-blue-50 to-slate-50"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-indigo-300/10 rounded-full blur-3xl opacity-70"></div>
    <div class="absolute top-20 left-20 w-72 h-72 bg-cyan-300/10 rounded-full blur-3xl opacity-70"></div>
</div>

<div class="w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col min-h-[calc(100vh-4rem)]">

    {{-- ALERT MESSAGES --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-5 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-2xl shadow-sm text-emerald-800 shrink-0">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-500 rounded-lg text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div>
                <p class="font-bold text-sm leading-none">{{ session('success') }}</p>
                @if(session('waktu_komputasi'))
                <p class="text-[10px] font-medium opacity-75 mt-1 uppercase tracking-wider">⏱️ Selesai dalam
                    {{ session('waktu_komputasi') }} Detik</p>
                @endif
            </div>
        </div>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700 transition">&times;</button>
    </div>
    @endif

    {{-- MAIN CARD --}}
    <div
        class="bg-white rounded-[2.5rem] border border-slate-200/60 shadow-2xl shadow-indigo-100/50 flex flex-col flex-1 overflow-hidden">

        {{-- HEADER SECTION --}}
        <div class="p-6 md:p-8 border-b border-slate-100">
            <div class="flex flex-col lg:flex-row justify-between gap-6">
                {{-- Title & Info (Gaya Guru) --}}
                <div class="flex gap-4">
                    <div class="w-2 h-10 bg-indigo-600 rounded-full"></div>
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Jadwal Pelajaran Terpadu</h1>
                        <p class="text-slate-500 text-sm mt-1">Manajemen jadwal otomatis, filter kelas, dan ekspor data
                            kurikulum.</p>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('jadwal.export') }}"
                        class="flex items-center gap-2 px-5 py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 font-bold text-xs uppercase tracking-widest rounded-xl transition border border-slate-200">
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
                            class="flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl transition shadow-lg shadow-indigo-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                                </path>
                            </svg>
                            Generate Jadwal
                        </button>
                    </form>
                </div>
            </div>

            {{-- FILTER BAR (Didesain mirip search bar di Guru) --}}
            <div class="mt-8">
                <form action="{{ route('jadwal.index') }}" method="GET"
                    class="flex flex-col md:flex-row gap-3 p-2 bg-slate-50 rounded-2xl border border-slate-200">
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div class="relative group">
                            <select name="guru_id" id="filter-guru" class="w-full">
                                <option value="">Semua Guru</option>
                                @foreach($gurusList as $g)
                                <option value="{{ $g->id }}" {{ $reqGuru == $g->id ? 'selected' : '' }}>
                                    {{ $g->nama_guru }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="relative group">
                            <select name="kelas_id" id="filter-kelas" class="w-full">
                                <option value="">Semua Kelas</option>
                                @foreach($kelassList as $k)
                                <option value="{{ $k->id }}" {{ $reqKelas == $k->id ? 'selected' : '' }}>
                                    {{ $k->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-8 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl font-bold text-sm transition flex items-center justify-center gap-2 min-w-[120px]">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Cari
                        </button>
                        @if($reqGuru || $reqKelas)
                        <a href="{{ route('jadwal.index') }}"
                            class="px-4 py-2.5 bg-white hover:bg-red-50 text-red-500 rounded-xl font-bold text-sm transition border border-slate-200 flex items-center">
                            Reset
                        </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE SECTION --}}
        <div class="flex-1 overflow-auto custom-scrollbar bg-slate-50/30">
            @if($kelass->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center text-3xl mb-4">Empty
                </div>
                <h3 class="text-lg font-bold text-slate-700">Data Tidak Ditemukan</h3>
                <p class="text-slate-400 text-sm">Silakan ubah filter pencarian Anda</p>
            </div>
            @else
            <table class="w-full border-separate border-spacing-0">
                <thead>
                    <tr>
                        <th
                            class="sticky top-0 left-0 z-40 bg-slate-800 text-white p-4 text-[10px] font-black uppercase tracking-[0.2em] border-b border-slate-700 border-r border-slate-700 w-16">
                            HARI</th>
                        <th
                            class="sticky top-0 left-16 z-40 bg-slate-800 text-white p-4 text-[10px] font-black uppercase tracking-[0.2em] border-b border-slate-700 border-r border-slate-700 w-12 text-center">
                            JP</th>
                        <th
                            class="sticky top-0 left-28 z-40 bg-slate-800 text-white p-4 text-[10px] font-black uppercase tracking-[0.2em] border-b border-slate-700 border-r border-slate-700 w-32 text-center">
                            WAKTU</th>
                        @foreach($kelass as $kelas)
                        <th
                            class="sticky top-0 z-30 bg-slate-50/95 backdrop-blur-md p-4 text-xs font-black text-slate-800 border-b border-r border-slate-200 text-center min-w-[160px]">
                            {{ $kelas->nama_kelas }}
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
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
                        class="group hover:bg-indigo-50/30 transition-colors">
                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="sticky left-0 z-20 bg-white border-r border-b border-slate-200 align-middle text-center shadow-sm">
                            <span
                                class="vertical-text text-[11px] font-black text-slate-300 uppercase tracking-widest group-hover:text-indigo-600 transition-colors">
                                {{ $hari }}
                            </span>
                        </td>
                        @endif

                        <td
                            class="sticky left-16 z-10 bg-slate-50 border-r border-b border-slate-200 text-center font-bold text-slate-400 text-[10px]">
                            {{ $jam }}</td>
                        <td
                            class="sticky left-28 z-10 bg-white border-r border-b border-slate-200 text-center text-[10px] font-mono text-slate-500 font-medium">
                            @php $w = ($hari == 'Senin') ? $waktu['Senin'][$jam] : (($hari == 'Jumat') ?
                            $waktu['Jumat'][$jam] : $waktu['Default'][$jam]); @endphp
                            {{ $w }}
                        </td>

                        @if($jam == 0)
                        <td colspan="{{ $kelass->count() }}"
                            class="p-3 border-b border-slate-200 bg-indigo-50/50 text-center">
                            <div class="flex items-center justify-center gap-3">
                                <span
                                    class="text-xs font-bold text-indigo-700 uppercase tracking-widest flex items-center gap-2">
                                    @if($hari == 'Senin') 🇮🇩 UPACARA BENDERA @else 📖 IMTAQ / SENAM @endif
                                </span>
                            </div>
                        </td>
                        @else
                        @foreach($kelass as $kelas)
                        @php $data = $jadwals[$kelas->id][$hari][$jam] ?? null; @endphp
                        <td
                            class="p-2 border-r border-b border-slate-100 text-center align-middle h-20 transition-all {{ $data ? $data['color'] : 'bg-transparent' }}">
                            @if($data)
                            <div class="flex flex-col justify-center items-center">
                                <span
                                    class="font-extrabold text-slate-800 text-[11px] leading-tight mb-1">{{ $data['mapel'] }}</span>
                                <span
                                    class="text-[9px] text-slate-500 font-semibold italic bg-white/60 px-2 py-0.5 rounded shadow-sm">{{ Str::limit($data['guru'], 15) }}</span>
                            </div>
                            @else
                            <span class="text-slate-200 text-[10px]">-</span>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        {{-- Istirahat --}}
                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr class="bg-slate-100/50">
                            <td
                                class="sticky left-16 z-10 bg-slate-100 border-r border-b border-slate-200 text-center font-black text-slate-400 text-[9px]">
                                IST</td>
                            <td
                                class="sticky left-28 z-10 bg-slate-100 border-r border-b border-slate-200 text-center text-[10px] font-mono text-slate-400 font-medium">
                                {{ $jam == 4 ? '10.30-10.45' : '13.30-13.50' }}
                            </td>
                            <td colspan="{{ $kelass->count() }}"
                                class="p-2 border-b border-slate-200 text-center font-black text-slate-400 text-[10px] uppercase tracking-[0.5em]">
                                ☕ ISTIRAHAT</td>
                        </tr>
                        @endif
                        @endfor
                        @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- FOOTER --}}
        <div
            class="bg-white border-t border-slate-100 p-4 px-8 flex justify-between items-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                Sistem Penjadwalan SMAN 1 Sampang
            </div>
            <div class="flex items-center gap-4">
                <span>© 2026 Admin Panel</span>
                <span class="text-slate-200">|</span>
                <span class="flex items-center gap-1 text-emerald-500">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zM10 15a1 1 0 100-2 1 1 0 000 2zM10 5a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                    Secure Data
                </span>
            </div>
        </div>
    </div>
</div>

{{-- LOADING OVERLAY --}}
<div id="loading-overlay"
    class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/80 backdrop-blur-md">
    <div class="bg-white p-10 rounded-[3rem] shadow-2xl text-center max-w-sm w-full mx-4">
        <div class="relative w-24 h-24 mx-auto mb-8">
            <div class="absolute inset-0 border-8 border-indigo-50 rounded-full"></div>
            <div class="absolute inset-0 border-8 border-indigo-600 border-t-transparent rounded-full animate-spin">
            </div>
            <div class="absolute inset-0 flex items-center justify-center text-4xl">⚙️</div>
        </div>
        <h3 class="text-2xl font-black text-slate-800">Menyusun...</h3>
        <p class="text-slate-500 text-sm mt-3 leading-relaxed font-medium">Algoritma sedang menghitung slot waktu
            terbaik untuk menghindari bentrok jadwal.</p>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
<style>
.vertical-text {
    writing-mode: vertical-lr;
    transform: rotate(180deg);
    white-space: nowrap;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
    border: 2px solid #fff;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

/* TomSelect Custom Styling */
.ts-control {
    border: none !important;
    background: transparent !important;
    padding: 10px 12px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    color: #334155 !important;
}

.ts-dropdown {
    border-radius: 1rem !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
    border: 1px solid #f1f5f9 !important;
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
        sortField: {
            field: "text",
            direction: "asc"
        },
        onInitialize: function() {
            this.control.classList.add('focus:ring-0');
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