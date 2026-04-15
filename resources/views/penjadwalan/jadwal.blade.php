@extends('layouts.app')

@section('content')
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

<div class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 h-[calc(100vh-4rem)] pb-2 pt-2 flex flex-col">

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-emerald-50 border border-emerald-100 rounded-lg shadow-sm text-emerald-800 shrink-0">
        <div class="flex items-center gap-2">
            <span class="font-bold text-xs">✅ {{ session('success') }}</span>
            @if(session('waktu_komputasi'))
            <span class="px-2 py-0.5 rounded bg-emerald-200/50 text-emerald-700 text-[9px] font-bold uppercase">⏱️
                {{ session('waktu_komputasi') }} Dtk</span>
            @endif
        </div>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-rose-50 border border-rose-100 rounded-lg shadow-sm text-rose-800 shrink-0">
        <span class="font-bold text-xs">❌ {{ session('error') }}</span>
        <button @click="show = false" class="text-rose-400 hover:text-rose-700">&times;</button>
    </div>
    @endif

    {{-- MAIN CARD UI (TANPA TABS) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-md flex flex-col flex-1 overflow-hidden min-h-0">

        {{-- HEADER SECTION (TOMBOL GENERATE & EXPORT) --}}
        <div class="px-6 py-4 bg-white shrink-0 z-20 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">

                {{-- Judul --}}
                <div class="flex items-center gap-3">
                    <div class="w-2 h-10 bg-indigo-600 rounded-full"></div>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-800 leading-none">Jadwal Pelajaran Terpadu</h1>
                        <p class="text-slate-500 text-xs mt-1 font-medium">T.A
                            {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }}</p>
                    </div>
                </div>

                {{-- Action Buttons & Search --}}
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-end">

                    {{-- Search Bar --}}
                    <div class="relative group w-full md:w-48">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="search-jadwal" oninput="filterJadwalRealtime()"
                            class="block w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition"
                            placeholder="Cari Guru/Mapel...">
                    </div>

                    {{-- TOMBOL EXPORT EXCEL --}}
                    <a href="{{ route('jadwal.export') }}"
                        class="flex items-center gap-2 px-4 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 font-bold text-xs uppercase rounded-xl shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Export Excel
                    </a>

                    {{-- TOMBOL GENERATE AI --}}
                    <form action="{{ route('jadwal.generate') }}" method="POST" onsubmit="showLoading()"
                        class="m-0 p-0">
                        @csrf
                        <button type="button"
                            onclick="if(confirm('Peringatan: Generate ulang akan menyusun ulang jadwal OFFLINE. Jadwal Online tidak akan terpengaruh. Lanjut?')) this.form.submit()"
                            class="flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-indigo-600 text-white font-bold text-xs uppercase rounded-xl shadow-md hover:shadow-lg transition-all transform active:scale-95">
                            <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Generate AI
                        </button>
                    </form>

                </div>
            </div>
        </div>

        {{-- TABLE SECTION (SATU TABEL UNTUK SEMUA HARI) --}}
        <div class="w-full flex-1 overflow-auto custom-scrollbar relative bg-slate-50/50 z-10 flex flex-col">
            @if($kelass->isEmpty() || empty($jadwals))
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="text-4xl mb-2 opacity-30">🗂️</div>
                <h3 class="text-sm font-bold text-slate-600">Data Jadwal Fisik Belum Dibuat / Kosong</h3>
            </div>
            @else
            <div class="flex-1">
                <table class="w-full min-w-max border-separate border-spacing-0 text-left" id="jadwal-tabel-main">

                    @if($dataHari->isEmpty())
                    <tbody>
                        <tr>
                            <td class="text-center py-20 text-slate-400 font-bold">Tidak ada data hari aktif.</td>
                        </tr>
                    </tbody>
                    @else
                    <thead>
                        <tr class="text-slate-600 bg-white shadow-sm">
                            <th
                                class="h-[40px] w-[40px] min-w-[40px] sticky top-0 left-0 z-[60] bg-white border-r border-b border-slate-200 text-center font-extrabold text-[10px] uppercase tracking-widest">
                                HARI</th>
                            <th
                                class="h-[40px] w-[35px] min-w-[35px] sticky top-0 left-[40px] z-[60] bg-white border-r border-b border-slate-200 text-center font-extrabold text-[10px] uppercase tracking-widest">
                                JP</th>
                            <th
                                class="h-[40px] w-[90px] min-w-[90px] sticky top-0 left-[75px] z-[60] bg-white border-r border-b border-slate-200 text-center font-extrabold text-[10px] uppercase tracking-widest shadow-[2px_0_5px_-1px_rgba(0,0,0,0.05)]">
                                WAKTU</th>
                            @foreach($kelass as $kelas)
                            <th class="h-[40px] min-w-[140px] max-w-[140px] bg-slate-100 sticky top-0 z-[50] border-r border-b border-slate-200 text-center font-extrabold text-[11px] uppercase tracking-widest text-slate-700 jadwal-header"
                                data-kelas="{{ strtolower($kelas->nama_kelas) }}">
                                {{ $kelas->nama_kelas }}
                            </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="bg-white">
                        @foreach($dataHari as $hariItem)
                        @php
                        $namaHari = $hariItem->nama_hari;

                        // Filter hanya slot jam yang terisi (Abaikan tipe "Tidak Ada")
                        // Filter dan PAKSA URUTKAN berdasarkan jam dinding (waktu_mulai)
                        $waktuAktif = $hariItem->waktuHaris->filter(function($w) {
                        return $w->tipe !== 'Tidak Ada';
                        })->sortBy('waktu_mulai');

                        $rowSpanTotal = $waktuAktif->count();
                        $firstRow = true;
                        @endphp

                        @if($rowSpanTotal > 0)
                        @foreach($waktuAktif as $waktuItem)
                        @php
                        $j = $waktuItem->jam_ke;
                        $waktuTampil = \Carbon\Carbon::parse($waktuItem->waktu_mulai)->format('H.i') . ' - ' .
                        \Carbon\Carbon::parse($waktuItem->waktu_selesai)->format('H.i');
                        $tipeTampil = $waktuItem->tipe;
                        @endphp

                        <tr class="hover:bg-slate-50/80 transition-colors">
                            @if($firstRow)
                            <td rowspan="{{ $rowSpanTotal }}"
                                class="w-[40px] min-w-[40px] sticky left-0 z-[30] p-0 bg-white border-r border-b border-slate-200 align-middle text-center shadow-[2px_0_5px_-1px_rgba(0,0,0,0.02)]">
                                <div class="font-extrabold text-slate-700 uppercase tracking-widest text-[12px] h-full flex items-center justify-center py-2"
                                    style="writing-mode: vertical-lr; transform: rotate(180deg);">
                                    {{ $namaHari }}
                                </div>
                            </td>
                            @php $firstRow = false; @endphp
                            @endif

                            <td
                                class="h-[45px] w-[35px] min-w-[35px] sticky left-[40px] z-[30] p-1 bg-white border-r border-b border-slate-200 text-center font-bold text-slate-700 text-[11px]">
                                @if(!in_array($tipeTampil, ['Istirahat', 'Upacara', 'Senam', 'Sholat', 'Sholat Dhuha',
                                'Jumat Bersih', 'Pramuka']))
                                {{ $j }}
                                @endif
                            </td>

                            <td
                                class="h-[45px] w-[90px] min-w-[90px] sticky left-[75px] z-[30] p-1 bg-white border-r border-b border-slate-200 text-center text-[10px] font-mono font-medium text-slate-700 shadow-[2px_0_5px_-1px_rgba(0,0,0,0.05)]">
                                {{ $waktuTampil }}
                            </td>

                            {{-- PERBAIKAN: SEMUA TIPE NON-BELAJAR DIBUAT MENYATU --}}
                            @if(in_array($tipeTampil, ['Istirahat', 'Upacara', 'Senam', 'Sholat', 'Sholat Dhuha', 'Jumat
                            Bersih', 'Pramuka']))
                            <td colspan="{{ $kelass->count() }}"
                                class="h-[45px] p-1 border-b border-slate-200 bg-slate-50 align-middle">
                                <div class="w-full h-full rounded flex items-center justify-center">
                                    <span
                                        class="font-bold text-slate-500 text-[11px] tracking-[0.2em] uppercase italic">
                                        {{ $tipeTampil == 'Sholat Dhuha' ? 'SHOLAT DHUHA / LITERASI' : $tipeTampil }}
                                    </span>
                                </div>
                            </td>
                            @else
                            @foreach($kelass as $kelas)
                            @php
                            $data = $jadwals[$kelas->id][$namaHari][$j] ?? null;
                            @endphp

                            <td class="h-[45px] p-1 border-r border-b border-slate-200 text-center align-middle min-w-[140px] max-w-[140px] w-[140px] bg-white transition-all jadwal-cell"
                                data-search="{{ $data ? strtolower($data['mapel'].' '.$data['guru'].' '.$kelas->nama_kelas) : '' }}"
                                data-kelas="{{ strtolower($kelas->nama_kelas) }}">

                                @if($data)
                                <div
                                    class="w-full h-full flex flex-col justify-center items-center px-1 {{ $data['color'] }} group sel-content rounded-md border border-slate-100">
                                    <span
                                        class="font-bold text-[11px] leading-tight text-slate-800 break-words line-clamp-1">{{ $data['mapel'] }}</span>
                                    <span
                                        class="text-[9px] font-medium leading-none mt-0.5 text-slate-500 truncate max-w-full">{{ $data['kode_guru'] }}</span>
                                </div>
                                @else
                                <div
                                    class="w-full h-full flex flex-col items-center justify-center sel-content opacity-30">
                                    <span class="text-[9px] font-bold text-slate-300">-</span>
                                </div>
                                @endif
                            </td>
                            @endforeach
                            @endif
                        </tr>
                        @endforeach

                        {{-- BARIS WALI KELAS DI BAWAH JADWAL FISIK (PER HARI) --}}
                        <tr class="bg-indigo-50/50">
                            <td colspan="3"
                                class="px-4 py-3 text-right font-extrabold text-indigo-800 text-[10px] tracking-widest uppercase border-b border-indigo-100">
                                Wali Kelas
                            </td>
                            @foreach($kelass as $kelas)
                            <td
                                class="px-4 py-3 text-center border-l border-b border-indigo-100 align-middle min-w-[140px] max-w-[140px] w-[140px]">
                                <div class="w-full flex flex-col items-center justify-center">
                                    @if($kelas->waliKelas)
                                    <span
                                        class="font-bold text-[10px] text-indigo-700 bg-white px-2 py-1 rounded shadow-sm border border-indigo-200">
                                        {{ $kelas->waliKelas->nama_guru }}
                                    </span>
                                    @else
                                    <span class="text-[9px] font-bold text-slate-400 italic">Belum Diatur</span>
                                    @endif
                                </div>
                            </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td colspan="{{ $kelass->count() + 3 }}" class="bg-slate-800 h-[2px] border-none p-0"></td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                    @endif
                </table>
            </div>
            @endif

            {{-- 3. TABEL KHUSUS KELAS DARING / ONLINE --}}
            @if(isset($onlineJadwals) && $onlineJadwals->isNotEmpty())
            <div class="w-full bg-slate-50 border-t-4 border-amber-400 p-6 z-10 shrink-0 mt-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="p-2 bg-amber-100 text-amber-600 rounded-lg shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                </path>
                            </svg>
                        </span>
                        <div>
                            <h2 class="font-extrabold text-slate-800 text-lg uppercase tracking-wider">Jadwal Kelas
                                Daring (Online)</h2>
                            <p class="text-slate-500 text-xs font-medium">Daftar mata pelajaran yang tidak memerlukan
                                ruang kelas fisik dan bebas penjadwalan.</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto bg-white rounded-xl shadow-sm border border-slate-200">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="bg-amber-50/50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-6 py-4 w-[10%] text-center border-r border-slate-100">No</th>
                                <th class="px-6 py-4 w-[25%] border-r border-slate-100">Kelas</th>
                                <th class="px-6 py-4 w-[35%] border-r border-slate-100">Mata Pelajaran</th>
                                <th class="px-6 py-4 w-[30%] border-r border-slate-100">Guru Pengampu</th>
                                <th class="px-6 py-4 text-center">Total Jam</th>
                            </tr>
                        </thead>
                        <tfoot class="divide-y divide-slate-100">
                            @foreach($onlineJadwals as $idx => $oj)
                            <tr class="hover:bg-amber-50/30 transition">
                                <td class="px-6 py-3 text-center font-bold text-slate-400 border-r border-slate-50">
                                    {{ $idx + 1 }}</td>
                                <td class="px-6 py-3 font-bold text-slate-700 border-r border-slate-50">
                                    {{ $oj->kelas->nama_kelas }}</td>
                                <td class="px-6 py-3 font-bold text-indigo-600 border-r border-slate-50">
                                    {{ $oj->mapel->nama_mapel }}</td>
                                <td class="px-6 py-3 text-slate-600 font-medium border-r border-slate-50">
                                    {{ $oj->guru->nama_guru }}</td>
                                <td class="px-6 py-3 text-center">
                                    <span
                                        class="bg-amber-100 text-amber-800 border border-amber-200 px-3 py-1 rounded-md font-bold text-xs shadow-sm">{{ $oj->jumlah_jam }}
                                        JP</span>
                                </td>
                            </tr>
                            @endforeach
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

{{-- LOADING OVERLAY --}}
<div id="loading-overlay"
    class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/70 backdrop-blur-sm transition-opacity">
    <div class="bg-white p-8 rounded-3xl shadow-2xl text-center max-w-sm mx-4 transform scale-105 transition-transform">
        <div class="relative w-20 h-20 mx-auto mb-6">
            <div class="absolute inset-0 border-4 border-slate-100 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin">
            </div>
            <div class="absolute inset-0 flex items-center justify-center text-3xl">🧠</div>
        </div>
        <h3 class="text-xl font-extrabold text-slate-800 mb-2">AI Sedang Menyusun...</h3>
        <p class="text-sm font-medium text-slate-500">Mencari kombinasi jadwal terbaik tanpa bentrok. Mohon tunggu
            sebentar.</p>
    </div>
</div>
@endsection

@push('styles')
<style>
table {
    border-collapse: separate;
    border-spacing: 0;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 10px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

[x-cloak] {
    display: none !important;
}
</style>
@endpush

@push('scripts')
<script>
function filterJadwalRealtime() {
    const input = document.getElementById('search-jadwal').value.toLowerCase().trim();
    const cells = document.querySelectorAll('.jadwal-cell');
    const headers = document.querySelectorAll('.jadwal-header');

    if (input === '') {
        cells.forEach(cell => {
            cell.style.opacity = '1';
            cell.style.filter = 'none';
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
        } else {
            cell.style.opacity = '0.15';
            cell.style.filter = 'grayscale(100%)';
        }
    });

    headers.forEach(header => {
        const headerKelas = header.getAttribute('data-kelas');
        header.style.opacity = (classMatched && headerKelas !== input) ? '0.2' : '1';
    });
}

function showLoading() {
    document.getElementById('loading-overlay').classList.remove('hidden');
}
</script>
@endpush