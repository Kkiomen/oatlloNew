<!DOCTYPE html>
<html lang="{{ env('APP_LANG_HTML', 'en') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <title>404 – Nie znaleziono strony | Oatllo</title>
    <meta name="description" content="Nie znaleziono strony (404).">
    <meta name="theme-color" content="#0a0a0a">
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}">
    <link rel="preload" href="{{ asset('assets/fonts/montserrat/montserrat-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"  media="print" onload="this.media='all'" /><noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" /></noscript>
<style>
        body { font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif; }
        .glow { background: radial-gradient(50% 50% at 50% 40%, rgba(244,63,94,.18) 0%, rgba(244,63,94,0) 70%); }
        .grid-mask {
            background-image: linear-gradient(to right, rgba(255,255,255,.04) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: radial-gradient(60% 60% at 50% 40%, #000 0%, transparent 75%);
            -webkit-mask-image: radial-gradient(60% 60% at 50% 40%, #000 0%, transparent 75%);
        }
    </style>
</head>
<body class="min-h-screen bg-neutral-950 text-neutral-100 antialiased">
<div class="relative isolate flex min-h-screen flex-col">
    <div class="absolute inset-0 -z-10 glow" aria-hidden="true"></div>
    <div class="absolute inset-0 -z-10 grid-mask" aria-hidden="true"></div>

    <header class="p-6 lg:px-8">
        <a href="{{ route('index') }}" class="inline-block"><div class="logo_oatllo">oatllo</div></a>
    </header>

    <main class="mx-auto flex w-full max-w-2xl flex-1 flex-col items-center justify-center px-6 text-center">
        <p class="bg-gradient-to-r from-rose-400 to-pink-500 bg-clip-text text-8xl font-extrabold tracking-tight text-transparent sm:text-9xl">404</p>
        <h1 class="mt-4 text-3xl font-bold text-white sm:text-4xl">Nie znaleziono strony</h1>
        <p class="mx-auto mt-4 max-w-md text-neutral-400">
            Strona, której szukasz, nie istnieje albo została przeniesiona. Sprawdź adres lub wróć na stronę główną.
        </p>

        <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
            <a href="{{ route('index') }}" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-rose-500/30 hover:bg-rose-400 transition-colors duration-200">
                <i class="fa-solid fa-house"></i> Strona główna
            </a>
            <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-base font-semibold text-white hover:bg-white/10 transition-colors duration-200">
                <i class="fa-solid fa-book-open"></i> Blog
            </a>
        </div>
    </main>

    <footer class="p-6 text-center text-sm text-neutral-600">
        &copy; {{ date('Y') }} Oatllo · Jakub Owsianka
    </footer>
</div>
</body>
</html>
