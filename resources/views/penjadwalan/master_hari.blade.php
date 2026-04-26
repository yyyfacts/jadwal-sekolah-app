@extends('layouts.app')

@section('content')
{{-- BACKGROUND POLOSAN --}}
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

{{-- CONTAINER UTAMA (SUPER PADAT & FULL WIDTH) --}}
<div class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 h-[calc(100vh-4rem)] pb-2 pt-2 flex flex-col relative z-0">

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-emerald-50 border border-emerald-100 rounded-lg shadow-sm text-emerald-800 shrink-0">
        <span class="font-bold text-[11px]">✅ {{ session('success') }}</span>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700">&times;</button>
    </div>
    @endif

    {{-- KARTU UTAMA --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-md flex flex-col flex-1 overflow-hidden">
        <div class="px-4 py-3 bg-white shrink-0 z-20 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex gap-2 items-center">
                    <div class="w-1.5 h-6 bg-indigo-600 rounded-full"></div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 leading-none">Master Hari & Waktu</h1>
                        <p class="text-slate-500 text-[10px] mt-0.5">Kelola hari aktif & struktur slot jam pelajaran.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="openModal('modaltambah')"
                        class="px-4 py-1.5 bg-indigo-600 text-white rounded-lg font-bold text-[10px] uppercase shadow-sm flex items-center gap-1 hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg> Tambah Hari
                    </button>
                </div>
            </div>
        </div>

        {{-- TABEL DATA --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead class="bg-slate-50 sticky top-0 shadow-sm z-10">
                    <tr>
                        <th
                            class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[20%] border-b border-slate-200">
                            Nama Hari</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-[20%] border-b border-slate-200">
                            Total Slot</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-[20%] border-b border-slate-200">
                            Status</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-[20%] border-b border-slate-200">
                            Waktu Sistem</th>
                        <th
                            class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right w-[20%] border-b border-slate-200">
                            Aksi & Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($haris as $h)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-4 py-2 align-middle font-bold text-slate-700 text-[11px]">{{ $h->nama_hari }}</td>
                        <td class="px-3 py-2 align-middle text-center">
                            <span
                                class="bg-purple-50 text-purple-700 font-bold px-2 py-0.5 rounded border border-purple-100 text-[10px]">{{ $h->waktuHaris->count() }}
                                Slot</span>
                        </td>
                        <td class="px-3 py-2 align-middle text-center">
                            @if($h->is_active)
                            <div
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 border border-emerald-200 text-[9px] font-bold">
                                <span class="relative flex h-1.5 w-1.5"><span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span
                                        class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>
                                AKTIF
                            </div>
                            @else
                            <span
                                class="text-slate-400 font-bold text-[9px] bg-slate-100 border border-slate-200 px-2 py-0.5 rounded">LIBUR</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center align-middle">
                            <div class="flex flex-col items-center gap-0.5 text-[9px]">
                                <span class="text-slate-400" title="Dibuat: {{ $h->created_at }}">➕
                                    {{ $h->created_at ? $h->created_at->format('d/m/Y') : '-' }}</span>
                                <span class="text-indigo-400" title="Diperbarui: {{ $h->updated_at }}">🔄
                                    {{ $h->updated_at ? $h->updated_at->format('d/m/Y') : '-' }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-2 align-middle text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <button onclick="bukaModalWaktu({{ $h->id }}, '{{ $h->nama_hari }}')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 bg-purple-600 text-white rounded-lg font-bold text-[9px] uppercase hover:bg-purple-700 shadow-sm transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg> Waktu
                                </button>
                                <button
                                    onclick="openEditModal({{ $h->id }}, '{{ $h->nama_hari }}', {{ $h->is_active ? 1 : 0 }})"
                                    class="p-1.5 border border-slate-200 rounded-lg text-slate-400 hover:text-amber-500 hover:border-amber-300 bg-white transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>
                                <form action="{{ route('master-hari.destroy', $h->id) }}" method="POST"
                                    class="inline m-0" onsubmit="return confirm('Hapus permanen hari ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-1.5 border border-slate-200 rounded-lg text-slate-400 hover:text-red-500 hover:border-red-300 bg-white transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <td colspan="5" class="px-4 py-12 text-center text-[11px] text-slate-400">Belum ada data hari.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- AREA MODAL --}}

{{-- 1. Modal Tambah Hari --}}
<div id="modaltambah"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99] hidden items-center justify-center p-2">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-xs overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-1.5"><span
                    class="w-1 h-4 bg-indigo-600 rounded-full"></span> Tambah Hari</h3>
            <button onclick="closeModal('modaltambah')"
                class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
        </div>
        <form action="{{ route('master-hari.store') }}" method="POST" class="p-4">
            @csrf
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Pilih Hari</label>
            <select name="nama_hari"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs mb-3 outline-none focus:ring-2 focus:ring-indigo-500"
                required>
                <option value="" disabled selected>-- Pilih --</option>
                <option value="Senin">Senin</option>
                <option value="Selasa">Selasa</option>
                <option value="Rabu">Rabu</option>
                <option value="Kamis">Kamis</option>
                <option value="Jumat">Jumat</option>
                <option value="Sabtu">Sabtu</option>
            </select>
            <button type="submit"
                class="w-full bg-slate-900 text-white font-bold py-2 rounded-lg text-[10px] uppercase hover:bg-indigo-600 transition">Simpan</button>
        </form>
    </div>
</div>

{{-- 2. Modal Ubah Status Hari --}}
<div id="modaledit"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99] hidden items-center justify-center p-2">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-xs overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-amber-100 bg-amber-50 flex justify-between items-center">
            <h3 class="font-bold text-amber-800 text-sm flex items-center gap-1.5"><span
                    class="w-1 h-4 bg-amber-500 rounded-full"></span> Status Hari</h3>
            <button onclick="closeModal('modaledit')"
                class="text-amber-400 hover:text-amber-600 text-lg leading-none">&times;</button>
        </div>
        <form id="form-edit-hari" method="POST" class="p-4">
            @csrf @method('PUT')
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Hari</label>
            <input type="text" id="edit_nama_hari" name="nama_hari"
                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-xs bg-slate-100 mb-2 cursor-not-allowed font-bold text-slate-500"
                readonly>

            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Status</label>
            <select id="edit_is_active" name="is_active"
                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs mb-3 outline-none focus:ring-2 focus:ring-amber-500">
                <option value="1">Aktif / Masuk</option>
                <option value="0">Libur</option>
            </select>
            <button type="submit"
                class="w-full bg-amber-500 text-white font-bold py-2 rounded-lg text-[10px] uppercase hover:bg-amber-600 transition">Perbarui</button>
        </form>
    </div>
</div>

{{-- 3. MODAL ATUR SLOT WAKTU (PENTING UNTUK AI) --}}
<div id="modalwaktu"
    class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[99] hidden items-center justify-center p-2 transition-all">
    <div
        class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex justify-between items-center shrink-0">
            <div>
                <h3 class="font-extrabold text-slate-800 text-sm flex items-center gap-1.5">
                    <span class="w-1.5 h-4 bg-purple-600 rounded-full"></span> Atur Jam & Istirahat
                </h3>
                <p id="modalwaktu-title" class="text-[10px] text-slate-500 font-medium mt-0.5">Hari: -</p>
            </div>
            <button onclick="closeModal('modalwaktu')"
                class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-3 sm:p-4 bg-slate-50/50">
            <div
                class="bg-blue-50 border border-blue-100 text-blue-800 text-[10px] p-2 rounded-lg mb-3 font-medium flex gap-2 items-start">
                <span class="text-sm leading-none">💡</span>
                <p><strong>Penting untuk AI:</strong> Tentukan tipe kotak <strong>"Belajar"</strong> atau
                    <strong>"Istirahat/Upacara"</strong>. AI hanya meletakkan jadwal di kotak tipe Belajar.
                </p>
            </div>

            <form id="form-waktu-hari" method="POST">
                @csrf
                <table class="w-full text-left bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                    <thead class="bg-slate-100 text-[10px] text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-2 py-2 border-b text-center">Jam Ke</th>
                            <th class="px-2 py-2 border-b">Mulai</th>
                            <th class="px-2 py-2 border-b">Selesai</th>
                            <th class="px-2 py-2 border-b w-1/3">Tipe Slot</th>
                            <th class="px-2 py-2 border-b text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="waktu-tbody" class="divide-y divide-slate-100 text-[11px]">
                        {{-- AJAX Injects Here --}}
                    </tbody>
                </table>

                <div class="mt-3 flex justify-center">
                    <button type="button" onclick="tambahBarisWaktu()"
                        class="flex items-center gap-1 px-3 py-1.5 border border-dashed border-purple-300 text-purple-600 bg-white hover:bg-purple-50 font-bold text-[10px] rounded-lg transition uppercase">
                        + Tambah Baris Waktu
                    </button>
                </div>
            </form>
        </div>

        <div class="p-3 border-t border-slate-100 bg-white shrink-0 flex justify-end">
            <button type="button" onclick="document.getElementById('form-waktu-hari').submit()"
                class="px-6 py-2 bg-slate-900 hover:bg-purple-600 text-white text-[10px] font-bold uppercase rounded-lg shadow-md transition">
                Simpan Waktu
            </button>
        </div>
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

function openEditModal(id, nama, active) {
    document.getElementById('form-edit-hari').action = `/master-hari/${id}`;
    document.getElementById('edit_nama_hari').value = nama;
    document.getElementById('edit_is_active').value = active;
    openModal('modaledit');
}

let rowCount = 0;

async function bukaModalWaktu(idHari, namaHari) {
    document.getElementById('modalwaktu-title').innerText = `Hari: ${namaHari}`;
    document.getElementById('form-waktu-hari').action = `/master-hari/${idHari}/waktu`;
    const tbody = document.getElementById('waktu-tbody');
    tbody.innerHTML =
        '<tr><td colspan="5" class="text-center py-6 text-[10px] text-slate-400">Memuat data...</td></tr>';

    openModal('modalwaktu');

    try {
        const res = await fetch(`/master-hari/${idHari}/waktu`);
        const data = await res.json();
        tbody.innerHTML = '';
        rowCount = 0;

        if (data.length === 0) {
            tambahBarisWaktu();
        } else {
            data.forEach(item => {
                tambahBarisWaktu(item.jam_ke, item.waktu_mulai, item.waktu_selesai, item.tipe);
            });
        }
    } catch (err) {
        tbody.innerHTML =
            '<tr><td colspan="5" class="text-center py-4 text-[10px] text-red-500">Gagal memuat.</td></tr>';
    }
}

function tambahBarisWaktu(jamKe = '', mulai = '', selesai = '', tipe = 'Belajar') {
    rowCount++;
    if (jamKe === '') jamKe = document.querySelectorAll('.jam-input').length + 1;

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="px-2 py-1.5 text-center">
            <input type="number" name="jam_ke[]" value="${jamKe}" class="jam-input w-12 px-1 py-1 text-center font-bold border border-slate-200 rounded text-xs outline-none focus:border-purple-500 bg-slate-50" required>
        </td>
        <td class="px-2 py-1.5">
            <input type="time" name="waktu_mulai[]" value="${mulai ? mulai.substring(0,5) : ''}" class="w-full px-1.5 py-1 border border-slate-200 rounded text-xs outline-none focus:border-purple-500">
        </td>
        <td class="px-2 py-1.5">
            <input type="time" name="waktu_selesai[]" value="${selesai ? selesai.substring(0,5) : ''}" class="w-full px-1.5 py-1 border border-slate-200 rounded text-xs outline-none focus:border-purple-500">
        </td>
        <td class="px-2 py-1.5">
            <select name="tipe[]" class="w-full px-1.5 py-1 font-bold border border-slate-200 rounded text-[10px] outline-none focus:border-purple-500 ${tipe === 'Belajar' ? 'text-emerald-600' : 'text-amber-600'}">
                <option value="Belajar" ${tipe === 'Belajar' ? 'selected' : ''}>Belajar</option>
                <option value="Istirahat" ${tipe === 'Istirahat' ? 'selected' : ''}>Istirahat</option>
                <option value="Upacara" ${tipe === 'Upacara' ? 'selected' : ''}>Upacara</option>
                <option value="Sholat" ${tipe === 'Sholat' ? 'selected' : ''}>Sholat</option>
                <option value="Senam" ${tipe === 'Senam' ? 'selected' : ''}>Senam</option>
            </select>
        </td>
        <td class="px-2 py-1.5 text-center">
            <button type="button" onclick="this.closest('tr').remove()" class="p-1 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded transition" title="Hapus">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </td>
    `;

    const select = tr.querySelector('select');
    select.addEventListener('change', function() {
        this.classList.replace('text-amber-600', 'text-emerald-600');
        this.classList.replace('text-emerald-600', 'text-amber-600');
        if (this.value === 'Belajar') this.classList.add('text-emerald-600');
        else this.classList.add('text-amber-600');
    });

    document.getElementById('waktu-tbody').appendChild(tr);
}
</script>
@endpush