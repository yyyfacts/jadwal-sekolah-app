@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">
    {{-- Header Section --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <div class="flex items-center gap-2 text-slate-500 text-sm mb-1">
                <a href="{{ route('guru.index') }}" class="hover:text-indigo-600 transition">Bank Data Guru</a>
                <span>/</span>
                <span>Atur Jam Libur</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Preferensi Waktu Mengajar</h1>
            <p class="text-slate-500 mt-1">
                Atur jam dimana guru <span class="font-bold text-red-500">BERHALANGAN</span> atau tidak bisa mengajar.
            </p>
        </div>
        <div>
            <div class="flex items-center gap-3 bg-white px-4 py-3 rounded-lg shadow-sm border border-slate-200">
                <div
                    class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-lg">
                    {{ substr($guru->nama_guru, 0, 1) }}
                </div>
                <div>
                    <div class="text-sm font-bold text-slate-800">{{ $guru->nama_guru }}</div>
                    <div class="text-xs text-slate-500 font-mono">{{ $guru->kode_guru }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert Info --}}
    <div class="bg-blue-50 border border-blue-100 text-blue-800 px-4 py-3 rounded-lg mb-6 flex items-start gap-3">
        <svg class="w-5 h-5 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="text-sm">
            <p class="font-bold">Instruksi:</p>
            <p>Klik kotak untuk menandai jam dimana guru <strong>TIDAK BISA</strong> mengajar (Warna Merah). <br>
                Jika salah centang, <strong>klik sekali lagi</strong> pada kotak tersebut untuk membatalkan (Warna
                Putih).</p>
        </div>
    </div>

    {{-- Main Form Card --}}
    <div class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
        <form action="{{ route('guru.simpanWaktuKosong', $guru->id) }}" method="POST">
            @csrf

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 w-32 sticky left-0 bg-slate-50 z-10 border-r border-slate-200">Hari
                            </th>
                            @for ($i = 1; $i <= 10; $i++) <th class="px-2 py-4 text-center min-w-[60px]">
                                <div class="flex flex-col items-center">
                                    <span>JP {{ $i }}</span>
                                </div>
                                </th>
                                @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php
                        $haris = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                        @endphp

                        @foreach ($haris as $hari)
                        <tr class="hover:bg-slate-50/80 transition group">
                            {{-- Kolom Hari --}}
                            <td
                                class="px-6 py-4 font-bold text-slate-700 bg-white group-hover:bg-slate-50/80 sticky left-0 border-r border-slate-200 z-10">
                                {{ $hari }}
                            </td>

                            {{-- Kolom Jam 1-10 --}}
                            @for ($jam = 1; $jam <= 10; $jam++) @php $key=$hari . '-' . $jam;
                                $isChecked=isset($selected) && in_array($key, $selected); @endphp <td
                                class="p-2 text-center relative">
                                <label class="cursor-pointer block w-full h-full">
                                    {{-- Checkbox Asli (Hidden) --}}
                                    {{-- Kita gunakan atribut 'checked' agar status awal benar, tapi styling diserahkan sepenuhnya ke CSS peer-checked --}}
                                    <input type="checkbox" name="libur[{{ $hari }}][]" value="{{ $jam }}"
                                        class="peer sr-only" {{ $isChecked ? 'checked' : '' }}>

                                    {{-- Tampilan Visual Checkbox --}}
                                    {{-- PERBAIKAN: Default selalu putih (unchecked style), Merah hanya jika peer-checked aktif --}}
                                    <div
                                        class="w-10 h-10 mx-auto rounded-lg border-2 flex items-center justify-center transition-all duration-200
                                            bg-white border-slate-200 text-transparent hover:border-slate-300 hover:bg-slate-50
                                            peer-checked:bg-red-500 peer-checked:border-red-600 peer-checked:text-white peer-checked:shadow-md peer-checked:scale-105">

                                        {{-- Icon Silang (X) --}}
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                </label>
                                </td>
                                @endfor
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Footer Action --}}
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <a href="{{ route('guru.index') }}"
                    class="text-slate-500 hover:text-slate-700 font-medium px-4 py-2 rounded-lg hover:bg-slate-200 transition text-sm">
                    &larr; Kembali
                </a>
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg hover:shadow-indigo-500/30 transition transform hover:-translate-y-0.5">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection