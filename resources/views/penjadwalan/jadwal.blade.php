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
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-6 flex items-center justify-between p-4 bg-rose-50 border border-rose-100 rounded-xl shadow-sm text-rose-800 shrink-0 relative z-[90]">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-rose-100 rounded-full text-rose-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </div>
            <span class="font-bold text-sm">{{ session('error') }}</span>
        </div>
        <button @click="show = false" class="text-rose-400 hover:text-rose-700 transition">&times;</button>
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
                            Tahun Ajaran {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }}
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
                            onclick="if(confirm('Generate ulang akan menimpa jadwal lama. AI akan menyusun berdasarkan ketersediaan jam di Master Waktu. Lanjut?')) this.form.submit()"
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

        {{-- 2. TABLE SECTION (Freeze Pane Anti Bocor - DINAMIS) --}}
        <div class="flex-1 overflow-auto custom-scrollbar relative bg-slate-50/30 z-10">
            @if($kelass->isEmpty() || empty($jadwals))
            <div class="flex flex-col items-center justify-center h-full py-20 text-center">
                <div class="text-6xl mb-4 opacity-30">🗂️</div>
                <h3 class="text-lg font-bold text-slate-600">Data Tidak Ditemukan</h3>
                <p class="text-slate-400 text-sm mt-1">Sistem belum memiliki jadwal, data kelas, atau Master Waktu.</p>
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
                            class="h-[50px] w-[100px] min-w-[100px] max-w-[100px] border-r border-b border-slate-700 bg-[#242b3d] sticky top-0 left-[110px] z-[70] shadow-[4px_0_8px_-2px_rgba(0,0,0,0.15)]">
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
                            class="h-[40px] w-[100px] min-w-[100px] max-w-[100px] sticky top-[50px] left-[110px] z-[70] bg-white border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest text-slate-400 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.05)]">
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
                    @foreach($dataHari as $hariItem)
                    @php
                    $namaHari = $hariItem->nama_hari;
                    $namaHariLower = strtolower($namaHari);

                    // Hitung jumlah row yang aktif di hari ini (untuk rowspan kolom Hari)
                    $rowSpanTotal = 0;
                    foreach($dataWaktu as $w) {
                    $tipeCheck = $w->tipe;
                    if ($namaHariLower == 'senin' && $w->tipe_senin) $tipeCheck = $w->tipe_senin;
                    if ($namaHariLower == 'jumat' && $w->tipe_jumat) $tipeCheck = $w->tipe_jumat;
                    if ($tipeCheck !== 'Tidak Ada') $rowSpanTotal++;
                    }
                    $firstRow =ue;
                    @endphp

                    @if($rowSpanTotal > 0)
                    @foreach($dataWaktu as $waktuItem)
                    @php
                    $j = $waktuItem->jam_ke;

                    // Ambil Waktu & Tipe Dinamis Sesuai Hari
                    $waktuTampil = \Carbon\Carbon::parse($waktuItem->waktu_mulai)->format('H:i') . ' - ' .
                    \Carbon\Carbon::parse($waktuItem->waktu_selesai)->format('H:i');
                    $tipeTampil = $waktuItem->tipe;

                    if ($namaHariLower == 'senin' && $waktuItem->mulai_senin) {
                    $waktuTampil = \Carbon\Carbon::parse($waktuItem->mulai_senin)->format('H:i') . ' - ' .
                    \Carbon\Carbon::parse($waktuItem->selesai_senin)->format('H:i');
                    $tipeTampil = $waktuItem->tipe_senin;
                    } elseif ($namaHariLower == 'jumat' && $waktuItem->mulai_jumat) {
                    $waktuTampil = \Carbon\Carbon::parse($waktuItem->mulai_jumat)->format('H:i') . ' - ' .
                    \Carbon\Carbon::parse($waktuItem->selesai_jumat)->format('H:i');
                    $tipeTampil = $waktuItem->tipe_jumat;
                    }
                    @endphp

                    {{-- JIKA TIDAK ADA JAM (Misal Jumat pulang awal), JANGAN RENDER BARIS INI --}}
                    @if($tipeTampil !== 'Tidak Ada')
                    <tr class="hover:bg-slate-50 transition-colors duration-150">

                        {{-- Kolom HARI (Rowspan) --}}
                        @if($firstRow)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="w-[60px] min-w-[60px] max-w-[60px] sticky left-0 z-[40] p-0 bg-white border-r border-b border-slate-200 align-middle text-center shadow-[4px_0_8px_-2px_rgba(0,0,0,0.02)]">
                            <div class="font-extrabold text-slate-400 uppercase tracking-[0.2em] text-[12px] h-full flex items-center justify-center py-4 px-2"
                                style="writing-mode: vertical-lr; transform: rotate(180deg);">
                                {{ $namaHari }}
                            </div>
                        </td>
                        @php $firstRow = false; @endphp
                        @endif

                        {{-- Kolom JP --}}
                        <td
                            class="w-[50px] min-w-[50px] max-w-[50px] sticky left-[60px] z-[40] p-2 bg-white border-r border-b border-slate-200 text-center font-bold text-slate-600 text-[11px]">
                            @if($tipeTampil == 'Istirahat')
                            <span class="text-amber-500">IST</span>
                            @else
                            {{ $j }}
                            @endif
                        </td>

                        {{-- Kolom WAKTU --}}
                        <td
                            class="w-[100px] min-w-[100px] max-w-[100px] sticky left-[110px] z-[40] p-2 bg-white border-r border-b border-slate-200 text-center text-[10px] font-mono font-medium text-slate-400 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.05)]">
                            @php $w_parts = explode(' - ', $waktuTampil); @endphp
                            <div class="flex flex-col items-center justify-center space-y-0.5">
                                <span>{{ $w_parts[0] ?? '' }}</span>
                                <span class="text-[8px] text-slate-300 leading-none">sd</span>
                                <span>{{ $w_parts[1] ?? '' }}</span>
                            </div>
                        </td>

                        {{-- Kolom Sel Jadwal per Kelas --}}
                        @if($tipeTampil == 'Istirahat')
                        <td colspan="{{ $kelass->count() }}"
                            class="p-2 border-b border-slate-100 bg-amber-50/30 align-middle z-[10] relative">
                            <div
                                class="w-full h-[60px] bg-amber-100/50 border border-amber-200/50 rounded flex items-center justify-center p-1.5">
                                <span class="font-extrabold text-amber-500 text-[10px] tracking-[0.3em] uppercase">☕
                                    WAKTU ISTIRAHAT</span>
                            </div>
                        </td>
                        @elseif(in_array($tipeTampil, ['Upacara', 'Senam', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka']))
                        <td colspan="{{ $kelass->count() }}"
                            class="p-2 border-b border-slate-100 bg-cyan-50/30 align-middle z-[10] relative">
                            <div
                                class="w-full h-[60px] bg-cyan-100/50 border border-cyan-200/50 rounded flex items-center justify-center p-1.5">
                                <span class="font-extrabold text-cyan-600 text-[10px] tracking-[0.3em] uppercase">✨
                                    {{ $tipeTampil }}</span>
                            </div>
                        </td>
                        @else
                        {{-- SEL BELAJAR BIASA --}}
                        @foreach($kelass as $kelas)
                        @php
                        $data = $jadwals[$kelas->id][$namaHari][$j] ?? null;
                        // Pastikan tidak merender sel yang kosong dari logika Controller (yang sudah di unset)
                        if($data && $data['tipe'] == 'empty' && empty($data['mapel'])) {
                        $data = null;
                        }
                        @endphp

                        <td class="p-2 border-r border-b border-slate-100 text-center align-middle h-[75px] min-w-[170px] max-w-[170px] jadwal-cell transition-all duration-300 z-[10] relative bg-white"
                            data-search="{{ $data ? strtolower($data['mapel'].' '.$data['guru'].' '.$kelas->nama_kelas) : '' }}"
                            data-kelas="{{ strtolower($kelas->nama_kelas) }}">

                            @if($data)
                            <div
                                class="w-full h-full rounded-xl flex flex-col justify-center items-center px-2 py-1.5 {{ $data['color'] ?? 'bg-[#f5f3ff] text-[#6d28d9]' }} cursor-pointer transition-all duration-300 relative group sel-content border border-transparent hover:border-indigo-200">
                                <span
                                    class="font-bold text-[13px] leading-tight mb-1 line-clamp-1 text-slate-800">{{ $data['mapel'] }}</span>
                                <span
                                    class="text-[10px] font-medium leading-tight line-clamp-1 opacity-90 text-indigo-600">{{ $data['guru'] }}</span>
                            </div>
                            @else
                            <div
                                class="w-full h-full rounded-xl border border-dashed border-slate-200 bg-slate-50 flex flex-col items-center justify-center transition-all duration-300 sel-content group hover:bg-slate-100 cursor-not-allowed">
                                <span
                                    class="text-[10px] font-bold text-slate-300 group-hover:text-slate-400">KOSONG</span>
                            </div>
                            @endif
                        </td>
                        @endforeach
                        @endif
                    </tr>
                    @endif
                    @endforeach

                    {{-- Pemisah Antar Hari (Garis Tebal) --}}
                    <tr class="z-[10] relative">
                        <td colspan="{{ $kelass->count() + 3 }}" class="bg-slate-200 h-[3px] z-[10] relative"></td>
                    </tr>
                    @endif
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
                Secure AI Core
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
        <h3 class="text-xl font-extrabold text-slate-800">AI Sedang Bekerja...</h3>
        <p class="text-slate-500 text-sm mt-2 font-medium">Memproses ribuan kombinasi algoritma constraint programming
            untuk mencegah jadwal bentrok.</p>
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
    width: 10px;
    height: 10px;
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
            if (innerBox) innerBox.classList.remove('ring-4', 'ring-indigo-400', 'ring-offset-2',
                'scale-[1.05]');
        });
        headers.forEach(header => header.style.opacity = '1');
        return;
    }

    let classMatched = false;

    cells.forEach(cell => {
        const searchData = cell.getAttribute('data-search');
        const dataKelas = cell.getAttribute('data-kelas');

        if (dataKelas === input) classMatched = true;

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
            if (innerBox) innerBox.classList.remove('ring-4', 'ring-indigo-400', 'ring-offset-2',
                'scale-[1.05]');
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