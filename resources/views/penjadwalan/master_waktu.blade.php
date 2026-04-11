@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none">
    <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-blue-50/50 to-white"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-300/10 rounded-full blur-3xl opacity-70"></div>
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
                        <p class="text-slate-500 text-sm mt-1 font-medium">Kelola urutan jam pelajaran per hari.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
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

            {{-- TABS MENU (Senin | Normal | Jumat) --}}
            <div class="flex gap-8">
                <button @click="activeTab = 'senin'"
                    :class="activeTab === 'senin' ? 'border-cyan-500 text-cyan-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                    class="pb-4 border-b-4 font-extrabold text-sm uppercase tracking-wider transition-colors">
                    Khusus Senin
                </button>
                <button @click="activeTab = 'normal'"
                    :class="activeTab === 'normal' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                    class="pb-4 border-b-4 font-extrabold text-sm uppercase tracking-wider transition-colors">
                    Normal (Selasa - Kamis)
                </button>
                <button @click="activeTab = 'jumat'"
                    :class="activeTab === 'jumat' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                    class="pb-4 border-b-4 font-extrabold text-sm uppercase tracking-wider transition-colors">
                    Khusus Jumat
                </button>
            </div>
        </div>

        {{-- 2. TABEL AREA --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white px-2 py-2">

            {{-- ==================== TABEL SENIN ==================== --}}
            <table x-show="activeTab === 'senin'" x-cloak class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-cyan-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-8 py-4 text-xs font-bold text-cyan-600 uppercase text-center w-[15%]">Jam Ke</th>
                        <th class="px-6 py-4 text-xs font-bold text-cyan-600 uppercase text-center w-[40%]">Waktu Senin
                        </th>
                        <th class="px-6 py-4 text-xs font-bold text-cyan-600 uppercase text-center w-[25%]">Tipe
                            Kegiatan</th>
                        <th class="px-6 py-4 text-xs font-bold text-cyan-600 uppercase text-right w-[20%]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($waktus as $w)
                    <tr class="hover:bg-slate-50 transition-colors duration-200">
                        <td class="px-8 py-5 text-center"><span
                                class="font-extrabold text-slate-700 text-lg bg-slate-100 px-4 py-2 rounded-xl">{{ $w->jam_ke }}</span>
                        </td>
                        <td class="px-6 py-5 text-center">
                            @if($w->mulai_senin)
                            <div class="font-bold text-slate-700 text-base">
                                {{ \Carbon\Carbon::parse($w->mulai_senin)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->selesai_senin)->format('H:i') }}</div>
                            @else
                            <span class="text-xs text-slate-400 italic">Sama spt Normal
                                ({{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-center">
                            @php $tipeS = $w->mulai_senin ? $w->tipe_senin : $w->tipe; @endphp
                            <span
                                class="inline-block px-3 py-1.5 rounded-md {{ $tipeS == 'Istirahat' ? 'bg-amber-50 text-amber-600' : 'bg-cyan-50 text-cyan-600' }} text-xs font-bold uppercase">{{ $tipeS }}</span>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <button
                                onclick="openEditSenin({{ $w->id }}, {{ $w->jam_ke }}, '{{ substr($w->waktu_mulai, 0, 5) }}', '{{ substr($w->waktu_selesai, 0, 5) }}', '{{ $w->tipe }}', '{{ $w->mulai_senin ? substr($w->mulai_senin, 0, 5) : '' }}', '{{ $w->selesai_senin ? substr($w->selesai_senin, 0, 5) : '' }}', '{{ $w->tipe_senin }}', '{{ $w->mulai_jumat ? substr($w->mulai_jumat, 0, 5) : '' }}', '{{ $w->selesai_jumat ? substr($w->selesai_jumat, 0, 5) : '' }}', '{{ $w->tipe_jumat }}')"
                                class="px-4 py-2 text-amber-500 font-bold text-xs hover:bg-amber-50 rounded-lg">EDIT
                                SENIN</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- ==================== TABEL NORMAL ==================== --}}
            <table x-show="activeTab === 'normal'" class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-indigo-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-8 py-4 text-xs font-bold text-indigo-600 uppercase text-center w-[15%]">Jam Ke
                        </th>
                        <th class="px-6 py-4 text-xs font-bold text-indigo-600 uppercase text-center w-[40%]">Waktu
                            Normal</th>
                        <th class="px-6 py-4 text-xs font-bold text-indigo-600 uppercase text-center w-[25%]">Tipe
                            Kegiatan</th>
                        <th class="px-6 py-4 text-xs font-bold text-indigo-600 uppercase text-right w-[20%]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($waktus as $w)
                    <tr class="hover:bg-slate-50 transition-colors duration-200">
                        <td class="px-8 py-5 text-center"><span
                                class="font-extrabold text-slate-700 text-lg bg-slate-100 px-4 py-2 rounded-xl">{{ $w->jam_ke }}</span>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <div class="font-bold text-slate-700 text-base">
                                {{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <span
                                class="inline-block px-3 py-1.5 rounded-md {{ $w->tipe == 'Istirahat' ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600' }} text-xs font-bold uppercase">{{ $w->tipe }}</span>
                        </td>
                        <td class="px-6 py-5 text-right flex justify-end gap-2">
                            <button
                                onclick="openEditNormal({{ $w->id }}, {{ $w->jam_ke }}, '{{ substr($w->waktu_mulai, 0, 5) }}', '{{ substr($w->waktu_selesai, 0, 5) }}', '{{ $w->tipe }}', '{{ $w->mulai_senin ? substr($w->mulai_senin, 0, 5) : '' }}', '{{ $w->selesai_senin ? substr($w->selesai_senin, 0, 5) : '' }}', '{{ $w->tipe_senin }}', '{{ $w->mulai_jumat ? substr($w->mulai_jumat, 0, 5) : '' }}', '{{ $w->selesai_jumat ? substr($w->selesai_jumat, 0, 5) : '' }}', '{{ $w->tipe_jumat }}')"
                                class="px-4 py-2 text-amber-500 font-bold text-xs hover:bg-amber-50 rounded-lg">EDIT
                                NORMAL</button>
                            <form action="{{ route('master-waktu.destroy', $w->id) }}" method="POST"
                                onsubmit="return confirm('Hapus jam ke-{{ $w->jam_ke }} di SEMUA HARI?');">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="px-4 py-2 text-red-500 font-bold text-xs hover:bg-red-50 rounded-lg">HAPUS
                                    SLOT</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- ==================== TABEL JUMAT ==================== --}}
            <table x-show="activeTab === 'jumat'" x-cloak class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-emerald-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-8 py-4 text-xs font-bold text-emerald-600 uppercase text-center w-[15%]">Jam Ke
                        </th>
                        <th class="px-6 py-4 text-xs font-bold text-emerald-600 uppercase text-center w-[40%]">Waktu
                            Jumat</th>
                        <th class="px-6 py-4 text-xs font-bold text-emerald-600 uppercase text-center w-[25%]">Tipe
                            Kegiatan</th>
                        <th class="px-6 py-4 text-xs font-bold text-emerald-600 uppercase text-right w-[20%]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($waktus as $w)
                    <tr class="hover:bg-slate-50 transition-colors duration-200">
                        <td class="px-8 py-5 text-center"><span
                                class="font-extrabold text-slate-700 text-lg bg-slate-100 px-4 py-2 rounded-xl">{{ $w->jam_ke }}</span>
                        </td>
                        <td class="px-6 py-5 text-center">
                            @if($w->mulai_jumat)
                            <div class="font-bold text-slate-700 text-base">
                                {{ \Carbon\Carbon::parse($w->mulai_jumat)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->selesai_jumat)->format('H:i') }}</div>
                            @else
                            <span class="text-xs text-slate-400 italic">Sama spt Normal
                                ({{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }} -
                                {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-center">
                            @php $tipeJ = $w->mulai_jumat ? $w->tipe_jumat : $w->tipe; @endphp
                            <span
                                class="inline-block px-3 py-1.5 rounded-md {{ $tipeJ == 'Istirahat' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }} text-xs font-bold uppercase">{{ $tipeJ }}</span>
                        </td>
                        <td class="px-6 py-5 text-right">
                            <button
                                onclick="openEditJumat({{ $w->id }}, {{ $w->jam_ke }}, '{{ substr($w->waktu_mulai, 0, 5) }}', '{{ substr($w->waktu_selesai, 0, 5) }}', '{{ $w->tipe }}', '{{ $w->mulai_senin ? substr($w->mulai_senin, 0, 5) : '' }}', '{{ $w->selesai_senin ? substr($w->selesai_senin, 0, 5) : '' }}', '{{ $w->tipe_senin }}', '{{ $w->mulai_jumat ? substr($w->mulai_jumat, 0, 5) : '' }}', '{{ $w->selesai_jumat ? substr($w->selesai_jumat, 0, 5) : '' }}', '{{ $w->tipe_jumat }}')"
                                class="px-4 py-2 text-amber-500 font-bold text-xs hover:bg-amber-50 rounded-lg">EDIT
                                JUMAT</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL TAMBAH JAM (HANYA MINTA NORMAL) --}}
{{-- ========================================== --}}
<div id="modaltambah"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800">Tambah Slot Waktu Baru</h3>
            <button onclick="closeModal('modaltambah')"
                class="text-slate-400 hover:text-red-500 text-2xl">&times;</button>
        </div>
        <form action="{{ route('master-waktu.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Jam Ke</label>
                <input type="number" name="jam_ke"
                    class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Waktu Mulai</label>
                    <input type="time" name="waktu_mulai"
                        class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Waktu Selesai</label>
                    <input type="time" name="waktu_selesai"
                        class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
                        required>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1">Tipe Kegiatan</label>
                <select name="tipe"
                    class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none" required>
                    <option value="Belajar">Belajar</option>
                    <option value="Istirahat">Istirahat</option>
                </select>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 text-white font-bold mt-4 py-3 rounded-xl hover:bg-indigo-600 transition">SIMPAN</button>
        </form>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL EDIT KHUSUS NORMAL --}}
{{-- ========================================== --}}
<div id="modaledit_normal"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="p-4 border-b flex justify-between items-center bg-indigo-50">
            <h3 class="font-bold text-indigo-800">Edit Waktu Normal (Selasa-Kamis)</h3><button
                onclick="closeModal('modaledit_normal')" class="text-slate-400">&times;</button>
        </div>
        <form id="form-edit-normal" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            {{-- Hidden inputs buat data hari lain --}}
            <input type="hidden" id="norm_mulai_senin" name="mulai_senin">
            <input type="hidden" id="norm_selesai_senin" name="selesai_senin">
            <input type="hidden" id="norm_tipe_senin" name="tipe_senin">
            <input type="hidden" id="norm_mulai_jumat" name="mulai_jumat">
            <input type="hidden" id="norm_selesai_jumat" name="selesai_jumat">
            <input type="hidden" id="norm_tipe_jumat" name="tipe_jumat">

            <div><label class="block text-xs font-bold text-slate-500 mb-1">Jam Ke</label><input type="number"
                    id="norm_jam_ke" name="jam_ke" class="w-full border bg-slate-100 rounded-lg p-2 text-slate-500"
                    readonly></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-bold text-slate-500 mb-1">Mulai</label><input type="time"
                        id="norm_waktu_mulai" name="waktu_mulai" class="w-full border rounded-lg p-2" required></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1">Selesai</label><input type="time"
                        id="norm_waktu_selesai" name="waktu_selesai" class="w-full border rounded-lg p-2" required>
                </div>
            </div>
            <div><label class="block text-xs font-bold text-slate-500 mb-1">Kegiatan</label><select id="norm_tipe"
                    name="tipe" class="w-full border rounded-lg p-2" required>
                    <option value="Belajar">Belajar</option>
                    <option value="Istirahat">Istirahat</option>
                </select></div>
            <button type="submit"
                class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700">UPDATE
                NORMAL</button>
        </form>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL EDIT KHUSUS SENIN --}}
{{-- ========================================== --}}
<div id="modaledit_senin"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="p-4 border-b flex justify-between items-center bg-cyan-50">
            <h3 class="font-bold text-cyan-800">Edit Waktu Khusus Senin</h3><button
                onclick="closeModal('modaledit_senin')" class="text-slate-400">&times;</button>
        </div>
        <form id="form-edit-senin" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            {{-- Wajib kirim waktu normal agar tidak error di Laravel --}}
            <input type="hidden" id="senin_waktu_mulai" name="waktu_mulai">
            <input type="hidden" id="senin_waktu_selesai" name="waktu_selesai">
            <input type="hidden" id="senin_tipe" name="tipe">
            <input type="hidden" id="senin_mulai_jumat" name="mulai_jumat">
            <input type="hidden" id="senin_selesai_jumat" name="selesai_jumat">
            <input type="hidden" id="senin_tipe_jumat" name="tipe_jumat">

            <div><label class="block text-xs font-bold text-slate-500 mb-1">Jam Ke</label><input type="number"
                    id="senin_jam_ke" name="jam_ke" class="w-full border bg-slate-100 rounded-lg p-2 text-slate-500"
                    readonly></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-bold text-slate-500 mb-1">Mulai <span
                            class="font-normal normal-case">(Kosongkan jika ikut Normal)</span></label><input
                        type="time" id="senin_mulai_senin" name="mulai_senin" class="w-full border rounded-lg p-2">
                </div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1">Selesai</label><input type="time"
                        id="senin_selesai_senin" name="selesai_senin" class="w-full border rounded-lg p-2"></div>
            </div>
            <div><label class="block text-xs font-bold text-slate-500 mb-1">Kegiatan</label><select
                    id="senin_tipe_senin" name="tipe_senin" class="w-full border rounded-lg p-2">
                    <option value="Belajar">Belajar</option>
                    <option value="Istirahat">Istirahat</option>
                </select></div>
            <button type="submit"
                class="w-full bg-cyan-600 text-white font-bold py-3 rounded-xl hover:bg-cyan-700">UPDATE SENIN</button>
        </form>
    </div>
</div>

{{-- ========================================== --}}
{{-- MODAL EDIT KHUSUS JUMAT --}}
{{-- ========================================== --}}
<div id="modaledit_jumat"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="p-4 border-b flex justify-between items-center bg-emerald-50">
            <h3 class="font-bold text-emerald-800">Edit Waktu Khusus Jumat</h3><button
                onclick="closeModal('modaledit_jumat')" class="text-slate-400">&times;</button>
        </div>
        <form id="form-edit-jumat" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            {{-- Wajib kirim waktu normal agar tidak error di Laravel --}}
            <input type="hidden" id="jumat_waktu_mulai" name="waktu_mulai">
            <input type="hidden" id="jumat_waktu_selesai" name="waktu_selesai">
            <input type="hidden" id="jumat_tipe" name="tipe">
            <input type="hidden" id="jumat_mulai_senin" name="mulai_senin">
            <input type="hidden" id="jumat_selesai_senin" name="selesai_senin">
            <input type="hidden" id="jumat_tipe_senin" name="tipe_senin">

            <div><label class="block text-xs font-bold text-slate-500 mb-1">Jam Ke</label><input type="number"
                    id="jumat_jam_ke" name="jam_ke" class="w-full border bg-slate-100 rounded-lg p-2 text-slate-500"
                    readonly></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-bold text-slate-500 mb-1">Mulai <span
                            class="font-normal normal-case">(Kosongkan jika ikut Normal)</span></label><input
                        type="time" id="jumat_mulai_jumat" name="mulai_jumat" class="w-full border rounded-lg p-2">
                </div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1">Selesai</label><input type="time"
                        id="jumat_selesai_jumat" name="selesai_jumat" class="w-full border rounded-lg p-2"></div>
            </div>
            <div><label class="block text-xs font-bold text-slate-500 mb-1">Kegiatan</label><select
                    id="jumat_tipe_jumat" name="tipe_jumat" class="w-full border rounded-lg p-2">
                    <option value="Belajar">Belajar</option>
                    <option value="Istirahat">Istirahat</option>
                </select></div>
            <button type="submit"
                class="w-full bg-emerald-600 text-white font-bold py-3 rounded-xl hover:bg-emerald-700">UPDATE
                JUMAT</button>
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

// 1. POP UP KHUSUS NORMAL
function openEditNormal(id, jam, mN, sN, tN, mS, sS, tS, mJ, sJ, tJ) {
    document.getElementById('form-edit-normal').action = `/master-waktu/${id}`;
    document.getElementById('norm_jam_ke').value = jam;
    // Yang kelihatan
    document.getElementById('norm_waktu_mulai').value = mN;
    document.getElementById('norm_waktu_selesai').value = sN;
    document.getElementById('norm_tipe').value = tN;
    // Yang disembunyiin
    document.getElementById('norm_mulai_senin').value = mS;
    document.getElementById('norm_selesai_senin').value = sS;
    document.getElementById('norm_tipe_senin').value = tS || 'Belajar';
    document.getElementById('norm_mulai_jumat').value = mJ;
    document.getElementById('norm_selesai_jumat').value = sJ;
    document.getElementById('norm_tipe_jumat').value = tJ || 'Belajar';
    openModal('modaledit_normal');
}

// 2. POP UP KHUSUS SENIN
function openEditSenin(id, jam, mN, sN, tN, mS, sS, tS, mJ, sJ, tJ) {
    document.getElementById('form-edit-senin').action = `/master-waktu/${id}`;
    document.getElementById('senin_jam_ke').value = jam;
    // Yang kelihatan
    document.getElementById('senin_mulai_senin').value = mS;
    document.getElementById('senin_selesai_senin').value = sS;
    document.getElementById('senin_tipe_senin').value = tS || 'Belajar';
    // Yang disembunyiin
    document.getElementById('senin_waktu_mulai').value = mN;
    document.getElementById('senin_waktu_selesai').value = sN;
    document.getElementById('senin_tipe').value = tN;
    document.getElementById('senin_mulai_jumat').value = mJ;
    document.getElementById('senin_selesai_jumat').value = sJ;
    document.getElementById('senin_tipe_jumat').value = tJ || 'Belajar';
    openModal('modaledit_senin');
}

// 3. POP UP KHUSUS JUMAT
function openEditJumat(id, jam, mN, sN, tN, mS, sS, tS, mJ, sJ, tJ) {
    document.getElementById('form-edit-jumat').action = `/master-waktu/${id}`;
    document.getElementById('jumat_jam_ke').value = jam;
    // Yang kelihatan
    document.getElementById('jumat_mulai_jumat').value = mJ;
    document.getElementById('jumat_selesai_jumat').value = sJ;
    document.getElementById('jumat_tipe_jumat').value = tJ || 'Belajar';
    // Yang disembunyiin
    document.getElementById('jumat_waktu_mulai').value = mN;
    document.getElementById('jumat_waktu_selesai').value = sN;
    document.getElementById('jumat_tipe').value = tN;
    document.getElementById('jumat_mulai_senin').value = mS;
    document.getElementById('jumat_selesai_senin').value = sS;
    document.getElementById('jumat_tipe_senin').value = tS || 'Belajar';
    openModal('modaledit_jumat');
}

window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.replace('flex', 'hidden');
    }
}
</script>
@endpush