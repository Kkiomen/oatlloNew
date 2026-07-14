@php
    use Illuminate\Support\Str;

    $coursesUrl = \App\Services\HomeService::getRouteCourses();

    // Zwraca publiczny URL kursu wg bieżącego języka.
    $courseUrl = function ($course) use ($defaultLangue) {
        return $defaultLangue === 'pl'
            ? route('course_pl', ['courseName' => $course->slug])
            : route('course_en', ['courseName' => $course->slug]);
    };

    // Normalizuje pole image kursu do gotowego URL-a.
    $courseImg = function ($course) {
        $img = empty($course->image) ? 'storage/uploads/empty_image.jpg' : $course->image;
        if (preg_match("/asset\\('(.+?)'\\)/", $img, $m)) { $img = $m[1]; }
        return str_contains($img, 'http') ? $img : asset($img);
    };

    $featuredCourse = $courses->first();
@endphp
<!DOCTYPE html>
<html lang="{{ env('APP_LANG_HTML') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ __('basic.courses') }} – {{ __('basic.courses_header_h2') }} | Oatllo</title>
    <meta name="description" content="{{ __('basic.courses_header') }} {{ __('basic.courses_header_h1') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="author" content="Oatllo - Jakub Owsianka">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="canonical" href="{{ $coursesUrl }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ __('basic.courses') }} – {{ __('basic.courses_header_h2') }} | Oatllo">
    <meta property="og:description" content="{{ __('basic.courses_header') }}">
    <meta property="og:url" content="{{ $coursesUrl }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:image" content="{{ asset('assets/images/logo-512.jpg') }}">
    <meta property="og:image:alt" content="Oatllo">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ __('basic.courses') }} – {{ __('basic.courses_header_h2') }}">
    <meta name="twitter:description" content="{{ __('basic.courses_header') }}">
    <meta name="twitter:image" content="{{ asset('assets/images/logo-512.jpg') }}">
    <meta name="twitter:site" content="@Oatllo">

    <link rel="alternate" type="application/rss+xml" title="Oatllo RSS Feed" href="{{ route('feed') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
        body { font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif; }
        .glass { background-color: rgba(10,10,10,.72); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .hero-glow-green { background: radial-gradient(60% 50% at 50% 0%, rgba(16,185,129,.16) 0%, rgba(16,185,129,0) 70%); }
        .card-hover { transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        .card-hover:hover { transform: translateY(-4px); border-color: rgba(16,185,129,.5); }
        .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
    </style>
</head>
<body class="bg-neutral-950 text-neutral-100 antialiased">
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
                <a href="{{ route('index') }}" class="text-sm font-semibold text-neutral-300 hover:text-emerald-400 transition-colors duration-200">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="text-sm font-semibold text-neutral-300 hover:text-emerald-400 transition-colors duration-200">Blog</a>
                <a href="{{ $coursesUrl }}" class="text-sm font-semibold text-white hover:text-emerald-400 transition-colors duration-200">{{ __('basic.courses') }}</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="text-sm font-semibold text-neutral-300 hover:text-emerald-400 transition-colors duration-200">
                    <i class="fa-brands fa-linkedin mr-1"></i>LinkedIn
                </a>
            </div>
        </nav>
    </header>

    <!-- Mobile menu poza naglowkiem: backdrop-filter tworzylby containing-block dla position:fixed -->
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
                            <a href="{{ $coursesUrl }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.courses') }}</a>
                        </div>
                        <div class="py-6">
                            <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800"><i class="fa-brands fa-linkedin mr-2"></i>LinkedIn</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>

<main id="courses" aria-label="Courses">
    <!-- ===========================================================
      HERO
    =========================================================== -->
    <section class="relative isolate overflow-hidden pt-36 pb-14 sm:pt-44">
        <div class="absolute inset-0 -z-10 hero-glow-green" aria-hidden="true"></div>

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="mx-auto mb-8 max-w-5xl px-4 sm:px-6 lg:px-8">
            <ol class="flex flex-wrap justify-center gap-2 text-sm text-neutral-500" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                    <a href="{{ route('index') }}" itemprop="item" class="hover:text-emerald-400"><span itemprop="name">{{ __('basic.home') }}</span></a>
                    <meta itemprop="position" content="1" />
                </li>
                <li>&#8250;</li>
                <li class="text-neutral-300" itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                    <span itemprop="name">{{ __('basic.courses') }}</span>
                    <meta itemprop="item" content="{{ $coursesUrl }}" />
                    <meta itemprop="position" content="2" />
                </li>
            </ol>
        </nav>

        <header class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-1.5 text-sm font-medium text-emerald-300">
                <i class="fa-solid fa-graduation-cap"></i> {{ __('basic.courses') }}
            </span>
            <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-white sm:text-5xl md:text-6xl">
                {{ __('basic.courses_header_h1') }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-lg text-neutral-400">
                {{ __('basic.courses_header') }}
            </p>

            <dl class="mx-auto mt-10 grid max-w-lg grid-cols-3 gap-6 border-t border-white/5 pt-8">
                <div>
                    <dt class="text-sm text-neutral-500">{{ __('basic.courses') }}</dt>
                    <dd class="mt-1 text-3xl font-bold text-white">{{ $courses->count() }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-neutral-500">Price</dt>
                    <dd class="mt-1 text-3xl font-bold text-white">Free</dd>
                </div>
                <div>
                    <dt class="text-sm text-neutral-500">Pace</dt>
                    <dd class="mt-1 text-3xl font-bold text-white">Self</dd>
                </div>
            </dl>
        </header>
    </section>

    <!-- ===========================================================
      FEATURED COURSE
    =========================================================== -->
    @if($featuredCourse)
        <section class="mx-auto mb-16 max-w-6xl px-4 sm:px-6 lg:px-8">
            <a href="{{ $courseUrl($featuredCourse) }}" class="card-hover group grid overflow-hidden rounded-3xl border border-white/10 bg-neutral-900 lg:grid-cols-2">
                <div class="relative min-h-[16rem] overflow-hidden bg-neutral-800">
                    <img src="{{ $courseImg($featuredCourse) }}" alt="{{ $featuredCourse->title_list ?: $featuredCourse->name }}" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="eager">
                    <div class="absolute inset-0 bg-gradient-to-t from-neutral-900/70 to-transparent lg:bg-gradient-to-r"></div>
                    <span class="absolute left-4 top-4 inline-flex items-center gap-2 rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white shadow-lg shadow-emerald-500/30">
                        <i class="fa-solid fa-star"></i> Featured
                    </span>
                </div>
                <div class="flex flex-col justify-center p-8 lg:p-12">
                    <div class="flex flex-wrap items-center gap-3 text-xs text-neutral-400">
                        <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-layer-group text-emerald-400"></i> {{ $featuredCourse->categories->count() }} {{ __('basic.chapter') }}</span>
                        <span>·</span>
                        <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-tag text-emerald-400"></i> Free</span>
                    </div>
                    <h2 class="mt-4 text-2xl font-bold text-white transition-colors duration-200 group-hover:text-emerald-300 lg:text-3xl">{{ $featuredCourse->title_list ?: $featuredCourse->name }}</h2>
                    <p class="mt-3 text-neutral-400 line-clamp-3">{{ $featuredCourse->description_list }}</p>
                    <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-emerald-400 group-hover:gap-3 transition-all duration-200">
                        {{ __('basic.go_to_course') }} <i class="fa-solid fa-arrow-right"></i>
                    </span>
                </div>
            </a>
        </section>
    @endif

    <!-- ===========================================================
      COURSES GRID
    =========================================================== -->
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" itemscope itemtype="https://schema.org/ItemList">
        @if($courses->count() > 1)
            <h2 class="mb-8 text-2xl font-bold text-white">{{ __('basic.courses') }}</h2>
        @endif
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($courses as $course)
                @if($loop->first) @continue @endif
                <article class="card-hover group flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-neutral-900" itemscope itemprop="itemListElement" itemtype="https://schema.org/Course">
                    <a href="{{ $courseUrl($course) }}" class="relative block overflow-hidden" itemprop="url" aria-label="{{ $course->title_list ?: $course->name }}">
                        <div class="aspect-[16/9] w-full overflow-hidden bg-neutral-800">
                            <img src="{{ $courseImg($course) }}" alt="{{ $course->title_list ?: $course->name }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" itemprop="image" loading="lazy" />
                        </div>
                        <span class="absolute left-3 top-3 rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white shadow-lg shadow-emerald-500/30">FREE</span>
                    </a>
                    <div class="flex flex-1 flex-col p-6">
                        <h3 class="text-lg font-bold text-white transition-colors duration-200 group-hover:text-emerald-300" itemprop="name">
                            <a href="{{ $courseUrl($course) }}">{{ $course->title_list ?: $course->name }}</a>
                        </h3>
                        <p class="mt-2 flex-1 text-sm text-neutral-400 line-clamp-3" itemprop="description">{{ $course->description_list }}</p>
                        <div class="mt-5 flex items-center justify-between text-sm">
                            <span class="inline-flex items-center gap-2 text-neutral-500">
                                <i class="fa-solid fa-layer-group text-emerald-400"></i>
                                {{ $course->categories->count() }} {{ __('basic.chapter') }}
                            </span>
                            <span class="inline-flex items-center gap-2 font-semibold text-emerald-400 group-hover:gap-3 transition-all duration-200">
                                {{ __('basic.go_to_course') }} <i class="fa-solid fa-arrow-right text-xs"></i>
                            </span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-16 text-center">
                    <i class="fa-solid fa-graduation-cap mb-4 block text-4xl text-neutral-600"></i>
                    <p class="text-lg text-neutral-400">No courses available at the moment.</p>
                    <p class="mt-2 text-sm text-neutral-500">Check back soon for new programming courses.</p>
                </div>
            @endforelse
        </div>

        <!-- SEO intro -->
        <div class="mx-auto mt-20 max-w-3xl rounded-2xl border border-white/5 bg-neutral-900/40 p-8 text-center">
            <h2 class="text-xl font-bold text-white">Free, practical programming courses</h2>
            <p class="mt-3 text-neutral-400">
                Learn modern <strong class="text-neutral-200">PHP</strong> and <strong class="text-neutral-200">Laravel</strong> the hands-on way —
                structured, self-paced chapters with real code you can follow along and reuse in your own projects. Free to start, no sign-up required.
            </p>
        </div>
    </section>

    <!-- ===========================================================
      CTA
    =========================================================== -->
    <section class="mx-auto mt-20 mb-4 max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl border border-emerald-500/20 bg-gradient-to-br from-emerald-500/15 via-neutral-900 to-neutral-900 p-10 text-center sm:p-16">
            <div class="absolute inset-0 -z-10 hero-glow-green" aria-hidden="true"></div>
            <h2 class="text-3xl font-bold text-white sm:text-4xl">Ready to start learning?</h2>
            <p class="mx-auto mt-4 max-w-2xl text-neutral-300">Pick a course and start building — or read the blog for fresh tutorials on PHP, Laravel and backend development.</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                @if($featuredCourse)
                    <a href="{{ $courseUrl($featuredCourse) }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-emerald-500/30 hover:bg-emerald-400 transition-colors duration-200">
                        <i class="fa-solid fa-graduation-cap"></i> {{ __('basic.go_to_course') }}
                    </a>
                @endif
                <a href="{{ route('blog') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-base font-semibold text-white hover:bg-white/10 transition-colors duration-200">
                    <i class="fa-solid fa-book-open"></i> {{ __('basic.header_blog') }}
                </a>
            </div>
        </div>
    </section>
</main>

<!-- ===========================================================
  FOOTER
=========================================================== -->
@include('partials.site_footer', ['accent' => 'emerald'])

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD
=========================================================== -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "Programming Courses",
  "description": {!! json_encode(__('basic.courses_header'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "url": "{{ $coursesUrl }}",
  "numberOfItems": {{ $courses->count() }},
  "itemListElement": [
    @foreach($courses as $index => $course)
    {
      "@type": "ListItem",
      "position": {{ $index + 1 }},
      "item": {
        "@type": "Course",
        "name": {!! json_encode($course->title_list ?: $course->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
        "description": {!! json_encode($course->description_list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
        "url": "{{ $courseUrl($course) }}",
        "provider": { "@type": "Organization", "name": "Oatllo", "url": "{{ route('index') }}" },
        "inLanguage": "{{ env('APP_LANG_HTML') }}",
        "isAccessibleForFree": true,
        "offers": { "@type": "Offer", "price": "0", "priceCurrency": "USD", "availability": "https://schema.org/InStock", "url": "{{ $courseUrl($course) }}" },
        "hasCourseInstance": { "@type": "CourseInstance", "courseMode": "online", "inLanguage": "{{ env('APP_LANG_HTML') }}" },
        "instructor": { "@type": "Person", "name": "Jakub Owsianka", "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/" }
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
    { "@type": "ListItem", "position": 1, "name": {!! json_encode(__('basic.home'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ route('index') }}" },
    { "@type": "ListItem", "position": 2, "name": {!! json_encode(__('basic.courses'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ $coursesUrl }}" }
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Oatllo",
  "url": "{{ route('index') }}",
  "logo": "{{ asset('assets/images/logo-512.jpg') }}",
  "sameAs": [ "https://www.linkedin.com/in/jakub-owsianka-446bb5213/" ],
  "founder": { "@type": "Person", "name": "Jakub Owsianka", "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/" }
}
</script>

<script src="{{ asset('/assets/js/script.js') }}"></script>
</body>
</html>
