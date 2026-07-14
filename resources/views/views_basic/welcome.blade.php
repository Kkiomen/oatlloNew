<!doctype html>
<html lang="{{ env('APP_LANG_HTML') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ __('basic.meta_title') }}</title>
    <meta name="description" content="{{ __('basic.meta_description') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="alternate" type="application/rss+xml" title="Oatllo RSS Feed" href="{{ route('feed') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}">
    <meta name="robots" content="index, follow">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="canonical" href="{{ route('index') }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <meta property="og:title" content="{{ __('basic.meta_title') }}">
    <meta property="og:description" content="{{ __('basic.meta_description') }}">
    <meta property="og:url" content="{{ route('index') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">
    <meta property="og:image" content="{{ asset('assets/images/logo-512.jpg') }}">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:image:alt" content="Oatllo">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ __('basic.meta_title') }}">
    <meta name="twitter:description" content="{{ __('basic.meta_description') }}">
    <meta name="twitter:image" content="{{ asset('assets/images/logo-512.jpg') }}">
    <meta name="twitter:site" content="@Oatllo">
    <meta name="theme-color" content="#0a0a0a">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css" media="print" onload="this.media='all'"><noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css"></noscript>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"  media="print" onload="this.media='all'" /><noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" /></noscript>
<style>
        body { font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif; }
        .glass { background-color: rgba(10,10,10,.72); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .hero-glow { background: radial-gradient(60% 50% at 50% 0%, rgba(244,63,94,.18) 0%, rgba(244,63,94,0) 70%); }
        .grid-mask {
            background-image: linear-gradient(to right, rgba(255,255,255,.04) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 44px 44px;
            mask-image: radial-gradient(60% 60% at 50% 0%, #000 0%, transparent 75%);
            -webkit-mask-image: radial-gradient(60% 60% at 50% 0%, #000 0%, transparent 75%);
        }
        .card-hover { transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        .card-hover:hover { transform: translateY(-4px); border-color: rgba(244,63,94,.5); }
        .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
    </style>
</head>
<body class="bg-neutral-950 text-neutral-100 antialiased">
{!! \App\Services\HomeService::getTagManagerBODY() !!}

<!-- ===========================================================
  NAVIGATION (sticky glass)
=========================================================== -->
<div x-data="{ open: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 20">
    <header class="fixed inset-x-0 top-0 z-50 transition-colors duration-300" :class="scrolled ? 'glass border-b border-white/5' : ''">
        <nav class="mx-auto flex max-w-7xl items-center justify-between p-5 lg:px-8" aria-label="Global">
            <div class="flex lg:flex-1">
                <a href="{{ route('index') }}" class="-m-1.5 p-1.5">
                    <div class="logo_oatllo">oatllo</div>
                </a>
            </div>
            <div class="flex lg:hidden">
                <button type="button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-300" @click="open = !open" aria-label="Open menu">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>
            <div class="hidden lg:flex lg:gap-x-10">
                <a href="{{ route('index') }}" class="text-sm font-semibold text-white hover:text-rose-400 transition-colors duration-200">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">Blog</a>
                <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">{{ __('basic.courses') }}</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end lg:items-center lg:gap-x-6">
                <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">
                    <i class="fa-brands fa-linkedin mr-1"></i>LinkedIn
                </a>
                <a href="{{ route('blog') }}" class="rounded-lg bg-rose-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-rose-500/25 hover:bg-rose-400 transition-colors duration-200">
                    {{ __('basic.more') }}
                </a>
            </div>
        </nav>
    </header>

    <!-- Mobile menu poza naglowkiem: backdrop-filter tworzylby containing-block dla position:fixed -->
        <div class="lg:hidden" role="dialog" aria-modal="true" x-show="open" x-cloak>
            <div class="fixed inset-0 z-50 bg-black/60" @click="open = false"></div>
            <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-neutral-900 px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-white/10">
                <div class="flex items-center justify-between">
                    <a href="{{ route('index') }}" class="-m-1.5 p-1.5">
                        <div class="logo_oatllo">oatllo</div>
                    </a>
                    <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-300" @click="open = false" aria-label="Close menu">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
                <div class="mt-6 flow-root">
                    <div class="-my-2 divide-y divide-white/10">
                        <div class="space-y-2 py-6">
                            <a href="{{ route('index') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.home') }}</a>
                            <a href="{{ route('blog') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">Blog</a>
                            <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.courses') }}</a>
                        </div>
                        <div class="py-6">
                            <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">
                                <i class="fa-brands fa-linkedin mr-2"></i>LinkedIn
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- ===========================================================
  HERO
=========================================================== -->
<section class="relative isolate overflow-hidden pt-36 pb-24 sm:pt-44 sm:pb-32">
    <div class="absolute inset-0 -z-10 hero-glow" aria-hidden="true"></div>
    <div class="absolute inset-0 -z-10 grid-mask" aria-hidden="true"></div>

    <div class="mx-auto max-w-4xl px-6 text-center lg:px-8">
        <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-sm text-neutral-300 hover:border-rose-400/40 hover:text-white transition-colors duration-200">
            <span class="inline-block h-2 w-2 rounded-full bg-rose-400 animate-pulse"></span>
            PHP · Laravel · JavaScript · DevOps
        </a>

        <h1 class="mt-6 text-balance text-5xl font-extrabold tracking-tight text-white sm:text-6xl md:text-7xl">
            {{ __('basic.blog_header_1') }}
            <span class="bg-gradient-to-r from-rose-400 to-pink-500 bg-clip-text text-transparent">{{ __('basic.blog_header_2') }}</span>
        </h1>

        <p class="mx-auto mt-6 max-w-2xl text-pretty text-lg text-neutral-400 sm:text-xl">
            {{ __('basic.blog_subheader') }}
        </p>

        <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
            <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-rose-500/30 hover:bg-rose-400 transition-colors duration-200">
                <i class="fa-solid fa-book-open"></i>
                {{ __('basic.header_blog') }}
            </a>
            <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-base font-semibold text-white hover:bg-white/10 transition-colors duration-200">
                <i class="fa-solid fa-graduation-cap text-emerald-400"></i>
                {{ __('basic.courses') }}
            </a>
        </div>

        <!-- Stats -->
        <dl class="mx-auto mt-16 grid max-w-2xl grid-cols-3 gap-6 border-t border-white/5 pt-10">
            <div>
                <dt class="text-sm text-neutral-500">{{ __('basic.articles') }}</dt>
                <dd class="mt-1 text-3xl font-bold text-white">{{ \App\Models\Article::where('is_published', true)->where('type','normal')->count() }}+</dd>
            </div>
            <div>
                <dt class="text-sm text-neutral-500">{{ __('basic.courses') }}</dt>
                <dd class="mt-1 text-3xl font-bold text-white">{{ \App\Models\Course::where('is_published', true)->count() }}</dd>
            </div>
            <div>
                <dt class="text-sm text-neutral-500">Open source</dt>
                <dd class="mt-1 text-3xl font-bold text-white">100%</dd>
            </div>
        </dl>
    </div>
</section>

<!-- ===========================================================
  LATEST ARTICLES (featured + grid)
=========================================================== -->
@if($featuredArticle)
<section class="border-t border-white/5 bg-neutral-950 py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('basic.header_blog') }}</h2>
                <p class="mt-2 max-w-xl text-neutral-400">{{ __('basic.header_sub_blog') }}</p>
            </div>
            <a href="{{ route('blog') }}" class="group inline-flex items-center gap-2 text-sm font-semibold text-rose-400 hover:text-rose-300">
                {{ __('basic.more') }}
                <i class="fa-solid fa-arrow-right transition-transform duration-200 group-hover:translate-x-1"></i>
            </a>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Featured -->
            <a href="{{ $featuredArticle->getRoute() }}" class="card-hover group relative flex flex-col justify-end overflow-hidden rounded-3xl border border-white/10 bg-neutral-900 min-h-[26rem]">
                <img decoding="async" src="{{ \App\Services\HomeService::responsiveImage($featuredArticle->image, 1000) }}" alt="{{ $featuredArticle->name }}" class="absolute inset-0 h-full w-full object-cover opacity-80 transition-transform duration-500 group-hover:scale-105" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-neutral-950 via-neutral-950/70 to-transparent"></div>
                <div class="relative p-8">
                    <div class="flex items-center gap-3 text-xs text-neutral-300">
                        @if($featuredArticle->getCategoryName())
                            <span class="rounded-full bg-rose-500/90 px-3 py-1 font-semibold text-white">{{ $featuredArticle->getCategoryName() }}</span>
                        @endif
                        <time datetime="{{ $featuredArticle->getPublishedDate()->format('Y-m-d') }}">{{ $featuredArticle->getPublishedDate()->format('M j, Y') }}</time>
                        <span>·</span>
                        <span>{{ $featuredArticle->getTimeRead() }} min</span>
                    </div>
                    <h3 class="mt-4 text-2xl font-bold text-white group-hover:text-rose-300 transition-colors duration-200 sm:text-3xl">
                        {{ $featuredArticle->name }}
                    </h3>
                    <p class="mt-3 max-w-xl text-neutral-300 line-clamp-2">{{ $featuredArticle->short_description }}</p>
                </div>
            </a>

            <!-- Latest list -->
            <div class="flex flex-col gap-4">
                @foreach($latestArticles as $article)
                    <a href="{{ $article->getRoute() }}" class="card-hover group flex gap-4 overflow-hidden rounded-2xl border border-white/10 bg-neutral-900 p-3">
                        <div class="relative h-24 w-32 flex-none overflow-hidden rounded-xl bg-neutral-800">
                            <img decoding="async" src="{{ \App\Services\HomeService::responsiveImage($article->image, 400) }}" alt="{{ $article->name }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" loading="lazy">
                        </div>
                        <div class="min-w-0 flex-1 py-1">
                            <div class="flex items-center gap-2 text-xs text-neutral-500">
                                @if($article->getCategoryName())
                                    <span class="text-rose-400 font-medium">{{ $article->getCategoryName() }}</span>
                                    <span>·</span>
                                @endif
                                <time datetime="{{ $article->getPublishedDate()->format('Y-m-d') }}">{{ $article->getPublishedDate()->format('M j, Y') }}</time>
                            </div>
                            <h3 class="mt-1 font-semibold text-white group-hover:text-rose-300 transition-colors duration-200 line-clamp-2">{{ $article->name }}</h3>
                            <p class="mt-1 text-sm text-neutral-400 line-clamp-2">{{ $article->short_description }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

<!-- ===========================================================
  COURSES
=========================================================== -->
@if($courses->count() > 0)
<section class="border-t border-white/5 bg-neutral-900/40 py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-1.5 text-sm font-medium text-emerald-300">
                <i class="fa-solid fa-graduation-cap"></i> {{ __('basic.courses') }}
            </span>
            <h2 class="mt-5 text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ __('basic.courses_header_h2') }}</h2>
            <p class="mt-3 text-neutral-400">{{ __('basic.courses_header_h1') }}</p>
        </div>

        <div class="mt-14 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
            @foreach($courses as $course)
                @php
                    $courseImage = empty($course->image) ? asset('storage/uploads/empty_image.jpg') : $course->image;
                    if (preg_match("/asset\\('(.+?)'\\)/", $courseImage, $m)) { $courseImage = $m[1]; }
                    $courseImage = str_contains($courseImage, 'http') ? $courseImage : asset($courseImage);
                @endphp
                <a href="{{ $course->getRoute() }}" class="card-hover group flex flex-col overflow-hidden rounded-3xl border border-white/10 bg-neutral-900">
                    <div class="relative h-44 overflow-hidden bg-neutral-800">
                        <img decoding="async" src="{{ \App\Services\HomeService::responsiveImage($courseImage, 800) }}" alt="{{ $course->name }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-neutral-900 to-transparent"></div>
                        <span class="absolute left-4 top-4 rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white shadow-lg shadow-emerald-500/30">FREE</span>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <h3 class="text-xl font-bold text-white group-hover:text-emerald-300 transition-colors duration-200">{{ $course->title_list ?: $course->name }}</h3>
                        <p class="mt-2 flex-1 text-sm text-neutral-400 line-clamp-3">{{ $course->description_list ?: $course->description_seo }}</p>
                        <div class="mt-5 flex items-center justify-between">
                            <span class="inline-flex items-center gap-2 text-sm text-neutral-500">
                                <i class="fa-solid fa-layer-group text-emerald-400"></i>
                                {{ $course->categories->count() }} {{ __('basic.chapter') }}
                            </span>
                            <span class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-400 group-hover:gap-3 transition-all duration-200">
                                {{ __('basic.go_to_course') }} <i class="fa-solid fa-arrow-right"></i>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- ===========================================================
  WHY OATLLO (features)
=========================================================== -->
<section id="features" class="border-t border-white/5 bg-neutral-950 py-20 sm:py-28" aria-label="Why Oatllo">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                Everything you need to <span class="text-rose-400">level up</span>
            </h2>
            <p class="mt-3 text-neutral-400">Practical, hands-on content on modern backend, architecture and tooling — no fluff.</p>
        </div>

        <div class="mt-14 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $features = [
                    ['icon' => 'fa-solid fa-code', 'title' => 'Hands-on Tutorials', 'desc' => 'Real-world PHP, Laravel and JavaScript walkthroughs you can apply immediately.'],
                    ['icon' => 'fa-solid fa-cubes', 'title' => 'SOLID & Patterns', 'desc' => 'Design patterns and clean-architecture principles explained on practical examples.'],
                    ['icon' => 'fa-solid fa-database', 'title' => 'Databases & Performance', 'desc' => 'MySQL, query tuning and backend performance optimisation deep-dives.'],
                    ['icon' => 'fa-solid fa-server', 'title' => 'DevOps & Tooling', 'desc' => 'CI/CD, Docker and the developer tooling that speeds up your workflow.'],
                    ['icon' => 'fa-brands fa-github', 'title' => 'Open Source', 'desc' => 'Full source code and example projects available on GitHub.'],
                    ['icon' => 'fa-solid fa-robot', 'title' => 'AI for Developers', 'desc' => 'How to use AI tools effectively in your day-to-day engineering.'],
                ];
            @endphp
            @foreach($features as $f)
                <div class="card-hover rounded-2xl border border-white/10 bg-neutral-900 p-6">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-500/10 text-rose-400">
                        <i class="{{ $f['icon'] }} text-lg"></i>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold text-white">{{ $f['title'] }}</h3>
                    <p class="mt-2 text-sm text-neutral-400">{{ $f['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ===========================================================
  ABOUT THE AUTHOR
=========================================================== -->
<section id="about" class="border-t border-white/5 bg-neutral-900/40 py-20 sm:py-28" aria-label="About the author" itemscope itemtype="https://schema.org/Person">
    <div class="mx-auto max-w-6xl px-6 lg:px-8">
        <div class="flex flex-col items-center gap-12 lg:flex-row lg:items-stretch">
            <div class="w-full max-w-xs flex-none">
                <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-neutral-800">
                    <img decoding="async" class="h-full w-full object-cover" src="{{ asset('/assets/images/owsianka_jakub.png') }}" alt="Jakub Owsianka - PHP developer" itemprop="image">
                </div>
            </div>
            <figure class="relative flex flex-1 flex-col justify-center">
                <i class="fa-solid fa-quote-left mb-4 text-4xl text-rose-500/40" aria-hidden="true"></i>
                <blockquote class="text-xl font-semibold leading-relaxed text-white sm:text-2xl">
                    <p itemprop="description">{{ __('basic.about_me_content') }}</p>
                </blockquote>
                <figcaption class="mt-8">
                    <div class="text-lg font-semibold text-white" itemprop="name">Jakub Owsianka</div>
                    <div class="mt-1 text-neutral-400" itemprop="jobTitle">{{ __('basic.about_me_description') }}</div>
                    <div class="mt-4 flex gap-4">
                        <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="text-neutral-400 hover:text-rose-400" aria-label="LinkedIn"><i class="fa-brands fa-linkedin fa-lg"></i></a>
                    </div>
                </figcaption>
            </figure>
        </div>
    </div>
</section>

<!-- ===========================================================
  FAQ
=========================================================== -->
<section id="faq" class="border-t border-white/5 bg-neutral-900/40 py-20 sm:py-28" aria-label="FAQ">
    <div class="mx-auto max-w-3xl px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-white sm:text-4xl">Frequently asked <span class="text-rose-400">questions</span></h2>
            <p class="mt-3 text-neutral-400">Everything about the articles, courses and how to get involved.</p>
        </div>

        <div class="mt-12 space-y-4" x-data="{ open: 0 }">
            @php
                $faqs = [
                    ['q' => 'What kind of content will I find here?', 'a' => 'In-depth blog posts, practical tutorials, code snippets and cheat sheets — all focused on modern PHP, Laravel, JavaScript, architecture and backend development.'],
                    ['q' => 'Do I need previous programming experience?', 'a' => 'Not necessarily. Many articles start from the basics and build up gradually, while others go deep for experienced developers. Pick the level that suits you.'],
                    ['q' => 'Are the courses really free?', 'a' => 'Yes. The courses on Oatllo are free and self-paced, with source code available so you can follow along at your own speed.'],
                    ['q' => 'How often do you publish new content?', 'a' => 'New tutorials and deep-dives are published regularly. Follow me on LinkedIn or Instagram so you never miss an update.'],
                    ['q' => 'Can I request a topic?', 'a' => 'Absolutely — reach out on LinkedIn or Instagram with your idea. Topics that help the community most get prioritised.'],
                ];
            @endphp
            @foreach($faqs as $i => $faq)
                <div class="overflow-hidden rounded-2xl border border-white/10 bg-neutral-900">
                    <button type="button" class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left" @click="open === {{ $i }} ? open = null : open = {{ $i }}">
                        <span class="font-semibold text-white">{{ $faq['q'] }}</span>
                        <i class="fa-solid fa-chevron-down flex-none text-rose-400 transition-transform duration-200" :class="open === {{ $i }} ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open === {{ $i }}" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0">
                        <p class="px-6 pb-5 text-neutral-400">{{ $faq['a'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ===========================================================
  NEWSLETTER / CTA
=========================================================== -->
<section class="border-t border-white/5 bg-neutral-950 py-20 sm:py-28">
    <div class="mx-auto max-w-5xl px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl border border-rose-500/20 bg-gradient-to-br from-rose-500/15 via-neutral-900 to-neutral-900 p-10 text-center sm:p-16">
            <div class="absolute inset-0 -z-10 hero-glow" aria-hidden="true"></div>
            <h2 class="text-3xl font-bold text-white sm:text-4xl">Keep learning with Oatllo</h2>
            <p class="mx-auto mt-4 max-w-2xl text-neutral-300">Fresh tutorials on PHP, Laravel, architecture and developer tooling — straight to the point.</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 rounded-xl bg-rose-500 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-rose-500/30 hover:bg-rose-400 transition-colors duration-200">
                    <i class="fa-solid fa-book-open"></i> {{ __('basic.header_blog') }}
                </a>
                <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-base font-semibold text-white hover:bg-white/10 transition-colors duration-200">
                    <i class="fa-brands fa-linkedin"></i> Follow on LinkedIn
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ===========================================================
  FOOTER
=========================================================== -->
@include('partials.site_footer')

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD
=========================================================== -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Oatllo",
  "url": "{{ route('index') }}",
  "inLanguage": "{{ env('APP_LANG_HTML') }}",
  "publisher": { "@type": "Organization", "name": "Oatllo", "url": "{{ route('index') }}" },
  "potentialAction": {
    "@type": "SearchAction",
    "target": { "@type": "EntryPoint", "urlTemplate": "{{ route('blog') }}?q={search_term_string}" },
    "query-input": "required name=search_term_string"
  }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    { "@type": "Question", "name": "What kind of content will I find here?", "acceptedAnswer": { "@type": "Answer", "text": "In-depth blog posts, practical tutorials, code snippets and cheat sheets - all focused on modern PHP, Laravel, JavaScript, architecture and backend development." } },
    { "@type": "Question", "name": "Do I need previous programming experience?", "acceptedAnswer": { "@type": "Answer", "text": "Not necessarily. Many articles start from the basics and build up gradually, while others go deep for experienced developers." } },
    { "@type": "Question", "name": "Are the courses really free?", "acceptedAnswer": { "@type": "Answer", "text": "Yes. The courses on Oatllo are free and self-paced, with source code available so you can follow along." } },
    { "@type": "Question", "name": "How often do you publish new content?", "acceptedAnswer": { "@type": "Answer", "text": "New tutorials and deep-dives are published regularly. Follow on LinkedIn or Instagram to stay updated." } },
    { "@type": "Question", "name": "Can I request a topic?", "acceptedAnswer": { "@type": "Answer", "text": "Yes - reach out on LinkedIn or Instagram with your idea." } }
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Oatllo",
  "url": "{{ route('index') }}",
  "logo": "{{ asset('assets/images/logo-512.jpg') }}",
  "sameAs": [ "https://www.linkedin.com/in/jakub-owsianka-446bb5213/" ],
  "founder": { "@type": "Person", "name": "Jakub Owsianka", "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/" },
  "description": "Educational platform with programming courses and technological development"
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Jakub Owsianka",
  "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/",
  "sameAs": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/",
  "jobTitle": "PHP Developer",
  "worksFor": { "@type": "Organization", "name": "Oatllo" }
}
</script>

</body>
</html>
