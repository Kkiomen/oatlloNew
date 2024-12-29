<!doctype html>
<html lang="{{ env('APP_LANG_HTML') }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('basic.meta_title') }}</title>
    <meta name="description" content="{{ __('basic.meta_description') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                            </div>
                            {{--                            <div class="py-6">--}}
                            {{--                                <a href="#" class="-mx-3 block rounded-lg px-3 py-2.5 text-base/7 font-semibold text-white hover:bg-gray-800">Log in</a>--}}
                            {{--                            </div>--}}
                        </div>
                    </div>
                </div>
            </div>
        </header>



        <div class="relative isolate pt-14 bg-gray-800 py-24 sm:py-32">
            <div class="mx-auto max-w-2xl px-6 lg:max-w-7xl pt-14 md:pt-0 lg:px-8">
                <h2 class="text-base/7 font-semibold text-white">{{ __('basic.courses_header_h1') }}</h2>
                <p class="mt-2 max-w-lg text-4xl font-semibold tracking-tight text-pretty text-white sm:text-5xl">{{ __('basic.courses_header_h2') }}</p>
                <div class="mt-10 grid grid-cols-1 gap-4 sm:mt-16 lg:grid-cols-6 lg:grid-rows-2">

                    @foreach($courses as $course)
                        <div class="relative lg:col-span-3">
                            <div class="absolute inset-px rounded-lg bg-white max-lg:rounded-t-[2rem] lg:rounded-tl-[2rem]"></div>
                            <div class="relative flex h-full flex-col overflow-hidden rounded-[calc(var(--radius-lg)+1px)] max-lg:rounded-t-[calc(2rem+1px)] lg:rounded-tl-[calc(2rem+1px)]">
                                @php
                                    $currentImage = empty($course->image) ? 'storage/uploads/empty_image.jpg' : $course->image;
                                    $pattern = "/asset\('(.+?)'\)/";
                                    if (preg_match($pattern, $currentImage, $matches)) {
                                        $currentImage = $matches[1];
                                    }
                                    $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);
                                @endphp

                                <img class="h-80 object-cover object-left" src="{{ $currentImage }}" alt="">

                                @php
                                    if($defaultLangue == 'pl'){
                                        $urlToCourse = route('course_pl', ['courseName' => $course->slug ]);
                                    }else{
                                        $urlToCourse = route('course_en', ['courseName' => $course->slug ]);
                                    }
                                 @endphp

                                <a href="{{ $urlToCourse }}">
                                    <div class="p-10 pt-4">
                                        <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">{{ $course->title_list }}</p>
                                        <p class="mt-2 max-w-lg text-sm/6 text-gray-600">{{ $course->description_list }}</p>
                                    </div>
                                </a>
                            </div>
                            <div class="pointer-events-none absolute inset-px rounded-lg ring-1 shadow-sm ring-black/5 max-lg:rounded-t-[2rem] lg:rounded-tl-[2rem]"></div>
                        </div>
                    @endforeach


{{--                    <div class="relative lg:col-span-3">--}}
{{--                        <div class="absolute inset-px rounded-lg bg-white lg:rounded-tr-[2rem]"></div>--}}
{{--                        <div class="relative flex h-full flex-col overflow-hidden rounded-[calc(var(--radius-lg)+1px)] lg:rounded-tr-[calc(2rem+1px)]">--}}
{{--                            <img class="h-80 object-cover object-left lg:object-right" src="https://tailwindui.com/plus/img/component-images/bento-01-releases.png" alt="">--}}
{{--                            <div class="p-10 pt-4">--}}
{{--                                <h3 class="text-sm/4 font-semibold text-indigo-600">Releases</h3>--}}
{{--                                <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">Push to deploy</p>--}}
{{--                                <p class="mt-2 max-w-lg text-sm/6 text-gray-600">Curabitur auctor, ex quis auctor venenatis, eros arcu rhoncus massa, laoreet dapibus ex elit vitae odio.</p>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="pointer-events-none absolute inset-px rounded-lg ring-1 shadow-sm ring-black/5 lg:rounded-tr-[2rem]"></div>--}}
{{--                    </div>--}}


{{--                    <div class="relative lg:col-span-2">--}}
{{--                        <div class="absolute inset-px rounded-lg bg-white lg:rounded-bl-[2rem]"></div>--}}
{{--                        <div class="relative flex h-full flex-col overflow-hidden rounded-[calc(var(--radius-lg)+1px)] lg:rounded-bl-[calc(2rem+1px)]">--}}
{{--                            <img class="h-80 object-cover object-left" src="https://tailwindui.com/plus/img/component-images/bento-01-speed.png" alt="">--}}
{{--                            <div class="p-10 pt-4">--}}
{{--                                <h3 class="text-sm/4 font-semibold text-indigo-600">Speed</h3>--}}
{{--                                <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">Built for power users</p>--}}
{{--                                <p class="mt-2 max-w-lg text-sm/6 text-gray-600">Sed congue eros non finibus molestie. Vestibulum euismod augue.</p>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="pointer-events-none absolute inset-px rounded-lg ring-1 shadow-sm ring-black/5 lg:rounded-bl-[2rem]"></div>--}}
{{--                    </div>--}}
{{--                    <div class="relative lg:col-span-2">--}}
{{--                        <div class="absolute inset-px rounded-lg bg-white"></div>--}}
{{--                        <div class="relative flex h-full flex-col overflow-hidden rounded-[calc(var(--radius-lg)+1px)]">--}}
{{--                            <img class="h-80 object-cover" src="https://tailwindui.com/plus/img/component-images/bento-01-integrations.png" alt="">--}}
{{--                            <div class="p-10 pt-4">--}}
{{--                                <h3 class="text-sm/4 font-semibold text-indigo-600">Integrations</h3>--}}
{{--                                <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">Connect your favorite tools</p>--}}
{{--                                <p class="mt-2 max-w-lg text-sm/6 text-gray-600">Maecenas at augue sed elit dictum vulputate, in nisi aliquam maximus arcu.</p>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="pointer-events-none absolute inset-px rounded-lg ring-1 shadow-sm ring-black/5"></div>--}}
{{--                    </div>--}}
{{--                    <div class="relative lg:col-span-2">--}}
{{--                        <div class="absolute inset-px rounded-lg bg-white max-lg:rounded-b-[2rem] lg:rounded-br-[2rem]"></div>--}}
{{--                        <div class="relative flex h-full flex-col overflow-hidden rounded-[calc(var(--radius-lg)+1px)] max-lg:rounded-b-[calc(2rem+1px)] lg:rounded-br-[calc(2rem+1px)]">--}}
{{--                            <img class="h-80 object-cover" src="https://tailwindui.com/plus/img/component-images/bento-01-network.png" alt="">--}}
{{--                            <div class="p-10 pt-4">--}}
{{--                                <h3 class="text-sm/4 font-semibold text-indigo-600">Network</h3>--}}
{{--                                <p class="mt-2 text-lg font-medium tracking-tight text-gray-950">Globally distributed CDN</p>--}}
{{--                                <p class="mt-2 max-w-lg text-sm/6 text-gray-600">Aenean vulputate justo commodo auctor vehicula in malesuada semper.</p>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="pointer-events-none absolute inset-px rounded-lg ring-1 shadow-sm ring-black/5 max-lg:rounded-b-[2rem] lg:rounded-br-[2rem]"></div>--}}
{{--                    </div>--}}
                </div>
            </div>
        </div>
    </div>








</div>
</body>
