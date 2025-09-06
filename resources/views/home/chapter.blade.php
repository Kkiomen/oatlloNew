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
    <title>{{ $courseCategory->title_seo ?: $courseCategory->title }}</title>
    <meta name="description" content="{{ $courseCategory->description_seo ?: $courseCategory->description }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="index, follow">
    <meta name="author" content="Oatllo - Jakub Owsianka">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">
    <link rel="canonical" href="{{ $courseCategory->getRoute() }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $courseCategory->title_seo ?: $courseCategory->title }}">
    <meta property="og:description" content="{{ $courseCategory->description_seo ?: $courseCategory->description }}">
    <meta property="og:image" content="{{ $currentImage }}">
    <meta property="og:url" content="{{ $courseCategory->getRoute() }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $courseCategory->title_seo ?: $courseCategory->title }}">
    <meta name="twitter:description" content="{{ $courseCategory->description_seo ?: $courseCategory->description }}">
    <meta name="twitter:image" content="{{ $currentImage }}">
    <meta name="twitter:site" content="@Oatllo">
    <meta name="twitter:creator" content="@Oatllo">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/fontawesome.min.css" integrity="sha512-v8QQ0YQ3H4K6Ic3PJkym91KoeNT5S3PnDKvqnwqFD1oiqIl653crGZplPdU5KKtHjO0QKcQ2aUlQZYjHczkmGw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- Highlight.js for code syntax highlighting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlightjs-themes@1.0.0/github.css">

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
            color: #22c55e; /* green-400 - dostosowane do kolorów chapter.blade.php */
        }

        .prose a {
            color: #22c55e; /* green-400 - dostosowane do kolorów chapter.blade.php */
            text-decoration: underline;
        }
        .prose a:hover {
            color: #4ade80; /* green-300 - dostosowane do kolorów chapter.blade.php */
        }

        .prose blockquote {
            border-left: 4px solid #22c55e; /* green-400 - dostosowane do kolorów chapter.blade.php */
            font-style: italic;
            color: #e5e5e5;
        }

        .prose pre {
            margin-bottom: 1rem;
        }
        .prose code {
            background-color: #171717; /* neutral-900 */
            color: #86efac; /* green-400 - dostosowane do kolorów chapter.blade.php */
            border-radius: 0.25rem;
        }

        .prose pre {
            background-color: #171717; /* neutral-900 */
            color: #f5f5f5;
            border-radius: 0.75rem;
            overflow-x: auto;
            font-size: 0.875rem;
        }
    </style>
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
    <ol class="flex flex-wrap gap-2 text-sm text-neutral-400" itemscope itemtype="https://schema.org/BreadcrumbList">
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
            <a href="{{ $course->getRoute() }}" itemprop="item" class="hover:text-green-400"><span itemprop="name">{{ $course->title_list }}</span></a>
            <meta itemprop="position" content="3" />
        </li>
        <li>&#8250;</li>
        <li class="text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <span itemprop="name">{{ $courseCategory->title }}</span>
            <meta itemprop="item" content="{{ $courseCategory->getRoute() }}" />
            <meta itemprop="position" content="4" />
        </li>
    </ol>
</nav>

<!-- ===========================================================
  CHAPTER HEADER (HERO)
=========================================================== -->
<header class="mx-auto mt-10 max-w-5xl px-4 sm:px-6 lg:px-8 text-center">
    <h1 class="text-4xl font-extrabold tracking-tight text-white md:text-5xl">
        {{ $courseCategory->title }}
    </h1>
    <p class="mx-auto mt-4 max-w-2xl text-lg text-neutral-300">
        {{ $courseCategory->description }}
    </p>

    <!-- Course info -->
    <div class="mt-6 flex flex-wrap justify-center gap-4 text-sm text-neutral-400">
        <div class="flex items-center">
            <i class="fa-solid fa-graduation-cap text-green-400 mr-2"></i>
            <span>{{ $course->title_list }}</span>
        </div>
        <div class="flex items-center">
            <i class="fa-solid fa-folder text-green-400 mr-2"></i>
            <span>{{ __('basic.chapter') }}</span>
        </div>
        <div class="flex items-center">
            <i class="fa-solid fa-play-circle text-green-400 mr-2"></i>
            <span>{{ $courseCategory->lessons->count() }} Lessons</span>
        </div>
    </div>
</header>

<!-- ===========================================================
  CHAPTER CONTENT
=========================================================== -->
<main class="mx-auto mt-16 max-w-5xl px-4 sm:px-6 lg:px-8">
    <!-- Chapter Description -->
    @if(!empty($courseCategory->description_content))
    <section class="mb-16">
        <div class="bg-neutral-900/50 rounded-2xl p-8 border border-neutral-800">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                <i class="fa-solid fa-info-circle text-green-400 mr-3"></i>
                About This Chapter
            </h2>
            <div class="prose prose-invert max-w-none text-neutral-300 leading-relaxed">
                {!! $courseCategory->description_content !!}
            </div>
        </div>
    </section>
    @endif

    <!-- Chapter Lessons -->
    <section class="mb-16">
        <h2 class="text-2xl font-bold text-white mb-8 flex items-center">
            <i class="fa-solid fa-list text-green-400 mr-3"></i>
            Chapter Lessons
        </h2>

        <div class="bg-neutral-900/50 rounded-2xl p-8 border border-neutral-800">
            <div class="space-y-4">
                @foreach($courseCategory->lessons as $lesson)
                    <div class="bg-neutral-800/50 rounded-lg p-6 hover:bg-neutral-800 transition-colors duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-white mb-2">
                                    <a href="{{ $lesson->getRoute() }}" class="hover:text-green-400 transition-colors duration-200 flex items-center">
                                        <i class="fa-solid fa-play-circle text-green-400 mr-3 text-lg"></i>
                                        {{ $lesson->title }}
                                    </a>
                                </h3>
                                @if(!empty($lesson->seo_description))
                                    <p class="text-neutral-400 text-sm">{{ $lesson->seo_description }}</p>
                                @endif
                            </div>
                            <div class="flex items-center text-sm text-neutral-500">
                                <span class="bg-neutral-700 px-2 py-1 rounded text-xs">Lesson {{ $loop->iteration }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Navigation -->
    <section class="text-center mb-16">
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ $course->getRoute() }}" class="inline-flex items-center justify-center rounded-lg bg-neutral-800 hover:bg-neutral-700 px-6 py-3 text-white font-semibold transition-colors duration-200">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Back to Course
            </a>
            @if($courseCategory->lessons->count() > 0)
                <a href="{{ $courseCategory->lessons->first()->getRoute() }}" class="inline-flex items-center justify-center rounded-lg bg-green-500 hover:bg-green-600 px-6 py-3 text-white font-semibold transition-colors duration-200">
                    <i class="fa-solid fa-play mr-2"></i>
                    Start Learning
                </a>
            @endif
        </div>
    </section>
</main>

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD
=========================================================== -->
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Course",
      "name": {!! json_encode($courseCategory->title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
      "description": {!! json_encode($courseCategory->description, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
      "url": "{{ $courseCategory->getRoute() }}",
      "provider": {
        "@type": "Organization",
        "name": "Oatllo",
        "url": "{{ route('index') }}"
      },
      "educationalLevel": "beginner to advanced",
      "image": "{{ $currentImage }}",
      "inLanguage": "{{ env('APP_LANG_HTML') }}",
      "isPartOf": {
        "@type": "Course",
        "name": {!! json_encode($course->title_list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
        "url": "{{ $course->getRoute() }}"
      },
      "hasCourseInstance": {
        "@type": "CourseInstance",
        "courseMode": "online",
        "inLanguage": "{{ env('APP_LANG_HTML') }}",
        "courseWorkload": "PT2H",
        "instructor": {
          "@type": "Person",
          "name": "Jakub Owsianka",
          "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/"
        }
      },
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD",
        "availability": "https://schema.org/InStock",
        "url": "{{ $courseCategory->getRoute() }}"
      },
      "coursePrerequisites": "Basic programming knowledge"
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
          "name": {!! json_encode($course->title_list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
          "item": "{{ $course->getRoute() }}"
        },
        {
          "@type": "ListItem",
          "position": 4,
          "name": {!! json_encode($courseCategory->title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
          "item": "{{ $courseCategory->getRoute() }}"
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

<script src="{{ asset('/assets/js/script.js') }}"></script>

<!-- Highlight.js initialization -->
<script>hljs.highlightAll();</script>
</body>
</html>
