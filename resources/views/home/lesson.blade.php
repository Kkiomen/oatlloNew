<!doctype html>
<html lang="{{ env('APP_LANG_HTML') }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $article->view_content['basic_website_structure_title'] }}</title>
    <meta name="description" content="{{ $article->view_content['basic_website_structure_description'] }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="index, follow">


    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">

    <link rel="canonical" href="{{ $courseCategory->getRoute() }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <meta property="og:title" content="{{ $courseCategory->title_seo }}">
    <meta property="og:description" content="{{ $courseCategory->description_seo }}">
    {{--    <meta property="og:image" content="{{ $basic_website_structure_op_image_img_file }}">--}}
    <meta property="og:url" content="{{ route('index') }}">


    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <link rel="stylesheet" href="{{ asset('/assets/css/article-style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>
</head>
<body>
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
                        <a href="{{ route('index') }}" class="text-sm/6 font-semibold text-white">{{ __('basic.home') }}</a>
                        <a href="{{ route('blog') }}" class="text-sm/6 font-semibold text-white">Blog</a>
                        <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="text-sm/6 font-semibold text-white">{{ __('basic.courses') }}</a>
                    </div>
                    {{--                <div class="hidden lg:flex lg:flex-1 lg:justify-end">--}}
                    {{--                    <a href="#" class="text-sm/6 font-semibold text-white">Log in <span aria-hidden="true">&rarr;</span></a>--}}
                    {{--                </div>--}}
                </nav>
                <!-- Mobile menu, show/hide based on menu open state. -->
                <div class="lg:hidden" role="dialog" aria-modal="true" x-show="!open">
                    <!-- Background backdrop, show/hide based on slide-over state. -->
                    <div class="fixed inset-0 z-50"></div>
                    <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-gray-900 px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-white/10">
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
                            <div class="-my-6 divide-y divide-gray-500/25">
                                <div class="space-y-2 py-6">
                                    <a href="{{ route('index') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-white hover:bg-gray-800">{{ __('basic.home') }}</a>
                                    <a href="{{ route('blog') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-white hover:bg-gray-800">Blog</a>
                                    <a href="{{ \App\Services\HomeService::getRouteCourses() }}" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-white hover:bg-gray-800">{{ __('basic.courses') }}</a>
                                </div>
                                {{--                            <div class="py-6">--}}
                                {{--                                <a href="#" class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-white hover:bg-gray-800">Log in</a>--}}
                                {{--                            </div>--}}
                            </div>
                        </div>
                    </div>
                </div>
            </header>



            <div class="relative isolate bg-gray-800 pt-24 pb-10 sm:pt-32">
                <div class="mx-auto max-w-2xl px-6 lg:max-w-7xl pt-14 md:pt-0 lg:px-8">
                    <nav class="flex mb-8" aria-label="Breadcrumb">
                        <ol role="list" class="flex items-center space-x-4">
                            <li>
                                <div>
                                    <a href="#" class="text-gray-400 hover:text-gray-500">
                                        <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                            <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="sr-only">Home</span>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                    <a href="{{ $course->getRoute() }}" class="ml-4 text-sm font-medium text-gray-300 hover:text-gray-700">{{ $course->name }}</a>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                    <a href="{{ $category->getRoute() }}" class="ml-4 text-sm font-medium text-gray-300 hover:text-gray-700" aria-current="page">{{ $category->category_name }}</a>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="size-5 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                    <a href="#" class="ml-4 text-sm font-medium text-gray-300 hover:text-gray-700" aria-current="page">{{ $article->name }}</a>
                                </div>
                            </li>
                        </ol>
                    </nav>

                    <h1 class="mt-2 max-w-3/4 text-3xl font-semibold tracking-tight text-pretty text-white sm:text-3xl">{!! $article->name !!}</h1>
                    <h2 class=" font-semibold text-white mt-3">{{ $course->name }}</h2>
                </div>
            </div>

        </div>



        <div class="jetbrains_bg_color px-0 md:px-6 md:py-16 lg:px-8" id="article-content">

            <div class="flex flex-col lg:flex-row lg:space-x-4">
                <!-- Element zajmujący 3/4 miejsca -->
                <div class="lg:basis-3/4 w-full">
                    <div class="jetbrains_bg_color p-5 md:p-10 text-gray-300 rounded-xl article-content-theme">
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
                    </div>
                </div>

                <!-- Element zajmujący 1/4 miejsca -->
                <div class="lg:basis-1/4 w-full">
                    <div class="jetbrains_bg_color p-5 md:p-10 text-gray-300 rounded-xl">
                        <div class="font-bold text-sm mb-10">Spis treści:</div>

                        @foreach($course->categories as $category)
                            <div class="mt-5">
                                <a href="{{ $category->getRoute() }}" class="hover:underline"><strong>{{ $category->category_name }}</strong></a>

                                <div class="mt-2">
                                    <ul>
                                        @foreach($category->lessons as $lesson)
                                            <li class="mt-1"><a href="{{ $lesson->getRouteCourse($category) }}" class="text-xs hover:text-white @if($article->name === $lesson->name ) underline font-bold text-amber-400 @endif ">{{ $lesson->name }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>


        <div class="flex flex-col lg:flex-row lg:space-x-4 bg-gray-900">
            @if(!empty($lessonSkip['previous']))
            <div class="lg:basis-1/2 w-full">
                <a href="{{ $lessonSkip['previous']['route'] }}">
                    <div class="p-3 border-l-2 border-gray-500" style="background-color: #99dafa">
                        <div class="font-bold">{{ $lessonSkip['previous']['name'] }}</div>
                        <div class="text-xs">{{ __('basic.go_to_back_lesson') }}</div>
                    </div>
                </a>
            </div>
            @endif
            @if(!empty($lessonSkip['next']))
            <div class="lg:basis-1/2 w-full" style="background-color:  #59fc62">
                <a href="{{ $lessonSkip['next']['route'] }}">
                    <div class="p-3 text-right">
                        <div class="font-bold">{{ $lessonSkip['next']['name'] }}</div>
                        <div class="text-xs">{{ __('basic.go_to_next_lesson') }}</div>
                    </div>
                </a>
            </div>
            @endif
        </div>

    </div>


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
