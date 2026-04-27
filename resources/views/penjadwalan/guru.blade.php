@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

{{-- CONTAINER UTAMA (SUPER PADAT & FULL WIDTH) --}}
<div class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 h-[calc(100vh-4rem)] pb-2 pt-2 flex flex-col relative z-0">

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-emerald-50 border border-emerald-100 rounded-lg shadow-sm text-emerald-800 shrink-0">
        <div class="flex items-center gap-2">
            <span class="font-bold text-xs">✅ {{ session('success') }}</span>
        </div>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700">&times;</button>
    </div>
    @endif

    {{-- UNIFIED CARD --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-md flex flex-col flex-1 overflow-hidden">

        {{-- 1. HEADER SECTION --}}
        <div class="px-4 py-3 bg-white shrink-0 z-20 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex gap-2 items-center">
                    <div class="w-1.5 h-6 bg-indigo-600 rounded-full"></div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 leading-none">Data Guru</h1>
                        <p class="text-slate-500 text-[10px] mt-0.5 font-medium">Manajemen profil, NIP, & beban jam.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div
                        class="hidden md:flex items-center px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg shadow-sm">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                            Total: <span class="text-indigo-600 ml-1 font-extrabold">{{ $gurus->count() }}</span>
                        </span>
                    </div>

                    <div class="relative group w-48">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="search-guru-main" oninput="searchMainTable()"
                            class="block w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-indigo-500 focus:bg-white transition"
                            placeholder="Cari Guru...">
                    </div>

                    <button type="button" onclick="openModal('modaltambah')"
                        class="px-4 py-1.5 font-bold text-white transition-all bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        <span class="text-[10px] uppercase tracking-wide">Tambah</span>
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
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[35%] border-b border-slate-200">
                            Profil Guru</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-[15%] border-b border-slate-200">
                            Beban Mengajar</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-[15%] border-b border-slate-200">
                            Waktu Sistem</th>
                        <th
                            class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right w-[25%] border-b border-slate-200">
                            Aksi & Jadwal</th>
                    </tr>
                </thead>

                <tbody id="tbody-guru-main" class="divide-y divide-slate-100">
                    @php
                    $themes = [
                    ['avatar' => 'bg-pink-600', 'pillBg' => 'bg-pink-50', 'pillText' => 'text-pink-700', 'dot' =>
                    'bg-pink-500'],
                    ['avatar' => 'bg-cyan-500', 'pillBg' => 'bg-cyan-50', 'pillText' => 'text-cyan-700', 'dot' =>
                    'bg-cyan-500'],
                    ['avatar' => 'bg-blue-800', 'pillBg' => 'bg-blue-50', 'pillText' => 'text-blue-800', 'dot' =>
                    'bg-blue-600'],
                    ['avatar' => 'bg-indigo-500', 'pillBg' => 'bg-indigo-50', 'pillText' => 'text-indigo-700', 'dot' =>
                    'bg-indigo-500'],
                    ];
                    @endphp

                    @forelse($gurus as $index => $g)
                    @php
                    $theme = $themes[$index % 4];
                    $jam = $g->total_jam_mengajar;

                    // Logika Status Beban Mengajar (24-40 jam adalah Ideal/Sesuai)
                    $statusLabel = 'Kosong';
                    $statusBg = 'bg-slate-50 text-slate-500 border-slate-200';

                    if ($jam > 0 && $jam < 24) { $statusLabel='Kurang' ;
                        $statusBg='bg-rose-50 text-rose-600 border-rose-200' ; } elseif ($jam>= 24 && $jam <= 40) {
                            $statusLabel='Sesuai' ; $statusBg='bg-emerald-50 text-emerald-600 border-emerald-200' ; }
                            elseif ($jam> 40) {
                            $statusLabel = 'Lebih';
                            $statusBg = 'bg-amber-50 text-amber-600 border-amber-200';
                            }
                            @endphp
                            <tr class="group hover:bg-slate-50/80 transition-colors"
                                data-filter="{{ strtolower($g->nama_guru) }} {{ strtolower($g->kode_guru) }}">
                                <td class="px-4 py-2 text-center align-middle">
                                    <span class="font-medium text-slate-400 text-[11px]">{{ $index + 1 }}</span>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-7 w-7 shrink-0 rounded-full {{ $theme['avatar'] }} text-white flex items-center justify-center font-bold text-[10px] shadow-sm">
                                            {{ substr($g->nama_guru, 0, 1) }}
                                        </div>
                                        <div class="leading-tight">
                                            <div class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                                {{ $g->nama_guru }}
                                                @if(!empty($g->hari_array))
                                                <span
                                                    class="px-1 py-0.5 {{ $g->jenis_hari == 'hard' ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600' }} text-[8px] rounded uppercase"
                                                    title="Aturan Hari">{{ $g->jenis_hari == 'hard' ? 'Ketat' : 'Bebas' }}</span>
                                                @endif
                                            </div>
                                            <div
                                                class="inline-block px-1.5 py-0.5 mt-0.5 rounded bg-slate-100 text-slate-500 font-bold text-[9px] uppercase tracking-wide border border-slate-200">
                                                {{ $g->kode_guru }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center align-middle">
                                    <div class="flex flex-col items-center gap-1">
                                        @if($jam > 0)
                                        <div
                                            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md {{ $theme['pillBg'] }} {{ $theme['pillText'] }} border border-slate-100">
                                            <div class="w-1.5 h-1.5 rounded-full {{ $theme['dot'] }}"></div>
                                            <span class="text-[10px] font-bold">{{ $jam }} Jam</span>
                                        </div>
                                        <span
                                            class="px-2 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wide border {{ $statusBg }}"
                                            title="Standar Sesuai: 24 - 40 Jam">
                                            {{ $statusLabel }}
                                        </span>
                                        @else
                                        <div
                                            class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-slate-50 text-slate-400 border border-slate-100">
                                            <div class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                                            <span class="text-[10px] font-bold">0 Jam</span>
                                        </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center align-middle">
                                    <div class="flex flex-col items-center gap-0.5 text-[9px]">
                                        <span class="text-slate-400" title="Dibuat: {{ $g->created_at }}">➕
                                            {{ $g->created_at ? $g->created_at->format('d/m/Y') : '-' }}</span>
                                        <span class="text-indigo-400" title="Diperbarui: {{ $g->updated_at }}">🔄
                                            {{ $g->updated_at ? $g->updated_at->format('d/m/Y') : '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 align-middle text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" onclick="openModal('modaljadwal{{ $g->id }}')"
                                            class="flex items-center gap-1 px-2.5 py-1.5 border border-slate-200 text-slate-600 hover:border-indigo-400 hover:text-indigo-600 text-[10px] font-bold rounded-lg transition-colors bg-white shadow-sm">
                                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg> Jadwal
                                        </button>
                                        <button type="button" onclick="openModal('edit{{ $g->id }}')"
                                            class="p-1.5 border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-300 rounded-lg transition-colors bg-white">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                </path>
                                            </svg>
                                        </button>
                                        <form action="{{ route('guru.destroy', $g->id) }}" method="POST"
                                            onsubmit="return confirm('Hapus data {{ $g->nama_guru }}?')" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="p-1.5 border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-300 rounded-lg transition-colors bg-white">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr id="no-data-row">
                                <td colspan="5" class="px-4 py-12 text-center text-slate-400 text-xs">Belum ada data
                                    guru.</td>
                            </tr>
                            @endforelse
                            <tr id="search-no-result" class="hidden">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400 text-xs">Guru tidak
                                    ditemukan.</td>
                            </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('modals')
{{-- Modal Tambah --}}
<div id="modaltambah"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] hidden items-center justify-center p-2">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-1.5"><span
                    class="w-1 h-4 bg-indigo-600 rounded-full"></span> Tambah Guru</h3>
            <button type="button" onclick="closeModal('modaltambah')"
                class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
        </div>
        <form action="{{ route('guru.store') }}" method="POST" class="p-4 space-y-3">
            @csrf
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label>
                <input type="text" name="nama_guru"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none text-xs"
                    required>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">NIP / Kode</label>
                <input type="text" name="kode_guru"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none font-mono text-xs uppercase"
                    required>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Hari Mengajar</label>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari)
                    <label
                        class="inline-flex items-center gap-1 px-2 py-1 bg-slate-50 border border-slate-200 rounded cursor-pointer hover:bg-indigo-50 transition">
                        <input type="checkbox" name="hari_mengajar[]" value="{{ $hari }}"
                            class="rounded text-indigo-600 text-[10px]">
                        <span class="text-[10px] font-bold text-slate-600">{{ $hari }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Sifat Hari</label>
                <select name="jenis_hari"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 outline-none text-xs">
                    <option value="soft">Fleksibel</option>
                    <option value="hard">Mutlak</option>
                </select>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold py-2.5 rounded-lg text-[10px] uppercase mt-2">Simpan</button>
        </form>
    </div>
</div>

{{-- Modal Edit --}}
@foreach($gurus as $g)
<div id="edit{{ $g->id }}"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[9999] hidden items-center justify-center p-2">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-amber-100 bg-amber-50 flex justify-between items-center">
            <h3 class="font-bold text-amber-800 text-sm flex items-center gap-1.5"><span
                    class="w-1 h-4 bg-amber-500 rounded-full"></span> Ubah Guru</h3>
            <button type="button" onclick="closeModal('edit{{ $g->id }}')"
                class="text-amber-400 hover:text-amber-600 text-lg leading-none">&times;</button>
        </div>
        <form action="{{ route('guru.update', $g->id) }}" method="POST" class="p-4 space-y-3">
            @csrf @method('PUT')
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label>
                <input type="text" name="nama_guru" value="{{ $g->nama_guru }}"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs" required>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">NIP / Kode</label>
                <input type="text" name="kode_guru" value="{{ $g->kode_guru }}"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono text-xs" required>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Hari Mengajar</label>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $hari)
                    <label
                        class="inline-flex items-center gap-1 px-2 py-1 bg-slate-50 border border-slate-200 rounded cursor-pointer">
                        <input type="checkbox" name="hari_mengajar[]" value="{{ $hari }}"
                            {{ in_array($hari, $g->hari_array ?? []) ? 'checked' : '' }}
                            class="rounded text-amber-500 text-[10px]">
                        <span class="text-[10px] font-bold text-slate-600">{{ $hari }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Sifat Hari</label>
                <select name="jenis_hari" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs">
                    <option value="soft" {{ $g->jenis_hari == 'soft' ? 'selected' : '' }}>Fleksibel</option>
                    <option value="hard" {{ $g->jenis_hari == 'hard' ? 'selected' : '' }}>Mutlak</option>
                </select>
            </div>
            <button type="submit"
                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 rounded-lg text-[10px] uppercase mt-2">Perbarui</button>
        </form>
    </div>
</div>

{{-- Modal Jadwal --}}
<div id="modaljadwal{{ $g->id }}"
    class="fixed inset-0 bg-slate-900/80 z-[9999] hidden items-center justify-center p-2 sm:p-4 transition-opacity">
    <div
        class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[85vh] flex flex-col border border-slate-200 overflow-hidden">
        <div class="px-4 py-2 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
            <div class="flex items-center gap-2">
                <div class="p-1.5 bg-indigo-600 text-white rounded"><svg class="w-4 h-4" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg></div>
                <div>
                    <h3 class="font-bold text-sm text-slate-800 leading-none">{{ $g->nama_guru }}</h3>
                    <p class="text-[9px] text-slate-500">{{ $g->kode_guru }}</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('modaljadwal{{ $g->id }}')"
                class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
        </div>

        <div class="flex flex-col lg:flex-row h-full overflow-hidden">
            <div class="flex-1 flex flex-col h-full border-r border-slate-100 relative min-w-0">
                <div class="p-2 border-b border-slate-100 bg-white shrink-0">
                    <input type="text" id="search-{{ $g->id }}" oninput="searchTable('{{ $g->id }}')"
                        placeholder="Cari..."
                        class="w-full border border-slate-200 rounded pl-2 pr-2 py-1.5 text-xs outline-none">
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">
                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-slate-50 text-slate-500 text-[9px] font-bold uppercase sticky top-0 shadow-sm">
                            <tr>
                                <th class="px-2 py-1.5 text-left w-[40%]">Mapel</th>
                                <th class="px-2 py-1.5 text-left w-[20%]">Kelas</th>
                                <th class="px-2 py-1.5 text-center w-[20%]">Jam</th>
                                <th class="px-2 py-1.5 text-right w-[20%]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-guru-{{ $g->id }}" class="divide-y divide-slate-50 text-[10px]">
                            @foreach($g->jadwals as $jadwal)
                            <tr id="row-jadwal-{{ $jadwal->id }}" class="hover:bg-indigo-50/50 group">
                                <td class="px-2 py-1.5 font-bold text-slate-700 mapel-text">
                                    {{ $jadwal->mapel->nama_mapel ?? '-' }} <div
                                        class="text-[8px] font-normal text-slate-400">
                                        {{ $jadwal->mapel->kode_mapel ?? '' }}</div>
                                </td>
                                <td class="px-2 py-1.5 text-slate-600 kelas-text font-medium">
                                    {{ $jadwal->kelas->nama_kelas ?? '-' }}</td>
                                <td class="px-2 py-1.5 text-center">
                                    <div class="flex flex-col items-center"><span
                                            class="bg-white text-indigo-700 px-1 py-0.5 rounded text-[9px] font-bold border border-indigo-100 jam-text">{{ $jadwal->jumlah_jam }}
                                            JP</span><span
                                            class="text-[8px] text-slate-400 mt-0.5 tipe-text uppercase">{{ $jadwal->tipe_jam }}</span>
                                    </div>
                                </td>
                                <td class="px-2 py-1.5 text-right">
                                    <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100"><button
                                            type="button"
                                            onclick="editJadwalInline('{{ $g->id }}', '{{ $jadwal->id }}', '{{ $jadwal->mapel_id }}', '{{ $jadwal->kelas_id }}', '{{ $jadwal->jumlah_jam }}', '{{ $jadwal->tipe_jam }}', '{{ $jadwal->status ?? 'offline' }}')"
                                            class="p-1 text-indigo-600 hover:bg-indigo-100 rounded"><svg class="w-3 h-3"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                </path>
                                            </svg></button><button type="button"
                                            onclick="hapusJadwal('{{ $jadwal->id }}', this)"
                                            class="p-1 text-red-500 hover:bg-red-50 rounded"><svg class="w-3 h-3"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg></button></div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="w-full lg:w-[280px] bg-slate-50 flex flex-col h-[40vh] lg:h-full">
                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
                    <div id="form-container-{{ $g->id }}" class="bg-white p-3 rounded-lg border border-slate-200">
                        <h4 id="form-title-{{ $g->id }}"
                            class="font-bold text-slate-700 text-[10px] uppercase mb-3 border-b pb-1">Input Jadwal</h4>
                        <form id="form-jadwal-{{ $g->id }}" action="{{ route('guru.simpanJadwal', $g->id) }}"
                            method="POST" onsubmit="handleFormJadwal(event, this, '{{ $g->id }}')">
                            <div id="method-spoof-{{ $g->id }}"></div>
                            <div class="space-y-3 text-[10px]">
                                <div class="relative custom-select-wrapper" id="wrapper-mapel-{{ $g->id }}">
                                    <label class="font-bold text-slate-500 uppercase block mb-0.5">Mapel</label>
                                    <input type="hidden" name="mapel_id" id="real-input-mapel-{{ $g->id }}" required>
                                    <button type="button" onclick="toggleCustomDropdown('mapel', '{{ $g->id }}')"
                                        class="w-full px-2 py-1.5 bg-slate-50 border rounded text-left flex justify-between items-center"><span
                                            id="display-mapel-{{ $g->id }}">Pilih...</span><svg class="w-3 h-3"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 9l-7 7-7-7"></path>
                                        </svg></button>
                                    <div id="dropdown-mapel-{{ $g->id }}"
                                        class="hidden absolute z-50 w-full bg-white border rounded shadow-md mt-1 max-h-40 overflow-y-auto">
                                        <input type="text" placeholder="Cari..."
                                            onkeyup="filterCustomDropdown('mapel', '{{ $g->id }}', this)"
                                            class="w-full p-1 text-[10px] border-b">
                                        <div id="list-mapel-{{ $g->id }}" class="p-1">@foreach($mapels as $m)<div
                                                class="option-item p-1 hover:bg-indigo-50 cursor-pointer"
                                                data-value="{{ $m->id }}" data-label="{{ $m->nama_mapel }}"
                                                onclick="selectCustomOption('mapel', '{{ $g->id }}', '{{ $m->id }}', '{{ $m->nama_mapel }}')">
                                                {{ $m->nama_mapel }}</div>@endforeach</div>
                                    </div>
                                </div>
                                <div class="relative custom-select-wrapper" id="wrapper-kelas-{{ $g->id }}">
                                    <label class="font-bold text-slate-500 uppercase block mb-0.5">Kelas</label>
                                    <input type="hidden" name="kelas_id" id="real-input-kelas-{{ $g->id }}" required>
                                    <button type="button" onclick="toggleCustomDropdown('kelas', '{{ $g->id }}')"
                                        class="w-full px-2 py-1.5 bg-slate-50 border rounded text-left flex justify-between items-center"><span
                                            id="display-kelas-{{ $g->id }}">Pilih...</span><svg class="w-3 h-3"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 9l-7 7-7-7"></path>
                                        </svg></button>
                                    <div id="dropdown-kelas-{{ $g->id }}"
                                        class="hidden absolute z-50 w-full bg-white border rounded shadow-md mt-1 max-h-40 overflow-y-auto">
                                        <input type="text" placeholder="Cari..."
                                            onkeyup="filterCustomDropdown('kelas', '{{ $g->id }}', this)"
                                            class="w-full p-1 text-[10px] border-b">
                                        <div id="list-kelas-{{ $g->id }}" class="p-1 grid grid-cols-2 gap-1">
                                            @foreach($kelases as $k)<div
                                                class="option-item p-1 hover:bg-indigo-50 cursor-pointer text-center border rounded"
                                                data-value="{{ $k->id }}" data-label="{{ $k->nama_kelas }}"
                                                onclick="selectCustomOption('kelas', '{{ $g->id }}', '{{ $k->id }}', '{{ $k->nama_kelas }}')">
                                                {{ $k->nama_kelas }}</div>@endforeach</div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div><label class="font-bold text-slate-500 block mb-0.5">Jam</label><input
                                            type="number" name="jumlah_jam" id="input-jam-{{ $g->id }}"
                                            class="w-full px-2 py-1 bg-slate-50 border rounded text-center" min="1"
                                            max="10" required></div>
                                    <div><label class="font-bold text-slate-500 block mb-0.5">Tipe</label><select
                                            name="tipe_jam" id="select-tipe-{{ $g->id }}"
                                            class="w-full px-1 py-1 bg-slate-50 border rounded">
                                            <option value="single">Satu(1x)</option>
                                            <option value="double">Dua(2x)</option>
                                            <option value="triple">Tiga(3x)</option>
                                        </select></div>
                                </div>
                                <div><label class="font-bold text-slate-500 block mb-0.5">Pelaksanaan</label><select
                                        name="status" id="select-status-{{ $g->id }}"
                                        class="w-full px-2 py-1.5 bg-slate-50 border rounded">
                                        <option value="offline">LURING (Jadwal Utama)</option>
                                        <option value="online">DARING</option>
                                    </select></div>
                                <button type="submit" id="btn-submit-{{ $g->id }}"
                                    class="w-full py-2 bg-slate-900 text-white rounded font-bold uppercase mt-2">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach
@endpush

@push('scripts')
<script>
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const CSRF_TOKEN = csrfMeta ? csrfMeta.content : '';

function searchMainTable() {
    const input = document.getElementById('search-guru-main').value.toLowerCase();
    const rows = document.querySelectorAll('#tbody-guru-main tr[data-filter]');
    rows.forEach(row => {
        row.style.display = row.getAttribute('data-filter').includes(input) ? "" : "none";
    });
}

function openModal(id) {
    document.getElementById(id)?.classList.remove('hidden');
    document.getElementById(id)?.classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id)?.classList.add('hidden');
    document.getElementById(id)?.classList.remove('flex');
    if (id.includes('modaljadwal')) resetFormJadwal(id.replace('modaljadwal', ''));
}
window.onclick = e => {
    if (e.target.classList.contains('fixed')) closeModal(e.target.id);
}

function searchTable(id) {
    const filter = document.getElementById('search-' + id).value.toLowerCase();
    const rows = document.getElementById('tbody-guru-' + id).getElementsByTagName('tr');
    for (let row of rows) {
        if (!row.classList.contains('empty-row')) row.style.display = row.innerText.toLowerCase().includes(filter) ?
            "" : "none";
    }
}

function toggleCustomDropdown(type, id) {
    const wrapper = document.getElementById(`wrapper-${type}-${id}`),
        dropdown = document.getElementById(`dropdown-${type}-${id}`);
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
    const filter = input.value.toLowerCase(),
        items = document.getElementById(`list-${type}-${id}`).children;
    for (let item of items) item.style.display = (item.getAttribute('data-label') || '').toLowerCase().includes(
        filter) ? "" : "none";
}

function selectCustomOption(type, id, value, label) {
    document.getElementById(`real-input-${type}-${id}`).value = value;
    document.getElementById(`display-${type}-${id}`).innerText = label;
    document.getElementById(`dropdown-${type}-${id}`).classList.add('hidden');
    document.getElementById(`wrapper-${type}-${id}`).style.zIndex = "0";
}

function setCustomDropdownValue(type, id, value) {
    const option = document.querySelector(`#list-${type}-${id} .option-item[data-value="${value}"]`);
    if (option) selectCustomOption(type, id, value, option.getAttribute('data-label'));
}

function resetCustomDropdown(type, id) {
    document.getElementById(`real-input-${type}-${id}`).value = '';
    document.getElementById(`display-${type}-${id}`).innerText = 'Pilih...';
}

function editJadwalInline(guruId, jadwalId, mapelId, kelasId, jam, tipe, status) {
    document.getElementById(`form-title-${guruId}`).innerText = "Ubah Distribusi";
    document.getElementById(`btn-submit-${guruId}`).innerText = "Perbarui";
    document.getElementById(`btn-submit-${guruId}`).classList.replace('bg-slate-900', 'bg-amber-500');
    setCustomDropdownValue('mapel', guruId, mapelId);
    setCustomDropdownValue('kelas', guruId, kelasId);
    document.getElementById(`input-jam-${guruId}`).value = jam;
    document.getElementById(`select-tipe-${guruId}`).value = tipe;
    document.getElementById(`select-status-${guruId}`).value = status;
    const form = document.getElementById(`form-jadwal-${guruId}`);
    form.action = `/guru/jadwal/${jadwalId}`;
    form.dataset.mode = 'edit';
    document.getElementById(`method-spoof-${guruId}`).innerHTML = `<input type="hidden" name="_method" value="PUT">`;
}

function resetFormJadwal(guruId) {
    document.getElementById(`form-title-${guruId}`).innerText = "Input Jadwal";
    const btn = document.getElementById(`btn-submit-${guruId}`);
    btn.innerText = "Simpan";
    btn.classList.replace('bg-amber-500', 'bg-slate-900');
    const form = document.getElementById(`form-jadwal-${guruId}`);
    form.reset();
    resetCustomDropdown('mapel', guruId);
    resetCustomDropdown('kelas', guruId);
    form.action = `/guru/${guruId}/jadwal`;
    delete form.dataset.mode;
    document.getElementById(`method-spoof-${guruId}`).innerHTML = '';
}

async function handleFormJadwal(e, form, guruId) {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    const oldText = btn.innerText;
    btn.innerText = "...";
    btn.disabled = true;
    try {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: new FormData(form)
        });
        const json = await res.json();
        if (res.ok && json.success) {
            updateTableUI(guruId, json.jadwal, form.dataset.mode === 'edit');
            resetFormJadwal(guruId);
        } else alert(json.message || "Gagal.");
    } catch (err) {
        alert("Error sistem.");
    } finally {
        btn.disabled = false;
        btn.innerText = oldText;
    }
}

function updateTableUI(guruId, jadwal, isEdit) {
    location.reload();
}
async function hapusJadwal(id, btn) {
    if (!confirm("Hapus?")) return;
    try {
        const res = await fetch(`/guru/jadwal/${id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                _method: 'DELETE'
            })
        });
        if (res.ok) btn.closest('tr').remove();
    } catch (e) {}
}
</script>
@endpush