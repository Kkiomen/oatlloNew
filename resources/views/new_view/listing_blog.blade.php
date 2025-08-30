<!-- =============================================================
  BLOG LISTING PAGE (Tailwind CSS v3 + Font Awesome 6)
  Brand: Dark UI with rose accent – consistent with landing page.
  Focus keywords: PHP blog, backend development articles, learn PHP
  ============================================================= -->

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ __('basic.meta_title') }}</title>
    <meta name="description" content="{{ __('basic.meta_description') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="index, follow">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}


    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">

    <link rel="canonical" href="{{ route('index') }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <meta property="og:title" content="{{ __('basic.meta_title') }}">
    <meta property="og:description" content="{{ __('basic.meta_description') }}">
    {{--    <meta property="og:image" content="{{ $basic_website_structure_op_image_img_file }}">--}}
    <meta property="og:url" content="{{ route('index') }}">


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
            Latest <span class="text-rose-400">PHP Articles</span>
        </h1>
        <p class="mt-4 text-lg text-neutral-300">
            Practical tutorials, performance tips and deep‑dive guides for modern backend developers.
        </p>
        <!-- Search / filter (optional) -->
        <form action="/search" method="get" class="relative mt-8 flex justify-center">
            <input type="search" name="q" placeholder="Search articles…" aria-label="Search blog" class="w-full max-w-lg rounded-xl border border-transparent bg-white/10 p-3 pr-10 placeholder-neutral-400 text-white focus:outline-none focus:ring-2 focus:ring-rose-500" />
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-rose-400" aria-label="Search"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
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
                    <p>Brak No articles to display.</p>
                </div>
            </div>
        @endforelse
    </section>

    <!-- Pagination -->
    <nav class="mt-20 flex justify-center" aria-label="Blog pagination">
        <ul class="inline-flex items-center gap-2">
            <li><a href="?page=1" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white" aria-label="First page"><i class="fa-solid fa-angles-left"></i></a></li>
            <li><a href="?page=prev" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white" aria-label="Previous page"><i class="fa-solid fa-angle-left"></i></a></li>
            <!-- Page numbers (render dynamically) -->
            <li><a href="?page=1" class="rounded-full bg-rose-500 px-3 py-2 text-sm font-semibold text-white">1</a></li>
            <li><a href="?page=2" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white">2</a></li>
            <li><a href="?page=3" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white">3</a></li>
            <li><span class="px-2 text-neutral-500">…</span></li>
            <li><a href="?page=8" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white">8</a></li>
            <li><a href="?page=next" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white" aria-label="Next page"><i class="fa-solid fa-angle-right"></i></a></li>
            <li><a href="?page=last" class="rounded-full bg-white/10 px-3 py-2 text-sm text-neutral-300 hover:bg-rose-500 hover:text-white" aria-label="Last page"><i class="fa-solid fa-angles-right"></i></a></li>
        </ul>
    </nav>
</main>

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD (ItemList of BlogPosting)
=========================================================== -->
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "ItemList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "url": "https://{YOUR_DOMAIN}/articles/optimise-php-performance"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "url": "https://{YOUR_DOMAIN}/articles/php-8-attributes-guide"
        }
        /* Add more items programmatically */
      ]
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


