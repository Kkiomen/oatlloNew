@php
    $currentImage = empty($course->image) ? 'storage/uploads/empty_image.jpg' : $course->image;
    if (preg_match("/asset\\('(.+?)'\\)/", $currentImage, $matches)) { $currentImage = $matches[1]; }
    $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);

    $lessonTitle = trim(strip_tags($article->title));
    $htmlLang    = env('APP_LANG_HTML');
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ $article->seo_title ?: $lessonTitle }} | {{ $course->title_list ?: $course->name }}</title>
    <meta name="description" content="{{ $article->seo_description ?: ('Course lesson: ' . $course->name . ' - ' . $category->title) }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="author" content="Oatllo - Jakub Owsianka">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="canonical" href="{{ $article->getRoute() }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $article->seo_title ?: $lessonTitle }}">
    <meta property="og:description" content="{{ $article->seo_description ?: ('Course lesson: ' . $course->name . ' - ' . $category->title) }}">
    <meta property="og:image" content="{{ $currentImage }}">
    <meta property="og:url" content="{{ $article->getRoute() }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:locale" content="{{ $htmlLang }}">
    <meta property="article:published_time" content="{{ $article->created_at->toISOString() }}">
    <meta property="article:modified_time" content="{{ $article->updated_at->toISOString() }}">
    <meta property="article:section" content="{{ $category->title }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $article->seo_title ?: $lessonTitle }}">
    <meta name="twitter:description" content="{{ $article->seo_description ?: ('Course lesson: ' . $course->name . ' - ' . $category->title) }}">
    <meta name="twitter:image" content="{{ $currentImage }}">
    <meta name="twitter:site" content="@Oatllo">

    <link rel="stylesheet" href="{{ asset('/assets/css/article-style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>
    <link rel="preload" href="{{ asset('assets/fonts/montserrat/montserrat-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"  media="print" onload="this.media='all'" /><noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" /></noscript>
<script defer src="{{ asset('/assets/libs/highlight/highlight.min.js') }}"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlightjs-themes@1.0.0/github.css" media="print" onload="this.media='all'"><noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlightjs-themes@1.0.0/github.css"></noscript>
    <script defer src="{{ asset('/assets/libs/highlight/php.min.js') }}"></script>

    <style>
        body { font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif; }
        .glass { background-color: rgba(10,10,10,.72); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .hero-glow-green { background: radial-gradient(60% 50% at 50% 0%, rgba(16,185,129,.16) 0%, rgba(16,185,129,0) 70%); }
        .card-hover { transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        .card-hover:hover { transform: translateY(-4px); border-color: rgba(16,185,129,.5); }
        .line-clamp-1 { display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden; }
        #reading-bar { position: fixed; top: 0; left: 0; height: 3px; width: 0; z-index: 60; background: linear-gradient(90deg,#10b981,#34d399); transition: width .1s linear; }

        .custom-scrollbar::-webkit-scrollbar { width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #171717; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #404040; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #525252; }
        .custom-scrollbar { scrollbar-width: thin; scrollbar-color: #404040 #171717; }

        /* PROSE */
        .prose { color:#d4d4d8; }
        .prose h2 { font-size:1.6rem; font-weight:700; color:#fff; margin-top:2.5rem; margin-bottom:1rem; padding-bottom:.5rem; border-bottom:1px solid #262626; }
        .prose h3 { font-size:1.25rem; font-weight:600; color:#fff; margin-top:2rem; margin-bottom:.75rem; }
        .prose h4 { font-size:1.125rem; font-weight:600; color:#e5e5e5; margin-top:1.5rem; margin-bottom:.5rem; }
        .prose p { color:#d4d4d8; line-height:1.85; margin-bottom:1.15rem; font-size:1.05rem; }
        .prose strong, .prose b { color:#fff; font-weight:600; }
        .prose ul { list-style:disc; padding-left:1.5rem; margin-bottom:1.15rem; color:#d4d4d8; }
        .prose ol { list-style:decimal; padding-left:1.5rem; margin-bottom:1.15rem; color:#d4d4d8; }
        .prose li { margin-bottom:.5rem; line-height:1.8; }
        .prose ul > li::marker, .prose ol > li::marker { color:#10b981; }
        .prose a { color:#34d399; text-decoration:underline; text-underline-offset:2px; }
        .prose a:hover { color:#6ee7b7; }
        .prose blockquote { border-left:4px solid #10b981; padding:.25rem 0 .25rem 1.25rem; margin:1.5rem 0; font-style:italic; color:#e5e5e5; background:rgba(16,185,129,.05); border-radius:0 .5rem .5rem 0; }
        .prose img { border-radius:.75rem; margin:1.5rem 0; }
        .prose code { background:#171717; color:#6ee7b7; border-radius:.25rem; padding:.125rem .35rem; font-size:.9em; }
        .prose pre { background:#0f0f0f !important; color:#f5f5f5; border:1px solid #262626; border-radius:.75rem; overflow-x:auto; font-size:.9rem; padding:1rem 1.15rem; margin:1.5rem 0; }
        .prose pre code { background:transparent; color:inherit; padding:0; }
        .prose table { width:100%; border-collapse:collapse; margin:1.5rem 0; font-size:.95rem; display:block; overflow-x:auto; }
        .prose th, .prose td { border:1px solid #262626; padding:.6rem .8rem; text-align:left; }
        .prose th { background:#171717; color:#fff; }
    </style>
</head>
<body class="bg-neutral-950 text-neutral-100 antialiased">
<div id="reading-bar"></div>
{!! \App\Services\HomeService::getTagManagerBODY() !!}

<!-- ===========================================================
  NAVIGATION (sticky glass)
=========================================================== -->
<div x-data="{ open: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 20">
    <header class="fixed inset-x-0 top-0 z-50 transition-colors duration-300" :class="scrolled ? 'glass border-b border-white/5' : ''">
        <nav class="mx-auto flex max-w-7xl items-center justify-between p-5 lg:px-8" aria-label="Global">
            <div class="flex lg:flex-1">
                <a href="{{ route('index') }}" class="-m-1.5 p-1.5"><div class="logo_oatllo">oatllo</div></a>
            </div>
            <div class="flex lg:hidden">
                <button type="button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-300" @click="open = !open" aria-label="Open menu">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>
            <div class="hidden lg:flex lg:gap-x-10">
                <a href="{{ route('index') }}" class="text-sm font-semibold text-neutral-300 hover:text-emerald-400 transition-colors duration-200">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="text-sm font-semibold text-neutral-300 hover:text-emerald-400 transition-colors duration-200">Blog</a>
                <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="text-sm font-semibold text-white hover:text-emerald-400 transition-colors duration-200">{{ __('basic.courses') }}</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                <a href="{{ $course->getRoute() }}" class="text-sm font-semibold text-emerald-400 hover:text-emerald-300 transition-colors duration-200">
                    <i class="fa-solid fa-graduation-cap mr-1"></i>{{ $course->title_list ?: $course->name }}
                </a>
            </div>
        </nav>
    </header>

    <!-- Mobile menu poza naglowkiem: backdrop-filter tworzylby containing-block dla position:fixed -->
        <div class="lg:hidden" role="dialog" aria-modal="true" x-show="open" x-cloak>
            <div class="fixed inset-0 z-50 bg-black/60" @click="open = false"></div>
            <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-neutral-900 px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-white/10">
                <div class="flex items-center justify-between">
                    <a href="{{ route('index') }}" class="-m-1.5 p-1.5"><div class="logo_oatllo">oatllo</div></a>
                    <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-300" @click="open = false" aria-label="Close menu"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>
                <div class="mt-6 flow-root">
                    <div class="-my-2 divide-y divide-white/10">
                        <div class="space-y-2 py-6">
                            <a href="{{ route('index') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.home') }}</a>
                            <a href="{{ route('blog') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">Blog</a>
                            <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.courses') }}</a>
                            <a href="{{ $course->getRoute() }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-emerald-400 hover:bg-neutral-800">{{ $course->title_list ?: $course->name }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- ===========================================================
  BREADCRUMB
=========================================================== -->
<nav aria-label="Breadcrumb" class="mx-auto max-w-7xl px-4 pt-28 sm:px-6 lg:px-8">
    <ol class="flex flex-wrap gap-2 text-sm text-neutral-500" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <a href="{{ route('index') }}" itemprop="item" class="hover:text-emerald-400"><span itemprop="name">{{ __('basic.home') }}</span></a>
            <meta itemprop="position" content="1" />
        </li>
        <li>&#8250;</li>
        <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <a href="{{ \App\Services\HomeService::getRouteCourses() }}" itemprop="item" class="hover:text-emerald-400"><span itemprop="name">{{ __('basic.courses') }}</span></a>
            <meta itemprop="position" content="2" />
        </li>
        <li>&#8250;</li>
        <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <a href="{{ $course->getRoute() }}" itemprop="item" class="hover:text-emerald-400"><span itemprop="name">{{ $course->title_list ?: $course->name }}</span></a>
            <meta itemprop="position" content="3" />
        </li>
        <li>&#8250;</li>
        <li class="truncate text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <span itemprop="name">{{ $lessonTitle }}</span>
            <meta itemprop="item" content="{{ $article->getRoute() }}" />
            <meta itemprop="position" content="4" />
        </li>
    </ol>
</nav>

<!-- ===========================================================
  HERO
=========================================================== -->
<header class="relative isolate overflow-hidden pt-10 pb-6" itemscope itemtype="https://schema.org/Article">
    <div class="absolute inset-0 -z-10 hero-glow-green" aria-hidden="true"></div>
    <meta itemprop="author" content="Jakub Owsianka" />
    <meta itemprop="datePublished" content="{{ $article->created_at->format('Y-m-d') }}" />
    <meta itemprop="dateModified" content="{{ $article->updated_at->format('Y-m-d') }}" />
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <a href="{{ $category->getRoute() }}" class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 font-semibold text-emerald-300 hover:bg-emerald-400/20 transition-colors duration-200">
                <i class="fa-solid fa-folder-open"></i> {{ $category->title }}
            </a>
            <span class="text-neutral-600">·</span>
            <span class="text-neutral-500">{{ $course->title_list ?: $course->name }}</span>
        </div>
        <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-white md:text-4xl lg:text-5xl" itemprop="headline">{!! $article->title !!}</h1>
        @if($article->seo_description)
            <p class="mt-4 text-lg text-neutral-400" itemprop="description">{{ $article->seo_description }}</p>
        @endif
    </div>
</header>

<!-- ===========================================================
  CONTENT + SIDEBAR
=========================================================== -->
<main class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 gap-10 lg:grid-cols-4">
        <!-- Lesson content -->
        <article class="lg:col-span-3" itemprop="articleBody">
            <div id="lesson-body" class="rounded-3xl border border-white/10 bg-neutral-900 p-6 sm:p-10">
                <div class="prose max-w-none">
                    @if(!empty($article->content_html))
                        {!! $article->getDisplayContentHtml() !!}
                    @else
                        @foreach($article->getDisplayContents() as $content)
                            @if($content['type'] == 'text' && !empty($content['content']))
                                {!! $content['content'] !!}
                            @endif
                            @if($content['type'] == 'image' && !empty($content['content']))
                                <figure class="my-8">
                                    <img decoding="async" class="w-full rounded-xl object-cover" src="{{ $content['content'] }}" alt="{{ $content['alt'] ?? $lessonTitle }}" loading="lazy">
                                </figure>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Prev / Next -->
            <nav class="mt-8 grid gap-4 sm:grid-cols-2" aria-label="Lesson navigation">
                @if(!empty($lessonSkip['previous']))
                    <a href="{{ $lessonSkip['previous']['route'] }}" class="card-hover group flex items-center gap-3 rounded-2xl border border-white/10 bg-neutral-900 p-5 text-neutral-300 hover:text-white">
                        <i class="fa-solid fa-angle-left flex-none text-emerald-400 transition-transform duration-200 group-hover:-translate-x-1"></i>
                        <div class="min-w-0">
                            <div class="text-xs uppercase tracking-wide text-neutral-500">{{ __('basic.go_to_back_lesson') }}</div>
                            <div class="truncate font-semibold">{{ $lessonSkip['previous']['name'] }}</div>
                        </div>
                    </a>
                @else
                    <div></div>
                @endif

                @if(!empty($lessonSkip['next']))
                    <a href="{{ $lessonSkip['next']['route'] }}" class="card-hover group flex items-center justify-end gap-3 rounded-2xl border border-white/10 bg-neutral-900 p-5 text-right text-neutral-300 hover:text-white">
                        <div class="min-w-0">
                            <div class="text-xs uppercase tracking-wide text-neutral-500">{{ __('basic.go_to_next_lesson') }}</div>
                            <div class="truncate font-semibold">{{ $lessonSkip['next']['name'] }}</div>
                        </div>
                        <i class="fa-solid fa-angle-right flex-none text-emerald-400 transition-transform duration-200 group-hover:translate-x-1"></i>
                    </a>
                @else
                    <div></div>
                @endif
            </nav>
        </article>

        <!-- Sidebar: course contents -->
        <aside class="lg:col-span-1">
            <div class="sticky top-24 rounded-2xl border border-white/10 bg-neutral-900 p-5 lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto custom-scrollbar">
                <a href="{{ $course->getRoute() }}" class="mb-5 flex items-center gap-2 text-sm font-semibold text-white hover:text-emerald-400 transition-colors duration-200">
                    <i class="fa-solid fa-graduation-cap text-emerald-400"></i>
                    {{ $course->title_list ?: $course->name }}
                </a>
                <div class="space-y-5">
                    @foreach($course->categories as $cat)
                        <div>
                            <a href="{{ $cat->getRoute() }}" class="mb-2 block text-sm font-semibold text-white hover:text-emerald-400 transition-colors duration-200">{{ $cat->title }}</a>
                            <ul class="space-y-1 border-l border-white/10">
                                @foreach($cat->lessons as $lesson)
                                    @php($isCurrent = $article->title === $lesson->title)
                                    <li>
                                        <a href="{{ $lesson->getRoute() }}" class="flex items-center gap-2 border-l-2 -ml-px py-1.5 pl-3 text-sm transition-colors duration-200 {{ $isCurrent ? 'border-emerald-400 text-emerald-400 font-semibold' : 'border-transparent text-neutral-400 hover:text-white' }}">
                                            <i class="fa-solid {{ $isCurrent ? 'fa-circle-play' : 'fa-play' }} text-xs {{ $isCurrent ? 'text-emerald-400' : 'text-neutral-600' }}"></i>
                                            <span class="line-clamp-1">{{ $lesson->title }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>
</main>

<!-- ===========================================================
  FOOTER
=========================================================== -->
@include('partials.site_footer', ['accent' => 'emerald'])

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD
=========================================================== -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": {!! json_encode($lessonTitle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "description": {!! json_encode($article->seo_description ?: ('Course lesson: ' . $course->name . ' - ' . $category->title), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "image": "{{ $currentImage }}",
  "inLanguage": "{{ $htmlLang }}",
  "author": { "@type": "Person", "name": "Jakub Owsianka", "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/" },
  "datePublished": "{{ $article->created_at->toISOString() }}",
  "dateModified": "{{ $article->updated_at->toISOString() }}",
  "publisher": { "@type": "Organization", "name": "Oatllo", "logo": { "@type": "ImageObject", "url": "{{ asset('assets/images/logo-512.jpg') }}" } },
  "mainEntityOfPage": "{{ $article->getRoute() }}",
  "articleSection": {!! json_encode($category->title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "isPartOf": { "@type": "Course", "name": {!! json_encode($course->title_list ?: $course->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "url": "{{ $course->getRoute() }}" }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": {!! json_encode(__('basic.home'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ route('index') }}" },
    { "@type": "ListItem", "position": 2, "name": {!! json_encode(__('basic.courses'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ \App\Services\HomeService::getRouteCourses() }}" },
    { "@type": "ListItem", "position": 3, "name": {!! json_encode($course->title_list ?: $course->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ $course->getRoute() }}" },
    { "@type": "ListItem", "position": 4, "name": {!! json_encode($lessonTitle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ $article->getRoute() }}" }
  ]
}
</script>

<script>
    // Reading progress
    (function () {
        const bar = document.getElementById('reading-bar');
        const body = document.getElementById('lesson-body');
        function onScroll() {
            if (!bar || !body) return;
            const rect = body.getBoundingClientRect();
            const total = rect.height - window.innerHeight;
            const current = -rect.top;
            const percent = total > 0 ? Math.max(0, Math.min(100, (current / total) * 100)) : 0;
            bar.style.width = percent + '%';
        }
        document.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    })();
</script>
<script>document.addEventListener('DOMContentLoaded', function () { if (window.hljs) hljs.highlightAll(); });</script>
<script src="{{ asset('/assets/js/script.js') }}"></script>
</body>
</html>
