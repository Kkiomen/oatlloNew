<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $basic_website_structure_title }}</title>
    <meta name="description" content="{{ $basic_website_structure_description }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="index, follow">


    <link rel="icon" href="{{ $basic_website_structure_favicon_img_file }}" type="image/x-icon">

    <link rel="canonical" href="{{ $basic_website_structure_canonical }}">
    <meta name="keywords" content="{{ $basic_website_structure_keywords }}">

    <meta property="og:title" content="{{ $basic_website_structure_op_title }}">
    <meta property="og:description" content="{{ $basic_website_structure_op_description }}">
    <meta property="og:image" content="{{ $basic_website_structure_op_image_img_file }}">
    <meta property="og:url" content="{{ $basic_website_structure_op_url }}">


    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>
<body>



<div class="bg-gray-900">
    <header class="absolute inset-x-0 top-0 z-50" x-data="{ open: true }">
        <nav class="flex items-center justify-between p-6 lg:px-8" aria-label="Global">
            <div class="flex lg:flex-1">
                <div class="text-white uppercase" id="logo_header" style="font-family: 'Montserrat', sans-serif; font-weight: 800">
                    {{ $header_title }}
                </div>
            </div>
            <div class="flex lg:hidden">
                <button type="button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-400"  @click="open = !open">
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
            <div class="hidden lg:flex lg:gap-x-12" id="navbar_desktop">
                <a href="{{ $navbar_1_btn_href }}" class="text-sm font-semibold leading-6 text-white">{{ $navbar_1_btn_text }}</a>
                <a href="{{ $navbar_2_btn_href }}" class="text-sm font-semibold leading-6 text-white">{{ $navbar_2_btn_text }}</a>
                <a href="{{ $navbar_3_btn_href }}" class="text-sm font-semibold leading-6 text-white">{{ $navbar_3_btn_text }}</a>
            </div>
            <div class="hidden lg:flex lg:flex-1 lg:justify-end">

            </div>
        </nav>
        <!-- Mobile menu, show/hide based on menu open state. -->

        <div class="lg:hidden"  role="dialog" x-show="!open" aria-modal="true">
            <!-- Background backdrop, show/hide based on slide-over state. -->
            <div class="fixed inset-0 z-50"></div>
            <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-gray-900 px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-white/10">
                <div class="flex items-center justify-between">
                    <a href="#" class="-m-1.5 p-1.5">
                        <span class="sr-only">Your Company</span>
                    </a>
                    <button type="button" class="-m-2.5 rounded-md p-2.5 text-gray-400"  @click="open = !open">
                        <span class="sr-only">Close menu</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mt-6 flow-root">
                    <div class="-my-6 divide-y divide-gray-500/25">
                        <div class="space-y-2 py-6" id="navbar_mobile">
                            <a href="{{ $navbar_1_btn_href }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-gray-800">{{ $navbar_1_btn_text }}</a>
                            <a href="{{ $navbar_2_btn_href }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-gray-800">{{ $navbar_2_btn_text }}</a>
                            <a href="{{ $navbar_3_btn_href }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-gray-800">{{ $navbar_3_btn_text }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div class="relative isolate overflow-hidden pt-14" id="header_main">
        <img src="{{ $header_image_full_img_file  }}" alt="{{ $header_image_full_img_alt }}" class="absolute inset-0 -z-10 h-full w-full object-cover">
        <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
            <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
        </div>
        <div class="mx-auto max-w-2xl py-32 sm:py-48 lg:py-56">

            <div class="text-center px-3 sm:px-0">
                <h1 class="text-balance text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ $header_head_1 }}</h1>
                <p class="mt-6 text-lg leading-8 text-gray-300">{{ $header_head_2 }}</p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="{{ $header_button_1_btn_href }}" class="rounded-md bg-indigo-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-400">{{ $header_button_1_btn_text }}</a>
                    <a href="{{ $header_button_2_btn_href }}" class="text-sm font-semibold leading-6 text-white">{{ $header_button_2_btn_text }} <span aria-hidden="true">→</span></a>
                </div>
            </div>
        </div>
        <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
            <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
        </div>
    </div>
</div>




<div class="bg-gray-50">
    <div class="mx-auto max-w-7xl mt-8 px-4 sm:px-6 sm:py-14 lg:px-8" id="main_information">
        <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:grid-rows-2 sm:gap-x-6 lg:gap-8">
            <div class="group aspect-h-1 aspect-w-2 overflow-hidden rounded-lg sm:aspect-none sm:relative sm:row-span-2" id="main_information_one">
                <img src="{{ $three_columns_first_image_img_file }}" alt="{{ $three_columns_first_image_img_alt }}" class="object-cover object-center group-hover:opacity-75">
                <div aria-hidden="true" class="bg-gradient-to-b from-transparent to-blue-950 opacity-65 sm:absolute sm:inset-0"></div>
                <div class="flex items-end p-6 sm:absolute sm:inset-0 bg-blue-950 sm:bg-transparent">
                    <div>
                        <h3 class="font-semibold text-white">
                            <a href="{{ $three_columns_first_url_link }}">
                                <span class="absolute inset-0"></span>
                                {{ $three_columns_first_head }}
                            </a>
                        </h3>
                        <p aria-hidden="true" class="mt-1 text-sm text-white">{{ $three_columns_first_text }}</p>
                    </div>
                </div>
            </div>


            <div class="group aspect-h-1 aspect-w-2 overflow-hidden rounded-lg sm:aspect-none sm:relative sm:h-full" id="main_information_two">
                <img src="{{ $three_columns_second_image_img_file }}" alt="Wooden shelf with gray and olive drab green baseball caps, next to wooden clothes hanger with sweaters." class="object-cover object-center group-hover:opacity-75 sm:absolute sm:inset-0 sm:h-full sm:w-full">
                <div aria-hidden="true" class="bg-gradient-to-b from-transparent to-blue-950 opacity-65 sm:absolute sm:inset-0"></div>
                <div class="flex items-end p-6 sm:absolute sm:inset-0 bg-blue-950 sm:bg-transparent">
                    <div>
                        <h3 class="font-semibold text-white">
                            <a href="{{ $three_columns_second_url_link }}">
                                <span class="absolute inset-0"></span>
                                {{ $three_columns_second_head }}
                            </a>
                        </h3>
                        <p aria-hidden="true" class="mt-1 text-sm text-white">{{ $three_columns_second_text }}</p>
                    </div>
                </div>
            </div>


            <div class="group aspect-h-1 aspect-w-2 overflow-hidden rounded-lg sm:aspect-none sm:relative sm:h-full" id="main_information_three">
                <img src="{{ $three_columns_third_image_img_file }}" alt="Walnut desk organizer set with white modular trays, next to porcelain mug on wooden desk." class="object-cover object-center group-hover:opacity-75 sm:absolute sm:inset-0 sm:h-full sm:w-full">
                <div aria-hidden="true" class="bg-gradient-to-b from-transparent to-blue-950 opacity-65 sm:absolute sm:inset-0"></div>
                <div class="flex items-end p-6 sm:absolute sm:inset-0 bg-blue-950 sm:bg-transparent">
                    <div>
                        <h3 class="font-semibold text-white">
                            <a href="{{ $three_columns_third_url_link }}">
                                <span class="absolute inset-0"></span>
                                {{ $three_columns_third_head }}
                            </a>
                        </h3>
                        <p aria-hidden="true" class="mt-1 text-sm text-white">{{ $three_columns_third_text }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="mx-auto max-w-2xl px-4 mt-8 sm:px-6 lg:max-w-7xl" id="testimonials">
    <div class="relative overflow-hidden rounded-lg lg:h-96">
        <div class="absolute inset-0">
            <img src="{{ $element_testimonials_image_img_file }}" alt="" class="h-full w-full object-cover object-center">
        </div>
        <div aria-hidden="true" class="relative h-96 w-full lg:hidden"></div>
        <div aria-hidden="true" class="relative h-32 w-full lg:hidden"></div>
        <div class="absolute inset-x-0 bottom-0 rounded-bl-lg rounded-br-lg bg-blue-950 bg-opacity-75 p-6 backdrop-blur backdrop-filter sm:flex sm:items-center sm:justify-between lg:inset-x-auto lg:inset-y-0 lg:w-96 lg:flex-col lg:items-start lg:rounded-br-none lg:rounded-tl-lg">
            <div>
                <h2 class="text-xl font-bold text-white">{{ $element_testimonials_heading }}</h2>
                <p class="mt-1 text-sm text-gray-300">{{ $element_testimonials_paragraph }}</p>
            </div>
            <a href="{{ $element_testimonials_link_btn_href }}" class="mt-6 flex flex-shrink-0 items-center justify-center rounded-md border border-white border-opacity-25 bg-white bg-opacity-0 px-4 py-3 text-base font-medium text-white hover:bg-opacity-10 sm:ml-8 sm:mt-0 lg:ml-0 lg:w-full">{{ $element_testimonials_link_btn_text }}</a>
        </div>
    </div>
</div>


<div class="grid min-h-full grid-cols-1 grid-rows-2 lg:grid-cols-2 lg:grid-rows-1 mt-14" id="information_second">
    <div class="relative flex" id="information_second_one">
        <img src="{{ $element_information_second_one_image_img_file }}" alt="{{ $element_information_second_one_image_img_alt }}" class="absolute inset-0 h-full w-full object-cover object-center">
        <div class="relative flex w-full flex-col items-start justify-end bg-black bg-opacity-40 p-8 sm:p-12">
            <h2 class="text-lg font-medium text-white text-opacity-75">{{ $element_information_second_one_text1 }}</h2>
            <p class="mt-1 text-2xl font-medium text-white">{{ $element_information_second_one_text2 }}</p>
            <a href="{{ $element_information_second_one_button_btn_href }}" class="mt-4 rounded-md bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-50">{{ $element_information_second_one_button_btn_text }}</a>
        </div>
    </div>
    <div class="relative flex" id="information_second_two">
        <img src="{{ $element_information_second_two_image_img_file }}" alt="{{ $element_information_second_two_image_img_alt }}" class="absolute inset-0 h-full w-full object-cover object-center">
        <div class="relative flex w-full flex-col items-start justify-end bg-black bg-opacity-60 p-8 sm:p-12">
            <h2 class="text-lg font-medium text-white text-opacity-75">{{ $element_information_second_two_text1 }}</h2>
            <p class="mt-1 text-2xl font-medium text-white">{{ $element_information_second_two_text2 }}</p>
            <a href="{{ $element_information_second_two_button_btn_href }}" class="mt-4 rounded-md bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-50">{{ $element_information_second_two_button_btn_text }}</a>
        </div>
    </div>
</div>


<div class="bg-white py-24 sm:py-32" id="articles">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $element_article_heading }}</h2>
            <p class="mt-2 text-lg leading-8 text-gray-600">{{ $element_article_paragraph }}</p>
        </div>
        <div class="mx-auto mt-16 grid max-w-2xl auto-rows-fr grid-cols-1 gap-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">

            <article class="relative isolate flex flex-col justify-end overflow-hidden rounded-2xl bg-gray-900 px-8 pb-8 pt-80 sm:pt-48 lg:pt-80" >
                <img src="https://images.unsplash.com/photo-1496128858413-b36217c2ce36?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=3603&q=80" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover">
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-gray-900 via-gray-900/40"></div>
                <div class="absolute inset-0 -z-10 rounded-2xl ring-1 ring-inset ring-gray-900/10"></div>

                <div class="flex flex-wrap items-center gap-y-1 overflow-hidden text-sm leading-6 text-gray-300">
                    <time datetime="2020-03-16" class="mr-8">Mar 16, 20204</time>
                </div>
                <h3 class="mt-3 text-lg font-semibold leading-6 text-white">
                    <a href="{{route('article')}}">
                        <span class="absolute inset-0"></span>
                        Naprawa inwerterów solarnych Fronius
                    </a>
                </h3>
            </article>

            <article class="relative isolate flex flex-col justify-end overflow-hidden rounded-2xl bg-gray-900 px-8 pb-8 pt-80 sm:pt-48 lg:pt-80">
                <img src="https://images.unsplash.com/photo-1496128858413-b36217c2ce36?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=3603&q=80" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover">
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-gray-900 via-gray-900/40"></div>
                <div class="absolute inset-0 -z-10 rounded-2xl ring-1 ring-inset ring-gray-900/10"></div>

                <div class="flex flex-wrap items-center gap-y-1 overflow-hidden text-sm leading-6 text-gray-300">
                    <time datetime="2020-03-16" class="mr-8">Mar 16, 20204</time>
                </div>
                <h3 class="mt-3 text-lg font-semibold leading-6 text-white">
                    <a href="{{route('article')}}">
                        <span class="absolute inset-0"></span>
                        Naprawa softstarterów ABB
                    </a>
                </h3>
            </article>


            <article class="relative isolate flex flex-col justify-end overflow-hidden rounded-2xl bg-gray-900 px-8 pb-8 pt-80 sm:pt-48 lg:pt-80">
                <img src="https://images.unsplash.com/photo-1496128858413-b36217c2ce36?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=3603&q=80" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover">
                <div class="absolute inset-0 -z-10 bg-gradient-to-t from-gray-900 via-gray-900/40"></div>
                <div class="absolute inset-0 -z-10 rounded-2xl ring-1 ring-inset ring-gray-900/10"></div>

                <div class="flex flex-wrap items-center gap-y-1 overflow-hidden text-sm leading-6 text-gray-300">
                    <time datetime="2020-03-16" class="mr-8">Mar 16, 2024</time>
                </div>
                <h3 class="mt-3 text-lg font-semibold leading-6 text-white">
                    <a href="{{route('article')}}">
                        <span class="absolute inset-0"></span>
                        Naprawa sterownika silnika
                    </a>
                </h3>
            </article>


        </div>
    </div>
</div>

<div class="relative isolate bg-gray-900" id="contact">
    <div class="mx-auto grid max-w-7xl grid-cols-1 lg:grid-cols-2">
        <div class="relative px-6 pb-20 pt-24 sm:pt-32 lg:static lg:px-8 lg:py-48">
            <div class="mx-auto max-w-xl lg:mx-0 lg:max-w-lg">
                <div class="absolute inset-y-0 left-0 -z-10 w-full overflow-hidden ring-1 ring-white/5 lg:w-1/2">
                    <svg class="absolute inset-0 h-full w-full stroke-gray-700 [mask-image:radial-gradient(100%_100%_at_top_right,white,transparent)]" aria-hidden="true">
                        <defs>
                            <pattern id="54f88622-e7f8-4f1d-aaf9-c2f5e46dd1f2" width="200" height="200" x="100%" y="-1" patternUnits="userSpaceOnUse">
                                <path d="M130 200V.5M.5 .5H200" fill="none" />
                            </pattern>
                        </defs>
                        <svg x="100%" y="-1" class="overflow-visible fill-gray-800/20">
                            <path d="M-470.5 0h201v201h-201Z" stroke-width="0" />
                        </svg>
                        <rect width="100%" height="100%" stroke-width="0" fill="url(#54f88622-e7f8-4f1d-aaf9-c2f5e46dd1f2)" />
                    </svg>
                    <div class="absolute -left-56 top-[calc(100%-13rem)] transform-gpu blur-3xl lg:left-[max(-14rem,calc(100%-59rem))] lg:top-[calc(50%-7rem)]" aria-hidden="true">
                        <div class="aspect-[1155/678] w-[72.1875rem] bg-gradient-to-br from-[#80caff] to-[#4f46e5] opacity-20" style="clip-path: polygon(74.1% 56.1%, 100% 38.6%, 97.5% 73.3%, 85.5% 100%, 80.7% 98.2%, 72.5% 67.7%, 60.2% 37.8%, 52.4% 32.2%, 47.5% 41.9%, 45.2% 65.8%, 27.5% 23.5%, 0.1% 35.4%, 17.9% 0.1%, 27.6% 23.5%, 76.1% 2.6%, 74.1% 56.1%)"></div>
                    </div>
                </div>
                <h2 class="text-3xl font-bold tracking-tight text-white">{{ $element26 }}</h2>
                <p class="mt-6 text-lg leading-8 text-gray-300">{{ $element27 }}</p>
                <dl class="mt-10 space-y-4 text-base leading-7 text-gray-300">
                    <div class="flex gap-x-4">
                        <dt class="flex-none">
                            <span class="sr-only">Address</span>
                            <svg class="h-7 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                            </svg>
                        </dt>
                        <dd>{!! $element28 !!}</dd>
                    </div>
                    <div class="flex gap-x-4">
                        <dt class="flex-none">
                            <span class="sr-only">Telephone</span>
                            <svg class="h-7 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                            </svg>
                        </dt>
                        <dd><a class="hover:text-white" href="tel:{{ $element29 }}">{!! $element29 !!}</a></dd>
                    </div>
                    <div class="flex gap-x-4">
                        <dt class="flex-none">
                            <span class="sr-only">Email</span>
                            <svg class="h-7 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </dt>
                        <dd><a class="hover:text-white" href="mailto:kontakt@serwis-elektroniki-bartlomiej-biernat.pl">{{ $element30 }}</a></dd>
                    </div>
                </dl>
            </div>
        </div>
        <form action="#" method="POST" class="px-6 pb-24 pt-20 sm:pb-32 lg:px-8 lg:py-48">
            <div class="mx-auto max-w-xl lg:mr-0 lg:max-w-lg">
                <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div>
                        <label for="first-name" class="block text-sm font-semibold leading-6 text-white">Imię | Nazwa firmy*</label>
                        <div class="mt-2.5">
                            <input type="text" name="first-name" id="first-name" autocomplete="given-name" class="block w-full rounded-md border-0 bg-white/5 px-3.5 py-2 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6">
                        </div>
                    </div>
                    <div>
                        <label for="last-name" class="block text-sm font-semibold leading-6 text-white">Nazwisko</label>
                        <div class="mt-2.5">
                            <input type="text" name="last-name" id="last-name" autocomplete="family-name" class="block w-full rounded-md border-0 bg-white/5 px-3.5 py-2 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="email" class="block text-sm font-semibold leading-6 text-white">Email*</label>
                        <div class="mt-2.5">
                            <input type="email" name="email" id="email" autocomplete="email" class="block w-full rounded-md border-0 bg-white/5 px-3.5 py-2 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="phone-number" class="block text-sm font-semibold leading-6 text-white">Temat*</label>
                        <div class="mt-2.5">
                            <input type="tel" name="topic" id="topic"  class="block w-full rounded-md border-0 bg-white/5 px-3.5 py-2 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="message" class="block text-sm font-semibold leading-6 text-white">Wiadomość*</label>
                        <div class="mt-2.5">
                            <textarea name="message" id="message" rows="4" class="block w-full rounded-md border-0 bg-white/5 px-3.5 py-2 text-white shadow-sm ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm sm:leading-6"></textarea>
                        </div>
                    </div>
                </div>
                <div class="mt-8 flex justify-end">
                    <button type="submit" class="rounded-md bg-indigo-500 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500">{{ $element32_btn_text }}</button>
                </div>
            </div>
        </form>
    </div>
</div>



<footer class="bg-gray-900" id="footer">
    <div class="mx-auto max-w-7xl px-6 pb-8 lg:px-8 ">
        <div class="border-t border-white/10 pt-8 md:flex md:items-center md:justify-between">
            <p class="mt-8 text-xs leading-5 text-gray-400 md:order-1 md:mt-0">&copy; {{ date('Y') }} {{ $element33 }}</p>
        </div>
    </div>
</footer>





</body>
</html>
