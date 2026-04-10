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
        <span class="font-semibold text-sm">✅ {{ session('success') }}</span>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700">&times;</button>
    </div>
    @endif
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-4 flex items-center justify-between p-4 bg-red-50 border border-red-100 rounded-xl shadow-sm text-red-800 shrink-0">
        <span class="font-semibold text-sm">❌ {{ session('error') }}</span>
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
                        <p class="text-slate-500 text-sm mt-1 font-medium">Kelola jam normal, khusus Senin, dan khusus
                            Jumat.</p>
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
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead class="bg-slate-50 sticky top-0 z-10">
                    <tr>
                        <th
                            class="px-4 py-4 text-xs font-bold text-slate-400 uppercase text-center border-b-2 border-slate-200">
                            Jam Ke</th>
                        <th
                            class="px-4 py-4 text-xs font-bold text-cyan-600 uppercase text-center border-b-2 border-cyan-200 bg-cyan-50/50">
                            Khusus Senin</th>
                        <th
                            class="px-4 py-4 text-xs font-bold text-indigo-500 uppercase text-center border-b-2 border-indigo-200 bg-indigo-50/50">
                            Normal (Sel - Kam)</th>
                        <th
                            class="px-4 py-4 text-xs font-bold text-emerald-600 uppercase text-center border-b-2 border-emerald-200 bg-emerald-50/50">
                            Khusus Jumat</th>
                        <th
                            class="px-4 py-4 text-xs font-bold text-slate-400 uppercase text-right border-b-2 border-slate-200 w-24">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-waktu-main" class="divide-y divide-slate-100">
                    @forelse($waktus as $w)
                    <tr class="hover:bg-slate-50 transition-colors"
                        data-filter="{{ strtolower($w->jam_ke) }} {{ strtolower($w->tipe) }}">
                        <td class="px-4 py-5 text-center">
                            <span
                                class="font-extrabold text-slate-700 text-lg bg-slate-100 px-4 py-2 rounded-xl">{{ $w->jam_ke }}</span>
                        </td>

                        {{-- SENIN --}}
                        <td class="px-4 py-5 text-center border-x border-slate-50">
                            @if($w->mulai_senin && $w->selesai_senin)
                            <div class="font-bold text-slate-700 text-base">
                                {{ \Carbon\Carbon::parse($w->mulai_senin)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->selesai_senin)->format('H:i') }}
                            </div>
                            <span
                                class="inline-block mt-1 px-2.5 py-1 rounded {{ $w->tipe_senin == 'Istirahat' ? 'bg-amber-50 text-amber-600' : 'bg-cyan-50 text-cyan-600' }} text-[10px] font-bold uppercase tracking-wider">{{ $w->tipe_senin }}</span>
                            @else
                            <span
                                class="text-xs font-semibold text-slate-400 italic bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">Sama
                                spt Normal</span>
                            @endif
                        </td>

                        {{-- SELASA - KAMIS (NORMAL) --}}
                        <td class="px-4 py-5 text-center border-r border-slate-50">
                            <div class="font-bold text-slate-700 text-base">
                                {{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }}
                            </div>
                            <span
                                class="inline-block mt-1 px-2.5 py-1 rounded {{ $w->tipe == 'Istirahat' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600' }} text-[10px] font-bold uppercase tracking-wider">{{ $w->tipe }}</span>
                        </td>

                        {{-- JUMAT --}}
                        <td class="px-4 py-5 text-center border-r border-slate-50">
                            @if($w->mulai_jumat && $w->selesai_jumat)
                            <div class="font-bold text-slate-700 text-base">
                                {{ \Carbon\Carbon::parse($w->mulai_jumat)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->selesai_jumat)->format('H:i') }}
                            </div>
                            <span
                                class="inline-block mt-1 px-2.5 py-1 rounded {{ $w->tipe_jumat == 'Istirahat' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }} text-[10px] font-bold uppercase tracking-wider">{{ $w->tipe_jumat }}</span>
                            @else
                            <span
                                class="text-xs font-semibold text-slate-400 italic bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">Sama
                                spt Normal</span>
                            @endif
                        </td>

                        <td class="px-4 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button"
                                    onclick="openEditModal({{ $w->id }}, {{ $w->jam_ke }}, '{{ substr($w->waktu_mulai, 0, 5) }}', '{{ substr($w->waktu_selesai, 0, 5) }}', '{{ $w->tipe }}', '{{ $w->mulai_senin ? substr($w->mulai_senin, 0, 5) : '' }}', '{{ $w->selesai_senin ? substr($w->selesai_senin, 0, 5) : '' }}', '{{ $w->tipe_senin }}', '{{ $w->mulai_jumat ? substr($w->mulai_jumat, 0, 5) : '' }}', '{{ $w->selesai_jumat ? substr($w->selesai_jumat, 0, 5) : '' }}', '{{ $w->tipe_jumat }}')"
                                    class="p-2 text-amber-500 hover:bg-amber-50 rounded-lg">EDIT</button>

                                <form action="{{ route('master-waktu.destroy', $w->id) }}" method="POST"
                                    onsubmit="return confirm('Hapus jam ke-{{ $w->jam_ke }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-2 text-red-500 hover:bg-red-50 rounded-lg">HAPUS</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-10 text-slate-400">Belum ada data jam pelajaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH --}}
<div id="modaltambah"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-4xl overflow-hidden shadow-2xl">
        <div class="p-4 border-b flex justify-between">
            <h3 class="font-bold text-lg text-indigo-700">Tambah Jam Pelajaran</h3><button
                onclick="closeModal('modaltambah')">✖</button>
        </div>
        <form action="{{ route('master-waktu.store') }}" method="POST" class="p-6">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold text-slate-500 mb-1">Jam Ke</label>
                <input type="number" name="jam_ke"
                    class="w-1/4 border border-slate-300 rounded-lg p-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                    required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- SENIN --}}
                <div class="p-4 bg-cyan-50/50 border border-cyan-100 rounded-xl">
                    <h4 class="font-bold text-cyan-700 mb-3 text-[11px] uppercase tracking-wider">Khusus Senin</h4>
                    <input type="time" name="mulai_senin"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-cyan-500 text-sm">
                    <input type="time" name="selesai_senin"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-cyan-500 text-sm">
                    <select name="tipe_senin" class="w-full border border-slate-300 p-2 rounded outline-none text-sm">
                        <option value="Belajar">Belajar</option>
                        <option value="Istirahat">Istirahat</option>
                    </select>
                </div>

                {{-- NORMAL --}}
                <div class="p-4 bg-indigo-50/50 border border-indigo-100 rounded-xl">
                    <h4 class="font-bold text-indigo-700 mb-3 text-[11px] uppercase tracking-wider">Normal (Sel - Kam)
                    </h4>
                    <input type="time" name="waktu_mulai"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                        required>
                    <input type="time" name="waktu_selesai"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
                        required>
                    <select name="tipe" class="w-full border border-slate-300 p-2 rounded outline-none text-sm"
                        required>
                        <option value="Belajar">Belajar</option>
                        <option value="Istirahat">Istirahat</option>
                    </select>
                </div>

                {{-- JUMAT --}}
                <div class="p-4 bg-emerald-50/50 border border-emerald-100 rounded-xl">
                    <h4 class="font-bold text-emerald-700 mb-3 text-[11px] uppercase tracking-wider">Khusus Jumat</h4>
                    <input type="time" name="mulai_jumat"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                    <input type="time" name="selesai_jumat"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                    <select name="tipe_jumat" class="w-full border border-slate-300 p-2 rounded outline-none text-sm">
                        <option value="Belajar">Belajar</option>
                        <option value="Istirahat">Istirahat</option>
                    </select>
                </div>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 text-white font-bold mt-6 py-3.5 rounded-lg shadow-lg hover:bg-indigo-600 transition tracking-widest text-sm">SIMPAN
                DATA</button>
        </form>
    </div>
</div>

{{-- MODAL EDIT --}}
<div id="modaledit" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-4xl overflow-hidden shadow-2xl">
        <div class="p-4 border-b flex justify-between">
            <h3 class="font-bold text-lg text-amber-600">Edit Jam Pelajaran</h3><button
                onclick="closeModal('modaledit')">✖</button>
        </div>
        <form id="form-edit-waktu" method="POST" class="p-6">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-xs font-bold text-slate-500 mb-1">Jam Ke</label>
                <input type="number" id="edit_jam_ke" name="jam_ke"
                    class="w-1/4 border border-slate-300 bg-slate-100 text-slate-500 rounded-lg p-2 text-sm" required
                    readonly>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- SENIN EDIT --}}
                <div class="p-4 bg-cyan-50/50 border border-cyan-100 rounded-xl">
                    <h4 class="font-bold text-cyan-700 mb-3 text-[11px] uppercase tracking-wider">Khusus Senin</h4>
                    <input type="time" id="edit_mulai_senin" name="mulai_senin"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                    <input type="time" id="edit_selesai_senin" name="selesai_senin"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                    <select id="edit_tipe_senin" name="tipe_senin"
                        class="w-full border border-slate-300 p-2 rounded outline-none text-sm">
                        <option value="Belajar">Belajar</option>
                        <option value="Istirahat">Istirahat</option>
                    </select>
                </div>

                {{-- NORMAL EDIT --}}
                <div class="p-4 bg-indigo-50/50 border border-indigo-100 rounded-xl">
                    <h4 class="font-bold text-indigo-700 mb-3 text-[11px] uppercase tracking-wider">Normal (Sel - Kam)
                    </h4>
                    <input type="time" id="edit_mulai" name="waktu_mulai"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                        required>
                    <input type="time" id="edit_selesai" name="waktu_selesai"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-amber-500 text-sm"
                        required>
                    <select id="edit_tipe" name="tipe"
                        class="w-full border border-slate-300 p-2 rounded outline-none text-sm" required>
                        <option value="Belajar">Belajar</option>
                        <option value="Istirahat">Istirahat</option>
                    </select>
                </div>

                {{-- JUMAT EDIT --}}
                <div class="p-4 bg-emerald-50/50 border border-emerald-100 rounded-xl">
                    <h4 class="font-bold text-emerald-700 mb-3 text-[11px] uppercase tracking-wider">Khusus Jumat</h4>
                    <input type="time" id="edit_mulai_jumat" name="mulai_jumat"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                    <input type="time" id="edit_selesai_jumat" name="selesai_jumat"
                        class="w-full mb-2 border border-slate-300 p-2 rounded outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                    <select id="edit_tipe_jumat" name="tipe_jumat"
                        class="w-full border border-slate-300 p-2 rounded outline-none text-sm">
                        <option value="Belajar">Belajar</option>
                        <option value="Istirahat">Istirahat</option>
                    </select>
                </div>
            </div>
            <button type="submit"
                class="w-full bg-amber-500 text-white font-bold mt-6 py-3.5 rounded-lg shadow-lg hover:bg-amber-600 transition tracking-widest text-sm">UPDATE
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

// FIX: Tambah parameter buat Senin
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
</script>
@endpush