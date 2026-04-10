@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-blue-50/50 to-white"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-300/10 rounded-full blur-3xl opacity-70"></div>
    <div class="absolute top-20 left-10 w-72 h-72 bg-cyan-300/10 rounded-full blur-3xl opacity-70"></div>
</div>

{{-- CONTAINER UTAMA --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-[calc(100vh-6rem)] pb-4 pt-6 flex flex-col">

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800 shrink-0">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-emerald-100 rounded-full text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <span class="font-semibold text-sm">{{ session('success') }}</span>
        </div>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700">&times;</button>
    </div>
    @endif
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-center justify-between p-4 bg-red-50 border border-red-100 rounded-xl shadow-sm text-red-800 shrink-0">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-red-100 rounded-full text-red-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
            </div>
            <span class="font-semibold text-sm">{{ session('error') }}</span>
        </div>
        <button @click="show = false" class="text-red-400 hover:text-red-700">&times;</button>
    </div>
    @endif

    {{-- UNIFIED CARD --}}
    <div
        class="bg-white rounded-[2rem] border border-slate-100 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.08)] flex flex-col flex-1 overflow-hidden">

        {{-- 1. HEADER SECTION --}}
        <div class="px-8 pt-8 pb-6 bg-white shrink-0 z-20">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                <div class="flex gap-3 items-start">
                    <div class="w-2.5 h-8 bg-indigo-600 rounded-full mt-0.5"></div>
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Master Waktu</h1>
                        <p class="text-slate-500 text-sm mt-1 font-medium">Kelola urutan jam pelajaran dan waktu
                            istirahat.</p>
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
                        class="px-6 py-2.5 font-bold text-white transition-all duration-300 bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        <span class="text-sm uppercase tracking-wide">Tambah</span>
                    </button>
                </div>
            </div>

            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400 group-focus-within:text-indigo-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" id="search-waktu-main" oninput="searchMainTable()"
                    class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl leading-5 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm transition"
                    placeholder="Cari Tipe atau Jam Ke...">
            </div>
        </div>

        {{-- 2. TABLE SECTION --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white px-2">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-white sticky top-0 z-10">
                    <tr>
                        <th
                            class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-[15%] border-b-2 border-slate-100">
                            Jam Ke</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider w-[40%] border-b-2 border-slate-100">
                            Waktu Pelaksanaan</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-[25%] border-b-2 border-slate-100">
                            Tipe</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right w-[20%] border-b-2 border-slate-100">
                            Aksi</th>
                    </tr>
                </thead>

                <tbody id="tbody-waktu-main" class="divide-y divide-slate-100/80">
                    @forelse($waktus as $w)
                    <tr class="group hover:bg-slate-50/50 transition-colors duration-200 {{ $w->tipe == 'Istirahat' ? 'bg-orange-50/30 hover:bg-orange-50/60' : '' }}"
                        data-filter="{{ strtolower($w->jam_ke) }} {{ strtolower($w->tipe) }}">
                        <td class="px-8 py-5 text-center">
                            <span
                                class="font-bold text-slate-700 text-sm bg-slate-100 px-4 py-1.5 rounded-full">{{ $w->jam_ke }}</span>
                        </td>

                        <td class="px-6 py-5">
                            <div class="flex items-center gap-2 font-bold text-slate-700">
                                {{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }}
                                <span class="text-slate-400 font-normal">s/d</span>
                                {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }}
                            </div>
                        </td>

                        <td class="px-6 py-5 text-center">
                            @if($w->tipe == 'Belajar')
                            <span
                                class="inline-flex items-center px-3 py-1.5 rounded-md bg-indigo-50 text-indigo-600 border border-indigo-100 text-xs font-bold">Belajar</span>
                            @else
                            <span
                                class="inline-flex items-center px-3 py-1.5 rounded-md bg-amber-50 text-amber-600 border border-amber-100 text-xs font-bold">Istirahat</span>
                            @endif
                        </td>

                        <td class="px-6 py-5">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                    @click="$dispatch('buka-modal-edit-waktu', { id: '{{ $w->id }}', jam_ke: '{{ $w->jam_ke }}', mulai: '{{ substr($w->waktu_mulai, 0, 5) }}', selesai: '{{ substr($w->waktu_selesai, 0, 5) }}', tipe: '{{ $w->tipe }}' })"
                                    class="p-2 border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-300 rounded-lg transition-colors bg-white"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>

                                <form action="{{ route('master-waktu.destroy', $w->id) }}" method="POST" class="inline"
                                    onsubmit="if(confirm('Hapus jam ke-{{ $w->jam_ke }}?')) { this.querySelector('button[type=submit]').disabled = true; return true; } return false;">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-2 border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-300 rounded-lg transition-colors bg-white disabled:opacity-50"
                                        title="Hapus">
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
                    <tr id="no-data-row">
                        <td colspan="4" class="px-6 py-20 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center opacity-50">
                                <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm font-medium">Belum ada data jam pelajaran.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse

                    <tr id="search-no-result" class="hidden">
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <p>Data waktu tidak ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- 3. FOOTER SECTION --}}
        <div class="bg-white border-t border-slate-100 px-8 py-4 flex justify-between items-center shrink-0">
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Sistem Penjadwalan</span>
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Secure Data</span>
        </div>
    </div>

    <div class="text-center mt-6 text-slate-500 text-[11px] font-medium tracking-wide">
        &copy; {{ date('Y') }} SMAN 1 SAMPANG. Sistem Penjadwalan Terintegrasi.
    </div>

    {{-- MODALS AREA --}}

    {{-- 1. Modal Tambah --}}
    <div id="modaltambah"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99] hidden flex items-center justify-center p-4">
        <div
            class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-scale-in border border-white/20">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <span class="w-1.5 h-5 bg-indigo-600 rounded-full"></span> Tambah Jam Pelajaran
                </h3>
                <button onclick="closeModal('modaltambah')"
                    class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>
            <form action="{{ route('master-waktu.store') }}" method="POST" class="p-6 space-y-5">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Jam Ke</label>
                        <input type="number" name="jam_ke"
                            class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition"
                            placeholder="Contoh: 1" min="1" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tipe</label>
                        <select name="tipe"
                            class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition"
                            required>
                            <option value="Belajar">Belajar</option>
                            <option value="Istirahat">Istirahat</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Waktu Mulai</label>
                        <input type="time" name="waktu_mulai"
                            class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition"
                            required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Waktu Selesai</label>
                        <input type="time" name="waktu_selesai"
                            class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition"
                            required>
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold py-3.5 rounded-xl shadow-lg transition duration-300 uppercase tracking-wider text-xs">SIMPAN
                    DATA</button>
            </form>
        </div>
    </div>

    {{-- 2. Modal Edit (AlpineJS) --}}
    <div x-data="{ openEditWaktu: false, editData: { id: '', jam_ke: '', mulai: '', selesai: '', tipe: '' } }"
        @buka-modal-edit-waktu.window="
            openEditWaktu = true;
            editData.id = $event.detail.id;
            editData.jam_ke = $event.detail.jam_ke;
            editData.mulai = $event.detail.mulai;
            editData.selesai = $event.detail.selesai;
            editData.tipe = $event.detail.tipe;
         ">

        <div x-show="openEditWaktu" style="display: none;"
            class="fixed inset-0 z-[99] flex items-center justify-center p-4 bg-slate-9