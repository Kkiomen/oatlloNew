<!doctype html>
<html lang="{{ env('APP_LANG_HTML') }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('basic.meta_title') }}</title>
    <meta name="description" content="{{ __('basic.meta_description') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="alternate" type="application/rss+xml" title="Oatllo RSS Feed" href="{{ route('feed') }}" />
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
<body>
{!! \App\Services\HomeService::getTagManagerBODY() !!}


<div>
    <div class="bg-gray-900" x-data="{ open: true }">
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
                    <a href="{{ route('index') }}" class="text-sm/6 font-semibold text-white hover:text-rose-400 transition-colors duration-200">{{ __('basic.home') }}</a>
                    <a href="{{ route('blog') }}" class="text-sm/6 font-semibold text-white hover:text-rose-400 transition-colors duration-200">Blog</a>
                    <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="text-sm/6 font-semibold text-white hover:text-rose-400 transition-colors duration-200">{{ __('basic.courses') }}</a>
                </div>
                <div class="hidden lg:flex lg:flex-1 lg:justify-end">
                    <a href="https://www.linkedin.com/in/jakub-owsianka-446bb5213/" target="_blank" rel="noopener" class="text-sm/6 font-semibold text-white hover:text-rose-400 transition-colors duration-200">
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

        <div class="relative isolate pt-14">
            <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
                <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
            </div>
            <div class="py-24 sm:py-32 lg:pb-40">
                <div class="mx-auto max-w-7xl px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h1 class="text-balance text-5xl font-semibold tracking-tight text-white sm:text-5xl">{{ __('basic.blog_header') }}</h1>
                        <p class="mt-8 text-pretty text-lg font-medium text-gray-400 sm:text-xl/8">{{ __('basic.blog_subheader') }}</p>
                        <div class="mt-10 flex items-center justify-center gap-x-6">
                            <a href="#about_me" class="rounded-md bg-indigo-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-400">{{ __('basic.more') }}</a>
                            <a href="{{ route('blog') }}" class="text-sm/6 font-semibold text-white">Blog <span aria-hidden="true">→</span></a>
                        </div>
                    </div>

                </div>
            </div>
            <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
                <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
            </div>
        </div>
    </div>



    <div class="bg-gray-900 pb-16 pt-24 sm:pb-24 sm:pt-32 xl:pb-32" id="about_me">
        <div class="bg-gray-900 pb-20 sm:pb-24 xl:pb-0">
            <div class="mx-auto flex max-w-7xl flex-col items-center gap-x-8 gap-y-10 px-6 sm:gap-y-8 lg:px-8 xl:flex-row xl:items-stretch">
                <div class="-mt-8 w-full max-w-2xl xl:-mb-8 xl:w-96 xl:flex-none">
                    <div class="relative aspect-[2/1] h-full md:-mx-8 xl:mx-0 xl:aspect-auto text-center">
                        <img class="absolute inset-0  rounded-2xl bg-gray-800 object-cover shadow-2xl m-auto" src="{{ asset('/assets/images/owsianka_jakub.png') }}" alt="Owsianka Jakub - programista, pasjonat dobrego kodu, programowanie php">
                    </div>
                </div>
                <div class="w-full max-w-2xl xl:max-w-none xl:flex-auto xl:px-16 xl:py-24">
                    <figure class="relative isolate pt-6 sm:pt-12">
                        <svg viewBox="0 0 162 128" fill="none" aria-hidden="true" class="absolute left-0 top-0 -z-10 h-32 stroke-white/20">
                            <path id="b56e9dab-6ccb-4d32-ad02-6b4bb5d9bbeb" d="M65.5697 118.507L65.8918 118.89C68.9503 116.314 71.367 113.253 73.1386 109.71C74.9162 106.155 75.8027 102.28 75.8027 98.0919C75.8027 94.237 75.16 90.6155 73.8708 87.2314C72.5851 83.8565 70.8137 80.9533 68.553 78.5292C66.4529 76.1079 63.9476 74.2482 61.0407 72.9536C58.2795 71.4949 55.276 70.767 52.0386 70.767C48.9935 70.767 46.4686 71.1668 44.4872 71.9924L44.4799 71.9955L44.4726 71.9988C42.7101 72.7999 41.1035 73.6831 39.6544 74.6492C38.2407 75.5916 36.8279 76.455 35.4159 77.2394L35.4047 77.2457L35.3938 77.2525C34.2318 77.9787 32.6713 78.3634 30.6736 78.3634C29.0405 78.3634 27.5131 77.2868 26.1274 74.8257C24.7483 72.2185 24.0519 69.2166 24.0519 65.8071C24.0519 60.0311 25.3782 54.4081 28.0373 48.9335C30.703 43.4454 34.3114 38.345 38.8667 33.6325C43.5812 28.761 49.0045 24.5159 55.1389 20.8979C60.1667 18.0071 65.4966 15.6179 71.1291 13.7305C73.8626 12.8145 75.8027 10.2968 75.8027 7.38572C75.8027 3.6497 72.6341 0.62247 68.8814 1.1527C61.1635 2.2432 53.7398 4.41426 46.6119 7.66522C37.5369 11.6459 29.5729 17.0612 22.7236 23.9105C16.0322 30.6019 10.618 38.4859 6.47981 47.558L6.47976 47.558L6.47682 47.5647C2.4901 56.6544 0.5 66.6148 0.5 77.4391C0.5 84.2996 1.61702 90.7679 3.85425 96.8404L3.8558 96.8445C6.08991 102.749 9.12394 108.02 12.959 112.654L12.959 112.654L12.9646 112.661C16.8027 117.138 21.2829 120.739 26.4034 123.459L26.4033 123.459L26.4144 123.465C31.5505 126.033 37.0873 127.316 43.0178 127.316C47.5035 127.316 51.6783 126.595 55.5376 125.148L55.5376 125.148L55.5477 125.144C59.5516 123.542 63.0052 121.456 65.9019 118.881L65.5697 118.507Z" />
                            <use href="#b56e9dab-6ccb-4d32-ad02-6b4bb5d9bbeb" x="86" />
                        </svg>
                        <blockquote class="text-xl/8 font-semibold text-white sm:text-2xl/9">
                            <p>{{ __('basic.about_me_content') }}</p>
                        </blockquote>
                        <figcaption class="mt-8 text-base">
                            <div class="font-semibold text-white">Jakub Owsianka</div>
                            <div class="mt-1 text-gray-400">{{ __('basic.about_me_description') }}</div>
                        </figcaption>
                    </figure>
                </div>
            </div>
        </div>
    </div>



    @if($randomArticles->count() > 0)
    <div class="jetbrains_bg_color py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-balance text-4xl font-semibold tracking-tight text-white sm:text-5xl jetbrains_text">{{ __('basic.header_blog') }}</h2>
                <p class="mt-2 text-lg/8 text-white jetbrains_text">{{ __('basic.header_sub_blog') }}</p>
            </div>
            <div class="mx-auto mt-16 grid max-w-2xl auto-rows-fr grid-cols-1 gap-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                @foreach($randomArticles as $article)
                <article class="relative isolate flex flex-col justify-end overflow-hidden rounded-2xl bg-gray-900 px-8 pb-8 pt-80 sm:pt-48 lg:pt-80">
                    <img src="{{ $article->image }}" alt="{{ $article->name }}" class="absolute inset-0 -z-10 h-full w-full object-cover">
                    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-gray-900 via-gray-900/40"></div>
                    <div class="absolute inset-0 -z-10 rounded-2xl ring-1 ring-inset ring-gray-900/10"></div>

                    <div class="flex flex-wrap items-center gap-y-1 overflow-hidden text-sm/6 text-gray-300 jetbrains_text">
                        <time datetime="{{ $article->getPublishedDate()->format('Y-m-d') }}" class="mr-8">{{ $article->getPublishedDate()->format('Y-m-d') }}</time>
                        <div class="-ml-4 flex items-center gap-x-4">
                            <svg viewBox="0 0 2 2" class="-ml-0.5 h-0.5 w-0.5 flex-none fill-white/50">
                                <circle cx="1" cy="1" r="1" />
                            </svg>
                            <div class="flex gap-x-2.5 jetbrains_text">
                                <img src="{{ asset('/assets/images/owsianka_jakub.png') }}" alt="" class="h-6 w-6 flex-none rounded-full bg-white/10">
                                Owsianka Jakub
                            </div>
                        </div>
                    </div>
                    <h3 class="mt-3 text-lg/6 font-semibold text-white  jetbrains_text">
                        <a href="{{ $article->getRoute() }}">
                            <span class="absolute inset-0"></span>
                            {{ $article->name }}
                        </a>
                    </h3>
                </article>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="bg-neutral-900 py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-4xl">
                <!-- Featured PHP Course Card -->
                <div class="flex flex-col lg:flex-row overflow-hidden rounded-2xl bg-gradient-to-r from-blue-900 to-neutral-800 shadow-2xl">
                    <!-- Left Section - Visual -->
                    <div class="lg:w-2/5 bg-blue-900 p-8 relative">
                        <div class="text-gray-300 mb-4">
                            <div class="text-sm font-medium">COURSE</div>
                            <div class="text-4xl font-bold mb-2">PHP</div>
                            <div class="bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm font-semibold inline-block">FOR FREE</div>
                        </div>

                        <!-- Elephant mascot placeholder -->
                        <div class="absolute bottom-8 right-8 w-24 h-24 bg-blue-400 rounded-full flex items-center justify-center opacity-80">
                            <div class="text-blue-900 font-bold text-lg">php</div>
                        </div>

                        <div class="absolute bottom-4 left-8 text-gray-300 text-sm font-medium">oatllo</div>
                    </div>

                    <!-- Right Section - Content -->
                    <div class="lg:w-3/5 bg-neutral-800 p-8 flex flex-col justify-between">
                        <div>
                            <!-- Featured tag -->
                            <div class="inline-flex items-center bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium mb-4">
                                <i class="fa-solid fa-star mr-1"></i>
                                Featured
                            </div>

                            <!-- Main headline -->
                            <h2 class="text-3xl font-bold text-white mb-4">
                                Instant results from learning PHP
                            </h2>

                            <!-- Description -->
                            <p class="text-gray-300 mb-6 leading-relaxed">
                                Gain practical knowledge and learn from real-world projects. This PHP course will help you quickly create dynamic web applications and gain the skills you need in the job market.
                            </p>

                            <!-- Course features -->
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center text-gray-300">
                                    <i class="fa-solid fa-clock text-green-400 mr-3"></i>
                                    <span>Self-paced learning</span>
                                </div>
                                <div class="flex items-center text-gray-300">
                                    <i class="fa-solid fa-users text-green-400 mr-3"></i>
                                    <span>Beginner to Advanced</span>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Button -->
                        <div class="mt-6">
                            <a href="https://oatllo.com/course/php" class="inline-flex items-center bg-green-500 hover:bg-green-600 text-white px-8 py-4 rounded-lg font-semibold text-lg transition-colors duration-200">
                                Start Learning
                                <i class="fa-solid fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($postInstagrams))
        <div class="bg-dark-brown">
            <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">
                <h2 class="poppins-semibold text-xl font-bold text-gray-900 text-white ">Instagram posts</h2>

                <div class="mt-8 grid grid-cols-1 gap-y-12 sm:grid-cols-2 sm:gap-x-6 lg:grid-cols-4 xl:gap-x-8">

                    @foreach($postInstagrams as $post)
                        <div>
                            <div class="relative">
                                <a href="{{ $post->url }}" target="_blank">
                                    <div class="relative h-80 w-full overflow-hidden shadow rounded-lg">
                                        <img src="{{ $post->getUrl()}}" alt="Front of zip tote bag with white canvas, black canvas straps and handle, and black zipper pulls." class="size-full object-cover object-top" />
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                    <!-- More products... -->
                </div>
            </div>
        </div>
    @endif

    <div class="bg-dark-brown">
    </div>

    <section id="features" class="bg-neutral-900 py-20 lg:py-28" aria-label="Key features of our PHP programming course platform">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Section Heading -->
            <header class="mb-12 text-center">
                <h2 class="text-4xl font-extrabold tracking-tight text-white md:text-5xl">
                    All-in-One <span class="text-rose-400">PHP Learning Features</span>
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-neutral-300">
                    Master PHP, Database, MySQL and modern backend development with interactive tutorials, real-world projects and expert mentor support.
                </p>
            </header>

            <!-- Feature List (schema.org ItemList) -->
            <ul class="flex flex-wrap justify-center gap-4" itemscope itemtype="https://schema.org/ItemList">
                <!-- 1. Interactive video lessons -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="1" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/90 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-rose-500/60 backdrop-blur" title="Interactive PHP video tutorials">
          <i class="fa-solid fa-play-circle" aria-hidden="true"></i>
          <span itemprop="name">Interactive Video Lessons</span>
        </span>
                </li>
                <!-- 2. Hands-on PHP coding projects -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="2" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-semibold text-neutral-900 shadow" title="Hands-on PHP coding projects">
          <i class="fa-solid fa-code" aria-hidden="true"></i>
          <span itemprop="name">Hands-on PHP Projects</span>
        </span>
                </li>
                <!-- 3. One-on-one mentor support -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="3" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/90 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-rose-500/60" title="Personal mentor coaching and code review">
          <i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i>
          <span itemprop="name">1-on-1 Mentor Support</span>
        </span>
                </li>
                <!-- 4. Knowledge tests & quizzes -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="4" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-semibold text-neutral-900 shadow" title="PHP quizzes and knowledge checks">
          <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
          <span itemprop="name">Quizzes &amp; Knowledge Tests</span>
        </span>
                </li>
                <!-- 5. Step-by-step learning paths -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="5" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/90 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-rose-500/60" title="Structured PHP learning paths for beginners and pros">
          <i class="fa-solid fa-map-signs" aria-hidden="true"></i>
          <span itemprop="name">Step-by-Step Learning Paths</span>
        </span>
                </li>
                <!-- 6. Practical design patterns -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="6" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-semibold text-neutral-900 shadow" title="Real-world PHP design patterns explained">
          <i class="fa-solid fa-puzzle-piece" aria-hidden="true"></i>
          <span itemprop="name">Practical Design Patterns</span>
        </span>
                </li>
                <!-- 7. SOLID best practices -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="7" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/90 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-rose-500/60" title="Master SOLID principles in PHP OOP">
          <i class="fa-solid fa-cubes" aria-hidden="true"></i>
          <span itemprop="name">SOLID Best Practices</span>
        </span>
                </li>
                <!-- 8. GitHub source-code access -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="8" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-semibold text-neutral-900 shadow" title="Access to full source code on GitHub">
          <i class="fa-brands fa-github" aria-hidden="true"></i>
          <span itemprop="name">GitHub Source Code</span>
        </span>
                </li>
                <!-- 9. Developer community & forum -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="9" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/90 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-rose-500/60" title="Active PHP developer community and forum">
          <i class="fa-solid fa-comments" aria-hidden="true"></i>
          <span itemprop="name">Community &amp; Forum</span>
        </span>
                </li>
                <!-- 10. Completion certificate -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="10" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-semibold text-neutral-900 shadow" title="Official course completion certificate">
          <i class="fa-solid fa-certificate" aria-hidden="true"></i>
          <span itemprop="name">Completion Certificate</span>
        </span>
                </li>
                <!-- 11. Live coding sessions -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="11" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/90 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-rose-500/60" title="Weekly live coding streams & workshops">
          <i class="fa-solid fa-laptop-code" aria-hidden="true"></i>
          <span itemprop="name">Live Coding Sessions</span>
        </span>
                </li>
                <!-- 12. Weekly PHP newsletter -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="12" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-semibold text-neutral-900 shadow" title="Weekly PHP and backend development newsletter">
          <i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i>
          <span itemprop="name">Weekly PHP Newsletter</span>
        </span>
                </li>

                <!-- 13. GitHub example projects -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="8" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-semibold text-neutral-900 shadow" title="Open-source example projects on GitHub">
          <i class="fa-brands fa-github" aria-hidden="true"></i>
          <span itemprop="name">GitHub Example Projects</span>
        </span>
                </li>
                <!-- 14. Dev community comments -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="9" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-rose-500/90 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-rose-500/60" title="Discuss and ask questions in comments">
          <i class="fa-solid fa-comments" aria-hidden="true"></i>
          <span itemprop="name">Community Q&A</span>
        </span>
                </li>
                <!-- 15. Free PDF guides -->
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <meta itemprop="position" content="10" />
                    <span class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-semibold text-neutral-900 shadow" title="Free downloadable PDF reference guides">
          <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
          <span itemprop="name">Free PDF Guides</span>
        </span>
                </li>
            </ul>
        </div>
    </section>

    <section id="faq" class="bg-neutral-800 py-20 lg:py-28" aria-label="Frequently asked questions about my PHP learning blog">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <header class="mb-12 text-center">
                <h2 class="text-3xl font-extrabold text-white md:text-4xl">
                    Learning&nbsp;Hub <span class="text-rose-400">FAQ</span>
                </h2>
                <p class="mx-auto mt-3 max-w-3xl text-neutral-300">
                    Answers to the most common questions about my articles, publishing schedule and how you can get involved.
                </p>
            </header>

            <!-- FAQ accordion using <details> for accessibility -->
            <div class="space-y-4" itemscope itemtype="https://schema.org/FAQPage">
                <!-- Q1 -->
                <details class="group rounded-lg bg-neutral-900 p-6 shadow-lg">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-lg font-medium text-rose-400">
                        <span itemprop="name">What kind of content will I find here?</span>
                        <i class="fa-solid fa-chevron-down transition-transform duration-200 group-open:rotate-180"></i>
                    </summary>
                    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer" class="mt-4 text-neutral-300">
                        <p itemprop="text">
                            You will find in-depth blog posts, practical tutorials, code snippets, cheat sheets and occasional live-coding sessions – all focused on modern PHP, MySQL and backend development techniques.
                        </p>
                    </div>
                </details>
                <!-- Q2 -->
                <details class="group rounded-lg bg-neutral-900 p-6 shadow-lg">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-lg font-medium text-rose-400">
                        <span itemprop="name">Do I need previous programming experience?</span>
                        <i class="fa-solid fa-chevron-down transition-transform duration-200 group-open:rotate-180"></i>
                    </summary>
                    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer" class="mt-4 text-neutral-300">
                        <p itemprop="text">
                            Not at all. Many articles start from the basics and offer incremental challenges. Beginners and seasoned developers alike can benefit – just pick the level that suits you.
                        </p>
                    </div>
                </details>
                <!-- Q3 -->
                <details class="group rounded-lg bg-neutral-900 p-6 shadow-lg">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-lg font-medium text-rose-400">
                        <span itemprop="name">How often do you publish new articles?</span>
                        <i class="fa-solid fa-chevron-down transition-transform duration-200 group-open:rotate-180"></i>
                    </summary>
                    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer" class="mt-4 text-neutral-300">
                        <p itemprop="text">
                            I aim to release a fresh tutorial or deep-dive every two weeks, plus a quick-tip newsletter every Friday. Follow me on Instagram or sign up for the email list so you never miss an update.
                        </p>
                    </div>
                </details>
                <!-- Q4 -->
                <details class="group rounded-lg bg-neutral-900 p-6 shadow-lg">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-lg font-medium text-rose-400">
                        <span itemprop="name">Can I request a topic or ask a question?</span>
                        <i class="fa-solid fa-chevron-down transition-transform duration-200 group-open:rotate-180"></i>
                    </summary>
                    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer" class="mt-4 text-neutral-300">
                        <p itemprop="text">
                            Absolutely! Leave a comment under any article or tweet me your idea. I prioritise topics that help the community the most.
                        </p>
                    </div>
                </details>
                <!-- Q5 -->
                <details class="group rounded-lg bg-neutral-900 p-6 shadow-lg">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-lg font-medium text-rose-400">
                        <span itemprop="name">What's the best way to stay updated?</span>
                        <i class="fa-solid fa-chevron-down transition-transform duration-200 group-open:rotate-180"></i>
                    </summary>
                    <div itemprop="acceptedAnswer" itemscope itemtype="https://schema.org/Answer" class="mt-4 text-neutral-300">
                        <p itemprop="text">
                            Subscribe to the free PHP newsletter. I also post quick updates on LinkedIn and Mastodon.
                        </p>
                    </div>
                </details>
            </div>
        </div>
    </section>

    <section id="about" class="bg-neutral-900 py-20 lg:py-28" aria-label="About the author – PHP developer and technical writer">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 flex flex-col items-center text-center" itemscope itemtype="https://schema.org/Person">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-3" itemprop="name">Jakub "oattlo" Owsianka</h2>
{{--            <p class="text-neutral-300 mb-6 max-w-2xl" itemprop="description">--}}
{{--                Senior PHP developer, open‑source contributor and tech writer. I share hands‑on tutorials and insider tips from <span class="font-semibold">15+ years</span> in backend development, DevOps and database optimisation.--}}
{{--            </p>--}}

{{--            <!-- Highlights -->--}}
{{--            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">--}}
{{--                <li class="flex items-center gap-3 text-neutral-200"><i class="fa-solid fa-award text-rose-400"></i> Speaker at PHPers Summit &amp; SymfonyLive</li>--}}
{{--                <li class="flex items-center gap-3 text-neutral-200"><i class="fa-brands fa-github text-rose-400"></i> 30k+ GitHub stars across projects</li>--}}
{{--                <li class="flex items-center gap-3 text-neutral-200"><i class="fa-solid fa-briefcase text-rose-400"></i> Ex‑lead dev at SaaS scale‑ups</li>--}}
{{--            </ul>--}}

{{--            <!-- Social links -->--}}
{{--            <div class="mt-8 flex gap-6">--}}
{{--                <a href="https://twitter.com/{YOUR_HANDLE}" target="_blank" rel="noopener" class="text-neutral-400 hover:text-rose-400" aria-label="Follow on Twitter"><i class="fa-brands fa-x-twitter fa-lg"></i></a>--}}
{{--                <a href="https://github.com/{YOUR_GITHUB}" target="_blank" rel="noopener" class="text-neutral-400 hover:text-rose-400" aria-label="GitHub profile"><i class="fa-brands fa-github fa-lg"></i></a>--}}
{{--                <a href="https://linkedin.com/in/{YOUR_LINKEDIN}" target="_blank" rel="noopener" class="text-neutral-400 hover:text-rose-400" aria-label="LinkedIn profile"><i class="fa-brands fa-linkedin fa-lg"></i></a>--}}
{{--            </div>--}}
        </div>
    </section>

{{--    <!-- ===============================================--}}
{{--      SECTION 4 – NEWSLETTER CTA--}}
{{--      ================================================== -->--}}
{{--    <section id="newsletter" class="relative bg-rose-600/10 py-20 lg:py-28 backdrop-blur-lg">--}}
{{--        <div class="absolute inset-0 pointer-events-none select-none" aria-hidden="true">--}}
{{--            <!-- decorative gradient / pattern -->--}}
{{--            <div class="h-full w-full bg-gradient-to-br from-transparent via-rose-500/10 to-rose-600/20"></div>--}}
{{--        </div>--}}
{{--        <div class="relative mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">--}}
{{--            <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-4">--}}
{{--                Join <span class="text-rose-400">3,000+</span> developers getting free PHP tips every Friday--}}
{{--            </h2>--}}
{{--            <p class="text-neutral-200 mb-8 max-w-2xl mx-auto">--}}
{{--                No spam — just practical articles, fresh code examples and hand‑picked links that will make you a better backend developer.--}}
{{--            </p>--}}

{{--            <!-- Subscribe form -->--}}
{{--            <form class="mx-auto flex max-w-md flex-col sm:flex-row gap-3" action="#" method="post" aria-label="Subscribe to PHP newsletter" itemscope itemtype="https://schema.org/SubscribeAction">--}}
{{--                <meta itemprop="name" content="Subscribe to weekly PHP newsletter" />--}}
{{--                <input type="email" name="email" placeholder="you@example.com" required class="w-full rounded-xl border border-transparent bg-white/10 p-3 text-white placeholder-neutral-400 focus:border-rose-400 focus:outline-none" itemprop="participant" />--}}
{{--                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-rose-500 px-6 py-3 font-semibold text-white shadow-md shadow-rose-500/40 transition hover:bg-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-300">--}}
{{--                    <i class="fa-solid fa-envelope-open-text mr-2" aria-hidden="true"></i> Subscribe--}}
{{--                </button>--}}
{{--            </form>--}}

{{--            <!-- Benefit bullets -->--}}
{{--            <ul class="mt-6 flex flex-col items-center gap-2 text-sm text-neutral-300 sm:flex-row sm:justify-center">--}}
{{--                <li class="flex items-center gap-2"><i class="fa-solid fa-check text-rose-400"></i> Actionable tips</li>--}}
{{--                <li class="flex items-center gap-2"><i class="fa-solid fa-check text-rose-400"></i> Curated resources</li>--}}
{{--                <li class="flex items-center gap-2"><i class="fa-solid fa-check text-rose-400"></i> Unsubscribe anytime</li>--}}
{{--            </ul>--}}
{{--        </div>--}}
{{--    </section>--}}

</div>


</body>
</html>
