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
<!-- ===========================================================
  MAIN CONTENT
=========================================================== -->
<main id="blog" class="pt-24 pb-32" aria-label="Blog articles">
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
        <!-- Single article card – duplicate dynamically (use a CMS / static site generator) -->
        <article class="flex flex-col overflow-hidden rounded-2xl bg-neutral-900/70 shadow-lg transition hover:shadow-rose-500/30" itemscope itemprop="blogPost" itemtype="https://schema.org/BlogPosting">
            <a href="/articles/optimise-php-performance" class="group relative block" itemprop="url">
                <img src="/images/posts/php-performance-cover.webp" alt="Boosting PHP performance – hero image" class="h-56 w-full object-cover transition group-hover:scale-105" itemprop="image" loading="lazy" />
                <span class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></span>
            </a>
            <div class="flex flex-1 flex-col p-6">
                <header class="mb-3 flex-1">
                    <h2 class="text-xl font-bold tracking-tight text-white group-hover:text-rose-400" itemprop="headline">
                        <a href="/articles/optimise-php-performance" class="inline-block h-full w-full" itemprop="url">10 Proven Ways to Optimise PHP Performance in 2025</a>
                    </h2>
                    <p class="mt-2 line-clamp-3 text-neutral-400" itemprop="description">
                        Learn how to squeeze every millisecond out of your PHP apps with OPcache tuning, JIT insights, and real‑world benchmarking examples.
                    </p>
                </header>
                <!-- Meta info -->
                <footer class="mt-auto flex items-center justify-between text-sm text-neutral-400">
                    <div>
                        <i class="fa-solid fa-calendar text-rose-400 mr-1"></i>
                        <time datetime="2025-06-18" itemprop="datePublished">Jun&nbsp;18,&nbsp;2025</time>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-clock text-rose-400"></i>
                        <span>7&nbsp;min read</span>
                    </div>
                </footer>
            </div>
        </article>

        <!-- Repeat sample cards below -->
        <article class="flex flex-col overflow-hidden rounded-2xl bg-neutral-900/70 shadow-lg transition hover:shadow-rose-500/30" itemscope itemprop="blogPost" itemtype="https://schema.org/BlogPosting">
            <a href="/articles/php-8-attributes-guide" class="group relative block" itemprop="url">
                <img src="/images/posts/php-8-attributes.webp" alt="PHP 8 attributes code sample" class="h-56 w-full object-cover transition group-hover:scale-105" itemprop="image" loading="lazy" />
                <span class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></span>
            </a>
            <div class="flex flex-1 flex-col p-6">
                <header class="mb-3 flex-1">
                    <h2 class="text-xl font-bold tracking-tight text-white group-hover:text-rose-400" itemprop="headline">
                        <a href="/articles/php-8-attributes-guide" class="inline-block h-full w-full" itemprop="url">PHP 8 Attributes: Complete Guide with Practical Examples</a>
                    </h2>
                    <p class="mt-2 line-clamp-3 text-neutral-400" itemprop="description">
                        Discover how to leverage PHP 8 attributes for custom annotations, cleaner code and powerful metadata‑driven development.
                    </p>
                </header>
                <footer class="mt-auto flex items-center justify-between text-sm text-neutral-400">
                    <div>
                        <i class="fa-solid fa-calendar text-rose-400 mr-1"></i>
                        <time datetime="2025-05-29" itemprop="datePublished">May&nbsp;29,&nbsp;2025</time>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-clock text-rose-400"></i>
                        <span>5&nbsp;min read</span>
                    </div>
                </footer>
            </div>
        </article>

        <!-- Add additional <article> cards as needed -->
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


