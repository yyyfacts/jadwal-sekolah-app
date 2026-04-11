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
                        <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Master Hari</h1>
                        <p class="text-slate-500 text-sm mt-1 font-medium">Kelola data hari aktif dan batas maksimal jam
                            mengajar.</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div
                        class="hidden md:flex items-center px-5 py-2.5 bg-white border border-slate-200 rounded-full shadow-sm">
                        <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">
                            Total: <span
                                class="text-indigo-600 text-sm ml-1 font-extrabold">{{ $haris->count() }}</span>
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
                <input type="text" id="search-hari-main" oninput="searchMainTable()"
                    class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl leading-5 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm transition"
                    placeholder="Cari Hari...">
            </div>
        </div>

        {{-- 2. TABLE SECTION --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white px-2">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-white sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider w-[30%]">Nama
                            Hari</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-[25%]">
                            Batas Jam</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-[25%]">
                            Status</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right w-[20%]">
                            Aksi</th>
                    </tr>
                </thead>

                <tbody id="tbody-hari-main" class="divide-y divide-slate-100/80">
                    @forelse($haris as $h)
                    <tr class="group hover:bg-slate-50/50 transition-colors duration-200"
                        data-filter="{{ strtolower($h->nama_hari) }}">
                        <td class="px-8 py-5">
                            <span class="font-bold text-slate-700 text-sm">{{ $h->nama_hari }}</span>
                        </td>

                        <td class="px-6 py-5 text-center">
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-600 border border-indigo-100 text-xs font-bold">
                                Maks: {{ $h->max_jam }} Jam
                            </span>
                        </td>

                        <td class="px-6 py-5 text-center">
                            @if($h->is_active)
                            <div
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 shadow-sm">
                                <span class="relative flex h-2 w-2">
                                    <span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                AKTIF
                            </div>
                            @else
                            <span
                                class="text-slate-400 font-semibold text-xs bg-slate-100 px-3 py-1 rounded-full">LIBUR</span>
                            @endif
                        </td>

                        <td class="px-6 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Tombol Edit Vanilla JS yang Pasti Jalan --}}
                                <button type="button"
                                    onclick="openEditModal({{ $h->id }}, '{{ $h->nama_hari }}', {{ $h->max_jam }}, {{ $h->is_active ? 1 : 0 }})"
                                    class="p-2 border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-300 rounded-lg transition-colors bg-white"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>

                                <form action="{{ route('master-hari.destroy', $h->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Yakin hapus data hari {{ $h->nama_hari }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-2 border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-300 rounded-lg transition-colors bg-white"
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
                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center text-slate-400 font-medium">Belum ada data hari
                            aktif.</td>
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
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-slate-800 flex items-center gap-2"><span
                    class="w-1.5 h-5 bg-indigo-600 rounded-full"></span> Tambah Hari Aktif</h3>
            <button onclick="closeModal('modaltambah')"
                class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
        </div>
        <form action="{{ route('master-hari.store') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Hari</label>
                <select name="nama_hari"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition"
                    required>
                    <option value="" disabled selected>-- Pilih Hari --</option>
                    <option value="Senin">Senin</option>
                    <option value="Selasa">Selasa</option>
                    <option value="Rabu">Rabu</option>
                    <option value="Kamis">Kamis</option>
                    <option value="Jumat">Jumat</option>
                    <option value="Sabtu">Sabtu</option>
                    <option value="Minggu">Minggu</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Batas Jam Mengajar</label>
                <input type="number" name="max_jam"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none text-sm transition"
                    placeholder="Contoh: 10" min="1" required>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold py-3.5 rounded-xl shadow-lg transition duration-300 uppercase tracking-wider text-xs">SIMPAN
                DATA</button>
        </form>
    </div>
</div>

{{-- 2. Modal Edit --}}
<div id="modaledit"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-amber-100 bg-amber-50 flex justify-between items-center">
            <h3 class="font-bold text-amber-800 flex items-center gap-2"><span
                    class="w-1.5 h-5 bg-amber-500 rounded-full"></span> Edit Konfigurasi Hari</h3>
            <button type="button" onclick="closeModal('modaledit')"
                class="text-amber-400 hover:text-amber-600 text-2xl leading-none">&times;</button>
        </div>

        <form id="form-edit-hari" method="POST" class="p-6 space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Hari</label>
                <input type="text" id="edit_nama_hari" name="nama_hari"
                    class="w-full border border-slate-200 rounded-xl px-4 py-3 bg-slate-100 text-slate-500 text-sm outline-none font-semibold cursor-not-allowed"
                    readonly>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Batas Jam Mengajar</label>
                <input type="number" id="edit_max_jam" name="max_jam"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 outline-none text-sm transition"
                    required min="1">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Status Hari</label>
                <select id="edit_is_active" name="is_active"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 outline-none text-sm transition">
                    <option value="1">Aktif / Masuk</option>
                    <option value="0">Libur</option>
                </select>
            </div>
            <button type="submit"
                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold mt-2 py-3.5 rounded-xl shadow-lg transition duration-300 uppercase tracking-wider text-xs">UPDATE
                DATA</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function searchMainTable() {
    const input = document.getElementById('search-hari-main').value.toLowerCase();
    const rows = document.querySelectorAll('#tbody-hari-main tr[data-filter]');
    let hasResult = false;

    rows.forEach(row => {
        if (row.getAttribute('data-filter').includes(input)) {
            row.style.display = "";
            hasResult = true;
        } else {
            row.style.display = "none";
        }
    });
}

function openModal(id) {
    document.getElementById(id).classList.replace('hidden', 'flex');
}

function closeModal(id) {
    document.getElementById(id).classList.replace('flex', 'hidden');
}

function openEditModal(id, nama, max, active) {
    document.getElementById('form-edit-hari').action = `/master-hari/${id}`;
    document.getElementById('edit_nama_hari').value = nama;
    document.getElementById('edit_max_jam').value = max;
    document.getElementById('edit_is_active').value = active;
    openModal('modaledit');
}

window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.replace('flex', 'hidden');
    }
}
</script>
@endpush