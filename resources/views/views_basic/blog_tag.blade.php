@php
    use Illuminate\Support\Str;

    $tagName   = $tag->name;
    $tagSlug   = $tag->slug ?: Str::slug($tag->name);
    $canonical = route('blogTag', ['tag' => $tagSlug]);
    $pageTitle = $tag->title_seo ?: ($tagName . ' – Blog | Oatllo');
    $pageDesc  = $tag->description_seo
        ?: ('Artykuły z tagiem „' . $tagName . '" na blogu Oatllo — praktyczne tutoriale i poradniki dla programistów.');
    $count     = is_countable($articles) ? count($articles) : 0;
@endphp
<!DOCTYPE html>
<html lang="{{ env('APP_LANG_HTML') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDesc }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="robots" content="{{ $count > 0 ? 'index, follow' : 'noindex, follow' }}">
    <meta name="author" content="Oatllo - Jakub Owsianka">
    <script src="https://cdn.tailwindcss.com"></script>

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDesc }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:image" content="{{ asset('assets/images/logo-512.jpg') }}">
    <meta property="og:image:alt" content="Oatllo">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDesc }}">
    <meta name="twitter:image" content="{{ asset('assets/images/logo-512.jpg') }}">
    <meta name="twitter:site" content="@Oatllo">

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
                <a href="{{ route('index') }}" class="-m-1.5 p-1.5"><div class="logo_oatllo">oatllo</div></a>
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
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
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

<main aria-label="Articles by tag">
    <!-- ===========================================================
      HERO
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
                <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                    <a href="{{ route('blog') }}" itemprop="item" class="hover:text-rose-400"><span itemprop="name">Blog</span></a>
                    <meta itemprop="position" content="2" />
                </li>
                <li>&#8250;</li>
                <li class="text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                    <span itemprop="name">#{{ $tagName }}</span>
                    <meta itemprop="item" content="{{ $canonical }}" />
                    <meta itemprop="position" content="3" />
                </li>
            </ol>
        </nav>

        <header class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
            <span class="inline-flex items-center gap-2 rounded-full border border-rose-400/20 bg-rose-500/10 px-4 py-1.5 text-sm font-medium text-rose-300">
                <i class="fa-solid fa-tag"></i> {{ __('basic.tags') }}
            </span>
            <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-white sm:text-5xl md:text-6xl">
                <span class="bg-gradient-to-r from-rose-400 to-pink-500 bg-clip-text text-transparent">#{{ $tagName }}</span>
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg text-neutral-400">
                {{ $count }} {{ __('basic.articles') }}
            </p>

            @if(!empty($tag->description))
                <div class="mx-auto mt-8 max-w-2xl rounded-2xl border border-white/10 bg-neutral-900/60 p-6 text-left text-neutral-300">
                    {!! $tag->description !!}
                </div>
            @endif
        </header>

        <!-- Kategorie powiązane z tagiem (wewnętrzne linkowanie) -->
        @isset($categories)
            @if($categories->count() > 0)
                <nav class="mx-auto mt-10 flex max-w-5xl flex-wrap justify-center gap-2 px-4 sm:px-6 lg:px-8" aria-label="Categories">
                    @foreach($categories as $cat)
                        <a href="{{ route('blog.list.category', ['slug' => $cat->slug]) }}" class="rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-sm font-medium text-neutral-300 transition-colors duration-200 hover:border-rose-400/40 hover:text-white">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </nav>
            @endif
        @endisset
    </section>

    <!-- ===========================================================
      LESSONS (opcjonalnie)
    =========================================================== -->
    @if(!empty($coursesLesson))
        <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="mb-8 text-2xl font-bold text-white">{{ __('basic.lessons_from_courses') }}</h2>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($coursesLesson as $lesson)
                    <a href="{{ $lesson->getRouteCourse() }}" class="card-hover group flex flex-col rounded-2xl border border-white/10 bg-neutral-900 p-6">
                        <h3 class="font-bold text-white transition-colors duration-200 group-hover:text-rose-300">{{ $lesson->name }}</h3>
                        <p class="mt-2 text-sm text-neutral-400 line-clamp-3">{{ $lesson->getShortDescriptionToBlogList() }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- ===========================================================
      ARTICLES GRID
    =========================================================== -->
    <section class="mx-auto max-w-7xl px-4 pb-8 sm:px-6 lg:px-8">
        @if($count > 0)
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($articles as $article)
                    @include('views_basic.partials.article_card', ['card' => $article])
                @endforeach
            </div>
        @else
            <div class="py-16 text-center">
                <i class="fa-solid fa-tag mb-4 block text-4xl text-neutral-600"></i>
                <p class="text-lg text-neutral-400">Brak artykułów z tym tagiem.</p>
                <p class="mt-2 text-sm text-neutral-500">
                    Zajrzyj do <a href="{{ route('blog') }}" class="text-rose-400 hover:text-rose-300">wszystkich artykułów</a>.
                </p>
            </div>
        @endif
    </section>

    <!-- Back to blog -->
    <div class="mx-auto max-w-7xl px-4 pb-4 text-center sm:px-6 lg:px-8">
        <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-base font-semibold text-white hover:bg-white/10 transition-colors duration-200">
            <i class="fa-solid fa-arrow-left"></i> {{ __('basic.header_blog') }}
        </a>
    </div>
</main>

<!-- ===========================================================
  FOOTER
=========================================================== -->
<footer class="mt-16 border-t border-white/5 bg-neutral-950">
    <div class="mx-auto max-w-7xl px-6 py-14 lg:px-8">
        <div class="flex flex-col gap-10 md:flex-row md:justify-between">
            <div class="max-w-sm">
                <div class="logo_oatllo">oatllo</div>
                <p class="mt-4 text-sm text-neutral-400">{{ __('basic.meta_description') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-10">
                <div>
                    <h2 class="text-sm font-semibold text-white">Explore</h2>
                    <ul class="mt-4 space-y-2 text-sm text-neutral-400">
                        <li><a href="{{ route('index') }}" class="hover:text-rose-400">{{ __('basic.home') }}</a></li>
                        <li><a href="{{ route('blog') }}" class="hover:text-rose-400">Blog</a></li>
                        <li><a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="hover:text-rose-400">{{ __('basic.courses') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-white">Connect</h2>
                    <ul class="mt-4 space-y-2 text-sm text-neutral-400">
                        <li><a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="hover:text-rose-400">LinkedIn</a></li>
                        <li><a href="{{ route('feed') }}" class="hover:text-rose-400">RSS</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/5 pt-8 sm:flex-row">
            <p class="text-sm text-neutral-500">&copy; {{ date('Y') }} Oatllo · Jakub Owsianka</p>
            <a href="{{ route('feed') }}" class="text-sm text-neutral-400 hover:text-rose-400"><i class="fa-solid fa-rss mr-1"></i> RSS</a>
        </div>
    </div>
</footer>

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD
=========================================================== -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": {!! json_encode($pageTitle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "description": {!! json_encode($pageDesc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "url": "{{ $canonical }}",
  "inLanguage": "{{ env('APP_LANG_HTML') }}",
  "isPartOf": { "@type": "Blog", "name": "Oatllo", "url": "{{ route('blog') }}" }
  @if($count > 0),
  "mainEntity": {
    "@type": "ItemList",
    "numberOfItems": {{ $count }},
    "itemListElement": [
      @foreach($articles as $i => $article)
      { "@type": "ListItem", "position": {{ $i + 1 }}, "url": "{{ $article->getRoute() }}", "name": {!! json_encode($article->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!} }@if(!$loop->last),@endif
      @endforeach
    ]
  }
  @endif
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": {!! json_encode(__('basic.home'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ route('index') }}" },
    { "@type": "ListItem", "position": 2, "name": "Blog", "item": "{{ route('blog') }}" },
    { "@type": "ListItem", "position": 3, "name": {!! json_encode('#' . $tagName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ $canonical }}" }
  ]
}
</script>
</body>
</html>
