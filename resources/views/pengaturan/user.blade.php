@extends('layouts.app')

@section('content')
    {{-- Diubah ke FULL WIDTH --}}
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 pb-20">

        {{-- ================================================================= --}}
        {{-- 1. HEADER HALAMAN --}}
        {{-- ================================================================= --}}
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 pt-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Pengguna</h1>
                <p class="text-slate-500 text-sm mt-1">Kelola akses administrator dan asisten operator.</p>
            </div>

            <div class="flex items-center gap-3">
                {{-- Statistik Badge --}}
                <div
                    class="hidden md:flex items-center px-3 py-1.5 bg-white border border-slate-200 rounded-full shadow-sm">
                    <div class="flex h-2 w-2 relative mr-2">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                    </div>
                    <span class="text-xs text-slate-600 font-medium">Total Admin: <span
                            class="font-bold text-slate-900">{{ $users->count() }}</span></span>
                </div>

                {{-- Tombol Tambah --}}
                <button onclick="openModal('modaltambah')"
                    class="group relative inline-flex items-center justify-center px-4 py-2 bg-slate-900 text-white text-xs font-bold uppercase tracking-wide rounded-lg shadow-md hover:bg-slate-800 transition-all focus:ring-2 focus:ring-slate-200">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Tambah Pengguna
                </button>
            </div>
        </div>

        {{-- FLASH MESSAGE --}}
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms
                class="mb-6 flex items-center justify-between p-3 bg-emerald-50/80 backdrop-blur-sm border border-emerald-100 rounded-xl shadow-sm text-emerald-800">
                <div class="flex items-center gap-3">
                    <div class="p-1.5 bg-emerald-100 rounded-full text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="font-medium text-xs">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-400 hover:text-emerald-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms
                class="mb-6 flex items-center justify-between p-3 bg-red-50/80 backdrop-blur-sm border border-red-100 rounded-xl shadow-sm text-red-800">
                <div class="flex items-center gap-3">
                    <div class="p-1.5 bg-red-100 rounded-full text-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </div>
                    <span class="font-medium text-xs">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-400 hover:text-red-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif

        {{-- ================================================================= --}}
        {{-- 2. TABLE CARD CONTAINER --}}
        {{-- ================================================================= --}}
        <div
            class="bg-white rounded-xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden relative flex flex-col">

            {{-- SEARCH BAR --}}
            <div
                class="p-4 border-b border-slate-100 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-4 z-10 relative">
                <div class="relative w-full sm:w-72 group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400 group-focus-within:text-blue-500 transition-colors" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" id="search-user" onkeyup="searchTable()"
                        class="block w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg leading-5 bg-slate-50 placeholder-slate-400 focus:bg-white focus:outline-none focus:placeholder-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-xs transition duration-200 ease-in-out"
                        placeholder="Cari Nama Pengguna...">
                </div>

                {{-- Indikator Tahun Ajaran --}}
                <div class="flex items-center gap-2">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">
                        Basis Data Aktif
                    </span>
                </div>
            </div>

            {{-- SCROLLABLE TABLE AREA --}}
            <div class="overflow-auto max-h-[75vh] custom-scrollbar relative bg-white">
                <table class="w-full text-left border-collapse min-w-[900px]">

                    {{-- HEADER TABEL --}}
                    <thead
                        class="bg-slate-50/95 backdrop-blur sticky top-0 z-10 shadow-sm text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 w-14 text-center border-b border-slate-200 bg-slate-50">No</th>
                            <th class="px-4 py-3 border-b border-slate-200 bg-slate-50 w-[45%]">Identitas Pengguna</th>
                            <th class="px-4 py-3 text-center border-b border-slate-200 bg-slate-50 w-[20%]">Status</th>
                            <th class="px-4 py-3 text-center border-b border-slate-200 bg-slate-50 w-[20%]">Aksi</th>
                        </tr>
                    </thead>

                    <tbody id="tbody-user" class="divide-y divide-slate-100 text-xs bg-white">
                        @forelse($users as $index => $u)
                            <tr class="hover:bg-slate-50/80 transition duration-150 group">

                                {{-- 1. NOMOR --}}
                                <td class="px-4 py-3 text-center text-slate-400 font-mono">{{ $index + 1 }}</td>

                                {{-- 2. IDENTITAS (Avatar + Nama + Username) --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <div
                                            class="h-9 w-9 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 text-white flex items-center justify-center font-bold text-xs mr-3 shadow-sm shadow-blue-200 border border-white flex-shrink-0">
                                            {{ substr($u->name, 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-800 text-sm truncate">{{ $u->name }}</div>
                                            <div class="flex items-center mt-0.5">
                                                <svg class="w-3 h-3 text-slate-400 mr-1" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                    </path>
                                                </svg>
                                                <span class="text-slate-500 text-[11px] font-mono">{{ $u->username }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- 3. STATUS (Badge) --}}
                                <td class="px-4 py-3 text-center align-middle">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-wide">
                                        Aktif
                                    </span>
                                </td>

                                {{-- 4. AKSI --}}
                                <td class="px-4 py-3 align-middle text-center">
                                    @if(Auth::id() != $u->id)
                                        {{-- Tombol Edit (BARU DITAMBAHKAN) --}}
                                        <a href="{{ route('user.edit', $u->id) }}"
                                            class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 border border-transparent hover:border-blue-100 rounded transition-colors hover:scale-105 inline-block mr-1"
                                            title="Ubah Pengguna">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                        </a>

                                        {{-- Tombol Hapus --}}
                                        <form action="{{ route('user.destroy', $u->id) }}" method="POST"
                                            onsubmit="return confirm('Hapus pengguna {{ $u->name }}?')" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 border border-transparent hover:border-red-100 rounded transition-colors hover:scale-105 inline-block"
                                                title="Hapus Pengguna">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <span
                                            class="text-[10px] font-medium text-slate-400 italic bg-slate-50 px-2 py-1 rounded border border-slate-100">
                                            Akun Anda
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="bg-slate-50 p-3 rounded-full mb-2">
                                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                                </path>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-500">Belum ada pengguna tambahan.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer Table --}}
            <div
                class="bg-slate-50 border-t border-slate-100 px-6 py-3 text-[10px] text-slate-400 flex justify-between items-center uppercase tracking-wide">
                <span>Manajemen Akses Sistem</span>
                <span>Tingkat Keamanan: Administrator</span>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- MODAL AREA --}}
        {{-- ========================================================= --}}

        {{-- Modal Tambah User --}}
        <div id="modaltambah"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99] hidden flex items-center justify-center p-4">
            <div
                class="bg-white rounded-xl shadow-2xl w-full max-w-md animate-scale-in transform transition-all border border-slate-100">
                <div
                    class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 rounded-t-xl">
                    <h3 class="font-bold text-lg text-slate-800">Tambah Pengguna Baru</h3>
                    <button onclick="closeModal('modaltambah')"
                        class="text-slate-400 hover:text-slate-600 transition text-2xl leading-none">&times;</button>
                </div>
                <form action="{{ route('user.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama
                            Lengkap</label>
                        <input type="text" name="name"
                            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-slate-50 focus:bg-white text-sm"
                            placeholder="Nama Pengguna" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Pengguna
                            (Login)</label>
                        <input type="text" name="username"
                            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-mono"
                            placeholder="Masukkan Nama Pengguna" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Kata
                            Sandi</label>
                        <input type="password" name="password"
                            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-slate-50 focus:bg-white text-sm"
                            placeholder="Minimal 8 karakter" required>
                    </div>

                    <button type="submit"
                        class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-lg transition shadow-md text-sm mt-2">
                        Buat Akun
                    </button>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function openModal(modalID) {
            const modal = document.getElementById(modalID);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                const firstInput = modal.querySelector('input');
                if (firstInput) firstInput.focus();
            }
        }

        function closeModal(modalID) {
            const modal = document.getElementById(modalID);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        window.onclick = function (event) {
            if (event.target.classList.contains('fixed')) {
                event.target.classList.add('hidden');
                event.target.classList.remove('flex');
            }
        }

        function searchTable() {
            const input = document.getElementById('search-user');
            const filter = input.value.toLowerCase();
            const tbody = document.getElementById('tbody-user');
            const rows = tbody.getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const colName = rows[i].getElementsByTagName('td')[1];
                if (colName) {
                    const txtValue = colName.textContent || colName.innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }
        }
    </script>
@endpush