@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-blue-50/50 to-white"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-300/10 rounded-full blur-3xl opacity-70"></div>
    <div class="absolute top-20 left-10 w-72 h-72 bg-cyan-300/10 rounded-full blur-3xl opacity-70"></div>
</div>

{{-- CONTAINER UTAMA (DENGAN ALPINE JS UNTUK SISTEM TAB) --}}
<div x-data="{ activeTab: 'normal' }"
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-[calc(100vh-6rem)] pb-4 pt-6 flex flex-col">

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

        {{-- 1. HEADER SECTION & NAVIGATION TABS --}}
        <div class="px-8 pt-8 pb-0 bg-white shrink-0 z-20 border-b border-slate-200">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                <div class="flex gap-3 items-start">
                    <div class="w-2.5 h-8 bg-indigo-600 rounded-full mt-0.5"></div>
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Master Waktu</h1>
                        <p class="text-slate-500 text-sm mt-1 font-medium">Kelola urutan jam pelajaran sesuai hari.</p>
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
                        <span class="text-sm uppercase tracking-wide">Tambah</span>
                    </button>
                </div>
            </div>

            {{-- TABS MENU (Senin | Selasa-Kamis | Jumat) --}}
            <div class="flex gap-8">
                <button @click="activeTab = 'senin'"
                    :class="activeTab === 'senin' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                    class="pb-4 border-b-4 font-extrabold text-sm uppercase tracking-wider transition-colors">
                    Khusus Senin
                </button>
                <button @click="activeTab = 'normal'"
                    :class="activeTab === 'normal' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                    class="pb-4 border-b-4 font-extrabold text-sm uppercase tracking-wider transition-colors">
                    Normal (Sel - Kam)
                </button>
                <button @click="activeTab = 'jumat'"
                    :class="activeTab === 'jumat' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                    class="pb-4 border-b-4 font-extrabold text-sm uppercase tracking-wider transition-colors">
                    Khusus Jumat
                </button>
            </div>
        </div>

        {{-- 2. TABLE SECTION (Berubah-ubah sesuai Tab) --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white px-2">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase text-center w-[15%]">Jam Ke</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center w-[40%]">Waktu
                            Pelaksanaan</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center w-[25%]">Tipe
                            Kegiatan</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right w-[20%]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/80">
                    @forelse($waktus as $w)
                    <tr class="hover:bg-slate-50 transition-colors duration-200">
                        <td class="px-8 py-5 text-center">
                            <span
                                class="font-extrabold text-slate-700 text-lg bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm">{{ $w->jam_ke }}</span>
                        </td>

                        {{-- ==================== KOLOM WAKTU (DINAMIS SESUAI TAB) ==================== --}}

                        {{-- Tampilan Senin --}}
                        <td x-show="activeTab === 'senin'" class="px-6 py-5 text-center">
                            @if($w->mulai_senin && $w->selesai_senin)
                            <div class="font-bold text-slate-700 text-base">
                                {{ \Carbon\Carbon::parse($w->mulai_senin)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->selesai_senin)->format('H:i') }}</div>
                            @else
                            <div class="font-bold text-slate-400 text-base">
                                {{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }}</div>
                            <span class="text-[10px] text-slate-400 italic bg-slate-100 px-2 py-0.5 rounded">Mengikuti
                                Normal</span>
                            @endif
                        </td>

                        {{-- Tampilan Normal (Sel-Kam) --}}
                        <td x-show="activeTab === 'normal'" class="px-6 py-5 text-center">
                            <div class="font-bold text-slate-700 text-base">
                                {{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }}</div>
                        </td>

                        {{-- Tampilan Jumat --}}
                        <td x-show="activeTab === 'jumat'" class="px-6 py-5 text-center">
                            @if($w->mulai_jumat && $w->selesai_jumat)
                            <div class="font-bold text-slate-700 text-base">
                                {{ \Carbon\Carbon::parse($w->mulai_jumat)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->selesai_jumat)->format('H:i') }}</div>
                            @else
                            <div class="font-bold text-slate-400 text-base">
                                {{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }}</div>
                            <span class="text-[10px] text-slate-400 italic bg-slate-100 px-2 py-0.5 rounded">Mengikuti
                                Normal</span>
                            @endif
                        </td>

                        {{-- ==================== KOLOM TIPE (DINAMIS SESUAI TAB) ==================== --}}

                        {{-- Tipe Senin --}}
                        <td x-show="activeTab === 'senin'" class="px-6 py-5 text-center">
                            @php $tipeSenin = $w->mulai_senin ? $w->tipe_senin : $w->tipe; @endphp
                            <span
                                class="inline-block px-3 py-1.5 rounded-md {{ $tipeSenin == 'Istirahat' ? 'bg-amber-50 text-amber-600' : 'bg-cyan-50 text-cyan-600' }} text-[11px] font-bold uppercase tracking-wider">{{ $tipeSenin }}</span>
                        </td>

                        {{-- Tipe Normal --}}
                        <td x-show="activeTab === 'normal'" class="px-6 py-5 text-center">
                            <span
                                class="inline-block px-3 py-1.5 rounded-md {{ $w->tipe == 'Istirahat' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600' }} text-[11px] font-bold uppercase tracking-wider">{{ $w->tipe }}</span>
                        </td>

                        {{-- Tipe Jumat --}}
                        <td x-show="activeTab === 'jumat'" class="px-6 py-5 text-center">
                            @php $tipeJumat = $w->mulai_jumat ? $w->tipe_jumat : $w->tipe; @endphp
                            <span
                                class="inline-block px-3 py-1.5 rounded-md {{ $tipeJumat == 'Istirahat' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }} text-[11px] font-bold uppercase tracking-wider">{{ $tipeJumat }}</span>
                        </td>

                        {{-- ==================== AKSI ==================== --}}
                        <td class="px-6 py-5">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                    onclick="openEditModal({{ $w->id }}, {{ $w->jam_ke }}, '{{ substr($w->waktu_mulai, 0, 5) }}', '{{ substr($w->waktu_selesai, 0, 5) }}', '{{ $w->tipe }}', '{{ $w->mulai_senin ? substr($w->mulai_senin, 0, 5) : '' }}', '{{ $w->selesai_senin ? substr($w->selesai_senin, 0, 5) : '' }}', '{{ $w->tipe_senin }}', '{{ $w->mulai_jumat ? substr($w->mulai_jumat, 0, 5) : '' }}', '{{ $w->selesai_jumat ? substr($w->selesai_jumat, 0, 5) : '' }}', '{{ $w->tipe_jumat }}')"
                                    class="p-2 border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-300 rounded-lg transition-colors bg-white"
                                    title="Edit Baris Ini">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>

                                <form action="{{ route('master-waktu.destroy', $w->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('PENTING: Menghapus akan membuang jam ke-{{ $w->jam_ke }} di SEMUA HARI. Lanjutkan?');">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-2 border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-300 rounded-lg transition-colors bg-white"
                                        title="Hapus Permanen">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center text-slate-400 font-medium">Belum ada data jam
                            pelajaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODALS AREA --}}

{{-- 1. Modal Tambah --}}
<div id="modaltambah"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99] hidden items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl my-8">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl">
            <h3 class="font-bold text-slate-800 flex items-center gap-2"><span
                    class="w-1.5 h-5 bg-indigo-600 rounded-full"></span> Tambah Slot Jam Pelajaran</h3>
            <button type="button" onclick="closeModal('modaltambah')"
                class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('master-waktu.store') }}" method="POST" class="p-6">
            @csrf
            <div class="mb-6 flex items-center gap-4">
                <label class="font-bold text-slate-700">Ini adalah pengaturan untuk Jam Ke -</label>
                <input type="number" name="jam_ke"
                    class="w-24 border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none text-center font-bold text-lg"
                    required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- SENIN --}}
                <div class="p-5 bg-cyan-50/30 border border-cyan-100 rounded-xl">
                    <h4 class="font-bold text-cyan-700 mb-4 text-xs uppercase tracking-wider text-center">Khusus Senin
                    </h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mulai <span
                                    class="normal-case font-normal">(Kosongkan jika ikut normal)</span></label>
                            <input type="time" name="mulai_senin"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-cyan-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Selesai</label>
                            <input type="time" name="selesai_senin"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-cyan-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kegiatan</label>
                            <select name="tipe_senin"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-cyan-500 outline-none">
                                <option value="Belajar">Belajar</option>
                                <option value="Istirahat">Istirahat</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- NORMAL --}}
                <div class="p-5 bg-indigo-50/50 border border-indigo-200 rounded-xl relative shadow-sm">
                    <div
                        class="absolute -top-3 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-widest">
                        Wajib Diisi</div>
                    <h4 class="font-bold text-indigo-700 mb-4 mt-2 text-xs uppercase tracking-wider text-center">Normal
                        (Selasa - Kamis)</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mulai</label>
                            <input type="time" name="waktu_mulai"
                                class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Selesai</label>
                            <input type="time" name="waktu_selesai"
                                class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kegiatan</label>
                            <select name="tipe"
                                class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                                required>
                                <option value="Belajar">Belajar</option>
                                <option value="Istirahat">Istirahat</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- JUMAT --}}
                <div class="p-5 bg-emerald-50/30 border border-emerald-100 rounded-xl">
                    <h4 class="font-bold text-emerald-700 mb-4 text-xs uppercase tracking-wider text-center">Khusus
                        Jumat</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mulai <span
                                    class="normal-case font-normal">(Kosongkan jika ikut normal)</span></label>
                            <input type="time" name="mulai_jumat"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Selesai</label>
                            <input type="time" name="selesai_jumat"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kegiatan</label>
                            <select name="tipe_jumat"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-emerald-500 outline-none">
                                <option value="Belajar">Belajar</option>
                                <option value="Istirahat">Istirahat</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold mt-8 py-4 rounded-xl shadow-lg transition-colors uppercase tracking-widest text-sm">SIMPAN
                DATA</button>
        </form>
    </div>
</div>

{{-- MODAL EDIT --}}
<div id="modaledit"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl my-8">
        <div class="px-6 py-4 border-b border-amber-100 flex justify-between items-center bg-amber-50 rounded-t-2xl">
            <h3 class="font-bold text-amber-800 flex items-center gap-2"><span
                    class="w-1.5 h-5 bg-amber-500 rounded-full"></span> Edit Jam Pelajaran</h3>
            <button type="button" onclick="closeModal('modaledit')"
                class="text-amber-400 hover:text-red-500 text-2xl leading-none">&times;</button>
        </div>
        <form id="form-edit-waktu" method="POST" class="p-6">
            @csrf @method('PUT')
            <div class="mb-6 flex items-center gap-4">
                <label class="font-bold text-slate-700">Mengedit pengaturan untuk Jam Ke -</label>
                <input type="number" id="edit_jam_ke" name="jam_ke"
                    class="w-24 border border-slate-300 bg-slate-100 rounded-lg px-4 py-2 outline-none text-center font-bold text-lg text-slate-500"
                    required readonly>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- SENIN EDIT --}}
                <div class="p-5 bg-cyan-50/30 border border-cyan-100 rounded-xl">
                    <h4 class="font-bold text-cyan-700 mb-4 text-xs uppercase tracking-wider text-center">Khusus Senin
                    </h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mulai</label>
                            <input type="time" id="edit_mulai_senin" name="mulai_senin"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-cyan-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Selesai</label>
                            <input type="time" id="edit_selesai_senin" name="selesai_senin"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-cyan-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kegiatan</label>
                            <select id="edit_tipe_senin" name="tipe_senin"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-cyan-500 outline-none">
                                <option value="Belajar">Belajar</option>
                                <option value="Istirahat">Istirahat</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- NORMAL EDIT --}}
                <div class="p-5 bg-amber-50/50 border border-amber-200 rounded-xl relative shadow-sm">
                    <div
                        class="absolute -top-3 left-1/2 -translate-x-1/2 bg-amber-500 text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-widest">
                        Wajib Diisi</div>
                    <h4 class="font-bold text-amber-700 mb-4 mt-2 text-xs uppercase tracking-wider text-center">Normal
                        (Selasa - Kamis)</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mulai</label>
                            <input type="time" id="edit_mulai" name="waktu_mulai"
                                class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-amber-500 outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Selesai</label>
                            <input type="time" id="edit_selesai" name="waktu_selesai"
                                class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-amber-500 outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kegiatan</label>
                            <select id="edit_tipe" name="tipe"
                                class="w-full border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-amber-500 outline-none"
                                required>
                                <option value="Belajar">Belajar</option>
                                <option value="Istirahat">Istirahat</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- JUMAT EDIT --}}
                <div class="p-5 bg-emerald-50/30 border border-emerald-100 rounded-xl">
                    <h4 class="font-bold text-emerald-700 mb-4 text-xs uppercase tracking-wider text-center">Khusus
                        Jumat</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mulai</label>
                            <input type="time" id="edit_mulai_jumat" name="mulai_jumat"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Selesai</label>
                            <input type="time" id="edit_selesai_jumat" name="selesai_jumat"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kegiatan</label>
                            <select id="edit_tipe_jumat" name="tipe_jumat"
                                class="w-full border border-slate-200 rounded-lg p-2 focus:ring-2 focus:ring-emerald-500 outline-none">
                                <option value="Belajar">Belajar</option>
                                <option value="Istirahat">Istirahat</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit"
                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold mt-8 py-4 rounded-xl shadow-lg transition-colors uppercase tracking-widest text-sm">UPDATE
                DATA</button>
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

function openEditModal(id, jam, mulai, selesai, tipe, mulaiS, selesaiS, tipeS, mulaiJ, selesaiJ, tipeJ) {
    document.getElementById('form-edit-waktu').action = `/master-waktu/${id}`;
    document.getElementById('edit_jam_ke').value = jam;

    // Normal
    document.getElementById('edit_mulai').value = mulai;
    document.getElementById('edit_selesai').value = selesai;
    document.getElementById('edit_tipe').value = tipe;

    // Senin
    document.getElementById('edit_mulai_senin').value = mulaiS || '';
    document.getElementById('edit_selesai_senin').value = selesaiS || '';
    document.getElementById('edit_tipe_senin').value = tipeS || 'Belajar';

    // Jumat
    document.getElementById('edit_mulai_jumat').value = mulaiJ || '';
    document.getElementById('edit_selesai_jumat').value = selesaiJ || '';
    document.getElementById('edit_tipe_jumat').value = tipeJ || 'Belajar';

    openModal('modaledit');
}

window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.replace('flex', 'hidden');
    }
}
</script>
@endpush