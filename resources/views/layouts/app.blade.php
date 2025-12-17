<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Penjadwalan - SMAN 1 Sampang</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

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

    /* Smooth Sidebar Transition */
    aside {
        transition: width 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }

    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    </style>
</head>

<body class="bg-slate-50 font-sans text-slate-800 antialiased" x-data="{ sidebarOpen: true }">

    <div class="flex h-screen overflow-hidden">

        {{-- ========================================================= --}}
        {{-- SIDEBAR --}}
        {{-- ========================================================= --}}
        <aside :class="sidebarOpen ? 'w-[280px]' : 'w-[88px]'" class="flex flex-col fixed md:relative z-30 h-full shadow-2xl border-r border-white/5
                   bg-gradient-to-b from-[#0f172a] via-[#1e293b] to-[#0f172a]">

            {{-- 1. HEADER SIDEBAR --}}
            <div
                class="relative flex items-center h-[90px] px-6 border-b border-white/10 shrink-0 overflow-hidden group">
                <div class="absolute top-0 left-0 w-full h-full bg-blue-600/5 pointer-events-none"></div>

                <div class="flex items-center w-full gap-4 transition-all duration-300"
                    :class="sidebarOpen ? 'justify-start' : 'justify-center'">

                    {{-- LOGO --}}
                    <div class="relative z-10 flex-shrink-0 group-hover:scale-105 transition-transform duration-300">
                        <div
                            class="absolute inset-0 bg-gradient-to-tr from-blue-500 to-cyan-400 rounded-full blur-lg opacity-40 group-hover:opacity-60 transition duration-500">
                        </div>
                        <img src="{{ asset('img/logo-sekolah.png') }}" alt="Logo"
                            class="relative w-10 h-10 object-contain p-0.5 bg-white/10 backdrop-blur-sm rounded-full border border-white/20 shadow-lg"
                            :class="sidebarOpen ? 'w-11 h-11' : 'w-10 h-10'">
                    </div>

                    {{-- TEKS IDENTITAS --}}
                    <div class="flex flex-col min-w-0 transition-all duration-300" x-show="sidebarOpen"
                        x-transition:enter="transition ease-out duration-300 delay-75"
                        x-transition:enter-start="opacity-0 translate-x-2"
                        x-transition:enter-end="opacity-100 translate-x-0">
                        <h1
                            class="font-extrabold text-white text-[15px] tracking-wide leading-none whitespace-nowrap drop-shadow-md">
                            SMAN 1 SAMPANG
                        </h1>
                        <div class="flex items-center gap-1.5 mt-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                            <p
                                class="text-[10px] font-bold text-slate-400 tracking-[0.1em] uppercase whitespace-nowrap">
                                Sistem Penjadwalan
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. NAVIGASI MENU --}}
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto custom-scrollbar">

                <div x-show="sidebarOpen"
                    class="px-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 transition-opacity duration-300 delay-100">
                    Main Menu
                </div>

                <a href="{{ route('guru.index') }}"
                    class="relative flex items-center px-3 py-3 rounded-xl transition-all duration-300 group
                    {{ request()->routeIs('guru.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20 ring-1 ring-white/10' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span
                        class="text-xl flex-shrink-0 transition-transform group-hover:scale-110 duration-300">👨‍🏫</span>
                    <span class="ml-3 text-sm font-medium whitespace-nowrap transition-all duration-300 origin-left"
                        :class="sidebarOpen ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-5 absolute pointer-events-none'">
                        Data Guru
                    </span>
                    <div x-show="!sidebarOpen"
                        class="absolute left-14 bg-slate-800 text-white text-xs px-2 py-1.5 rounded shadow-xl border border-white/10 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 whitespace-nowrap">
                        Data Guru</div>
                </a>

                <a href="{{ route('mapel.index') }}"
                    class="relative flex items-center px-3 py-3 rounded-xl transition-all duration-300 group
                    {{ request()->routeIs('mapel.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20 ring-1 ring-white/10' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span
                        class="text-xl flex-shrink-0 transition-transform group-hover:scale-110 duration-300">📚</span>
                    <span class="ml-3 text-sm font-medium whitespace-nowrap transition-all duration-300 origin-left"
                        :class="sidebarOpen ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-5 absolute pointer-events-none'">
                        Mata Pelajaran
                    </span>
                    <div x-show="!sidebarOpen"
                        class="absolute left-14 bg-slate-800 text-white text-xs px-2 py-1.5 rounded shadow-xl border border-white/10 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 whitespace-nowrap">
                        Mata Pelajaran</div>
                </a>

                <a href="{{ route('kelas.index') }}"
                    class="relative flex items-center px-3 py-3 rounded-xl transition-all duration-300 group
                    {{ request()->routeIs('kelas.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20 ring-1 ring-white/10' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span
                        class="text-xl flex-shrink-0 transition-transform group-hover:scale-110 duration-300">🏫</span>
                    <span class="ml-3 text-sm font-medium whitespace-nowrap transition-all duration-300 origin-left"
                        :class="sidebarOpen ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-5 absolute pointer-events-none'">
                        Data Kelas
                    </span>
                    <div x-show="!sidebarOpen"
                        class="absolute left-14 bg-slate-800 text-white text-xs px-2 py-1.5 rounded shadow-xl border border-white/10 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 whitespace-nowrap">
                        Data Kelas</div>
                </a>

                <a href="{{ route('jadwal.index') }}"
                    class="relative flex items-center px-3 py-3 rounded-xl transition-all duration-300 group
                    {{ request()->routeIs('jadwal.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20 ring-1 ring-white/10' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span
                        class="text-xl flex-shrink-0 transition-transform group-hover:scale-110 duration-300">🗓️</span>
                    <span class="ml-3 text-sm font-medium whitespace-nowrap transition-all duration-300 origin-left"
                        :class="sidebarOpen ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-5 absolute pointer-events-none'">
                        Jadwal Pelajaran
                    </span>
                    <div x-show="!sidebarOpen"
                        class="absolute left-14 bg-slate-800 text-white text-xs px-2 py-1.5 rounded shadow-xl border border-white/10 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 whitespace-nowrap">
                        Jadwal Pelajaran</div>
                </a>

                <div class="my-4 mx-3 border-t border-white/10"></div>

                <div x-show="sidebarOpen"
                    class="px-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 transition-opacity duration-300 delay-100">
                    Admin
                </div>

                <a href="{{ route('user.index') }}"
                    class="relative flex items-center px-3 py-3 rounded-xl transition-all duration-300 group
                    {{ request()->routeIs('user.*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/20 ring-1 ring-white/10' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <span
                        class="text-xl flex-shrink-0 transition-transform group-hover:scale-110 duration-300">👥</span>
                    <span class="ml-3 text-sm font-medium whitespace-nowrap transition-all duration-300 origin-left"
                        :class="sidebarOpen ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-5 absolute pointer-events-none'">
                        Kelola User
                    </span>
                    <div x-show="!sidebarOpen"
                        class="absolute left-14 bg-slate-800 text-white text-xs px-2 py-1.5 rounded shadow-xl border border-white/10 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 whitespace-nowrap">
                        Kelola User</div>
                </a>

            </nav>

            {{-- 3. TOMBOL TOGGLE --}}
            <div class="p-6 shrink-0 flex justify-center relative z-20">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="group flex items-center justify-center w-10 h-10 rounded-full bg-slate-800/80 text-slate-400 hover:bg-blue-600 hover:text-white border border-white/10 hover:border-blue-500 hover:shadow-[0_0_15px_rgba(37,99,235,0.5)] transition-all duration-500 ease-out focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5 transition-transform duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)]"
                        :class="sidebarOpen ? 'rotate-0' : 'rotate-180'" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

        </aside>

        {{-- ========================================================= --}}
        {{-- MAIN CONTENT --}}
        {{-- ========================================================= --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative bg-[#F8FAFC]">

            {{-- TOP HEADER --}}
            <header
                class="bg-white/80 backdrop-blur-md border-b border-slate-200/80 h-16 flex items-center justify-between px-8 shadow-sm z-20 sticky top-0">

                <div class="flex items-center gap-2 text-sm">
                    <span class="text-slate-400 font-medium">Pages</span>
                    <span class="text-slate-300">/</span>
                    <span
                        class="font-semibold text-slate-800 bg-white px-3 py-1 rounded-full border border-slate-200 shadow-sm text-xs tracking-wide uppercase">
                        @if(request()->routeIs('guru.*')) Data Guru
                        @elseif(request()->routeIs('mapel.*')) Mata Pelajaran
                        @elseif(request()->routeIs('kelas.*')) Data Kelas
                        @elseif(request()->routeIs('jadwal.*')) Penjadwalan
                        @elseif(request()->routeIs('user.*')) Kelola User
                        @else Dashboard
                        @endif
                    </span>
                </div>

                <div class="flex items-center gap-5">
                    <div class="text-right hidden md:block leading-tight">
                        <div class="text-sm font-bold text-slate-800">{{ Auth::user()->name ?? 'Administrator' }}</div>
                        <div class="text-[10px] font-bold text-blue-600 uppercase tracking-wider">Admin</div>
                    </div>

                    {{-- User Dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                            class="flex items-center gap-1 p-0.5 rounded-full border border-slate-200 hover:border-blue-300 hover:ring-2 hover:ring-blue-100 transition-all duration-200 focus:outline-none">
                            <div class="w-9 h-9 rounded-full overflow-hidden bg-slate-100">
                                @if(Auth::user()->avatar)
                                <img src="{{ asset('storage/avatars/' . Auth::user()->avatar) }}" alt="Foto Profil"
                                    class="w-full h-full object-cover">
                                @else
                                <div
                                    class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-500 to-indigo-600 text-white font-bold text-sm">
                                    {{ substr(Auth::user()->name ?? 'A', 0, 1) }}</div>
                                @endif
                            </div>
                        </button>

                        {{-- DROPDOWN MENU --}}
                        <div x-show="open" @click.away="open = false"
                            class="absolute right-0 mt-3 w-60 bg-white rounded-xl shadow-2xl border border-slate-100 py-2 z-50 transform origin-top-right"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 -translate-y-2" style="display: none;" x-cloak>

                            {{-- Header Mobile --}}
                            <div class="px-4 py-3 border-b border-slate-50 md:hidden bg-slate-50/50">
                                <div class="text-sm font-bold text-slate-800">{{ Auth::user()->name ?? 'User' }}</div>
                            </div>

                            <div class="px-2 py-1.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2">Akun
                                    Saya</span>
                            </div>

                            {{-- MENU PROFILE: Menggunakan MX-2 (Margin X) dan W-AUTO agar pas --}}
                            <a href="{{ route('profile.edit') }}"
                                class="flex items-center gap-3 px-3 py-2.5 mx-2 rounded-lg text-sm text-slate-600 hover:bg-blue-50 hover:text-blue-700 transition-all duration-200 group font-medium">
                                <span
                                    class="bg-slate-100 p-1.5 rounded-md group-hover:bg-white group-hover:shadow-sm transition text-slate-500 group-hover:text-blue-600 text-xs">👤</span>
                                Edit Profil
                            </a>

                            <div class="my-1 border-t border-slate-100 mx-2"></div>

                            {{-- LOGOUT: FIX MARGIN & WIDTH --}}
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex w-[calc(100%-1rem)] items-center gap-3 px-3 py-2.5 mx-2 mb-1 rounded-lg text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200 group font-medium">
                                    <span
                                        class="bg-red-50 p-1.5 rounded-md group-hover:bg-white group-hover:shadow-sm text-red-500 transition text-xs">⭕</span>
                                    Keluar Sistem
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- CONTENT AREA --}}
            <main class="flex-1 overflow-y-auto p-6 custom-scrollbar relative scroll-smooth">
                <div class="max-w-7xl mx-auto">
                    @yield('content')
                </div>
            </main>
        </div>

        @hasSection('rightbar')
        <aside
            class="w-80 bg-white border-l border-slate-200 p-6 hidden xl:block overflow-y-auto custom-scrollbar shadow-lg z-20">
            @yield('rightbar')
        </aside>
        @endif
    </div>

    @stack('scripts')
</body>

</html>