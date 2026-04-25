@extends('layouts.app')

@section('content')
    {{-- Wrapper AlpineJS untuk state Modal Edit, diubah ke FULL WIDTH --}}
    <div x-data="{ 
            editModalOpen: false, 
            editId: '', 
            editTahun: '', 
            editSemester: '',
            openEditModal(id, tahun, semester) {
                this.editId = id;
                this.editTahun = tahun;
                this.editSemester = semester;
                this.editModalOpen = true;
            }
        }" class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header Halaman --}}
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tahun Pelajaran</h1>
                <p class="text-slate-500 text-sm mt-1">Kelola data tahun ajaran dan status aktif semester.</p>
            </div>
        </div>

        {{-- Flash Message (Success) --}}
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms
                class="mb-6 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-100 rounded-xl shadow-sm text-emerald-800">
                <div class="flex items-center gap-3">
                    <div class="p-1.5 bg-emerald-100 rounded-full text-emerald-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="font-medium text-sm">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-400 hover:text-emerald-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif

        {{-- Flash Message (Error) --}}
        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition.duration.300ms
                class="mb-6 flex items-center justify-between p-4 bg-red-50 border border-red-100 rounded-xl shadow-sm text-red-800">
                <div class="flex items-center gap-3">
                    <div class="p-1.5 bg-red-100 rounded-full text-red-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                    <span class="font-medium text-sm">{{ session('error') }}</span>
                </div>
                <button @click="show = false" class="text-red-400 hover:text-red-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        @endif

        {{-- CARD 1: FORM TAMBAH --}}
        <div class="bg-white rounded-xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6">
                        </path>
                    </svg>
                    Tambah Data Baru
                </h3>
            </div>

            <form action="{{ route('tahun-pelajaran.store') }}" method="POST" class="p-6">
                @csrf
                <div class="flex flex-col md:flex-row gap-5 items-end">
                    {{-- Input Tahun --}}
                    <div class="w-full md:w-1/2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tahun
                            Ajaran</label>
                        <input type="text" name="tahun" placeholder="Contoh: 2025/2026" required
                            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-medium text-slate-700 placeholder-slate-400">
                    </div>

                    {{-- Input Semester --}}
                    <div class="w-full md:w-1/2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Semester</label>
                        <div class="relative">
                            <select name="semester" required
                                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-medium text-slate-700 appearance-none cursor-pointer">
                                <option value="" disabled selected>-- Pilih Semester --</option>
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                            <div
                                class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Simpan --}}
                    <div class="w-full md:w-auto">
                        <button type="submit"
                            class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold uppercase tracking-wide rounded-lg shadow-md hover:shadow-lg hover:shadow-indigo-500/30 transition-all duration-200 transform active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4">
                                </path>
                            </svg>
                            Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- CARD 2: TABEL DATA --}}
        <div class="bg-white rounded-xl shadow-lg shadow-slate-200/50 border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    Daftar Riwayat
                </h3>
                <span class="px-2.5 py-1 rounded-md bg-slate-200 text-slate-600 text-[10px] font-bold">Total:
                    {{ $tahuns->count() }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead
                        class="bg-white text-slate-500 border-b border-slate-100 uppercase font-bold text-xs tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Tahun Ajaran</th>
                            <th class="px-6 py-4">Semester</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($tahuns as $t)
                            <tr
                                class="hover:bg-indigo-50/30 transition duration-150 {{ $t->is_active ? 'bg-indigo-50/60' : '' }}">
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-700 text-sm">{{ $t->tahun }}</span>
                                </td>

                                <td class="px-6 py-4">
                                    @if($t->semester == 'Ganjil')
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-md bg-orange-50 text-orange-600 border border-orange-100 text-xs font-bold">
                                            Ganjil
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded-md bg-purple-50 text-purple-600 border border-purple-100 text-xs font-bold">
                                            Genap
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if($t->is_active)
                                        <div
                                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 shadow-sm">
                                            <span class="relative flex h-2 w-2">
                                                <span
                                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                            </span>
                                            AKTIF
                                        </div>
                                    @else
                                        <span class="text-slate-400 font-semibold text-xs bg-slate-100 px-2 py-1 rounded">Tidak
                                            Aktif</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">

                                        {{-- Tombol Edit (Bisa edit yang aktif maupun tidak) --}}
                                        <button type="button"
                                            @click="openEditModal({{ $t->id }}, '{{ $t->tahun }}', '{{ $t->semester }}')"
                                            class="text-xs font-bold text-amber-500 hover:text-amber-700 hover:bg-amber-50 px-2 py-1.5 rounded transition"
                                            title="Ubah Data">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                        </button>

                                        @if(!$t->is_active)
                                            <form action="{{ route('tahun-pelajaran.activate', $t->id) }}" method="POST"
                                                class="m-0 p-0">
                                                @csrf @method('PATCH')
                                                <button type="submit"
                                                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-3 py-1.5 rounded transition border border-transparent hover:border-indigo-100">
                                                    Aktifkan
                                                </button>
                                            </form>

                                            <div class="w-px h-4 bg-slate-300 mx-1"></div>

                                            <form action="{{ route('tahun-pelajaran.destroy', $t->id) }}" method="POST"
                                                class="m-0 p-0"
                                                onsubmit="return confirm('Hapus tahun {{ $t->tahun }} {{ $t->semester }}?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="text-xs font-bold text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1.5 rounded transition"
                                                    title="Hapus Data">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @else
                                            <span
                                                class="text-[10px] font-bold text-slate-400 italic uppercase tracking-wider ml-2 bg-white px-2 py-1 rounded border border-slate-200">Sedang
                                                Digunakan</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-50">
                                        <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        <span class="text-sm font-medium text-slate-500">Belum ada data tahun pelajaran.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MODAL UBAH (AlpineJS) --}}
        <div x-show="editModalOpen" style="display: none;" class="fixed inset-0 z-[100] flex items-center justify-center">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="editModalOpen = false"
                x-show="editModalOpen" x-transition.opacity.duration.300ms></div>

            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden relative z-10 transform transition-all"
                x-show="editModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Ubah Tahun Pelajaran</h3>
                    <button @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                </div>

                <form :action="`{{ route('tahun-pelajaran.index') }}/${editId}`" method="POST" class="p-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tahun
                                Ajaran</label>
                            <input type="text" name="tahun" x-model="editTahun" required
                                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-medium text-slate-700">
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Semester</label>
                            <select name="semester" x-model="editSemester" required
                                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-medium text-slate-700">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="editModalOpen = false"
                            class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-lg transition">Batal</button>
                        <button type="submit"
                            class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-lg shadow-md hover:shadow-lg transition transform active:scale-95">Perbarui
                            Data</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection