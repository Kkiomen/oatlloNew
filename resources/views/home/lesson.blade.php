@php
    $currentImage = empty($course->image) ? 'storage/uploads/empty_image.jpg' : $course->image;
    $pattern = "/asset\('(.+?)'\)/";
    if (preg_match($pattern, $currentImage, $matches)) {
        $currentImage = $matches[1];
    }
    $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);
@endphp

<!DOCTYPE html>
<html lang="{{ env('APP_LANG_HTML') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ $article->seo_title ?: $article->title }}</title>
    <meta name="description" content="{{ $article->seo_description ?: 'Lekcja kursu' }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="author" content="Jakub Owsianka">
    <meta name="creator" content="Jakub Owsianka">
    <meta name="publisher" content="Oatllo">
    <meta name="language" content="{{ env('APP_LANG_HTML') }}">
    <meta name="revisit-after" content="7 days">
    <meta name="rating" content="general">
    <meta name="distribution" content="global">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">
    <link rel="canonical" href="{{ $article->getRoute() }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}, {{ $course->name }}, {{ $category->title }}, programming, PHP, development">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $article->seo_title ?: $article->title }}">
    <meta property="og:description" content="{{ $article->seo_description ?: 'Lekcja kursu ' . $course->name . ' - ' . $category->title }}">
    <meta property="og:image" content="{{ $currentImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $article->seo_title ?: $article->title }}">
    <meta property="og:url" content="{{ $article->getRoute() }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">
    <meta property="og:author" content="Jakub Owsianka">
    <meta property="og:section" content="{{ $category->title }}">
    <meta property="og:tag" content="{{ $course->name }}, {{ $category->title }}, programming, PHP, development">
    <meta property="article:published_time" content="{{ $article->created_at->toISOString() }}">
    <meta property="article:modified_time" content="{{ $article->updated_at->toISOString() }}">
    <meta property="article:author" content="https://www.linkedin.com/in/jakub-owsianka-446bb5213/">
    <meta property="article:section" content="{{ $category->title }}">
    <meta property="article:tag" content="{{ $course->name }}, {{ $category->title }}, programming, PHP, development">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $article->seo_title ?: $article->title }}">
    <meta name="twitter:description" content="{{ $article->seo_description ?: 'Lekcja kursu ' . $course->name . ' - ' . $category->title }}">
    <meta name="twitter:image" content="{{ $currentImage }}">
    <meta name="twitter:image:alt" content="{{ $article->seo_title ?: $article->title }}">
    <meta name="twitter:site" content="@Oatllo">
    <meta name="twitter:creator" content="@Oatllo">

    <link rel="stylesheet" href="{{ asset('/assets/css/article-style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/fontawesome.min.css" integrity="sha512-v8QQ0YQ3H4K6Ic3PJkym91KoeNT5S3PnDKvqnwqFD1oiqIl653crGZplPdU5KKtHjO0QKcQ2aUlQZYjHczkmGw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>


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
            font-style: italic;
            color: #e5e5e5;
        }

        .prose pre {
            margin-bottom: 1rem;
        }
        .prose code {
            background-color: #171717; /* neutral-900 */
            color: #fda4af; /* rose-400 */
            border-radius: 0.25rem;
        }

        .prose pre {
            background-color: #171717; /* neutral-900 */
            color: #f5f5f5;
            border-radius: 0.75rem;
            overflow-x: auto;
            font-size: 0.875rem;
        }

        /* Custom scrollbar styles */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #262626; /* neutral-800 */
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #525252; /* neutral-600 */
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #737373; /* neutral-500 */
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:active {
            background: #a3a3a3; /* neutral-400 */
        }

        /* Firefox scrollbar */
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #525252 #262626;
        }
    </style>

    <!-- Highlight.js for code syntax highlighting -->

    <script src="{{ asset('/assets/libs/highlight/highlight.min.js') }}"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlightjs-themes@1.0.0/github.css">
    <link rel="stylesheet" href="{{ asset('/assets/css/article-style.css') }}">

    <script src="{{ asset('/assets/libs/highlight/php.min.js') }}"></script>


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
                <a href="{{ route('index') }}" class="text-sm/6 font-semibold text-white hover:text-green-400 transition-colors duration-200">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="text-sm/6 font-semibold text-white hover:text-green-400 transition-colors duration-200">Blog</a>
                <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="text-sm/6 font-semibold text-white hover:text-green-400 transition-colors duration-200">{{ __('basic.courses') }}</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="text-sm/6 font-semibold text-white hover:text-green-400 transition-colors duration-200">
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
  BREADCRUMB NAVIGATION
=========================================================== -->
<nav aria-label="Breadcrumb" class="px-4 pt-24 sm:px-6 lg:px-8">
    <!-- Desktop breadcrumb (full) -->
    <ol class="hidden md:flex flex-wrap gap-2 text-sm text-neutral-400" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <a href="{{ route('index') }}" itemprop="item" class="hover:text-green-400"><span itemprop="name">Home</span></a>
            <meta itemprop="position" content="1" />
        </li>
        <li>&#8250;</li>
        <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <a href="{{ \App\Services\HomeService::getRouteCourses() }}" itemprop="item" class="hover:text-green-400"><span itemprop="name">{{ __('basic.courses') }}</span></a>
            <meta itemprop="position" content="2" />
        </li>
        <li>&#8250;</li>
        <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <a href="{{ $course->getRoute() }}" itemprop="item" class="hover:text-green-400"><span itemprop="name">{{ $course->name }}</span></a>
            <meta itemprop="position" content="3" />
        </li>
        <li>&#8250;</li>
        <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <a href="{{ $category->getRoute() }}" itemprop="item" class="hover:text-green-400"><span itemprop="name">{{ $category->title }}</span></a>
            <meta itemprop="position" content="4" />
        </li>
        <li>&#8250;</li>
        <li class="text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <span itemprop="name">{{ $article->title }}</span>
            <meta itemprop="item" content="{{ $article->getRoute() }}" />
            <meta itemprop="position" content="5" />
        </li>
    </ol>

    <!-- Mobile breadcrumb (simplified) -->
    <ol class="md:hidden flex flex-wrap gap-2 text-sm text-neutral-400" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <a href="{{ $course->getRoute() }}" itemprop="item" class="hover:text-green-400"><span itemprop="name">{{ $course->name }}</span></a>
            <meta itemprop="position" content="1" />
        </li>
        <li>&#8250;</li>
        <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <a href="{{ $category->getRoute() }}" itemprop="item" class="hover:text-green-400"><span itemprop="name">{{ $category->title }}</span></a>
            <meta itemprop="position" content="2" />
        </li>
        <li>&#8250;</li>
        <li class="text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <span itemprop="name" class="truncate max-w-[150px] block">{{ $article->title }}</span>
            <meta itemprop="item" content="{{ $article->getRoute() }}" />
            <meta itemprop="position" content="3" />
        </li>
    </ol>
</nav>

<!-- ===========================================================
  LESSON HEADER (HERO)
=========================================================== -->
<header class="mx-auto mt-10 max-w-5xl px-4 sm:px-6 lg:px-8" itemscope itemtype="https://schema.org/Article">
    <meta itemprop="mainEntityOfPage" content="{{ $article->getRoute() }}" />
    <meta itemprop="author" content="Jakub Owsianka" />
    <meta itemprop="publisher" content="Oatllo - Jakub Owsianka" />
    <meta itemprop="headline" content="{{ $article->title }}" />
    <meta itemprop="description" content="{{ $article->seo_description ?: 'Lekcja kursu' }}" />
    <meta itemprop="image" content="{{ $currentImage }}" />
    <meta itemprop="articleSection" content="{{ $category->title }}" />

    <h1 class="text-4xl font-extrabold tracking-tight text-white md:text-5xl" itemprop="headline">
        {!! $article->title !!}
    </h1>
    <p class="mx-auto mt-4 max-w-2xl text-lg text-neutral-300" itemprop="description">
        {{ $article->seo_description ?: 'Lekcja kursu' }}
    </p>

    <!-- Course and category info -->
    <div class="mt-6 flex flex-wrap justify-center gap-4 text-sm text-neutral-400">
        <div class="flex items-center">
            <i class="fa-solid fa-graduation-cap text-green-400 mr-2"></i>
            <span>{{ $course->name }}</span>
        </div>
        <div class="flex items-center">
            <i class="fa-solid fa-folder text-green-400 mr-2"></i>
            <span>{{ $category->title }}</span>
        </div>
        <div class="flex items-center">
            <i class="fa-solid fa-play-circle text-green-400 mr-2"></i>
            <span>Lesson</span>
        </div>
    </div>
</header>

<!-- ===========================================================
  LESSON CONTENT
=========================================================== -->
<main class="mx-auto mt-16 max-w-7xl px-0 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Main content (3/4 width) -->
        <article class="lg:col-span-3 w-full" itemprop="articleBody">
            <div class="bg-neutral-900/50 rounded-none sm:rounded-2xl p-4 sm:p-8 border-0 sm:border sm:border-neutral-800">
                <div class="prose prose-invert max-w-none text-neutral-300 leading-relaxed">
                    <style>
                        .prose-invert p {
                            color: #d4d4d8 !important;
                            margin-bottom: 1rem;
                            line-height: 1.75;
                        }
                        .prose-invert strong,
                        .prose-invert b {
                            color: #ffffff !important;
                            font-weight: 600;
                        }
                        .prose-invert h1,
                        .prose-invert h2,
                        .prose-invert h3,
                        .prose-invert h4,
                        .prose-invert h5,
                        .prose-invert h6 {
                            color: #ffffff !important;
                            margin-top: 1.5rem;
                            margin-bottom: 0.75rem;
                        }
                        .prose-invert ul,
                        .prose-invert ol {
                            color: #d4d4d8 !important;
                            margin-bottom: 1rem;
                        }
                        .prose-invert li {
                            margin-bottom: 0.5rem;
                        }
                        .prose-invert a {
                            color: #4ade80 !important;
                            text-decoration: underline;
                        }
                        .prose-invert a:hover {
                            color: #22c55e !important;
                        }
                        .prose-invert code {
                            background-color: #171717 !important;
                            color: #4ade80 !important;
                            border-radius: 0.25rem;
                            padding: 0.125rem 0.25rem;
                        }
                        .prose-invert pre {
                            background-color: #171717 !important;
                            color: #f5f5f5 !important;
                            border-radius: 0.75rem;
                            overflow-x: auto;
                            font-size: 0.875rem;
                            padding: 1rem;
                            margin-bottom: 1rem;
                        }
                    </style>

                    @if(!empty($article->content_html))
                        {!! $article->content_html !!}
                    @else
                        @foreach($article->contents as $content)
                            @if($content['type'] == 'text' && !empty($content['content']))
                                {!! $content['content'] !!}
                            @endif

                            @if($content['type'] == 'image' && !empty($content['content']))
                                <figure class="mt-8 mb-8">
                                    <img class="rounded-xl bg-neutral-800 object-cover w-full" src="{{ $content['content'] }}" alt="{{ $content['alt'] ?? '' }}" loading="lazy">
                                </figure>
                            @endif
                        @endforeach
                    @endif


                </div>
            </div>
        </article>

        <!-- Sidebar (1/4 width) -->
        <aside class="lg:col-span-1 w-full mt-8 lg:mt-0">
            <div class="bg-neutral-900/50 rounded-none sm:rounded-2xl p-4 sm:p-6 border-0 sm:border sm:border-neutral-800 sticky top-8 lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto custom-scrollbar">
                <h3 class="text-lg font-semibold text-white mb-6 flex items-center">
                    <i class="fa-solid fa-list text-green-400 mr-2"></i>
                    Course Contents
                </h3>

                <div class="space-y-6">
                    @foreach($course->categories as $cat)
                        <div>
                            <h4 class="font-semibold text-white mb-3">
                                <a href="{{ $cat->getRoute() }}" class="hover:text-green-400 transition-colors duration-200">
                                    {{ $cat->title }}
                                </a>
                            </h4>
                            <ul class="space-y-2">
                                @foreach($cat->lessons as $lesson)
                                    <li>
                                        <a href="{{ $lesson->getRoute() }}" class="text-sm text-neutral-400 hover:text-green-400 transition-colors duration-200 flex items-center @if($article->title === $lesson->title) text-green-400 font-semibold @endif">
                                            <i class="fa-solid fa-play-circle text-xs mr-2 @if($article->title === $lesson->title) text-green-400 @else text-neutral-500 @endif"></i>
                                            {{ $lesson->title }}
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
  LESSON NAVIGATION
=========================================================== -->
<nav class="mx-auto mt-16 max-w-5xl px-4 sm:px-6 lg:px-8 mb-10" aria-label="Lesson navigation">
    <div class="flex flex-col sm:flex-row justify-between gap-4">
        @if(!empty($lessonSkip['previous']))
            <a href="{{ $lessonSkip['previous']['route'] }}" class="group flex-1 sm:flex-none sm:max-w-xs text-neutral-400 hover:text-green-400 transition-colors duration-200">
                <div class="flex items-center gap-3 p-4 rounded-lg bg-neutral-900 hover:bg-neutral-800 transition-colors duration-200">
                    <i class="fa-solid fa-angle-left group-hover:-translate-x-1 transition-transform duration-200 flex-shrink-0"></i>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs text-neutral-500 uppercase tracking-wide">{{ __('basic.go_to_back_lesson') }}</div>
                        <div class="truncate font-medium text-sm sm:text-base">{{ $lessonSkip['previous']['name'] }}</div>
                    </div>
                </div>
            </a>
        @else
            <div class="flex-1 sm:flex-none"></div>
        @endif

        @if(!empty($lessonSkip['next']))
            <a href="{{ $lessonSkip['next']['route'] }}" class="group flex-1 sm:flex-none sm:max-w-xs text-neutral-400 hover:text-green-400 transition-colors duration-200">
                <div class="flex items-center gap-3 p-4 rounded-lg bg-neutral-900 hover:bg-neutral-800 transition-colors duration-200">
                    <div class="min-w-0 flex-1 text-right">
                        <div class="text-xs text-neutral-500 uppercase tracking-wide">{{ __('basic.go_to_next_lesson') }}</div>
                        <div class="truncate font-medium text-sm sm:text-base">{{ $lessonSkip['next']['name'] }}</div>
                    </div>
                    <i class="fa-solid fa-angle-right group-hover:translate-x-1 transition-transform duration-200 flex-shrink-0"></i>
                </div>
            </a>
        @else
            <div class="flex-1 sm:flex-none"></div>
        @endif
    </div>
</nav>

<!-- Back to Course -->
<div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 mb-10 text-center">
    <a href="{{ $course->getRoute() }}" class="inline-flex items-center gap-2 bg-neutral-800 hover:bg-neutral-700 text-white px-6 py-3 rounded-lg transition-colors duration-200">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Back to {{ $course->title_list }}</span>
    </a>
</div>

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD (Article + BreadcrumbList)
=========================================================== -->
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Article",
      "headline": "{{ addslashes($article->seo_title ?: $article->title) }}",
      "description": "{{ addslashes($article->seo_description ?: 'Lekcja kursu ' . $course->name . ' - ' . $category->title) }}",
      "image": {
        "@type": "ImageObject",
        "url": "{{ $currentImage }}",
        "width": 1200,
        "height": 630,
        "alt": "{{ addslashes($article->seo_title ?: $article->title) }}"
      },
      "author": {
        "@type": "Person",
        "name": "Jakub Owsianka",
        "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/",
        "sameAs": [
          "https://www.linkedin.com/in/jakub-owsianka-446bb5213/"
        ]
      },
      "datePublished": "{{ $article->created_at->toISOString() }}",
      "dateModified": "{{ $article->updated_at->toISOString() }}",
      "publisher": {
        "@type": "Organization",
        "name": "Oatllo",
        "url": "{{ route('index') }}",
        "logo": {
          "@type": "ImageObject",
          "url": "{{ asset('assets/images/favicon.ico') }}",
          "width": 32,
          "height": 32
        },
        "sameAs": [
          "https://www.linkedin.com/in/jakub-owsianka-446bb5213/"
        ]
      },
      "mainEntityOfPage": {
        "@type": "WebPage",
        "@id": "{{ $article->getRoute() }}"
      },
      "articleSection": "{{ addslashes($category->title) }}",
      "keywords": "{{ addslashes($course->name) }}, {{ addslashes($category->title) }}, programming, PHP, development, {{ __('basic.meta_keywords') }}",
      "inLanguage": "{{ env('APP_LANG_HTML') }}",
      "isPartOf": {
        "@type": "Course",
        "name": "{{ addslashes($course->name) }}",
        "description": "{{ addslashes($course->description ?? '') }}",
        "url": "{{ $course->getRoute() }}"
      },
      "wordCount": {{ str_word_count(strip_tags($article->content_html ?? '')) }},
      "timeRequired": "PT15M"
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
          "name": "{{ __('basic.courses') }}",
          "item": "{{ \App\Services\HomeService::getRouteCourses() }}"
        },
        {
          "@type": "ListItem",
          "position": 3,
          "name": "{{ addslashes($course->title_list) }}",
          "item": "{{ $course->getRoute() }}"
        },
        {
          "@type": "ListItem",
          "position": 4,
          "name": "{{ addslashes($category->title) }}",
          "item": "{{ $category->getRoute() }}"
        },
        {
          "@type": "ListItem",
          "position": 5,
          "name": "{{ addslashes($article->title) }}",
          "item": "{{ $article->getRoute() }}"
        }
      ]
    }
</script>

<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Oatllo",
      "url": "{{ route('index') }}",
      "logo": {
        "@type": "ImageObject",
        "url": "{{ asset('assets/images/favicon.ico') }}",
        "width": 32,
        "height": 32
      },
      "sameAs": [
        "https://www.linkedin.com/in/jakub-owsianka-446bb5213/"
      ],
      "founder": {
        "@type": "Person",
        "name": "Jakub Owsianka",
        "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/"
      },
      "description": "Platforma edukacyjna z kursami programowania i rozwoju technologicznego"
    }
</script>

<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Course",
      "name": "{{ addslashes($course->name) }}",
      "description": "{{ addslashes($course->description ?? 'Kurs programowania') }}",
      "url": "{{ $course->getRoute() }}",
      "provider": {
        "@type": "Organization",
        "name": "Oatllo",
        "url": "{{ route('index') }}"
      },
      "instructor": {
        "@type": "Person",
        "name": "Jakub Owsianka",
        "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/"
      },
      "coursePrerequisites": "Podstawowa znajomość programowania",
      "educationalLevel": "Intermediate",
      "inLanguage": "{{ env('APP_LANG_HTML') }}",
      "hasCourseInstance": {
        "@type": "CourseInstance",
        "courseMode": "online",
        "inLanguage": "{{ env('APP_LANG_HTML') }}"
      }
    }
</script>

<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Oatllo",
      "url": "{{ route('index') }}",
      "description": "Platforma edukacyjna z kursami programowania i rozwoju technologicznego",
      "publisher": {
        "@type": "Organization",
        "name": "Oatllo",
        "logo": {
          "@type": "ImageObject",
          "url": "{{ asset('assets/images/favicon.ico') }}"
        }
      },
      "potentialAction": {
        "@type": "SearchAction",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "{{ route('index') }}?search={search_term_string}"
        },
        "query-input": "required name=search_term_string"
      }
    }
</script>

<script>hljs.highlightAll();</script>
<script src="{{ asset('/assets/js/script.js') }}"></script>
</body>
</html>
