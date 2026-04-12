@extends('layouts.app')

@section('content')
{{-- BACKGROUND AMBIENT --}}
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]">
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-blue-50/50 to-transparent"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-300/10 rounded-full blur-3xl opacity-70"></div>
</div>

{{-- 
    PERUBAHAN UTAMA LAYOUT: 
    1. Buang height statis seperti h-screen atau h-[calc(...)]
    2. Pakai min-h-screen agar kalau jadwalnya dikit tetep full, kalau banyak dia melar ke bawah.
--}}
<div class="w-full max-w-[100vw] mx-auto px-4 sm:px-6 lg:px-8 min-h-screen pb-12 pt-6 flex flex-col">

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-6 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800 shrink-0">
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

    {{-- ERROR & REKOMENDASI AI --}}
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-3 flex items-center justify-between p-4 bg-rose-50 border border-rose-100 rounded-xl shadow-sm text-rose-800 shrink-0">
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

    {{-- KOTAK REKOMENDASI DSS --}}
    @if(session('rekomendasi'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-6 flex flex-col p-5 bg-amber-50 border border-amber-200 rounded-xl shadow-sm text-amber-800 shrink-0 relative">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-1.5 bg-amber-200 rounded-full text-amber-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <span class="font-extrabold text-[13px] uppercase tracking-wider text-amber-700">
                💡 Rekomendasi Solusi untuk: <span
                    class="text-indigo-600 border-b border-indigo-300 pb-0.5">{{ session('target_error') }}</span>
            </span>
        </div>
        <div class="pl-11 text-[13px] font-medium leading-relaxed text-amber-900/80">
            {!! nl2br(e(session('rekomendasi'))) !!}
        </div>
        <button @click="show = false"
            class="absolute top-4 right-4 text-amber-400 hover:text-amber-700 transition">&times;</button>
    </div>
    @endif
    @endif

    {{-- MAIN CARD UI --}}
    {{-- Card nggak dipaksa max-height, jadi dia akan memanjang terus ke bawah mengikuti isinya --}}
    <div
        class="bg-white rounded-[2rem] border border-slate-100 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.08)] flex flex-col w-full">

        {{-- 1. HEADER SECTION --}}
        <div class="px-8 pt-8 pb-6 bg-white shrink-0 z-20 rounded-t-[2rem]">
            <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-6 mb-6">
                {{-- Judul --}}
                <div class="flex gap-4 items-start">
                    <div class="w-2.5 h-12 bg-indigo-600 rounded-full mt-0.5"></div>
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                            Jadwal Pelajaran Terpadu
                        </h1>
                        <p class="text-slate-500 text-sm mt-1 font-medium">
                            Tahun Ajaran {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }}
                        </p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('jadwal.export') }}"
                        class="flex items-center justify-center gap-2 px-6 py-2.5 bg-white border border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50 font-bold text-xs uppercase tracking-widest rounded-xl transition-all shadow-sm">
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
                            class="flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl transition-all shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/40 hover:-translate-y-0.5">
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

            {{-- Search Bar --}}
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-indigo-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="search-jadwal" oninput="filterJadwalRealtime()"
                    class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-700 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                    placeholder="Cari Nama Guru, Mata Pelajaran, atau Kelas... (Merespon Langsung)">
            </div>
        </div>

        {{-- 2. TABLE SECTION (Ini dia kuncinya, biarkan overflow-x-auto jalan buat geser kiri-kanan) --}}
        <div
            class="w-full overflow-x-auto overflow-y-clip custom-scrollbar relative bg-slate-50/50 pb-8 px-2 rounded-b-[2rem]">
            @if($kelass->isEmpty() || empty($jadwals))
            <div class="flex flex-col items-center justify-center py-32 text-center">
                <div class="text-6xl mb-4 opacity-30">🗂️</div>
                <h3 class="text-lg font-bold text-slate-600">Data Tidak Ditemukan</h3>
                <p class="text-slate-400 text-sm mt-1">Sistem belum memiliki jadwal, data kelas, atau Master Waktu.</p>
            </div>
            @else
            <table class="w-full min-w-max border-separate border-spacing-0 text-left" id="jadwal-tabel">
                <thead>
                    {{-- Row 1: Header Hitam Gelap --}}
                    <tr class="text-white shadow-sm">
                        <th
                            class="h-[50px] w-[60px] min-w-[60px] border-r border-b border-slate-700 bg-[#242b3d] sticky top-0 left-0 z-[60]">
                        </th>
                        <th
                            class="h-[50px] w-[50px] min-w-[50px] border-r border-b border-slate-700 bg-[#242b3d] sticky top-0 left-[60px] z-[60]">
                        </th>
                        <th
                            class="h-[50px] w-[100px] min-w-[100px] border-r border-b border-slate-700 bg-[#242b3d] sticky top-0 left-[110px] z-[60] shadow-[4px_0_8px_-2px_rgba(0,0,0,0.15)]">
                        </th>
                        @foreach($kelass as $kelas)
                        <th class="h-[50px] min-w-[180px] bg-[#242b3d] sticky top-0 z-[50] border-r border-b border-slate-700 text-center font-extrabold text-sm tracking-wider uppercase jadwal-header shadow-sm"
                            data-kelas="{{ strtolower($kelas->nama_kelas) }}">
                            {{ $kelas->nama_kelas }}
                        </th>
                        @endforeach
                    </tr>

                    {{-- Row 2: Sub-Header Putih --}}
                    <tr class="text-slate-500 bg-white shadow-sm">
                        <th
                            class="h-[40px] w-[60px] min-w-[60px] sticky top-[50px] left-0 z-[60] bg-white border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest text-slate-400">
                            HARI</th>
                        <th
                            class="h-[40px] w-[50px] min-w-[50px] sticky top-[50px] left-[60px] z-[60] bg-white border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest text-slate-400">
                            JP</th>
                        <th
                            class="h-[40px] w-[100px] min-w-[100px] sticky top-[50px] left-[110px] z-[60] bg-white border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest text-slate-400 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.05)]">
                            WAKTU</th>
                        @foreach($kelass as $kelas)
                        <th
                            class="h-[40px] min-w-[180px] bg-white sticky top-[50px] z-[40] border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest text-slate-400">
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

                    @if($tipeTampil !== 'Tidak Ada')
                    <tr class="hover:bg-slate-50/50 transition-colors duration-150">

                        {{-- Kolom HARI --}}
                        @if($firstRow)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="w-[60px] min-w-[60px] sticky left-0 z-[30] p-0 bg-white border-r border-b border-slate-200 align-middle text-center shadow-[4px_0_8px_-2px_rgba(0,0,0,0.02)]">
                            <div class="font-extrabold text-slate-400 uppercase tracking-[0.2em] text-[12px] h-full flex items-center justify-center py-4 px-2"
                                style="writing-mode: vertical-lr; transform: rotate(180deg);">
                                {{ $namaHari }}
                            </div>
                        </td>
                        @php $firstRow = false; @endphp
                        @endif

                        {{-- Kolom JP --}}
                        <td
                            class="w-[50px] min-w-[50px] sticky left-[60px] z-[30] p-2 bg-white border-r border-b border-slate-200 text-center font-bold text-slate-600 text-xs">
                            @if($tipeTampil == 'Istirahat')
                            <span class="text-amber-500">IST</span>
                            @else
                            {{ $j }}
                            @endif
                        </td>

                        {{-- Kolom WAKTU --}}
                        <td
                            class="w-[100px] min-w-[100px] sticky left-[110px] z-[30] p-2 bg-white border-r border-b border-slate-200 text-center text-[10px] font-mono font-medium text-slate-400 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.05)]">
                            @php $w_parts = explode(' - ', $waktuTampil); @endphp
                            <div class="flex flex-col items-center justify-center space-y-0.5">
                                <span>{{ $w_parts[0] ?? '' }}</span>
                                <span class="text-[8px] text-slate-300 leading-none">sd</span>
                                <span>{{ $w_parts[1] ?? '' }}</span>
                            </div>
                        </td>

                        {{-- Kolom MATA PELAJARAN --}}
                        @if($tipeTampil == 'Istirahat')
                        <td colspan="{{ $kelass->count() }}"
                            class="p-3 border-b border-slate-200 bg-amber-50/40 align-middle">
                            <div
                                class="w-full py-2 bg-amber-100/60 border border-amber-200/60 rounded flex items-center justify-center">
                                <span class="font-extrabold text-amber-500 text-[10px] tracking-[0.3em] uppercase">☕
                                    WAKTU ISTIRAHAT</span>
                            </div>
                        </td>
                        @elseif(in_array($tipeTampil, ['Upacara', 'Senam', 'Sholat Dhuha', 'Jumat Bersih', 'Pramuka']))
                        <td colspan="{{ $kelass->count() }}"
                            class="p-3 border-b border-slate-200 bg-cyan-50/40 align-middle">
                            <div
                                class="w-full py-2 bg-cyan-100/60 border border-cyan-200/60 rounded flex items-center justify-center">
                                <span class="font-extrabold text-cyan-600 text-[10px] tracking-[0.3em] uppercase">✨
                                    {{ $tipeTampil }}</span>
                            </div>
                        </td>
                        @else
                        {{-- SEL BELAJAR BIASA --}}
                        @foreach($kelass as $kelas)
                        @php
                        $data = $jadwals[$kelas->id][$namaHari][$j] ?? null;
                        if($data && $data['tipe'] == 'empty' && empty($data['mapel'])) $data = null;
                        @endphp

                        <td class="p-2 border-r border-b border-slate-200 text-center align-middle h-[85px] min-w-[180px] w-[180px] jadwal-cell transition-all duration-300 relative bg-white"
                            data-search="{{ $data ? strtolower($data['mapel'].' '.$data['guru'].' '.$kelas->nama_kelas) : '' }}"
                            data-kelas="{{ strtolower($kelas->nama_kelas) }}">

                            @if($data)
                            <div
                                class="w-full h-full rounded-xl flex flex-col justify-center items-center px-3 py-2 {{ $data['color'] ?? 'bg-[#f5f3ff] text-[#6d28d9]' }} transition-all duration-300 relative group sel-content border border-transparent shadow-sm">
                                <span
                                    class="font-bold text-xs leading-tight mb-1 text-slate-800 break-words">{{ $data['mapel'] }}</span>
                                <span
                                    class="text-[10px] font-medium leading-tight opacity-90 text-indigo-600 truncate max-w-full">{{ $data['guru'] }}</span>
                            </div>
                            @else
                            <div
                                class="w-full h-full rounded-xl border border-dashed border-slate-200 bg-slate-50 flex flex-col items-center justify-center transition-all duration-300 sel-content opacity-60">
                                <span class="text-[10px] font-bold text-slate-300 tracking-wider">KOSONG</span>
                            </div>
                            @endif
                        </td>
                        @endforeach
                        @endif
                    </tr>
                    @endif
                    @endforeach

                    {{-- Pemisah Antar Hari --}}
                    <tr>
                        <td colspan="{{ $kelass->count() + 3 }}" class="bg-slate-200/70 h-1"></td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- COPYRIGHT TEXT --}}
    <div class="text-center mt-6 mb-4 text-slate-500 text-[11px] font-medium tracking-wide">
        &copy; 2026 SMAN 1 SAMPANG. Sistem Penjadwalan Terintegrasi.
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
/* Reset Table */
table {
    border-collapse: separate;
    border-spacing: 0;
}

/* Custom Scrollbar Agar Rapi Waktu Di Scroll Kanan */
.custom-scrollbar::-webkit-scrollbar {
    width: 10px;
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
function filterJadwalRealtime() {
    const input = document.getElementById('search-jadwal').value.toLowerCase().trim();
    const cells = document.querySelectorAll('.jadwal-cell');
    const headers = document.querySelectorAll('.jadwal-header');

    if (input === '') {
        cells.forEach(cell => {
            cell.style.opacity = '1';
            cell.style.filter = 'none';
            const innerBox = cell.querySelector('.sel-content');
            if (innerBox && innerBox.classList.contains('ring-4')) {
                innerBox.classList.remove('ring-4', 'ring-indigo-400', 'ring-offset-2', 'scale-[1.05]');
            }
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
            if (innerBox && !innerBox.classList.contains('opacity-60')) {
                innerBox.classList.add('ring-4', 'ring-indigo-400', 'ring-offset-2', 'scale-[1.05]');
            }
        } else {
            cell.style.opacity = '0.15';
            cell.style.filter = 'grayscale(100%)';
            const innerBox = cell.querySelector('.sel-content');
            if (innerBox && innerBox.classList.contains('ring-4')) {
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