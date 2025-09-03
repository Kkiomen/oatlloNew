<!DOCTYPE html>
<html lang="{{ env('APP_LANG_HTML') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ __('basic.meta_title') }} - {{ __('basic.courses') }}</title>
    <meta name="description" content="{{ __('basic.meta_description') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="index, follow">
    <meta name="author" content="Oatllo - Jakub Owsianka">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">
    <link rel="canonical" href="{{ \App\Services\HomeService::getRouteCourses() }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ __('basic.meta_title') }} - {{ __('basic.courses') }}">
    <meta property="og:description" content="{{ __('basic.meta_description') }}">
    <meta property="og:url" content="{{ \App\Services\HomeService::getRouteCourses() }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:image" content="https://oatllo.com/assets/images/logo-512.png">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ __('basic.meta_title') }} - {{ __('basic.courses') }}">
    <meta name="twitter:description" content="{{ __('basic.meta_description') }}">
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
        <li class="text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
            <span itemprop="name">{{ __('basic.courses') }}</span>
            <meta itemprop="item" content="{{ \App\Services\HomeService::getRouteCourses() }}" />
            <meta itemprop="position" content="2" />
        </li>
    </ol>
</nav>

<!-- ===========================================================
  MAIN CONTENT
=========================================================== -->
<main id="courses" class="pt-32 pb-32" aria-label="Courses">
    <!-- Page Header -->
    <header class="mx-auto mb-16 max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight text-white md:text-5xl">
            {{ __('basic.courses_header_h1') }} <span class="text-green-400">{{ __('basic.courses_header_h2') }}</span>
        </h1>
        <p class="mt-4 text-lg text-neutral-300">
            Comprehensive programming courses designed to help you master modern development skills and advance your career.
        </p>
    </header>

    <!-- Featured Course Section -->
    @if($courses->count() > 0)
        <section class="mx-auto mb-20 max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-white mb-4">Featured <span class="text-green-400">Course</span></h2>
                <p class="text-neutral-300">Our most comprehensive and popular course</p>
            </div>
            @php $featuredCourse = $courses->first(); @endphp
            <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-neutral-900 to-neutral-800 shadow-2xl">
                <div class="grid lg:grid-cols-2 gap-0">
                    <div class="relative">
                        @php
                            $currentImage = empty($featuredCourse->image) ? 'storage/uploads/empty_image.jpg' : $featuredCourse->image;
                            $pattern = "/asset\('(.+?)'\)/";
                            if (preg_match($pattern, $currentImage, $matches)) {
                                $currentImage = $matches[1];
                            }
                            $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);
                        @endphp
                        <img src="{{ $currentImage }}" alt="{{ $featuredCourse->title_list }}" class="h-full w-full object-cover" loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-transparent to-transparent"></div>
                    </div>
                    <div class="p-8 lg:p-12 flex flex-col justify-center">
                        <div class="mb-4">
                            <span class="inline-flex items-center rounded-full bg-green-500/20 px-3 py-1 text-sm font-medium text-green-400">
                                <i class="fa-solid fa-star mr-2"></i>Featured
                            </span>
                        </div>
                        <h3 class="text-2xl lg:text-3xl font-bold text-white mb-4">{{ $featuredCourse->title_list }}</h3>
                        <p class="text-lg text-neutral-300 mb-6 line-clamp-4">{{ $featuredCourse->description_list }}</p>
                        <div class="flex items-center gap-6 text-sm text-neutral-400 mb-6">
                            <div class="flex items-center">
                                <i class="fa-solid fa-clock text-green-400 mr-2"></i>
                                <span>Self-paced learning</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fa-solid fa-users text-green-400 mr-2"></i>
                                <span>Beginner to Advanced</span>
                            </div>
                        </div>
                        @php
                            if($defaultLangue == 'pl'){
                                $urlToCourse = route('course_pl', ['courseName' => $featuredCourse->slug ]);
                            }else{
                                $urlToCourse = route('course_en', ['courseName' => $featuredCourse->slug ]);
                            }
                        @endphp
                        <a href="{{ $urlToCourse }}" class="inline-flex items-center justify-center rounded-lg bg-green-500 px-6 py-3 text-white font-semibold transition-colors hover:bg-green-600">
                            Start Learning
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
            <h2 class="text-2xl font-bold text-white mb-6 text-center">Programming Education & <span class="text-green-400">Skill Development</span></h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-xl font-semibold text-white mb-4 flex items-center">
                        <i class="fa-solid fa-graduation-cap text-green-400 mr-3"></i>
                        Structured Learning Paths
                    </h3>
                    <p class="text-neutral-300 mb-4">Follow carefully designed curricula that take you from beginner to advanced levels with practical, hands-on projects.</p>
                    <ul class="space-y-2 text-sm text-neutral-400">
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-400 mr-2"></i>Step-by-step progression</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-400 mr-2"></i>Real-world projects</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-400 mr-2"></i>Interactive exercises</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-400 mr-2"></i>Expert guidance</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-white mb-4 flex items-center">
                        <i class="fa-solid fa-certificate text-green-400 mr-3"></i>
                        Professional Certification
                    </h3>
                    <p class="text-neutral-300 mb-4">Earn certificates upon completion to showcase your skills and advance your career in the tech industry.</p>
                    <ul class="space-y-2 text-sm text-neutral-400">
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-400 mr-2"></i>Industry-recognized certificates</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-400 mr-2"></i>Portfolio-ready projects</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-400 mr-2"></i>Lifetime access</li>
                        <li class="flex items-center"><i class="fa-solid fa-check text-green-400 mr-2"></i>Continuous updates</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses Grid -->
    <section class="mx-auto grid max-w-7xl gap-8 px-4 sm:grid-cols-2 lg:grid-cols-3 sm:px-6 lg:px-8" itemscope itemtype="https://schema.org/ItemList">
        @forelse($courses as $course)
            @php
                if($defaultLangue == 'pl'){
                    $urlToCourse = route('course_pl', ['courseName' => $course->slug ]);
                }else{
                    $urlToCourse = route('course_en', ['courseName' => $course->slug ]);
                }
                $currentImage = empty($course->image) ? 'storage/uploads/empty_image.jpg' : $course->image;
                $pattern = "/asset\('(.+?)'\)/";
                if (preg_match($pattern, $currentImage, $matches)) {
                    $currentImage = $matches[1];
                }
                $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);
            @endphp
            <article class="flex flex-col overflow-hidden rounded-2xl bg-neutral-900/70 shadow-lg transition hover:shadow-green-500/30" itemscope itemprop="itemListElement" itemtype="https://schema.org/Course">
                <a href="{{ $urlToCourse }}" class="group relative block" itemprop="url">
                    <img src="{{ $currentImage }}" alt="{{ $course->title_list }}" class="h-56 w-full object-cover transition group-hover:scale-105" itemprop="image" loading="lazy" />
                    <span class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></span>
                </a>
                <div class="flex flex-1 flex-col p-6">
                    <header class="mb-3 flex-1">
                        <h2 class="text-xl font-bold tracking-tight text-white group-hover:text-green-400" itemprop="name">
                            <a href="{{ $urlToCourse }}" class="inline-block h-full w-full" itemprop="url">{{ $course->title_list }}</a>
                        </h2>
                        <p class="mt-2 line-clamp-3 text-neutral-400" itemprop="description">
                            {{ $course->description_list }}
                        </p>
                    </header>
                    <!-- Meta info -->
                    <footer class="mt-auto flex items-center justify-between text-sm text-neutral-400">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-clock text-green-400"></i>
                            <span>Self-paced</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-users text-green-400"></i>
                            <span>All levels</span>
                        </div>
                    </footer>
                </div>
            </article>
        @empty
            <div class="col-span-full text-center py-12">
                <div class="text-neutral-400 text-lg">
                    <i class="fa-solid fa-graduation-cap text-4xl mb-4 block"></i>
                    <p>No courses available at the moment.</p>
                    <p class="mt-2 text-sm">Check back soon for new programming courses!</p>
                </div>
            </div>
        @endforelse
    </section>

    <!-- Call to Action Section -->
    <section class="mx-auto mt-20 max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg p-8 text-center">
            <h2 class="text-2xl font-bold text-white mb-4">Ready to Start Learning?</h2>
            <p class="text-green-100 mb-6">Join thousands of developers who have already advanced their careers with our courses.</p>
            <div class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
                <a href="{{ route('blog') }}" class="bg-white text-green-600 px-6 py-3 rounded-lg font-semibold hover:bg-neutral-100 transition-colors duration-200">
                    <i class="fa-solid fa-book mr-2"></i>Read Our Blog
                </a>
                <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="bg-green-700 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-800 transition-colors duration-200">
                    <i class="fa-brands fa-linkedin mr-2"></i>Connect on LinkedIn
                </a>
            </div>
            <p class="text-green-100 text-sm mt-4">Follow us for the latest programming insights and course updates.</p>
        </div>
    </section>
</main>

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD (Course + ItemList + BreadcrumbList)
=========================================================== -->
<script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "ItemList",
      "name": "Programming Courses",
      "description": "Comprehensive programming courses designed to help you master modern development skills",
      "url": "{{ \App\Services\HomeService::getRouteCourses() }}",
      "numberOfItems": {{ $courses->count() }},
      "itemListElement": [
    @foreach($courses as $index => $course)
        {
          "@type": "ListItem",
          "position": {{ $index + 1 }},
              "item": {
                "@type": "Course",
                "name": "{{ addslashes($course->title_list) }}",
                "description": "{{ addslashes($course->description_list) }}",
                "url": "{{ $urlToCourse }}",
                "provider": {
                  "@type": "Organization",
                  "name": "Oatllo",
                  "url": "{{ route('index') }}"
                },
                "courseMode": "online",
                "educationalLevel": "beginner to advanced",
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
          "name": "{{ __('basic.courses') }}",
          "item": "{{ \App\Services\HomeService::getRouteCourses() }}"
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
