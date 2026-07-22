@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

{{-- CONTAINER UTAMA --}}
<div class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 h-[calc(100vh-4rem)] pb-2 pt-2 flex flex-col relative z-0">

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-emerald-50 border border-emerald-100 rounded-lg shadow-sm text-emerald-800 shrink-0">
        <span class="font-bold text-xs">✅ {{ session('success') }}</span>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700">&times;</button>
    </div>
    @endif

    {{-- UNIFIED CARD --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-md flex flex-col flex-1 overflow-hidden">

        {{-- 1. HEADER SECTION --}}
        <div class="px-4 py-3 bg-white shrink-0 z-20 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex gap-2 items-center">
                    <div class="w-1.5 h-6 bg-purple-600 rounded-full"></div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 leading-none">Data Kelas</h1>
                        <p class="text-slate-500 text-[10px] mt-0.5 font-medium">Manajemen kapasitas & distribusi mapel.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div
                        class="hidden md:flex items-center px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg shadow-sm">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Total: <span
                                class="text-purple-600 font-extrabold ml-1">{{ $kelass->count() }}</span></span>
                    </div>

                    <form action="{{ route('kelas.sinkronisasi') }}" method="POST" class="inline m-0">
                        @csrf
                        <button type="submit" onclick="return confirm('Sinkronisasi Batas?')"
                            class="px-3 py-1.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-[10px] font-bold uppercase tracking-wider shadow-sm hover:bg-amber-100 transition">Sync
                            Batas</button>
                    </form>

                    <div class="relative w-48">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="search-kelas-main" oninput="searchMainTable()"
                            class="block w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs outline-none focus:border-purple-500 focus:bg-white transition"
                            placeholder="Cari Kelas...">
                    </div>

                    <button onclick="openModal('modaltambah')"
                        class="px-4 py-1.5 bg-[#9333ea] text-white rounded-lg font-bold text-[10px] uppercase shadow-sm flex items-center gap-1.5 hover:bg-purple-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg> Tambah
                    </button>
                </div>
            </div>
        </div>

        {{-- 2. TABEL DATA --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white">
            <table class="w-full text-left border-collapse min-w-[850px]">
                <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th
                            class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-12 border-b border-slate-200">
                            No</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[30%] border-b border-slate-200">
                            Identitas Kelas</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-[20%] border-b border-slate-200">
                            Beban / Kapasitas</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-[15%] border-b border-slate-200">
                            Waktu Sistem</th>
                        <th
                            class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right w-[25%] border-b border-slate-200">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-kelas-main" class="divide-y divide-slate-100">
                    @forelse($kelass as $index => $k)
                    @php
                    // PERBAIKAN: Pisahkan Offline (Fisik) dan Online (Daring)
                    $totalOffline = $k->jadwals->where('status', 'offline')->sum('jumlah_jam');
                    $totalOnline = $k->jadwals->where('status', 'online')->sum('jumlah_jam');
                    $maxJam = $k->max_jam ?? 48;
                    $percentage = $maxJam > 0 ? ($totalOffline / $maxJam) * 100 : 0;

                    $statusLabel = 'Kosong';
                    $statusBg = 'bg-slate-50 text-slate-500 border-slate-200';
                    $barColor = 'bg-slate-300';
                    $textColor = 'text-slate-600';

                    if ($totalOffline == 0) {
                    $statusLabel = 'Kosong';
                    } elseif ($totalOffline < $maxJam) { $statusLabel='Kurang' ;
                        $statusBg='bg-rose-50 text-rose-600 border-rose-200' ; $barColor='bg-rose-500' ;
                        $textColor='text-rose-600' ; } elseif ($totalOffline==$maxJam) { $statusLabel='Sesuai' ;
                        $statusBg='bg-emerald-50 text-emerald-600 border-emerald-200' ; $barColor='bg-emerald-500' ;
                        $textColor='text-emerald-600' ; } elseif ($totalOffline> $maxJam) {
                        $statusLabel = 'Lebih';
                        $statusBg = 'bg-amber-50 text-amber-600 border-amber-200';
                        $barColor = 'bg-amber-500';
                        $textColor = 'text-amber-600';
                        }
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors"
                            data-filter="{{ strtolower($k->nama_kelas) }} {{ strtolower($k->kode_kelas) }}">
                            <td class="px-4 py-2 text-center text-[11px] font-medium text-slate-400 align-middle">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-3 py-2 align-middle">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-8 w-8 shrink-0 rounded bg-[#f3e8ff] text-[#9333ea] flex items-center justify-center font-bold text-[10px] border border-[#e9d5ff]">
                                        {{ substr($k->nama_kelas, 0, 2) }}
                                    </div>
                                    <div class="leading-tight">
                                        <div class="font-bold text-slate-800 text-xs flex items-center gap-1">
                                            {{ $k->nama_kelas }}
                                            @if($k->waktuKhusus->isNotEmpty())
                                            <span
                                                class="px-1 py-0.5 bg-rose-100 text-rose-600 text-[8px] rounded uppercase font-bold"
                                                title="{{ $k->waktuKhusus->count() }} jam dikecualikan">🚫 Ada
                                                Blokir</span>
                                            @endif
                                        </div>
                                        <div
                                            class="inline-block px-1.5 py-0.5 mt-0.5 rounded bg-slate-100 text-slate-500 font-bold text-[9px] uppercase border border-slate-200">
                                            {{ $k->kode_kelas }}
                                        </div>
                                        @if($k->waliKelas) <span class="text-[9px] text-slate-400 ml-1">Wali:
                                            {{ $k->waliKelas->nama_guru }}</span> @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-center align-middle">
                                <div class="flex flex-col items-center gap-1.5">
                                    <span class="text-[10px] font-bold {{ $textColor }}">{{ $totalOffline }} /
                                        {{ $maxJam }} Jam</span>
                                    <div class="w-20 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full {{ $barColor }}" style="width: {{ min($percentage, 100) }}%">
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span
                                            class="px-2 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wide border {{ $statusBg }}"
                                            title="Kapasitas Fisik/Offline: {{ $maxJam }} Jam">
                                            {{ $statusLabel }}
                                        </span>
                                        @if($totalOnline > 0)
                                        <span
                                            class="px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wide bg-blue-50 text-blue-600 border border-blue-200"
                                            title="Jadwal Daring/Online">
                                            +{{ $totalOnline }} Daring
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-center align-middle">
                                <div class="flex flex-col items-center gap-0.5 text-[9px]">
                                    <span class="text-slate-400" title="Dibuat: {{ $k->created_at }}">➕
                                        {{ $k->created_at ? $k->created_at->format('d/m/Y') : '-' }}</span>
                                    <span class="text-purple-400" title="Diperbarui: {{ $k->updated_at }}">🔄
                                        {{ $k->updated_at ? $k->updated_at->format('d/m/Y') : '-' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-right align-middle">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button onclick="openModal('modaljadwal{{ $k->id }}')"
                                        class="flex items-center gap-1 px-2.5 py-1.5 bg-[#1e293b] text-white text-[10px] font-bold rounded-lg shadow-sm hover:bg-slate-800 transition"><svg
                                            class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4"></path>
                                        </svg> Distribusi</button>
                                    <button onclick="openModal('edit{{ $k->id }}')"
                                        class="p-1.5 border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-300 rounded-lg bg-white transition"><svg
                                            class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                            </path>
                                        </svg></button>
                                    <form action="{{ route('kelas.destroy', $k->id) }}" method="POST"
                                        onsubmit="return confirm('Hapus kelas?')" class="inline m-0">@csrf
                                        @method('DELETE')<button type="submit"
                                            class="p-1.5 border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-300 rounded-lg bg-white transition"><svg
                                                class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg></button></form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-xs text-slate-400">Belum ada data.</td>
                        </tr>
                        @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- AREA MODAL TAMBAH --}}
    <div id="modaltambah"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[999] hidden items-center justify-center p-2">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden border border-white/20">
            <div class="px-4 py-3 border-b border-slate-100 flex justify-between bg-slate-50">
                <h3 class="font-bold text-sm flex items-center gap-1.5"><span
                        class="w-1 h-4 bg-purple-600 rounded"></span> Tambah Kelas</h3><button
                    onclick="closeModal('modaltambah')"
                    class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>
            <form action="{{ route('kelas.store') }}" method="POST" class="p-4 space-y-3">
                @csrf
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Kelas</label><input
                        type="text" name="nama_kelas"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-purple-500 outline-none"
                        required></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kode</label><input
                            type="text" name="kode_kelas"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs font-mono uppercase focus:ring-2 focus:ring-purple-500 outline-none"
                            required></div>
                    <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kapasitas
                            (Jam)</label><input type="number" name="max_jam" value="48"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-center font-bold focus:ring-2 focus:ring-purple-500 outline-none"
                            required></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Limit
                            Harian</label><input type="number" name="limit_harian" value="10"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-center focus:ring-2 focus:ring-purple-500 outline-none"
                            required></div>
                    <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Limit
                            Jumat</label><input type="number" name="limit_jumat" value="7"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-center focus:ring-2 focus:ring-purple-500 outline-none"
                            required></div>
                </div>
                <p class="text-[9px] text-slate-400 -mt-1">Jam Kosong / Blokir bisa diatur belakangan lewat tombol
                    "Ubah" setelah kelas ini tersimpan.</p>
                <button type="submit"
                    class="w-full bg-slate-900 text-white font-bold py-2.5 rounded-lg text-[10px] uppercase mt-2 hover:bg-purple-600 transition">Simpan</button>
            </form>
        </div>
    </div>

    {{-- AREA MODAL JAM KOSONG / BLOKIR KELAS (satu instance, dipake bareng buat semua kelas) --}}
    <div id="modaljamkhusus"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[999] hidden items-center justify-center p-2 sm:p-4">
        <div
            class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[85vh] flex flex-col border border-white/20 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex justify-between items-start bg-slate-50 shrink-0">
                <div>
                    <h3 class="font-bold text-sm flex items-center gap-1.5"><span
                            class="w-1 h-4 bg-purple-600 rounded"></span> Jam Kosong / Blokir Kelas</h3>
                    <p class="text-[10px] text-slate-400 mt-0.5">Kelas: <span id="jk-nama-kelas"
                            class="font-bold text-slate-600"></span></p>
                </div>
                <button onclick="closeModal('modaljamkhusus')"
                    class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <div class="px-4 py-2 bg-purple-50 text-[9px] text-purple-700 border-b border-purple-100 shrink-0">
                💡 Default semua <b>"Belajar"</b> (normal). Pilih tipe lain di jam yang kelas ini gak bisa dipakai
                (ujian, ekskul, dll) - otomatis di-skip pas generate, kelas lain gak kepengaruh.
            </div>

            <div id="jk-isi" class="overflow-y-auto px-4 py-3 space-y-4 flex-1">
                <p class="text-xs text-slate-400 text-center py-4">Memuat...</p>
            </div>

            <div class="px-4 py-3 border-t border-slate-100 flex justify-end gap-2 shrink-0 bg-white">
                <button onclick="closeModal('modaljamkhusus')"
                    class="px-3 py-2 rounded-lg text-[10px] font-bold uppercase text-slate-500 hover:bg-slate-100">Batal</button>
                <button onclick="simpanJamKhusus()"
                    class="px-4 py-2 rounded-lg text-[10px] font-bold uppercase text-white bg-purple-600 hover:bg-purple-700">Simpan</button>
            </div>
        </div>
    </div>

    {{-- AREA MODAL EDIT --}}
    @foreach($kelass as $k)
    <div id="edit{{ $k->id }}"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[999] hidden items-center justify-center p-2">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden border border-white/20">
            <div class="px-4 py-3 border-b border-amber-100 flex justify-between bg-amber-50">
                <h3 class="font-bold text-sm text-amber-800 flex items-center gap-1.5"><span
                        class="w-1 h-4 bg-amber-500 rounded"></span> Ubah Kelas</h3><button
                    onclick="closeModal('edit{{ $k->id }}')"
                    class="text-amber-400 hover:text-amber-600 text-lg">&times;</button>
            </div>
            <form action="{{ route('kelas.update', $k->id) }}" method="POST" class="p-4 space-y-3">
                @csrf @method('PUT')
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Kelas</label><input
                        type="text" name="nama_kelas" value="{{ $k->nama_kelas }}"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 outline-none"
                        required></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kode</label><input
                            type="text" name="kode_kelas" value="{{ $k->kode_kelas }}"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs font-mono uppercase focus:ring-2 focus:ring-amber-500 outline-none"
                            required></div>
                    <div><label
                            class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kapasitas</label><input
                            type="number" name="max_jam" value="{{ $k->max_jam ?? 48 }}"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-center font-bold focus:ring-2 focus:ring-amber-500 outline-none"
                            required></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Limit
                            Harian</label><input type="number" name="limit_harian" value="{{ $k->limit_harian ?? 10 }}"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-center focus:ring-2 focus:ring-amber-500 outline-none"
                            required></div>
                    <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Limit
                            Jumat</label><input type="number" name="limit_jumat" value="{{ $k->limit_jumat ?? 7 }}"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs text-center focus:ring-2 focus:ring-amber-500 outline-none"
                            required></div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Jam Kosong /
                        Blokir</label>
                    <button type="button" onclick="bukaModalJamKhusus({{ $k->id }}, '{{ addslashes($k->nama_kelas) }}')"
                        class="w-full flex items-center justify-between border border-slate-300 rounded-lg px-3 py-2 text-xs text-slate-600 hover:bg-slate-50 transition">
                        <span>Atur jam kosong / blokir
                            @if($k->waktuKhusus->isNotEmpty())
                            <b class="text-rose-600">({{ $k->waktuKhusus->count() }} jam)</b>
                            @endif
                        </span>
                        <span class="text-slate-400">›</span>
                    </button>
                    <span class="text-[9px] text-slate-400 mt-0.5 block">Pilih jam yang kelas ini gak bisa dipakai
                        (misal lagi ujian/ekskul) - otomatis di-skip pas generate.</span>
                </div>
                <button type="submit"
                    class="w-full bg-amber-500 text-white font-bold py-2.5 rounded-lg text-[10px] uppercase mt-2 hover:bg-amber-600 transition">Perbarui</button>
            </form>
        </div>
    </div>

    {{-- AREA MODAL JADWAL KELAS --}}
    <div id="modaljadwal{{ $k->id }}"
        class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[999] hidden items-center justify-center p-2 sm:p-4 transition-opacity">
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[85vh] flex flex-col border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex justify-between bg-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-purple-600 text-white rounded"><svg class="w-4 h-4" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg></div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-800">{{ $k->nama_kelas }}</h3>
                        <p class="text-[10px] text-slate-500 font-medium">Fisik (Offline):
                            {{ $k->jadwals->where('status', 'offline')->sum('jumlah_jam') }} JP | Daring (Online):
                            {{ $k->jadwals->where('status', 'online')->sum('jumlah_jam') }} JP
                        </p>
                    </div>
                </div>
                <button onclick="closeModal('modaljadwal{{ $k->id }}')"
                    class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
            </div>

            <div class="flex flex-col lg:flex-row h-full overflow-hidden">
                <div class="flex-1 flex flex-col h-full border-r border-slate-100 relative">
                    <div class="p-2 border-b bg-white shrink-0"><input type="text" id="search-{{ $k->id }}"
                            oninput="searchTable({{ $k->id }})" placeholder="Cari Mapel/Guru..."
                            class="w-full border border-slate-200 rounded px-2 py-1.5 text-xs outline-none focus:border-purple-500 bg-slate-50 focus:bg-white transition">
                    </div>
                    <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">
                        <table class="w-full text-[10px] border-collapse">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase sticky top-0 shadow-sm">
                                <tr>
                                    <th class="px-3 py-2 text-left w-[40%] border-b border-slate-200">Mapel</th>
                                    <th class="px-3 py-2 text-left w-[30%] border-b border-slate-200">Guru</th>
                                    <th class="px-3 py-2 text-center w-[15%] border-b border-slate-200">Jam</th>
                                    <th class="px-3 py-2 text-right w-[15%] border-b border-slate-200">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-kelas-{{ $k->id }}" class="divide-y divide-slate-100">
                                @foreach($k->jadwals as $jadwal)
                                <tr class="hover:bg-purple-50/50 group transition-colors">
                                    <td class="px-3 py-2 font-bold text-slate-700">
                                        {{ $jadwal->mapel->nama_mapel ?? '-' }}
                                        @if($jadwal->status == 'online')
                                        <span
                                            class="ml-1 px-1 py-0.2 bg-blue-100 text-blue-600 text-[8px] rounded uppercase font-extrabold">Daring</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-slate-600 font-medium">
                                        {{ $jadwal->guru->nama_guru ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-center align-middle">
                                        <div class="flex flex-col items-center">
                                            <span
                                                class="bg-white border border-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-bold text-[9px] shadow-sm">{{ $jadwal->jumlah_jam }}
                                                Jam</span>
                                            <span
                                                class="text-[8px] text-slate-400 mt-0.5 uppercase tracking-wider font-bold">{{ $jadwal->tipe_jam }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-right align-middle">
                                        <div
                                            class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button type="button"
                                                onclick="editJadwalInline('{{ $k->id }}', '{{ $jadwal->id }}', '{{ $jadwal->mapel_id }}', '{{ $jadwal->guru_id }}', '{{ $jadwal->jumlah_jam }}', '{{ $jadwal->tipe_jam }}', '{{ $jadwal->status ?? 'offline' }}')"
                                                class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                    </path>
                                                </svg>
                                            </button>
                                            <button onclick="hapusJadwal({{ $jadwal->id }}, this)"
                                                class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="w-full lg:w-[280px] bg-slate-50 flex flex-col h-full border-t lg:border-t-0">
                    <div class="p-4 overflow-y-auto">
                        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                            <h4
                                class="font-bold text-[10px] text-slate-700 uppercase tracking-wider mb-3 border-b border-slate-100 pb-1.5 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Input Distribusi
                            </h4>

                            <form id="form-jadwal-{{ $k->id }}"
                                data-store-url="{{ route('kelas.simpanJadwal', $k->id) }}"
                                action="{{ route('kelas.simpanJadwal', $k->id) }}" method="POST"
                                onsubmit="handleFormJadwal(event, this, '{{ $k->id }}', 'kelas')">
                                @csrf
                                <div id="method-spoof-{{ $k->id }}"></div>
                                <div class="space-y-3.5 text-[10px]">

                                    <div class="relative custom-select-wrapper" id="wrapper-mapel-{{ $k->id }}">
                                        <label class="font-bold text-slate-500 block mb-1">MAPEL</label>
                                        <input type="hidden" name="mapel_id" id="real-input-mapel-{{ $k->id }}"
                                            required>
                                        <button type="button" onclick="toggleCustomDropdown('mapel', '{{ $k->id }}')"
                                            class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-left flex justify-between items-center outline-none focus:border-purple-500 transition">
                                            <span id="display-mapel-{{ $k->id }}" class="truncate text-slate-700">Pilih
                                                Mapel...</span>
                                            <svg class="w-3 h-3 shrink-0 text-slate-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <div id="dropdown-mapel-{{ $k->id }}"
                                            class="hidden absolute z-50 w-full bg-white border border-slate-200 rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto flex flex-col">
                                            <div class="p-1.5 sticky top-0 bg-white border-b border-slate-100 z-10">
                                                <input type="text" placeholder="Cari Mapel..."
                                                    onkeyup="filterCustomDropdown('mapel', '{{ $k->id }}', this)"
                                                    class="w-full px-2 py-1.5 text-[10px] bg-slate-50 border border-slate-200 rounded outline-none focus:border-purple-500">
                                            </div>
                                            <div id="list-mapel-{{ $k->id }}" class="p-1">
                                                @foreach($mapels as $m)
                                                <div class="option-item p-1.5 hover:bg-purple-50 rounded cursor-pointer text-slate-700 transition-colors"
                                                    data-value="{{ $m->id }}" data-label="{{ $m->nama_mapel }}"
                                                    onclick="selectCustomOption('mapel', '{{ $k->id }}', this)">
                                                    {{ $m->nama_mapel }}
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="relative custom-select-wrapper" id="wrapper-guru-{{ $k->id }}">
                                        <label class="font-bold text-slate-500 block mb-1">GURU</label>
                                        <input type="hidden" name="guru_id" id="real-input-guru-{{ $k->id }}" required>
                                        <button type="button" onclick="toggleCustomDropdown('guru', '{{ $k->id }}')"
                                            class="w-full px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-lg text-left flex justify-between items-center outline-none focus:border-purple-500 transition">
                                            <span id="display-guru-{{ $k->id }}" class="truncate text-slate-700">Pilih
                                                Guru...</span>
                                            <svg class="w-3 h-3 shrink-0 text-slate-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <div id="dropdown-guru-{{ $k->id }}"
                                            class="hidden absolute z-50 w-full bg-white border border-slate-200 rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto flex flex-col">
                                            <div class="p-1.5 sticky top-0 bg-white border-b border-slate-100 z-10">
                                                <input type="text" placeholder="Cari Guru..."
                                                    onkeyup="filterCustomDropdown('guru', '{{ $k->id }}', this)"
                                                    class="w-full px-2 py-1.5 text-[10px] bg-slate-50 border border-slate-200 rounded outline-none focus:border-purple-500">
                                            </div>
                                            <div id="list-guru-{{ $k->id }}" class="p-1">
                                                @foreach($gurus as $g)
                                                <div class="option-item p-1.5 hover:bg-purple-50 rounded cursor-pointer text-slate-700 transition-colors"
                                                    data-value="{{ $g->id }}" data-label="{{ $g->nama_guru }}"
                                                    onclick="selectCustomOption('guru', '{{ $k->id }}', this)">
                                                    {{ $g->nama_guru }}
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="font-bold text-slate-500 block mb-1">JAM</label>
                                            <input type="number" name="jumlah_jam" id="input-jam-{{ $k->id }}"
                                                class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 bg-slate-50 text-center outline-none focus:border-purple-500 focus:bg-white transition"
                                                min="1" max="10" required>
                                        </div>
                                        <div>
                                            <label class="font-bold text-slate-500 block mb-1">TIPE</label>
                                            <select name="tipe_jam" id="select-tipe-{{ $k->id }}"
                                                class="w-full border border-slate-200 rounded-lg px-1.5 py-1.5 bg-slate-50 outline-none focus:border-purple-500 focus:bg-white transition">
                                                <option value="single">Satu(1x)</option>
                                                <option value="double">Dua(2x)</option>
                                                <option value="triple">Tiga(3x)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="font-bold text-slate-500 block mb-1">STATUS</label>
                                        <select name="status" id="select-status-{{ $k->id }}"
                                            class="w-full border border-slate-200 rounded-lg px-2.5 py-2 bg-slate-50 outline-none focus:border-purple-500 focus:bg-white transition">
                                            <option value="offline">Luring (Jadwal Tetap)</option>
                                            <option value="online">Daring</option>
                                        </select>
                                    </div>

                                    <div class="flex gap-2 mt-2">
                                        <button type="button" id="btn-cancel-{{ $k->id }}"
                                            onclick="resetFormJadwal('{{ $k->id }}')"
                                            class="hidden w-1/3 bg-slate-200 hover:bg-slate-300 text-slate-700 py-2.5 rounded-lg font-bold uppercase tracking-wider shadow-md transition">Batal</button>
                                        <button type="submit" id="btn-submit-{{ $k->id }}"
                                            class="flex-1 bg-slate-900 hover:bg-purple-600 text-white py-2.5 rounded-lg font-bold uppercase tracking-wider shadow-md transition">Simpan</button>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>
@endsection

@push('scripts')
<script>
function searchMainTable() {
    const input = document.getElementById('search-kelas-main').value.toLowerCase();
    document.querySelectorAll('#tbody-kelas-main tr[data-filter]').forEach(row => {
        row.style.display = row.getAttribute('data-filter').includes(input) ? "" : "none";
    });
}

function searchTable(id) {
    const filter = document.getElementById('search-' + id).value.toLowerCase();
    const rows = document.getElementById('tbody-kelas-' + id).getElementsByTagName('tr');
    for (let row of rows) {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
    }
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

// LOGIKA CUSTOM DROPDOWN
function toggleCustomDropdown(type, id) {
    const wrapper = document.getElementById(`wrapper-${type}-${id}`);
    const dropdown = document.getElementById(`dropdown-${type}-${id}`);
    document.querySelectorAll('.custom-select-wrapper').forEach(el => {
        if (el !== wrapper) {
            el.style.zIndex = "0";
            el.querySelector('[id^="dropdown-"]')?.classList.add('hidden');
        }
    });
    dropdown.classList.toggle('hidden');
    wrapper.style.zIndex = dropdown.classList.contains('hidden') ? "0" : "50";
}

function filterCustomDropdown(type, id, input) {
    const filter = input.value.toLowerCase();
    document.querySelectorAll(`#list-${type}-${id} .option-item`).forEach(item => {
        const label = (item.getAttribute('data-label') || '').toLowerCase();
        item.style.display = label.includes(filter) ? "" : "none";
    });
}

function selectCustomOption(type, entityId, element) {
    document.getElementById(`real-input-${type}-${entityId}`).value = element.getAttribute('data-value');
    document.getElementById(`display-${type}-${entityId}`).innerText = element.getAttribute('data-label');
    document.getElementById(`dropdown-${type}-${entityId}`).classList.add('hidden');
    document.getElementById(`wrapper-${type}-${entityId}`).style.zIndex = "0";
}

function setCustomDropdownValue(type, entityId, value) {
    const option = document.querySelector(`#list-${type}-${entityId} .option-item[data-value="${value}"]`);
    if (option) selectCustomOption(type, entityId, option);
}

function resetCustomDropdown(type, entityId, defaultText) {
    document.getElementById(`real-input-${type}-${entityId}`).value = '';
    document.getElementById(`display-${type}-${entityId}`).innerText = defaultText;
    const searchInput = document.querySelector(`#dropdown-${type}-${entityId} input[type="text"]`);
    if (searchInput) {
        searchInput.value = '';
        filterCustomDropdown(type, entityId, searchInput);
    }
}

document.addEventListener('click', e => {
    if (!e.target.closest('.custom-select-wrapper')) {
        document.querySelectorAll('.custom-select-wrapper [id^="dropdown-"]').forEach(el => el.classList.add(
            'hidden'));
        document.querySelectorAll('.custom-select-wrapper').forEach(el => el.style.zIndex = "0");
    }
});

// LOGIKA EDIT INLINE FORM JADWAL KELAS
function editJadwalInline(kelasId, jadwalId, mapelId, guruId, jam, tipe, status) {
    setCustomDropdownValue('mapel', kelasId, mapelId);
    setCustomDropdownValue('guru', kelasId, guruId);
    document.getElementById(`input-jam-${kelasId}`).value = jam;
    document.getElementById(`select-tipe-${kelasId}`).value = tipe;
    document.getElementById(`select-status-${kelasId}`).value = status;

    const form = document.getElementById(`form-jadwal-${kelasId}`);
    form.action = `/kelas/jadwal/${jadwalId}`;
    document.getElementById(`method-spoof-${kelasId}`).innerHTML = `<input type="hidden" name="_method" value="PUT">`;

    const btnSubmit = document.getElementById(`btn-submit-${kelasId}`);
    btnSubmit.innerText = "Perbarui";
    btnSubmit.classList.replace('bg-slate-900', 'bg-amber-500');
    btnSubmit.classList.replace('hover:bg-purple-600', 'hover:bg-amber-600');
    document.getElementById(`btn-cancel-${kelasId}`).classList.remove('hidden');
}

function resetFormJadwal(kelasId) {
    const form = document.getElementById(`form-jadwal-${kelasId}`);
    form.reset();
    resetCustomDropdown('mapel', kelasId, 'Pilih Mapel...');
    resetCustomDropdown('guru', kelasId, 'Pilih Guru...');

    form.action = form.getAttribute('data-store-url');
    document.getElementById(`method-spoof-${kelasId}`).innerHTML = '';

    const btnSubmit = document.getElementById(`btn-submit-${kelasId}`);
    btnSubmit.innerText = "Simpan";
    btnSubmit.classList.replace('bg-amber-500', 'bg-slate-900');
    btnSubmit.classList.replace('hover:bg-amber-600', 'hover:bg-purple-600');
    document.getElementById(`btn-cancel-${kelasId}`).classList.add('hidden');
}

// SIMPAN ATAU UPDATE TANPA RELOAD HALAMAN
async function handleFormJadwal(e, form, entityId, type) {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const oldText = btn.innerText;
    btn.innerText = "...";
    btn.disabled = true;

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (res.ok) {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTbody = doc.getElementById(`tbody-${type}-${entityId}`);
                    const oldTbody = document.getElementById(`tbody-${type}-${entityId}`);
                    if (newTbody && oldTbody) oldTbody.innerHTML = newTbody.innerHTML;
                });
            resetFormJadwal(entityId);
        } else {
            alert("Gagal menyimpan data.");
        }
    } catch (err) {
        alert("Error sistem.");
    } finally {
        btn.disabled = false;
        btn.innerText = oldText;
    }
}

// HAPUS TANPA RELOAD HALAMAN
async function hapusJadwal(id, btn) {
    if (!confirm('Hapus jadwal ini?')) return;
    try {
        const res = await fetch(`/kelas/jadwal/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        if (res.ok) btn.closest('tr').remove();
    } catch (e) {}
}

// ================================================================
// MODAL JAM KOSONG / BLOKIR KELAS
// ================================================================
const JK_TIPE_OPTIONS = ['Belajar', 'Kosong', 'Ujian', 'Ekstrakurikuler', 'Kegiatan Khusus'];
let jkKelasIdAktif = null;

async function bukaModalJamKhusus(kelasId, namaKelas) {
    jkKelasIdAktif = kelasId;
    document.getElementById('jk-nama-kelas').innerText = namaKelas;
    document.getElementById('jk-isi').innerHTML =
    '<p class="text-xs text-slate-400 text-center py-4">Memuat...</p>';
    openModal('modaljamkhusus');

    try {
        const res = await fetch(`/kelas/${kelasId}/waktu-khusus`);
        const data = await res.json();
        renderJamKhusus(data.hari);
    } catch (e) {
        document.getElementById('jk-isi').innerHTML =
            '<p class="text-xs text-red-500 text-center py-4">Gagal memuat data. Coba lagi.</p>';
    }
}

function renderJamKhusus(hariList) {
    const opsiHtml = (selected) => JK_TIPE_OPTIONS
        .map(t => `<option value="${t}" ${t === selected ? 'selected' : ''}>${t}</option>`)
        .join('');

    const html = (hariList || []).map(hari => `
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase mb-1.5">${hari.nama_hari}</p>
                <div class="space-y-1">
                    ${hari.slots.map(s => `
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-24 shrink-0 text-slate-500">
                                Jam ke-${s.jam_ke}
                                <span class="text-slate-300 mx-0.5">·</span>
                                <span class="text-slate-400 text-[10px]">${(s.waktu_mulai || '').slice(0, 5)}</span>
                            </span>
                            <select data-hari-id="${hari.master_hari_id}" data-jam-ke="${s.jam_ke}"
                                class="jk-tipe-select flex-1 border border-slate-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-purple-500 outline-none">
                                ${opsiHtml(s.tipe_khusus)}
                            </select>
                        </div>
                    `).join('')}
                </div>
            </div>
        `).join('');

    document.getElementById('jk-isi').innerHTML = html ||
        '<p class="text-xs text-slate-400 text-center py-4">Belum ada hari aktif / slot Belajar.</p>';
}

async function simpanJamKhusus() {
    if (!jkKelasIdAktif) return;

    const items = Array.from(document.querySelectorAll('.jk-tipe-select')).map(sel => ({
        master_hari_id: sel.dataset.hariId,
        jam_ke: sel.dataset.jamKe,
        tipe: sel.value,
    }));

    try {
        const res = await fetch(`/kelas/${jkKelasIdAktif}/waktu-khusus`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                items
            })
        });
        const data = await res.json();

        if (data.success) {
            closeModal('modaljamkhusus');
            window.location.reload();
        } else {
            alert(data.message || 'Gagal menyimpan.');
        }
    } catch (e) {
        alert('Error: ' + e.message);
    }
}
</script>
@endpush