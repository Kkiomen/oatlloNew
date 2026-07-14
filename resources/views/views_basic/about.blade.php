@php
    $authorUrl   = route('about.us');
    $authorImage = asset('/assets/images/owsianka_jakub.png');
    $linkedin    = 'https://www.linkedin.com/in/jakub-owsianka-446bb5213/';
    $pageTitle   = 'About Jakub Owsianka – Creator of Oatllo | Oatllo';
    $pageDesc    = 'Jakub Owsianka — PHP & Laravel developer with 10+ years of experience and the creator of Oatllo. I write about backend, architecture, DevOps and AI for developers.';
@endphp
<!DOCTYPE html>
<html lang="{{ env('APP_LANG_HTML') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDesc }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="author" content="Jakub Owsianka">
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="canonical" href="{{ $authorUrl }}">

    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDesc }}">
    <meta property="og:url" content="{{ $authorUrl }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:image" content="{{ $authorImage }}">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDesc }}">
    <meta name="twitter:image" content="{{ $authorImage }}">
    <meta name="twitter:site" content="@Oatllo">

    <link rel="alternate" type="application/rss+xml" title="Oatllo RSS Feed" href="{{ route('feed') }}" />
    <link rel="preload" href="{{ asset('assets/fonts/montserrat/montserrat-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>

    <style>
        body { font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif; }
        .glass { background-color: rgba(10,10,10,.72); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .hero-glow { background: radial-gradient(60% 50% at 50% 0%, rgba(244,63,94,.18) 0%, rgba(244,63,94,0) 70%); }
        .card-hover { transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        .card-hover:hover { transform: translateY(-4px); border-color: rgba(244,63,94,.5); }
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
                <button type="button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-300" @click="open = !open" aria-label="Open menu">{!! \App\Support\Icons::svg('bars', 'text-xl') !!}</button>
            </div>
            <div class="hidden lg:flex lg:gap-x-10">
                <a href="{{ route('index') }}" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">Blog</a>
                <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">{{ __('basic.courses') }}</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                <a href="{{ $linkedin }}" target="_blank" rel="noopener" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">{!! \App\Support\Icons::svg('linkedin', 'mr-1') !!}LinkedIn</a>
            </div>
        </nav>
    </header>

    <!-- Mobile menu poza naglowkiem -->
    <div class="lg:hidden" role="dialog" aria-modal="true" x-show="open" x-cloak>
        <div class="fixed inset-0 z-50 bg-black/60" @click="open = false"></div>
        <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-neutral-900 px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-white/10">
            <div class="flex items-center justify-between">
                <a href="{{ route('index') }}" class="-m-1.5 p-1.5"><div class="logo_oatllo">oatllo</div></a>
                <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-300" @click="open = false" aria-label="Close menu">{!! \App\Support\Icons::svg('xmark', 'text-xl') !!}</button>
            </div>
            <div class="mt-6 space-y-2">
                <a href="{{ route('index') }}" class="block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">Blog</a>
                <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.courses') }}</a>
            </div>
        </div>
    </div>
</div>

<main itemscope itemtype="https://schema.org/Person">
    <!-- HERO -->
    <section class="relative isolate overflow-hidden pt-36 pb-14 sm:pt-44">
        <div class="absolute inset-0 -z-10 hero-glow" aria-hidden="true"></div>

        <nav aria-label="Breadcrumb" class="mx-auto mb-8 max-w-5xl px-4 sm:px-6 lg:px-8">
            <ol class="flex flex-wrap gap-2 text-sm text-neutral-500">
                <li><a href="{{ route('index') }}" class="hover:text-rose-400">{{ __('basic.home') }}</a></li>
                <li>&#8250;</li>
                <li class="text-neutral-300">{{ __('basic.about') }}</li>
            </ol>
        </nav>

        <div class="mx-auto flex max-w-4xl flex-col items-center gap-10 px-4 text-center sm:px-6 lg:flex-row lg:items-center lg:gap-14 lg:text-left lg:px-8">
            <div class="w-40 flex-none sm:w-48">
                <div class="overflow-hidden rounded-3xl border border-white/10 shadow-2xl">
                    <img src="{{ $authorImage }}" alt="Jakub Owsianka" width="288" height="360" class="h-full w-full object-cover" loading="eager" decoding="async" itemprop="image">
                </div>
            </div>
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-rose-400/20 bg-rose-500/10 px-4 py-1.5 text-sm font-medium text-rose-300">Creator of Oatllo · developer</span>
                <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-white sm:text-5xl" itemprop="name">Jakub Owsianka</h1>
                <p class="mt-2 text-lg text-neutral-400" itemprop="jobTitle">PHP &amp; Laravel developer</p>
                <p class="mx-auto mt-5 max-w-xl text-neutral-300 lg:mx-0" itemprop="description">
                    For over <strong class="text-white">10 years</strong> I've been building backend applications in PHP and Laravel.
                    I run <strong class="text-white">Oatllo</strong> — a blog and free courses for developers, where I share
                    practical, battle-tested knowledge from real projects. No fluff.
                </p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4 lg:justify-start">
                    <a href="{{ $linkedin }}" target="_blank" rel="noopener" itemprop="sameAs" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-rose-500/30 hover:bg-rose-400 transition-colors duration-200">{!! \App\Support\Icons::svg('linkedin') !!} LinkedIn</a>
                    <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-base font-semibold text-white hover:bg-white/10 transition-colors duration-200">{!! \App\Support\Icons::svg('book-open') !!} {{ __('basic.header_blog') }}</a>
                </div>
            </div>
        </div>
    </section>

    <!-- STORY -->
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="space-y-5 text-lg leading-relaxed text-neutral-300">
            <p>
                Programming is not just my job — it's a passion. Over more than a decade I've worked on backend
                systems, from small apps to larger projects that demand thoughtful architecture, efficient database
                queries and a solid deployment process.
            </p>
            <p>
                I started Oatllo to share exactly what I was looking for when I began: concrete, battle-tested
                solutions instead of tutorials detached from reality. I write about modern
                <strong class="text-white">PHP</strong> and <strong class="text-white">Laravel</strong>, clean
                architecture and design patterns, as well as DevOps and the practical use of AI in a developer's
                everyday work.
            </p>
        </div>
    </section>

    <!-- EXPERTISE -->
    <section class="mx-auto mt-16 max-w-5xl px-4 sm:px-6 lg:px-8">
        <h2 class="mb-8 text-center text-2xl font-bold text-white">What I focus on</h2>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            @php
                $areas = [
                    ['icon' => 'code', 'title' => 'PHP & Laravel', 'desc' => 'Modern backend: PHP 8+, Laravel, Eloquent, queues and APIs.'],
                    ['icon' => 'cubes', 'title' => 'Architecture & patterns', 'desc' => 'SOLID, design patterns and clean architecture on practical examples.'],
                    ['icon' => 'server', 'title' => 'DevOps & AI', 'desc' => 'CI/CD, Docker and using AI effectively in a developer\'s workflow.'],
                ];
            @endphp
            @foreach($areas as $a)
                <div class="card-hover rounded-2xl border border-white/10 bg-neutral-900 p-6 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-rose-500/10 text-rose-400 text-xl">{!! \App\Support\Icons::svg($a['icon']) !!}</div>
                    <h3 class="mt-4 text-lg font-semibold text-white">{{ $a['title'] }}</h3>
                    <p class="mt-2 text-sm text-neutral-400">{{ $a['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <!-- OATLLO / CTA -->
    <section class="mx-auto mt-16 mb-4 max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl border border-rose-500/20 bg-gradient-to-br from-rose-500/15 via-neutral-900 to-neutral-900 p-10 text-center sm:p-14">
            <div class="absolute inset-0 -z-10 hero-glow" aria-hidden="true"></div>
            <h2 class="text-2xl font-bold text-white sm:text-3xl">What is Oatllo?</h2>
            <p class="mx-auto mt-4 max-w-2xl text-neutral-300">
                A blog and free courses for developers — practical articles and lessons on PHP, Laravel,
                architecture, databases, DevOps and AI. No fluff, built around real-world projects.
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-rose-500/30 hover:bg-rose-400 transition-colors duration-200">{!! \App\Support\Icons::svg('book-open') !!} {{ __('basic.header_blog') }}</a>
                <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-base font-semibold text-white hover:bg-white/10 transition-colors duration-200">{!! \App\Support\Icons::svg('graduation-cap') !!} {{ __('basic.courses') }}</a>
            </div>
        </div>
    </section>

    <meta itemprop="url" content="{{ $authorUrl }}" />
</main>

@include('partials.site_footer')

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Jakub Owsianka",
  "url": "{{ $authorUrl }}",
  "image": "{{ $authorImage }}",
  "jobTitle": "PHP & Laravel Developer",
  "description": {!! json_encode($pageDesc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "worksFor": { "@type": "Organization", "name": "Oatllo", "url": "{{ route('index') }}" },
  "knowsAbout": ["PHP", "Laravel", "Software architecture", "Design patterns", "Databases", "DevOps", "AI for developers"],
  "sameAs": ["{{ $linkedin }}"],
  "mainEntityOfPage": "{{ $authorUrl }}"
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": {!! json_encode(__('basic.home'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ route('index') }}" },
    { "@type": "ListItem", "position": 2, "name": {!! json_encode(__('basic.about'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ $authorUrl }}" }
  ]
}
</script>
</body>
</html>
