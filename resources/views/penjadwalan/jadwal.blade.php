@extends('layouts.app')

@section('content')
{{-- BACKGROUND AMBIENT --}}
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]">
    {{-- Elemen dekorasi background bisa ditambahkan di sini jika perlu, tapi mengikuti referensi, backgroundnya sangat clean --}}
</div>

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

    {{-- MAIN CARD UI (Sesuai Referensi) --}}
    <div
        class="bg-white rounded-[2rem] shadow-[0_10px_40px_-10px_rgba(0,0,0,0.05)] border border-slate-100 flex flex-col flex-1 overflow-hidden">

        {{-- 1. HEADER SECTION (Gaya Baru: Clean & Memanjang) --}}
        <div class="p-8 border-b border-slate-100 shrink-0 bg-white">

            <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-6 mb-6">

                {{-- Judul (Kiri) --}}
                <div class="flex gap-4 items-start">
                    <div class="w-2.5 h-10 bg-blue-600 rounded-full mt-1"></div>
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                            Jadwal Pelajaran Terpadu
                        </h1>
                        <p class="text-slate-500 text-sm mt-1.5 font-medium">
                            Tahun Ajaran {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }} Semester Genap
                        </p>
                    </div>
                </div>

                {{-- Action Buttons (Kanan Atas) --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('jadwal.export') }}"
                        class="flex items-center justify-center gap-2 px-6 py-2.5 bg-white border-2 border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50 font-bold text-xs uppercase tracking-widest rounded-xl transition-all shadow-sm">
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
                            class="flex items-center justify-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl transition-all shadow-md shadow-blue-500/30 hover:-translate-y-0.5">
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

            {{-- Filter Bar (Sesuai Referensi, Memanjang di bawah judul) --}}
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="search-jadwal" oninput="filterJadwalRealtime()"
                    class="block w-full pl-12 pr-4 py-4 bg-white border border-slate-200 rounded-2xl text-[13px] font-medium text-slate-700 placeholder-slate-400 focus:outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300"
                    placeholder="Cari Nama Guru, Mata Pelajaran, atau Kelas... (Merespon Langsung)">
            </div>
        </div>

        {{-- 2. TABLE SECTION (Header Gelap & Cell Berwarna) --}}
        <div class="flex-1 overflow-auto custom-scrollbar relative bg-white">
            @if($kelass->isEmpty())
            <div class="flex flex-col items-center justify-center h-full py-20 text-center">
                <div class="text-6xl mb-4 opacity-30">🗂️</div>
                <h3 class="text-lg font-bold text-slate-600">Data Tidak Ditemukan</h3>
                <p class="text-slate-400 text-sm mt-1">Sistem belum memiliki jadwal atau data kelas.</p>
            </div>
            @else
            <table class="w-full border-collapse min-w-[1200px]" id="jadwal-tabel">
                <thead class="sticky top-0 z-[40]">
                    {{-- Row 1: Header Hitam/Biru Gelap (Sesuai Gambar) --}}
                    <tr class="bg-[#242b3d] text-white">
                        <th colspan="3" class="h-12 border-r border-slate-600/50 bg-[#1e2434]"></th>
                        @foreach($kelass as $kelas)
                        <th class="h-12 px-4 border-r border-slate-600/50 text-center font-extrabold text-sm tracking-wider jadwal-header"
                            data-kelas="{{ strtolower($kelas->nama_kelas) }}">
                            {{ $kelas->nama_kelas }}
                        </th>
                        @endforeach
                    </tr>

                    {{-- Row 2: Sub-Header Putih, Teks Abu (HARI, JP, WAKTU, KELAS) --}}
                    <tr class="bg-white text-slate-400 border-b border-slate-200">
                        <th
                            class="h-10 w-16 border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest">
                            HARI</th>
                        <th
                            class="h-10 w-12 border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest">
                            JP</th>
                        <th
                            class="h-10 w-24 border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest">
                            WAKTU</th>
                        @foreach($kelass as $kelas)
                        <th
                            class="h-10 min-w-[160px] border-r border-b border-slate-200 text-center font-bold text-[10px] uppercase tracking-widest">
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
                        class="hover:bg-slate-50/50 transition-colors duration-150">

                        {{-- Kolom Hari (Sticky) --}}
                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpanTotal }}"
                            class="sticky left-0 z-[20] p-0 bg-white border-r border-b border-slate-200 align-middle text-center">
                            <div
                                class="font-extrabold text-slate-400 uppercase tracking-[0.2em] text-[12px] h-full flex items-center justify-center py-4 px-2 vertical-text">
                                {{ $hari }}
                            </div>
                        </td>
                        @endif

                        {{-- Kolom JP & Waktu --}}
                        <td
                            class="sticky left-16 z-[10] p-2 bg-white border-r border-b border-slate-100 text-center font-bold text-slate-600 text-[11px]">
                            {{ $jam }}
                        </td>
                        <td
                            class="sticky left-[7rem] z-[10] p-2 bg-white border-r border-b border-slate-100 text-center text-[10px] font-mono font-medium text-slate-400">
                            @php
                            $w = ($hari == 'Senin') ? $waktu['Senin'][$jam] : (($hari == 'Jumat') ?
                            $waktu['Jumat'][$jam] : $waktu['Default'][$jam]);
                            $w_parts = explode('-', $w);
                            @endphp
                            @if(count($w_parts) == 2)
                            <div class="flex flex-col">
                                <span>{{ $w_parts[0] }} -</span>
                                <span>{{ $w_parts[1] }}</span>
                            </div>
                            @else
                            {{ $w }}
                            @endif
                        </td>

                        {{-- Kolom Kelas / Sel Jadwal (Isi Data) --}}
                        @if($jam == 0)
                        <td colspan="{{ $kelass->count() }}"
                            class="p-1 border-b border-slate-100 align-middle bg-white">
                            <div
                                class="w-full h-full bg-blue-50/50 border border-blue-100 rounded flex items-center justify-center p-2">
                                <span class="text-xs font-bold text-blue-700 uppercase tracking-widest">
                                    @if($hari == 'Senin') 🇮🇩 UPACARA BENDERA @else 📖 IMTAQ / SENAM @endif
                                </span>
                            </div>
                        </td>
                        @else
                        @foreach($kelass as $kelas)
                        @php $data = $jadwals[$kelas->id][$hari][$jam] ?? null; @endphp

                        {{-- ATRIBUT DATA-SEARCH DISINI SEBAGAI KUNCI FILTER --}}
                        <td class="p-2 border-r border-b border-slate-100 text-center align-middle h-[72px] jadwal-cell transition-all duration-300"
                            data-search="{{ $data ? strtolower($data['mapel'].' '.$data['guru'].' '.$kelas->nama_kelas) : '' }}"
                            data-kelas="{{ strtolower($kelas->nama_kelas) }}">

                            @if($data)
                            {{-- Menggunakan warna latar dari $data['color'] jika ada, jika tidak default purple ringan --}}
                            <div
                                class="w-full h-full rounded-lg flex flex-col justify-center items-center px-1 py-1.5 {{ $data['color'] ?? 'bg-[#f5f3ff]' }} sel-content transition-all duration-300">
                                <span
                                    class="font-bold text-[12px] text-slate-800 leading-tight mb-1 line-clamp-1">{{ $data['mapel'] }}</span>
                                <span
                                    class="text-[10px] text-blue-600 font-medium leading-tight line-clamp-1">{{ $data['guru'] }}</span>
                            </div>
                            @else
                            <div
                                class="w-full h-full rounded-lg bg-transparent flex items-center justify-center sel-content transition-all duration-300 opacity-30">
                                <span class="text-slate-300 text-[10px] font-bold">-</span>
                            </div>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        {{-- Row Istirahat --}}
                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr>
                            <td
                                class="sticky left-16 z-[10] p-1 border-r border-b border-slate-100 bg-slate-50 text-center font-bold text-slate-400 text-[10px]">
                                IST
                            </td>
                            <td
                                class="sticky left-[7rem] z-[10] p-1 border-r border-b border-slate-100 bg-slate-50 text-center text-[10px] font-mono font-medium text-slate-400">
                                @php
                                $w_ist = $jam==4 ? '10.00-10.15' : '13.30-13.50'; // Contoh format
                                $w_ist_parts = explode('-', $w_ist);
                                @endphp
                                <div class="flex flex-col">
                                    <span>{{ $w_ist_parts[0] }} -</span>
                                    <span>{{ $w_ist_parts[1] ?? '' }}</span>
                                </div>
                            </td>
                            <td colspan="{{ $kelass->count() }}"
                                class="p-1 border-b border-slate-100 bg-slate-50 align-middle">
                                <div
                                    class="w-full h-full bg-slate-100/50 rounded flex items-center justify-center p-1.5">
                                    <span class="font-bold text-slate-400 text-[10px] tracking-[0.3em] uppercase">☕
                                        Istirahat</span>
                                </div>
                            </td>
                        </tr>
                        @endif
                        @endfor

                        {{-- Pemisah Antar Hari (Garis Tebal) --}}
                        <tr>
                            <td colspan="{{ $kelass->count() + 3 }}" class="bg-slate-100 h-2 border-y border-slate-200">
                            </td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- 3. FOOTER SECTION --}}
        <div class="bg-white border-t border-slate-100 px-8 py-4 flex justify-between items-center shrink-0">
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
            <div class="absolute inset-0 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
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
/* Tabel Setup */
table {
    border-collapse: separate;
    border-spacing: 0;
}

.vertical-text {
    writing-mode: vertical-lr;
    transform: rotate(180deg);
    white-space: nowrap;
}

/* Custom Scrollbar Clean */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
    border: 2px solid #fff;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Sel styling */
.sel-content {
    /* Opsional: Efek border radius khusus atau bayangan halus */
}
</style>
@endpush

@push('scripts')
<script>
// ==========================================================
// MAGIC LIVE SEARCH JADWAL MATRIKS (Sangat Cepat & Smooth)
// ==========================================================
function filterJadwalRealtime() {
    const input = document.getElementById('search-jadwal').value.toLowerCase().trim();
    const cells = document.querySelectorAll('.jadwal-cell');
    const headers = document.querySelectorAll('.jadwal-header');

    // KONDISI 1: JIKA KOTAK PENCARIAN KOSONG
    if (input === '') {
        cells.forEach(cell => {
            cell.style.opacity = '1';
            cell.style.filter = 'none';
            const innerBox = cell.querySelector('.sel-content');
            if (innerBox) {
                innerBox.classList.remove('ring-2', 'ring-blue-400', 'ring-offset-2', 'scale-105', 'shadow-md');
            }
        });
        headers.forEach(header => {
            header.style.opacity = '1';
        });
        return;
    }

    // KONDISI 2: MENCARI
    let classMatched = false;

    cells.forEach(cell => {
        const searchData = cell.getAttribute('data-search');
        const dataKelas = cell.getAttribute('data-kelas');

        // Cek jika input adalah persis nama kelas
        if (dataKelas === input) {
            classMatched = true;
        }

        // Cek isi data
        if (searchData && searchData.includes(input)) {
            cell.style.opacity = '1';
            cell.style.filter = 'none';

            // Highlight sel yang cocok
            const innerBox = cell.querySelector('.sel-content');
            if (innerBox && !innerBox.classList.contains('opacity-30')) {
                innerBox.classList.add('ring-2', 'ring-blue-400', 'ring-offset-2', 'scale-105', 'shadow-md');
            }
        } else {
            // Sel tidak cocok jadi buram/grayscale
            cell.style.opacity = '0.2';
            cell.style.filter = 'grayscale(100%)';
            const innerBox = cell.querySelector('.sel-content');
            if (innerBox) {
                innerBox.classList.remove('ring-2', 'ring-blue-400', 'ring-offset-2', 'scale-105', 'shadow-md');
            }
        }
    });

    // Sesuaikan Header Kelas
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