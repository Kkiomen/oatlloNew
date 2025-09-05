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
    <title>{{ $course->title_seo }}</title>
    <meta name="description" content="{{ $course->description_seo }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="index, follow">
    <meta name="author" content="Oatllo - Jakub Owsianka">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">
    <link rel="canonical" href="{{ $urlToCourse }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <!-- Hreflang tags for language versions -->
    <link rel="alternate" hreflang="pl" href="{{ $urlToCourse }}">
    <link rel="alternate" hreflang="en" href="{{ str_replace('/pl/', '/en/', $urlToCourse) }}">
    <link rel="alternate" hreflang="x-default" href="{{ str_replace('/pl/', '/en/', $urlToCourse) }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $course->title_seo }}">
    <meta property="og:description" content="{{ $course->description_seo }}">
    <meta property="og:image" content="{{ $currentImage }}">
    <meta property="og:url" content="{{ $urlToCourse }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $course->title_seo }}">
    <meta name="twitter:description" content="{{ $course->description_seo }}">
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
        <li class="text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <span itemprop="name">{{ $course->title_list }}</span>
            <meta itemprop="item" content="{{ $urlToCourse }}" />
            <meta itemprop="position" content="3" />
        </li>
    </ol>
</nav>

<!-- ===========================================================
  COURSE HEADER (HERO)
=========================================================== -->
<header class="mx-auto mt-10 max-w-5xl px-4 sm:px-6 lg:px-8 text-center" itemscope itemtype="https://schema.org/Course">
    <meta itemprop="name" content="{{ $course->title_seo }}" />
    <meta itemprop="description" content="{{ $course->description_seo }}" />
    <meta itemprop="provider" content="Oatllo" />
    <meta itemprop="url" content="{{ $urlToCourse }}" />
    <meta itemprop="courseMode" content="online" />
    <meta itemprop="educationalLevel" content="beginner to advanced" />

    <h1 class="text-4xl font-extrabold tracking-tight text-white md:text-5xl" itemprop="name">
        {!! $course->title_full !!}
    </h1>
    <p class="mx-auto mt-4 max-w-2xl text-lg text-neutral-300" itemprop="description">
        {!! $course->description_full !!}
    </p>

    <!-- Course meta info -->
    <div class="mt-6 flex flex-wrap justify-center gap-4 text-sm text-neutral-400">
        <div class="flex items-center">
            <i class="fa-solid fa-clock text-green-400 mr-2"></i>
            <span>Self-paced learning</span>
        </div>
        <div class="flex items-center">
            <i class="fa-solid fa-users text-green-400 mr-2"></i>
            <span>Beginner to Advanced</span>
        </div>
    </div>

    <!-- Hero image -->
    <figure class="relative mx-auto mt-10 overflow-hidden rounded-2xl shadow-lg" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
        <img src="{{ $currentImage }}" alt="{{ $course->title_seo }}" class="h-72 w-full object-cover" loading="lazy" />
        <meta itemprop="url" content="{{ $currentImage }}" />
        <meta itemprop="width" content="1200" />
        <meta itemprop="height" content="630" />
    </figure>

    <!-- CTA Button -->
    <div class="mt-8">
        <a href="{{ $firstLessonRoute }}" class="inline-flex items-center justify-center rounded-lg bg-green-500 px-8 py-4 text-white font-semibold transition-colors hover:bg-green-600 shadow-lg hover:shadow-xl">
            <i class="fa-solid fa-play mr-2"></i>
            {{ __('basic.go_to_course') }}
        </a>
    </div>
</header>

<!-- ===========================================================
  COURSE CONTENT
=========================================================== -->
<main class="mx-auto mt-16 max-w-5xl px-4 sm:px-6 lg:px-8">
    <!-- Course Description -->
    <section class="mb-16">
        <div class="bg-neutral-900/50 rounded-2xl p-8 border border-neutral-800">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                <i class="fa-solid fa-info-circle text-green-400 mr-3"></i>
                About This Course
            </h2>
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
                </style>
                {!! $course->content_description_offers !!}
            </div>
        </div>
    </section>

    <!-- Course Curriculum -->
    <section class="mb-16">
        <h2 class="text-2xl font-bold text-white mb-8 flex items-center">
            <i class="fa-solid fa-list text-green-400 mr-3"></i>
            Course Curriculum
        </h2>

        <div class="bg-neutral-900/50 rounded-2xl p-8 border border-neutral-800">
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @foreach($course->categories as $category)
                        <li>
                            <div class="relative pb-8">
                                @if(!$loop->last)
                                    <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-neutral-700" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex items-start space-x-3">
                                    <div>
                                        <div class="relative px-1">
                                            <div class="flex size-8 items-center justify-center rounded-full bg-green-500 ring-8 ring-neutral-900">
                                                <i class="fa-solid fa-folder text-white text-sm"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1 py-0">
                                        <div class="text-sm/8 text-neutral-400">
                                            <div class="mr-0.5">
                                                <h3 class="text-lg font-semibold text-white mb-2">
                                                    <a href="{{ $category->getRoute() }}" class="hover:text-green-400 transition-colors duration-200">
                                                        {{ $category->title }}
                                                    </a>
                                                    <span class="ml-2">
                                                        <span class="inline-flex items-center gap-x-1.5 rounded-full px-2 py-1 text-xs font-medium text-green-400 ring-1 ring-green-500/20">
                                                            <i class="fa-solid fa-circle text-green-400 text-xs"></i>
                                                            {{ __('basic.chapter') }}
                                                        </span>
                                                    </span>
                                                </h3>
                                            </div>

                                            <div class="mt-4 space-y-3">
                                                @foreach($category->lessons as $lesson)
                                                    <div class="bg-neutral-800/50 rounded-lg p-4 hover:bg-neutral-800 transition-colors duration-200">
                                                        <h4 class="font-medium text-white mb-1">
                                                            <a href="{{ $lesson->getRoute() }}" class="hover:text-green-400 transition-colors duration-200 flex items-center">
                                                                <i class="fa-solid fa-play-circle text-green-400 mr-2 text-sm"></i>
                                                                {{ $lesson->title }}
                                                            </a>
                                                        </h4>
                                                        <p class="text-sm text-neutral-400">{{ $lesson->seo_description ?: 'Lekcja kursu' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="text-center mb-16">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-8">
            <h2 class="text-2xl font-bold text-white mb-4">Ready to Start Learning?</h2>
            <p class="text-green-100 mb-6">Join thousands of developers who have already advanced their careers with this course.</p>
            <a href="{{ $firstLessonRoute }}" class="inline-flex items-center justify-center rounded-lg bg-white px-8 py-4 text-green-600 font-semibold transition-colors hover:bg-neutral-100 shadow-lg">
                <i class="fa-solid fa-rocket mr-2"></i>
                {{ __('basic.go_to_course') }}
            </a>
        </div>
    </section>
</main>

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD (Course + BreadcrumbList)
=========================================================== -->
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Course",
      "name": "{{ addslashes($course->title_seo) }}",
      "description": "{{ addslashes($course->description_seo) }}",
      "url": "{{ $urlToCourse }}",
      "provider": {
        "@type": "Organization",
        "name": "Oatllo",
        "url": "{{ route('index') }}"
      },
      "courseMode": "online",
      "educationalLevel": "beginner to advanced",
      "image": "{{ $currentImage }}",
      "inLanguage": "{{ env('APP_LANG_HTML') }}",
      "hasCourseInstance": {
        "@type": "CourseInstance",
        "courseMode": "online",
        "inLanguage": "{{ env('APP_LANG_HTML') }}",
        "courseWorkload": "PT2H"
      },
      "offers": {
        "@type": "Offer",
        "price": "0",
        "priceCurrency": "USD",
        "availability": "https://schema.org/InStock",
        "url": "{{ $urlToCourse }}"
      },
      "instructor": {
        "@type": "Person",
        "name": "Jakub Owsianka",
        "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/"
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
          "name": "{{ addslashes($course->title_list) }}",
          "item": "{{ $urlToCourse }}"
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
</body>
</html>
