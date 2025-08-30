<!-- =============================================================
  BLOG LISTING PAGE (Tailwind CSS v3 + Font Awesome 6)
  Brand: Dark UI with rose accent – consistent with landing page.
  Focus keywords: PHP blog, backend development articles, learn PHP
  ============================================================= -->

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ $searchQuery ? 'Search Results for ' . $searchQuery . ' - ' . __('basic.meta_title') : __('basic.meta_title') }}</title>
    <meta name="description" content="{{ $searchQuery ? 'Search results for ' . $searchQuery . '. ' . __('basic.meta_description') : __('basic.meta_description') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="index, follow">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}


    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">

    <link rel="canonical" href="{{ request()->fullUrl() }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <meta property="og:title" content="{{ $searchQuery ? 'Search Results for ' . $searchQuery . ' - ' . __('basic.meta_title') : __('basic.meta_title') }}">
    <meta property="og:description" content="{{ $searchQuery ? 'Search results for ' . $searchQuery . '. ' . __('basic.meta_description') : __('basic.meta_description') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->fullUrl() }}">
    <meta property="og:site_name" content="oatllo">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $searchQuery ? 'Search Results for ' . $searchQuery . ' - ' . __('basic.meta_title') : __('basic.meta_title') }}">
    <meta name="twitter:description" content="{{ $searchQuery ? 'Search results for ' . $searchQuery . '. ' . __('basic.meta_description') : __('basic.meta_description') }}">

    @if(env('LANGUAGE_MODE') == 'strict')
        <link rel="alternate" hreflang="pl" href="{{ request()->fullUrl() }}">
        <link rel="alternate" hreflang="en" href="{{ str_replace('/pl/', '/en/', request()->fullUrl()) }}">
        <link rel="alternate" hreflang="x-default" href="{{ str_replace('/pl/', '/en/', request()->fullUrl()) }}">
    @endif


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/fontawesome.min.css" integrity="sha512-v8QQ0YQ3H4K6Ic3PJkym91KoeNT5S3PnDKvqnwqFD1oiqIl653crGZplPdU5KKtHjO0QKcQ2aUlQZYjHczkmGw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body class="bg-neutral-950 text-neutral-100 antialiased">
{!! \App\Services\HomeService::getTagManagerBODY() !!}

<!-- ===========================================================
  HEADER NAVIGATION
=========================================================== -->
<div x-data="{ open: false }">
    <header class="absolute inset-x-0 top-0 z-50">
        <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
            <div class="flex lg:flex-1">
                <a href="{{ route('index') }}" class="-m-1.5 p-1.5">
                    <div class="logo_oatllo">oatllo</div>
                </a>
            </div>
            <div class="flex lg:hidden">
                <button type="button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-400" @click="open = !open">
                    <span class="sr-only">Open menu</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
            <div class="hidden lg:flex lg:gap-x-12">
                <a href="{{ route('index') }}" class="text-sm/6 font-semibold text-white hover:text-rose-400 transition-colors duration-200">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="text-sm/6 font-semibold text-white hover:text-rose-400 transition-colors duration-200">Blog</a>
                <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="text-sm/6 font-semibold text-white hover:text-rose-400 transition-colors duration-200">{{ __('basic.courses') }}</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="text-sm/6 font-semibold text-white hover:text-rose-400 transition-colors duration-200">
                    <i class="fa-brands fa-linkedin mr-1"></i>LinkedIn
                </a>
            </div>
        </nav>

        <!-- Mobile menu, show/hide based on menu open state. -->
        <div class="lg:hidden" role="dialog" aria-modal="true" x-show="open">
            <!-- Background backdrop, show/hide based on slide-over state. -->
            <div class="fixed inset-0 z-50"></div>
            <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-neutral-900 px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-white/10">
                <div class="flex items-center justify-between">
                    <a href="{{ route('index') }}" class="-m-1.5 p-1.5">
                        <div class="logo_oatllo">oatllo</div>
                    </a>
                    <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-400" @click="open = !open">
                        <span class="sr-only">Close menu</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mt-6 flow-root">
                    <div class="-my-2 divide-y divide-gray-500/25">
                        <div class="space-y-2 py-6">
                            <a href="{{ route('index') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-neutral-800">{{ __('basic.home') }}</a>
                            <a href="{{ route('blog') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-neutral-800">Blog</a>
                            <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-neutral-800">{{ __('basic.courses') }}</a>
                        </div>
                        <div class="py-6">
                            <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-neutral-800">
                                <i class="fa-brands fa-linkedin mr-2"></i>LinkedIn
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</div>

<!-- ===========================================================
  MAIN CONTENT
=========================================================== -->
<main id="blog" class="pt-32 pb-32" aria-label="Blog articles">
    <!-- Page Header -->
    <header class="mx-auto mb-16 max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight text-white md:text-5xl">
            @if($searchQuery)
                Search Results for "<span class="text-rose-400">{{ $searchQuery }}</span>"
            @else
                Latest <span class="text-rose-400">PHP Articles</span>
            @endif
        </h1>
        <p class="mt-4 text-lg text-neutral-300">
            @if($searchQuery)
                Found {{ $articles->total() }} article(s) matching your search.
            @else
                Practical tutorials, performance tips and deep‑dive guides for modern backend developers.
            @endif
        </p>
        <!-- Search / filter (optional) -->
        <form action="{{ route('test') }}" method="get" class="relative mt-8 flex justify-center">
            <input type="search" name="q" value="{{ $searchQuery ?? '' }}" placeholder="Search articles…" aria-label="Search blog" class="w-full max-w-lg rounded-xl border border-transparent bg-white/10 p-3 pr-10 placeholder-neutral-400 text-white focus:outline-none focus:ring-2 focus:ring-rose-500" />
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-rose-400" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
        @if($searchQuery)
            <div class="mt-4 text-center">
                <a href="{{ route('test') }}" class="inline-flex items-center gap-2 rounded-full bg-neutral-800 px-4 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white transition-colors duration-200">
                    <i class="fa-solid fa-times"></i>
                    Clear search
                </a>
            </div>
        @endif

        @if($articles->total() > 0)
            <div class="mt-4 text-center text-sm text-neutral-400">
                Showing {{ $articles->firstItem() }} to {{ $articles->lastItem() }} of {{ $articles->total() }} article{{ $articles->total() > 1 ? 's' : '' }}
            </div>
        @elseif($searchQuery)
            <div class="mt-4 text-center text-sm text-neutral-400">
                No articles found matching your search
            </div>
        @else
            <div class="mt-4 text-center text-sm text-neutral-400">
                No articles available
            </div>
        @endif
    </header>

    <!-- Articles Grid -->
    <section class="mx-auto grid max-w-7xl gap-8 px-4 sm:grid-cols-2 lg:grid-cols-3 sm:px-6 lg:px-8" itemscope itemtype="https://schema.org/Blog">
        @forelse($articles as $article)
            <article class="flex flex-col overflow-hidden rounded-2xl bg-neutral-900/70 shadow-lg transition hover:shadow-rose-500/30" itemscope itemprop="blogPost" itemtype="https://schema.org/BlogPosting">
                <a href="{{ $article->getRoute() }}" class="group relative block" itemprop="url">
                    <img src="{{ $article->image }}" alt="{{ $article->name }}" class="h-56 w-full object-cover transition group-hover:scale-105" itemprop="image" loading="lazy" />
                    <span class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></span>
                </a>
                <div class="flex flex-1 flex-col p-6">
                    <header class="mb-3 flex-1">
                        <h2 class="text-xl font-bold tracking-tight text-white group-hover:text-rose-400" itemprop="headline">
                            <a href="{{ $article->getRoute() }}" class="inline-block h-full w-full" itemprop="url">{{ $article->name }}</a>
                        </h2>
                        <p class="mt-2 line-clamp-3 text-neutral-400" itemprop="description">
                            {{ $article->short_description }}
                        </p>
                    </header>
                    <!-- Meta info -->
                    <footer class="mt-auto flex items-center justify-between text-sm text-neutral-400">
                        <div>
                            <i class="fa-solid fa-calendar text-rose-400 mr-1"></i>
                            <time datetime="{{ $article->getPublishedDate()->format('Y-m-d') }}" itemprop="datePublished">{{ $article->getPublishedDate()->format('M j, Y') }}</time>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-clock text-rose-400"></i>
                            <span>{{ $article->getTimeRead() }}&nbsp;min read</span>
                        </div>
                    </footer>
                </div>
            </article>
        @empty
            <div class="col-span-full text-center py-12">
                <div class="text-neutral-400 text-lg">
                    <i class="fa-solid fa-newspaper text-4xl mb-4 block"></i>
                    @if($searchQuery)
                        <p>No articles found matching "{{ $searchQuery }}".</p>
                        <p class="mt-2 text-sm">Try different keywords or <a href="{{ route('test') }}" class="text-rose-400 hover:text-rose-300">browse all articles</a>.</p>
                    @else
                        <p>No articles to display.</p>
                    @endif
                </div>
            </div>
        @endforelse
    </section>

    <!-- Pagination -->
    @if($articles->hasPages())
        <nav class="mt-20 flex justify-center" aria-label="Blog pagination">
            <ul class="inline-flex items-center gap-2">
                {{-- First page --}}
                @if($articles->onFirstPage())
                    <li><span class="rounded-full bg-neutral-800 px-3 py-2 text-sm text-neutral-500 cursor-not-allowed" aria-label="First page"><i class="fa-solid fa-angles-left"></i></span></li>
                @else
                    <li><a href="{{ $articles->url(1) }}" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white transition-colors duration-200" aria-label="First page"><i class="fa-solid fa-angles-left"></i></a></li>
                @endif

                {{-- Previous page --}}
                @if($articles->onFirstPage())
                    <li><span class="rounded-full bg-neutral-800 px-3 py-2 text-sm text-neutral-500 cursor-not-allowed" aria-label="Previous page"><i class="fa-solid fa-angle-left"></i></span></li>
                @else
                    <li><a href="{{ $articles->previousPageUrl() }}" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white transition-colors duration-200" aria-label="Previous page"><i class="fa-solid fa-angle-left"></i></a></li>
                @endif

                {{-- Page numbers --}}
                @foreach($articles->getUrlRange(1, $articles->lastPage()) as $page => $url)
                    @if($page == $articles->currentPage())
                        <li><span class="rounded-full bg-rose-500 px-3 py-2 text-sm font-semibold text-white">{{ $page }}</span></li>
                    @elseif($page <= 3 || $page > $articles->lastPage() - 2)
                        <li><a href="{{ $url }}" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white transition-colors duration-200">{{ $page }}</a></li>
                    @elseif($page == 4 && $articles->lastPage() > 6)
                        <li><span class="px-2 text-neutral-500">…</span></li>
                    @elseif($page == $articles->lastPage() - 2 && $articles->lastPage() > 6)
                        <li><span class="px-2 text-neutral-500">…</span></li>
                    @endif
                @endforeach

                {{-- Next page --}}
                @if($articles->hasMorePages())
                    <li><a href="{{ $articles->nextPageUrl() }}" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white transition-colors duration-200" aria-label="Next page"><i class="fa-solid fa-angle-right"></i></a></li>
                @else
                    <li><span class="rounded-full bg-neutral-800 px-3 py-2 text-sm text-neutral-500 cursor-not-allowed" aria-label="Next page"><i class="fa-solid fa-angle-right"></i></span></li>
                @endif

                {{-- Last page --}}
                @if($articles->currentPage() == $articles->lastPage())
                    <li><span class="rounded-full bg-neutral-800 px-3 py-2 text-sm text-neutral-500 cursor-not-allowed" aria-label="Last page"><i class="fa-solid fa-angles-right"></i></span></li>
                @else
                    <li><a href="{{ $articles->url($articles->lastPage()) }}" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white transition-colors duration-200" aria-label="Last page"><i class="fa-solid fa-angles-right"></i></a></li>
                @endif
            </ul>
        </nav>
    @endif
</main>

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD (Blog + ItemList + BreadcrumbList)
=========================================================== -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Blog",
  "name": "{{ __('basic.meta_title') }}",
  "description": "{{ __('basic.meta_description') }}",
  "url": "{{ route('test') }}",
  "publisher": {
    "@type": "Organization",
    "name": "oatllo",
    "url": "{{ route('index') }}"
  },
  "blogPost": [
    @foreach($articles as $index => $article)
    {
      "@type": "BlogPosting",
      "headline": "{{ addslashes($article->name) }}",
      "description": "{{ addslashes($article->short_description) }}",
      "url": "{{ $article->getRoute() }}",
      "datePublished": "{{ $article->getPublishedDate()->format('Y-m-d\TH:i:sP') }}",
      "dateModified": "{{ $article->updated_at->format('Y-m-d\TH:i:sP') }}",
      "wordCount": {{ str_word_count(strip_tags($article->content ?? '')) }},
      "timeRequired": "PT{{ $article->getTimeRead() }}M",
      "author": {
        "@type": "Person",
        "name": "Jakub Owsianka"
      },
      "publisher": {
        "@type": "Organization",
        "name": "oatllo",
        "url": "{{ route('index') }}"
      },
      "image": {
        "@type": "ImageObject",
        "url": "{{ $article->image }}",
        "width": 800,
        "height": 600
      },
      "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $article->getRoute() }}"
      }@if($article->category),@endif
      @if($article->category)
      "articleSection": "{{ addslashes($article->category->name) }}"
      @endif
      @if($article->tags && $article->tags->count() > 0),
      "keywords": "{{ $article->tags->pluck('name')->implode(', ') }}"
      @endif
    }@if(!$loop->last),@endif
    @endforeach
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "{{ $searchQuery ? 'Search Results for ' . $searchQuery : 'Latest PHP Articles' }}",
  "description": "{{ $searchQuery ? 'Search results for ' . $searchQuery : 'Latest PHP articles and tutorials' }}",
  "url": "{{ request()->fullUrl() }}",
  "numberOfItems": {{ $articles->total() }},
  "itemListElement": [
    @foreach($articles as $index => $article)
    {
      "@type": "ListItem",
      "position": {{ ($articles->currentPage() - 1) * $articles->perPage() + $loop->iteration }},
      "url": "{{ $article->getRoute() }}",
      "name": "{{ addslashes($article->name) }}",
      "description": "{{ addslashes($article->short_description) }}"
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
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "{{ route('index') }}"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "{{ $searchQuery ? 'Search Results' : 'Blog' }}",
      "item": "{{ request()->fullUrl() }}"
    }
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "{{ __('basic.meta_title') }}",
  "url": "{{ route('index') }}",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "{{ route('test') }}?q={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  }
}
</script>

@if($articles->hasPages())
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "{{ $searchQuery ? 'Search Results for ' . $searchQuery : 'Latest PHP Articles' }}",
  "description": "{{ $searchQuery ? 'Search results for ' . $searchQuery : 'Latest PHP articles and tutorials' }}",
  "url": "{{ request()->fullUrl() }}",
  "isPartOf": {
    "@type": "Blog",
    "name": "{{ __('basic.meta_title') }}",
    "url": "{{ route('test') }}"
  }@if($articles->previousPageUrl()),@endif
  @if($articles->previousPageUrl())
  "previousPage": "{{ $articles->previousPageUrl() }}"@if($articles->nextPageUrl()),@endif
  @endif
  @if($articles->nextPageUrl())
  "nextPage": "{{ $articles->nextPageUrl() }}"
  @endif
}
</script>
@endif

@if($searchQuery && $articles->total() == 0)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "No articles found for '{{ $searchQuery }}'",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "We couldn't find any articles matching your search term. Try using different keywords or browse our complete article collection."
      }
    }
  ]
}
</script>
@endif

@if(!$searchQuery && $articles->total() > 0)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "HowTo",
  "name": "How to Find PHP Articles and Tutorials",
  "description": "Learn how to browse and search through our collection of PHP articles, tutorials, and guides for backend developers.",
  "image": "{{ asset('assets/images/favicon.ico') }}",
  "totalTime": "PT5M",
  "estimatedCost": {
    "@type": "MonetaryAmount",
    "currency": "USD",
    "value": "0"
  },
  "step": [
    {
      "@type": "HowToStep",
      "name": "Browse Articles",
      "text": "Scroll through our latest PHP articles and tutorials on the main blog page.",
      "url": "{{ route('test') }}"
    },
    {
      "@type": "HowToStep",
      "name": "Search Articles",
      "text": "Use the search box to find specific topics or keywords in our article collection.",
      "url": "{{ route('test') }}"
    },
    {
      "@type": "HowToStep",
      "name": "Read Articles",
      "text": "Click on any article to read the full tutorial or guide.",
      "url": "{{ route('test') }}"
    }
  ]
}
</script>
@endif

@if($articles->total() > 0)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ArticleList",
  "name": "{{ $searchQuery ? 'Search Results for ' . $searchQuery : 'Latest PHP Articles' }}",
  "description": "{{ $searchQuery ? 'Search results for ' . $searchQuery : 'Latest PHP articles and tutorials for backend developers' }}",
  "url": "{{ request()->fullUrl() }}",
  "numberOfItems": {{ $articles->total() }},
  "itemListElement": [
    @foreach($articles as $index => $article)
    {
      "@type": "ListItem",
      "position": {{ ($articles->currentPage() - 1) * $articles->perPage() + $loop->iteration }},
      "item": {
        "@type": "Article",
        "headline": "{{ addslashes($article->name) }}",
        "description": "{{ addslashes($article->short_description) }}",
        "url": "{{ $article->getRoute() }}",
        "datePublished": "{{ $article->getPublishedDate()->format('Y-m-d\TH:i:sP') }}",
        "author": {
          "@type": "Person",
          "name": "Jakub Owsianka"
        }
      }
    }@if(!$loop->last),@endif
    @endforeach
  ]
}
</script>
@endif

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "oatllo",
  "url": "{{ route('index') }}",
  "logo": "{{ asset('assets/images/favicon.ico') }}",
  "sameAs": [
    "https://www.linkedin.com/in/jakub-owsianka-446bb5213/"
  ],
  "founder": {
    "@type": "Person",
    "name": "Jakub Owsianka",
    "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/"
  }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "Jakub Owsianka",
  "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/",
  "jobTitle": "PHP Developer",
  "worksFor": {
    "@type": "Organization",
    "name": "oatllo"
  },
  "sameAs": [
    "https://www.linkedin.com/in/jakub-owsianka-446bb5213/"
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "oatllo",
  "description": "PHP development tutorials and articles for backend developers",
  "url": "{{ route('index') }}",
  "telephone": "+48-XXX-XXX-XXX",
  "address": {
    "@type": "PostalAddress",
    "addressCountry": "PL"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "52.2297",
    "longitude": "21.0122"
  },
  "openingHours": "Mo-Fr 09:00-17:00",
  "priceRange": "$$"
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CreativeWork",
  "name": "{{ __('basic.meta_title') }}",
  "description": "{{ __('basic.meta_description') }}",
  "url": "{{ route('index') }}",
  "author": {
    "@type": "Person",
    "name": "Jakub Owsianka"
  },
  "publisher": {
    "@type": "Organization",
    "name": "oatllo"
  },
  "datePublished": "2024-01-01",
  "dateModified": "{{ now()->format('Y-m-d') }}",
  "inLanguage": "{{ env('APP_LOCALE', 'en') }}",
  "isAccessibleForFree": true
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "{{ $searchQuery ? 'Search Results for ' . $searchQuery . ' - ' . __('basic.meta_title') : __('basic.meta_title') }}",
  "description": "{{ $searchQuery ? 'Search results for ' . $searchQuery . '. ' . __('basic.meta_description') : __('basic.meta_description') }}",
  "url": "{{ request()->fullUrl() }}",
  "isPartOf": {
    "@type": "WebSite",
    "name": "{{ __('basic.meta_title') }}",
    "url": "{{ route('index') }}"
  },
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "{{ route('index') }}"
      },
      {
        "@type": "ListItem",
        "position": 2,
        "name": "{{ $searchQuery ? 'Search Results' : 'Blog' }}",
        "item": "{{ request()->fullUrl() }}"
      }
    ]
  },
  "mainEntity": {
    "@type": "{{ $searchQuery ? 'SearchResultsPage' : 'Blog' }}",
    "name": "{{ $searchQuery ? 'Search Results for ' . $searchQuery : 'Latest PHP Articles' }}"
  }
}
</script>

@if($searchQuery)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SearchResultsPage",
  "name": "Search Results for {{ $searchQuery }}",
  "description": "Search results for {{ $searchQuery }} on {{ __('basic.meta_title') }}",
  "url": "{{ request()->fullUrl() }}",
  "query": "{{ $searchQuery }}",
  "numberOfItems": {{ $articles->total() }},
  "isPartOf": {
    "@type": "WebSite",
    "name": "{{ __('basic.meta_title') }}",
    "url": "{{ route('index') }}"
  }
}
</script>
@endif

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "{{ __('basic.meta_title') }}",
  "description": "{{ __('basic.meta_description') }}",
  "url": "{{ route('index') }}",
  "applicationCategory": "DeveloperApplication",
  "operatingSystem": "Web Browser",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "USD"
  },
  "author": {
    "@type": "Person",
    "name": "Jakub Owsianka"
  }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Course",
  "name": "PHP Development Tutorials",
  "description": "Comprehensive PHP tutorials and guides for backend developers",
  "provider": {
    "@type": "Organization",
    "name": "oatllo",
    "url": "{{ route('index') }}"
  },
  "courseMode": "online",
  "educationalLevel": "intermediate",
  "inLanguage": "{{ env('APP_LOCALE', 'en') }}",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "USD",
    "availability": "https://schema.org/InStock"
  },
  "instructor": {
    "@type": "Person",
    "name": "Jakub Owsianka"
  }
}
</script>

@if($articles->total() > 0)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TechArticle",
  "name": "PHP Development Resources",
  "description": "Collection of PHP articles, tutorials, and guides for backend developers",
  "url": "{{ request()->fullUrl() }}",
  "author": {
    "@type": "Person",
    "name": "Jakub Owsianka"
  },
  "publisher": {
    "@type": "Organization",
    "name": "oatllo"
  },
  "datePublished": "{{ $articles->first()->getPublishedDate()->format('Y-m-d\TH:i:sP') }}",
  "dateModified": "{{ $articles->first()->updated_at->format('Y-m-d\TH:i:sP') }}",
  "wordCount": {{ $articles->sum(function($article) { return str_word_count(strip_tags($article->content ?? '')); }) }},
  "dependencies": "PHP, Laravel, MySQL",
  "proficiencyLevel": "intermediate"
}
</script>
@endif

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LearningResource",
  "name": "{{ __('basic.meta_title') }}",
  "description": "{{ __('basic.meta_description') }}",
  "url": "{{ route('index') }}",
  "learningResourceType": "Tutorial",
  "educationalLevel": "intermediate",
  "inLanguage": "{{ env('APP_LOCALE', 'en') }}",
  "teaches": "PHP Development",
  "educationalUse": "self-study",
  "timeRequired": "PT30M",
  "typicalAgeRange": "18-65",
  "interactivityType": "active",
  "isAccessibleForFree": true,
  "author": {
    "@type": "Person",
    "name": "Jakub Owsianka"
  }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "{{ __('basic.meta_title') }}",
  "url": "{{ route('index') }}",
  "description": "{{ __('basic.meta_description') }}",
  "potentialAction": [
    {
      "@type": "SearchAction",
      "target": {
        "@type": "EntryPoint",
        "urlTemplate": "{{ route('test') }}?q={search_term_string}"
      },
      "query-input": "required name=search_term_string"
    },
    {
      "@type": "ReadAction",
      "target": "{{ route('test') }}"
    }
  ],
  "publisher": {
    "@type": "Organization",
    "name": "oatllo"
  }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SiteNavigationElement",
  "name": "Main Navigation",
  "url": "{{ route('index') }}",
  "hasPart": [
    {
      "@type": "WebPage",
      "name": "Home",
      "url": "{{ route('index') }}"
    },
    {
      "@type": "WebPage",
      "name": "Blog",
      "url": "{{ route('test') }}"
    },
    {
      "@type": "WebPage",
      "name": "Courses",
      "url": "{{ \App\Services\HomeService::getRouteCourses() }}"
    }
  ]
}
</script>

@if($articles->total() > 0)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "DataFeed",
  "name": "PHP Articles Feed",
  "description": "Latest PHP articles and tutorials",
  "url": "{{ request()->fullUrl() }}",
  "dataFeedElement": [
    @foreach($articles as $article)
    {
      "@type": "DataFeedItem",
      "name": "{{ addslashes($article->name) }}",
      "description": "{{ addslashes($article->short_description) }}",
      "url": "{{ $article->getRoute() }}",
      "dateModified": "{{ $article->updated_at->format('Y-m-d\TH:i:sP') }}",
      "dateCreated": "{{ $article->getPublishedDate()->format('Y-m-d\TH:i:sP') }}"
    }@if(!$loop->last),@endif
    @endforeach
  ]
}
</script>
@endif

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "{{ $searchQuery ? 'Search Results for ' . $searchQuery : 'PHP Articles Collection' }}",
  "description": "{{ $searchQuery ? 'Search results for ' . $searchQuery : 'Collection of PHP articles, tutorials, and guides for backend developers' }}",
  "url": "{{ request()->fullUrl() }}",
  "isPartOf": {
    "@type": "WebSite",
    "name": "{{ __('basic.meta_title') }}",
    "url": "{{ route('index') }}"
  },
  "mainEntity": {
    "@type": "ItemList",
    "numberOfItems": {{ $articles->total() }},
    "itemListElement": [
      @foreach($articles as $index => $article)
      {
        "@type": "ListItem",
        "position": {{ ($articles->currentPage() - 1) * $articles->perPage() + $loop->iteration }},
        "item": {
          "@type": "Article",
          "headline": "{{ addslashes($article->name) }}",
          "description": "{{ addslashes($article->short_description) }}",
          "url": "{{ $article->getRoute() }}"
        }
      }@if(!$loop->last),@endif
      @endforeach
    ]
  }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "{{ __('basic.meta_title') }}",
  "url": "{{ route('index') }}",
  "description": "{{ __('basic.meta_description') }}",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "{{ route('test') }}?q={search_term_string}"
    },
    "query-input": "required name=search_term_string"
  },
  "publisher": {
    "@type": "Organization",
    "name": "oatllo"
  },
  "inLanguage": "{{ env('APP_LOCALE', 'en') }}"
}
</script>
</body>
</html>
<!-- =============================================================
  NOTES:
  • Replace {YOUR_DOMAIN} and {YOUR_KIT_ID}.
  • Duplicate <article> card markup via a CMS/SSG loop.
  • Use line‑clamp utilities (requires @tailwindcss/line-clamp plugin) for description.
  • Accessible: <article>, <time>, alt texts, aria labels for nav.
  • SEO: Canonical, meta description, OpenGraph, Blog schema + ItemList.
  • UI/UX: Card hover zoom, dark theme, grid responsive, search field.
============================================================= -->


