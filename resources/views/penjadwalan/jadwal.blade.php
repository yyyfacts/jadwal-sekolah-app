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

    {{-- Notifikasi Sukses --}}
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

            {{-- Info Teknis --}}
            <div class="mt-3 bg-white/60 rounded p-3 border border-emerald-100 text-xs text-emerald-800">
                <strong>Catatan Sistem:</strong><br>
                Beberapa mapel dengan durasi panjang (3+ jam) mungkin dipecah otomatis oleh AI untuk menghindari bentrok
                dan menyesuaikan kapasitas jam harian siswa.
            </div>
        </div>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700 font-bold text-lg">&times;</button>
    </div>
    @endif

    {{-- Notifikasi Error --}}
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show"
        class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 shadow-sm flex items-start gap-3">
        <div class="bg-red-100 p-2 rounded-full text-red-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                </path>
            </svg>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-red-900">Gagal Generate Jadwal</h3>
            <p class="text-red-700 text-sm mt-1 whitespace-pre-line">{{ session('error') }}</p>
        </div>
        <button @click="show = false" class="text-red-400 hover:text-red-700 font-bold text-lg">&times;</button>
    </div>
    @endif

    {{-- Tabel Jadwal --}}
    <div class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden mb-10">
        {{-- Set max-height agar bisa discroll jika perlu, tapi sticky header akan tetap jalan --}}
        <div class="overflow-x-auto custom-scrollbar relative max-h-[80vh]">
            <table class="w-full text-xs border-collapse min-w-[1000px]">
                <thead>
                    {{-- Judul Tabel --}}
                    <tr>
                        <th colspan="{{ 3 + $kelass->count() }}"
                            class="p-4 bg-slate-800 text-white font-bold text-center text-base uppercase tracking-wider">
                            Jadwal Pelajaran Tahun Ajaran {{ date('Y') }}/{{ date('Y')+1 }}
                        </th>
                    </tr>
                    {{-- Header Kolom (STICKY) --}}
                    {{-- Perubahan: Ditambahkan class sticky top-0 z-50 shadow-md dan bg solid --}}
                    <tr
                        class="bg-slate-200 text-slate-800 font-bold text-center uppercase border-b-2 border-slate-300 sticky top-0 z-50 shadow-md">
                        <th class="p-3 border-r border-slate-300 w-12 sticky left-0 z-50 bg-slate-200">Hari</th>
                        <th class="p-3 border-r border-slate-300 w-10">Jam</th>
                        <th class="p-3 border-r border-slate-300 w-24">Waktu</th>
                        @foreach($kelass as $kelas)
                        <th class="p-3 border-r border-slate-300 min-w-[120px]">{{ $kelas->nama_kelas }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @php
                    $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

                    // Definisi Waktu (Hardcoded untuk contoh, idealnya dari DB)
                    $waktu = [
                    'Senin' => [0=>'07.00-07.45', 1=>'07.45-08.25', 2=>'08.25-09.05', 3=>'09.05-09.45',
                    4=>'10.00-10.40', 5=>'10.40-11.20', 6=>'11.20-12.00', 7=>'12.50-13.30',
                    8=>'13.30-14.10', 9=>'14.10-14.50', 10=>'14.50-15.30'],
                    'Jumat' => [0=>'07.00-07.45', 1=>'07.45-08.20', 2=>'08.20-08.55', 3=>'08.55-09.30',
                    4=>'09.30-10.00', 5=>'10.15-10.45', 6=>'10.45-11.15', 7=>'11.15-11.45',
                    8=>'12.45-13.15', 9=>'13.15-13.45', 10=>'13.45-14.15'],
                    'Default' => [0=>'07.00-07.40', 1=>'07.40-08.20', 2=>'08.20-09.00', 3=>'09.00-09.40',
                    4=>'09.50-10.30', 5=>'10.30-11.10', 6=>'11.10-11.50', 7=>'12.35-13.15',
                    8=>'13.15-13.55', 9=>'13.55-14.35', 10=>'14.35-15.15']
                    ];
                    @endphp

                    @foreach($hariList as $hari)
                    @php
                    // Config per Hari
                    $maxJam = 10;
                    $startJam = ($hari == 'Senin' || $hari == 'Jumat') ? 0 : 1;

                    $baseRows = ($maxJam - $startJam) + 1;
                    $istirahatRows = ($hari != 'Jumat') ? 2 : 0;
                    $rowSpan = $baseRows + $istirahatRows;
                    @endphp

                    @for($jam = $startJam; $jam <= $maxJam; $jam++) <tr class="hover:bg-slate-50 transition">

                        {{-- Kolom Hari (Sticky & Merged) --}}
                        @if($jam == $startJam)
                        <td rowspan="{{ $rowSpan }}"
                            class="p-3 border-r border-b border-slate-300 bg-slate-50 font-bold text-center align-middle uppercase text-slate-700 sticky left-0 z-40 shadow-sm writing-mode-vertical md:writing-mode-horizontal">
                            <div class="transform md:-rotate-90 whitespace-nowrap">{{ $hari }}</div>
                        </td>
                        @endif

                        {{-- Kolom Jam Ke- --}}
                        <td class="p-2 border-r border-slate-200 text-center font-bold text-slate-500 bg-slate-50/50">
                            {{ $jam }}
                        </td>

                        {{-- Kolom Waktu --}}
                        <td class="p-2 border-r border-slate-200 text-center text-[10px] font-mono text-slate-600">
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
                            class="p-2 border-r border-slate-200 bg-gray-100 text-center text-xs font-bold text-gray-500 tracking-widest uppercase shadow-inner">
                            @if($hari == 'Senin') 🇮🇩 UPACARA BENDERA
                            @elseif($hari == 'Jumat') 🏃 SENAM / IMTAQ / JALAN SEHAT
                            @else 📖 LITERASI PAGI @endif
                        </td>
                        @else
                        {{-- Loop Kelas --}}
                        @foreach($kelass as $kelas)
                        @php
                        $data = $jadwals[$kelas->id][$hari][$jam] ?? null;
                        @endphp

                        <td
                            class="p-1 border-r border-slate-200 text-center align-middle h-14 {{ $data ? $data['color'] : '' }} hover:brightness-95 transition relative group">
                            @if($data)
                            <div class="flex flex-col justify-center h-full w-full">
                                <span class="font-bold text-slate-800 text-[11px] leading-tight line-clamp-2">
                                    {{ $data['mapel'] }}
                                </span>
                                <span class="text-[10px] text-slate-600 leading-tight mt-0.5 line-clamp-1">
                                    {{ $data['guru'] }}
                                </span>
                                {{-- Tooltip Kode (Hover) --}}
                                <div
                                    class="absolute inset-0 bg-slate-900/90 text-white text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10 p-2 rounded">
                                    {{ $data['kode_mapel'] }} - {{ $data['kode_guru'] }}
                                </div>
                            </div>
                            @endif
                        </td>
                        @endforeach
                        @endif
                        </tr>

                        {{-- Baris Istirahat (Setelah Jam 4 dan 8, Kecuali Jumat) --}}
                        {{-- Perubahan: Warna background lebih solid, teks waktu hitam, teks istirahat lebih gelap --}}
                        @if(($jam == 4 || $jam == 8) && $hari != 'Jumat')
                        <tr class="bg-orange-100 border-y border-orange-300">
                            <td class="p-1 border-r border-orange-300 text-center font-black text-orange-900 text-xs">
                                IST
                            </td>
                            <td
                                class="p-1 border-r border-orange-300 text-center text-xs font-black text-slate-900 font-mono">
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
                        <tr>
                            <td colspan="{{ $kelass->count() + 3 }}" class="bg-slate-300 h-1"></td>
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
/* Utility Class untuk animasi */
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