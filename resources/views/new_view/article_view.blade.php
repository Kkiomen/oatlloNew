@php
use Illuminate\Support\Str;
@endphp


<!DOCTYPE html>
<html lang="{{ env('APP_LANG_HTML') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="index, follow">
    <title>{{ !empty($article->view_content['basic_website_structure_title']) ? $article->view_content['basic_website_structure_title'] : $article->name }}</title>
    <meta name="description" content="{{ !empty($article->view_content['basic_website_structure_description']) ? $article->view_content['basic_website_structure_description'] : $article->short_description }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="canonical" href="{{ $article->getRoute() }}" />
    <link rel="alternate" hreflang="en" href="{{ $article->getRoute() }}">
    <link rel="alternate" type="application/rss+xml" title="Oatllo RSS Feed" href="{{ route('feed') }}" />
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('/assets/css/article-style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <!-- SEO Meta Tags -->
    @if(!empty($article->view_content['basic_website_structure_keywords']))
        <meta name="keywords" content="{{ $article->view_content['basic_website_structure_keywords'] }}">
    @endif

    <!-- Additional SEO Meta Tags -->
    <meta name="author" content="Jakub Owsianka">
    <meta name="copyright" content="Oatllo">
    <meta name="language" content="{{ env('APP_LANG_HTML') }}">
    <meta name="revisit-after" content="7 days">
    <meta name="distribution" content="global">
    <meta name="rating" content="general">
    <meta name="coverage" content="worldwide">
    <meta name="target" content="all">
    <meta name="HandheldFriendly" content="true">
    <meta name="format-detection" content="telephone=no">

    <!-- Article specific meta tags -->
    <meta property="article:author" content="Jakub Owsianka">
    <meta property="article:published_time" content="{{ $article->created_at->toISOString() }}">
    <meta property="article:modified_time" content="{{ $article->updated_at->toISOString() }}">
    @if($article->category_id)
        <meta property="article:section" content="{{ $article->getCategoryName() }}">
    @endif
    @if(!$article->tags->isEmpty())
        @foreach($article->tags as $tag)
            <meta property="article:tag" content="{{ $tag->name }}">
        @endforeach
    @endif

    <!-- Open Graph Meta Tags -->
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">
    <meta property="og:title" content="{{ !empty($article->view_content['basic_website_structure_op_title']) ? $article->view_content['basic_website_structure_op_title'] : $article->name }}" />
    <meta property="og:description" content="{{ !empty($article->view_content['basic_website_structure_op_description']) ? $article->view_content['basic_website_structure_op_description'] : $article->short_description }}" />
    <meta property="og:url" content="{{ $article->getRoute() }}" />
    <meta property="og:image" content="{{ $article->image }}" />
    <meta property="og:type" content="article">
    <meta property="article:section" content="Programming">

    <meta property="article:published_time" content="{{ $article->created_at }}">
    <meta property="article:modified_time" content="{{ $article->updated_at }}">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $article->name }}">
    <meta name="twitter:description" content="{{ !empty($article->view_content['basic_website_structure_op_description']) ? $article->view_content['basic_website_structure_op_description'] : $article->short_description }}">
    <meta name="twitter:image" content="{{ $article->image }}">
    <meta name="twitter:site" content="@Oatllo">
    <meta name="twitter:creator" content="@Oatllo">

    <!-- Highlight.js for code syntax highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>

    <!-- Structured Data - JSON-LD -->
    @if(!empty($article->structure_data_google))
        <script type="application/ld+json">
            {!! $article->structure_data_google !!}
        </script>
    @endif

    <style>
        .prose h2 {
            font-size: 1.5rem; /* text-2xl */
            font-weight: 700;  /* font-bold */
            color: #fff;       /* text-white */
            margin-top: 3rem;  /* mt-12 */
            margin-bottom: 1rem; /* mb-4 */
            border-bottom: 1px solid #262626; /* border-neutral-800 */
            padding-bottom: 0.5rem; /* pb-2 */
        }

        .prose h3 {
            font-size: 1.25rem; /* text-xl */
            font-weight: 600;   /* font-semibold */
            color: #fff;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
        }

        .prose h4 {
            font-size: 1.125rem; /* text-lg */
            font-weight: 600;
            color: #e5e5e5; /* text-neutral-200 */
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .prose p {
            color: #d4d4d8; /* text-neutral-300 */
            line-height: 1.75; /* leading-relaxed */
            margin-bottom: 1rem;
        }

        .prose strong,
        .prose b {
            color: #fff;
            font-weight: 600;
        }

        .prose ul {
            list-style-type: disc;
            padding-left: 1.5rem;
            margin-bottom: 1rem;
            color: #d4d4d8;
        }
        .prose ol {
            list-style-type: decimal;
            padding-left: 1.5rem;
            margin-bottom: 1rem;
            color: #d4d4d8;
        }
        .prose li {
            margin-bottom: 0.5rem;
            line-height: 1.75;
        }
        .prose ul > li::marker,
        .prose ol > li::marker {
            color: #f43f5e; /* rose-400 */
        }

        .prose a {
            color: #f43f5e;
            text-decoration: underline;
        }
        .prose a:hover {
            color: #fb7185; /* rose-300 */
        }

        .prose blockquote {
            border-left: 4px solid #f43f5e;
            padding-left: 1rem;
            font-style: italic;
            color: #e5e5e5;
        }

        .prose code {
            background-color: #171717; /* neutral-900 */
            color: #fda4af; /* rose-400 */
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
        }

        .prose pre {
            background-color: #171717; /* neutral-900 */
            color: #f5f5f5;
            padding: 1rem;
            border-radius: 0.75rem;
            overflow-x: auto;
            font-size: 0.875rem;
        }
    </style>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/fontawesome.min.css" integrity="sha512-v8QQ0YQ3H4K6Ic3PJkym91KoeNT5S3PnDKvqnwqFD1oiqIl653crGZplPdU5KKtHjO0QKcQ2aUlQZYjHczkmGw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body class="bg-neutral-950 text-neutral-100 antialiased">
{!! \App\Services\HomeService::getTagManagerBODY() !!}
<!-- ===========================================================
  BREADCRUMB NAVIGATION
=========================================================== -->
<nav aria-label="Breadcrumb" class="px-4 pt-6 sm:px-6 lg:px-8">
    <ol class="flex flex-wrap gap-2 text-sm text-neutral-400" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <a href="{{ route('index') }}" itemprop="item" class="hover:text-rose-400"><span itemprop="name">Home</span></a>
            <meta itemprop="position" content="1" />
        </li>
        <li>&#8250;</li>
        <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <a href="{{ route('blog') }}" itemprop="item" class="hover:text-rose-400"><span itemprop="name">Blog</span></a>
            <meta itemprop="position" content="2" />
        </li>
        @if($article->category_id)
            <li>&#8250;</li>
            <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                <a href="{{ route('blog.list.category', ['slug' => $article->getCategoryName()]) }}" itemprop="item" class="hover:text-rose-400"><span itemprop="name">{{ $article->getCategoryName() }}</span></a>
                <meta itemprop="position" content="3" />
            </li>
        @endif
        <li>&#8250;</li>
        <li class="text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <span itemprop="name">{{ $article->name }}</span>
            <meta itemprop="item" content="{{ $article->getRoute() }}" />
            <meta itemprop="position" content="{{ $article->category_id ? '4' : '3' }}" />
        </li>
    </ol>
</nav>

<!-- ===========================================================
  ARTICLE HEADER (HERO)
=========================================================== -->
<header class="mx-auto mt-10 max-w-5xl px-4 sm:px-6 lg:px-8 text-center" itemscope itemtype="https://schema.org/Article">
    <meta itemprop="mainEntityOfPage" content="{{ $article->getRoute() }}" />
    <meta itemprop="author" content="Jakub Owsianka" />
    <meta itemprop="publisher" content="Oatllo - Jakub Owsianka" />
    <meta itemprop="datePublished" content="{{ $article->getPublishedDate()->format('Y-m-d') }}" />
    <meta itemprop="dateModified" content="{{ $article->updated_at->format('Y-m-d') }}" />
    <meta itemprop="headline" content="{{ $article->name }}" />
    <meta itemprop="description" content="{{ $article->short_description }}" />
    <meta itemprop="image" content="{{ $article->image }}" />
    <meta itemprop="articleSection" content="Programming" />
    <meta itemprop="articleBody" content="{{ strip_tags($article->short_description) }}" />

    <h1 class="text-4xl font-extrabold tracking-tight text-white md:text-5xl" itemprop="headline">
        {{ $article->name }}
    </h1>
    <p class="mx-auto mt-4 max-w-2xl text-lg text-neutral-300" itemprop="description">
        {{ $article->short_description }}
    </p>

    <!-- Meta: date, reading time, tags -->
    <div class="mt-6 flex flex-wrap justify-center gap-4 text-sm text-neutral-400">
        <div>
            <i class="fa-solid fa-calendar text-rose-400 mr-1"></i>
            <time datetime="{{ $article->getPublishedDate()->format('Y-m-d') }}" itemprop="datePublished">{{ $article->getPublishedDate()->format('M j, Y')  }}</time>
        </div>
        <div class="flex items-center gap-1">
            <i class="fa-solid fa-clock text-rose-400 mr-1"></i>
            <span> {{ $article->getTimeRead() }}&nbsp;min read</span>
        </div>
        @if(!$article->tags->isEmpty())
            <div>
                <i class="fa-solid fa-tags text-rose-400 mr-1"></i>
                @foreach($article->tags as $index => $tag)
                    <a href="{{ route('blogTag', ['tag' => Str::slug($tag->name)]) }}" class="hover:text-rose-400">{{ $tag->name }}</a>{{ $index < count($article->tags) - 1 ? ',' : '' }}
                @endforeach
            </div>
        @endif
    </div>

    <!-- Hero image -->
    <figure class="relative mx-auto mt-10 overflow-hidden rounded-2xl shadow-lg" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
        <img src="{{ $article->image }}" alt="{{ !empty($article->view_content['basic_website_structure_image_img_alt']) ? $article->view_content['basic_website_structure_image_img_alt'] : $article->name }}" class="h-72 w-full object-cover" loading="lazy" />
        <meta itemprop="url" content="{{ $article->image }}" />
        <meta itemprop="width" content="1200" />
        <meta itemprop="height" content="630" />
    </figure>
</header>

{{--<!-- ===========================================================--}}
{{--  TABLE OF CONTENTS (desktop sticky)--}}
{{--=========================================================== -->--}}
{{--@if(str_contains($article->contents[0]['content'] ?? '', '<h2>') || str_contains($article->contents[0]['content'] ?? '', '<h3>'))--}}
{{--<aside class="relative mx-auto mt-16 max-w-5xl px-4 sm:px-6 lg:px-8">--}}
{{--    <div class="lg:fixed lg:right-8 lg:top-40 lg:w-64 lg:pt-0">--}}
{{--        <h2 class="mb-3 text-lg font-semibold text-white">Spis treści</h2>--}}
{{--        <nav class="space-y-2 text-sm text-neutral-300" id="table-of-contents">--}}
{{--            <!-- Table of contents will be generated by JavaScript -->--}}
{{--        </nav>--}}
{{--    </div>--}}
{{--</aside>--}}
{{--@endif--}}

<!-- ===========================================================
  ARTICLE CONTENT
=========================================================== -->
<article class="prose prose-invert mx-auto mt-8 max-w-3xl px-4 sm:px-6 lg:px-8" itemprop="articleBody">
    @foreach($article->contents as $content)
        @if($content['type'] == 'text' && !empty($content['content']))
            {!! $content['content'] !!}
        @endif

        @if($content['type'] == 'image' && !empty($content['content']))
            <figure class="mt-16">
                <img class="rounded-xl bg-gray-50 object-cover" src="{{ $content['content'] }}" alt="{{ $content['alt'] ?? '' }}">
            </figure>
        @endif

    @endforeach
{{--    <h2 id="tip-1">1. Enable OPcache &amp; JIT</h2>--}}
{{--    <p>OPcache is the quickest win for most PHP apps...</p>--}}

{{--    <h2 id="tip-2">2. Switch to a PSR-7 Micro‑Framework</h2>--}}
{{--    <p>Lightweight routers like Slim or FastRoute...</p>--}}

{{--    <h2 id="tip-3">3. Offload Heavy Tasks with Swoole &amp; Queues</h2>--}}
{{--    <p>Asynchronous processing...</p>--}}

{{--    <!-- Callout -->--}}
{{--    <div class="my-8 rounded-xl border-l-4 border-rose-500 bg-neutral-900/60 p-4 shadow">--}}
{{--        <p class="m-0 flex items-start gap-2 text-sm text-neutral-200"><i class="fa-solid fa-lightbulb text-rose-400 mt-1"></i> <strong>Pro tip:</strong> Combine OPcache with *preloading* to warm up critical classes before the first request hits your server.</p>--}}
{{--    </div>--}}

{{--    <!-- Code block (Tailwind typography plugin styles) -->--}}
{{--    <pre><code class="language-php">--}}

{{--    </code></pre>--}}

{{--    <!-- ...more sections... -->--}}
</article>

<!-- ===========================================================
  SOCIAL SHARE + AUTHOR FOOTER
=========================================================== -->
<section class="mx-auto mt-16 max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center border-t border-neutral-800 pt-8">
            <!-- Share buttons -->
    <div class="flex gap-4">
        <a href="https://twitter.com/intent/tweet?url={{ urlencode($article->getRoute()) }}&text={{ urlencode($article->name) }}&via=Oatllo" target="_blank" rel="noopener" class="text-neutral-400 hover:text-rose-400" aria-label="Share on X/Twitter"><i class="fa-brands fa-x-twitter fa-lg"></i></a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($article->getRoute()) }}&title={{ urlencode($article->name) }}&summary={{ urlencode($article->short_description) }}" target="_blank" rel="noopener" class="text-neutral-400 hover:text-rose-400" aria-label="Share on LinkedIn"><i class="fa-brands fa-linkedin fa-lg"></i></a>
        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($article->getRoute()) }}" target="_blank" rel="noopener" class="text-neutral-400 hover:text-rose-400" aria-label="Share on Facebook"><i class="fa-brands fa-facebook fa-lg"></i></a>
    </div>
        <!-- Reading progress (JS required) placeholder -->
        <div id="reading-progress" class="h-2 w-40 rounded-full bg-neutral-800 overflow-hidden hidden sm:block">
            <div class="h-full bg-rose-500 transition-[width] duration-200" style="width: 0%;"></div>
        </div>
    </div>

    <!-- Author bio snippet -->
    <div class="mt-14 flex items-start gap-4 border-t border-neutral-800 pt-8" itemscope itemtype="https://schema.org/Person">
        <img src="{{ asset('/assets/images/owsianka_jakub.png') }}" alt="Jakub Owsianka" class="h-14 w-14 rounded-full object-cover" itemprop="image" />
        <div>
            <h3 class="text-lg font-semibold" itemprop="name">Jakub Owsianka</h3>
            <p class="text-neutral-400 text-sm" itemprop="description">Senior PHP developer &amp; open‑source enthusiast. I write about modern backend, DevOps and performance optimisation.</p>
            <div class="mt-2 flex gap-3">
                <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="text-neutral-400 hover:text-rose-400" aria-label="Connect on LinkedIn">
                    <i class="fa-brands fa-linkedin"></i>
                </a>
                <a href="https://www.instagram.com/oatllo_com/" target="_blank" rel="noopener" class="text-neutral-400 hover:text-rose-400" aria-label="Follow on Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ===========================================================
  RELATED ARTICLES
=========================================================== -->
@if(isset($relatedArticles) && $relatedArticles->count() > 0)
<section class="mx-auto mt-16 max-w-5xl px-4 sm:px-6 lg:px-8">
    <h2 class="text-2xl font-bold text-white mb-8">Powiązane artykuły</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($relatedArticles as $relatedArticle)
            <article class="bg-neutral-900 rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow">
                <a href="{{ $relatedArticle->getRoute() }}" class="block">
                    <img src="{{ $relatedArticle->image }}" alt="{{ $relatedArticle->name }}" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-white mb-2 line-clamp-2">{{ $relatedArticle->name }}</h3>
                        <p class="text-neutral-400 text-sm line-clamp-3">{{ $relatedArticle->short_description }}</p>
                        <div class="mt-3 flex items-center text-xs text-neutral-500">
                            <time datetime="{{ $relatedArticle->getPublishedDate()->format('Y-m-d') }}">{{ $relatedArticle->getPublishedDate()->format('M j, Y') }}</time>
                            <span class="mx-2">•</span>
                            <span>{{ $relatedArticle->getTimeRead() }} min read</span>
                        </div>
                    </div>
                </a>
            </article>
        @endforeach
    </div>
</section>
@endif

<!-- ===========================================================
  NEXT / PREVIOUS NAVIGATION
=========================================================== -->
<nav class="mx-auto mt-24 flex max-w-5xl justify-between px-4 sm:px-6 lg:px-8 mb-10" aria-label="Article navigation">
    @if($previousArticle)
        <a href="{{ $previousArticle->getRoute() }}" class="flex max-w-xs items-center gap-3 text-neutral-400 hover:text-rose-400">
            <i class="fa-solid fa-angle-left"></i>
            <span class="truncate">{{ $previousArticle->name }}</span>
        </a>
    @else
        <div></div>
    @endif

    @if($nextArticle)
        <a href="{{ $nextArticle->getRoute() }}" class="flex max-w-xs items-center gap-3 text-neutral-400 hover:text-rose-400">
            <span class="truncate">{{ $nextArticle->name }}</span>
            <i class="fa-solid fa-angle-right"></i>
        </a>
    @else
        <div></div>
    @endif
</nav>

{{--<!-- ===========================================================--}}
{{--  COMMENTS (placeholder)--}}
{{--=========================================================== -->--}}
{{--<section id="comments" class="mx-auto mt-24 max-w-3xl px-4 sm:px-6 lg:px-8">--}}
{{--    <h2 class="mb-6 text-2xl font-bold text-white">Join the conversation</h2>--}}
{{--    <!-- Integrate Disqus, Giscus, or custom backend here -->--}}
{{--    <div class="rounded-xl border border-neutral-800 bg-neutral-900/60 p-8 text-center text-neutral-400">--}}
{{--        <p>Comments are powered by <strong>Giscus</strong>. Enable JavaScript to load them.</p>--}}
{{--    </div>--}}
{{--</section>--}}

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD (Article)
=========================================================== -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "{{ $article->name }}",
  "description": "{{ $article->short_description }}",
  "image": "{{ $article->image }}",
  "author": {
    "@type": "Person",
    "name": "Jakub Owsianka",
    "url": "https://oatllo.com/"
  },
  "datePublished": "{{ $article->getPublishedDate()->format('Y-m-d') }}",
  "dateModified": "{{ $article->updated_at->format('Y-m-d') }}",
  "publisher": {
    "@type": "Organization",
    "name": "Oatllo",
    "logo": {
      "@type": "ImageObject",
      "url": "https://oatllo.com/assets/images/logo-512.jpg"
    }
  },
  "mainEntityOfPage": "{{ $article->getRoute() }}",
  "articleSection": "Programming",
  "keywords": "{{ !empty($article->view_content['basic_website_structure_keywords']) ? $article->view_content['basic_website_structure_keywords'] : 'programming, PHP, development' }}"
}
</script>

<!-- ===========================================================
  OPTIONAL: Reading progress bar (simple vanilla JS)
=========================================================== -->
<script>
    const progress = document.querySelector('#reading-progress div');
    const article = document.querySelector('article');
    function updateProgress() {
        if (!progress || !article) return;
        const rect = article.getBoundingClientRect();
        const total = rect.height - window.innerHeight;
        const current = -rect.top;
        const percent = Math.max(0, Math.min(100, (current / total) * 100));
        progress.style.width = percent + '%';
    }
    document.addEventListener('scroll', updateProgress, { passive: true });
</script>

<!-- Highlight.js initialization -->
<script>hljs.highlightAll();</script>
<script src="{{ asset('/assets/js/script.js') }}"></script>
</body>
</html>
