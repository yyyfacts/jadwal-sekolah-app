@extends('layouts.app')

@section('content')
{{-- BACKGROUND AMBIENT --}}
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]">
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-blue-50/50 to-transparent"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-300/10 rounded-full blur-3xl opacity-70"></div>
</div>

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

    @if(session('rekomendasi'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex flex-col p-3 bg-amber-50 border border-amber-200 rounded-lg shadow-sm text-amber-800 shrink-0 relative">
        <span class="font-extrabold text-[11px] uppercase text-amber-700 mb-1">💡 Solusi:
            {{ session('target_error') }}</span>
        <div class="text-[11px] font-medium leading-relaxed text-amber-900/80">{!! nl2br(e(session('rekomendasi'))) !!}
        </div>
        <button @click="show = false"
            class="absolute top-2 right-2 text-amber-400 hover:text-amber-700">&times;</button>
    </div>
    @endif
    @endif

    {{-- MAIN CARD UI --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-md flex flex-col flex-1 overflow-hidden min-h-0">

        {{-- 1. HEADER SECTION --}}
        <div class="px-4 py-3 bg-white shrink-0 z-20 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">

                {{-- Judul --}}
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-8 bg-indigo-600 rounded-full"></div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 leading-none">Jadwal Pelajaran Terpadu</h1>
                        <p class="text-slate-500 text-[10px] mt-0.5 font-medium">T.A
                            {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }}</p>
                    </div>
                </div>

                {{-- Action Buttons & Search --}}
                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <div class="relative group w-full md:w-64">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="search-jadwal" oninput="filterJadwalRealtime()"
                            class="block w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none transition"
                            placeholder="Cari Guru/Mapel...">
                    </div>

                    <a href="{{ route('jadwal.export') }}"
                        class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-[10px] uppercase rounded-lg shadow-sm whitespace-nowrap">
                        Export
                    </a>

                    <form action="{{ route('jadwal.generate') }}" method="POST" onsubmit="showLoading()"
                        class="m-0 p-0">
                        @csrf
                        <button type="button"
                            onclick="if(confirm('Generate ulang akan menimpa jadwal lama. Lanjut?')) this.form.submit()"
                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[10px] uppercase rounded-lg shadow-sm whitespace-nowrap">
                            Generate
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- 2. TABLE SECTION --}}
        <div class="w-full flex-1 overflow-auto custom-scrollbar relative bg-slate-50/50 z-10">
            @if($kelass->isEmpty() || empty($jadwals))
            <div class="flex flex-col items-center justify-center h-full text-center">
                <div class="text-4xl mb-2 opacity-30">🗂️</div>
                <h3 class="text-sm font-bold text-slate-600">Data Tidak Ditemukan</h3>
            </div>
            @else
            <table class="w-full min-w-max border-separate border-spacing-0 text-left" id="jadwal-tabel">
                <thead>
                    <tr class="text-white shadow-sm">
                        <th
                            class="h-[35px] w-[50px] min-w-[50px] border-r border-b border-slate-700 bg-[#242b3d] sticky top-0 left-0 z-[60]">
                        </th>
                        <th
                            class="h-[35px] w-[40px] min-w-[40px] border-r border-b border-slate-700 bg-[#242b3d] sticky top-0 left-[50px] z-[60]">
                        </th>
                        <th
                            class="h-[35px] w-[90px] min-w-[90px] border-r border-b border-slate-700 bg-[#242b3d] sticky top-0 left-[90px] z-[60] shadow-[2px_0_5px_-1px_rgba(0,0,0,0.15)]">
                        </th>
                        @foreach($kelass as $kelas)
                        <th class="h-[35px] min-w-[150px] max-w-[150px] bg-[#242b3d] sticky top-0 z-[50] border-r border-b border-slate-700 text-center font-bold text-xs uppercase jadwal-header"
                            data-kelas="{{ strtolower($kelas->nama_kelas) }}">
                            {{ $kelas->nama_kelas }}
                        </th>
                        @endforeach
                    </tr>

                    <tr class="text-slate-500 bg-white shadow-sm">
                        <th
                            class="h-[30px] w-[50px] min-w-[50px] sticky top-[35px] left-0 z-[60] bg-white border-r border-b border-slate-200 text-center font-bold text-[9px] uppercase tracking-widest text-slate-400">
                            HARI</th>
                        <th
                            class="h-[30px] w-[40px] min-w-[40px] sticky top-[35px] left-[50px] z-[60] bg-white border-r border-b border-slate-200 text-center font-bold text-[9px] uppercase tracking-widest text-slate-400">
                            JP</th>
                        <th
                            class="h-[30px] w-[90px] min-w-[90px] sticky top-[35px] left-[90px] z-[60] bg-white border-r border-b border-slate-200 text-center font-bold text-[9px] uppercase tracking-widest text-slate-400 shadow-[2px_0_5px_-1px_rgba(0,0,0,0.05)]">
                            WAKTU</th>
                        @foreach($kelass as $kelas)
                        <th
                            class="h-[30px] min-w-[150px] max-w-[150px] bg-slate-50 sticky top-[35px] z-[40] border-r border-b border-slate-200 text-center font-bold text-[9px] uppercase tracking-widest text-slate-400">
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

                    $rowSpanTotal = 0;
                    foreach($dataWaktu as $w) {
                    $tipeCheck = $w->tipe;
                    if ($namaHariLower == 'senin' && $w->tipe_senin) $tipeCheck = $w->tipe_senin;
                    if ($namaHariLower == 'jumat' && $w->tipe_jumat) $tipeCheck = $w->tipe_jumat;
                    if ($tipeCheck !== 'Tidak Ada') $rowSpanTotal++;
                    }
                    $firstRow = true;
                    @endphp

                    @if($rowSpanTotal > 0)
                    @foreach($dataWaktu as $waktuItem)
                    @php
                    $j = $waktuItem->jam_ke;
                    $waktuTampil = \Carbon\Carbon::parse($waktuItem->waktu_mulai)->format('H:i') . '-' .
                    \Carbon\Carbon::parse($waktuItem->waktu_selesai)->format('H:i');
                    $tipeTampil = $waktuItem->tipe;

                    if ($namaHariLower == 'senin' && $waktuItem->mulai_senin) {
                    $waktuTampil = \Carbon\Carbon::parse($waktuItem->mulai_senin)->format('H:i') . '-' .
                    \Carbon\Carbon::parse($waktuItem->selesai_senin)->format('H:i');
                    $tipeTampil = $waktuItem->tipe_senin;
                    } elseif ($namaHariLower == 'jumat' && $waktuItem->mulai_jumat) {
                    $waktuTampil = \Carbon\Carbon::parse($waktuItem->mulai_jumat)->format('H:i') . '-' .
                    \Carbon\Carbon::parse($waktuItem->selesai_jumat)->format('H:i');
                    $tipeTampil = $waktuItem->tipe_jumat;
                    }
                    @endphp

                    @if($tipeTampil !== 'Tidak Ada')
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        @if($firstRow)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="w-[50px] min-w-[50px] sticky left-0 z-[30] p-0 bg-white border-r border-b border-slate-200 align-middle text-center shadow-[2px_0_5px_-1px_rgba(0,0,0,0.02)]">
                            <div class="font-extrabold text-slate-400 uppercase tracking-widest text-[11px] h-full flex items-center justify-center py-2"
                                style="writing-mode: vertical-lr; transform: rotate(180deg);">
                                {{ $namaHari }}
                            </div>
                        </td>
                        @php $firstRow = false; @endphp
                        @endif

                        {{-- INI YANG DIUBAH: Selalu Tampilkan Angka (1, 2, 3, 4, dst) --}}
                        <td
                            class="w-[40px] min-w-[40px] sticky left-[50px] z-[30] p-1 bg-white border-r border-b border-slate-200 text-center font-bold text-slate-600 text-[10px]">
                            {{ $j }}
                        </td>

                        <td
                            class="w-[90px] min-w-[90px] sticky left-[90px] z-[30] p-1 bg-white border-r border-b border-slate-200 text-center text-[9px] font-mono font-bold text-slate-500 shadow-[2px_0_5px_-1px_rgba(0,0,0,0.05)]">
                            {{ $waktuTampil }}
                        </td>

                        @if($tipeTampil == 'Istirahat')
                        <td colspan="{{ $kelass->count() }}"
                            class="p-1 border-b border-slate-200 bg-amber-50/60 align-middle">
                            <div
                                class="w-full py-1 bg-amber-100/50 border border-amber-200/50 rounded flex items-center justify-center">
                                <span
                                    class="font-bold text-amber-500 text-[9px] tracking-widest uppercase">ISTIRAHAT</span>
                            </div>
                        </td>
                        @elseif(in_array($tipeTampil, ['Upacara', 'Senam', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka']))
                        <td colspan="{{ $kelass->count() }}"
                            class="p-1 border-b border-slate-200 bg-cyan-50/60 align-middle">
                            <div
                                class="w-full py-1 bg-cyan-100/50 border border-cyan-200/50 rounded flex items-center justify-center">
                                <span
                                    class="font-bold text-cyan-600 text-[9px] tracking-widest uppercase">{{ $tipeTampil }}</span>
                            </div>
                        </td>
                        @else
                        @foreach($kelass as $kelas)
                        @php
                        $data = $jadwals[$kelas->id][$namaHari][$j] ?? null;
                        if($data && $data['tipe'] == 'empty' && empty($data['mapel'])) $data = null;
                        @endphp

                        <td class="p-1 border-r border-b border-slate-100 text-center align-middle h-[55px] min-w-[150px] w-[150px] jadwal-cell transition-all bg-white"
                            data-search="{{ $data ? strtolower($data['mapel'].' '.$data['guru'].' '.$kelas->nama_kelas) : '' }}"
                            data-kelas="{{ strtolower($kelas->nama_kelas) }}">

                            @if($data)
                            <div
                                class="w-full h-full rounded-lg flex flex-col justify-center items-center px-1 py-1 {{ $data['color'] ?? 'bg-[#f5f3ff] text-[#6d28d9]' }} group sel-content border border-transparent">
                                <span
                                    class="font-bold text-[11px] leading-tight text-slate-800 break-words line-clamp-1">{{ $data['mapel'] }}</span>
                                <span
                                    class="text-[9px] font-medium leading-tight text-indigo-600 truncate max-w-full">{{ $data['guru'] }}</span>
                            </div>
                            @else
                            <div
                                class="w-full h-full rounded-lg border border-dashed border-slate-200 bg-slate-50 flex flex-col items-center justify-center sel-content opacity-50">
                                <span class="text-[9px] font-bold text-slate-300">KOSONG</span>
                            </div>
                            @endif
                        </td>
                        @endforeach
                        @endif
                    </tr>
                    @endif
                    @endforeach
                    <tr>
                        <td colspan="{{ $kelass->count() + 3 }}" class="bg-slate-300 h-0.5 border-none p-0"></td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>

{{-- LOADING OVERLAY --}}
<div id="loading-overlay"
    class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-opacity">
    <div class="bg-white p-6 rounded-2xl shadow-2xl text-center max-w-xs mx-4">
        <div class="relative w-16 h-16 mx-auto mb-4">
            <div class="absolute inset-0 border-4 border-slate-100 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin">
            </div>
            <div class="absolute inset-0 flex items-center justify-center text-2xl">⚙️</div>
        </div>
        <h3 class="text-lg font-bold text-slate-800">AI Sedang Bekerja...</h3>
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