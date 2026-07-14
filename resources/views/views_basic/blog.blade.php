@php
    use Illuminate\Support\Str;

    $currentPage = $articles->currentPage();
    $pageSuffix  = $currentPage > 1 ? ' – ' . __('basic.page') . ' ' . $currentPage : '';

    $pageTitle = $searchQuery
        ? ($searchQuery . ' – ' . __('basic.header_blog') . ' | Oatllo')
        : ($currentCategory
            ? ($currentCategory . ' – ' . __('basic.header_blog') . ' | Oatllo' . $pageSuffix)
            : __('basic.meta_title_blog') . $pageSuffix);

    // Unikalny opis meta dla każdej strony (kategoria/paginacja) – bez duplikatów w indeksie.
    $pageDescription = $searchQuery
        ? 'Search results for "' . $searchQuery . '" on the Oatllo programming blog.'
        : ($currentCategory
            ? ($currentCategory . ' – articles, tutorials and guides for developers on the Oatllo programming blog.' . ($currentPage > 1 ? ' ' . __('basic.page') . ' ' . $currentPage . '.' : ''))
            : __('basic.meta_description_blog') . ($currentPage > 1 ? ' ' . __('basic.page') . ' ' . $currentPage . '.' : ''));

    // Duplikat "featured" pokazujemy tylko na 1. stronie listy głównej (bez szukania/kategorii).
    $showFeatured = !$searchQuery && !$currentCategory && $articles->onFirstPage() && $articles->count() > 0;
    $featuredArticle = $showFeatured ? $articles->first() : null;

    // Self-canonical: strony paginacji wskazują na SIEBIE (z ?page=N), a nie na stronę 1.
    // Pomijamy pozostałe parametry (np. ?q=), by nie tworzyć śmieciowych kanonikali.
    $canonical = $currentPage > 1 ? url()->current() . '?page=' . $currentPage : url()->current();
@endphp
<!DOCTYPE html>
<html lang="{{ env('APP_LANG_HTML') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}">
    <meta name="author" content="Oatllo - Jakub Owsianka">

    @if($searchQuery)
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow">
    @endif

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="canonical" href="{{ $canonical }}">

    {{-- Paginacja: podpowiedzi prev/next dla robotów --}}
    @if($articles->currentPage() > 1)
        <link rel="prev" href="{{ $articles->previousPageUrl() }}">
    @endif
    @if($articles->hasMorePages())
        <link rel="next" href="{{ $articles->nextPageUrl() }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:image" content="{{ asset('assets/images/logo-512.jpg') }}">
    <meta property="og:image:alt" content="Oatllo">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">
    <meta name="theme-color" content="#0a0a0a">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ asset('assets/images/logo-512.jpg') }}">
    <meta name="twitter:site" content="@Oatllo">

    <link rel="alternate" type="application/rss+xml" title="Oatllo RSS Feed" href="{{ route('feed') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
        body { font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif; }
        .glass { background-color: rgba(10,10,10,.72); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .hero-glow { background: radial-gradient(60% 50% at 50% 0%, rgba(244,63,94,.18) 0%, rgba(244,63,94,0) 70%); }
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
                <a href="{{ route('index') }}" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="text-sm font-semibold text-white hover:text-rose-400 transition-colors duration-200">Blog</a>
                <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">{{ __('basic.courses') }}</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end lg:items-center lg:gap-x-6">
                <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="text-sm font-semibold text-neutral-300 hover:text-rose-400 transition-colors duration-200">
                    <i class="fa-brands fa-linkedin mr-1"></i>LinkedIn
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

<main id="blog" aria-label="Blog articles">
    <!-- ===========================================================
      HERO / PAGE HEADER
    =========================================================== -->
    <section class="relative isolate overflow-hidden pt-36 pb-14 sm:pt-44">
        <div class="absolute inset-0 -z-10 hero-glow" aria-hidden="true"></div>

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="mx-auto mb-8 max-w-5xl px-4 sm:px-6 lg:px-8">
            <ol class="flex flex-wrap justify-center gap-2 text-sm text-neutral-500" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                    <a href="{{ route('index') }}" itemprop="item" class="hover:text-rose-400"><span itemprop="name">{{ __('basic.home') }}</span></a>
                    <meta itemprop="position" content="1" />
                </li>
                <li>&#8250;</li>
                <li class="text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                    <a href="{{ route('blog') }}" itemprop="item" class="hover:text-rose-400"><span itemprop="name">Blog</span></a>
                    <meta itemprop="position" content="2" />
                </li>
                @if($currentCategory)
                    <li>&#8250;</li>
                    <li class="text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                        <span itemprop="name">{{ $currentCategory }}</span>
                        <meta itemprop="position" content="3" />
                    </li>
                @endif
            </ol>
        </nav>

        <header class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
            <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl md:text-6xl">
                @if($searchQuery)
                    {{ __('basic.header_blog') }}: <span class="bg-gradient-to-r from-rose-400 to-pink-500 bg-clip-text text-transparent">{{ $searchQuery }}</span>
                @elseif($currentCategory)
                    <span class="bg-gradient-to-r from-rose-400 to-pink-500 bg-clip-text text-transparent">{{ $currentCategory }}</span>
                @else
                    {{ __('basic.header_blog') }} <span class="bg-gradient-to-r from-rose-400 to-pink-500 bg-clip-text text-transparent">Oatllo</span>
                @endif
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg text-neutral-400">
                @if($searchQuery)
                    {{ $articles->total() }} {{ __('basic.articles') }} · "{{ $searchQuery }}"
                @else
                    {{ __('basic.header_sub_blog') }}
                @endif
            </p>

            <!-- Search -->
            <form action="{{ route('blog') }}" method="get" class="relative mx-auto mt-8 flex max-w-lg justify-center" role="search">
                <input type="search" name="q" value="{{ $searchQuery ?? '' }}" placeholder="{{ __('basic.articles') }}…" aria-label="Search blog" class="w-full rounded-xl border border-white/10 bg-white/5 p-3.5 pr-11 placeholder-neutral-500 text-white focus:border-rose-400/50 focus:outline-none focus:ring-2 focus:ring-rose-500/40" />
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-rose-400" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            @if($searchQuery)
                <div class="mt-4">
                    <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white transition-colors duration-200">
                        <i class="fa-solid fa-xmark"></i> Clear search
                    </a>
                </div>
            @endif
        </header>

        <!-- Category chips (internal linking) -->
        @isset($categories)
            @if($categories->count() > 0)
                <nav class="mx-auto mt-10 flex max-w-5xl flex-wrap justify-center gap-2 px-4 sm:px-6 lg:px-8" aria-label="Categories">
                    <a href="{{ route('blog') }}" class="rounded-full border px-4 py-1.5 text-sm font-medium transition-colors duration-200 {{ !$currentCategory ? 'border-rose-400/50 bg-rose-500/15 text-rose-300' : 'border-white/10 bg-white/5 text-neutral-300 hover:border-rose-400/40 hover:text-white' }}">
                        {{ __('basic.articles') }}
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('blog.list.category', ['slug' => $cat->slug]) }}" class="rounded-full border px-4 py-1.5 text-sm font-medium transition-colors duration-200 {{ $currentCategory === $cat->name ? 'border-rose-400/50 bg-rose-500/15 text-rose-300' : 'border-white/10 bg-white/5 text-neutral-300 hover:border-rose-400/40 hover:text-white' }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </nav>
            @endif
        @endisset
    </section>

    <!-- ===========================================================
      FEATURED ARTICLE
    =========================================================== -->
    @if($featuredArticle)
        <section class="mx-auto mb-16 max-w-6xl px-4 sm:px-6 lg:px-8">
            <a href="{{ $featuredArticle->getRoute() }}" class="card-hover group grid overflow-hidden rounded-3xl border border-white/10 bg-neutral-900 lg:grid-cols-2">
                <div class="relative min-h-[16rem] overflow-hidden bg-neutral-800">
                    <img src="{{ $featuredArticle->image }}" alt="{{ $featuredArticle->name }}" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-neutral-900/70 to-transparent lg:bg-gradient-to-r"></div>
                    <span class="absolute left-4 top-4 inline-flex items-center gap-2 rounded-full bg-rose-500 px-3 py-1 text-xs font-semibold text-white shadow-lg shadow-rose-500/30">
                        <i class="fa-solid fa-star"></i> Featured
                    </span>
                </div>
                <div class="flex flex-col justify-center p-8 lg:p-12">
                    <div class="flex flex-wrap items-center gap-3 text-xs text-neutral-400">
                        @if($featuredArticle->getCategoryName())
                            <span class="font-semibold text-rose-400">{{ $featuredArticle->getCategoryName() }}</span>
                            <span>·</span>
                        @endif
                        <time datetime="{{ $featuredArticle->getPublishedDate()->format('Y-m-d') }}">{{ $featuredArticle->getPublishedDate()->format('M j, Y') }}</time>
                        <span>·</span>
                        <span>{{ $featuredArticle->getTimeRead() }} min read</span>
                    </div>
                    <h2 class="mt-4 text-2xl font-bold text-white group-hover:text-rose-300 transition-colors duration-200 lg:text-3xl">{{ $featuredArticle->name }}</h2>
                    <p class="mt-3 text-neutral-400 line-clamp-3">{{ $featuredArticle->short_description }}</p>
                    <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-rose-400 group-hover:gap-3 transition-all duration-200">
                        {{ __('basic.more') }} <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>
            </a>
        </section>
    @endif

    <!-- ===========================================================
      ARTICLES GRID
    =========================================================== -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if($articles->total() > 0)
            <div class="mb-8 flex items-center justify-between">
                <h2 class="text-2xl font-bold text-white">
                    {{ $searchQuery ? 'Results' : ($currentCategory ?: __('basic.articles')) }}
                </h2>
                <p class="text-sm text-neutral-500">
                    {{ $articles->firstItem() }}–{{ $articles->lastItem() }} / {{ $articles->total() }}
                </p>
            </div>
        @endif

        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3" itemscope itemtype="https://schema.org/Blog">
            @forelse($articles as $article)
                @if($showFeatured && $loop->first) @continue @endif
                <article class="card-hover group flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-neutral-900" itemscope itemprop="blogPost" itemtype="https://schema.org/BlogPosting">
                    <a href="{{ $article->getRoute() }}" class="relative block overflow-hidden" itemprop="url">
                        <div class="aspect-[16/9] w-full overflow-hidden bg-neutral-800">
                            <img src="{{ $article->image }}" alt="{{ $article->name }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" itemprop="image" loading="lazy" />
                        </div>
                        @if($article->getCategoryName())
                            <span class="absolute left-3 top-3 rounded-full bg-neutral-950/80 px-3 py-1 text-xs font-medium text-rose-300 backdrop-blur">{{ $article->getCategoryName() }}</span>
                        @endif
                    </a>
                    <div class="flex flex-1 flex-col p-6">
                        <div class="mb-3 flex items-center gap-2 text-xs text-neutral-500">
                            <time datetime="{{ $article->getPublishedDate()->format('Y-m-d') }}" itemprop="datePublished">{{ $article->getPublishedDate()->format('M j, Y') }}</time>
                            <span>·</span>
                            <span><i class="fa-solid fa-clock mr-1 text-rose-400/70"></i>{{ $article->getTimeRead() }} min</span>
                        </div>
                        <h3 class="text-lg font-bold tracking-tight text-white group-hover:text-rose-300 transition-colors duration-200 line-clamp-2" itemprop="headline">
                            <a href="{{ $article->getRoute() }}">{{ $article->name }}</a>
                        </h3>
                        <p class="mt-2 flex-1 text-sm text-neutral-400 line-clamp-3" itemprop="description">{{ $article->short_description }}</p>
                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-rose-400 group-hover:gap-3 transition-all duration-200">
                            {{ __('basic.more') }} <i class="fa-solid fa-arrow-right text-xs"></i>
                        </span>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-16 text-center">
                    <i class="fa-solid fa-newspaper mb-4 block text-4xl text-neutral-600"></i>
                    @if($searchQuery)
                        <p class="text-lg text-neutral-400">No articles found for "{{ $searchQuery }}".</p>
                        <p class="mt-2 text-sm text-neutral-500">Try different keywords or <a href="{{ route('blog') }}" class="text-rose-400 hover:text-rose-300">browse all articles</a>.</p>
                    @else
                        <p class="text-lg text-neutral-400">No articles to display yet.</p>
                    @endif
                </div>
            @endforelse
        </div>

        <!-- SEO intro (only default listing, first page) -->
        @if(!$searchQuery && !$currentCategory && $articles->onFirstPage())
            <div class="mx-auto mt-20 max-w-3xl rounded-2xl border border-white/5 bg-neutral-900/40 p-8 text-center">
                <h2 class="text-xl font-bold text-white">Practical programming articles for developers</h2>
                <p class="mt-3 text-neutral-400">
                    The Oatllo blog covers modern <strong class="text-neutral-200">PHP</strong> and <strong class="text-neutral-200">Laravel</strong>,
                    JavaScript, software architecture, databases, DevOps and developer tooling — hands-on tutorials and deep-dives
                    you can apply to real projects. New articles are published regularly.
                </p>
            </div>
        @endif
    </section>

    <!-- ===========================================================
      PAGINATION
    =========================================================== -->
    @if($articles->hasPages())
        <nav class="mt-16 flex justify-center" aria-label="Blog pagination">
            <ul class="inline-flex items-center gap-2">
                @if($articles->onFirstPage())
                    <li><span class="rounded-full bg-neutral-900 px-3 py-2 text-sm text-neutral-600" aria-hidden="true"><i class="fa-solid fa-angle-left"></i></span></li>
                @else
                    <li><a href="{{ $articles->previousPageUrl() }}" rel="prev" class="rounded-full bg-white/5 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white transition-colors duration-200" aria-label="Previous page"><i class="fa-solid fa-angle-left"></i></a></li>
                @endif

                @foreach($articles->getUrlRange(1, $articles->lastPage()) as $page => $url)
                    @if($page == $articles->currentPage())
                        <li><span class="rounded-full bg-rose-500 px-4 py-2 text-sm font-semibold text-white" aria-current="page">{{ $page }}</span></li>
                    @elseif($page <= 2 || $page > $articles->lastPage() - 2 || abs($page - $articles->currentPage()) <= 1)
                        <li><a href="{{ $url }}" class="rounded-full bg-white/5 px-4 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white transition-colors duration-200">{{ $page }}</a></li>
                    @elseif($page == 3 || $page == $articles->lastPage() - 2)
                        <li><span class="px-1 text-neutral-600">…</span></li>
                    @endif
                @endforeach

                @if($articles->hasMorePages())
                    <li><a href="{{ $articles->nextPageUrl() }}" rel="next" class="rounded-full bg-white/5 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white transition-colors duration-200" aria-label="Next page"><i class="fa-solid fa-angle-right"></i></a></li>
                @else
                    <li><span class="rounded-full bg-neutral-900 px-3 py-2 text-sm text-neutral-600" aria-hidden="true"><i class="fa-solid fa-angle-right"></i></span></li>
                @endif
            </ul>
        </nav>
    @endif
</main>

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
  "@type": "Blog",
  "name": {!! json_encode($pageTitle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "description": {!! json_encode($pageDescription, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "url": "{{ route('blog') }}",
  "inLanguage": "{{ env('APP_LANG_HTML') }}",
  "publisher": {
    "@type": "Organization",
    "name": "Oatllo",
    "url": "{{ route('index') }}",
    "logo": { "@type": "ImageObject", "url": "{{ asset('assets/images/logo-512.jpg') }}" }
  },
  "blogPost": [
    @foreach($articles as $index => $article)
        @php
            $wc = 0;
            foreach (($article->contents ?? []) as $c) {
                if (($c['type'] ?? '') === 'text') { $wc += str_word_count(strip_tags($c['content'] ?? '')); }
            }
        @endphp
    {
      "@type": "BlogPosting",
      "headline": {!! json_encode($article->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
      "description": {!! json_encode($article->short_description, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
      "url": "{{ $article->getRoute() }}",
      "datePublished": "{{ $article->getPublishedDate()->format('Y-m-d\TH:i:sP') }}",
      "dateModified": "{{ $article->updated_at->format('Y-m-d\TH:i:sP') }}",
      "wordCount": {{ $wc }},
      "timeRequired": "PT{{ $article->getTimeRead() }}M",
      "inLanguage": "{{ $article->language ?? env('APP_LANG_HTML') }}",
      "author": { "@type": "Person", "name": "Jakub Owsianka" },
      "publisher": { "@type": "Organization", "name": "Oatllo", "url": "{{ route('index') }}" },
      "image": { "@type": "ImageObject", "url": "{{ $article->image }}", "width": 1200, "height": 630 },
      "mainEntityOfPage": { "@type": "WebPage", "@id": "{{ $article->getRoute() }}" }@if($article->getCategoryName()),
      "articleSection": {!! json_encode($article->getCategoryName(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}@endif
      @if($article->tags && $article->tags->count() > 0),
      "keywords": {!! json_encode($article->tags->pluck('name')->implode(', '), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}@endif
    }@if(!$loop->last),@endif
    @endforeach
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": {!! json_encode($pageTitle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "url": "{{ $canonical }}",
  "numberOfItems": {{ $articles->total() }},
  "itemListElement": [
    @foreach($articles as $index => $article)
    {
      "@type": "ListItem",
      "position": {{ ($articles->currentPage() - 1) * $articles->perPage() + $loop->iteration }},
      "url": "{{ $article->getRoute() }}",
      "name": {!! json_encode($article->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    }@if(!$loop->last),@endif
    @endforeach
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": {!! json_encode(__('basic.home'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ route('index') }}" },
    { "@type": "ListItem", "position": 2, "name": "Blog", "item": "{{ route('blog') }}" }@if($currentCategory),
    { "@type": "ListItem", "position": 3, "name": {!! json_encode($currentCategory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ $canonical }}" }@endif
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Oatllo",
  "url": "{{ route('index') }}",
  "potentialAction": {
    "@type": "SearchAction",
    "target": { "@type": "EntryPoint", "urlTemplate": "{{ route('blog') }}?q={search_term_string}" },
    "query-input": "required name=search_term_string"
  }
}
</script>
</body>
</html>
