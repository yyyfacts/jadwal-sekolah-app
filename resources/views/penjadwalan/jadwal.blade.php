@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-slate-100 to-slate-50"></div>
</div>

<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 pb-10 pt-4 flex flex-col h-[calc(100vh-6rem)]">

    {{-- HEADER SECTION (Fixed Height) --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight flex items-center gap-2">
                <span class="w-2 h-6 bg-blue-600 rounded-full"></span>
                Jadwal Pelajaran Terpadu
            </h1>
            <p class="text-slate-500 text-sm mt-1 ml-4">Tahun Ajaran {{ date('Y') }}/{{ date('Y')+1 }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            {{-- Tombol Download Excel --}}
            <a href="{{ route('jadwal.export') }}"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg shadow-sm font-bold transition flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Export Excel
            </a>

            {{-- Tombol Generate AI --}}
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
                    Jalankan AI Solver
                </button>
            </form>
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show"
        class="mb-4 bg-emerald-50 border border-emerald-200 rounded-lg p-3 flex items-center gap-3 shrink-0">
        <div class="bg-emerald-100 p-1.5 rounded-full text-emerald-600"><svg class="w-4 h-4" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg></div>
        <div class="flex-1 text-sm text-emerald-800 font-medium">{{ session('success') }}</div>
        <button @click="show = false" class="text-emerald-500 hover:text-emerald-700">&times;</button>
    </div>
    @endif

    {{-- CONTAINER TABEL UTAMA (FLEX-1 agar mengisi sisa layar) --}}
    <div class="bg-white border border-slate-300 rounded-xl shadow-sm flex flex-col flex-1 overflow-hidden relative">

        {{-- WRAPPER SCROLLABLE (Kunci Sticky Header) --}}
        <div class="overflow-auto custom-scrollbar flex-1 w-full h-full relative">
            <table class="w-full text-xs border-collapse relative min-w-[1200px]">

                {{-- HEADER TABEL (STICKY TOP) --}}
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase sticky top-0 z-20 shadow-sm">
                    <tr>
                        {{-- Kolom Hari & Jam (Sticky Left & Top -> z-30) --}}
                        <th class="p-3 border-r border-b border-slate-300 w-12 sticky left-0 top-0 z-30 bg-slate-200">
                            Hari</th>
                        <th class="p-3 border-r border-b border-slate-300 w-10 sticky left-12 top-0 z-30 bg-slate-200">
                            Jam</th>
                        <th
                            class="p-3 border-r border-b border-slate-300 w-24 sticky left-[5.5rem] top-0 z-30 bg-slate-200">
                            Waktu</th>

                        {{-- Loop Kelas --}}
                        @foreach($kelass as $kelas)
                        <th class="p-3 border-r border-b border-slate-300 min-w-[120px] bg-slate-100">
                            {{ $kelas->nama_kelas }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200">
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
                    $rowSpan = ($maxJam - $startJam) + 1 + (($hari != 'Jumat') ? 1 : 0); // +1 untuk baris istirahat
                    @endphp

                    @for($jam = $startJam; $jam <= $maxJam; $jam++) <tr class="hover:bg-slate-50 transition group">

                        {{-- Kolom Hari (Sticky Left) --}}
                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpan }}"
                            class="p-0 border-r border-b border-slate-300 bg-white font-bold text-center align-middle text-slate-700 sticky left-0 z-10 w-12 writing-mode-vertical">
                            <div class="transform -rotate-180 py-4 uppercase tracking-widest text-xs">{{ $hari }}</div>
                        </td>
                        @endif

                        {{-- Kolom Jam (Sticky Left) --}}
                        <td
                            class="p-2 border-r border-slate-200 text-center font-bold text-slate-500 bg-slate-50 sticky left-12 z-10">
                            {{ $jam }}
                        </td>

                        {{-- Kolom Waktu (Sticky Left) --}}
                        <td
                            class="p-2 border-r border-slate-200 text-center text-[10px] font-mono text-slate-600 bg-slate-50 sticky left-[5.5rem] z-10">
                            @php
                            if($hari == 'Senin') $w = $waktu['Senin'][$jam] ?? '-';
                            elseif($hari == 'Jumat') $w = $waktu['Jumat'][$jam] ?? '-';
                            else $w = $waktu['Default'][$jam] ?? '-';
                            @endphp
                            {{ $w }}
                        </td>

                        {{-- Event Khusus (Jam 0) --}}
                        @if($jam == 0)
                        <td colspan="{{ $kelass->count() }}"
                            class="p-2 bg-slate-100 text-center font-bold text-slate-500 uppercase text-xs border-r border-slate-200 tracking-widest">
                            @if($hari == 'Senin') 🇮🇩 UPACARA BENDERA
                            @elseif($hari == 'Jumat') 🏃 SENAM / IMTAQ
                            @else 📖 LITERASI @endif
                        </td>
                        @else
                        {{-- Loop Kelas / Mapel --}}
                        @foreach($kelass as $kelas)
                        @php $data = $jadwals[$kelas->id][$hari][$jam] ?? null; @endphp
                        <td
                            class="p-1 border-r border-slate-200 text-center align-middle h-12 relative group/cell {{ $data ? 'bg-white hover:bg-blue-50' : 'bg-slate-50/30' }}">
                            @if($data)
                            <div class="flex flex-col justify-center h-full w-full px-1">
                                <span class="font-bold text-slate-800 text-[10px] leading-tight line-clamp-2">
                                    {{ $data['mapel'] }}
                                </span>
                                <span class="text-[9px] text-slate-500 leading-tight mt-0.5 truncate">
                                    {{ $data['guru'] }}
                                </span>
                                {{-- Tooltip --}}
                                <div
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover/cell:block z-50 bg-slate-800 text-white text-[10px] py-1 px-2 rounded shadow-lg whitespace-nowrap">
                                    {{ $data['kode_mapel'] }} - {{ $data['kode_guru'] }}
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
                            {{-- Sticky cell placeholders for Jam & Waktu on break --}}
                            <td
                                class="p-1 border-r border-orange-200 text-center font-bold text-orange-800 text-[10px] sticky left-12 z-10 bg-orange-50">
                                IST</td>
                            <td
                                class="p-1 border-r border-orange-200 text-center font-mono text-orange-800 text-[10px] sticky left-[5.5rem] z-10 bg-orange-50">
                                {{ $jam==4 ? '10.30-10.45' : '13.30-13.50' }}
                            </td>
                            <td colspan="{{ $kelass->count() }}"
                                class="p-1 text-center font-bold text-orange-800 text-xs tracking-[0.2em] uppercase">
                                ☕ ISTIRAHAT
                            </td>
                        </tr>
                        @endif

                        @endfor

                        {{-- Spacer antar Hari --}}
                        <tr class="bg-slate-300 h-1">
                            <td colspan="{{ 3 + $kelass->count() }}"></td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Footer Kecil --}}
    <div class="mt-2 text-center text-[10px] text-slate-400">
        &copy; Sistem Penjadwalan SMAN 1 SAMPANG - Auto Generated Layout
    </div>

</div>

{{-- Loading Overlay --}}
<div id="loading-overlay"
    class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/90 backdrop-blur-sm">
    <div class="bg-white p-6 rounded-xl shadow-xl text-center animate-bounce">
        <span class="text-4xl">🤖</span>
        <p class="mt-4 font-bold text-slate-700">AI Sedang Bekerja...</p>
    </div>
</div>

@endsection

@push('scripts')
<style>
/* Styling khusus untuk sticky dan scrollbar */
.writing-mode-vertical {
    writing-mode: vertical-rl;
    text-orientation: mixed;
}
</style>
<script>
function showLoading() {
    document.getElementById('loading-overlay').classList.remove('hidden');
}
</script>
@endpush