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
                        class="px-2 py-0.5 rounded text-[9px] font-bold uppercase border {{ session('status_solver') == 'OPTIMAL' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-yellow-100 text-yellow-700 border-yellow-200' }}">
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
                @if(session('csr') !== null)
                <div
                    class="p-3 bg-white/60 border border-blue-200/50 rounded-md text-[11px] text-blue-900 font-mono flex flex-col gap-1">
                    <strong class="text-blue-700">🛡️ Detail Perhitungan CSR (Aturan Mutlak):</strong>
                    <span>Rumus : ((Total Evaluasi - Pelanggaran) / Total Evaluasi) x 100%</span>
                    <span>Hitung : (({{ session('total_hard_constraints') }} - {{ session('jumlah_pelanggaran_hard') }})
                        / {{ session('total_hard_constraints') ?: 1 }}) x 100%</span>
                    <span>Hasil : <strong class="text-[12px]">{{ session('csr') }}%</strong></span>
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
                                    <td class="px-2 py-1"><span class="font-bold">{{ $b['kategori'] }}</span>: <span
                                            class="text-[9px] text-blue-700/80">{{ $b['deskripsi'] }}</span></td>
                                    <td class="px-2 py-1 text-center font-bold border-l border-blue-100/50">
                                        {{ $b['total'] }}</td>
                                    <td
                                        class="px-2 py-1 text-center font-bold border-l border-blue-100/50 {{ $b['pelanggaran'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $b['pelanggaran'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                @endif

                @if(session('scfr') !== null)
                <div
                    class="p-3 bg-white/60 border border-emerald-200/50 rounded-md text-[11px] text-emerald-900 font-mono flex flex-col gap-1">
                    <strong class="text-emerald-700">💡 Detail Perhitungan SCFR (Preferensi):</strong>
                    <span>Rumus : ((Total Evaluasi - Pelanggaran) / Total Evaluasi) x 100%</span>
                    <span>Hitung : (({{ session('total_preferensi') }} - {{ session('jumlah_pelanggaran_soft') }}) /
                        {{ session('total_preferensi') ?: 1 }}) x 100%</span>
                    <span>Hasil : <strong class="text-[12px]">{{ session('scfr') }}%</strong></span>
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
                                    <td class="px-2 py-1"><span class="font-bold">{{ $b['kategori'] }}</span>: <span
                                            class="text-[9px] text-emerald-700/80">{{ $b['deskripsi'] }}</span></td>
                                    <td class="px-2 py-1 text-center font-bold border-l border-emerald-100/50">
                                        {{ $b['total'] }}</td>
                                    <td
                                        class="px-2 py-1 text-center font-bold border-l border-emerald-100/50 {{ $b['pelanggaran'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                                        {{ $b['pelanggaran'] }}</td>
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

        @if(session('jumlah_pelanggaran_hard') > 0)
        <div x-data="{ bukaDetailHard: true }"
            class="bg-rose-50 border border-rose-200 rounded-lg shadow-sm text-sm overflow-hidden transition-all duration-300 mb-3">
            <button @click="bukaDetailHard = !bukaDetailHard"
                class="w-full flex items-center justify-between p-3 text-rose-700 font-bold hover:bg-rose-100 transition-colors">
                <div class="flex items-center gap-2"><span>❌ Terdapat {{ session('jumlah_pelanggaran_hard') }}
                        Pelanggaran Aturan Mutlak (Hard Constraint)</span></div>
                <svg :class="{'rotate-180': bukaDetailHard}" class="w-4 h-4 transition-transform" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="bukaDetailHard" class="px-5 pb-4 pt-1">
                <ul class="list-disc list-inside text-rose-600 text-xs space-y-1">
                    @foreach(session('detail_pelanggaran_hard') as $ph) <li>{{ $ph }}</li> @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if(session('jumlah_pelanggaran_soft') > 0)
        <div x-data="{ bukaDetail: false }"
            class="bg-indigo-50/50 border border-indigo-100 rounded-lg shadow-sm text-sm overflow-hidden transition-all duration-300">
            <button @click="bukaDetail = !bukaDetail"
                class="w-full flex items-center justify-between p-3 text-indigo-700 font-medium hover:bg-indigo-50 transition-colors">
                <div class="flex items-center gap-2"><span>⚠️ Terdapat {{ session('jumlah_pelanggaran_soft') }}
                        Penyesuaian Jadwal (Soft Constraint)</span></div>
                <svg :class="{'rotate-180': bukaDetail}" class="w-4 h-4 transition-transform" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="bukaDetail" style="display: none;" class="px-5 pb-4 pt-1">
                <ul class="list-disc list-inside text-indigo-600/80 text-xs space-y-1">
                    @foreach(session('detail_pelanggaran_soft') as $p) <li>{{ $p }}</li> @endforeach
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

                    {{-- TOMBOL CEK KETERSEDIAAN GURU --}}
                    <button type="button" onclick="document.getElementById('modal-cek-guru').classList.remove('hidden')"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 font-bold text-xs uppercase rounded-xl shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                        Cek Guru
                    </button>

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
                        $wMulai = \Carbon\Carbon::parse($waktuItem->waktu_mulai)->format('H:i');
                        $wSelesai = \Carbon\Carbon::parse($waktuItem->waktu_selesai)->format('H:i');
                        $waktuTampil = \Carbon\Carbon::parse($waktuItem->waktu_mulai)->format('H.i') . ' - ' .
                        \Carbon\Carbon::parse($waktuItem->waktu_selesai)->format('H.i');
                        $tipeTampil = $waktuItem->tipe;
                        @endphp

                        {{-- MENAMBAHKAN ATRIBUT DATA PADA TR UNTUK KEBUTUHAN FILTER WAKTU --}}
                        <tr class="hover:bg-slate-50/80 transition-colors jadwal-row"
                            data-hari="{{ strtolower($namaHari) }}" data-mulai="{{ $wMulai }}"
                            data-selesai="{{ $wSelesai }}">

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
                                {{ $waktuTampil }}
                            </td>

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
                            @php
                            $data = $jadwals[$kelas->id][$namaHari][$j] ?? null;
                            $kodeGuruStr = $data ? $data['kode_guru'] : '';
                            $namaGuruStr = $data ? ($data['guru'] ?? $data['kode_guru']) : '';
                            @endphp

                            {{-- MENAMBAHKAN ATRIBUT DATA GURU PADA TD --}}
                            <td class="h-[45px] p-1 border-r border-b border-slate-200 text-center align-middle min-w-[140px] max-w-[140px] w-[140px] bg-white transition-all jadwal-cell"
                                data-search="{{ $data ? strtolower($data['mapel'].' '.$namaGuruStr.' '.$kelas->nama_kelas) : '' }}"
                                data-kelas="{{ strtolower($kelas->nama_kelas) }}" data-guru="{{ $kodeGuruStr }}"
                                data-namaguru="{{ $namaGuruStr }}">

                                @if($data)
                                <div
                                    class="w-full h-full flex flex-col justify-center items-center px-1 {{ $data['color'] }} rounded-md border border-slate-100">
                                    <span
                                        class="font-bold text-[11px] leading-tight text-slate-800 break-words line-clamp-1">{{ $data['mapel'] }}</span>
                                    <span
                                        class="text-[9px] font-medium mt-0.5 text-slate-500 truncate max-w-full">{{ $kodeGuruStr }}</span>
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

{{-- MODAL CEK GURU MENGAJAR (DISAMAKAN DENGAN STYLE GURU/MAPEL) --}}
<div id="modal-cek-guru"
    class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-2 sm:p-4 transition-opacity">
    <div
        class="bg-white rounded-xl shadow-2xl w-full max-w-4xl h-auto max-h-[90vh] flex flex-col border border-slate-200 overflow-hidden">

        {{-- Header Modal --}}
        <div class="px-4 py-3 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
            <div class="flex items-center gap-2">
                <div class="p-1.5 bg-blue-600 text-white rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-slate-800 leading-none">Cek Ketersediaan Guru</h3>
                    <p class="text-[9px] text-slate-500 mt-0.5">Pengecekan guru berhalangan/dinas berdasarkan jadwal</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-cek-guru').classList.add('hidden')"
                class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
        </div>

        {{-- Body Modal --}}
        <div class="flex flex-col flex-1 min-h-0 p-4 bg-white">

            {{-- Form Pencarian --}}
            <div
                class="grid grid-cols-1 sm:grid-cols-4 gap-2 items-end bg-slate-50 p-3 rounded-lg border border-slate-200 shrink-0">
                <div class="sm:col-span-1">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Hari</label>
                    <select id="cg-hari"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white transition-shadow">
                        <option value="senin">Senin</option>
                        <option value="selasa">Selasa</option>
                        <option value="rabu">Rabu</option>
                        <option value="kamis">Kamis</option>
                        <option value="jumat">Jumat</option>
                        <option value="sabtu">Sabtu</option>
                    </select>
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Jam Mulai</label>
                    <input type="time" id="cg-mulai" value="09:00"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white transition-shadow">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Jam Selesai</label>
                    <input type="time" id="cg-selesai" value="11:00"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none bg-white transition-shadow">
                </div>
                <div class="sm:col-span-1">
                    <button type="button" onclick="prosesCekGuru()"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg text-xs uppercase tracking-wide transition-colors shadow-sm h-[34px] flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Cari
                    </button>
                </div>
            </div>

            {{-- Area Hasil --}}
            <div id="cg-hasil"
                class="mt-4 hidden flex-col flex-1 min-h-0 border border-slate-100 rounded-lg overflow-hidden bg-slate-50/50">
                <div class="px-3 py-2 border-b border-slate-100 bg-slate-50 flex items-center justify-between shrink-0">
                    <h4 class="text-[10px] font-bold text-slate-600 uppercase tracking-wide flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Guru Sedang Mengajar
                    </h4>
                    <span id="cg-count"
                        class="text-[9px] font-bold text-blue-600 bg-blue-100 px-1.5 py-0.5 rounded border border-blue-200 hidden">0</span>
                </div>

                {{-- List Guru --}}
                <div id="cg-list-guru"
                    class="overflow-y-auto p-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 custom-scrollbar bg-white flex-1">
                </div>

                {{-- Pesan Kosong --}}
                <div id="cg-kosong"
                    class="hidden h-full flex flex-col items-center justify-center p-6 text-center bg-white flex-1">
                    <span class="text-3xl mb-2">✅</span>
                    <span class="text-xs font-bold text-emerald-600">Alhamdulillah, tidak ada jadwal guru yang
                        terikat.</span>
                    <span class="text-[10px] text-slate-400 mt-1">Rentang waktu ini cocok untuk mengadakan rapat.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="loading-overlay"
    class="hidden fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-slate-900/70 backdrop-blur-sm transition-opacity">
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
    width: 6px;
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

// FUNGSI CEK KETERSEDIAAN GURU
function prosesCekGuru() {
    const hari = document.getElementById('cg-hari').value.toLowerCase();
    const mulai = document.getElementById('cg-mulai').value;
    const selesai = document.getElementById('cg-selesai').value;

    if (!mulai || !selesai) {
        alert('Harap pilih jam mulai dan jam selesai terlebih dahulu!');
        return;
    }

    const tMulai = parseInt(mulai.split(':')[0]) * 60 + parseInt(mulai.split(':')[1]);
    const tSelesai = parseInt(selesai.split(':')[0]) * 60 + parseInt(selesai.split(':')[1]);

    const rows = document.querySelectorAll('.jadwal-row');
    let guruMap = new Map();

    rows.forEach(row => {
        if (row.dataset.hari === hari) {
            const rowMulai = row.dataset.mulai;
            const rowSelesai = row.dataset.selesai;

            if (rowMulai && rowSelesai) {
                const rMulai = parseInt(rowMulai.split(':')[0]) * 60 + parseInt(rowMulai.split(':')[1]);
                const rSelesai = parseInt(rowSelesai.split(':')[0]) * 60 + parseInt(rowSelesai.split(':')[1]);

                if (rMulai < tSelesai && rSelesai > tMulai) {
                    const cells = row.querySelectorAll('.jadwal-cell');
                    cells.forEach(cell => {
                        const kodeGuru = cell.dataset.guru;
                        const namaGuru = cell.dataset.namaguru;

                        if (kodeGuru && kodeGuru.trim() !== '') {
                            guruMap.set(kodeGuru, namaGuru);
                        }
                    });
                }
            }
        }
    });

    const hasilContainer = document.getElementById('cg-hasil');
    const listContainer = document.getElementById('cg-list-guru');
    const kosongMsg = document.getElementById('cg-kosong');
    const countTag = document.getElementById('cg-count');

    hasilContainer.classList.remove('hidden');
    hasilContainer.classList.add('flex');
    listContainer.innerHTML = '';

    if (guruMap.size > 0) {
        kosongMsg.classList.add('hidden');
        countTag.classList.remove('hidden');
        countTag.innerText = guruMap.size + ' Guru';

        guruMap.forEach((nama, kode) => {
            const div = document.createElement('div');
            // Menyesuaikan ukuran padding dan text agar selaras dengan form di atasnya
            div.className =
                'flex items-center justify-between bg-white border border-slate-200 p-2 rounded-lg shadow-sm hover:border-blue-300 transition-colors';
            div.innerHTML = `
                <div class="flex items-center gap-2">
                    <div class="bg-indigo-50 text-indigo-700 text-[10px] font-black px-2 py-1 rounded border border-indigo-100">${kode}</div>
                    <span class="text-slate-700 text-[11px] font-bold leading-tight line-clamp-1">${nama}</span>
                </div>
                <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider bg-slate-50 px-1.5 py-0.5 rounded">Dinas</span>
            `;
            listContainer.appendChild(div);
        });
    } else {
        kosongMsg.classList.remove('hidden');
        countTag.classList.add('hidden');
    }
}
</script>
@endpush