@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8"
    x-data="{ openEdit: false, editData: { id: '', jam_ke: '', mulai: '', selesai: '', tipe: '' } }">

    {{-- Header Halaman --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Master Waktu</h1>
            <p class="text-slate-500 text-sm mt-1">Kelola urutan jam pelajaran dan waktu istirahat sekolah.</p>
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
                Tambah Jam Pelajaran
            </h3>
        </div>

        <form action="{{ route('master-waktu.store') }}" method="POST" class="p-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Jam Ke</label>
                    <input type="number" name="jam_ke" placeholder="Contoh: 1" min="1" required
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-medium text-slate-700 placeholder-slate-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Mulai</label>
                    <input type="time" name="waktu_mulai" required
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-medium text-slate-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Selesai</label>
                    <input type="time" name="waktu_selesai" required
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-medium text-slate-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipe</label>
                    <div class="relative">
                        <select name="tipe" required
                            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition bg-slate-50 focus:bg-white text-sm font-medium text-slate-700 appearance-none cursor-pointer">
                            <option value="Belajar" selected>Belajar</option>
                            <option value="Istirahat">Istirahat</option>
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

                <div>
                    <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold uppercase tracking-wide rounded-lg shadow-md hover:shadow-lg hover:shadow-indigo-500/30 transition-all duration-200 transform active:scale-95">
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
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Daftar Susunan Waktu
            </h3>
            <span class="px-2.5 py-1 rounded-md bg-slate-200 text-slate-600 text-[10px] font-bold">Total:
                {{ $waktus->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead
                    class="bg-white text-slate-500 border-b border-slate-100 uppercase font-bold text-xs tracking-wider">
                    <tr>
                        <th class="px-6 py-4 text-center">Jam Ke</th>
                        <th class="px-6 py-4">Waktu Pelaksanaan</th>
                        <th class="px-6 py-4 text-center">Tipe</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($waktus as $w)
                    <tr
                        class="hover:bg-indigo-50/30 transition duration-150 {{ $w->tipe == 'Istirahat' ? 'bg-orange-50/30' : '' }}">
                        <td class="px-6 py-4 text-center">
                            <span
                                class="font-bold text-slate-700 text-sm bg-slate-100 px-3 py-1 rounded-full">{{ $w->jam_ke }}</span>
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 font-bold text-slate-700">
                                {{ \Carbon\Carbon::parse($w->waktu_mulai)->format('H:i') }}
                                <span class="text-slate-400 font-normal">s/d</span>
                                {{ \Carbon\Carbon::parse($w->waktu_selesai)->format('H:i') }}
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if($w->tipe == 'Belajar')
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-600 border border-indigo-100 text-xs font-bold">Belajar</span>
                            @else
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-md bg-amber-50 text-amber-600 border border-amber-100 text-xs font-bold">Istirahat</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Tombol Edit --}}
                                <button
                                    @click="openEdit = true; editData = { id: '{{ $w->id }}', jam_ke: '{{ $w->jam_ke }}', mulai: '{{ substr($w->waktu_mulai, 0, 5) }}', selesai: '{{ substr($w->waktu_selesai, 0, 5) }}', tipe: '{{ $w->tipe }}' }"
                                    class="text-xs font-bold text-amber-600 hover:text-amber-800 hover:bg-amber-50 px-2 py-1.5 rounded transition border border-transparent hover:border-amber-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4L16.5 3.5z">
                                        </path>
                                    </svg>
                                </button>

                                <div class="w-px h-4 bg-slate-200"></div>

                                {{-- Tombol Delete --}}
                                <form action="{{ route('master-waktu.destroy', $w->id) }}" method="POST"
                                    onsubmit="return confirm('Hapus jam ke-{{ $w->jam_ke }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="text-xs font-bold text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1.5 rounded transition">
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
                        <td colspan="4" class="px-6 py-12 text-center text-slate-400 font-medium tracking-wide italic">
                            Belum ada data jam pelajaran.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL EDIT WAKTU --}}
    <template x-if="openEdit">
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
            x-transition.opacity>
            <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 overflow-hidden"
                @click.away="openEdit = false">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 tracking-tight">Edit Jam Pelajaran</h3>
                    <button @click="openEdit = false" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form :action="'{{ url('master-waktu') }}/' + editData.id" method="POST" class="p-6 space-y-5">
                    @csrf @method('PUT')

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Jam
                                Ke</label>
                            <input type="number" name="jam_ke" x-model="editData.jam_ke" required min="1"
                                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm font-medium text-slate-700">
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tipe</label>
                            <select name="tipe" x-model="editData.tipe"
                                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm font-medium text-slate-700 appearance-none">
                                <option value="Belajar">Belajar</option>
                                <option value="Istirahat">Istirahat</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Mulai</label>
                            <input type="time" name="waktu_mulai" x-model="editData.mulai" required
                                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm font-medium text-slate-700">
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Selesai</label>
                            <input type="time" name="waktu_selesai" x-model="editData.selesai" required
                                class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition text-sm font-medium text-slate-700">
                        </div>
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" @click="openEdit = false"
                            class="flex-1 px-4 py-2.5 border border-slate-300 rounded-lg text-sm font-bold text-slate-600 hover:bg-slate-50 transition">Batal</button>
                        <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-bold uppercase hover:bg-indigo-700 transition shadow-md">Update
                            Waktu</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection