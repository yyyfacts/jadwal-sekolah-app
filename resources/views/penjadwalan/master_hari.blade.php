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

    {{-- UNIFIED CARD --}}
    <div
        class="bg-white rounded-[2rem] border border-slate-100 shadow-[0_10px_40px_-10px_rgba(0,0,0,0.08)] flex flex-col flex-1 overflow-hidden">
        <div class="px-8 pt-8 pb-6 bg-white shrink-0 z-20">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-6">
                <div class="flex gap-3 items-start">
                    <div class="w-2.5 h-8 bg-indigo-600 rounded-full mt-0.5"></div>
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800 tracking-tight">Master Hari</h1>
                        <p class="text-slate-500 text-sm mt-1 font-medium">Kelola data status hari aktif dan pengaturan
                            slot waktu.</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="openModal('modaltambah')"
                        class="px-6 py-2.5 font-bold text-white transition-all duration-300 bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-md flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        <span class="text-sm uppercase tracking-wide">Tambah</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- TABLE SECTION --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white px-2">
            <table class="w-full text-left border-separate border-spacing-0 min-w-[600px]">
                <thead class="bg-white sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th
                            class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider w-[30%] border-b-2 border-slate-100 bg-white">
                            Nama Hari</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-[25%] border-b-2 border-slate-100 bg-white">
                            Total Slot (Jam Ke)</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-[20%] border-b-2 border-slate-100 bg-white">
                            Status</th>
                        <th
                            class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right w-[25%] border-b-2 border-slate-100 bg-white pr-8">
                            Aksi & Atur Waktu</th>
                    </tr>
                </thead>

                <tbody id="tbody-hari-main" class="divide-y divide-slate-100/80">
                    @forelse($haris as $h)
                    <tr class="group hover:bg-slate-50/50 transition-colors duration-200">
                        <td class="px-8 py-5">
                            <span class="font-bold text-slate-700 text-sm">{{ $h->nama_hari }}</span>
                        </td>
                        <td class="px-6 py-5 text-center">
                            <span
                                class="bg-purple-50 text-purple-700 font-bold px-3 py-1 rounded-md text-xs border border-purple-100">
                                {{ $h->waktuHaris->count() }} Slot Terdaftar
                            </span>
                        </td>
                        <td class="px-6 py-5 text-center">
                            @if($h->is_active)
                            <div
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 shadow-sm">
                                <span class="relative flex h-2 w-2"><span
                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span
                                        class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span></span>
                                AKTIF
                            </div>
                            @else
                            <span
                                class="text-slate-400 font-semibold text-xs bg-slate-100 border border-slate-200 px-3 py-1 rounded-full">LIBUR</span>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-right pr-8">
                            <div class="flex items-center justify-end gap-2">

                                {{-- Tombol Atur Waktu Jam Ke- --}}
                                <button type="button" onclick="bukaModalWaktu({{ $h->id }}, '{{ $h->nama_hari }}')"
                                    class="flex items-center gap-2 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition shadow-sm text-xs font-bold uppercase tracking-wider"
                                    title="Atur Jam Ke">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg> Atur Jam
                                </button>

                                {{-- Tombol Edit Status --}}
                                <button type="button"
                                    onclick="openEditModal({{ $h->id }}, '{{ $h->nama_hari }}', {{ $h->is_active ? 1 : 0 }})"
                                    class="p-2 border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-300 rounded-lg transition-colors bg-white"
                                    title="Edit Status">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg>
                                </button>

                                <form action="{{ route('master-hari.destroy', $h->id) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Yakin hapus data hari ini?');">
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
                        <td colspan="4" class="px-6 py-20 text-center text-slate-400">Belum ada data hari aktif.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODALS --}}

{{-- Modal Tambah Hari --}}
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
                </select>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-bold py-3.5 rounded-xl shadow-lg transition duration-300 uppercase tracking-wider text-xs">SIMPAN
                DATA</button>
        </form>
    </div>
</div>

{{-- Modal Edit Status Hari --}}
<div id="modaledit"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99] hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-amber-100 bg-amber-50 flex justify-between items-center">
            <h3 class="font-bold text-amber-800">Edit Status Hari</h3>
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
                <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Status Hari</label>
                <select id="edit_is_active" name="is_active"
                    class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-amber-500 outline-none text-sm transition">
                    <option value="1">Aktif / Masuk</option>
                    <option value="0">Libur</option>
                </select>
            </div>
            <button type="submit"
                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold mt-2 py-3.5 rounded-xl shadow-lg transition">UPDATE
                STATUS</button>
        </form>
    </div>
</div>

{{-- MODAL POP-UP ATUR JAM KE- (SANGAT PENTING UNTUK AI SCHEDULER) --}}
<div id="modalwaktu"
    class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[99] hidden items-center justify-center p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 shrink-0">
            <div>
                <h3 class="font-extrabold text-slate-800 text-lg flex items-center gap-2">
                    <span class="w-1.5 h-5 bg-purple-600 rounded-full"></span> Atur Jam & Istirahat
                </h3>
                <p id="modalwaktu-title" class="text-xs text-slate-500 font-medium mt-0.5">Hari: -</p>
            </div>
            <button type="button" onclick="closeModal('modalwaktu')"
                class="text-slate-400 hover:text-red-500 text-3xl leading-none">&times;</button>
        </div>

        <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-slate-50/50">
            <div
                class="bg-blue-50 border border-blue-100 text-blue-800 text-xs p-3 rounded-xl mb-4 font-medium flex gap-2">
                <span class="text-lg leading-none">💡</span>
                <p><strong>Penting untuk AI:</strong> Tentukan mana kotak yang bertipe "Belajar" dan mana yang
                    "Istirahat/Upacara". AI Python hanya akan meletakkan jadwal pelajaran di kotak yang bertipe
                    <strong>Belajar</strong>.
                </p>
            </div>

            <form id="form-waktu-hari" method="POST">
                @csrf
                <table class="w-full text-left bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                    <thead class="bg-slate-100 text-xs text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 border-b">Jam Ke-</th>
                            <th class="px-4 py-3 border-b">Mulai</th>
                            <th class="px-4 py-3 border-b">Selesai</th>
                            <th class="px-4 py-3 border-b w-1/3">Tipe (Belajar / Non-Belajar)</th>
                            <th class="px-4 py-3 border-b text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="waktu-tbody" class="divide-y divide-slate-100">
                        {{-- Diisi via JavaScript AJAX --}}
                    </tbody>
                </table>

                <div class="mt-4 flex justify-center">
                    <button type="button" onclick="tambahBarisWaktu()"
                        class="flex items-center gap-2 px-4 py-2 border-2 border-dashed border-purple-300 text-purple-600 hover:bg-purple-50 hover:border-purple-500 font-bold text-sm rounded-xl transition">
                        <span>+ Tambah Kotak Jam</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="p-4 border-t border-slate-100 bg-white shrink-0 flex justify-end">
            <button type="button" onclick="document.getElementById('form-waktu-hari').submit()"
                class="px-6 py-2.5 bg-slate-900 hover:bg-purple-600 text-white text-sm font-bold tracking-wider uppercase rounded-xl shadow-lg transition">Simpan
                Aturan Waktu</button>
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

// LOGIK UNTUK POP-UP ATUR WAKTU
let rowCount = 0;

async function bukaModalWaktu(idHari, namaHari) {
    document.getElementById('modalwaktu-title').innerText = `Hari: ${namaHari}`;
    document.getElementById('form-waktu-hari').action = `/master-hari/${idHari}/waktu`;
    const tbody = document.getElementById('waktu-tbody');
    tbody.innerHTML =
        '<tr><td colspan="5" class="text-center py-8 text-sm text-slate-400">⏳ Sedang memuat data...</td></tr>';

    openModal('modalwaktu');

    try {
        const response = await fetch(`/master-hari/${idHari}/waktu`);
        const data = await response.json();

        tbody.innerHTML = '';
        rowCount = 0;

        if (data.length === 0) {
            // Kalau kosong, otomatis kasih 1 baris
            tambahBarisWaktu();
        } else {
            data.forEach(item => {
                tambahBarisWaktu(item.jam_ke, item.waktu_mulai, item.waktu_selesai, item.tipe);
            });
        }
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-red-500">Gagal memuat data</td></tr>';
    }
}

function tambahBarisWaktu(jamKe = '', mulai = '', selesai = '', tipe = 'Belajar') {
    rowCount++;
    // Otomatis ngisi angka kalau kosong
    if (jamKe === '') jamKe = document.querySelectorAll('.jam-input').length + 1;

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="px-4 py-2">
            <input type="number" name="jam_ke[]" value="${jamKe}" class="jam-input w-16 px-2 py-1.5 text-center font-bold border border-slate-200 rounded-lg outline-none focus:border-purple-500 text-sm" required>
        </td>
        <td class="px-4 py-2">
            <input type="time" name="waktu_mulai[]" value="${mulai ? mulai.substring(0,5) : ''}" class="w-full px-2 py-1.5 border border-slate-200 rounded-lg outline-none focus:border-purple-500 text-sm">
        </td>
        <td class="px-4 py-2">
            <input type="time" name="waktu_selesai[]" value="${selesai ? selesai.substring(0,5) : ''}" class="w-full px-2 py-1.5 border border-slate-200 rounded-lg outline-none focus:border-purple-500 text-sm">
        </td>
        <td class="px-4 py-2">
            <select name="tipe[]" class="w-full px-2 py-1.5 font-bold border border-slate-200 rounded-lg outline-none focus:border-purple-500 text-sm ${tipe === 'Belajar' ? 'text-emerald-600' : 'text-amber-600'}">
                <option value="Belajar" ${tipe === 'Belajar' ? 'selected' : ''}>📚 Belajar</option>
                <option value="Istirahat" ${tipe === 'Istirahat' ? 'selected' : ''}>☕ Istirahat</option>
                <option value="Upacara" ${tipe === 'Upacara' ? 'selected' : ''}>🚩 Upacara</option>
                <option value="Sholat" ${tipe === 'Sholat' ? 'selected' : ''}>🕌 Sholat</option>
                <option value="Lainnya" ${tipe === 'Lainnya' ? 'selected' : ''}>⚙️ Lainnya</option>
            </select>
        </td>
        <td class="px-4 py-2 text-center">
            <button type="button" onclick="this.closest('tr').remove()" class="p-1.5 bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-lg transition" title="Hapus Kotak">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </td>
    `;

    // Ganti warna dropdown saat dipilih
    const select = tr.querySelector('select');
    select.addEventListener('change', function() {
        if (this.value === 'Belajar') {
            this.classList.replace('text-amber-600', 'text-emerald-600');
        } else {
            this.classList.replace('text-emerald-600', 'text-amber-600');
        }
    });

    document.getElementById('waktu-tbody').appendChild(tr);
}
</script>
@endpush