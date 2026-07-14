@php
    use App\Services\Article\ContentSanitizer;

    $currentImage = empty($course->image) ? 'storage/uploads/empty_image.jpg' : $course->image;
    if (preg_match("/asset\\('(.+?)'\\)/", $currentImage, $matches)) { $currentImage = $matches[1]; }
    $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);

    // Sanityzacja treści (em/en dashe -> dywiz, słownik anti-AI) przed wyświetleniem.
    $sanitizer  = app(ContentSanitizer::class);
    $titleFull  = $sanitizer->sanitize((string) $course->title_full);
    $descFull   = $sanitizer->sanitize((string) $course->description_full);
    $aboutHtml  = $sanitizer->sanitize((string) $course->content_description_offers);

    $coursesUrl   = \App\Services\HomeService::getRouteCourses();
    $chapterCount = $course->categories->count();
    $lessonCount  = $course->categories->sum(fn ($c) => $c->lessons->count());
@endphp
<!DOCTYPE html>
<html lang="{{ env('APP_LANG_HTML') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ $course->title_seo ?: $course->title_list }} | Oatllo</title>
    <meta name="description" content="{{ $course->description_seo }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="author" content="Oatllo - Jakub Owsianka">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="canonical" href="{{ $urlToCourse }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $course->title_seo ?: $course->title_list }} | Oatllo">
    <meta property="og:description" content="{{ $course->description_seo }}">
    <meta property="og:image" content="{{ $currentImage }}">
    <meta property="og:url" content="{{ $urlToCourse }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $course->title_seo ?: $course->title_list }}">
    <meta name="twitter:description" content="{{ $course->description_seo }}">
    <meta name="twitter:image" content="{{ $currentImage }}">
    <meta name="twitter:site" content="@Oatllo">

    <link rel="alternate" type="application/rss+xml" title="Oatllo RSS Feed" href="{{ route('feed') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>
    <link rel="preload" href="{{ asset('assets/fonts/montserrat/montserrat-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">

<style>
        body { font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif; }
        .glass { background-color: rgba(10,10,10,.72); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .hero-glow-green { background: radial-gradient(60% 50% at 50% 0%, rgba(16,185,129,.16) 0%, rgba(16,185,129,0) 70%); }
        .card-hover { transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        .card-hover:hover { transform: translateY(-4px); border-color: rgba(16,185,129,.5); }
        .prose-invert p { color:#d4d4d8; margin-bottom:1rem; line-height:1.8; }
        .prose-invert strong, .prose-invert b { color:#fff; font-weight:600; }
        .prose-invert h1,.prose-invert h2,.prose-invert h3,.prose-invert h4 { color:#fff; margin-top:1.5rem; margin-bottom:.75rem; font-weight:700; }
        .prose-invert ul { list-style:disc; padding-left:1.5rem; margin-bottom:1rem; color:#d4d4d8; }
        .prose-invert ol { list-style:decimal; padding-left:1.5rem; margin-bottom:1rem; color:#d4d4d8; }
        .prose-invert li { margin-bottom:.5rem; }
        .prose-invert a { color:#34d399; text-decoration:underline; }
        .prose-invert a:hover { color:#6ee7b7; }
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
                    {!! \App\Support\Icons::svg('bars', 'text-xl') !!}
                </button>
            </div>
            <div class="hidden lg:flex lg:gap-x-10">
                <a href="{{ route('index') }}" class="text-sm font-semibold text-neutral-300 hover:text-emerald-400 transition-colors duration-200">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="text-sm font-semibold text-neutral-300 hover:text-emerald-400 transition-colors duration-200">Blog</a>
                <a href="{{ $coursesUrl }}" class="text-sm font-semibold text-white hover:text-emerald-400 transition-colors duration-200">{{ __('basic.courses') }}</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="text-sm font-semibold text-neutral-300 hover:text-emerald-400 transition-colors duration-200">
                    {!! \App\Support\Icons::svg('linkedin', 'mr-1') !!}LinkedIn
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
                    <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-300" @click="open = false" aria-label="Close menu">{!! \App\Support\Icons::svg('xmark', 'text-xl') !!}</button>
                </div>
                <div class="mt-6 flow-root">
                    <div class="-my-2 divide-y divide-white/10">
                        <div class="space-y-2 py-6">
                            <a href="{{ route('index') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.home') }}</a>
                            <a href="{{ route('blog') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">Blog</a>
                            <a href="{{ $coursesUrl }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.courses') }}</a>
                        </div>
                        <div class="py-6">
                            <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{!! \App\Support\Icons::svg('linkedin', 'mr-2') !!}LinkedIn</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- ===========================================================
  HERO
=========================================================== -->
<header class="relative isolate overflow-hidden pt-32 pb-10 sm:pt-40" itemscope itemtype="https://schema.org/Course">
    <div class="absolute inset-0 -z-10 hero-glow-green" aria-hidden="true"></div>
    <meta itemprop="name" content="{{ $course->title_seo ?: $course->title_list }}" />
    <meta itemprop="description" content="{{ $course->description_seo }}" />
    <meta itemprop="url" content="{{ $urlToCourse }}" />

    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mx-auto mb-8 max-w-5xl px-4 sm:px-6 lg:px-8">
        <ol class="flex flex-wrap gap-2 text-sm text-neutral-500" itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                <a href="{{ route('index') }}" itemprop="item" class="hover:text-emerald-400"><span itemprop="name">{{ __('basic.home') }}</span></a>
                <meta itemprop="position" content="1" />
            </li>
            <li>&#8250;</li>
            <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                <a href="{{ $coursesUrl }}" itemprop="item" class="hover:text-emerald-400"><span itemprop="name">{{ __('basic.courses') }}</span></a>
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

    <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-1.5 text-sm font-medium text-emerald-300">
                {!! \App\Support\Icons::svg('graduation-cap', '') !!} {{ __('basic.courses') }}
            </span>
            <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-white md:text-5xl">{!! $titleFull !!}</h1>
            <div class="mt-5 max-w-xl text-lg text-neutral-400">{!! $descFull !!}</div>

            <div class="mt-6 flex flex-wrap gap-3 text-sm">
                <span class="inline-flex items-center gap-2 rounded-lg bg-white/5 px-3 py-1.5 text-neutral-300">{!! \App\Support\Icons::svg('layer-group', 'text-emerald-400') !!} {{ $chapterCount }} {{ __('basic.chapter') }}</span>
                @if($lessonCount > 0)
                    <span class="inline-flex items-center gap-2 rounded-lg bg-white/5 px-3 py-1.5 text-neutral-300">{!! \App\Support\Icons::svg('play-circle', 'text-emerald-400') !!} {{ $lessonCount }} {{ __('basic.lessons_from_courses') }}</span>
                @endif
                <span class="inline-flex items-center gap-2 rounded-lg bg-white/5 px-3 py-1.5 text-neutral-300">{!! \App\Support\Icons::svg('tag', 'text-emerald-400') !!} Free</span>
            </div>

            <div class="mt-8">
                <a href="{{ $firstLessonRoute }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-emerald-500/30 hover:bg-emerald-400 transition-colors duration-200">
                    {!! \App\Support\Icons::svg('play', '') !!} {{ __('basic.go_to_course') }}
                </a>
            </div>
        </div>

        <figure class="overflow-hidden rounded-3xl border border-white/10 shadow-2xl" itemprop="image">
            <div class="aspect-[16/10] w-full bg-neutral-800">
                <img decoding="async" src="{{ $currentImage }}" alt="{{ $course->title_seo ?: $course->title_list }}" width="1200" height="750" class="h-full w-full object-cover" loading="eager" fetchpriority="high" />
            </div>
        </figure>
    </div>
</header>

<main class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
    <!-- About -->
    @if(trim(strip_tags($aboutHtml)) !== '')
        <section class="mt-16">
            <div class="rounded-3xl border border-white/10 bg-neutral-900 p-8">
                <h2 class="mb-6 flex items-center gap-3 text-2xl font-bold text-white">
                    {!! \App\Support\Icons::svg('circle-info', 'text-emerald-400') !!} About this course
                </h2>
                <div class="prose-invert max-w-none">{!! $aboutHtml !!}</div>
            </div>
        </section>
    @endif

    <!-- Curriculum -->
    <section class="mt-16">
        <h2 class="mb-8 flex items-center gap-3 text-2xl font-bold text-white">
            {!! \App\Support\Icons::svg('list-check', 'text-emerald-400') !!} Course curriculum
        </h2>

        <div class="space-y-4">
            @foreach($course->categories as $category)
                <div class="overflow-hidden rounded-2xl border border-white/10 bg-neutral-900" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }">
                    <button type="button" class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left" @click="open = !open">
                        <span class="flex items-center gap-4">
                            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-emerald-500/15 text-sm font-bold text-emerald-400">{{ $loop->iteration }}</span>
                            <span>
                                <a href="{{ $category->getRoute() }}" class="font-semibold text-white hover:text-emerald-400 transition-colors duration-200" @click.stop>{{ $category->title }}</a>
                                <span class="ml-2 text-xs text-neutral-500">{{ $category->lessons->count() }} {{ __('basic.lessons_from_courses') }}</span>
                            </span>
                        </span>
                        {!! \App\Support\Icons::svg('chevron-down', 'flex-none text-emerald-400 transition-transform duration-200') !!}
                    </button>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0">
                        <ul class="space-y-2 px-6 pb-5">
                            @foreach($category->lessons as $lesson)
                                <li>
                                    <a href="{{ $lesson->getRoute() }}" class="group flex items-start gap-3 rounded-xl bg-white/5 p-4 hover:bg-white/10 transition-colors duration-200">
                                        {!! \App\Support\Icons::svg('play-circle', 'mt-0.5 flex-none text-emerald-400') !!}
                                        <span class="min-w-0">
                                            <span class="block font-medium text-white group-hover:text-emerald-300 transition-colors duration-200">{{ $lesson->title }}</span>
                                            @if($lesson->seo_description)
                                                <span class="mt-0.5 block text-sm text-neutral-400 line-clamp-1">{{ $lesson->seo_description }}</span>
                                            @endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Final CTA -->
    <section class="mt-20 mb-4">
        <div class="relative overflow-hidden rounded-3xl border border-emerald-500/20 bg-gradient-to-br from-emerald-500/15 via-neutral-900 to-neutral-900 p-10 text-center sm:p-14">
            <div class="absolute inset-0 -z-10 hero-glow-green" aria-hidden="true"></div>
            <h2 class="text-3xl font-bold text-white">Start with the first lesson</h2>
            <p class="mx-auto mt-3 max-w-xl text-neutral-300">Free, self-paced and hands-on. Jump in and start building.</p>
            <a href="{{ $firstLessonRoute }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-7 py-3.5 text-base font-semibold text-white shadow-lg shadow-emerald-500/30 hover:bg-emerald-400 transition-colors duration-200">
                {!! \App\Support\Icons::svg('play', '') !!} {{ __('basic.go_to_course') }}
            </a>
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
  "@type": "Course",
  "name": {!! json_encode($course->title_seo ?: $course->title_list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "description": {!! json_encode($course->description_seo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "url": "{{ $urlToCourse }}",
  "provider": { "@type": "Organization", "name": "Oatllo", "url": "{{ route('index') }}" },
  "image": "{{ $currentImage }}",
  "inLanguage": "{{ env('APP_LANG_HTML') }}",
  "isAccessibleForFree": true,
  "hasCourseInstance": {
    "@type": "CourseInstance",
    "courseMode": "online",
    "inLanguage": "{{ env('APP_LANG_HTML') }}",
    "courseWorkload": "PT2H",
    "instructor": { "@type": "Person", "name": "Jakub Owsianka", "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/" }
  },
  "offers": { "@type": "Offer", "price": "0", "priceCurrency": "USD", "availability": "https://schema.org/InStock", "url": "{{ $urlToCourse }}" }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": {!! json_encode(__('basic.home'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ route('index') }}" },
    { "@type": "ListItem", "position": 2, "name": {!! json_encode(__('basic.courses'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ $coursesUrl }}" },
    { "@type": "ListItem", "position": 3, "name": {!! json_encode($course->title_list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ $urlToCourse }}" }
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

<script defer src="{{ asset('/assets/js/script.js') }}"></script>
</body>
</html>
