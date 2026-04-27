<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- ======================================================== --}}
    {{-- META TAGS UNTUK LINK PREVIEW (WHATSAPP, TELEGRAM, DLL) --}}
    {{-- ======================================================== --}}
    <title>Sistem Penjadwalan - SMAN 1 Sampang</title>
    <meta name="description" content="Aplikasi cerdas untuk manajemen jadwal pelajaran SMAN 1 Sampang.">

    {{-- Open Graph (Facebook, WA, LinkedIn) --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Sistem Penjadwalan - SMAN 1 Sampang">
    <meta property="og:description" content="web untuk manajemen jadwal pelajaran SMAN 1 Sampang.">
    {{-- Ganti 'preview.png' dengan nama file asli kamu yang ukuran 237x212 --}}
    <meta property="og:image" content="{{ asset('img/logo-sekolah.png') }}">

    {{-- Twitter Card (Pakai 'summary' karena gambar berbentuk kotak kecil) --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="Sistem Penjadwalan - SMAN 1 Sampang">
    <meta name="twitter:description" content="web untuk manajemen jadwal pelajaran SMAN 1 Sampang.">
    <meta name="twitter:image" content="{{ asset('img/logo-sekolah.png') }}">
    {{-- ======================================================== --}}
    <title>Masuk - SISTEM PENJADWALAN SMAN 1 SAMPANG</title>
    <link rel="icon" href="{{ asset('img/logo-sekolah.png') }}" type="image/png">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
    body {
        font-family: 'Inter', sans-serif;
        /* Background gradien radial halus khas dark mode modern */
        background-color: #0B1120;
        background-image: radial-gradient(ellipse at center, rgba(30, 41, 59, 0.4) 0%, #0B1120 100%);
    }
    </style>
</head>

<body
    class="text-slate-100 antialiased min-h-screen flex flex-col items-center justify-center relative overflow-hidden">

    <div
        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-blue-600/10 rounded-full blur-[100px] pointer-events-none">
    </div>

    <div class="w-full max-w-[400px] px-6 relative z-10">
        <div class="bg-[#111827]/80 backdrop-blur-xl rounded-2xl border border-slate-700/50 p-8 shadow-2xl">

            <div class="flex justify-center mb-6">
                <div class="relative w-20 h-20 flex items-center justify-center">
                    <div class="absolute inset-[-15px] bg-blue-500/20 rounded-full blur-xl"></div>
                    <div class="absolute inset-[-10px] rounded-full border border-slate-600/30"></div>
                    <div class="absolute inset-[-2px] rounded-full border border-blue-500/30"></div>

                    <div
                        class="relative w-full h-full rounded-full bg-slate-800/80 border border-slate-600/50 flex items-center justify-center shadow-[0_0_15px_rgba(59,130,246,0.2)]">
                        <img src="{{ asset('img/logo-sekolah.png') }}" alt="Logo"
                            class="w-10 h-10 object-contain drop-shadow-[0_0_8px_rgba(255,255,255,0.3)]">
                    </div>
                </div>
            </div>

            <div class="text-center mb-8">
                <h1
                    class="text-2xl font-bold text-white tracking-wide mb-1 drop-shadow-[0_0_8px_rgba(255,255,255,0.3)]">
                    SMAN 1 SAMPANG</h1>
                <p class="text-[10px] text-slate-400 tracking-[0.15em] uppercase font-medium">Sistem Penjadwalan</p>
            </div>

            @if ($errors->any())
            <div class="mb-6 p-3 bg-red-500/10 border border-red-500/20 text-red-300 text-xs rounded-lg text-center">
                @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="username"
                        class="block text-[10px] font-semibold text-slate-300 mb-1.5 uppercase tracking-wide">Nama
                        Pengguna</label>
                    <div class="relative">
                        <input type="text" name="username" id="username" value="{{ old('username') }}" required
                            autofocus
                            class="w-full bg-[#1E293B] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                            placeholder="Masukkan Nama Pengguna">
                        <div
                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="password"
                        class="block text-[10px] font-semibold text-slate-300 mb-1.5 uppercase tracking-wide">Kata
                        Sandi</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                            class="w-full bg-[#1E293B] border border-slate-700/80 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"
                            placeholder="••••••••">
                        <div
                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="flex items-center pt-1 pb-1">
                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" name="remember"
                            class="w-4 h-4 rounded bg-[#1E293B] border-slate-600 text-blue-500 focus:ring-blue-500 focus:ring-offset-0 focus:ring-offset-transparent cursor-pointer">
                        <span class="ml-2 text-[11px] text-slate-400 font-medium">Ingat Saya</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full py-3.5 bg-gradient-to-r from-[#2563eb] to-[#38bdf8] hover:opacity-90 text-white font-semibold rounded-lg text-sm transition-opacity flex items-center justify-center gap-2 shadow-[0_0_15px_rgba(56,189,248,0.2)]">
                    MASUK
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <div class="absolute bottom-6 w-full text-center">
        <p class="text-[9px] text-slate-500/60 font-mono tracking-widest uppercase">
            SISTEM TERPROTEKSI &nbsp;&nbsp;•&nbsp;&nbsp; @ {{ date('Y') }} SMAN 1 SAMPANG
        </p>
    </div>

</body>

</html>