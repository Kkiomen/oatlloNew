<!doctype html>
<html lang="{{ env('APP_LANG_HTML') }}">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="index, follow">
    <title>{{ $article->view_content['basic_website_structure_title'] }}</title>
    <meta name="description" content="{{ $article->view_content['basic_website_structure_description'] }}">
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

    <meta property="og:site_name" content="Oatllo">
    <meta property="og:locale" content="{{ env('APP_LANG_HTML') }}">
    <meta property="og:title" content="{{ $article->view_content['basic_website_structure_op_title'] }}" />
    <meta property="og:description" content="{{ $article->view_content['basic_website_structure_op_description'] }}" />
    <meta property="og:url" content="{{ $article->getRoute() }}" />
    <meta property="og:image" content="{{ $article->view_content['basic_website_structure_op_image_img_file'] }}" />
    <meta property="og:type" content="article">
    <meta property="article:section" content="Programming">

    <meta property="article:published_time" content="{{ $article->created_at }}">
    <meta property="article:modified_time" content="{{ $article->updated_at }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $article->name }}">
    <meta name="twitter:description" content="{{ $article->view_content['basic_website_structure_op_description'] }}">
    <meta name="twitter:image" content="{{ $article->view_content['basic_website_structure_op_image_img_file'] }}">


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>


    @if(!empty($article->structure_data_google))
    <script type="application/ld+json">
        {!! $article->structure_data_google !!}
    </script>
    @endif

</head>
<body>
{!! \App\Services\HomeService::getTagManagerBODY() !!}




<div class="jetbrains_bg_color">
    <header class="absolute inset-x-0 top-0 z-50" x-data="{ open: true }">
        <div class="mx-auto max-w-7xl">
            <div class="px-6 pt-6 lg:max-w-2xl lg:pl-8 lg:pr-0">
                <nav class="flex items-center justify-between lg:justify-start" aria-label="Global">
                    <a href="{{ route('index') }}">
                        <div class="logo_oatllo">
                            oatllo
                        </div>
                    </a>
                    <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-700 lg:hidden"  @click="open = !open">
                        <span class="sr-only">Open main menu</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <div class="hidden lg:ml-12 lg:flex lg:gap-x-14">
                        <a href="{{ route('index') }}" class="text-sm font-semibold leading-6 text-gray-300">{{ __('basic.home') }}</a>
                        <a href="{{ route('blog') }}" class="text-sm font-semibold leading-6 text-gray-300">Blog</a>
                        <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="text-sm font-semibold leading-6 text-gray-300">{{ __('basic.courses') }}</a>
                    </div>
                </nav>
            </div>
        </div>
        <!-- Mobile menu, show/hide based on menu open state. -->
        <div class="lg:hidden" x-show="!open" role="dialog" aria-modal="true">
            <!-- Background backdrop, show/hide based on slide-over state. -->
            <div class="fixed inset-0 z-50"></div>
            <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10">
                <div class="flex items-center justify-between">
                    <a href="{{ route('index') }}">
                        <div class="logo_oatllo text-black" style="font-family: 'Montserrat', sans-serif; font-weight: 800">
                            oatllo
                        </div>
                    </a>
                    <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-700" @click="open = !open">
                        <span class="sr-only">Close menu</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mt-6 flow-root">
                    <div class="-my-6 divide-y divide-gray-500/10">
                        <div class="space-y-2 py-6">
                            <a href="{{ route('index') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">{{ __('basic.home') }}</a>
                            <a href="{{ route('blog') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">Blog</a>
                            <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 hover:bg-gray-50">{{ __('basic.courses') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="relative">
        <div class="mx-auto max-w-7xl">
            <div class="relative z-10 pt-14 lg:w-full lg:max-w-2xl">
                <svg class="absolute inset-y-0 right-8 hidden h-full w-80 translate-x-1/2 transform jetbrains_fill_color lg:block" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" style="fill: #1e1f22;">
                    <polygon points="0,0 90,0 50,100 0,100" />
                </svg>

                <div class="relative px-6 py-10 sm:py-10 lg:px-8 lg:py-20 lg:pr-0">
                    <div class="mx-auto max-w-2xl lg:mx-0 lg:max-w-xl">

                        <nav class="flex mb-16" aria-label="Breadcrumb">
                            <ol role="list" class="flex items-center space-x-4">
                                <li>
                                    <div>
                                        <a href="{{ route('index') }}" class="text-gray-300">
                                            <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                                <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd" />
                                            </svg>
                                            <span class="sr-only">Strona główna</span>
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                        </svg>
                                        <a href="{{ route('blog') }}" class="ml-4 text-xs sm:text-sm font-medium text-gray-300 hover:text-gray-200">Blog</a>
                                    </div>
                                </li>
                                @if($category)
                                    <li>
                                        <div class="flex items-center">
                                            <svg class="h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                                <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                            </svg>
                                            <a href="{{ route('blog.list.category', ['slug' => $category->slug]) }}" class="ml-4 text-xs sm:text-sm font-medium text-gray-300 hover:text-gray-200">{{ $category->name }}</a>
                                        </div>
                                    </li>
                                @endif
                                <li>
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                        </svg>
                                        <a href="#" class="ml-4 text-xs sm:text-sm font-medium text-gray-300 hover:text-gray-200" aria-current="page">{{ $article->name }}</a>
                                    </div>
                                </li>
                            </ol>
                        </nav>



                        <h1 class="text-3xl font-bold mt-3 sm:mt-0 tracking-tight text-gray-300 sm:text-4xl">{{ $article->name }}</h1>
                        <p class="mt-6 text-lg leading-8 text-gray-400">{{ $article->short_description }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2">
            <img class="aspect-[3/2] object-cover object-center lg:aspect-auto lg:h-full lg:w-full" src="{{ $article->image }}" alt="{{ !empty($article->view_content['basic_website_structure_image_img_alt']) ? $article->view_content['basic_website_structure_image_img_alt'] : $article->name }}">
        </div>
    </div>
</div>


<div class="jetbrains_bg_color px-0 md:px-6 md:py-16 lg:px-8" id="article-content">

    <div class="mx-auto max-w-full md:max-w-7xl text-base leading-7 jetbrains_bg_color p-5 md:p-10 text-gray-300 rounded-xl article-content-theme">

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

        @if(!$article->tags->isEmpty())
            <div class="mt-20 tags_list">
                <div class="font-bold text-lg">{{ __('basic.tags') }}:</div>

                <div class="mt-3">
                    @foreach($article->tags as $tag)
                        <a href="{{ route('blogTag', ['tag' => Str::slug($tag->name)]) }}" class="inline-block px-4 py-1 hover:bg-indigo-600 border border-indigo-600 mx-2 my-2 rounded-2xl">
                            {{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@if($randomArticles->count() > 0)

    <div class="jetbrains_bg_color pb-10 pt-10 sm:pt-0">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold tracking-tight text-gray-300 sm:text-4xl">{{ __('basic.see_other_posts') }}</h2>
            </div>
            <div class="mx-auto mt-16 grid max-w-2xl auto-rows-fr grid-cols-1 gap-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">

                @foreach($randomArticles as $currentArticle)
                    <article class="relative isolate flex flex-col justify-end overflow-hidden rounded-2xl bg-gray-900 px-8 pb-8 pt-80 sm:pt-48 lg:pt-80">
                        <img src="{{ $currentArticle->image }}" alt="{{ $currentArticle->name }}" class="absolute inset-0 -z-10 h-full w-full object-cover">
                        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-gray-900 via-gray-900/40"></div>
                        <div class="absolute inset-0 -z-10 rounded-2xl ring-1 ring-inset ring-gray-900/10"></div>

                        <div class="flex flex-wrap items-center gap-y-1 overflow-hidden text-sm leading-6 text-gray-300">
                            <time datetime="{{ $currentArticle->created_at->format('Y-m-d') }}" class="mr-8">{{ $currentArticle->created_at->format('Y-m-d') }}</time>
                        </div>
                        <h3 class="mt-3 text-lg font-semibold leading-6 text-white">
                            <a href="{{ $currentArticle->getRoute() }}">
                                <span class="absolute inset-0"></span>
                                {{ $currentArticle->name }}
                            </a>
                        </h3>
                    </article>
                @endforeach

            </div>
        </div>
    </div>

@endif

<footer class="jetbrains_bg_color">
    <div class="mx-auto max-w-7xl px-6 pb-8 lg:px-8 ">
        <div class="border-t border-white/10 pt-8 md:flex md:items-center md:justify-between">
            <p class="mt-8 text-xs leading-5 text-gray-400 md:order-1 md:mt-0">&copy; {{ date('Y') }} oattlo</p>
        </div>
    </div>
</footer>

<script>hljs.highlightAll();</script>
<script src="{{ asset('/assets/js/script.js') }}"></script>
</body>
</html>
