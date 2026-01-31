@extends('layouts.app')

@section('content')
<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Jadwal Pelajaran Terpadu</h1>
            <p class="text-slate-500 text-sm mt-1">Generate otomatis jadwal anti-bentrok menggunakan AI Solver.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            {{-- Tombol Download Excel --}}
            <a href="{{ route('jadwal.export') }}"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg shadow-sm font-bold transition flex items-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg shadow-sm font-bold transition flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                        </path>
                    </svg>
                    Jalankan Solver AI
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
            <p class="text-slate-500 text-sm mt-2">AI sedang mencari kombinasi terbaik. Mohon tunggu.</p>
        </div>
    </div>

    {{-- Notifikasi --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show"
        class="mb-6 bg-emerald-50 border border-emerald-200 rounded-xl p-4 shadow-sm flex items-start gap-3">
        <div class="bg-emerald-100 p-2 rounded-full text-emerald-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-emerald-900">Generate Berhasil!</h3>
            <p class="text-emerald-700 text-sm mt-1">{{ session('success') }}</p>
        </div>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700 font-bold text-lg">&times;</button>
    </div>
    @endif

    {{-- Container Tabel dengan Sticky Behavior --}}
    <div class="bg-white shadow-md rounded-xl border border-slate-200 overflow-hidden mb-10">
        {{-- max-h-[80vh] untuk memicu scrollbar internal jika data sangat panjang --}}
        <div class="overflow-auto custom-scrollbar relative" style="max-height: 80vh;">
            <table class="w-full text-xs border-separate border-spacing-0 min-w-[1000px]">
                <thead>
                    {{-- Judul Tabel Utama --}}
                    <tr>
                        <th colspan="{{ 3 + $kelass->count() }}"
                            class="p-4 bg-slate-800 text-white font-bold text-center text-base uppercase tracking-wider sticky top-0 z-[60]">
                            Jadwal Pelajaran Tahun Ajaran {{ date('Y') }}/{{ date('Y')+1 }}
                        </th>
                    </tr>
                    {{-- Header Kolom --}}
                    <tr class="bg-slate-100 text-slate-800 font-bold text-center uppercase shadow-sm">
                        {{-- Sticky Top di bawah judul (top-14 menyesuaikan tinggi baris pertama) --}}
                        <th
                            class="p-3 border-b border-r border-slate-300 w-16 sticky top-[56px] left-0 z-[70] bg-slate-200">
                            Hari</th>
                        <th class="p-3 border-b border-r border-slate-300 w-10 sticky top-[56px] z-50 bg-slate-100">Jam
                        </th>
                        <th class="p-3 border-b border-r border-slate-300 w-24 sticky top-[56px] z-50 bg-slate-100">
                            Waktu</th>
                        @foreach($kelass as $kelas)
                        <th
                            class="p-3 border-b border-r border-slate-300 min-w-[140px] sticky top-[56px] z-40 bg-slate-100">
                            {{ $kelas->nama_kelas }}
                        </th>
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
                    $baseRows = ($maxJam - $startJam) + 1;
                    $istirahatRows = ($hari != 'Jumat') ? 2 : 0;
                    $rowSpanTotal = $baseRows + $istirahatRows;
                    @endphp

                    @for($jam = $startJam; $jam <= $maxJam; $jam++) <tr class="hover:bg-slate-50 transition">
                        {{-- Kolom Hari (Sticky Left & Span) --}}
                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="p-3 border-r border-b border-slate-300 bg-slate-50 font-bold text-center align-middle uppercase text-slate-700 sticky left-0 z-30 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                            <div class="vertical-text">{{ $hari }}</div>
                        </td>
                        @endif

                        {{-- Info Jam --}}
                        <td
                            class="p-2 border-r border-b border-slate-200 text-center font-bold text-slate-500 bg-slate-50/50">
                            {{ $jam }}
                        </td>

                        {{-- Info Waktu --}}
                        <td
                            class="p-2 border-r border-b border-slate-200 text-center text-[10px] font-mono text-slate-600 bg-white">
                            @php
                            if($hari == 'Senin') $w = $waktu['Senin'][$jam] ?? '-';
                            elseif($hari == 'Jumat') $w = $waktu['Jumat'][$jam] ?? '-';
                            else $w = $waktu['Default'][$jam] ?? '-';
                            @endphp
                            {{ $w }}
                        </td>

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
                        <td
                            class="p-1 border-r border-b border-slate-200 text-center align-middle h-16 {{ $data ? $data['color'] : 'bg-white' }} hover:brightness-95 transition relative group">
                            @if($data)
                            <div class="flex flex-col justify-center overflow-hidden">
                                <span
                                    class="font-extrabold text-slate-800 text-[10px] leading-tight line-clamp-2 uppercase">
                                    {{ $data['mapel'] }}
                                </span>
                                <span class="text-[9px] text-slate-600 leading-tight mt-1 font-medium italic">
                                    {{ $data['guru'] }}
                                </span>
                            </div>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        {{-- Baris Istirahat --}}
                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr class="bg-amber-100/50">
                            <td
                                class="p-1 border-r border-b border-slate-200 text-center font-bold text-amber-800 text-[10px]">
                                IST</td>
                            <td
                                class="p-1 border-r border-b border-slate-200 text-center text-[10px] font-mono font-bold text-slate-700">
                                {{ $jam==4 ? '10.30-10.45' : '13.30-13.50' }}
                            </td>
                            <td colspan="{{ $kelass->count() }}"
                                class="p-1 border-b border-slate-200 text-center font-bold text-amber-800 text-[10px] tracking-widest uppercase">
                                ☕ ISTIRAHAT
                            </td>
                        </tr>
                        @endif
                        @endfor

                        {{-- Gap antar hari --}}
                        <tr>
                            <td colspan="{{ $kelass->count() + 3 }}" class="bg-slate-800 h-0.5"></td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
/* Membuat teks hari tegak/vertikal di layar kecil jika perlu */
.vertical-text {
    writing-mode: vertical-lr;
    transform: rotate(180deg);
    white-space: nowrap;
}

@media (min-width: 768px) {
    .vertical-text {
        writing-mode: horizontal-tb;
        transform: none;
    }
}

/* Custom Scrollbar agar lebih cantik */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Menghilangkan gap putih pada sticky header */
th.sticky,
td.sticky {
    background-clip: padding-box;
}

.animate-scale-in {
    animation: scaleIn 0.3s ease-out forwards;
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
<script>
function showLoading() {
    document.getElementById('loading-overlay').classList.remove('hidden');
}
</script>
@endpush