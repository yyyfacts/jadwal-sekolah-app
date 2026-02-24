<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - SISTEM PENJADWALAN SMAN 1 SAMPANG</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
    [x-cloak] {
        display: none !important;
    }

    body {
        font-family: 'Inter', sans-serif;
    }

    h1,
    h2,
    .font-orbitron {
        font-family: 'Orbitron', sans-serif;
    }

    /* Animated Background Gradients */
    .bg-animate {
        background-size: 400%;
        animation: move-bg 15s infinite alternate;
    }

    @keyframes move-bg {
        0% {
            background-position: 0% 50%;
        }

        100% {
            background-position: 100% 50%;
        }
    }

    /* Matrix/Data Stream Effect */
    .cyber-grid {
        background-image:
            linear-gradient(rgba(59, 130, 246, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(59, 130, 246, 0.05) 1px, transparent 1px);
        background-size: 50px 50px;
        perspective: 1000px;
        transform-style: preserve-3d;
        animation: grid-move 20s linear infinite;
    }

    @keyframes grid-move {
        0% {
            transform: translateY(0) rotateX(20deg);
        }

        100% {
            transform: translateY(50px) rotateX(20deg);
        }
    }

    /* Glow Effects */
    .glow-text {
        text-shadow: 0 0 15px rgba(59, 130, 246, 0.6);
    }

    .glow-box {
        box-shadow: 0 0 30px rgba(59, 130, 246, 0.15), inset 0 0 20px rgba(59, 130, 246, 0.05);
    }

    /* Loading Bar Animation - DIPERCEPAT DARI 2.5s MENJADI 1s */
    @keyframes load {
        0% {
            width: 0%;
        }

        100% {
            width: 100%;
        }
    }

    .loading-bar {
        animation: load 1s ease-in-out forwards;
    }
    </style>
</head>

<body class="bg-slate-950 text-slate-100 antialiased overflow-hidden">

    <div class="fixed inset-0 z-0 bg-gradient-to-br from-slate-950 via-[#0f172a] to-blue-950 bg-animate"></div>
    <div class="fixed inset-0 z-0 cyber-grid opacity-40"></div>

    <div
        class="fixed top-[-10%] left-[-10%] w-96 h-96 bg-blue-600 rounded-full mix-blend-screen filter blur-[120px] opacity-20 animate-pulse">
    </div>
    <div class="fixed bottom-[-10%] right-[-10%] w-96 h-96 bg-cyan-600 rounded-full mix-blend-screen filter blur-[120px] opacity-20 animate-pulse"
        style="animation-delay: 2s"></div>

    {{-- DIPERCEPAT: setTimeout diubah dari 2800ms ke 1000ms --}}
    <div x-data="{ showIntro: true }" x-init="setTimeout(() => showIntro = false, 1000)"
        class="relative min-h-screen flex items-center justify-center z-10">

        {{-- BAGIAN 1: INTRO ANIMATION (LOGO SEKOLAH) --}}
        {{-- DIPERCEPAT: duration-700 diubah ke duration-300 --}}
        <div x-show="showIntro" x-transition:leave="transition ease-in-out duration-300"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-110 blur-md"
            class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-950">

            <div class="mb-8 relative">
                <div class="absolute inset-0 bg-cyan-500 blur-3xl opacity-30 rounded-full animate-pulse"></div>
                <div class="relative w-32 h-32 border-4 border-cyan-500/30 rounded-full border-t-cyan-400 animate-spin">
                </div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <img src="{{ asset('img/logo-sekolah.png') }}"
                        class="w-20 h-20 object-contain drop-shadow-[0_0_15px_rgba(255,255,255,0.3)]">
                </div>
            </div>

            <h1 class="text-4xl md:text-5xl font-black tracking-widest mb-4 glow-text text-white font-orbitron">
                SYSTEM <span class="text-cyan-400">ACCESS</span>
            </h1>

            <div class="w-64 h-1 bg-slate-800 rounded-full overflow-hidden border border-slate-700 mt-4">
                <div class="h-full bg-cyan-500 loading-bar shadow-[0_0_10px_#06b6d4]"></div>
            </div>
        </div>

        {{-- BAGIAN 2: HALAMAN LOGIN UTAMA --}}
        {{-- DIPERCEPAT: duration-1000 delay-300 diubah ke duration-500 delay-100 --}}
        <div x-show="!showIntro" x-cloak x-transition:enter="transition ease-out duration-500 delay-100"
            x-transition:enter-start="opacity-0 translate-y-8 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100" class="w-full max-w-md px-6 py-8">

            <div
                class="backdrop-blur-2xl bg-slate-900/70 rounded-3xl shadow-[0_0_50px_rgba(8,145,178,0.15)] border border-white/10 p-8 sm:p-10 relative overflow-hidden glow-box">

                <div
                    class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-cyan-500 to-transparent opacity-60">
                </div>
                <div
                    class="absolute bottom-0 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-blue-500 to-transparent opacity-60">
                </div>

                {{-- HEADER LOGIN --}}
                <div class="text-center mb-10 relative z-10">
                    <div class="relative inline-block group mb-6">
                        <div
                            class="absolute inset-0 bg-cyan-500 rounded-full blur-2xl opacity-20 group-hover:opacity-40 transition duration-700">
                        </div>
                        <div
                            class="relative flex items-center justify-center w-24 h-24 rounded-full bg-gradient-to-b from-white/10 to-white/5 border border-white/20 shadow-2xl p-4 backdrop-blur-sm">
                            <img src="{{ asset('img/logo-sekolah.png') }}" alt="Logo Sekolah"
                                class="w-full h-full object-contain drop-shadow-md transform group-hover:scale-110 transition duration-500">
                        </div>
                    </div>

                    <h2 class="text-3xl font-black text-white font-orbitron tracking-wider glow-text whitespace-nowrap">
                        SMAN 1 SAMPANG
                    </h2>

                    <div class="flex items-center justify-center gap-3 mt-3">
                        <div class="h-px w-8 bg-gradient-to-r from-transparent to-cyan-500"></div>
                        <p class="text-cyan-200/90 text-[11px] font-mono tracking-[0.25em] uppercase font-bold">
                            SISTEM PENJADWALAN
                        </p>
                        <div class="h-px w-8 bg-gradient-to-l from-transparent to-cyan-500"></div>
                    </div>
                </div>

                @if ($errors->any())
                <div
                    class="mb-6 p-4 bg-red-500/10 border border-red-500/20 text-red-200 text-xs rounded-lg flex items-start gap-3 backdrop-blur-md">
                    <svg class="w-4 h-4 flex-shrink-0 text-red-400 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                    <div class="space-y-1">
                        @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5 relative z-10">
                    @csrf

                    <div class="group">
                        <label for="email"
                            class="block text-[10px] font-bold text-cyan-300/70 mb-1.5 uppercase tracking-widest">Email</label>
                        <div class="relative">
                            <input type="email" name="email" id="email" required autofocus
                                class="w-full px-4 py-3.5 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300 font-medium"
                                placeholder="Masukkan Email Anda">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <svg class="w-5 h-5 text-slate-600 group-focus-within:text-cyan-400 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="group">
                        <label for="password"
                            class="block text-[10px] font-bold text-cyan-300/70 mb-1.5 uppercase tracking-widest">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required
                                class="w-full px-4 py-3.5 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all duration-300 font-medium"
                                placeholder="••••••••">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                <svg class="w-5 h-5 text-slate-600 group-focus-within:text-cyan-400 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <label class="flex items-center cursor-pointer group">
                            <div class="relative">
                                <input type="checkbox" name="remember" class="peer sr-only">
                                <div
                                    class="w-4 h-4 border border-slate-600 rounded bg-slate-800/50 peer-checked:bg-cyan-600 peer-checked:border-cyan-500 transition-all shadow-inner">
                                </div>
                                <svg class="absolute w-3 h-3 text-white hidden peer-checked:block top-0.5 left-0.5"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span
                                class="ml-2 text-xs text-slate-400 group-hover:text-cyan-300 transition-colors font-medium">Ingat
                                Saya</span>
                        </label>
                    </div>

                    <button type="submit"
                        class="w-full py-4 px-4 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-[0_0_25px_rgba(6,182,212,0.3)] hover:shadow-[0_0_35px_rgba(6,182,212,0.5)] transition-all duration-300 transform hover:-translate-y-0.5 active:scale-95 flex items-center justify-center gap-3 border-t border-white/20 group">
                        <span
                            class="tracking-[0.15em] font-orbitron text-sm group-hover:tracking-[0.25em] transition-all duration-300">LOGIN</span>
                        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </button>
                </form>
            </div>

            <div class="text-center mt-8 text-[10px] text-slate-600 font-mono tracking-wide">
                <span class="opacity-60 hover:opacity-100 transition cursor-default">SECURE SYSTEM</span>
                <span class="mx-2 text-cyan-800">•</span>
                <span class="opacity-60 hover:opacity-100 transition cursor-default">&copy; {{ date('Y') }} SMAN 1
                    SAMPANG</span>
            </div>
        </div>

    </div>
</body>

</html>