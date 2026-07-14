@php use Illuminate\Support\Str; @endphp
<!DOCTYPE html>
<html lang="{{ env('APP_LANG_HTML') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ __('basic.sitemap') }} | Oatllo</title>
    <meta name="description" content="Mapa strony Oatllo — wszystkie artykuły, kategorie, tagi i kursy w jednym miejscu.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="robots" content="index, follow">
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="canonical" href="{{ route('site.map') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ __('basic.sitemap') }} | Oatllo">
    <meta property="og:description" content="Mapa strony Oatllo — wszystkie artykuły, kategorie, tagi i kursy.">
    <meta property="og:url" content="{{ route('site.map') }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:image" content="{{ asset('assets/images/logo-512.jpg') }}">

    <link rel="alternate" type="application/rss+xml" title="Oatllo RSS Feed" href="{{ route('feed') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/fontawesome.min.css" integrity="sha512-v8QQ0YQ3H4K6Ic3PJkym91KoeNT5S3PnDKvqnwqFD1oiqIl653crGZplPdU5KKtHjO0QKcQ2aUlQZYjHczkmGw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <style>
        body { font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif; }
        .glass { background-color: rgba(10,10,10,.72); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .hero-glow { background: radial-gradient(60% 50% at 50% 0%, rgba(244,63,94,.18) 0%, rgba(244,63,94,0) 70%); }
    </style>
</head>
<body class="bg-neutral-950 text-neutral-100 antialiased">
{!! \App\Services\HomeService::getTagManagerBODY() !!}

<!-- NAV (sticky glass) -->
<div x-data="{ open: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 20">
    <header class="fixed inset-x-0 top-0 z-50 transition-colors duration-300" :class="scrolled ? 'glass border-b border-white/5' : ''">
        <nav class="mx-auto flex max-w-7xl items-center justify-between p-5 lg:px-8" aria-label="Global">
            <div class="flex lg:flex-1">
                <a href="{{ route('index') }}" class="-m-1.5 p-1.5"><div class="logo_oatllo">oatllo</div></a>
            </div>
            <div class="flex lg:hidden">
                <button type="button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-300" @click="open = !open" aria-label="Open menu"><i class="fa-solid fa-bars text-xl"></i></button>
            </div>
            <div class="hidden lg:flex lg:gap-x-10">
                <a href="{{ route('index') }}" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">Blog</a>
                <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">{{ __('basic.courses') }}</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200"><i class="fa-brands fa-linkedin mr-1"></i>LinkedIn</a>
            </div>
        </nav>
    </header>

    <!-- Mobile menu poza naglowkiem -->
    <div class="lg:hidden" role="dialog" aria-modal="true" x-show="open" x-cloak>
        <div class="fixed inset-0 z-50 bg-black/60" @click="open = false"></div>
        <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-neutral-900 px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-white/10">
            <div class="flex items-center justify-between">
                <a href="{{ route('index') }}" class="-m-1.5 p-1.5"><div class="logo_oatllo">oatllo</div></a>
                <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-300" @click="open = false" aria-label="Close menu"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <div class="mt-6 space-y-2">
                <a href="{{ route('index') }}" class="block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">Blog</a>
                <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.courses') }}</a>
            </div>
        </div>
    </div>
</div>

<main>
    <!-- HERO -->
    <section class="relative isolate overflow-hidden pt-36 pb-10 sm:pt-44">
        <div class="absolute inset-0 -z-10 hero-glow" aria-hidden="true"></div>
        <nav aria-label="Breadcrumb" class="mx-auto mb-8 max-w-5xl px-4 sm:px-6 lg:px-8">
            <ol class="flex flex-wrap justify-center gap-2 text-sm text-neutral-500" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                    <a href="{{ route('index') }}" itemprop="item" class="hover:text-rose-400"><span itemprop="name">{{ __('basic.home') }}</span></a>
                    <meta itemprop="position" content="1" />
                </li>
                <li>&#8250;</li>
                <li class="text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                    <span itemprop="name">{{ __('basic.sitemap') }}</span>
                    <meta itemprop="item" content="{{ route('site.map') }}" />
                    <meta itemprop="position" content="2" />
                </li>
            </ol>
        </nav>
        <header class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
            <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl">
                {{ __('basic.sitemap') }} <span class="bg-gradient-to-r from-rose-400 to-pink-500 bg-clip-text text-transparent">Oatllo</span>
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-neutral-400">
                Wszystkie kategorie, kursy, tagi i artykuły w jednym miejscu.
            </p>
        </header>
    </section>

    <div class="mx-auto max-w-5xl px-4 pb-16 sm:px-6 lg:px-8 space-y-14">
        <!-- Kategorie -->
        @if($categories->count() > 0)
            <section>
                <h2 class="mb-5 flex items-center gap-3 text-2xl font-bold text-white"><i class="fa-solid fa-folder text-rose-400"></i> {{ __('basic.categories') }}</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($categories as $cat)
                        <a href="{{ route('blog.list.category', ['slug' => $cat->slug]) }}" class="rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-sm text-neutral-300 hover:border-rose-400/40 hover:text-white transition-colors duration-200">{{ $cat->name }}</a>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Kursy -->
        @if($courses->count() > 0)
            <section>
                <h2 class="mb-5 flex items-center gap-3 text-2xl font-bold text-white"><i class="fa-solid fa-graduation-cap text-emerald-400"></i> {{ __('basic.courses') }}</h2>
                <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach($courses as $course)
                        <li><a href="{{ $course->getRoute() }}" class="inline-flex items-center gap-2 text-neutral-300 hover:text-emerald-400 transition-colors duration-200"><i class="fa-solid fa-angle-right text-xs text-emerald-400/70"></i>{{ $course->title_list ?: $course->name }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endif

        <!-- Tagi -->
        @if($tags->count() > 0)
            <section>
                <h2 class="mb-5 flex items-center gap-3 text-2xl font-bold text-white"><i class="fa-solid fa-tags text-rose-400"></i> {{ __('basic.tags') }}</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                        <a href="{{ route('blogTag', ['tag' => $tag->slug]) }}" class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-neutral-300 hover:border-rose-400/40 hover:text-white transition-colors duration-200">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Artykuły -->
        @if($articles->count() > 0)
            <section>
                <h2 class="mb-5 flex items-center gap-3 text-2xl font-bold text-white"><i class="fa-solid fa-newspaper text-rose-400"></i> {{ __('basic.articles') }} <span class="text-base font-normal text-neutral-500">({{ $articles->count() }})</span></h2>
                <ul class="grid grid-cols-1 gap-x-8 gap-y-2 md:grid-cols-2">
                    @foreach($articles as $article)
                        <li><a href="{{ $article->getRoute() }}" class="inline-flex items-start gap-2 text-neutral-300 hover:text-rose-400 transition-colors duration-200"><i class="fa-solid fa-angle-right mt-1 text-xs text-rose-400/70"></i><span>{{ $article->name }}</span></a></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</main>

@include('partials.site_footer')
</body>
</html>
