@extends('layouts.app')

@section('content')
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

<div x-data="{ 
        editModalOpen: false, 
        editId: '', editTahun: '', editSemester: '',
        openEditModal(id, tahun, semester) {
            this.editId = id; this.editTahun = tahun; this.editSemester = semester; this.editModalOpen = true;
        }
    }" class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 h-[calc(100vh-4rem)] pb-2 pt-2 flex flex-col relative z-0">

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-emerald-50 border border-emerald-100 rounded-lg shadow-sm text-emerald-800 shrink-0">
        <span class="font-bold text-xs">✅ {{ session('success') }}</span><button @click="show = false"
            class="text-emerald-400">&times;</button>
    </div>
    @endif
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-rose-50 border border-rose-100 rounded-lg shadow-sm text-rose-800 shrink-0">
        <span class="font-bold text-xs">❌ {{ session('error') }}</span><button @click="show = false"
            class="text-rose-400">&times;</button>
    </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-2 flex-1 min-h-0">
        {{-- KIRI: FORM TAMBAH --}}
        <div class="w-full lg:w-[300px] bg-white rounded-xl shadow-md border flex flex-col h-fit shrink-0">
            <div class="px-4 py-3 border-b bg-slate-50">
                <h3 class="font-bold text-sm">Tambah Tahun Ajaran</h3>
            </div>
            <form action="{{ route('tahun-pelajaran.store') }}" method="POST" class="p-4 space-y-3">
                @csrf
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tahun (Misal:
                        2025/2026)</label><input type="text" name="tahun"
                        class="w-full border rounded-lg px-3 py-2 text-xs" required></div>
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Semester</label><select
                        name="semester" class="w-full border rounded-lg px-3 py-2 text-xs" required>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select></div>
                <button type="submit"
                    class="w-full bg-slate-900 text-white font-bold py-2 rounded-lg text-[10px] uppercase mt-2">Simpan
                    Baru</button>
            </form>
        </div>

        {{-- KANAN: TABEL --}}
        <div class="flex-1 bg-white rounded-xl shadow-md border flex flex-col overflow-hidden min-w-0">
            <div class="px-4 py-3 border-b bg-slate-50 flex justify-between">
                <h3 class="font-bold text-sm">Daftar Tahun Pelajaran</h3>
            </div>
            <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 sticky top-0 shadow-sm z-10 text-[10px] text-slate-500 uppercase">
                        <tr>
                            <th class="px-4 py-2 border-b">Tahun</th>
                            <th class="px-3 py-2 border-b">Semester</th>
                            <th class="px-3 py-2 text-center border-b">Status</th>
                            <th class="px-3 py-2 text-center border-b">Waktu Sistem</th>
                            <th class="px-4 py-2 text-right border-b">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse($tahuns as $t)
                        <tr class="hover:bg-slate-50 {{ $t->is_active ? 'bg-indigo-50/40' : '' }}">
                            <td class="px-4 py-2 font-bold text-slate-700">{{ $t->tahun }}</td>
                            <td class="px-3 py-2"><span
                                    class="px-2 py-0.5 rounded text-[10px] font-bold {{ $t->semester=='Ganjil'?'bg-orange-100 text-orange-700':'bg-purple-100 text-purple-700' }}">{{ $t->semester }}</span>
                            </td>
                            <td class="px-3 py-2 text-center">@if($t->is_active)<span
                                    class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded font-bold text-[10px]">AKTIF</span>@else<span
                                    class="bg-slate-100 text-slate-400 px-2 py-0.5 rounded text-[10px] font-bold">Nonaktif</span>@endif
                            </td>
                            <td class="px-3 py-2 text-center align-middle">
                                <div class="flex flex-col items-center gap-0.5 text-[9px]">
                                    <span class="text-slate-400" title="Dibuat: {{ $t->created_at }}">➕
                                        {{ $t->created_at ? $t->created_at->format('d/m/Y') : '-' }}</span>
                                    <span class="text-slate-500" title="Diperbarui: {{ $t->updated_at }}">🔄
                                        {{ $t->updated_at ? $t->updated_at->format('d/m/Y') : '-' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2 text-right">
                                <div class="flex justify-end gap-1.5 items-center">
                                    <button
                                        @click="openEditModal({{ $t->id }}, '{{ $t->tahun }}', '{{ $t->semester }}')"
                                        class="text-amber-500 font-bold text-[10px] border px-2 py-1 rounded hover:bg-amber-50">Edit</button>
                                    @if(!$t->is_active)
                                    <form action="{{ route('tahun-pelajaran.activate', $t->id) }}" method="POST"
                                        class="m-0 inline">@csrf @method('PATCH')<button type="submit"
                                            class="text-indigo-600 font-bold text-[10px] border border-indigo-200 px-2 py-1 rounded hover:bg-indigo-50">Aktifkan</button>
                                    </form>
                                    <form action="{{ route('tahun-pelajaran.destroy', $t->id) }}" method="POST"
                                        class="m-0 inline" onsubmit="return confirm('Hapus?')">@csrf
                                        @method('DELETE')<button type="submit"
                                            class="text-red-500 font-bold text-[10px] border border-red-200 px-2 py-1 rounded hover:bg-red-50">Hapus</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-400 text-xs">Kosong.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL UBAH ALPINE --}}
    <div x-show="editModalOpen" style="display: none;"
        class="fixed inset-0 z-[100] flex items-center justify-center p-2">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" @click="editModalOpen = false"></div>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden border"
            x-show="editModalOpen">
            <div class="px-4 py-3 border-b bg-amber-50 flex justify-between">
                <h3 class="font-bold text-sm text-amber-800">Ubah Tahun</h3><button @click="editModalOpen = false"
                    class="text-lg">&times;</button>
            </div>
            <form :action="`{{ route('tahun-pelajaran.index') }}/${editId}`" method="POST" class="p-4 space-y-3">
                @csrf @method('PUT')
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Tahun</label><input
                        type="text" name="tahun" x-model="editTahun" class="w-full border rounded-lg px-3 py-2 text-xs"
                        required></div>
                <div><label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Semester</label><select
                        name="semester" x-model="editSemester" class="w-full border rounded-lg px-3 py-2 text-xs"
                        required>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select></div>
                <button type="submit"
                    class="w-full bg-amber-500 text-white font-bold py-2 rounded-lg text-[10px] uppercase mt-2">Perbarui</button>
            </form>
        </div>
    </div>
</div>
@endsection