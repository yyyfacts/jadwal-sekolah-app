@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

{{-- CONTAINER UTAMA (SUPER PADAT & FULL WIDTH SEPERTI GURU) --}}
<div class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 h-[calc(100vh-4rem)] pb-2 pt-2 flex flex-col relative z-0">

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
                    <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 leading-none">Mata Pelajaran</h1>
                        <p class="text-slate-500 text-[10px] mt-0.5 font-medium">Manajemen kurikulum & batas jam.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div
                        class="hidden md:flex items-center px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg shadow-sm">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Total: <span
                                class="text-blue-600 font-extrabold ml-1">{{ $mapels->count() }}</span></span>
                    </div>

                    <div class="relative w-48">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none"><svg
                                class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg></div>
                        <input type="text" id="search-mapel-main" oninput="searchMainTable()"
                            class="block w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs outline-none focus:border-blue-500 focus:bg-white transition"
                            placeholder="Cari Mapel...">
                    </div>

                    <button onclick="openModal('modaltambah')"
                        class="px-4 py-1.5 bg-blue-600 text-white rounded-lg font-bold text-[10px] uppercase shadow-sm flex items-center gap-1.5 hover:bg-blue-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg> Tambah
                    </button>
                </div>
            </div>
        </div>

        {{-- 2. TABEL DATA (FLEX-1 MENGISI RUANG TERSISA) --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th
                            class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-12 border-b border-slate-200">
                            No</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[35%] border-b border-slate-200">
                            Identitas Mapel</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[25%] border-b border-slate-200 text-center">
                            Total Distribusi</th>
                        <th
                            class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right w-[35%] border-b border-slate-200">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-mapel-main" class="divide-y divide-slate-100">
                    @forelse($mapels as $index => $m)
                    <tr class="hover:bg-slate-50/80 transition-colors"
                        data-filter="{{ strtolower($m->nama_mapel) }} {{ strtolower($m->kode_mapel) }}">
                        <td class="px-4 py-2 text-center text-[11px] font-medium text-slate-400 align-middle">
                            {{ $index + 1 }}</td>
                        <td class="px-3 py-2 align-middle">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-8 w-8 shrink-0 rounded-full bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center font-bold text-[10px] shadow-sm">
                                    {{ substr($m->nama_mapel, 0, 1) }}
                                </div>
                                <div class="leading-tight">
                                    <div class="font-bold text-slate-800 text-xs">{{ $m->nama_mapel }}
                                        @if($m->batas_maksimal_jam) <span
                                            class="text-[8px] font-bold bg-slate-100 text-slate-500 px-1 py-0.5 rounded ml-1 border border-slate-200">Maks:
                                            {{ $m->batas_maksimal_jam }}</span> @endif</div>
                                    <div
                                        class="inline-block px-1.5 py-0.5 mt-0.5 rounded bg-slate-100 text-slate-500 font-bold text-[9px] uppercase border border-slate-200">
                                        {{ $m->kode_mapel }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2 align-middle text-center">
                            <span
                                class="inline-flex font-bold text-[10px] bg-blue-50 text-blue-700 px-2 py-1 rounded-md border border-blue-100">{{ $m->total_jam_terdistribusi ?: 0 }}
                                Jam Total</span>
                        </td>
                        <td class="px-4 py-2 text-right align-middle">
                            <div class="flex items-center justify-end gap-1.5">
                                <button onclick="openModal('modaljadwal{{ $m->id }}')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 bg-[#294269] text-white text-[10px] font-bold rounded-lg shadow-sm hover:bg-blue-900 transition"><svg
                                        class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                                        </path>
                                    </svg> Distribusi</button>
                                <button onclick="openModal('edit{{ $m->id }}')"
                                    class="p-1.5 border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-300 rounded-lg bg-white transition"><svg
                                        class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg></button>
                                <form action="{{ route('mapel.destroy', $m->id) }}" method="POST" class="inline m-0"
                                    onsubmit="return confirm('Hapus permanen mapel ini?')">@csrf
                                    @method('DELETE')<button type="submit"
                                        class="p-1.5 border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-300 rounded-lg bg-white transition"><svg
                                            class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg></button></form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-xs text-slate-400">Belum ada data mapel.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- AREA MODAL --}}
    <div id="modaltambah"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[999] hidden items-center justify-center p-2">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden border border-white/20">
            <div class="px-4 py-3 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-1.5"><span
                        class="w-1 h-4 bg-blue-600 rounded-full"></span> Tambah Mapel</h3>
                <button onclick="closeModal('modaltambah')"
                    class="text-slate-400 hover:text-slate-600 text-lg leading-none">&times;</button>
            </div>
            <form action="{{ route('mapel.store') }}" method="POST" class="p-4 space-y-3">
                @csrf
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label><input
                        type="text" name="nama_mapel"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none"
                        required></div>
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kode Mapel</label><input
                        type="text" name="kode_mapel"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs font-mono uppercase focus:ring-2 focus:ring-blue-500 outline-none"
                        required></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Maks Jam
                            (Opsional)</label><input type="number" name="batas_maksimal_jam"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Sifat
                            Batasan</label><select name="jenis_batas"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="soft">Fleksibel</option>
                            <option value="hard">Mutlak</option>
                        </select></div>
                </div>
                <button type="submit"
                    class="w-full bg-slate-900 hover:bg-blue-600 text-white font-bold py-2.5 rounded-lg text-[10px] uppercase mt-2 transition">Simpan</button>
            </form>
        </div>
    </div>

    @foreach($mapels as $m)
    <div id="edit{{ $m->id }}"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[999] hidden items-center justify-center p-2">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden border border-white/20">
            <div class="px-4 py-3 border-b border-amber-100 bg-amber-50 flex justify-between items-center">
                <h3 class="font-bold text-amber-800 text-sm flex items-center gap-1.5"><span
                        class="w-1 h-4 bg-amber-500 rounded-full"></span> Ubah Mapel</h3>
                <button onclick="closeModal('edit{{ $m->id }}')"
                    class="text-amber-400 hover:text-amber-600 text-lg leading-none">&times;</button>
            </div>
            <form action="{{ route('mapel.update', $m->id) }}" method="POST" class="p-4 space-y-3">
                @csrf @method('PUT')
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label><input
                        type="text" name="nama_mapel" value="{{ $m->nama_mapel }}"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 outline-none"
                        required></div>
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kode Mapel</label><input
                        type="text" name="kode_mapel" value="{{ $m->kode_mapel }}"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs font-mono uppercase focus:ring-2 focus:ring-amber-500 outline-none"
                        required></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Maks Jam</label><input
                            type="number" name="batas_maksimal_jam" value="{{ $m->batas_maksimal_jam }}"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 outline-none">
                    </div>
                    <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Sifat
                            Batasan</label><select name="jenis_batas"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-amber-500 outline-none">
                            <option value="soft" {{ $m->jenis_batas=='soft'?'selected':'' }}>Fleksibel</option>
                            <option value="hard" {{ $m->jenis_batas=='hard'?'selected':'' }}>Mutlak</option>
                        </select></div>
                </div>
                <button type="submit"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 rounded-lg text-[10px] uppercase mt-2 transition">Perbarui</button>
            </form>
        </div>
    </div>

    <div id="modaljadwal{{ $m->id }}"
        class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[999] hidden items-center justify-center p-2 sm:p-4 transition-opacity">
        <div
            class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[85vh] flex flex-col border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex justify-between bg-slate-50 shrink-0">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-blue-600 text-white rounded"><svg class="w-4 h-4" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg></div>
                    <div>
                        <h3 class="font-bold text-sm text-slate-800">{{ $m->nama_mapel }}</h3>
                        <p class="text-[10px] text-slate-500 font-medium">{{ $m->kode_mapel }}</p>
                    </div>
                </div>
                <button onclick="closeModal('modaljadwal{{ $m->id }}')"
                    class="text-slate-400 hover:text-red-500 text-2xl leading-none">&times;</button>
            </div>

            <div class="flex flex-col lg:flex-row h-full overflow-hidden">
                <div class="flex-1 flex flex-col h-full border-r border-slate-100 relative">
                    <div class="p-2 border-b bg-white shrink-0"><input type="text" id="search-{{ $m->id }}"
                            oninput="searchTable({{ $m->id }})" placeholder="Cari Kelas/Guru..."
                            class="w-full border border-slate-200 rounded px-2 py-1.5 text-xs outline-none focus:border-blue-500 bg-slate-50 focus:bg-white transition">
                    </div>
                    <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">
                        <table class="w-full text-[10px] border-collapse">
                            <thead class="bg-slate-50 text-slate-500 font-bold uppercase sticky top-0 shadow-sm">
                                <tr>
                                    <th class="px-3 py-2 text-left w-[30%] border-b border-slate-200">Kelas</th>
                                    <th class="px-3 py-2 text-left w-[40%] border-b border-slate-200">Guru Pengampu</th>
                                    <th class="px-3 py-2 text-center w-[15%] border-b border-slate-200">Jam</th>
                                    <th class="px-3 py-2 text-right w-[15%] border-b border-slate-200">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-mapel-{{ $m->id }}" class="divide-y divide-slate-100">
                                @foreach($m->jadwals as $jadwal)
                                <tr class="hover:bg-blue-50/50 group transition-colors">
                                    <td class="px-3 py-2 font-bold text-slate-700">
                                        {{ $jadwal->kelas->nama_kelas ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600 font-medium">
                                        {{ $jadwal->guru->nama_guru ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center align-middle">
                                        <div class="flex flex-col items-center"><span
                                                class="bg-white border border-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-bold text-[9px] shadow-sm">{{ $jadwal->jumlah_jam }}
                                                Jam</span><span
                                                class="text-[8px] text-slate-400 mt-0.5 uppercase tracking-wider font-bold">{{ $jadwal->tipe_jam }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-right align-middle">
                                        <div
                                            class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick="hapusJadwal({{ $jadwal->id }}, this)"
                                                class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition"><svg
                                                    class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg></button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="w-full lg:w-[280px] bg-slate-50 flex flex-col h-full border-t lg:border-t-0">
                    <div class="p-4 overflow-y-auto">
                        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                            <h4
                                class="font-bold text-[10px] text-slate-700 uppercase tracking-wider mb-3 border-b border-slate-100 pb-1.5 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Input Distribusi
                            </h4>
                            <form action="{{ route('mapel.simpanJadwal', $m->id) }}" method="POST">
                                @csrf
                                <div class="space-y-3.5 text-[10px]">
                                    <div><label class="font-bold text-slate-500 block mb-1">KELAS</label><select
                                            name="kelas_id"
                                            class="w-full border border-slate-200 rounded-lg px-2.5 py-2 bg-slate-50 outline-none focus:border-blue-500 focus:bg-white transition">@foreach($kelases
                                            as $k)<option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                            @endforeach</select></div>
                                    <div><label class="font-bold text-slate-500 block mb-1">GURU</label><select
                                            name="guru_id"
                                            class="w-full border border-slate-200 rounded-lg px-2.5 py-2 bg-slate-50 outline-none focus:border-blue-500 focus:bg-white transition">@foreach($gurus
                                            as $g)<option value="{{ $g->id }}">{{ $g->nama_guru }}</option>
                                            @endforeach</select></div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div><label class="font-bold text-slate-500 block mb-1">JAM</label><input
                                                type="number" name="jumlah_jam"
                                                class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 bg-slate-50 text-center outline-none focus:border-blue-500 focus:bg-white transition"
                                                min="1" max="10" required></div>
                                        <div><label class="font-bold text-slate-500 block mb-1">TIPE</label><select
                                                name="tipe_jam"
                                                class="w-full border border-slate-200 rounded-lg px-1.5 py-1.5 bg-slate-50 outline-none focus:border-blue-500 focus:bg-white transition">
                                                <option value="single">Satu(1x)</option>
                                                <option value="double">Dua(2x)</option>
                                            </select></div>
                                    </div>
                                    <div><label class="font-bold text-slate-500 block mb-1">STATUS</label><select
                                            name="status"
                                            class="w-full border border-slate-200 rounded-lg px-2.5 py-2 bg-slate-50 outline-none focus:border-blue-500 focus:bg-white transition">
                                            <option value="offline">Luring (Jadwal Tetap)</option>
                                            <option value="online">Daring</option>
                                        </select></div>
                                    <button type="submit"
                                        class="w-full bg-slate-900 hover:bg-blue-600 text-white py-2.5 rounded-lg font-bold uppercase tracking-wider mt-2 shadow-md transition">Simpan
                                        Jadwal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>
@endsection

@push('scripts')
<script>
function searchMainTable() {
    const input = document.getElementById('search-mapel-main').value.toLowerCase();
    document.querySelectorAll('#tbody-mapel-main tr[data-filter]').forEach(row => {
        row.style.display = row.getAttribute('data-filter').includes(input) ? "" : "none";
    });
}

function searchTable(id) {
    const filter = document.getElementById('search-' + id).value.toLowerCase();
    const rows = document.getElementById('tbody-mapel-' + id).getElementsByTagName('tr');
    for (let row of rows) {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
    }
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}
async function hapusJadwal(id, btn) {
    if (!confirm('Hapus jadwal ini?')) return;
    try {
        const res = await fetch(`/mapel/jadwal/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        if (res.ok) location.reload();
    } catch (e) {}
}
</script>
@endpush