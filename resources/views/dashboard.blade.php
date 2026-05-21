@extends('layouts.app')

@section('content')
<div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Banner Welcome --}}
    <div class="bg-gradient-to-r from-blue-600 to-indigo-800 rounded-2xl shadow-xl overflow-hidden mb-8">
        <div class="px-8 py-10 relative z-10">
            <h1 class="text-3xl font-extrabold text-white mb-2">Selamat Datang,
                {{ Auth::user()->name ?? 'Administrator' }}! 👋</h1>
            <p class="text-blue-100 text-sm max-w-xl">Ini adalah pusat kendali Sistem Penjadwalan SMAN 1 Sampang. Pantau
                statistik data dan kelola jadwal dengan mudah melalui menu di atas.</p>
        </div>
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
    </div>

    {{-- Kartu Statistik --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

        {{-- Card Guru --}}
        <div
            class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-14 h-14 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-2xl">👨‍🏫
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Guru</p>
                <h3 class="text-2xl font-extrabold text-slate-800">{{ $totalGuru }}</h3>
            </div>
        </div>

        {{-- Card Kelas --}}
        <div
            class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div
                class="w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">
                🏫</div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Kelas</p>
                <h3 class="text-2xl font-extrabold text-slate-800">{{ $totalKelas }}</h3>
            </div>
        </div>

        {{-- Card Mapel --}}
        <div
            class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-14 h-14 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-2xl">📚
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Mata Pelajaran</p>
                <h3 class="text-2xl font-extrabold text-slate-800">{{ $totalMapel }}</h3>
            </div>
        </div>

        {{-- Card Jadwal --}}
        <div
            class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-14 h-14 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center text-2xl">
                📅</div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Slot Jadwal Aktif</p>
                <h3 class="text-2xl font-extrabold text-slate-800">{{ $totalJadwal }}</h3>
            </div>
        </div>

    </div>
</div>
@endsection