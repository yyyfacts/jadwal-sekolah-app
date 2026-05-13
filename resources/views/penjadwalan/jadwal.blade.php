@extends('layouts.app')

@section('content')
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

<div class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 h-[calc(100vh-4rem)] pb-2 pt-2 flex flex-col">

    {{-- FLASH MESSAGES & METRIK SCROLLABLE --}}
    @if(session('success') || !empty($latestMetrics))
    <div x-data="{ show: true }" x-show="show" x-transition class="mb-4 space-y-3 shrink-0">

        {{-- CARD HEADER STATUS --}}
        <div class="flex flex-col p-3 bg-white border-2 border-indigo-100 rounded-lg shadow-sm text-slate-800">
            <div class="flex items-center justify-between mb-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-bold text-xs text-emerald-600">✅
                        {{ session('success') ?? 'Jadwal Terakhir (Tersimpan)' }}</span>
                    @if(!empty($latestMetrics['status_solver']))
                    <span
                        class="px-2 py-0.5 rounded text-[9px] font-bold uppercase border {{ $latestMetrics['status_solver'] == 'OPTIMAL' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-yellow-100 text-yellow-700 border-yellow-200' }}">
                        {{ $latestMetrics['status_solver'] }}
                    </span>
                    @endif
                    @if(!empty($latestMetrics['waktu_komputasi']))
                    <span
                        class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[9px] font-bold uppercase border border-slate-200">
                        ⏱️ Waktu: {{ round($latestMetrics['waktu_komputasi'], 1) }} Detik
                    </span>
                    @endif
                </div>
                <button @click="show = false"
                    class="text-slate-400 hover:text-rose-500 ml-4 font-bold text-lg">&times;</button>
            </div>
            @if(!empty($latestMetrics['status_penjelasan']))
            <p
                class="text-[11px] text-slate-600 font-medium leading-relaxed bg-indigo-50/50 p-2 border border-indigo-50 rounded">
                🤖 <strong>AI Insight:</strong> {{ $latestMetrics['status_penjelasan'] }}
            </p>
            @endif
        </div>

        {{-- BUNGKUSAN METRIK & GRAFIK (SCROLLABLE) --}}
        <div
            class="bg-white border-2 border-slate-800 rounded-xl shadow-lg max-h-[50vh] overflow-y-auto custom-scrollbar p-4 flex flex-col gap-4">

            {{-- GRAFIK KURVA OBJEKTIF --}}
            @if(!empty($latestMetrics['kurva_solver']) && count($latestMetrics['kurva_solver']) > 0)
            <div class="w-full border border-slate-200 bg-slate-50 rounded-lg p-3">
                <h4 class="font-extrabold text-xs text-indigo-700 uppercase mb-1">📈 Riwayat Optimasi AI (Kurva
                    Objektif)</h4>
                <p class="text-[9px] text-slate-500 font-mono mb-2">Grafik memperlihatkan penurunan skor penalti dari
                    awal pencarian hingga solver berhenti.</p>
                <div class="relative w-full h-[200px]">
                    <canvas id="objCurve"></canvas>
                </div>
            </div>
            @endif

            {{-- TABEL HARD & SOFT CONSTRAINT FULL PERHITUNGAN MATEMATIKA --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Hard Constraint --}}
                @if(isset($latestMetrics['breakdown_hard']))
                <div class="border border-blue-200 rounded-lg bg-blue-50/50 p-3 h-fit flex flex-col gap-3 shadow-sm">
                    <strong class="text-xs text-blue-800 block">🎯 Evaluasi Aturan Mutlak (Hard Constraints)</strong>

                    <div
                        class="p-3 bg-white/90 border border-blue-200/50 rounded-md text-[11px] text-blue-900 font-mono flex flex-col gap-1 shadow-[inset_0_1px_3px_rgba(0,0,0,0.02)]">
                        <strong class="text-blue-700">🛡️ Jaminan Mutlak (CP-SAT Solver):</strong>
                        <span>Seluruh aturan mutlak terpenuhi sempurna secara matematis. Jadwal dipastikan bebas
                            bentrok.</span>
                    </div>

                    <div class="overflow-x-auto rounded border border-blue-200/60 bg-white shadow-sm">
                        <table class="w-full text-left border-collapse min-w-full">
                            <thead class="bg-blue-50">
                                <tr class="text-blue-800 text-[10px]">
                                    <th class="px-2 py-1.5 font-bold border-b border-blue-200/60 w-[80%]">Kategori
                                        Evaluasi Mutlak</th>
                                    <th
                                        class="px-2 py-1.5 font-bold text-center border-b border-blue-200/60 border-l w-[20%]">
                                        Pelanggaran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-100/50 text-[10px]">
                                @foreach($latestMetrics['breakdown_hard'] ?? [] as $b)
                                <tr class="hover:bg-blue-50/50 transition-colors">
                                    <td class="px-2 py-1.5"><span class="font-bold">{{ $b['kategori'] }}</span>: <span
                                            class="text-[9px] text-blue-700/80">{{ $b['deskripsi'] }}</span></td>
                                    <td
                                        class="px-2 py-1.5 text-center font-bold border-l border-blue-100/50 text-emerald-600">
                                        {{ $b['pelanggaran'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Soft Constraint --}}
                @if(isset($latestMetrics['breakdown_soft']))
                <div
                    class="border border-emerald-200 rounded-lg bg-emerald-50/50 p-3 h-fit flex flex-col gap-3 shadow-sm">
                    <strong class="text-xs text-emerald-800 block">⚖️ Evaluasi Preferensi (Soft Constraints)</strong>

                    <div
                        class="p-3 bg-white/90 border border-emerald-200/50 rounded-md text-[11px] text-emerald-900 font-mono flex flex-col gap-1 shadow-[inset_0_1px_3px_rgba(0,0,0,0.02)]">
                        <strong class="text-emerald-700">💡 Kualitas Preferensi (Fungsi Objektif):</strong>
                        <span>Total Nilai Penalti (Z) : <strong
                                class="text-[12px] bg-emerald-100 px-1 rounded">{{ $latestMetrics['total_penalti'] ?? 0 }}
                                Poin</strong></span>
                        <span>Optimality Gap : <strong
                                class="text-[12px] bg-emerald-100 px-1 rounded">{{ $latestMetrics['gap_pct'] ?? 0 }}%</strong></span>
                    </div>

                    <div class="overflow-x-auto rounded border border-emerald-200/60 bg-white shadow-sm">
                        <table class="w-full text-left border-collapse min-w-full">
                            <thead class="bg-emerald-50">
                                <tr class="text-emerald-800 text-[10px]">
                                    <th class="px-2 py-1.5 font-bold border-b border-emerald-200/60 w-[80%]">Kategori
                                        Evaluasi (Soft)</th>
                                    <th
                                        class="px-2 py-1.5 font-bold text-center border-b border-emerald-200/60 border-l w-[20%]">
                                        Total Kasus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-emerald-100/50 text-[10px]">
                                @foreach($latestMetrics['breakdown_soft'] ?? [] as $b)
                                <tr class="hover:bg-emerald-50/50 transition-colors">
                                    <td class="px-2 py-1.5"><span class="font-bold">{{ $b['kategori'] }}</span>: <span
                                            class="text-[9px] text-emerald-700/80">{{ $b['deskripsi'] }}</span></td>
                                    <td
                                        class="px-2 py-1.5 text-center font-bold border-l border-emerald-100/50 {{ $b['pelanggaran'] > 0 ? 'text-amber-600 bg-amber-50/30' : 'text-emerald-600' }}">
                                        {{ $b['pelanggaran'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(!empty($latestMetrics['jumlah_pelanggaran_soft']) && $latestMetrics['jumlah_pelanggaran_soft'] >
                    0)
                    <div
                        class="mt-1 bg-white border border-amber-100 rounded p-2 text-[10px] text-amber-700 max-h-32 overflow-y-auto custom-scrollbar shadow-inner">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($latestMetrics['detail_pelanggaran_soft'] ?? [] as $ps) <li>{{ $ps }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-rose-50 border border-rose-100 rounded-lg shadow-sm text-rose-800 shrink-0">
        <span class="font-bold text-xs">❌ {{ session('error') }}</span>
        <button @click="show = false" class="text-rose-400 hover:text-rose-700">&times;</button>
    </div>
    @endif

    {{-- MAIN CARD UI (TABEL JADWAL) --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-md flex flex-col flex-1 overflow-hidden min-h-0">
        {{-- HEADER SECTION --}}
        <div class="px-6 py-4 bg-white shrink-0 z-20 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-2 h-10 bg-indigo-600 rounded-full"></div>
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-800 leading-none">Jadwal Pelajaran Terpadu</h1>
                        <div class="flex items-center gap-2 mt-1.5">
                            <p class="text-slate-500 text-xs font-medium">T.A
                                {{ $judulTahun ?? date('Y').'/'.(date('Y')+1) }}</p>
                            <span class="text-slate-300">|</span>
                            <p
                                class="text-[10px] text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">
                                ⏱️ Terakhir Update: {{ $terakhirGenerate ?? 'Belum pernah' }}
                            </p>
                        </div>
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

                    {{-- FROM GENERATE DENGAN INPUT NUMBER BEBAS --}}
                    <form action="{{ route('jadwal.generate') }}" method="POST" onsubmit="showLoading()"
                        class="m-0 p-0 flex items-center gap-2">
                        @csrf
                        <div
                            class="relative flex items-center bg-white border border-slate-300 rounded-xl px-2 py-1 text-xs focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent transition-all h-[36px]">
                            <span class="mr-1 opacity-70">⏱️</span>
                            <input type="number" name="max_time" min="1" max="30" value="10" required
                                class="w-8 text-center font-bold text-slate-800 focus:outline-none bg-transparent"
                                title="Ketik angka antara 1 sampai 30">
                            <span class="ml-1 text-slate-500 font-medium">Menit</span>
                        </div>
                        <button type="button"
                            onclick="if(confirm('Peringatan: AI akan menyusun jadwal sesuai batas waktu yang Anda ketik. Lanjut?')) this.form.submit()"
                            class="flex items-center gap-2 px-5 py-2 bg-slate-900 hover:bg-indigo-600 text-white font-bold text-xs uppercase rounded-xl shadow-md transition-all h-[36px]">
                            Generate AI
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- TABLE SECTION (SCROLLABLE AREA) --}}
        <div class="w-full flex-1 overflow-auto custom-scrollbar relative bg-slate-50/50 z-10 flex flex-col">
            @if($kelass->isEmpty() || empty($jadwals))
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <h3 class="text-sm font-bold text-slate-600">Data Jadwal Kosong</h3>
            </div>
            @else
            <div class="flex-1 w-full overflow-x-auto overflow-y-auto">
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

                        <tr class="hover:bg-slate-50/80 transition-colors jadwal-row"
                            data-hari="{{ strtolower($namaHari) }}" data-mulai="{{ $wMulai }}"
                            data-selesai="{{ $wSelesai }}">

                            @if($firstRow)
                            <td rowspan="{{ $rowSpanTotal }}"
                                class="w-[40px] min-w-[40px] sticky left-0 z-[30] p-0 bg-white border-r border-b border-slate-200 align-middle text-center shadow-[2px_0_5px_rgba(0,0,0,0.05)]">
                                <div class="font-extrabold text-slate-700 uppercase tracking-widest text-[12px] h-full flex items-center justify-center py-2"
                                    style="writing-mode: vertical-lr; transform: rotate(180deg);">{{ $namaHari }}</div>
                            </td>
                            @php $firstRow = false; @endphp
                            @endif

                            <td
                                class="h-[45px] w-[35px] min-w-[35px] sticky left-[40px] z-[30] p-1 bg-white border-r border-b border-slate-200 text-center font-bold text-slate-700 text-[11px] shadow-[2px_0_5px_rgba(0,0,0,0.05)]">
                                @if(!in_array($tipeTampil, ['Istirahat', 'Upacara', 'Senam', 'Sholat', 'Sholat Dhuha',
                                'Jumat Bersih', 'Pramuka'])) {{ $j }} @endif
                            </td>
                            <td
                                class="h-[45px] w-[90px] min-w-[90px] sticky left-[75px] z-[30] p-1 bg-white border-r border-b border-slate-200 text-center text-[10px] font-mono font-medium text-slate-700 shadow-[2px_0_5px_rgba(0,0,0,0.05)]">
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
@endsection

@push('modals')
<div id="modal-cek-guru"
    class="hidden fixed inset-0 z-[99999] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-2 sm:p-4 transition-opacity">
    <div
        class="bg-white rounded-xl shadow-2xl w-full max-w-4xl h-auto max-h-[90vh] flex flex-col border border-slate-200 overflow-hidden">
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
                    <p class="text-[9px] text-slate-500 mt-0.5">Melihat daftar guru yang sedang ada jadwal mengajar</p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-cek-guru').classList.add('hidden')"
                class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
        </div>

        <div class="flex flex-col flex-1 min-h-0 p-4 bg-white">
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
                        </svg> Cari
                    </button>
                </div>
            </div>

            <div id="cg-hasil"
                class="mt-4 hidden flex-col flex-1 min-h-0 border border-slate-100 rounded-lg overflow-hidden bg-slate-50/50">
                <div class="px-3 py-2 border-b border-slate-100 bg-slate-50 flex items-center justify-between shrink-0">
                    <h4 class="text-[10px] font-bold text-slate-600 uppercase tracking-wide flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Ketersediaan Guru
                    </h4>
                    <span id="cg-count"
                        class="text-[9px] font-bold text-blue-600 bg-blue-100 px-1.5 py-0.5 rounded border border-blue-200 hidden">0</span>
                </div>
                <div id="cg-list-guru"
                    class="overflow-y-auto p-3 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 custom-scrollbar bg-white flex-1">
                </div>
                <div id="cg-kosong"
                    class="hidden h-full flex flex-col items-center justify-center p-6 text-center bg-white flex-1">
                    <span class="text-3xl mb-2">✅</span>
                    <span class="text-xs font-bold text-emerald-600">Alhamdulillah, tidak ada jadwal guru yang
                        terikat.</span>
                    <span class="text-[10px] text-slate-400 mt-1">Semua guru tersedia (kosong).</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="loading-overlay"
    class="hidden fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-slate-900/70 backdrop-blur-sm transition-opacity">
    <div class="bg-white p-8 rounded-3xl shadow-2xl text-center">
        <h3 class="text-xl font-extrabold text-slate-800 mb-2">AI Sedang Menyusun...</h3>
        <p class="text-[11px] text-slate-500 font-mono mt-2 max-w-[200px] mx-auto">Proses memakan waktu sesuai yang Anda
            ketik. Harap bersabar.</p>
    </div>
</div>
@endpush

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

/* Menyembunyikan panah up/down pada input number agar terlihat lebih rapi */
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type=number] {
    -moz-appearance: textfield;
}

[x-cloak] {
    display: none !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Membaca dari data permanen (latestMetrics)
    @if(!empty($latestMetrics['kurva_solver']) && count($latestMetrics['kurva_solver']) > 0)
    const rawData = @json($latestMetrics['kurva_solver']);
    if (rawData && rawData.length > 0) {

        // FORMAT WAKTU KE MENIT & DETIK
        const labels = rawData.map(d => {
            let m = Math.floor(d.waktu / 60);
            let s = Math.round(d.waktu % 60);
            return m > 0 ? `${m}m ${s}s` : `${s}s`;
        });

        const dataPoints = rawData.map(d => d.objektif);

        const ctx = document.getElementById('objCurve').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Skor Penalti Preferensi',
                    data: dataPoints,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.2)',
                    borderWidth: 2,
                    pointRadius: 1,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Waktu Komputasi (Menit & Detik)',
                            color: '#64748b',
                            font: {
                                size: 10,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            font: {
                                size: 9
                            },
                            maxTicksLimit: 15
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Skor Penalti Preferensi',
                            color: '#64748b',
                            font: {
                                size: 10,
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            font: {
                                size: 10
                            }
                        },
                        beginAtZero: false
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' Penalti: ' + context.parsed.y;
                            }
                        }
                    }
                }
            }
        });
    }
    @endif
});

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

    let semuaGuruMap = new Map();
    document.querySelectorAll('.jadwal-cell').forEach(cell => {
        const kode = cell.dataset.guru;
        const nama = cell.dataset.namaguru;
        if (kode && kode.trim() !== '') semuaGuruMap.set(kode, nama);
    });
    const jumlahTotalGuru = semuaGuruMap.size;

    let guruMengajarMap = new Map();

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
                        const kelas = cell.dataset.kelas;

                        if (kodeGuru && kodeGuru.trim() !== '') {
                            if (!guruMengajarMap.has(kodeGuru)) {
                                guruMengajarMap.set(kodeGuru, {
                                    nama: namaGuru,
                                    kelas: new Set()
                                });
                            }
                            guruMengajarMap.get(kodeGuru).kelas.add(kelas.toUpperCase());
                        }
                    });
                }
            }
        }
    });

    let guruKosongMap = new Map();
    semuaGuruMap.forEach((nama, kode) => {
        if (!guruMengajarMap.has(kode)) guruKosongMap.set(kode, nama);
    });

    const hasilContainer = document.getElementById('cg-hasil');
    const listContainer = document.getElementById('cg-list-guru');
    const kosongMsg = document.getElementById('cg-kosong');
    const countTag = document.getElementById('cg-count');

    hasilContainer.classList.remove('hidden');
    hasilContainer.classList.add('flex');
    listContainer.innerHTML = '';
    kosongMsg.classList.add('hidden');

    const jumlahMengajar = guruMengajarMap.size;
    const jumlahKosong = guruKosongMap.size;

    countTag.classList.remove('hidden');
    countTag.innerText = `${jumlahMengajar} Mengajar | ${jumlahKosong} Kosong (Total: ${jumlahTotalGuru})`;
    countTag.className = "text-[9px] font-bold text-blue-600 bg-blue-100 px-1.5 py-0.5 rounded border border-blue-200";

    let htmlContent = '';

    if (jumlahKosong > 0) {
        htmlContent += `
            <div class="col-span-full mb-1 mt-1 border-b border-emerald-100 pb-1">
                <span class="text-[10px] font-extrabold text-emerald-600 uppercase flex items-center gap-1">
                    🟢 Daftar Guru Tersedia (Free)
                </span>
            </div>
        `;
        guruKosongMap.forEach((nama, kode) => {
            htmlContent += `
                <div class="bg-emerald-50/50 border border-emerald-200 shadow-sm p-2 rounded-lg flex flex-col justify-center hover:border-emerald-300 transition-colors">
                    <span class="text-[11px] font-bold text-slate-800 break-words leading-tight">${nama}</span>
                    <div class="mt-1 flex items-center gap-1">
                        <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[8px] font-bold uppercase tracking-wider">
                            Kosong / Available
                        </span>
                    </div>
                </div>
            `;
        });
    }

    if (jumlahMengajar > 0) {
        htmlContent += `
            <div class="col-span-full mb-1 mt-4 border-b border-slate-200 pb-1">
                <span class="text-[10px] font-extrabold text-blue-600 uppercase flex items-center gap-1">
                    🔵 Daftar Guru Sedang Mengajar
                </span>
            </div>
        `;
        guruMengajarMap.forEach((data, kode) => {
            const daftarKelas = Array.from(data.kelas).join(', ');
            htmlContent += `
                <div class="bg-white border border-slate-200 shadow-sm p-2 rounded-lg flex flex-col justify-center hover:border-blue-300 transition-colors">
                    <span class="text-[11px] font-bold text-slate-800 break-words leading-tight">${data.nama}</span>
                    <div class="mt-1 flex items-center gap-1">
                        <span class="px-1.5 py-0.5 bg-indigo-50 border border-indigo-100 text-indigo-600 rounded text-[8px] font-bold uppercase tracking-wider">Kelas</span>
                        <span class="text-[9px] text-slate-600 font-medium truncate" title="${daftarKelas}">${daftarKelas}</span>
                    </div>
                </div>
            `;
        });
    }

    listContainer.innerHTML = htmlContent;
}
</script>
@endpush