@php
    use Illuminate\Support\Str;

    $seoTitle = !empty($article->view_content['basic_website_structure_title']) ? $article->view_content['basic_website_structure_title'] : $article->name;
    $seoDescription = !empty($article->view_content['basic_website_structure_description']) ? $article->view_content['basic_website_structure_description'] : $article->short_description;
    $ogTitle = !empty($article->view_content['basic_website_structure_op_title']) ? $article->view_content['basic_website_structure_op_title'] : $article->name;
    $ogDescription = !empty($article->view_content['basic_website_structure_op_description']) ? $article->view_content['basic_website_structure_op_description'] : $article->short_description;
    $imgAlt = !empty($article->view_content['basic_website_structure_image_img_alt']) ? $article->view_content['basic_website_structure_image_img_alt'] : $article->name;

    $categoryName = $article->getCategoryName();
    $categorySlug = optional($article->category)->slug ?: ($categoryName ? Str::slug($categoryName) : null);
    $sectionName  = $categoryName ?: 'Programming';
    $htmlLang     = env('APP_LANG_HTML');
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLang }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="index, follow">
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="canonical" href="{{ $article->getRoute() }}" />
    <link rel="alternate" hreflang="{{ $htmlLang }}" href="{{ $article->getRoute() }}">
    <link rel="alternate" type="application/rss+xml" title="Oatllo RSS Feed" href="{{ route('feed') }}" />
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <!-- SEO Meta Tags -->
    @if(!empty($article->view_content['basic_website_structure_keywords']))
        <meta name="keywords" content="{{ $article->view_content['basic_website_structure_keywords'] }}">
    @endif
    <meta name="author" content="Jakub Owsianka">
    <meta name="copyright" content="Oatllo">
    <meta name="language" content="{{ $htmlLang }}">

    <!-- Article specific meta tags -->
    <meta property="article:author" content="Jakub Owsianka">
    <meta property="article:published_time" content="{{ $article->created_at->toISOString() }}">
    <meta property="article:modified_time" content="{{ $article->updated_at->toISOString() }}">
    <meta property="article:section" content="{{ $sectionName }}">
    @if(!$article->tags->isEmpty())
        @foreach($article->tags as $tag)
            <meta property="article:tag" content="{{ $tag->name }}">
        @endforeach
    @endif

    <!-- Open Graph -->
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:locale" content="{{ $htmlLang }}">
    <meta property="og:title" content="{{ $ogTitle }}" />
    <meta property="og:description" content="{{ $ogDescription }}" />
    <meta property="og:url" content="{{ $article->getRoute() }}" />
    <meta property="og:image" content="{{ $article->image }}" />
    <meta property="og:type" content="article">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $article->name }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $article->image }}">
    <meta name="twitter:site" content="@Oatllo">
    <meta name="twitter:creator" content="@Oatllo">

    <!-- Structured Data - JSON-LD (custom from CMS) -->
    @if(!empty($article->structure_data_google))
        <script type="application/ld+json">
            {!! $article->structure_data_google !!}
        </script>
    @endif

    <style>
        body { font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif; }
        .glass { background-color: rgba(10,10,10,.72); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .hero-glow { background: radial-gradient(60% 50% at 50% 0%, rgba(244,63,94,.16) 0%, rgba(244,63,94,0) 70%); }
        .card-hover { transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        .card-hover:hover { transform: translateY(-4px); border-color: rgba(244,63,94,.5); }
        .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }

        /* Reading progress */
        #reading-bar { position: fixed; top: 0; left: 0; height: 3px; width: 0; z-index: 60; background: linear-gradient(90deg,#f43f5e,#ec4899); transition: width .1s linear; }

        /* Table of contents active state */
        .toc a { color:#a3a3a3; border-left:2px solid transparent; transition: color .15s, border-color .15s; }
        .toc a:hover { color:#fff; }
        .toc a.active { color:#fb7185; border-left-color:#f43f5e; }

        /* PROSE (article body) */
        .prose { color:#d4d4d8; }
        .prose h2 { font-size:1.6rem; font-weight:700; color:#fff; margin-top:3rem; margin-bottom:1rem; padding-bottom:.5rem; border-bottom:1px solid #262626; scroll-margin-top:6rem; }
        .prose h3 { font-size:1.25rem; font-weight:600; color:#fff; margin-top:2rem; margin-bottom:.75rem; scroll-margin-top:6rem; }
        .prose h4 { font-size:1.125rem; font-weight:600; color:#e5e5e5; margin-top:1.5rem; margin-bottom:.5rem; scroll-margin-top:6rem; }
        .prose p { color:#d4d4d8; line-height:1.85; margin-bottom:1.15rem; font-size:1.05rem; }
        .prose strong, .prose b { color:#fff; font-weight:600; }
        .prose ul { list-style-type:disc; padding-left:1.5rem; margin-bottom:1.15rem; color:#d4d4d8; }
        .prose ol { list-style-type:decimal; padding-left:1.5rem; margin-bottom:1.15rem; color:#d4d4d8; }
        .prose li { margin-bottom:.5rem; line-height:1.8; }
        .prose ul > li::marker, .prose ol > li::marker { color:#f43f5e; }
        .prose a { color:#f43f5e; text-decoration:underline; text-underline-offset:2px; }
        .prose a:hover { color:#fb7185; }
        .prose blockquote { border-left:4px solid #f43f5e; padding:.25rem 0 .25rem 1.25rem; margin:1.5rem 0; font-style:italic; color:#e5e5e5; background:rgba(244,63,94,.05); border-radius:0 .5rem .5rem 0; }
        .prose img { border-radius:.75rem; margin:1.5rem 0; }
        .prose code { background:#171717; color:#fda4af; border-radius:.25rem; padding:.125rem .35rem; font-size:.9em; }
        .prose pre { background:#0f0f0f !important; color:#f5f5f5; border:1px solid #262626; border-radius:.75rem; overflow-x:auto; font-size:.9rem; padding:1rem 1.15rem; margin:1.5rem 0; }
        .prose pre code { background:transparent; color:inherit; padding:0; }
        .prose table { width:100%; border-collapse:collapse; margin:1.5rem 0; font-size:.95rem; display:block; overflow-x:auto; }
        .prose th, .prose td { border:1px solid #262626; padding:.6rem .8rem; text-align:left; }
        .prose th { background:#171717; color:#fff; }
    </style>

    <!-- Highlight.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlightjs-themes@1.0.0/github.css">
    <link rel="stylesheet" href="{{ asset('/assets/css/article-style.css') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/fontawesome.min.css" integrity="sha512-v8QQ0YQ3H4K6Ic3PJkym91KoeNT5S3PnDKvqnwqFD1oiqIl653crGZplPdU5KKtHjO0QKcQ2aUlQZYjHczkmGw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
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
                        </div>
                        <div class="py-6">
                            <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800"><i class="fa-brands fa-linkedin mr-2"></i>LinkedIn</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</div>

<!-- ===========================================================
  ARTICLE HEADER (HERO)
=========================================================== -->
<header class="relative isolate overflow-hidden pt-32 pb-10 sm:pt-40" itemscope itemtype="https://schema.org/Article">
    <div class="absolute inset-0 -z-10 hero-glow" aria-hidden="true"></div>
    <meta itemprop="mainEntityOfPage" content="{{ $article->getRoute() }}" />
    <meta itemprop="author" content="Jakub Owsianka" />
    <meta itemprop="publisher" content="Oatllo - Jakub Owsianka" />
    <meta itemprop="datePublished" content="{{ $article->getPublishedDate()->format('Y-m-d') }}" />
    <meta itemprop="dateModified" content="{{ $article->updated_at->format('Y-m-d') }}" />
    <meta itemprop="image" content="{{ $article->image }}" />
    <meta itemprop="articleSection" content="{{ $sectionName }}" />

    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mx-auto mb-8 max-w-4xl px-4 sm:px-6 lg:px-8">
        <ol class="flex flex-wrap gap-2 text-sm text-neutral-500" itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                <a href="{{ route('index') }}" itemprop="item" class="hover:text-rose-400"><span itemprop="name">{{ __('basic.home') }}</span></a>
                <meta itemprop="position" content="1" />
            </li>
            <li>&#8250;</li>
            <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                <a href="{{ route('blog') }}" itemprop="item" class="hover:text-rose-400"><span itemprop="name">Blog</span></a>
                <meta itemprop="position" content="2" />
            </li>
            @if($categorySlug)
                <li>&#8250;</li>
                <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                    <a href="{{ route('blog.list.category', ['slug' => $categorySlug]) }}" itemprop="item" class="hover:text-rose-400"><span itemprop="name">{{ $categoryName }}</span></a>
                    <meta itemprop="position" content="3" />
                </li>
            @endif
            <li>&#8250;</li>
            <li class="truncate text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                <span itemprop="name">{{ $article->name }}</span>
                <meta itemprop="item" content="{{ $article->getRoute() }}" />
                <meta itemprop="position" content="{{ $categorySlug ? '4' : '3' }}" />
            </li>
        </ol>
    </nav>

    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        @if($categoryName)
            <a href="{{ route('blog.list.category', ['slug' => $categorySlug]) }}" class="inline-flex items-center gap-2 rounded-full border border-rose-400/30 bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-300 hover:bg-rose-500/20 transition-colors duration-200">
                <i class="fa-solid fa-folder-open"></i> {{ $categoryName }}
            </a>
        @endif

        <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-white md:text-5xl lg:text-6xl" itemprop="headline">{{ $article->name }}</h1>
        <p class="mt-5 max-w-2xl text-lg text-neutral-400" itemprop="description">{{ $article->short_description }}</p>

        <!-- Meta row -->
        <div class="mt-8 flex flex-wrap items-center gap-x-4 gap-y-3 border-y border-white/5 py-5 text-sm">
            <div class="flex items-center gap-3">
                <img src="{{ asset('/assets/images/owsianka_jakub.png') }}" alt="Jakub Owsianka" class="h-10 w-10 rounded-full object-cover ring-2 ring-white/10">
                <div class="leading-tight">
                    <div class="font-semibold text-white">Jakub Owsianka</div>
                    <div class="text-xs text-neutral-500">{{ __('basic.about_me_description') }}</div>
                </div>
            </div>
            <span class="hidden h-8 w-px bg-white/10 sm:block"></span>
            <div class="flex items-center gap-2 text-neutral-400">
                <i class="fa-solid fa-calendar text-rose-400"></i>
                <time datetime="{{ $article->getPublishedDate()->format('Y-m-d') }}" itemprop="datePublished">{{ $article->getPublishedDate()->format('M j, Y') }}</time>
            </div>
            <div class="flex items-center gap-2 text-neutral-400">
                <i class="fa-solid fa-clock text-rose-400"></i>
                <span>{{ $article->getTimeRead() }}&nbsp;min read</span>
            </div>
        </div>

        @if(!$article->tags->isEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($article->tags as $tag)
                    @php($tagSlug = $tag->slug ?: Str::slug($tag->name))
                    @if($tagSlug)
                        <a href="{{ route('blogTag', ['tag' => $tagSlug]) }}" class="rounded-full bg-white/5 px-3 py-1 text-xs text-neutral-300 hover:bg-rose-500 hover:text-white transition-colors duration-200">#{{ $tag->name }}</a>
                    @else
                        <span class="rounded-full bg-white/5 px-3 py-1 text-xs text-neutral-400">#{{ $tag->name }}</span>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <!-- Hero image -->
    <figure class="mx-auto mt-10 max-w-5xl px-4 sm:px-6 lg:px-8" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
        <div class="overflow-hidden rounded-3xl border border-white/10 shadow-2xl">
            <img src="{{ $article->image }}" alt="{{ $imgAlt }}" class="h-auto max-h-[28rem] w-full object-cover" loading="eager" />
        </div>
        <meta itemprop="url" content="{{ $article->image }}" />
        <meta itemprop="width" content="1200" />
        <meta itemprop="height" content="630" />
    </figure>
</header>

<!-- ===========================================================
  CONTENT + TABLE OF CONTENTS
=========================================================== -->
<div class="mx-auto mt-12 max-w-6xl px-4 sm:px-6 lg:px-8">
    <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_15rem] lg:gap-12">
        <!-- Article body -->
        <article id="article-body" class="prose prose-invert max-w-none" itemprop="articleBody">
            @foreach($article->getDisplayContents() as $content)
                @if($content['type'] == 'text' && !empty($content['content']))
                    {!! $content['content'] !!}
                @endif
                @if($content['type'] == 'image' && !empty($content['content']))
                    <figure class="my-8">
                        <img class="w-full rounded-xl object-cover" src="{{ $content['content'] }}" alt="{{ $content['alt'] ?? $article->name }}" loading="lazy">
                    </figure>
                @endif
            @endforeach

            <!-- Share row -->
            <div class="mt-12 flex flex-wrap items-center gap-4 border-t border-white/10 pt-6 not-prose">
                <span class="text-sm font-semibold text-neutral-400">Share:</span>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($article->getRoute()) }}&text={{ urlencode($article->name) }}&via=Oatllo" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 text-neutral-400 hover:bg-rose-500 hover:text-white transition-colors duration-200" aria-label="Share on X/Twitter"><i class="fa-brands fa-x-twitter"></i></a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($article->getRoute()) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 text-neutral-400 hover:bg-rose-500 hover:text-white transition-colors duration-200" aria-label="Share on LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($article->getRoute()) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 text-neutral-400 hover:bg-rose-500 hover:text-white transition-colors duration-200" aria-label="Share on Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                <button type="button" onclick="navigator.clipboard.writeText('{{ $article->getRoute() }}'); this.querySelector('span').textContent='Copied!';" class="ml-auto inline-flex items-center gap-2 rounded-lg bg-white/5 px-3 py-2 text-sm text-neutral-400 hover:bg-white/10 hover:text-white transition-colors duration-200"><i class="fa-solid fa-link"></i><span>Copy link</span></button>
            </div>
        </article>

        <!-- Sticky TOC (desktop) -->
        <aside class="hidden lg:block">
            <div class="sticky top-24">
                <nav id="toc" class="toc space-y-1 border-l border-white/10 text-sm" aria-label="Table of contents" x-data x-show="$el.children.length > 0">
                    <p class="mb-3 pl-4 text-xs font-semibold uppercase tracking-wide text-neutral-500">On this page</p>
                </nav>
            </div>
        </aside>
    </div>
</div>

<!-- ===========================================================
  AUTHOR CARD
=========================================================== -->
<section class="mx-auto mt-16 max-w-4xl px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col items-start gap-5 rounded-2xl border border-white/10 bg-neutral-900 p-6 sm:flex-row sm:items-center" itemscope itemtype="https://schema.org/Person">
        <img src="{{ asset('/assets/images/owsianka_jakub.png') }}" alt="Jakub Owsianka" class="h-16 w-16 flex-none rounded-full object-cover ring-2 ring-rose-500/30" itemprop="image" />
        <div class="flex-1">
            <div class="text-xs uppercase tracking-wide text-rose-400">Written by</div>
            <h2 class="text-lg font-semibold text-white" itemprop="name">Jakub Owsianka</h2>
            <p class="mt-1 text-sm text-neutral-400" itemprop="description">PHP &amp; Laravel developer. I write about modern backend, architecture, DevOps and developer tooling.</p>
        </div>
        <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-rose-500 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-400 transition-colors duration-200">
            <i class="fa-brands fa-linkedin"></i> Follow
        </a>
    </div>
</section>

<!-- ===========================================================
  MORE ARTICLES
=========================================================== -->
<section class="mx-auto mt-20 max-w-6xl px-4 sm:px-6 lg:px-8">
    @if(isset($relatedArticles) && $relatedArticles->count() > 0)
        <div class="mb-16">
            <h2 class="mb-8 flex items-center gap-3 text-2xl font-bold text-white">
                <i class="fa-solid fa-link text-rose-400"></i> Related articles
            </h2>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($relatedArticles as $relatedArticle)
                    @include('views_basic.partials.article_card', ['card' => $relatedArticle])
                @endforeach
            </div>
        </div>
    @endif

    @if(isset($categoryArticles) && $categoryArticles->count() > 0)
        <div class="mb-16">
            <div class="mb-8 flex items-center justify-between gap-4">
                <h2 class="flex items-center gap-3 text-2xl font-bold text-white">
                    <i class="fa-solid fa-folder text-rose-400"></i> More from {{ $categoryName }}
                </h2>
                @if($categorySlug)
                    <a href="{{ route('blog.list.category', ['slug' => $categorySlug]) }}" class="hidden shrink-0 text-sm font-semibold text-rose-400 hover:text-rose-300 sm:inline-flex sm:items-center sm:gap-2">
                        View all <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                @endif
            </div>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($categoryArticles as $categoryArticle)
                    @include('views_basic.partials.article_card', ['card' => $categoryArticle])
                @endforeach
            </div>
        </div>
    @endif

    @if(isset($latestArticles) && $latestArticles->count() > 0)
        <div class="mb-4">
            <h2 class="mb-8 flex items-center gap-3 text-2xl font-bold text-white">
                <i class="fa-solid fa-clock text-rose-400"></i> Latest articles
            </h2>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($latestArticles as $latestArticle)
                    @include('views_basic.partials.article_card', ['card' => $latestArticle])
                @endforeach
            </div>
        </div>
    @endif
</section>

<!-- ===========================================================
  PREV / NEXT
=========================================================== -->
@if($previousArticle || $nextArticle)
    <nav class="mx-auto mt-20 mb-10 max-w-6xl px-4 sm:px-6 lg:px-8" aria-label="Article navigation">
        <div class="grid gap-4 sm:grid-cols-2">
            @if($previousArticle)
                <a href="{{ $previousArticle->getRoute() }}" class="card-hover group flex items-center gap-3 rounded-2xl border border-white/10 bg-neutral-900 p-5 text-neutral-300 hover:text-white">
                    <i class="fa-solid fa-angle-left flex-none transition-transform duration-200 group-hover:-translate-x-1 text-rose-400"></i>
                    <div class="min-w-0">
                        <div class="text-xs uppercase tracking-wide text-neutral-500">Previous</div>
                        <div class="truncate font-semibold">{{ $previousArticle->name }}</div>
                    </div>
                </a>
            @else
                <div></div>
            @endif

            @if($nextArticle)
                <a href="{{ $nextArticle->getRoute() }}" class="card-hover group flex items-center justify-end gap-3 rounded-2xl border border-white/10 bg-neutral-900 p-5 text-right text-neutral-300 hover:text-white">
                    <div class="min-w-0">
                        <div class="text-xs uppercase tracking-wide text-neutral-500">Next</div>
                        <div class="truncate font-semibold">{{ $nextArticle->name }}</div>
                    </div>
                    <i class="fa-solid fa-angle-right flex-none transition-transform duration-200 group-hover:translate-x-1 text-rose-400"></i>
                </a>
            @else
                <div></div>
            @endif
        </div>
    </nav>
@endif

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
            <a href="{{ route('blog') }}" class="text-sm text-neutral-400 hover:text-rose-400"><i class="fa-solid fa-arrow-left mr-1"></i> {{ __('basic.header_blog') }}</a>
        </div>
    </div>
</footer>

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD (Article)
=========================================================== -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": {!! json_encode($article->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "description": {!! json_encode($article->short_description, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "image": "{{ $article->image }}",
  "inLanguage": "{{ $article->language ?? $htmlLang }}",
  "author": { "@type": "Person", "name": "Jakub Owsianka", "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/" },
  "datePublished": "{{ $article->getPublishedDate()->format('Y-m-d\TH:i:sP') }}",
  "dateModified": "{{ $article->updated_at->format('Y-m-d\TH:i:sP') }}",
  "publisher": { "@type": "Organization", "name": "Oatllo", "logo": { "@type": "ImageObject", "url": "{{ asset('assets/images/logo-512.png') }}" } },
  "mainEntityOfPage": "{{ $article->getRoute() }}",
  "articleSection": {!! json_encode($sectionName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}@if(!$article->tags->isEmpty()),
  "keywords": {!! json_encode($article->tags->pluck('name')->implode(', '), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}@endif
}
</script>

<!-- ===========================================================
  SCRIPTS: reading bar, TOC, highlight
=========================================================== -->
<script>
    // Reading progress bar
    (function () {
        const bar = document.getElementById('reading-bar');
        const body = document.getElementById('article-body');
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

    // Auto Table of Contents from H2/H3 in the article body
    (function () {
        const body = document.getElementById('article-body');
        const toc = document.getElementById('toc');
        if (!body || !toc) return;
        const headings = body.querySelectorAll('h2, h3');
        if (!headings.length) { toc.style.display = 'none'; return; }

        const slugify = (t) => t.toLowerCase().trim().replace(/[^\w\s-]/g, '').replace(/\s+/g, '-').slice(0, 60) || 'section';
        const used = {};
        const links = [];

        headings.forEach((h) => {
            let id = h.id || slugify(h.textContent);
            if (used[id]) { id = id + '-' + (++used[id]); } else { used[id] = 1; }
            h.id = id;
            const a = document.createElement('a');
            a.href = '#' + id;
            a.textContent = h.textContent;
            a.className = 'block py-1.5 ' + (h.tagName === 'H3' ? 'pl-8 text-neutral-500' : 'pl-4');
            toc.appendChild(a);
            links.push(a);
        });

        // Active state on scroll
        const map = new Map();
        links.forEach((a) => map.set(a.getAttribute('href').slice(1), a));
        const obs = new IntersectionObserver((entries) => {
            entries.forEach((e) => {
                if (e.isIntersecting) {
                    links.forEach((l) => l.classList.remove('active'));
                    const link = map.get(e.target.id);
                    if (link) link.classList.add('active');
                }
            });
        }, { rootMargin: '-80px 0px -70% 0px', threshold: 0 });
        headings.forEach((h) => obs.observe(h));
    })();
</script>
<script>hljs.highlightAll();</script>
<script src="{{ asset('/assets/js/script.js') }}"></script>
</body>
</html>
