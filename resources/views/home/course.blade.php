<!doctype html>
<html lang="{{ env('APP_LANG_HTML') }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $course->title_seo }}</title>
    <meta name="description" content="{{ $course->description_seo }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="index, follow">


    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">

    <link rel="canonical" href="{{ $urlToCourse }}">
    <meta name="keywords" content="{{ __('basic.meta_keywords') }}">

    <meta property="og:title" content="{{ $course->title_seo }}">
    <meta property="og:description" content="{{ $course->description_seo }}">
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
                    <a href="{{ route('courses') }}" class="text-sm/6 font-semibold text-white">{{ __('basic.courses') }}</a>
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
                                <a href="{{ route('courses') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base/7 font-semibold text-white hover:bg-gray-800">{{ __('basic.courses') }}</a>
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
                <h1 class="mt-2 max-w-3/4 text-4xl font-semibold tracking-tight text-pretty text-white sm:text-5xl">{!! $course->title_full !!}</h1>
                <h2 class="text-base/7 max-w-3/4 font-semibold text-white mt-3">{!! $course->description_full !!}</h2>


                <div class="relative overflow-hidden pt-16">
                    <div class="mx-auto max-w-7xl px-6 lg:px-8">
                        @php
                            $currentImage = empty($course->image) ? 'storage/uploads/empty_image.jpg' : $course->image;
                            $pattern = "/asset\('(.+?)'\)/";
                            if (preg_match($pattern, $currentImage, $matches)) {
                                $currentImage = $matches[1];
                            }
                            $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);
                        @endphp
                        <img src="{{ $currentImage }}" alt="App screenshot" class="rounded-xl ring-1 shadow-2xl object-cover object-center ring-white/10" width="2432" height="1442">
                        <div class="relative" aria-hidden="true">
                            <div class="absolute -inset-x-20 bottom-0 bg-linear-to-t from-gray-900 pt-[7%]"></div>
                        </div>
                    </div>
                </div>


                <div class="relative overflow-hidden pt-16">
                    <a href="#">
                        <div class="text-white text-center p-3 shadow-2xl rounded-xl " style="background-color: #2d9a53">
                            {{ __('basic.go_to_course') }}
                        </div>
                    </a>
                </div>



                <div class="bg-white py-24 sm:py-12 sm:pb-24 mt-10 rounded-xl pb-4">
                    <div class="mx-auto max-w-7xl px-6 lg:px-8">
                        <div class="mx-auto lg:mx-0">
                            {!! $course->content_description_offers !!}
                        </div>
                        <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
                            <div class="flow-root">
                                <ul role="list" class="-mb-8">

                                    @foreach($course->categories as $category)
                                        <li>
                                            <div class="relative pb-8">
                                                <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-300" aria-hidden="true"></span>
                                                <div class="relative flex items-start space-x-3">
                                                    <div>
                                                        <div class="relative px-1">
                                                            <div class="flex size-8 items-center justify-center rounded-full bg-gray-400 ring-8 ring-white">
                                                                <svg class="size-5 text-gray-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                                                    <path fill-rule="evenodd" d="M4.5 2A2.5 2.5 0 0 0 2 4.5v3.879a2.5 2.5 0 0 0 .732 1.767l7.5 7.5a2.5 2.5 0 0 0 3.536 0l3.878-3.878a2.5 2.5 0 0 0 0-3.536l-7.5-7.5A2.5 2.5 0 0 0 8.38 2H4.5ZM5 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                                                </svg>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="min-w-0 flex-1 py-0">
                                                        <div class="text-sm/8 text-gray-500">
                                                            <div class="mr-0.5">
                                                                <h3>
                                                                    <a href="{{ $category->getRoute() }}" class="font-medium text-lg text-gray-900 hover:text-gray-700 hover:underline">{{ $category->category_name }}</a>
                                                                    <span class="ml-2">
                                                                    <a href="{{ $category->getRoute() }}" class="inline-flex items-center gap-x-1.5 rounded-full px-2 py-1 text-xs font-medium text-gray-900 ring-1 ring-gray-200 ring-inset">
                                                                      <svg class="size-1.5 fill-indigo-500" viewBox="0 0 6 6" aria-hidden="true">
                                                                        <circle cx="3" cy="3" r="3" />
                                                                      </svg>
                                                                      {{ __('basic.chapter') }}
                                                                    </a>
                                                                </span>
                                                                </h3>
                                                            </div>


                                                            <div class="mt-4">
                                                                @foreach($category->lessons as $lesson)
                                                                    <div class="py-1">
                                                                        <h4><a href="#" class="font-medium text-gray-900 hover:text-blue-600">{{ $lesson->name }}</a></h4>
                                                                        {{ $lesson->short_description }}
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
                    </div>
                </div>



                <div class="relative overflow-hidden pt-16">
                    <a href="#">
                        <div class="text-white text-center p-3 shadow-2xl rounded-xl " style="background-color: #2d9a53">
                            {{ __('basic.go_to_course') }}
                        </div>
                    </a>
                </div>




{{--                <div class="bg-white mt-10 rounded-2xl">--}}
{{--                    <div class="mx-auto max-w-7xl px-6 py-24 sm:pt-32 lg:px-8 lg:py-40">--}}
{{--                        <div class="lg:grid lg:grid-cols-12 lg:gap-8">--}}
{{--                            <div class="lg:col-span-5">--}}
{{--                                <h2 class="text-3xl font-semibold tracking-tight text-pretty text-gray-900 sm:text-4xl">Frequently asked questions</h2>--}}
{{--                                <p class="mt-4 text-base/7 text-pretty text-gray-600">Can’t find the answer you’re looking for? Reach out to our <a href="#" class="font-semibold text-indigo-600 hover:text-indigo-500">customer support</a> team.</p>--}}
{{--                            </div>--}}
{{--                            <div class="mt-10 lg:col-span-7 lg:mt-0">--}}
{{--                                <dl class="space-y-10">--}}
{{--                                    <div>--}}
{{--                                        <dt class="text-base/7 font-semibold text-gray-900">How do you make holy water?</dt>--}}
{{--                                        <dd class="mt-2 text-base/7 text-gray-600">You boil the hell out of it. Lorem ipsum dolor sit amet consectetur adipisicing elit. Quas cupiditate laboriosam fugiat.</dd>--}}
{{--                                    </div>--}}

{{--                                    <!-- More questions... -->--}}
{{--                                </dl>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}



{{--                <div class="relative overflow-hidden pt-16">--}}
{{--                    <a href="#">--}}
{{--                        <div class="text-white text-center p-3 shadow-2xl rounded-xl " style="background-color: #2d9a53">--}}
{{--                            {{ __('basic.go_to_course') }}--}}
{{--                        </div>--}}
{{--                    </a>--}}
{{--                </div>--}}




            </div>
        </div>
    </div>








</div>
</body>
