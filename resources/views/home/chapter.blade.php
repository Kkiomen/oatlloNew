@php
    use App\Services\Article\ContentSanitizer;

    $currentImage = empty($course->image) ? 'storage/uploads/empty_image.jpg' : $course->image;
    if (preg_match("/asset\\('(.+?)'\\)/", $currentImage, $matches)) { $currentImage = $matches[1]; }
    $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);

    $aboutHtml  = app(ContentSanitizer::class)->sanitize((string) $courseCategory->description_content);
    $coursesUrl = \App\Services\HomeService::getRouteCourses();
    $lessonCount = $courseCategory->lessons->count();
    $seoTitle   = $courseCategory->title_seo ?: $courseCategory->title;
    $seoDesc    = $courseCategory->description_seo ?: $courseCategory->description;

    // Akcent per-kurs (motyw okładki): nazwa palety Tailwind + hex do poświaty tła.
    $coverService = app(\App\Services\Course\CourseCoverImageService::class);
    $accent    = $coverService->accentColor($course);
    $accentHex = $coverService->resolveTheme($course)['accent'];
@endphp
<!DOCTYPE html>
<html lang="{{ env('APP_LANG_HTML') }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>{{ $seoTitle }} | {{ $course->title_list ?: $course->name }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <link rel="stylesheet" href="{{ asset('assets/css/tailwind.css') }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="author" content="Oatllo - Jakub Owsianka">

    {!! \App\Services\HomeService::getTagManagerHEAD() !!}

    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/images/logo-512.jpg') }}">
    <link rel="canonical" href="{{ $courseCategory->getRoute() }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }} | {{ $course->title_list ?: $course->name }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:image" content="{{ $currentImage }}">
    <meta property="og:url" content="{{ $courseCategory->getRoute() }}">
    <meta property="og:site_name" content="Oatllo">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $currentImage }}">
    <meta name="twitter:site" content="@Oatllo">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>
    <link rel="preload" href="{{ asset('assets/fonts/montserrat/montserrat-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">

<style>
        body { font-family: 'Montserrat', ui-sans-serif, system-ui, sans-serif; }
        .glass { background-color: rgba(10,10,10,.72); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        :root { --accent: {{ $accentHex }}; }
        .hero-glow-green { background: radial-gradient(42% 70% at 50% -20%, color-mix(in srgb, var(--accent) 12%, transparent) 0%, transparent 70%); }
        .card-hover { transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        .card-hover:hover { transform: translateY(-4px); border-color: color-mix(in srgb, var(--accent) 50%, transparent); }
        .prose-invert p { color:#d4d4d8; margin-bottom:1rem; line-height:1.8; }
        .prose-invert strong, .prose-invert b { color:#fff; font-weight:600; }
        .prose-invert h1,.prose-invert h2,.prose-invert h3,.prose-invert h4 { color:#fff; margin-top:1.5rem; margin-bottom:.75rem; font-weight:700; }
        .prose-invert ul { list-style:disc; padding-left:1.5rem; margin-bottom:1rem; color:#d4d4d8; }
        .prose-invert ol { list-style:decimal; padding-left:1.5rem; margin-bottom:1rem; color:#d4d4d8; }
        .prose-invert li { margin-bottom:.5rem; }
        .prose-invert a { color: var(--accent); text-decoration:underline; }
        .prose-invert a:hover { color:#6ee7b7; }
        .prose-invert code { background:#171717; color:#6ee7b7; border-radius:.25rem; padding:.125rem .35rem; }
        .prose-invert pre { background:#0f0f0f; border:1px solid #262626; border-radius:.75rem; overflow-x:auto; padding:1rem; margin:1rem 0; }
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
                <a href="{{ route('index') }}" class="text-sm font-semibold text-neutral-300 hover:text-{{ $accent }}-400 transition-colors duration-200">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="text-sm font-semibold text-neutral-300 hover:text-{{ $accent }}-400 transition-colors duration-200">Blog</a>
                <a href="{{ $coursesUrl }}" class="text-sm font-semibold text-white hover:text-{{ $accent }}-400 transition-colors duration-200">{{ __('basic.courses') }}</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                <a href="{{ $course->getRoute() }}" class="text-sm font-semibold text-{{ $accent }}-400 hover:text-{{ $accent }}-300 transition-colors duration-200">
                    {!! \App\Support\Icons::svg('graduation-cap', 'mr-1') !!}{{ $course->title_list ?: $course->name }}
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
            <div class="mt-6 space-y-2">
                <a href="{{ route('index') }}" class="block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.home') }}</a>
                <a href="{{ route('blog') }}" class="block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">Blog</a>
                <a href="{{ $coursesUrl }}" class="block rounded-lg px-3 py-2 text-base font-semibold text-white hover:bg-neutral-800">{{ __('basic.courses') }}</a>
                <a href="{{ $course->getRoute() }}" class="block rounded-lg px-3 py-2 text-base font-semibold text-{{ $accent }}-400 hover:bg-neutral-800">{{ $course->title_list ?: $course->name }}</a>
            </div>
        </div>
    </div>
</div>

<!-- ===========================================================
  HERO
=========================================================== -->
<header class="relative isolate overflow-hidden pt-32 pb-10 sm:pt-40">
    <div class="absolute inset-0 -z-10 hero-glow-green" aria-hidden="true"></div>

    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mx-auto mb-8 max-w-5xl px-4 sm:px-6 lg:px-8">
        <ol class="flex flex-wrap gap-2 text-sm text-neutral-500" itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                <a href="{{ route('index') }}" itemprop="item" class="hover:text-{{ $accent }}-400"><span itemprop="name">{{ __('basic.home') }}</span></a>
                <meta itemprop="position" content="1" />
            </li>
            <li>&#8250;</li>
            <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                <a href="{{ $coursesUrl }}" itemprop="item" class="hover:text-{{ $accent }}-400"><span itemprop="name">{{ __('basic.courses') }}</span></a>
                <meta itemprop="position" content="2" />
            </li>
            <li>&#8250;</li>
            <li itemscope itemprop="itemListElement" itemtype="https://schema.org/ListItem">
                <a href="{{ $course->getRoute() }}" itemprop="item" class="hover:text-{{ $accent }}-400"><span itemprop="name">{{ $course->title_list ?: $course->name }}</span></a>
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

    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <span class="inline-flex items-center gap-2 rounded-full border border-{{ $accent }}-400/20 bg-{{ $accent }}-400/10 px-4 py-1.5 text-sm font-medium text-{{ $accent }}-300">
            {!! \App\Support\Icons::svg('folder-open', '') !!} {{ __('basic.chapter') }}
        </span>
        <h1 class="mt-5 text-4xl font-extrabold tracking-tight text-white md:text-5xl">{{ $courseCategory->title }}</h1>
        @if($courseCategory->description)
            <p class="mt-4 text-lg text-neutral-400">{{ $courseCategory->description }}</p>
        @endif
        <div class="mt-6 flex flex-wrap gap-3 text-sm">
            <a href="{{ $course->getRoute() }}" class="inline-flex items-center gap-2 rounded-lg bg-white/5 px-3 py-1.5 text-neutral-300 hover:text-{{ $accent }}-400 transition-colors duration-200">{!! \App\Support\Icons::svg('graduation-cap', 'text-'.$accent.'-400') !!} {{ $course->title_list ?: $course->name }}</a>
            <span class="inline-flex items-center gap-2 rounded-lg bg-white/5 px-3 py-1.5 text-neutral-300">{!! \App\Support\Icons::svg('play-circle', 'text-'.$accent.'-400') !!} {{ $lessonCount }} {{ __('basic.lessons_from_courses') }}</span>
        </div>
    </div>
</header>

<main class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <!-- About chapter -->
    @if(trim(strip_tags($aboutHtml)) !== '')
        <section class="mt-14">
            <div class="rounded-3xl border border-white/10 bg-neutral-900 p-8">
                <h2 class="mb-6 flex items-center gap-3 text-2xl font-bold text-white">
                    {!! \App\Support\Icons::svg('circle-info', 'text-'.$accent.'-400') !!} About this chapter
                </h2>
                <div class="prose-invert max-w-none">{!! $aboutHtml !!}</div>
            </div>
        </section>
    @endif

    <!-- Lessons -->
    <section class="mt-14">
        <h2 class="mb-6 flex items-center gap-3 text-2xl font-bold text-white">
            {!! \App\Support\Icons::svg('list-check', 'text-'.$accent.'-400') !!} {{ __('basic.lessons_from_courses') }}
        </h2>
        <ol class="space-y-3">
            @foreach($courseCategory->lessons as $lesson)
                <li>
                    <a href="{{ $lesson->getRoute() }}" class="card-hover group flex items-start gap-4 rounded-2xl border border-white/10 bg-neutral-900 p-5">
                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-{{ $accent }}-500/15 text-sm font-bold text-{{ $accent }}-400">{{ $loop->iteration }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-white transition-colors duration-200 group-hover:text-{{ $accent }}-300">{{ $lesson->title }}</span>
                            @if($lesson->seo_description)
                                <span class="mt-1 block text-sm text-neutral-400">{{ $lesson->seo_description }}</span>
                            @endif
                        </span>
                        {!! \App\Support\Icons::svg('play-circle', 'mt-1 flex-none text-'.$accent.'-400') !!}
                    </a>
                </li>
            @endforeach
        </ol>
    </section>

    <!-- Nav -->
    <section class="mt-14 flex flex-col gap-3 sm:flex-row">
        <a href="{{ $course->getRoute() }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-base font-semibold text-white hover:bg-white/10 transition-colors duration-200">
            {!! \App\Support\Icons::svg('arrow-left', '') !!} {{ $course->title_list ?: $course->name }}
        </a>
        @if($lessonCount > 0)
            <a href="{{ $courseCategory->lessons->first()->getRoute() }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-{{ $accent }}-500 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-{{ $accent }}-500/30 hover:bg-{{ $accent }}-400 transition-colors duration-200">
                {!! \App\Support\Icons::svg('play', '') !!} {{ __('basic.go_to_course') }}
            </a>
        @endif
    </section>
</main>

<!-- ===========================================================
  FOOTER
=========================================================== -->
@include('partials.site_footer', ['accent' => $accent])

<!-- ===========================================================
  STRUCTURED DATA – JSON-LD
=========================================================== -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Course",
  "name": {!! json_encode($courseCategory->title ?: 'Course Chapter', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "description": {!! json_encode($courseCategory->description ?: 'Learn programming with this course chapter', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
  "url": "{{ $courseCategory->getRoute() }}",
  "provider": { "@type": "Organization", "name": "Oatllo", "url": "{{ route('index') }}" },
  "image": "{{ $currentImage }}",
  "inLanguage": "{{ env('APP_LANG_HTML') }}",
  "isAccessibleForFree": true,
  "isPartOf": { "@type": "Course", "name": {!! json_encode($course->title_list ?: $course->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "url": "{{ $course->getRoute() }}" },
  "hasCourseInstance": { "@type": "CourseInstance", "courseMode": "online", "inLanguage": "{{ env('APP_LANG_HTML') }}", "courseWorkload": "PT2H", "instructor": { "@type": "Person", "name": "Jakub Owsianka", "url": "https://www.linkedin.com/in/jakub-owsianka-446bb5213/" } },
  "offers": { "@type": "Offer", "price": "0", "priceCurrency": "USD", "availability": "https://schema.org/InStock", "url": "{{ $courseCategory->getRoute() }}" }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": {!! json_encode(__('basic.home'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ route('index') }}" },
    { "@type": "ListItem", "position": 2, "name": {!! json_encode(__('basic.courses'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ $coursesUrl }}" },
    { "@type": "ListItem", "position": 3, "name": {!! json_encode($course->title_list ?: $course->name, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ $course->getRoute() }}" },
    { "@type": "ListItem", "position": 4, "name": {!! json_encode($courseCategory->title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}, "item": "{{ $courseCategory->getRoute() }}" }
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
