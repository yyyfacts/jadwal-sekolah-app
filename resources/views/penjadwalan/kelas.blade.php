@extends('layouts.app')

@section('content')
{{-- BACKGROUND --}}
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

{{-- CONTAINER UTAMA (SUPER PADAT & FULL WIDTH) --}}
<div class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 h-[calc(100vh-4rem)] pb-2 pt-2 flex flex-col">

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-emerald-50 border border-emerald-100 rounded-lg shadow-sm text-emerald-800 shrink-0">
        <span class="font-bold text-xs">✅ {{ session('success') }}</span>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-700">&times;</button>
    </div>
    @endif

    {{-- UNIFIED CARD --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-md flex flex-col flex-1 overflow-hidden">
        <div class="px-4 py-3 bg-white shrink-0 z-20 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex gap-2 items-center">
                    <div class="w-1.5 h-6 bg-purple-600 rounded-full"></div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 leading-none">Data Kelas</h1>
                        <p class="text-slate-500 text-[10px] mt-0.5 font-medium">Manajemen kapasitas & distribusi mapel.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div
                        class="hidden md:flex items-center px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg shadow-sm">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Total: <span
                                class="text-purple-600 font-extrabold ml-1">{{ $kelass->count() }}</span></span>
                    </div>

                    <form action="{{ route('kelas.sinkronisasi') }}" method="POST" class="inline m-0">
                        @csrf
                        <button type="submit" onclick="return confirm('Sinkronisasi Batas?')"
                            class="px-3 py-1.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-[10px] font-bold uppercase tracking-wider shadow-sm">Sync
                            Batas</button>
                    </form>

                    <div class="relative w-48">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none"><svg
                                class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg></div>
                        <input type="text" id="search-kelas-main" oninput="searchMainTable()"
                            class="block w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs outline-none focus:border-purple-500 focus:bg-white"
                            placeholder="Cari Kelas...">
                    </div>

                    <button onclick="openModal('modaltambah')"
                        class="px-4 py-1.5 bg-[#9333ea] text-white rounded-lg font-bold text-[10px] uppercase shadow-sm flex items-center gap-1.5 hover:bg-purple-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 4v16m8-8H4"></path>
                        </svg> Tambah
                    </button>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead class="bg-slate-50 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th
                            class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-12 border-b border-slate-200">
                            No</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider w-[35%] border-b border-slate-200">
                            Identitas Kelas</th>
                        <th
                            class="px-3 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center w-[25%] border-b border-slate-200">
                            Beban / Kapasitas</th>
                        <th
                            class="px-4 py-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right w-[35%] border-b border-slate-200">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-kelas-main" class="divide-y divide-slate-100">
                    @forelse($kelass as $index => $k)
                    @php
                    $totalJamOffline = $k->jadwals->where('status', 'offline')->sum('jumlah_jam');
                    $maxJam = $k->max_jam ?? 48;
                    $percentage = $maxJam > 0 ? ($totalJamOffline / $maxJam) * 100 : 0;
                    $barColor = $totalJamOffline > $maxJam ? 'bg-rose-500' : 'bg-emerald-400';
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition-colors"
                        data-filter="{{ strtolower($k->nama_kelas) }} {{ strtolower($k->kode_kelas) }}">
                        <td class="px-4 py-2 text-center text-[11px] font-medium text-slate-400 align-middle">
                            {{ $index + 1 }}</td>
                        <td class="px-3 py-2 align-middle">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-8 w-8 shrink-0 rounded bg-[#f3e8ff] text-[#9333ea] flex items-center justify-center font-bold text-[10px] border border-[#e9d5ff]">
                                    {{ substr($k->nama_kelas, 0, 2) }}</div>
                                <div class="leading-tight">
                                    <div class="font-bold text-slate-800 text-xs">{{ $k->nama_kelas }}</div>
                                    <div
                                        class="inline-block px-1.5 py-0.5 mt-0.5 rounded bg-slate-100 text-slate-500 font-bold text-[9px] uppercase border border-slate-200">
                                        {{ $k->kode_kelas }}</div>
                                    @if($k->waliKelas) <span class="text-[9px] text-slate-400 ml-1">Wali:
                                        {{ $k->waliKelas->nama_guru }}</span> @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-center align-middle">
                            <div class="flex flex-col items-center gap-1">
                                <span
                                    class="text-[10px] font-bold {{ $totalJamOffline > $maxJam ? 'text-rose-600' : 'text-emerald-600' }}">{{ $totalJamOffline }}
                                    / {{ $maxJam }} Jam</span>
                                <div class="w-20 h-1 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full {{ $barColor }}" style="width: {{ min($percentage, 100) }}%">
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right align-middle">
                            <div class="flex items-center justify-end gap-1.5">
                                <button onclick="openModal('modaljadwal{{ $k->id }}')"
                                    class="flex items-center gap-1 px-2.5 py-1.5 bg-[#1e293b] text-white text-[10px] font-bold rounded-lg shadow-sm hover:bg-slate-800"><svg
                                        class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 4v16m8-8H4"></path>
                                    </svg> Distribusi</button>
                                <button onclick="openModal('edit{{ $k->id }}')"
                                    class="p-1.5 border border-slate-200 text-slate-400 hover:text-amber-500 hover:border-amber-300 rounded-lg bg-white"><svg
                                        class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                        </path>
                                    </svg></button>
                                <form action="{{ route('kelas.destroy', $k->id) }}" method="POST"
                                    onsubmit="return confirm('Hapus kelas?')" class="inline m-0">@csrf
                                    @method('DELETE')<button type="submit"
                                        class="p-1.5 border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-300 rounded-lg bg-white"><svg
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
                        <td colspan="4" class="px-4 py-12 text-center text-xs text-slate-400">Belum ada data.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('modals')
<div id="modaltambah"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99] hidden items-center justify-center p-2">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-slate-100 flex justify-between bg-slate-50">
            <h3 class="font-bold text-sm flex items-center gap-1.5"><span class="w-1 h-4 bg-purple-600 rounded"></span>
                Tambah Kelas</h3><button onclick="closeModal('modaltambah')"
                class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
        </div>
        <form action="{{ route('kelas.store') }}" method="POST" class="p-4 space-y-3">
            @csrf
            <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Kelas</label><input
                    type="text" name="nama_kelas" class="w-full border rounded-lg px-3 py-2 text-xs" required></div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kode</label><input
                        type="text" name="kode_kelas"
                        class="w-full border rounded-lg px-3 py-2 text-xs font-mono uppercase" required></div>
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kapasitas
                        (Jam)</label><input type="number" name="max_jam" value="48"
                        class="w-full border rounded-lg px-3 py-2 text-xs text-center font-bold" required></div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Limit Harian</label><input
                        type="number" name="limit_harian" value="10"
                        class="w-full border rounded-lg px-3 py-2 text-xs text-center" required></div>
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Limit Jumat</label><input
                        type="number" name="limit_jumat" value="7"
                        class="w-full border rounded-lg px-3 py-2 text-xs text-center" required></div>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 text-white font-bold py-2.5 rounded-lg text-[10px] uppercase mt-2">Simpan</button>
        </form>
    </div>
</div>

@foreach($kelass as $k)
<div id="edit{{ $k->id }}"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[99] hidden items-center justify-center p-2">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden border border-white/20">
        <div class="px-4 py-3 border-b border-amber-100 flex justify-between bg-amber-50">
            <h3 class="font-bold text-sm text-amber-800 flex items-center gap-1.5"><span
                    class="w-1 h-4 bg-amber-500 rounded"></span> Ubah Kelas</h3><button
                onclick="closeModal('edit{{ $k->id }}')"
                class="text-amber-400 hover:text-amber-600 text-lg">&times;</button>
        </div>
        <form action="{{ route('kelas.update', $k->id) }}" method="POST" class="p-4 space-y-3">
            @csrf @method('PUT')
            <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Kelas</label><input
                    type="text" name="nama_kelas" value="{{ $k->nama_kelas }}"
                    class="w-full border rounded-lg px-3 py-2 text-xs" required></div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kode</label><input
                        type="text" name="kode_kelas" value="{{ $k->kode_kelas }}"
                        class="w-full border rounded-lg px-3 py-2 text-xs font-mono uppercase" required></div>
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kapasitas</label><input
                        type="number" name="max_jam" value="{{ $k->max_jam ?? 48 }}"
                        class="w-full border rounded-lg px-3 py-2 text-xs text-center font-bold" required></div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Limit Harian</label><input
                        type="number" name="limit_harian" value="{{ $k->limit_harian ?? 10 }}"
                        class="w-full border rounded-lg px-3 py-2 text-xs text-center" required></div>
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Limit Jumat</label><input
                        type="number" name="limit_jumat" value="{{ $k->limit_jumat ?? 7 }}"
                        class="w-full border rounded-lg px-3 py-2 text-xs text-center" required></div>
            </div>
            <button type="submit"
                class="w-full bg-amber-500 text-white font-bold py-2.5 rounded-lg text-[10px] uppercase mt-2">Perbarui</button>
        </form>
    </div>
</div>

<div id="modaljadwal{{ $k->id }}"
    class="fixed inset-0 bg-slate-900/80 z-[99] hidden items-center justify-center p-2 sm:p-4 transition-opacity">
    <div
        class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[85vh] flex flex-col border border-slate-200 overflow-hidden">
        <div class="px-4 py-2 border-b border-slate-100 flex justify-between bg-slate-50 shrink-0">
            <div class="flex items-center gap-2">
                <div class="p-1.5 bg-purple-600 text-white rounded"><svg class="w-4 h-4" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg></div>
                <div>
                    <h3 class="font-bold text-sm text-slate-800">{{ $k->nama_kelas }}</h3>
                    <p class="text-[9px] text-slate-500">Terisi: {{ $k->jadwals->sum('jumlah_jam') }} Jam</p>
                </div>
            </div>
            <button onclick="closeModal('modaljadwal{{ $k->id }}')"
                class="text-slate-400 hover:text-red-500 text-2xl">&times;</button>
        </div>
        <div class="flex flex-col lg:flex-row h-full overflow-hidden">
            <div class="flex-1 flex flex-col h-full border-r border-slate-100 relative">
                <div class="p-2 border-b bg-white"><input type="text" id="search-{{ $k->id }}"
                        oninput="searchTable({{ $k->id }})" placeholder="Cari..."
                        class="w-full border rounded px-2 py-1.5 text-xs outline-none focus:border-purple-500"></div>
                <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">
                    <table class="w-full text-[10px] border-collapse">
                        <thead class="bg-slate-50 text-slate-500 font-bold uppercase sticky top-0">
                            <tr>
                                <th class="px-2 py-1.5 text-left w-[40%]">Mapel</th>
                                <th class="px-2 py-1.5 text-left w-[30%]">Guru</th>
                                <th class="px-2 py-1.5 text-center w-[15%]">Jam</th>
                                <th class="px-2 py-1.5 text-right w-[15%]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-kelas-{{ $k->id }}" class="divide-y divide-slate-50">
                            @foreach($k->jadwals as $jadwal)
                            <tr class="hover:bg-purple-50 group">
                                <td class="px-2 py-1.5 font-bold text-slate-700">{{ $jadwal->mapel->nama_mapel ?? '-' }}
                                </td>
                                <td class="px-2 py-1.5 text-slate-600">{{ $jadwal->guru->nama_guru ?? '-' }}</td>
                                <td class="px-2 py-1.5 text-center"><span
                                        class="bg-white border text-purple-700 px-1 py-0.5 rounded font-bold text-[9px]">{{ $jadwal->jumlah_jam }}
                                        Jam</span></td>
                                <td class="px-2 py-1.5 text-right">
                                    <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100"><button
                                            onclick="hapusJadwal({{ $jadwal->id }}, this)"
                                            class="p-1 text-red-500 hover:bg-red-50 rounded"><svg class="w-3 h-3"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg></button></div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="w-full lg:w-[280px] bg-slate-50 flex flex-col h-full">
                <div class="p-4 overflow-y-auto">
                    <div class="bg-white p-3 rounded-lg border">
                        <h4 class="font-bold text-[10px] uppercase mb-2 border-b pb-1">Input Distribusi</h4>
                        <form action="{{ route('kelas.simpanJadwal', $k->id) }}" method="POST">
                            @csrf
                            <div class="space-y-3 text-[10px]">
                                <div><label class="font-bold text-slate-500 block mb-0.5">Mapel</label><select
                                        name="mapel_id"
                                        class="w-full border rounded px-2 py-1.5 bg-slate-50">@foreach($mapels as $m)
                                        <option value="{{ $m->id }}">{{ $m->nama_mapel }}</option>@endforeach
                                    </select>
                                </div>
                                <div><label class="font-bold text-slate-500 block mb-0.5">Guru</label><select
                                        name="guru_id"
                                        class="w-full border rounded px-2 py-1.5 bg-slate-50">@foreach($gurus as $g)
                                        <option value="{{ $g->id }}">{{ $g->nama_guru }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div><label class="font-bold block mb-0.5">Jam</label><input type="number"
                                            name="jumlah_jam"
                                            class="w-full border rounded px-2 py-1 bg-slate-50 text-center" min="1"
                                            max="10" required></div>
                                    <div><label class="font-bold block mb-0.5">Tipe</label><select name="tipe_jam"
                                            class="w-full border rounded px-1 py-1 bg-slate-50">
                                            <option value="single">Satu(1x)</option>
                                            <option value="double">Dua(2x)</option>
                                        </select></div>
                                </div>
                                <div><label class="font-bold block mb-0.5">Status</label><select name="status"
                                        class="w-full border rounded px-2 py-1.5 bg-slate-50">
                                        <option value="offline">Luring</option>
                                        <option value="online">Daring</option>
                                    </select></div>
                                <button type="submit"
                                    class="w-full bg-slate-900 text-white py-2 rounded font-bold uppercase mt-2">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach
@endpush

@push('scripts')
<script>
function searchMainTable() {
    const input = document.getElementById('search-kelas-main').value.toLowerCase();
    document.querySelectorAll('#tbody-kelas-main tr[data-filter]').forEach(row => {
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
async function hapusJadwal(id, btn) {
    if (!confirm('Hapus?')) return;
    try {
        const res = await fetch(`/kelas/jadwal/${id}`, {
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