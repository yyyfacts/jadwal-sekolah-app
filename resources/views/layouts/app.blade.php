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
    {{-- 1. NAVBAR UTAMA (HORIZONTAL) --}}
    {{-- ========================================================= --}}
    <nav class="bg-[#0f172a] text-white shadow-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">

                {{-- LOGO & BRAND --}}
                <div class="flex items-center gap-4">
                    <div class="relative flex-shrink-0 group">
                        <div
                            class="absolute inset-0 bg-blue-500 rounded-full blur opacity-20 group-hover:opacity-40 transition">
                        </div>
                        <img src="{{ asset('img/logo-sekolah.png') }}" alt="Logo"
                            class="relative w-10 h-10 object-contain p-0.5 bg-white/10 rounded-full border border-white/20">
                    </div>
                    <div class="hidden md:block leading-tight">
                        <h1 class="font-extrabold text-[15px] tracking-wide">SMAN 1 SAMPANG</h1>
                        <p class="text-[10px] font-bold text-slate-400 tracking-[0.15em] uppercase">Sistem Penjadwalan
                        </p>
                    </div>
                </div>

                {{-- MENU NAVIGASI (Desktop) --}}
                <div class="hidden lg:flex items-center gap-1 bg-white/5 px-2 py-1.5 rounded-xl border border-white/5">
                    <a href="{{ route('guru.index') }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('guru.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Data Guru
                    </a>
                    <a href="{{ route('mapel.index') }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('mapel.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Mapel
                    </a>
                    <a href="{{ route('kelas.index') }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('kelas.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Kelas
                    </a>
                    <a href="{{ route('jadwal.index') }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('jadwal.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Jadwal
                    </a>

                    {{-- Divider Kecil --}}
                    <div class="w-px h-5 bg-white/10 mx-2"></div>

                    <a href="{{ route('user.index') }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('user.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        Admin User
                    </a>
                </div>

                {{-- USER PROFILE (Kanan) --}}
                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block leading-tight">
                        <div class="text-sm font-bold">{{ Auth::user()->name ?? 'Administrator' }}</div>
                        <div class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">Admin</div>
                    </div>

                    {{-- Dropdown Profile --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                            class="flex items-center justify-center w-10 h-10 rounded-full border-2 border-white/20 bg-gradient-to-br from-blue-500 to-indigo-600 shadow-md hover:scale-105 transition-all focus:outline-none">
                            <span class="text-white font-bold text-sm tracking-tighter">
                                {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                            </span>
                        </button>

                        <div x-show="open" @click.away="open = false" x-cloak
                            class="absolute right-0 mt-3 w-56 bg-white rounded-xl shadow-2xl border border-slate-100 py-2 z-50 text-slate-800 transform origin-top-right transition-all"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                            <div class="px-4 py-3 border-b border-slate-50 bg-slate-50/50">
                                <div class="text-sm font-bold text-slate-800 truncate">{{ Auth::user()->name }}</div>
                                <div class="text-[10px] text-slate-500 truncate">{{ Auth::user()->email }}</div>
                            </div>

                            <div class="px-2 py-1">
                                <a href="{{ route('profile.edit') }}"
                                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-blue-50 hover:text-blue-700 transition-all font-medium">
                                    <span>👤</span> Edit Profil
                                </a>

                                <div class="border-t border-slate-100 my-1"></div>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="flex w-full items-center gap-3 px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-all font-medium text-left">
                                        <span>⭕</span> Keluar Sistem
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- ========================================================= --}}
    {{-- 2. SUB-HEADER / BREADCRUMB --}}
    {{-- ========================================================= --}}
    <div class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center h-12 gap-2 text-xs font-medium uppercase tracking-wider text-slate-400">
                <span>Pages</span>
                <span class="text-slate-300">/</span>
                <span class="text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                    @if(request()->routeIs('guru.*')) Data Guru
                    @elseif(request()->routeIs('mapel.*')) Mata Pelajaran
                    @elseif(request()->routeIs('kelas.*')) Data Kelas
                    @elseif(request()->routeIs('jadwal.*')) Penjadwalan
                    @elseif(request()->routeIs('user.*')) Kelola User
                    @elseif(request()->routeIs('profile.*')) Profil Saya
                    @else Dashboard
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- 3. CONTENT AREA --}}
    {{-- ========================================================= --}}
    <main class="flex-grow max-w-7xl w-full mx-auto py-8 px-4 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    {{-- ========================================================= --}}
    {{-- 4. FOOTER --}}
    {{-- ========================================================= --}}
    <footer class="bg-white border-t border-slate-200 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-slate-400 text-xs font-medium">
                &copy; {{ date('Y') }} SMAN 1 SAMPANG. All rights reserved.
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>