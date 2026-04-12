@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-blue-50/50 to-white"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-300/10 rounded-full blur-3xl opacity-70"></div>
</div>

{{-- CONTAINER UTAMA --}}
<div x-data="{ 
        activeTab: localStorage.getItem('tabMasterWaktu') || 'normal',
        setTab(tab) {
            this.activeTab = tab;
            localStorage.setItem('tabMasterWaktu', tab);
        }
    }" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-[calc(100vh-6rem)] pb-4 pt-6 flex flex-col">

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800 shrink-0">
        <span class="font-semibold text-sm">✅ {{ session('success') }}</span>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700">&times;</button>
    </div>
    @endif

    {{-- UNIFIED CARD --}}
    <div
        class="bg-white rounded-[2rem] border border-slate-100 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.08)] flex flex-col flex-1 overflow-hidden">

        {{-- 1. HEADER SECTION & TABS --}}
        <div class="px-8 pt-8 pb-0 bg-white shrink-0 z-20 border-b border-slate-200">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                <div class="flex gap-3 items-start">
                    <div class="w-2.5 h-8 bg-indigo-600 rounded-full mt-0.5"></div>
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Master Waktu</h1>
                        <p class="text-slate-500 text-sm mt-1 font-medium">Kelola urutan jam pelajaran per hari.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div
                        class="hidden md:flex items-center px-5 py-2.5 bg-white border border-slate-200 rounded-full shadow-sm">
                        <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">
                            Total Jam: <span
                                class="text-indigo-600 text-sm ml-1 font-extrabold">{{ $waktus->count() }}</span>
                        </span>
                    </div>
                    <button onclick="openModal('modaltambah')"
                        class="px-6 py-2.5 font-bold text-white transition-all duration-300 bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-md hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        <span class="text-sm uppercase tracking-wide">Tambah Jam</span>
                    </button>
                </div>
            </div>

            <div class="flex gap-8">
                <button @click="setTab('senin')"
                    :class="activeTab === 'senin' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                    class="pb-4 border-b-4 font-extrabold text-sm uppercase tracking-wider transition-colors">Khusus
                    Senin</button>
                <button @click="setTab('normal')"
                    :class="activeTab === 'normal' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                    class="pb-4 border-b-4 font-extrabold text-sm uppercase tracking-wider transition-colors">Normal
                    (Selasa - Kamis)</button>
                <button @click="setTab('jumat')"
                    :class="activeTab === 'jumat' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                    class="pb-4 border-b-4 font-extrabold text-sm uppercase tracking-wider transition-colors">Khusus
                    Jumat</button>
            </div>
        </div>

        {{-- 2. TABEL AREA --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white px-2 py-2">

            {{-- ==================== TABEL SENIN ==================== --}}
            <table x-show="activeTab === 'senin'" x-cloak
                class="w-full text-left border-separate border-spacing-0 min-w-[700px]">
                <thead>
                    <tr>
                        <th
                            class="sticky top-0 z-20 bg-cyan-50 px-4 py-3 text-[11px] font-bold text-cyan-700 uppercase text-center w-[15%] border-b-2 border-cyan-100">
                            Jam Ke</th>
                        <th
                            class="sticky top-0 z-20 bg-cyan-50 px-4 py-3 text-[11px] font-bold text-cyan-700 uppercase text-center w-[30%] border-b-2 border-cyan-100">
                            Waktu Senin</th>
                        <th
                            class="sticky top-0 z-20 bg-cyan-50 px-4 py-3 text-[11px] font-bold text-cyan-700 uppercase text-center w-[25%] border-b-2 border-cyan-100">
                            Kegiatan</th>
                        <th
                            class="sticky top-0 z-20 bg-cyan-50 px-4 py-3 text-[11px] font-bold text-cyan-700 uppercase text-right w-[30%] border-b-2 border-cyan-100">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($waktus as $w)
                    <tr class="hover:bg-slate-50 transition-colors duration-200">
                        <td class="px-4 py-2 text-center h-[50px]">
                            <span class="font-extrabold text-slate-700 text-sm bg-slate-100 px-3 py-1 rounded-lg">
                                {{ $w->jam_ke ?? '-' }}
                            </span>
                        </td>

                        <td class="px-4 py-2 text-center h-[50px]">
                            @if($w->tipe_senin == 'Tidak Ada')
                            <span
                                class="text-[10px] text-red-500 font-bold bg-red-50 px-2 py-1 rounded border border-red-200">❌
                                PULANG</span>
                            @elseif($w->mulai_senin)
                            <div class="font-bold text-slate-700 text-sm font-mono">
                                {{ \Carbon\Carbon::parse($w->mulai_senin)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->selesai_senin)->format('H:i') }}</div>
                            @else
                            <span
                                class="text-[10px] text-slate-400 italic font-mono">{{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }}
                                - {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-2 text-center h-[50px]">
                            @php $tipeS = $w->mulai_senin ? $w->tipe_senin : $w->tipe; @endphp
                            @if($tipeS == 'Tidak Ada')
                            <span class="text-slate-300 font-bold">-</span>
                            @else
                            <span
                                class="inline-block px-2 py-1 rounded-md {{ $tipeS == 'Belajar' ? 'bg-cyan-50 text-cyan-600' : 'bg-amber-50 text-amber-600' }} text-[10px] font-bold uppercase tracking-wider">{{ $tipeS }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-2 text-right h-[50px]">
                            <div class="flex items-center justify-end gap-1.5">
                                @if($w->tipe_senin != 'Tidak Ada')
                                <button
                                    onclick="openEditSenin({{ $w->id }}, '{{ $w->jam_ke }}', '{{ substr($w->waktu_mulai, 0, 5) }}', '{{ substr($w->waktu_selesai, 0, 5) }}', '{{ $w->tipe }}', '{{ $w->mulai_senin ? substr($w->mulai_senin, 0, 5) : '' }}', '{{ $w->selesai_senin ? substr($w->selesai_senin, 0, 5) : '' }}', '{{ $w->tipe_senin }}', '{{ $w->mulai_jumat ? substr($w->mulai_jumat, 0, 5) : '' }}', '{{ $w->selesai_jumat ? substr($w->selesai_jumat, 0, 5) : '' }}', '{{ $w->tipe_jumat }}')"
                                    class="px-3 py-1.5 text-cyan-600 border border-cyan-200 font-bold text-[10px] hover:bg-cyan-50 rounded-lg transition">EDIT
                                    WAKTU</button>

                                {{-- FORM NONAKTIFKAN SENIN --}}
                                <form action="{{ route('master-waktu.update', $w->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Nonaktifkan jam ke-{{ $w->jam_ke ?? '-' }} khusus hari Senin?');">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="jam_ke" value="{{ $w->jam_ke }}">
                                    <input type="hidden" name="waktu_mulai"
                                        value="{{ substr($w->waktu_mulai, 0, 5) }}"><input type="hidden"
                                        name="waktu_selesai" value="{{ substr($w->waktu_selesai, 0, 5) }}"><input
                                        type="hidden" name="tipe" value="{{ $w->tipe }}">
                                    <input type="hidden" name="mulai_jumat"
                                        value="{{ $w->mulai_jumat ? substr($w->mulai_jumat, 0, 5) : '' }}"><input
                                        type="hidden" name="selesai_jumat"
                                        value="{{ $w->selesai_jumat ? substr($w->selesai_jumat, 0, 5) : '' }}"><input
                                        type="hidden" name="tipe_jumat" value="{{ $w->tipe_jumat }}">
                                    {{-- Timpa Senin jadi 00:00 dan Tidak Ada --}}
                                    <input type="hidden" name="mulai_senin" value="00:00"><input type="hidden"
                                        name="selesai_senin" value="00:00"><input type="hidden" name="tipe_senin"
                                        value="Tidak Ada">
                                    <button type="submit"
                                        class="px-3 py-1.5 text-red-500 border border-red-200 font-bold text-[10px] hover:bg-red-50 rounded-lg transition">NONAKTIFKAN</button>
                                </form>
                                @else
                                {{-- FORM AKTIFKAN KEMBALI SENIN --}}
                                <form action="{{ route('master-waktu.update', $w->id) }}" method="POST" class="inline">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="jam_ke" value="{{ $w->jam_ke }}">
                                    <input type="hidden" name="waktu_mulai"
                                        value="{{ substr($w->waktu_mulai, 0, 5) }}"><input type="hidden"
                                        name="waktu_selesai" value="{{ substr($w->waktu_selesai, 0, 5) }}"><input
                                        type="hidden" name="tipe" value="{{ $w->tipe }}">
                                    <input type="hidden" name="mulai_jumat"
                                        value="{{ $w->mulai_jumat ? substr($w->mulai_jumat, 0, 5) : '' }}"><input
                                        type="hidden" name="selesai_jumat"
                                        value="{{ $w->selesai_jumat ? substr($w->selesai_jumat, 0, 5) : '' }}"><input
                                        type="hidden" name="tipe_jumat" value="{{ $w->tipe_jumat }}">
                                    {{-- Kembalikan Senin ke Null biar ikut normal --}}
                                    <input type="hidden" name="mulai_senin" value=""><input type="hidden"
                                        name="selesai_senin" value=""><input type="hidden" name="tipe_senin" value="">
                                    <button type="submit"
                                        class="px-3 py-1.5 text-emerald-600 bg-emerald-50 border border-emerald-200 font-bold text-[10px] hover:bg-emerald-100 rounded-lg transition">AKTIFKAN
                                        LAGI</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- ==================== TABEL NORMAL ==================== --}}
            <table x-show="activeTab === 'normal'"
                class="w-full text-left border-separate border-spacing-0 min-w-[700px]">
                <thead>
                    <tr>
                        <th
                            class="sticky top-0 z-20 bg-indigo-50 px-4 py-3 text-[11px] font-bold text-indigo-700 uppercase text-center w-[15%] border-b-2 border-indigo-100">
                            Jam Ke</th>
                        <th
                            class="sticky top-0 z-20 bg-indigo-50 px-4 py-3 text-[11px] font-bold text-indigo-700 uppercase text-center w-[30%] border-b-2 border-indigo-100">
                            Waktu Normal</th>
                        <th
                            class="sticky top-0 z-20 bg-indigo-50 px-4 py-3 text-[11px] font-bold text-indigo-700 uppercase text-center w-[25%] border-b-2 border-indigo-100">
                            Kegiatan</th>
                        <th
                            class="sticky top-0 z-20 bg-indigo-50 px-4 py-3 text-[11px] font-bold text-indigo-700 uppercase text-right w-[30%] border-b-2 border-indigo-100">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($waktus as $w)
                    <tr class="hover:bg-slate-50 transition-colors duration-200">
                        <td class="px-4 py-2 text-center h-[50px]"><span
                                class="font-extrabold text-slate-700 text-sm bg-slate-100 px-3 py-1 rounded-lg">
                                {{ $w->jam_ke ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center h-[50px]">
                            <div class="font-bold text-slate-700 text-sm font-mono">
                                {{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }}</div>
                        </td>
                        <td class="px-4 py-2 text-center h-[50px]">
                            <span
                                class="inline-block px-2 py-1 rounded-md {{ $w->tipe == 'Belajar' ? 'bg-indigo-50 text-indigo-600' : 'bg-amber-50 text-amber-600' }} text-[10px] font-bold uppercase tracking-wider">{{ $w->tipe }}</span>
                        </td>
                        <td class="px-4 py-2 text-right h-[50px]">
                            <div class="flex items-center justify-end gap-1.5">
                                <button
                                    onclick="openEditNormal({{ $w->id }}, '{{ $w->jam_ke }}', '{{ substr($w->waktu_mulai, 0, 5) }}', '{{ substr($w->waktu_selesai, 0, 5) }}', '{{ $w->tipe }}', '{{ $w->mulai_senin ? substr($w->mulai_senin, 0, 5) : '' }}', '{{ $w->selesai_senin ? substr($w->selesai_senin, 0, 5) : '' }}', '{{ $w->tipe_senin }}', '{{ $w->mulai_jumat ? substr($w->mulai_jumat, 0, 5) : '' }}', '{{ $w->selesai_jumat ? substr($w->selesai_jumat, 0, 5) : '' }}', '{{ $w->tipe_jumat }}')"
                                    class="px-3 py-1.5 text-indigo-600 border border-indigo-200 font-bold text-[10px] hover:bg-indigo-50 rounded-lg transition">EDIT
                                    WAKTU</button>

                                {{-- INI HAPUS PERMANEN DARI DATABASE --}}
                                <form action="{{ route('master-waktu.destroy', $w->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('BAHAYA: Menghapus akan membuang baris ini di SEMUA HARI secara permanen. Lanjutkan?');">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="px-3 py-1.5 text-white bg-red-500 font-bold text-[10px] hover:bg-red-600 rounded-lg">HAPUS</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- ==================== TABEL JUMAT ==================== --}}
            <table x-show="activeTab === 'jumat'" x-cloak
                class="w-full text-left border-separate border-spacing-0 min-w-[700px]">
                <thead>
                    <tr>
                        <th
                            class="sticky top-0 z-20 bg-emerald-50 px-4 py-3 text-[11px] font-bold text-emerald-700 uppercase text-center w-[15%] border-b-2 border-emerald-100">
                            Jam Ke</th>
                        <th
                            class="sticky top-0 z-20 bg-emerald-50 px-4 py-3 text-[11px] font-bold text-emerald-700 uppercase text-center w-[30%] border-b-2 border-emerald-100">
                            Waktu Jumat</th>
                        <th
                            class="sticky top-0 z-20 bg-emerald-50 px-4 py-3 text-[11px] font-bold text-emerald-700 uppercase text-center w-[25%] border-b-2 border-emerald-100">
                            Kegiatan</th>
                        <th
                            class="sticky top-0 z-20 bg-emerald-50 px-4 py-3 text-[11px] font-bold text-emerald-700 uppercase text-right w-[30%] border-b-2 border-emerald-100">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($waktus as $w)
                    <tr class="hover:bg-slate-50 transition-colors duration-200">
                        <td class="px-4 py-2 text-center h-[50px]"><span
                                class="font-extrabold text-slate-700 text-sm bg-slate-100 px-3 py-1 rounded-lg">
                                {{ $w->jam_ke ?? '-' }}
                            </span>
                        </td>

                        <td class="px-4 py-2 text-center h-[50px]">
                            @if($w->tipe_jumat == 'Tidak Ada')
                            <span
                                class="text-[10px] text-red-500 font-bold bg-red-50 px-2 py-1 rounded border border-red-200">❌
                                PULANG</span>
                            @elseif($w->mulai_jumat)
                            <div class="font-bold text-slate-700 text-sm font-mono">
                                {{ \Carbon\Carbon::parse($w->mulai_jumat)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->selesai_jumat)->format('H:i') }}</div>
                            @else
                            <span
                                class="text-[10px] text-slate-400 italic font-mono">{{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }}
                                - {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-2 text-center h-[50px]">
                            @php $tipeJ = $w->mulai_jumat ? $w->tipe_jumat : $w->tipe; @endphp
                            @if($tipeJ == 'Tidak Ada')
                            <span class="text-slate-300 font-bold">-</span>
                            @else
                            <span
                                class="inline-block px-2 py-1 rounded-md {{ $tipeJ == 'Belajar' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }} text-[10px] font-bold uppercase tracking-wider">{{ $tipeJ }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-2 text-right h-[50px]">
                            <div class="flex items-center justify-end gap-1.5">
                                @if($w->tipe_jumat != 'Tidak Ada')
                                <button
                                    onclick="openEditJumat({{ $w->id }}, '{{ $w->jam_ke }}', '{{ substr($w->waktu_mulai, 0, 5) }}', '{{ substr($w->waktu_selesai, 0, 5) }}', '{{ $w->tipe }}', '{{ $w->mulai_senin ? substr($w->mulai_senin, 0, 5) : '' }}', '{{ $w->selesai_senin ? substr($w->selesai_senin, 0, 5) : '' }}', '{{ $w->tipe_senin }}', '{{ $w->mulai_jumat ? substr($w->mulai_jumat, 0, 5) : '' }}', '{{ $w->selesai_jumat ? substr($w->selesai_jumat, 0, 5) : '' }}', '{{ $w->tipe_jumat }}')"
                                    class="px-3 py-1.5 text-emerald-600 border border-emerald-200 font-bold text-[10px] hover:bg-emerald-50 rounded-lg transition">EDIT
                                    JUMAT</button>

                                {{-- FORM NONAKTIFKAN JUMAT --}}
                                <form action="{{ route('master-waktu.update', $w->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Nonaktifkan jam ke-{{ $w->jam_ke ?? '-' }} khusus hari Jumat?');">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="jam_ke" value="{{ $w->jam_ke }}">
                                    <input type="hidden" name="waktu_mulai"
                                        value="{{ substr($w->waktu_mulai, 0, 5) }}"><input type="hidden"
                                        name="waktu_selesai" value="{{ substr($w->waktu_selesai, 0, 5) }}"><input
                                        type="hidden" name="tipe" value="{{ $w->tipe }}">
                                    <input type="hidden" name="mulai_senin"
                                        value="{{ $w->mulai_senin ? substr($w->mulai_senin, 0, 5) : '' }}"><input
                                        type="hidden" name="selesai_senin"
                                        value="{{ $w->selesai_senin ? substr($w->selesai_senin, 0, 5) : '' }}"><input
                                        type="hidden" name="tipe_senin" value="{{ $w->tipe_senin }}">
                                    {{-- Timpa Jumat jadi 00:00 dan Tidak Ada --}}
                                    <input type="hidden" name="mulai_jumat" value="00:00"><input type="hidden"
                                        name="selesai_jumat" value="00:00"><input type="hidden" name="tipe_jumat"
                                        value="Tidak Ada">
                                    <button type="submit"
                                        class="px-3 py-1.5 text-red-500 border border-red-200 font-bold text-[10px] hover:bg-red-50 rounded-lg transition">NONAKTIFKAN</button>
                                </form>
                                @else
                                {{-- FORM AKTIFKAN KEMBALI JUMAT --}}
                                <form action="{{ route('master-waktu.update', $w->id) }}" method="POST" class="inline">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="jam_ke" value="{{ $w->jam_ke }}">
                                    <input type="hidden" name="waktu_mulai"
                                        value="{{ substr($w->waktu_mulai, 0, 5) }}"><input type="hidden"
                                        name="waktu_selesai" value="{{ substr($w->waktu_selesai, 0, 5) }}"><input
                                        type="hidden" name="tipe" value="{{ $w->tipe }}">
                                    <input type="hidden" name="mulai_senin"
                                        value="{{ $w->mulai_senin ? substr($w->mulai_senin, 0, 5) : '' }}"><input
                                        type="hidden" name="selesai_senin"
                                        value="{{ $w->selesai_senin ? substr($w->selesai_senin, 0, 5) : '' }}"><input
                                        type="hidden" name="tipe_senin" value="{{ $w->tipe_senin }}">
                                    {{-- Kembalikan Jumat ke Null biar ikut normal --}}
                                    <input type="hidden" name="mulai_jumat" value=""><input type="hidden"
                                        name="selesai_jumat" value=""><input type="hidden" name="tipe_jumat" value="">
                                    <button type="submit"
                                        class="px-3 py-1.5 text-emerald-600 bg-emerald-50 border border-emerald-200 font-bold text-[10px] hover:bg-emerald-100 rounded-lg transition">AKTIFKAN
                                        LAGI</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- DATALIST UNTUK SUGGESTI KEGIATAN --}}
<datalist id="kegiatan-list">
    <option value="Belajar"></option>
    <option value="Istirahat"></option>
    <option value="Upacara"></option>
    <option value="Sholat Dhuha"></option>
    <option value="Senam"></option>
    <option value="Jumat Bersih"></option>
    <option value="Pramuka"></option>
    <option value="Tidak Ada"></option>
</datalist>

{{-- ========================================== --}}
{{-- MODAL TAMBAH SLOT --}}
{{-- ========================================== --}}
<div id="modaltambah"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden border border-white/20">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">Tambah Slot Jam</h3>
            <button type="button" onclick="closeModal('modaltambah')"
                class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('master-waktu.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Jam Ke</label>
                <input type="number" name="jam_ke"
                    class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Waktu Mulai</label>
                    <input type="time" name="waktu_mulai"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Waktu Selesai</label>
                    <input type="time" name="waktu_selesai"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                        required>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Kegiatan (Pilih/Ketik)</label>
                <input type="text" name="tipe" list="kegiatan-list"
                    class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                    placeholder="Cth: Belajar" required>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold mt-4 py-3 rounded-xl transition uppercase tracking-widest text-xs">SIMPAN
                DATA</button>
        </form>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL EDIT KHUSUS NORMAL --}}
{{-- ========================================== --}}
<div id="modaledit_normal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="p-5 border-b flex justify-between items-center bg-indigo-50">
            <h3 class="font-bold text-indigo-800">Edit Waktu Normal (Sel-Kam)</h3>
            <button type="button" onclick="closeModal('modaledit_normal')"
                class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
        </div>
        <form id="form-edit-normal" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            <input type="hidden" id="norm_mulai_senin" name="mulai_senin">
            <input type="hidden" id="norm_selesai_senin" name="selesai_senin">
            <input type="hidden" id="norm_tipe_senin" name="tipe_senin">
            <input type="hidden" id="norm_mulai_jumat" name="mulai_jumat">
            <input type="hidden" id="norm_selesai_jumat" name="selesai_jumat">
            <input type="hidden" id="norm_tipe_jumat" name="tipe_jumat">

            {{-- PERUBAHAN: Input jam_ke bisa diketik/diedit sekarang --}}
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Jam Ke</label>
                <input type="number" id="norm_jam_ke" name="jam_ke"
                    class="w-full border border-slate-300 focus:ring-2 focus:ring-indigo-500 bg-white rounded-lg p-2 text-slate-700 font-bold outline-none transition-colors">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Mulai</label>
                    <input type="time" id="norm_waktu_mulai" name="waktu_mulai"
                        class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Selesai</label>
                    <input type="time" id="norm_waktu_selesai" name="waktu_selesai"
                        class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                        required>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Kegiatan (Pilih/Ketik)</label>
                <input type="text" id="norm_tipe" name="tipe" list="kegiatan-list"
                    class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                    required>
            </div>
            <button type="submit"
                class="w-full bg-indigo-600 text-white font-bold py-3 mt-4 rounded-xl hover:bg-indigo-700 transition">SIMPAN
                PERUBAHAN</button>
        </form>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL EDIT KHUSUS SENIN --}}
{{-- ========================================== --}}
<div id="modaledit_senin"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="p-5 border-b flex justify-between items-center bg-cyan-50">
            <h3 class="font-bold text-cyan-800">Edit Waktu Khusus Senin</h3>
            <button type="button" onclick="closeModal('modaledit_senin')"
                class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
        </div>
        <form id="form-edit-senin" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            <input type="hidden" id="senin_waktu_mulai" name="waktu_mulai">
            <input type="hidden" id="senin_waktu_selesai" name="waktu_selesai">
            <input type="hidden" id="senin_tipe" name="tipe">
            <input type="hidden" id="senin_mulai_jumat" name="mulai_jumat">
            <input type="hidden" id="senin_selesai_jumat" name="selesai_jumat">
            <input type="hidden" id="senin_tipe_jumat" name="tipe_jumat">

            {{-- PERUBAHAN: Input jam_ke bisa diketik/diedit sekarang --}}
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Jam Ke</label>
                <input type="number" id="senin_jam_ke" name="jam_ke"
                    class="w-full border border-slate-300 focus:ring-2 focus:ring-cyan-500 bg-white rounded-lg p-2 text-slate-700 font-bold outline-none transition-colors">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Mulai <span
                            class="font-normal normal-case">(Kosongkan jika = Normal)</span></label>
                    <input type="time" id="senin_mulai_senin" name="mulai_senin"
                        class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-cyan-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Selesai</label>
                    <input type="time" id="senin_selesai_senin" name="selesai_senin"
                        class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-cyan-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Kegiatan (Pilih/Ketik)</label>
                <input type="text" id="senin_tipe_senin" name="tipe_senin" list="kegiatan-list"
                    class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-cyan-500 outline-none">
            </div>
            <button type="submit"
                class="w-full bg-cyan-600 text-white font-bold py-3 mt-4 rounded-xl hover:bg-cyan-700 transition">SIMPAN
                PERUBAHAN</button>
        </form>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL EDIT KHUSUS JUMAT --}}
{{-- ========================================== --}}
<div id="modaledit_jumat"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="p-5 border-b flex justify-between items-center bg-emerald-50">
            <h3 class="font-bold text-emerald-800">Edit Waktu Khusus Jumat</h3>
            <button type="button" onclick="closeModal('modaledit_jumat')"
                class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
        </div>
        <form id="form-edit-jumat" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            <input type="hidden" id="jumat_waktu_mulai" name="waktu_mulai">
            <input type="hidden" id="jumat_waktu_selesai" name="waktu_selesai">
            <input type="hidden" id="jumat_tipe" name="tipe">
            <input type="hidden" id="jumat_mulai_senin" name="mulai_senin">
            <input type="hidden" id="jumat_selesai_senin" name="selesai_senin">
            <input type="hidden" id="jumat_tipe_senin" name="tipe_senin">

            {{-- PERUBAHAN: Input jam_ke bisa diketik/diedit sekarang --}}
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Jam Ke</label>
                <input type="number" id="jumat_jam_ke" name="jam_ke"
                    class="w-full border border-slate-300 focus:ring-2 focus:ring-emerald-500 bg-white rounded-lg p-2 text-slate-700 font-bold outline-none transition-colors">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Mulai <span
                            class="font-normal normal-case">(Kosongkan jika = Normal)</span></label>
                    <input type="time" id="jumat_mulai_jumat" name="mulai_jumat"
                        class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Selesai</label>
                    <input type="time" id="jumat_selesai_jumat" name="selesai_jumat"
                        class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Kegiatan (Pilih/Ketik)</label>
                <input type="text" id="jumat_tipe_jumat" name="tipe_jumat" list="kegiatan-list"
                    class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-emerald-500 outline-none">
            </div>
            <button type="submit"
                class="w-full bg-emerald-600 text-white font-bold py-3 mt-4 rounded-xl hover:bg-emerald-700 transition">SIMPAN
                PERUBAHAN</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openModal(id) {
    document.getElementById(id).classList.replace('hidden', 'flex');
}

function closeModal(id) {
    document.getElementById(id).classList.replace('flex', 'hidden');
}

// 1. FUNGSI EDIT NORMAL
function openEditNormal(id, jam, mN, sN, tN, mS, sS, tS, mJ, sJ, tJ) {
    document.getElementById('form-edit-normal').action = `/master-waktu/${id}`;
    document.getElementById('norm_jam_ke').value = jam !== 'null' && jam !== '' ? jam : '';

    // Yg Diedit
    document.getElementById('norm_waktu_mulai').value = mN;
    document.getElementById('norm_waktu_selesai').value = sN;
    document.getElementById('norm_tipe').value = tN;

    // Yg Disembunyikan (Biar data nggak hilang)
    document.getElementById('norm_mulai_senin').value = mS;
    document.getElementById('norm_selesai_senin').value = sS;
    document.getElementById('norm_tipe_senin').value = tS || 'Belajar';
    document.getElementById('norm_mulai_jumat').value = mJ;
    document.getElementById('norm_selesai_jumat').value = sJ;
    document.getElementById('norm_tipe_jumat').value = tJ || 'Belajar';

    openModal('modaledit_normal');
}

// 2. FUNGSI EDIT SENIN
function openEditSenin(id, jam, mN, sN, tN, mS, sS, tS, mJ, sJ, tJ) {
    document.getElementById('form-edit-senin').action = `/master-waktu/${id}`;
    document.getElementById('senin_jam_ke').value = jam !== 'null' && jam !== '' ? jam : '';

    // Yg Diedit
    document.getElementById('senin_mulai_senin').value = mS;
    document.getElementById('senin_selesai_senin').value = sS;
    document.getElementById('senin_tipe_senin').value = tS || 'Belajar';

    // Yg Disembunyikan
    document.getElementById('senin_waktu_mulai').value = mN;
    document.getElementById('senin_waktu_selesai').value = sN;
    document.getElementById('senin_tipe').value = tN;
    document.getElementById('senin_mulai_jumat').value = mJ;
    document.getElementById('senin_selesai_jumat').value = sJ;
    document.getElementById('senin_tipe_jumat').value = tJ || 'Belajar';

    openModal('modaledit_senin');
}

// 3. FUNGSI EDIT JUMAT
function openEditJumat(id, jam, mN, sN, tN, mS, sS, tS, mJ, sJ, tJ) {
    document.getElementById('form-edit-jumat').action = `/master-waktu/${id}`;
    document.getElementById('jumat_jam_ke').value = jam !== 'null' && jam !== '' ? jam : '';

    // Yg Diedit
    document.getElementById('jumat_mulai_jumat').value = mJ;
    document.getElementById('jumat_selesai_jumat').value = sJ;
    document.getElementById('jumat_tipe_jumat').value = tJ || 'Belajar';

    // Yg Disembunyikan
    document.getElementById('jumat_waktu_mulai').value = mN;
    document.getElementById('jumat_waktu_selesai').value = sN;
    document.getElementById('jumat_tipe').value = tN;
    document.getElementById('jumat_mulai_senin').value = mS;
    document.getElementById('jumat_selesai_senin').value = sS;
    document.getElementById('jumat_tipe_senin').value = tS || 'Belajar';

    openModal('modaledit_jumat');
}

// Tutup pop-up kalau klik area gelap
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.replace('flex', 'hidden');
    }
}
</script>
@endpush