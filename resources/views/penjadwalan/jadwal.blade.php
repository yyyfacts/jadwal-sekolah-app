@extends('layouts.app')

@section('content')
{{-- BACKGROUND AMBIENT --}}
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

<div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 min-h-[calc(100vh-6rem)] pb-8 pt-6 flex flex-col">

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-6 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800 shrink-0 relative z-[90]">
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

    {{-- MAIN CARD UI --}}
    <div
        class="bg-white rounded-[2rem] shadow-[0_10px_40px_-10px_rgba(0,0,0,0.05)] border border-slate-100 flex flex-col flex-1 overflow-hidden">

        {{-- 1. HEADER SECTION --}}
        <div class="p-8 border-b border-slate-100 shrink-0 bg-white z-20 relative">
            <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-6 mb-6">

                {{-- Judul --}}
                <div class="flex gap-4 items-start">
                    <div class="w-2.5 h-12 bg-indigo-600 rounded-full mt-1"></div>
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                            Jadwal Pelajaran Terpadu
                        </h1>
                        <p class="text-slate-500 text-sm mt-1.5 font-medium">
                            Tahun Ajaran {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }} Semester Genap
                        </p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('jadwal.export') }}"
                        class="flex items-center justify-center gap-2 px-6 py-3 bg-white border border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50 font-bold text-xs uppercase tracking-widest rounded-xl transition-all shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        EXPORT EXCEL
                    </a>

                    <form action="{{ route('jadwal.generate') }}" method="POST" onsubmit="showLoading()">
                        @csrf
                        <button type="button"
                            onclick="if(confirm('Generate ulang akan menimpa jadwal lama. Lanjut?')) this.form.submit()"
                            class="flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl transition-all shadow-md shadow-indigo-500/30 hover:-translate-y-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            GENERATE JADWAL
                        </button>
                    </form>
                </div>
            </div>

            {{-- Live Search Bar --}}
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-indigo-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="search-jadwal" oninput="filterJadwalRealtime()"
                    class="block w-full pl-12 pr-4 py-4 bg-white border border-slate-200 rounded-xl text-[13px] font-medium text-slate-700 placeholder-slate-400 focus:outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 transition-all duration-300"
                    placeholder="Cari Nama Guru, Mata Pelajaran, atau Kelas... (Merespon Langsung)">
            </div>
        </div>

        {{-- 2. TABLE SECTION (Freeze Pane Anti Bocor) --}}
        <div class="flex-1 overflow-auto custom-scrollbar relative bg-slate-50/30 z-10">
            @if($kelass->isEmpty())
            <div class="flex flex-col items-center justify-center h-full py-20 text-center">
                <div class="text-6xl mb-4 opacity-30">🗂️</div>
                <h3 class="text-lg font-bold text-slate-600">Data Tidak Ditemukan</h3>
                <p class="text-slate-400 text-sm mt-1">Sistem belum memiliki jadwal atau data kelas.</p>
            </div>
            @else
            <table class="w-full min-w-max border-separate border-spacing-0" id="jadwal-tabel">
                <thead>
                    {{-- Row 1: Header Hitam Gelap --}}
                    <tr class="text-white">
                        <th
                            class="h-[50px] w-[60px] min-w-[60px] max-w-[60px] border-r border-b border-slate-700 bg-[#242b3d] sticky top-0 left-0 z-[70]">
                        </th>
                        <th
                            class="h-[50px] w-[50px] min-w-[50px] max-w-[50px] border-r border-b border-slate-700 bg-[#242b3d] sticky top-0 left-[60px] z-[70]">
                        </th>
                        <th
                            class="h-[50px] w-[90px] min-w-[90px] max-w-[90px] border-r border-b border-slate-700 bg-[#242b3d] sticky top-0 left-[110px] z-[70] shadow-[4px_0_8px_-2px_rgba(0,0,0,0.15)]">
                        </th>
                        @foreach($kelass as $kelas)
                        <th class="h-[50px] min-w-[170px] bg-[#242b3d] sticky top-0 z-[50] border-r border-b border-slate-700 text-center font-extrabold text-[14px] tracking-wider uppercase jadwal-header"
                            data-kelas="{{ strtolower($kelas->nama_kelas) }}">
                            {{ $kelas->nama_kelas }}
                        </th>
                        @endforeach
                    </tr>

                    {{-- Row 2: Sub-Header --}}
                    <tr class="text-slate-500">
                        <th
                            class="h-[40px] w-[60px] min-w-[60px] max-w-[60px] sticky top-[50px] left-0 z-[70] bg-white border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest text-slate-400">
                            HARI</th>
                        <th
                            class="h-[40px] w-[50px] min-w-[50px] max-w-[50px] sticky top-[50px] left-[60px] z-[70] bg-white border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest text-slate-400">
                            JP</th>
                        <th
                            class="h-[40px] w-[90px] min-w-[90px] max-w-[90px] sticky top-[50px] left-[110px] z-[70] bg-white border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest text-slate-400 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.05)]">
                            WAKTU</th>
                        @foreach($kelass as $kelas)
                        <th
                            class="h-[40px] min-w-[170px] bg-slate-50/80 sticky top-[50px] z-[60] border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest text-slate-400">
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
                        class="hover:bg-slate-50 transition-colors duration-150">

                        {{-- Kolom HARI --}}
                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="w-[60px] min-w-[60px] max-w-[60px] sticky left-0 z-[40] p-0 bg-white border-r border-b border-slate-200 align-middle text-center">
                            <div class="font-extrabold text-slate-400 uppercase tracking-[0.2em] text-[12px] h-full flex items-center justify-center py-4 px-2"
                                style="writing-mode: vertical-lr; transform: rotate(180deg);">
                                {{ $hari }}
                            </div>
                        </td>
                        @endif

                        {{-- Kolom JP --}}
                        <td
                            class="w-[50px] min-w-[50px] max-w-[50px] sticky left-[60px] z-[40] p-2 bg-white border-r border-b border-slate-200 text-center font-bold text-slate-600 text-[11px]">
                            {{ $jam }}
                        </td>

                        {{-- Kolom WAKTU --}}
                        <td
                            class="w-[90px] min-w-[90px] max-w-[90px] sticky left-[110px] z-[40] p-2 bg-white border-r border-b border-slate-200 text-center text-[10px] font-mono font-medium text-slate-400 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.05)]">
                            @php
                            $w = ($hari == 'Senin') ? $waktu['Senin'][$jam] : (($hari == 'Jumat') ?
                            $waktu['Jumat'][$jam] : $waktu['Default'][$jam]);
                            $w_parts = explode('-', $w);
                            @endphp
                            @if(count($w_parts) == 2)
                            <div class="flex flex-col items-center justify-center space-y-0.5">
                                <span>{{ trim($w_parts[0]) }}</span>
                                <span class="text-[8px] text-slate-300 leading-none">-</span>
                                <span>{{ trim($w_parts[1]) }}</span>
                            </div>
                            @else
                            {{ $w }}
                            @endif
                        </td>

                        {{-- Kolom Kelas / Sel Jadwal --}}
                        @if($jam == 0)
                        <td colspan="{{ $kelass->count() }}"
                            class="p-2 border-b border-slate-100 align-middle bg-slate-50/80 z-[10] relative">
                            <div
                                class="w-full h-full border border-slate-200 bg-white rounded-lg flex items-center justify-center p-2">
                                <span class="text-xs font-extrabold text-indigo-600 uppercase tracking-widest">
                                    @if($hari == 'Senin') 🇮🇩 UPACARA BENDERA @else 📖 IMTAQ / SENAM @endif
                                </span>
                            </div>
                        </td>
                        @else
                        @foreach($kelass as $kelas)
                        @php $data = $jadwals[$kelas->id][$hari][$jam] ?? null; @endphp

                        <td class="p-2 border-r border-b border-slate-100 text-center align-middle h-[75px] jadwal-cell transition-all duration-300 z-[10] relative bg-white"
                            data-search="{{ $data ? strtolower($data['mapel'].' '.$data['guru'].' '.$kelas->nama_kelas) : '' }}"
                            data-kelas="{{ strtolower($kelas->nama_kelas) }}">

                            @if($data)
                            <div
                                class="w-full h-full rounded-xl flex flex-col justify-center items-center px-2 py-1.5 {{ $data['color'] ?? 'bg-[#f5f3ff] text-[#6d28d9]' }} cursor-pointer transition-all duration-300 relative group sel-content">
                                <span
                                    class="font-bold text-[13px] leading-tight mb-1 line-clamp-1 text-slate-800">{{ $data['mapel'] }}</span>
                                <span
                                    class="text-[10px] font-medium leading-tight line-clamp-1 opacity-90 text-blue-600">{{ $data['guru'] }}</span>
                            </div>
                            @else
                            <div
                                class="w-full h-full flex items-center justify-center transition-all duration-300 opacity-20 sel-content">
                            </div>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        {{-- Row Istirahat --}}
                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr class="z-[10] relative">
                            <td
                                class="w-[50px] sticky left-[60px] z-[40] p-1 border-r border-b border-slate-200 bg-slate-50/80 text-center font-bold text-slate-400 text-[10px]">
                                IST
                            </td>
                            <td
                                class="w-[90px] sticky left-[110px] z-[40] p-1 border-r border-b border-slate-200 bg-slate-50/80 text-center text-[10px] font-mono font-medium text-slate-400 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.05)]">
                                @php
                                $w_ist = $jam==4 ? '10.00-10.15' : '13.30-13.50';
                                $w_ist_parts = explode('-', $w_ist);
                                @endphp
                                <div class="flex flex-col items-center justify-center space-y-0.5">
                                    <span>{{ trim($w_ist_parts[0]) }}</span>
                                    <span class="text-[8px] text-slate-300 leading-none">-</span>
                                    <span>{{ trim($w_ist_parts[1] ?? '') }}</span>
                                </div>
                            </td>
                            <td colspan="{{ $kelass->count() }}"
                                class="p-2 border-b border-slate-100 bg-slate-50/80 align-middle z-[10] relative">
                                <div
                                    class="w-full h-full bg-slate-100/80 rounded flex items-center justify-center p-1.5">
                                    <span class="font-bold text-slate-400 text-[10px] tracking-[0.3em] uppercase">☕
                                        Istirahat</span>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endfor

                        {{-- Pemisah Antar Hari --}}
                        <tr class="z-[10] relative">
                            <td colspan="{{ $kelass->count() + 3 }}"
                                class="bg-slate-100/50 h-3 border-b border-slate-200 z-[10] relative"></td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- 3. FOOTER SECTION --}}
        <div
            class="bg-white border-t border-slate-100 px-8 py-4 flex justify-between items-center shrink-0 z-20 relative">
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Sistem Penjadwalan
                Terintegrasi</span>
            <span class="text-[11px] font-bold text-emerald-500 flex items-center gap-1.5 uppercase tracking-wider">
                <svg class="w-4 h-4 bg-emerald-500 text-white rounded-full p-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
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
            <div class="absolute inset-0 border-4 border-slate-100 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin">
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
<style>
/* KEKAKUAN TABEL SUPER KETAT */
table {
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed;
}

/* Custom Scrollbar Clean */
.custom-scrollbar::-webkit-scrollbar {
    width: 12px;
    height: 12px;
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
</style>
@endpush

@push('scripts')
<script>
// ==========================================================
// MAGIC LIVE SEARCH JADWAL MATRIKS
// ==========================================================
function filterJadwalRealtime() {
    const input = document.getElementById('search-jadwal').value.toLowerCase().trim();
    const cells = document.querySelectorAll('.jadwal-cell');
    const headers = document.querySelectorAll('.jadwal-header');

    if (input === '') {
        cells.forEach(cell => {
            cell.style.opacity = '1';
            cell.style.filter = 'none';
            const innerBox = cell.querySelector('.sel-content');
            if (innerBox) {
                innerBox.classList.remove('ring-4', 'ring-indigo-400', 'ring-offset-2', 'scale-[1.05]');
            }
        });
        headers.forEach(header => {
            header.style.opacity = '1';
        });
        return;
    }

    let classMatched = false;

    cells.forEach(cell => {
        const searchData = cell.getAttribute('data-search');
        const dataKelas = cell.getAttribute('data-kelas');

        if (dataKelas === input) {
            classMatched = true;
        }

        if (searchData && searchData.includes(input)) {
            cell.style.opacity = '1';
            cell.style.filter = 'none';

            const innerBox = cell.querySelector('.sel-content');
            if (innerBox && !innerBox.classList.contains('opacity-20')) {
                innerBox.classList.add('ring-4', 'ring-indigo-400', 'ring-offset-2', 'scale-[1.05]');
            }
        } else {
            cell.style.opacity = '0.15';
            cell.style.filter = 'grayscale(100%)';
            const innerBox = cell.querySelector('.sel-content');
            if (innerBox) {
                innerBox.classList.remove('ring-4', 'ring-indigo-400', 'ring-offset-2', 'scale-[1.05]');
            }
        }
    });

    headers.forEach(header => {
        const headerKelas = header.getAttribute('data-kelas');
        if (classMatched) {
            header.style.opacity = (headerKelas === input) ? '1' : '0.2';
        } else {
            header.style.opacity = '1';
        }
    });
}

function showLoading() {
    document.getElementById('loading-overlay').classList.remove('hidden');
}
</script>
@endpush