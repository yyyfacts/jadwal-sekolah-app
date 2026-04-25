@extends('layouts.app')

@section('content')
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

<div class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 h-[calc(100vh-4rem)] pb-2 pt-2 flex flex-col relative z-0">

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-emerald-50 border border-emerald-100 rounded-lg shadow-sm text-emerald-800 shrink-0">
        <span class="font-bold text-xs">✅ {{ session('success') }}</span>
        <button @click="show = false" class="text-emerald-400">&times;</button>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-100 shadow-md flex flex-col flex-1 overflow-hidden">
        <div class="px-4 py-3 bg-white shrink-0 z-20 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex gap-2 items-center">
                    <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 leading-none">Mata Pelajaran</h1>
                        <p class="text-slate-500 text-[10px] mt-0.5">Manajemen kurikulum & batas jam.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div
                        class="hidden md:flex items-center px-3 py-1.5 bg-slate-50 border rounded-lg text-[10px] font-bold text-slate-500">
                        Total: <span class="text-blue-600 ml-1">{{ $mapels->count() }}</span>
                    </div>

                    <div class="relative w-48">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none"><svg
                                class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg></div>
                        <input type="text" id="search-mapel-main" oninput="searchMainTable()"
                            class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border rounded-lg text-xs outline-none"
                            placeholder="Cari Mapel...">
                    </div>

                    <button onclick="openModal('modaltambah')"
                        class="px-4 py-1.5 bg-blue-600 text-white rounded-lg font-bold text-[10px] uppercase shadow-sm flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 4v16m8-8H4"></path>
                        </svg> Tambah
                    </button>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-slate-50 sticky top-0 shadow-sm z-10">
                    <tr>
                        <th class="px-4 py-2 text-[10px] font-bold text-slate-500 text-center w-12 border-b">No</th>
                        <th class="px-3 py-2 text-[10px] font-bold text-slate-500 w-[35%] border-b">Identitas Mapel</th>
                        <th class="px-3 py-2 text-[10px] font-bold text-slate-500 w-[25%] border-b">Total Distribusi
                        </th>
                        <th class="px-4 py-2 text-[10px] font-bold text-slate-500 text-right w-[35%] border-b">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-mapel-main" class="divide-y divide-slate-100">
                    @forelse($mapels as $index => $m)
                    <tr class="hover:bg-slate-50/80"
                        data-filter="{{ strtolower($m->nama_mapel) }} {{ strtolower($m->kode_mapel) }}">
                        <td class="px-4 py-2 text-center text-[11px] text-slate-400 align-middle">{{ $index + 1 }}</td>
                        <td class="px-3 py-2 align-middle">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-8 w-8 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-[10px] border border-blue-200">
                                    {{ substr($m->nama_mapel, 0, 1) }}</div>
                                <div>
                                    <div class="font-bold text-slate-800 text-xs">{{ $m->nama_mapel }}
                                        @if($m->batas_maksimal_jam) <span
                                            class="text-[8px] bg-slate-100 px-1 rounded ml-1">Maks:
                                            {{ $m->batas_maksimal_jam }}</span> @endif</div>
                                    <div
                                        class="inline-block px-1.5 py-0.5 mt-0.5 rounded bg-slate-100 text-slate-500 font-bold text-[9px] uppercase border">
                                        {{ $m->kode_mapel }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2 align-middle">
                            <span
                                class="font-bold text-[11px] bg-blue-50 text-blue-700 px-2 py-1 rounded border border-blue-100">{{ $m->total_jam_terdistribusi ?: 0 }}
                                Jam Total</span>
                        </td>
                        <td class="px-4 py-2 text-right align-middle">
                            <div class="flex justify-end gap-1.5">
                                <button onclick="openModal('modaljadwal{{ $m->id }}')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 bg-[#294269] text-white text-[10px] font-bold rounded-lg"><svg
                                        class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                                        </path>
                                    </svg> Distribusi</button>
                                <button onclick="openModal('edit{{ $m->id }}')"
                                    class="p-1.5 border rounded-lg text-slate-400 hover:text-amber-500 hover:border-amber-300"><svg
                                        class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg></button>
                                <form action="{{ route('mapel.destroy', $m->id) }}" method="POST" class="inline m-0"
                                    onsubmit="return confirm('Hapus mapel?')">@csrf @method('DELETE')<button
                                        type="submit"
                                        class="p-1.5 border rounded-lg text-slate-400 hover:text-red-500 hover:border-red-300"><svg
                                            class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
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
</div>

@push('modals')
<div id="modaltambah"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[999] hidden items-center justify-center p-2">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden border">
        <div class="px-4 py-3 border-b bg-slate-50 flex justify-between">
            <h3 class="font-bold text-sm">Tambah Mapel</h3><button onclick="closeModal('modaltambah')"
                class="text-lg">&times;</button>
        </div>
        <form action="{{ route('mapel.store') }}" method="POST" class="p-4 space-y-3">
            @csrf
            <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama</label><input type="text"
                    name="nama_mapel" class="w-full border rounded-lg px-3 py-2 text-xs" required></div>
            <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kode</label><input type="text"
                    name="kode_mapel" class="w-full border rounded-lg px-3 py-2 text-xs font-mono uppercase" required>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Maks Jam</label><input
                        type="number" name="batas_maksimal_jam" class="w-full border rounded-lg px-3 py-2 text-xs">
                </div>
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Sifat</label><select
                        name="jenis_batas" class="w-full border rounded-lg px-3 py-2 text-xs">
                        <option value="soft">Fleksibel</option>
                        <option value="hard">Mutlak</option>
                    </select></div>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 text-white font-bold py-2.5 rounded-lg text-[10px] uppercase mt-2">Simpan</button>
        </form>
    </div>
</div>

@foreach($mapels as $m)
<div id="edit{{ $m->id }}"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[999] hidden items-center justify-center p-2">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden border">
        <div class="px-4 py-3 border-b bg-amber-50 flex justify-between">
            <h3 class="font-bold text-sm text-amber-800">Ubah Mapel</h3><button onclick="closeModal('edit{{ $m->id }}')"
                class="text-lg">&times;</button>
        </div>
        <form action="{{ route('mapel.update', $m->id) }}" method="POST" class="p-4 space-y-3">
            @csrf @method('PUT')
            <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama</label><input type="text"
                    name="nama_mapel" value="{{ $m->nama_mapel }}" class="w-full border rounded-lg px-3 py-2 text-xs"
                    required></div>
            <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kode</label><input type="text"
                    name="kode_mapel" value="{{ $m->kode_mapel }}"
                    class="w-full border rounded-lg px-3 py-2 text-xs font-mono uppercase" required></div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Maks Jam</label><input
                        type="number" name="batas_maksimal_jam" value="{{ $m->batas_maksimal_jam }}"
                        class="w-full border rounded-lg px-3 py-2 text-xs"></div>
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Sifat</label><select
                        name="jenis_batas" class="w-full border rounded-lg px-3 py-2 text-xs">
                        <option value="soft" {{ $m->jenis_batas=='soft'?'selected':'' }}>Fleksibel</option>
                        <option value="hard" {{ $m->jenis_batas=='hard'?'selected':'' }}>Mutlak</option>
                    </select></div>
            </div>
            <button type="submit"
                class="w-full bg-amber-500 text-white font-bold py-2.5 rounded-lg text-[10px] uppercase mt-2">Perbarui</button>
        </form>
    </div>
</div>
@endforeach
@endpush
@push('scripts')
<script>
function searchMainTable() {
    const input = document.getElementById('search-mapel-main').value.toLowerCase();
    document.querySelectorAll('#tbody-mapel-main tr[data-filter]').forEach(row => {
        row.style.display = row.getAttribute('data-filter').includes(input) ? "" : "none";
    });
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}
</script>
@endpush