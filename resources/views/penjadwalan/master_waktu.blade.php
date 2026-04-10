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
                        <p class="text-slate-500 text-sm mt-1 font-medium">Kelola urutan jam pelajaran dan seting khusus
                            hari Jumat.</p>
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
                <thead class="bg-slate-50 sticky top-0 z-10">
                    <tr>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-center border-b-2 border-slate-200">
                            Jam Ke</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-indigo-500 uppercase text-center border-b-2 border-indigo-200 bg-indigo-50/50">
                            Waktu Normal (Senin - Kamis)</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-emerald-600 uppercase text-center border-b-2 border-emerald-200 bg-emerald-50/50">
                            Waktu Khusus Jumat</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right border-b-2 border-slate-200">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-waktu-main" class="divide-y divide-slate-100">
                    @forelse($waktus as $w)
                    <tr class="hover:bg-slate-50 transition-colors"
                        data-filter="{{ strtolower($w->jam_ke) }} {{ strtolower($w->tipe) }}">
                        <td class="px-6 py-5 text-center">
                            <span
                                class="font-extrabold text-slate-700 text-lg bg-slate-100 px-4 py-2 rounded-xl">{{ $w->jam_ke }}</span>
                        </td>

                        {{-- SENIN - KAMIS --}}
                        <td class="px-6 py-5 text-center border-x border-slate-50">
                            <div class="font-bold text-slate-700 text-base">
                                {{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }}
                            </div>
                            @if($w->tipe == 'Belajar')
                            <span
                                class="inline-block mt-1 px-2.5 py-1 rounded bg-indigo-50 text-indigo-600 text-[10px] font-bold uppercase tracking-wider">Belajar</span>
                            @else
                            <span
                                class="inline-block mt-1 px-2.5 py-1 rounded bg-amber-50 text-amber-600 text-[10px] font-bold uppercase tracking-wider">Istirahat</span>
                            @endif
                        </td>

                        {{-- JUMAT --}}
                        <td class="px-6 py-5 text-center border-r border-slate-50">
                            @if($w->mulai_jumat && $w->selesai_jumat)
                            <div class="font-bold text-slate-700 text-base">
                                {{ \Carbon\Carbon::parse($w->mulai_jumat)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->selesai_jumat)->format('H:i') }}
                            </div>
                            @if($w->tipe_jumat == 'Belajar')
                            <span
                                class="inline-block mt-1 px-2.5 py-1 rounded bg-emerald-50 text-emerald-600 text-[10px] font-bold uppercase tracking-wider">Belajar</span>
                            @else
                            <span
                                class="inline-block mt-1 px-2.5 py-1 rounded bg-amber-50 text-amber-600 text-[10px] font-bold uppercase tracking-wider">Istirahat</span>
                            @endif
                            @else
                            <span
                                class="text-xs font-semibold text-slate-400 italic bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">Sama
                                seperti normal</span>
                            @endif
                        </td>

                        <td class="px-6 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- TOMBOL EDIT VANILLA JS (ANTI NYANGKUT) --}}
                                <button type="button"
                                    onclick="openEditModal({{ $w->id }}, {{ $w->jam_ke }}, '{{ substr($w->waktu_mulai, 0, 5) }}', '{{ substr($w->waktu_selesai, 0, 5) }}', '{{ $w->tipe }}', '{{ $w->mulai_jumat ? substr($w->mulai_jumat, 0, 5) : '' }}', '{{ $w->selesai_jumat ? substr($w->selesai_jumat, 0, 5) : '' }}', '{{ $w->tipe_jumat }}')"
                                    class="p-2 border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-300 hover:bg-amber-50 rounded-lg transition-all"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>

                                <form action="{{ route('master-waktu.destroy', $w->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Yakin hapus jam ke-{{ $w->jam_ke }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-2 border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-300 hover:bg-red-50 rounded-lg transition-all"
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
                        <td colspan="4" class="px-6 py-20 text-center text-slate-400">Belum ada data jam pelajaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 3. FOOTER SECTION --}}
        <div class="bg-white border-t border-slate-100 px-8 py-4 flex justify-between items-center shrink-0">
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Sistem Penjadwalan</span>
            <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest">Secure Data</span>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL TAMBAH JAM --}}
{{-- ========================================== --}}
<div id="modaltambah"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-white/20 animate-scale-in">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800 flex items-center gap-2"><span
                    class="w-1.5 h-5 bg-indigo-600 rounded-full"></span> Tambah Jam Pelajaran</h3>
            <button onclick="closeModal('modaltambah')"
                class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('master-waktu.store') }}" method="POST" class="p-6">
            @csrf
            <div class="mb-5">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Jam Ke</label>
                <input type="number" name="jam_ke"
                    class="w-1/3 border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none text-sm"
                    placeholder="Contoh: 1" min="1" required>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- SETTING NORMAL --}}
                <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4">
                    <h4 class="font-bold text-indigo-700 text-xs uppercase mb-4 tracking-wider">Seting Normal (Senin -
                        Kamis)</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mulai</label>
                            <input type="time" name="waktu_mulai"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Selesai</label>
                            <input type="time" name="waktu_selesai"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tipe</label>
                            <select name="tipe"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
                                required>
                                <option value="Belajar">Belajar</option>
                                <option value="Istirahat">Istirahat</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- SETTING JUMAT --}}
                <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-4">
                    <h4 class="font-bold text-emerald-700 text-xs uppercase mb-4 tracking-wider">Seting Khusus Jumat
                    </h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mulai <span
                                    class="text-slate-400 normal-case font-normal">(Kosongkan jika sama)</span></label>
                            <input type="time" name="mulai_jumat"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Selesai</label>
                            <input type="time" name="selesai_jumat"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tipe</label>
                            <select name="tipe_jumat"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                                <option value="Belajar">Belajar</option>
                                <option value="Istirahat">Istirahat</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit"
                class="w-full mt-6 bg-slate-900 hover:bg-indigo-600 text-white font-bold py-3.5 rounded-xl shadow-lg transition duration-300 uppercase tracking-wider text-xs">SIMPAN
                DATA</button>
        </form>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL EDIT JAM (VANILLA JS ANTI NYANGKUT) --}}
{{-- ========================================== --}}
<div id="modaledit"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-white/20">
        <div class="px-6 py-4 border-b border-amber-100 bg-amber-50 flex justify-between items-center">
            <h3 class="font-bold text-amber-800 flex items-center gap-2"><span
                    class="w-1.5 h-5 bg-amber-500 rounded-full"></span> Edit Jam Pelajaran</h3>
            <button onclick="closeModal('modaledit')"
                class="text-amber-400 hover:text-amber-600 text-2xl leading-none">&times;</button>
        </div>
        <form id="form-edit-waktu" method="POST" class="p-6">
            @csrf @method('PUT')
            <div class="mb-5">
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Jam Ke</label>
                <input type="number" id="edit_jam_ke" name="jam_ke"
                    class="w-1/3 border border-slate-300 bg-slate-100 rounded-xl px-4 py-3 outline-none text-sm text-slate-500"
                    required readonly>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- SETTING NORMAL EDIT --}}
                <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4">
                    <h4 class="font-bold text-indigo-700 text-xs uppercase mb-4 tracking-wider">Seting Normal (Senin -
                        Kamis)</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mulai</label>
                            <input type="time" id="edit_waktu_mulai" name="waktu_mulai"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Selesai</label>
                            <input type="time" id="edit_waktu_selesai" name="waktu_selesai"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                                required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tipe</label>
                            <select id="edit_tipe" name="tipe"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                                required>
                                <option value="Belajar">Belajar</option>
                                <option value="Istirahat">Istirahat</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- SETTING JUMAT EDIT --}}
                <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-4">
                    <h4 class="font-bold text-emerald-700 text-xs uppercase mb-4 tracking-wider">Seting Khusus Jumat
                    </h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Mulai</label>
                            <input type="time" id="edit_mulai_jumat" name="mulai_jumat"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Selesai</label>
                            <input type="time" id="edit_selesai_jumat" name="selesai_jumat"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tipe</label>
                            <select id="edit_tipe_jumat" name="tipe_jumat"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                                <option value="Belajar">Belajar</option>
                                <option value="Istirahat">Istirahat</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit"
                class="w-full mt-6 bg-amber-500 hover:bg-amber-600 text-white font-bold py-3.5 rounded-xl shadow-lg transition duration-300 uppercase tracking-wider text-xs">UPDATE
                DATA</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Pencarian Table
function searchMainTable() {
    const input = document.getElementById('search-waktu-main').value.toLowerCase();
    const rows = document.querySelectorAll('#tbody-waktu-main tr[data-filter]');
    let hasResult = false;
    rows.forEach(row => {
        if (row.getAttribute('data-filter').includes(input)) {
            row.style.display = "";
            hasResult = true;
        } else {
            row.style.display = "none";
        }
    });
    document.getElementById('search-no-result')?.classList.toggle('hidden', hasResult || input === '');
}

// Tutup/Buka Modal Dasar
function openModal(id) {
    document.getElementById(id).classList.replace('hidden', 'flex');
}

function closeModal(id) {
    document.getElementById(id).classList.replace('flex', 'hidden');
}

// FUNGSI JAVASCRIPT UNTUK MODAL EDIT (MENJAMIN POP UP MUNCUL)
function openEditModal(id, jam, mulai, selesai, tipe, mulaiJ, selesaiJ, tipeJ) {
    // Arahin form ke URL ID yang bener
    document.getElementById('form-edit-waktu').action = `/master-waktu/${id}`;

    // Setel data ke form Normal
    document.getElementById('edit_jam_ke').value = jam;
    document.getElementById('edit_waktu_mulai').value = mulai;
    document.getElementById('edit_waktu_selesai').value = selesai;
    document.getElementById('edit_tipe').value = tipe;

    // Setel data ke form Jumat
    document.getElementById('edit_mulai_jumat').value = mulaiJ || '';
    document.getElementById('edit_selesai_jumat').value = selesaiJ || '';
    document.getElementById('edit_tipe_jumat').value = tipeJ || 'Belajar';

    // Munculin Modalnya
    openModal('modaledit');
}

// Tutup modal kalau klik area gelap
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.replace('flex', 'hidden');
    }
}
</script>
@endpush