<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Penjadwalan - SMAN 1 Sampang</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    {{-- Scripts & Styles --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['"Plus Jakarta Sans"', 'sans-serif']
                },
                colors: {
                    primary: {
                        50: '#eff6ff',
                        500: '#3b82f6',
                        600: '#2563eb',
                        900: '#1e3a8a'
                    }
                }
            }
        }
    }
    </script>
    <style>
    [x-cloak] {
        display: none !important;
    }

    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
        height: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }
    </style>
</head>

<body class="bg-slate-50 font-sans text-slate-800 antialiased flex flex-col min-h-screen">

    {{-- ========================================================= --}}
    {{-- 1. NAVBAR UTAMA (STICKY) --}}
    {{-- ========================================================= --}}
    <nav x-data="{ mobileMenuOpen: false }"
        class="bg-[#0f172a] text-white shadow-xl sticky top-0 z-50 h-20 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full">
            <div class="flex items-center justify-between h-full">

                {{-- A. LOGO --}}
                <div class="flex items-center gap-3">
                    <a href="{{ url('/') }}" class="relative flex-shrink-0 group">
                        <div
                            class="absolute inset-0 bg-blue-500 rounded-full blur opacity-20 group-hover:opacity-40 transition">
                        </div>
                        <img src="{{ asset('img/logo-sekolah.png') }}" alt="Logo"
                            class="relative w-10 h-10 object-contain p-0.5 bg-white/10 rounded-full border border-white/20">
                    </a>
                    <div class="leading-tight">
                        <h1 class="font-extrabold text-[15px] tracking-wide">SMAN 1 SAMPANG</h1>
                        <p class="text-[10px] font-bold text-slate-400 tracking-[0.15em] uppercase hidden sm:block">
                            Sistem Penjadwalan
                        </p>
                    </div>
                </div>

                {{-- B. MENU DESKTOP (Hidden on Mobile) --}}
                <div class="hidden lg:flex items-center gap-1 bg-white/5 px-2 py-1.5 rounded-xl border border-white/5">

                    {{-- 1. MENU DASHBOARD --}}
                    <a href="{{ url('/') }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->is('/') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Dashboard
                    </a>

                    {{-- 2. DROPDOWN DATA MASTER --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false"
                            class="flex items-center gap-1 px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('guru.*') || request()->routeIs('mapel.*') || request()->routeIs('kelas.*') || request()->routeIs('tahun-pelajaran.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                            Data Master
                            <svg class="w-4 h-4 ml-1 opacity-70" :class="{'rotate-180': open}" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        {{-- Isi Dropdown --}}
                        <div x-show="open" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0" x-cloak
                            class="absolute top-full left-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-1 text-slate-800 z-50 border border-slate-100 ring-1 ring-black ring-opacity-5">

                            <div class="px-4 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Data
                                Akademik</div>

                            <a href="{{ route('guru.index') }}"
                                class="block px-4 py-2 text-sm hover:bg-slate-50 hover:text-blue-600 {{ request()->routeIs('guru.*') ? 'text-blue-600 font-bold bg-slate-50' : '' }}">
                                Data Guru
                            </a>
                            <a href="{{ route('mapel.index') }}"
                                class="block px-4 py-2 text-sm hover:bg-slate-50 hover:text-blue-600 {{ request()->routeIs('mapel.*') ? 'text-blue-600 font-bold bg-slate-50' : '' }}">
                                Mata Pelajaran
                            </a>
                            <a href="{{ route('kelas.index') }}"
                                class="block px-4 py-2 text-sm hover:bg-slate-50 hover:text-blue-600 {{ request()->routeIs('kelas.*') ? 'text-blue-600 font-bold bg-slate-50' : '' }}">
                                Data Kelas
                            </a>

                            <div class="border-t border-slate-100 my-1"></div>
                            <div class="px-4 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                Konfigurasi</div>

                            <a href="{{ route('tahun-pelajaran.index') }}"
                                class="block px-4 py-2 text-sm hover:bg-slate-50 hover:text-blue-600 {{ request()->routeIs('tahun-pelajaran.*') ? 'text-blue-600 font-bold bg-slate-50' : '' }}">
                                Tahun Pelajaran
                            </a>
                        </div>
                    </div>

                    {{-- 3. MENU JADWAL --}}
                    <a href="{{ route('jadwal.index') }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('jadwal.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Jadwal Pelajaran
                    </a>

                    {{-- Divider --}}
                    <div class="w-px h-5 bg-white/10 mx-2"></div>

                    {{-- 4. MENU ADMIN USER --}}
                    <a href="{{ route('user.index') }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('user.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Admin User
                    </a>
                </div>

                {{-- C. KANAN (PROFILE & HAMBURGER) --}}
                <div class="flex items-center gap-4">

                    {{-- Profile (Desktop Only) --}}
                    <div class="hidden sm:flex items-center gap-3">
                        <div class="text-right leading-tight">
                            <div class="text-sm font-bold">{{ Auth::user()->name ?? 'Administrator' }}</div>
                            <div class="text-[10px] text-blue-400 font-bold uppercase">Admin</div>
                        </div>
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open"
                                class="flex items-center justify-center w-10 h-10 rounded-full border-2 border-white/20 bg-white/10 hover:bg-white/20 transition overflow-hidden shadow-inner">
                                <span class="font-bold text-sm">{{ substr(Auth::user()->name ?? 'A', 0, 1) }}</span>
                            </button>

                            {{-- Dropdown Profile --}}
                            <div x-show="open" @click.away="open = false" x-cloak
                                class="absolute right-0 mt-4 w-48 bg-white rounded-xl shadow-2xl py-2 text-slate-800 z-50 border border-slate-100 origin-top-right ring-1 ring-black ring-opacity-5"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100">

                                <div class="px-4 py-2 border-b border-slate-100 bg-slate-50/50">
                                    <p class="text-xs text-slate-500">Login sebagai:</p>
                                    <p class="text-sm font-bold text-slate-800 truncate">{{ Auth::user()->email }}</p>
                                </div>

                                <a href="{{ route('profile.edit') }}"
                                    class="block px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-blue-600 transition">Edit
                                    Profil</a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full text-left block px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">Keluar</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Hamburger (Mobile Only) --}}
                    <div class="flex lg:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2 text-slate-300 hover:text-white transition focus:outline-none rounded-lg hover:bg-white/10">
                            <svg x-show="!mobileMenuOpen" class="w-7 h-7" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                            <svg x-show="mobileMenuOpen" x-cloak class="w-7 h-7" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- D. MOBILE MENU DROPDOWN --}}
        <div x-show="mobileMenuOpen" x-cloak
            class="lg:hidden bg-[#0f172a] border-t border-white/10 shadow-2xl absolute top-20 left-0 w-full z-40 overflow-hidden"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-2 height-0"
            x-transition:enter-end="opacity-100 translate-y-0 height-auto">

            <div class="px-4 py-6 space-y-2">

                {{-- Dashboard --}}
                <a href="{{ url('/') }}"
                    class="flex items-center gap-4 px-4 py-3.5 rounded-xl text-base font-medium transition-all {{ request()->is('/') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <span class="text-xl">🏠</span> Dashboard
                </a>

                {{-- Mobile Dropdown: Data Master --}}
                <div x-data="{ expanded: false }"
                    class="rounded-xl overflow-hidden {{ request()->routeIs('guru.*') || request()->routeIs('mapel.*') || request()->routeIs('kelas.*') || request()->routeIs('tahun-pelajaran.*') ? 'bg-white/5 border border-white/10' : '' }}">
                    <button @click="expanded = !expanded"
                        class="w-full flex items-center justify-between px-4 py-3.5 text-base font-medium text-slate-300 hover:text-white hover:bg-white/10 transition-all">
                        <div class="flex items-center gap-4">
                            <span class="text-xl">📂</span> Data Master
                        </div>
                        <svg class="w-5 h-5 transition-transform" :class="{'rotate-180': expanded}" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>

                    <div x-show="expanded" class="bg-black/20 pb-2">
                        <a href="{{ route('guru.index') }}"
                            class="block pl-14 pr-4 py-2.5 text-sm {{ request()->routeIs('guru.*') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-white' }}">Data
                            Guru</a>
                        <a href="{{ route('mapel.index') }}"
                            class="block pl-14 pr-4 py-2.5 text-sm {{ request()->routeIs('mapel.*') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-white' }}">Mata
                            Pelajaran</a>
                        <a href="{{ route('kelas.index') }}"
                            class="block pl-14 pr-4 py-2.5 text-sm {{ request()->routeIs('kelas.*') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-white' }}">Data
                            Kelas</a>
                        <a href="{{ route('tahun-pelajaran.index') }}"
                            class="block pl-14 pr-4 py-2.5 text-sm {{ request()->routeIs('tahun-pelajaran.*') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-white' }}">Tahun
                            Pelajaran</a>
                    </div>
                </div>

                {{-- Jadwal --}}
                <a href="{{ route('jadwal.index') }}"
                    class="flex items-center gap-4 px-4 py-3.5 rounded-xl text-base font-medium transition-all {{ request()->routeIs('jadwal.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <span class="text-xl">📅</span> Penjadwalan
                </a>

                {{-- Admin User --}}
                <a href="{{ route('user.index') }}"
                    class="flex items-center gap-4 px-4 py-3.5 rounded-xl text-base font-medium transition-all {{ request()->routeIs('user.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                    <span class="text-xl">⚙️</span> Admin User
                </a>

                {{-- Divider --}}
                <div class="border-t border-white/10 my-4 pt-4">
                    <div class="flex items-center gap-3 px-4 mb-4">
                        <div
                            class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center font-bold text-white border border-white/20">
                            {{ substr(Auth::user()->name ?? 'A', 0, 1) }}</div>
                        <div>
                            <div class="text-white font-medium">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-slate-400">{{ Auth::user()->email }}</div>
                        </div>
                    </div>

                    <a href="{{ route('profile.edit') }}"
                        class="block px-4 py-3 rounded-xl text-slate-300 hover:bg-white/10 hover:text-white mb-2">Edit
                        Profil</a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 hover:text-red-300 font-medium transition">
                            🚪 Keluar Sistem
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    {{-- ========================================================= --}}
    {{-- 2. CONTENT AREA --}}
    {{-- ========================================================= --}}
    <main class="flex-grow max-w-7xl w-full mx-auto py-8 px-4 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    {{-- ========================================================= --}}
    {{-- 3. FOOTER --}}
    {{-- ========================================================= --}}
    <footer class="bg-white border-t border-slate-200 py-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-slate-400 text-xs font-semibold tracking-wide">
                &copy; {{ date('Y') }} SMAN 1 SAMPANG. <span class="hidden sm:inline">Sistem Penjadwalan
                    Terintegrasi.</span>
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>