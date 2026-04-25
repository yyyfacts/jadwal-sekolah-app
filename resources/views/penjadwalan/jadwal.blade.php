@extends('layouts.app')

@section('content')
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

<div class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 h-[calc(100vh-4rem)] pb-2 pt-2 flex flex-col">

    {{-- FLASH MESSAGES & METRIK --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition class="mb-4 space-y-3">

        <div
            class="flex flex-col p-3 bg-emerald-50 border border-emerald-100 rounded-lg shadow-sm text-emerald-800 shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-bold text-xs">✅ {{ session('success') }}</span>

                    @if(session('status_solver'))
                    <span
                        class="px-2 py-0.5 rounded text-[9px] font-bold uppercase border 
                        {{ session('status_solver') == 'OPTIMAL' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-yellow-100 text-yellow-700 border-yellow-200' }}">
                        {{ session('status_solver') }}
                    </span>
                    @endif

                    @if(session('waktu_komputasi'))
                    <span
                        class="px-2 py-0.5 rounded bg-emerald-200/50 text-emerald-700 text-[9px] font-bold uppercase border border-emerald-200"
                        title="Waktu proses pencarian solusi">
                        ⏱️ {{ session('waktu_komputasi') }} DTK
                    </span>
                    @endif

                    @if(session('csr') !== null)
                    <span
                        class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-[9px] font-bold uppercase border border-blue-200"
                        title="Tingkat Pemenuhan Aturan Mutlak (Hard Constraint)">
                        🎯 CSR: {{ session('csr') }}%
                    </span>
                    @endif

                    @if(session('scfr') !== null)
                    <span
                        class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 text-[9px] font-bold uppercase border border-indigo-200"
                        title="Tingkat Pemenuhan Preferensi (Soft Constraint)">
                        ⚖️ SCFR: {{ session('scfr') }}%
                    </span>
                    @endif
                </div>

                <button @click="show = false" class="text-emerald-400 hover:text-emerald-700 ml-4">&times;</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                {{-- DETAIL PERHITUNGAN CSR (HARD CONSTRAINT) --}}
                @if(session('csr') !== null)
                <div
                    class="p-3 bg-white/60 border border-blue-200/50 rounded-md text-[11px] text-blue-900 font-mono flex flex-col gap-1">
                    <strong class="text-blue-700">🛡️ Detail Perhitungan CSR (Aturan Mutlak):</strong>
                    <span>Rumus : ((Total Evaluasi - Pelanggaran) / Total Evaluasi) x 100%</span>
                    <span>Hitung : (({{ session('total_hard_constraints') }} - {{ session('jumlah_pelanggaran_hard') }})
                        / {{ session('total_hard_constraints') ?: 1 }}) x 100%</span>
                    <span>Hasil : <strong class="text-[12px]">{{ session('csr') }}%</strong></span>

                    {{-- TABEL BREAKDOWN SELALU TAMPIL SEBAGAI BUKTI --}}
                    @if(session('breakdown_csr') && count(session('breakdown_csr')) > 0)
                    <div class="mt-2 overflow-x-auto rounded border border-blue-200/60 bg-white">
                        <table class="w-full text-left border-collapse min-w-full">
                            <thead class="bg-blue-50">
                                <tr class="text-blue-800 text-[10px]">
                                    <th class="px-2 py-1 font-bold border-b border-blue-200/60">Kategori Evaluasi</th>
                                    <th class="px-2 py-1 font-bold text-center border-b border-blue-200/60 border-l">
                                        Total</th>
                                    <th class="px-2 py-1 font-bold text-center border-b border-blue-200/60 border-l">
                                        Pelanggaran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-100/50 text-[10px]">
                                @foreach(session('breakdown_csr') as $b)
                                <tr class="hover:bg-blue-50/50 transition-colors">
                                    <td class="px-2 py-1">
                                        <span class="font-bold">{{ $b['kategori'] }}</span>: <span
                                            class="text-[9px] text-blue-700/80">{{ $b['deskripsi'] }}</span>
                                    </td>
                                    <td class="px-2 py-1 text-center font-bold border-l border-blue-100/50">
                                        {{ $b['total'] }}</td>
                                    <td
                                        class="px-2 py-1 text-center font-bold border-l border-blue-100/50 {{ $b['pelanggaran'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $b['pelanggaran'] }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                @endif

                {{-- DETAIL PERHITUNGAN SCFR (SOFT CONSTRAINT) --}}
                @if(session('scfr') !== null)
                <div
                    class="p-3 bg-white/60 border border-emerald-200/50 rounded-md text-[11px] text-emerald-900 font-mono flex flex-col gap-1">
                    <strong class="text-emerald-700">💡 Detail Perhitungan SCFR (Preferensi):</strong>
                    <span>Rumus : ((Total Evaluasi - Pelanggaran) / Total Evaluasi) x 100%</span>
                    <span>Hitung : (({{ session('total_preferensi') }} - {{ session('jumlah_pelanggaran_soft') }}) /
                        {{ session('total_preferensi') ?: 1 }}) x 100%</span>
                    <span>Hasil : <strong class="text-[12px]">{{ session('scfr') }}%</strong></span>

                    {{-- TABEL BREAKDOWN SCFR --}}
                    @if(session('breakdown_scfr') && count(session('breakdown_scfr')) > 0)
                    <div class="mt-2 overflow-x-auto rounded border border-emerald-200/60 bg-white">
                        <table class="w-full text-left border-collapse min-w-full">
                            <thead class="bg-emerald-50">
                                <tr class="text-emerald-800 text-[10px]">
                                    <th class="px-2 py-1 font-bold border-b border-emerald-200/60">Kategori Evaluasi
                                    </th>
                                    <th class="px-2 py-1 font-bold text-center border-b border-emerald-200/60 border-l">
                                        Total</th>
                                    <th class="px-2 py-1 font-bold text-center border-b border-emerald-200/60 border-l">
                                        Pelanggaran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-emerald-100/50 text-[10px]">
                                @foreach(session('breakdown_scfr') as $b)
                                <tr class="hover:bg-emerald-50/50 transition-colors">
                                    <td class="px-2 py-1">
                                        <span class="font-bold">{{ $b['kategori'] }}</span>: <span
                                            class="text-[9px] text-emerald-700/80">{{ $b['deskripsi'] }}</span>
                                    </td>
                                    <td class="px-2 py-1 text-center font-bold border-l border-emerald-100/50">
                                        {{ $b['total'] }}</td>
                                    <td
                                        class="px-2 py-1 text-center font-bold border-l border-emerald-100/50 {{ $b['pelanggaran'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                                        {{ $b['pelanggaran'] }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- UI DETAIL PELANGGARAN HARD CONSTRAINT --}}
        @if(session('jumlah_pelanggaran_hard') > 0)
        <div x-data="{ bukaDetailHard: true }"
            class="bg-rose-50 border border-rose-200 rounded-lg shadow-sm text-sm overflow-hidden transition-all duration-300 mb-3">
            <button @click="bukaDetailHard = !bukaDetailHard"
                class="w-full flex items-center justify-between p-3 text-rose-700 font-bold hover:bg-rose-100 transition-colors">
                <div class="flex items-center gap-2">
                    <span>❌ Terdapat {{ session('jumlah_pelanggaran_hard') }} Pelanggaran Aturan Mutlak (Hard
                        Constraint)</span>
                </div>
                <svg :class="{'rotate-180': bukaDetailHard}" class="w-4 h-4 transition-transform" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="bukaDetailHard" class="px-5 pb-4 pt-1">
                <ul class="list-disc list-inside text-rose-600 text-xs space-y-1">
                    @foreach(session('detail_pelanggaran_hard') as $ph)
                    <li>{{ $ph }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- UI DETAIL PELANGGARAN SOFT CONSTRAINT --}}
        @if(session('jumlah_pelanggaran_soft') > 0)
        <div x-data="{ bukaDetail: false }"
            class="bg-indigo-50/50 border border-indigo-100 rounded-lg shadow-sm text-sm overflow-hidden transition-all duration-300">
            <button @click="bukaDetail = !bukaDetail"
                class="w-full flex items-center justify-between p-3 text-indigo-700 font-medium hover:bg-indigo-50 transition-colors">
                <div class="flex items-center gap-2">
                    <span>⚠️ Terdapat {{ session('jumlah_pelanggaran_soft') }} Penyesuaian Jadwal (Soft
                        Constraint)</span>
                </div>
                <svg :class="{'rotate-180': bukaDetail}" class="w-4 h-4 transition-transform" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="bukaDetail" style="display: none;" class="px-5 pb-4 pt-1">
                <ul class="list-disc list-inside text-indigo-600/80 text-xs space-y-1">
                    @foreach(session('detail_pelanggaran_soft') as $p)
                    <li>{{ $p }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>
    @endif

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-rose-50 border border-rose-100 rounded-lg shadow-sm text-rose-800 shrink-0">
        <span class="font-bold text-xs">❌ {{ session('error') }}</span>
        <button @click="show = false" class="text-rose-400 hover:text-rose-700">&times;</button>
    </div>
    @endif

    {{-- MAIN CARD UI --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-md flex flex-col flex-1 overflow-hidden min-h-0">
        {{-- HEADER SECTION --}}
        <div class="px-6 py-4 bg-white shrink-0 z-20 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-10 bg-indigo-600 rounded-full"></div>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-800 leading-none">Jadwal Pelajaran Terpadu</h1>
                        <p class="text-slate-500 text-xs mt-1 font-medium">T.A
                            {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-end">
                    <div class="relative group w-full md:w-48">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="search-jadwal" oninput="filterJadwalRealtime()"
                            class="block w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition"
                            placeholder="Cari Mapel...">
                    </div>

                    <a href="{{ route('jadwal.export') }}"
                        class="flex items-center gap-2 px-4 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 font-bold text-xs uppercase rounded-xl shadow-sm transition-all">
                        Export Excel
                    </a>

                    <form action="{{ route('jadwal.generate') }}" method="POST" onsubmit="showLoading()"
                        class="m-0 p-0">
                        @csrf
                        <button type="button"
                            onclick="if(confirm('Peringatan: Lanjut generate jadwal AI?')) this.form.submit()"
                            class="flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-indigo-600 text-white font-bold text-xs uppercase rounded-xl shadow-md transition-all">
                            Generate AI
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- TABLE SECTION --}}
        <div class="w-full flex-1 overflow-auto custom-scrollbar relative bg-slate-50/50 z-10 flex flex-col">
            @if($kelass->isEmpty() || empty($jadwals))
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <h3 class="text-sm font-bold text-slate-600">Data Jadwal Kosong</h3>
            </div>
            @else
            <div class="flex-1">
                <table class="w-full min-w-max border-separate border-spacing-0 text-left" id="jadwal-tabel-main">
                    <thead>
                        <tr class="text-slate-600 bg-white shadow-sm">
                            <th
                                class="h-[40px] w-[40px] min-w-[40px] sticky top-0 left-0 z-[60] bg-white border-r border-b border-slate-200 text-center font-extrabold text-[10px] uppercase tracking-widest">
                                HARI</th>
                            <th
                                class="h-[40px] w-[35px] min-w-[35px] sticky top-0 left-[40px] z-[60] bg-white border-r border-b border-slate-200 text-center font-extrabold text-[10px] uppercase tracking-widest">
                                JP</th>
                            <th
                                class="h-[40px] w-[90px] min-w-[90px] sticky top-0 left-[75px] z-[60] bg-white border-r border-b border-slate-200 text-center font-extrabold text-[10px] uppercase tracking-widest">
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
                        $waktuAktif = $hariItem->waktuHaris->filter(fn($w) => $w->tipe !== 'Tidak
                        Ada')->sortBy('waktu_mulai');
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
                                class="w-[40px] min-w-[40px] sticky left-0 z-[30] p-0 bg-white border-r border-b border-slate-200 align-middle text-center">
                                <div class="font-extrabold text-slate-700 uppercase tracking-widest text-[12px] h-full flex items-center justify-center py-2"
                                    style="writing-mode: vertical-lr; transform: rotate(180deg);">{{ $namaHari }}</div>
                            </td>
                            @php $firstRow = false; @endphp
                            @endif

                            <td
                                class="h-[45px] w-[35px] min-w-[35px] sticky left-[40px] z-[30] p-1 bg-white border-r border-b border-slate-200 text-center font-bold text-slate-700 text-[11px]">
                                @if(!in_array($tipeTampil, ['Istirahat', 'Upacara', 'Senam', 'Sholat', 'Sholat Dhuha',
                                'Jumat Bersih', 'Pramuka'])) {{ $j }} @endif
                            </td>
                            <td
                                class="h-[45px] w-[90px] min-w-[90px] sticky left-[75px] z-[30] p-1 bg-white border-r border-b border-slate-200 text-center text-[10px] font-mono font-medium text-slate-700">
                                {{ $waktuTampil }}</td>

                            @if(in_array($tipeTampil, ['Istirahat', 'Upacara', 'Senam', 'Sholat', 'Sholat Dhuha', 'Jumat
                            Bersih', 'Pramuka']))
                            <td colspan="{{ $kelass->count() }}"
                                class="h-[45px] p-1 border-b border-slate-200 bg-slate-50 align-middle">
                                <div class="w-full h-full rounded flex items-center justify-center"><span
                                        class="font-bold text-slate-500 text-[11px] tracking-[0.2em] uppercase italic">{{ $tipeTampil }}</span>
                                </div>
                            </td>
                            @else
                            @foreach($kelass as $kelas)
                            @php $data = $jadwals[$kelas->id][$namaHari][$j] ?? null; @endphp
                            <td class="h-[45px] p-1 border-r border-b border-slate-200 text-center align-middle min-w-[140px] max-w-[140px] w-[140px] bg-white transition-all jadwal-cell"
                                data-search="{{ $data ? strtolower($data['mapel'].' '.$data['guru'].' '.$kelas->nama_kelas) : '' }}"
                                data-kelas="{{ strtolower($kelas->nama_kelas) }}">
                                @if($data)
                                <div
                                    class="w-full h-full flex flex-col justify-center items-center px-1 {{ $data['color'] }} rounded-md border border-slate-100">
                                    <span
                                        class="font-bold text-[11px] leading-tight text-slate-800 break-words line-clamp-1">{{ $data['mapel'] }}</span>
                                    <span
                                        class="text-[9px] font-medium mt-0.5 text-slate-500 truncate max-w-full">{{ $data['kode_guru'] }}</span>
                                </div>
                                @else
                                <div class="w-full h-full flex items-center justify-center opacity-30"><span
                                        class="text-[9px] font-bold text-slate-300">-</span></div>
                                @endif
                            </td>
                            @endforeach
                            @endif
                        </tr>
                        @endforeach
                        <tr>
                            <td colspan="{{ $kelass->count() + 3 }}" class="bg-slate-800 h-[2px] border-none p-0"></td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

<div id="loading-overlay"
    class="hidden fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/70 backdrop-blur-sm transition-opacity">
    <div class="bg-white p-8 rounded-3xl shadow-2xl text-center">
        <h3 class="text-xl font-extrabold text-slate-800 mb-2">AI Sedang Menyusun...</h3>
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
    if (input === '') {
        cells.forEach(c => {
            c.style.opacity = '1';
            c.style.filter = 'none';
        });
        return;
    }
    cells.forEach(c => {
        if (c.getAttribute('data-search') && c.getAttribute('data-search').includes(input)) {
            c.style.opacity = '1';
            c.style.filter = 'none';
        } else {
            c.style.opacity = '0.15';
            c.style.filter = 'grayscale(100%)';
        }
    });
}

function showLoading() {
    document.getElementById('loading-overlay').classList.remove('hidden');
}
</script>
@endpush