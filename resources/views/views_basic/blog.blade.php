<!-- =============================================================
  BLOG LISTING PAGE (Tailwind CSS v3 + Font Awesome 6)
  Brand: Dark UI with rose accent – consistent with landing page.
  Focus keywords: PHP blog, backend development articles, learn PHP
  ============================================================= -->

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>Programming Blog – Tips, Tutorials & Best Practices</title>
    <meta name="description" content="Discover programming tutorials, coding tips, and best practices. Stay up to date with software development insights for PHP, Python, JavaScript and more.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="author" content="Oatllo - Jakub Owsianka">

    <meta name="robots" content="index, follow">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}


    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">

    <link rel="canonical" href="{{ route('blog') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="Programming Blog – Tips, Tutorials & Best Practices">
    <meta property="og:description" content="Discover programming tutorials, coding tips, and best practices. Stay up to date with software development insights for PHP, Python, JavaScript and more.">
    <meta property="og:url" content="{{ route('blog') }}">
    <meta property="og:site_name" content="Programming Blog">
    <meta property="og:image" content="https://oatllo.com/assets/images/logo-512.png">
    <meta property="og:locale" content="en_US">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Programming Blog – Tips, Tutorials & Best Practices">
    <meta name="twitter:description" content="Discover programming tutorials, coding tips, and best practices. Stay up to date with software development insights for PHP, Python, JavaScript and more.">
    <meta name="twitter:image" content="https://oatllo.com/assets/images/logo-512.png">
    <meta name="twitter:site" content="@Oatllo">
    <meta name="twitter:creator" content="@Oatllo">


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
        <form action="{{ route('blog') }}" method="get" class="relative mt-8 flex justify-center">
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

{{--    <!-- Programming Topics Section -->--}}
{{--    <section class="mx-auto mb-20 max-w-7xl px-4 sm:px-6 lg:px-8">--}}
{{--        <div class="text-center mb-12">--}}
{{--            <h2 class="text-3xl font-bold text-white mb-4">Explore Programming <span class="text-rose-400">Topics</span></h2>--}}
{{--            <p class="text-neutral-300 max-w-2xl mx-auto">Discover articles organized by topics that matter to developers</p>--}}
{{--        </div>--}}
{{--        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">--}}
{{--            <a href="{{ route('blog') }}?topic=backend" class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-neutral-800 to-neutral-900 p-6 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-rose-500/20">--}}
{{--                <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>--}}
{{--                <div class="relative z-10">--}}
{{--                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-500/20 text-blue-400">--}}
{{--                        <i class="fa-solid fa-server text-xl"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white mb-2">Backend Development</h3>--}}
{{--                    <p class="text-sm text-neutral-400">Server-side programming</p>--}}
{{--                </div>--}}
{{--            </a>--}}

{{--            <a href="{{ route('blog') }}?topic=performance" class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-neutral-800 to-neutral-900 p-6 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-rose-500/20">--}}
{{--                <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>--}}
{{--                <div class="relative z-10">--}}
{{--                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-green-500/20 text-green-400">--}}
{{--                        <i class="fa-solid fa-tachometer-alt text-xl"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white mb-2">Performance</h3>--}}
{{--                    <p class="text-sm text-neutral-400">Optimization techniques</p>--}}
{{--                </div>--}}
{{--            </a>--}}

{{--            <a href="{{ route('blog') }}?topic=security" class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-neutral-800 to-neutral-900 p-6 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-rose-500/20">--}}
{{--                <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>--}}
{{--                <div class="relative z-10">--}}
{{--                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-500/20 text-yellow-400">--}}
{{--                        <i class="fa-solid fa-shield-alt text-xl"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white mb-2">Security</h3>--}}
{{--                    <p class="text-sm text-neutral-400">Best practices</p>--}}
{{--                </div>--}}
{{--            </a>--}}

{{--            <a href="{{ route('blog') }}?topic=testing" class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-neutral-800 to-neutral-900 p-6 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-rose-500/20">--}}
{{--                <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>--}}
{{--                <div class="relative z-10">--}}
{{--                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-purple-500/20 text-purple-400">--}}
{{--                        <i class="fa-solid fa-vial text-xl"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white mb-2">Testing</h3>--}}
{{--                    <p class="text-sm text-neutral-400">Quality assurance</p>--}}
{{--                </div>--}}
{{--            </a>--}}

{{--            <a href="{{ route('blog') }}?topic=devops" class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-neutral-800 to-neutral-900 p-6 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-rose-500/20">--}}
{{--                <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>--}}
{{--                <div class="relative z-10">--}}
{{--                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400">--}}
{{--                        <i class="fa-solid fa-cogs text-xl"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white mb-2">DevOps</h3>--}}
{{--                    <p class="text-sm text-neutral-400">Deployment & CI/CD</p>--}}
{{--                </div>--}}
{{--            </a>--}}

{{--            <a href="{{ route('blog') }}?topic=api" class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-neutral-800 to-neutral-900 p-6 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-rose-500/20">--}}
{{--                <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>--}}
{{--                <div class="relative z-10">--}}
{{--                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-pink-500/20 text-pink-400">--}}
{{--                        <i class="fa-solid fa-code text-xl"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white mb-2">API Design</h3>--}}
{{--                    <p class="text-sm text-neutral-400">REST & GraphQL</p>--}}
{{--                </div>--}}
{{--            </a>--}}

{{--            <a href="{{ route('blog') }}?topic=database" class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-neutral-800 to-neutral-900 p-6 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-rose-500/20">--}}
{{--                <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>--}}
{{--                <div class="relative z-10">--}}
{{--                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-cyan-500/20 text-cyan-400">--}}
{{--                        <i class="fa-solid fa-database text-xl"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white mb-2">Database</h3>--}}
{{--                    <p class="text-sm text-neutral-400">Design & optimization</p>--}}
{{--                </div>--}}
{{--            </a>--}}

{{--            <a href="{{ route('blog') }}?topic=tips" class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-neutral-800 to-neutral-900 p-6 transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-rose-500/20">--}}
{{--                <div class="absolute inset-0 bg-gradient-to-br from-rose-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>--}}
{{--                <div class="relative z-10">--}}
{{--                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-orange-500/20 text-orange-400">--}}
{{--                        <i class="fa-solid fa-lightbulb text-xl"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white mb-2">Tips & Tricks</h3>--}}
{{--                    <p class="text-sm text-neutral-400">Developer insights</p>--}}
{{--                </div>--}}
{{--            </a>--}}
{{--        </div>--}}
{{--    </section>--}}

    <!-- Featured Article Section -->
    @if($articles->count() > 0)
        <section class="mx-auto mb-20 max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-white mb-4">Featured <span class="text-rose-400">Article</span></h2>
                <p class="text-neutral-300">Our most popular and comprehensive guide</p>
            </div>
            @php $featuredArticle = $articles->first(); @endphp
            <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-neutral-900 to-neutral-800 shadow-2xl">
                <div class="grid lg:grid-cols-2 gap-0">
                    <div class="relative">
                        <img src="{{ $featuredArticle->image }}" alt="{{ $featuredArticle->name }}" class="h-full w-full object-cover" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-transparent to-transparent"></div>
                    </div>
                    <div class="p-8 lg:p-12 flex flex-col justify-center">
                        <div class="mb-4">
                            <span class="inline-flex items-center rounded-full bg-rose-500/20 px-3 py-1 text-sm font-medium text-rose-400">
                                <i class="fa-solid fa-star mr-2"></i>Featured
                            </span>
                        </div>
                        <h3 class="text-2xl lg:text-3xl font-bold text-white mb-4">{{ $featuredArticle->name }}</h3>
                        <p class="text-lg text-neutral-300 mb-6 line-clamp-4">{{ $featuredArticle->short_description }}</p>
                        <div class="flex items-center gap-6 text-sm text-neutral-400 mb-6">
                            <div class="flex items-center">
                                <i class="fa-solid fa-calendar text-rose-400 mr-2"></i>
                                <time datetime="{{ $featuredArticle->getPublishedDate()->format('Y-m-d') }}">{{ $featuredArticle->getPublishedDate()->format('M j, Y') }}</time>
                            </div>
                            <div class="flex items-center">
                                <i class="fa-solid fa-clock text-rose-400 mr-2"></i>
                                <span>{{ $featuredArticle->getTimeRead() }} min read</span>
                            </div>
                            @if($featuredArticle->category)
                                <div class="flex items-center">
                                    <i class="fa-solid fa-folder text-rose-400 mr-2"></i>
                                    <span>{{ $featuredArticle->category->name }}</span>
                                </div>
                            @endif
                        </div>
                        <a href="{{ $featuredArticle->getRoute() }}" class="inline-flex items-center justify-center rounded-lg bg-rose-500 px-6 py-3 text-white font-semibold transition-colors hover:bg-rose-600">
                            Read Full Article
                            <i class="fa-solid fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </article>
        </section>
    @endif

    <!-- SEO Content Section -->
    <section class="mx-auto mb-20 max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="bg-neutral-900/50 rounded-2xl p-8 border border-neutral-800">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">Programming Resources & <span class="text-rose-400">Learning Hub</span></h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-xl font-semibold text-white mb-4 flex items-center">
                        <i class="fa-solid fa-code text-rose-400 mr-3"></i>
                        Latest Programming Tutorials
                    </h3>
                    <p class="text-neutral-300 mb-4">Discover comprehensive guides covering modern programming practices, best practices, and advanced techniques for developers at all skill levels.</p>
                    <ul class="space-y-2 text-sm text-neutral-400">
                        <li class="flex items-center"><i class="fa-solid fa-check text-rose-400 mr-2"></i>PHP 8+ features and optimizations</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-rose-400 mr-2"></i>Laravel framework tutorials</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-rose-400 mr-2"></i>Performance optimization tips</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-rose-400 mr-2"></i>Security best practices</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-white mb-4 flex items-center">
                        <i class="fa-solid fa-rocket text-rose-400 mr-3"></i>
                        Developer Productivity
                    </h3>
                    <p class="text-neutral-300 mb-4">Learn tools, techniques, and methodologies that will help you write better code faster and more efficiently.</p>
                    <ul class="space-y-2 text-sm text-neutral-400">
                        <li class="flex items-center"><i class="fa-solid fa-check text-rose-400 mr-2"></i>Code review strategies</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-rose-400 mr-2"></i>Testing methodologies</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-rose-400 mr-2"></i>CI/CD best practices</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-rose-400 mr-2"></i>Development workflow optimization</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

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

{{--        <!-- Developer Resources Section -->--}}
{{--    <section class="mx-auto mt-20 max-w-6xl px-4 sm:px-6 lg:px-8">--}}
{{--        <div class="text-center mb-12">--}}
{{--            <h2 class="text-2xl font-bold text-white mb-4">Developer <span class="text-rose-400">Resources</span></h2>--}}
{{--            <p class="text-neutral-300">Essential tools and knowledge for modern development</p>--}}
{{--        </div>--}}
{{--        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">--}}
{{--            <div class="group bg-neutral-900/50 rounded-xl p-6 border border-neutral-800 hover:border-rose-500/50 transition-all duration-300">--}}
{{--                <div class="flex items-center mb-4">--}}
{{--                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/20 text-blue-400 mr-4">--}}
{{--                        <i class="fa-solid fa-server text-lg"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white">Backend Development</h3>--}}
{{--                </div>--}}
{{--                <p class="text-neutral-400 text-sm mb-4">Master server-side programming with PHP, databases, APIs, and system architecture for scalable applications.</p>--}}
{{--                <div class="flex items-center justify-between">--}}
{{--                    <a href="{{ route('blog') }}?topic=backend" class="text-rose-400 hover:text-rose-300 text-sm font-medium inline-flex items-center">--}}
{{--                        Explore Backend--}}
{{--                        <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>--}}
{{--                    </a>--}}
{{--                    <span class="text-xs text-neutral-500">PHP, APIs, Databases</span>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <div class="group bg-neutral-900/50 rounded-xl p-6 border border-neutral-800 hover:border-rose-500/50 transition-all duration-300">--}}
{{--                <div class="flex items-center mb-4">--}}
{{--                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-500/20 text-green-400 mr-4">--}}
{{--                        <i class="fa-solid fa-tachometer-alt text-lg"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white">Performance Optimization</h3>--}}
{{--                </div>--}}
{{--                <p class="text-neutral-400 text-sm mb-4">Learn techniques to improve application speed, efficiency, and user experience through optimization.</p>--}}
{{--                <div class="flex items-center justify-between">--}}
{{--                    <a href="{{ route('blog') }}?topic=performance" class="text-rose-400 hover:text-rose-300 text-sm font-medium inline-flex items-center">--}}
{{--                        Optimize Performance--}}
{{--                        <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>--}}
{{--                    </a>--}}
{{--                    <span class="text-xs text-neutral-500">Speed, Efficiency</span>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <div class="group bg-neutral-900/50 rounded-xl p-6 border border-neutral-800 hover:border-rose-500/50 transition-all duration-300">--}}
{{--                <div class="flex items-center mb-4">--}}
{{--                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-500/20 text-yellow-400 mr-4">--}}
{{--                        <i class="fa-solid fa-shield-alt text-lg"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white">Security Best Practices</h3>--}}
{{--                </div>--}}
{{--                <p class="text-neutral-400 text-sm mb-4">Implement robust security measures to protect your applications and user data from vulnerabilities.</p>--}}
{{--                <div class="flex items-center justify-between">--}}
{{--                    <a href="{{ route('blog') }}?topic=security" class="text-rose-400 hover:text-rose-300 text-sm font-medium inline-flex items-center">--}}
{{--                        Secure Your Code--}}
{{--                        <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>--}}
{{--                    </a>--}}
{{--                    <span class="text-xs text-neutral-500">Protection, Safety</span>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <div class="group bg-neutral-900/50 rounded-xl p-6 border border-neutral-800 hover:border-rose-500/50 transition-all duration-300">--}}
{{--                <div class="flex items-center mb-4">--}}
{{--                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500/20 text-purple-400 mr-4">--}}
{{--                        <i class="fa-solid fa-vial text-lg"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white">Testing & Quality</h3>--}}
{{--                </div>--}}
{{--                <p class="text-neutral-400 text-sm mb-4">Build reliable applications with comprehensive testing strategies and quality assurance practices.</p>--}}
{{--                <div class="flex items-center justify-between">--}}
{{--                    <a href="{{ route('blog') }}?topic=testing" class="text-rose-400 hover:text-rose-300 text-sm font-medium inline-flex items-center">--}}
{{--                        Improve Quality--}}
{{--                        <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>--}}
{{--                    </a>--}}
{{--                    <span class="text-xs text-neutral-500">Testing, QA</span>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <div class="group bg-neutral-900/50 rounded-xl p-6 border border-neutral-800 hover:border-rose-500/50 transition-all duration-300">--}}
{{--                <div class="flex items-center mb-4">--}}
{{--                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400 mr-4">--}}
{{--                        <i class="fa-solid fa-cogs text-lg"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white">DevOps & Deployment</h3>--}}
{{--                </div>--}}
{{--                <p class="text-neutral-400 text-sm mb-4">Streamline your development workflow with modern DevOps practices and deployment tools.</p>--}}
{{--                <div class="flex items-center justify-between">--}}
{{--                    <a href="{{ route('blog') }}?topic=devops" class="text-rose-400 hover:text-rose-300 text-sm font-medium inline-flex items-center">--}}
{{--                        Master DevOps--}}
{{--                        <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>--}}
{{--                    </a>--}}
{{--                    <span class="text-xs text-neutral-500">CI/CD, Deployment</span>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <div class="group bg-neutral-900/50 rounded-xl p-6 border border-neutral-800 hover:border-rose-500/50 transition-all duration-300">--}}
{{--                <div class="flex items-center mb-4">--}}
{{--                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-pink-500/20 text-pink-400 mr-4">--}}
{{--                        <i class="fa-solid fa-lightbulb text-lg"></i>--}}
{{--                    </div>--}}
{{--                    <h3 class="text-lg font-semibold text-white">Tips & Tricks</h3>--}}
{{--                </div>--}}
{{--                <p class="text-neutral-400 text-sm mb-4">Discover practical tips, shortcuts, and best practices to enhance your development skills.</p>--}}
{{--                <div class="flex items-center justify-between">--}}
{{--                    <a href="{{ route('blog') }}?topic=tips" class="text-rose-400 hover:text-rose-300 text-sm font-medium inline-flex items-center">--}}
{{--                        Learn Tips--}}
{{--                        <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>--}}
{{--                    </a>--}}
{{--                    <span class="text-xs text-neutral-500">Best Practices</span>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </section>--}}

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
          "headline": {!! json_encode($article->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
      "description": {!! json_encode($article->short_description, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
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
            "articleSection": {!! json_encode($article->category->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
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
      "name": {!! json_encode($article->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
      "description": {!! json_encode($article->short_description, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
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

@if($searchQuery)
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
@endif

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
  }
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


