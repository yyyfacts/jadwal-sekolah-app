@extends('layouts.app')

@section('content')
<div class="fixed inset-0 -z-10 pointer-events-none bg-[#f4f7fb]"></div>

<div class="w-full max-w-[100vw] mx-auto px-2 sm:px-4 h-[calc(100vh-4rem)] pb-2 pt-2 flex flex-col relative z-0">

    {{-- Notifikasi --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-emerald-50 border border-emerald-100 rounded-lg shadow-sm text-emerald-800 shrink-0">
        <span class="font-bold text-[11px]">✅ {{ session('success') }}</span><button @click="show = false"
            class="text-emerald-400">&times;</button>
    </div>
    @endif
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
        class="mb-2 flex items-center justify-between p-3 bg-red-50 border border-red-100 rounded-lg shadow-sm text-red-800 shrink-0">
        <span class="font-bold text-[11px]">❌ {{ session('error') }}</span><button @click="show = false"
            class="text-red-400">&times;</button>
    </div>
    @endif

    {{-- Kartu Utama Tabel --}}
    <div class="bg-white rounded-xl border border-slate-100 shadow-md flex flex-col flex-1 overflow-hidden">

        {{-- Header Area --}}
        <div class="px-4 py-3 bg-white shrink-0 z-20 border-b border-slate-100">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex gap-2 items-center">
                    <div class="w-1.5 h-6 bg-slate-800 rounded-full"></div>
                    <div>
                        <h1 class="text-lg font-extrabold text-slate-800 leading-none">Manajemen Pengguna</h1>
                        <p class="text-slate-500 text-[10px] mt-0.5">Kelola akses administrator dan operator.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div
                        class="hidden md:flex items-center px-3 py-1.5 bg-slate-50 border rounded-lg text-[10px] font-bold text-slate-500">
                        Total Admin: <span class="text-slate-800 ml-1">{{ $users->count() }}</span>
                    </div>

                    <div class="relative w-48">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="search-user" onkeyup="searchTable()"
                            class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border rounded-lg text-xs outline-none focus:border-slate-400 focus:bg-white"
                            placeholder="Cari Nama...">
                    </div>

                    <button onclick="openModal('modaltambah')"
                        class="px-4 py-1.5 bg-slate-900 text-white rounded-lg font-bold text-[10px] uppercase shadow-sm flex items-center gap-1 hover:bg-slate-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 4v16m8-8H4"></path>
                        </svg> Tambah
                    </button>
                </div>
            </div>
        </div>

        {{-- Area Tabel Data --}}
        <div class="flex-1 overflow-y-auto custom-scrollbar bg-white">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead class="bg-slate-50 sticky top-0 shadow-sm z-10">
                    <tr>
                        <th class="px-4 py-2 text-[10px] font-bold text-slate-500 text-center w-12 border-b">No</th>
                        <th class="px-3 py-2 text-[10px] font-bold text-slate-500 w-[45%] border-b">Identitas Pengguna
                        </th>
                        <th class="px-3 py-2 text-[10px] font-bold text-slate-500 text-center w-[20%] border-b">Status
                        </th>
                        <th class="px-4 py-2 text-[10px] font-bold text-slate-500 text-right w-[20%] border-b">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-user" class="divide-y divide-slate-100">
                    @forelse($users as $index => $u)
                    <tr class="hover:bg-slate-50/80 transition-colors">
                        <td class="px-4 py-2 text-center text-[11px] font-medium text-slate-400 align-middle">
                            {{ $index + 1 }}</td>
                        <td class="px-3 py-2 align-middle">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-8 w-8 rounded-full bg-gradient-to-br from-slate-700 to-slate-500 text-white flex items-center justify-center font-bold text-[10px] shadow-sm">
                                    {{ substr($u->name, 0, 1) }}
                                </div>
                                <div class="leading-tight">
                                    <div class="font-bold text-slate-800 text-xs">{{ $u->name }}</div>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                            </path>
                                        </svg>
                                        <span class="text-slate-500 text-[10px] font-mono">{{ $u->username }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-center align-middle">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase">Aktif</span>
                        </td>
                        <td class="px-4 py-2 text-right align-middle">
                            @if(Auth::id() != $u->id)
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('user.edit', $u->id) }}"
                                    class="p-1.5 border rounded-lg text-slate-400 hover:text-blue-600 hover:border-blue-300 transition-colors bg-white"><svg
                                        class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg></a>
                                <form action="{{ route('user.destroy', $u->id) }}" method="POST" class="inline m-0"
                                    onsubmit="return confirm('Hapus pengguna {{ $u->name }}?')">@csrf
                                    @method('DELETE')<button type="submit"
                                        class="p-1.5 border rounded-lg text-slate-400 hover:text-red-500 hover:border-red-300 transition-colors bg-white"><svg
                                            class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg></button></form>
                            </div>
                            @else
                            <span
                                class="text-[9px] font-bold text-slate-400 italic bg-slate-50 border px-2 py-1 rounded">Akun
                                Anda</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-xs text-slate-400">Belum ada pengguna
                            tambahan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL TAMBAH PENGGUNA --}}
<div id="modaltambah"
    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[999] hidden items-center justify-center p-2">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden border">
        <div class="px-4 py-3 border-b bg-slate-50 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-sm">Tambah Pengguna</h3>
            <button onclick="closeModal('modaltambah')"
                class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
        </div>
        <form action="{{ route('user.store') }}" method="POST" class="p-4 space-y-3">
            @csrf
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label>
                <input type="text" name="name"
                    class="w-full border rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-slate-400 outline-none"
                    required>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Pengguna (Login)</label>
                <input type="text" name="username"
                    class="w-full border rounded-lg px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-slate-400 outline-none"
                    required>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Kata Sandi</label>
                <input type="password" name="password"
                    class="w-full border rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-slate-400 outline-none"
                    placeholder="Minimal 8 karakter" required>
            </div>
            <button type="submit"
                class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 rounded-lg text-[10px] uppercase mt-2">Buat
                Akun</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openModal(id) {
    const m = document.getElementById(id);
    if (m) {
        m.classList.remove('hidden');
        m.classList.add('flex');
        m.querySelector('input')?.focus();
    }
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (m) {
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
}
window.onclick = function(e) {
    if (e.target.classList.contains('fixed')) e.target.classList.add('hidden');
}

function searchTable() {
    const filter = document.getElementById('search-user').value.toLowerCase();
    const rows = document.getElementById('tbody-user').getElementsByTagName('tr');
    for (let i = 0; i < rows.length; i++) {
        const col = rows[i].getElementsByTagName('td')[1];
        if (col) {
            rows[i].style.display = (col.textContent || col.innerText).toLowerCase().indexOf(filter) > -1 ? "" : "none";
        }
    }
}
</script>
@endpush