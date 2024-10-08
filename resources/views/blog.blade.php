<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Naprawa Automatyki Przemysłowej Gliwice – Bartłomiej Bernat</title>
    <meta name="description" content="Profesjonalna naprawa automatyki przemysłowej w Gliwicach. Bartłomiej Bernat – serwis, diagnostyka i modernizacja systemów automatyki. Gwarancja jakości i precyzji.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
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
                <div class="text-white uppercase" style="font-family: 'Montserrat', sans-serif; font-weight: 800">
                    Bartłomiej Biernat
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
            <div class="hidden lg:flex lg:gap-x-12">
                <a href="{{ route('index') }}" class="text-sm font-semibold leading-6 text-white">Strona główna</a>
                <a href="{{ route('blog') }}" class="text-sm font-semibold leading-6 text-white">Blog</a>
                <a href="{{ route('index') }}#contact" class="text-sm font-semibold leading-6 text-white">Kontakt</a>
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
                        <div class="space-y-2 py-6">
                            <a href="{{ route('index') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-gray-800">Strona główna</a>
                            <a href="{{ route('blog') }}" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-gray-800">Blog</a>
                            <a href="{{ route('index') }}#contact" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-white hover:bg-gray-800">Kontakt</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div class="relative isolate overflow-hidden pt-14">
        <img src="{{ asset('assets/images/header.webp') }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover">
        <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
            <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
        </div>
        <div class="mx-auto max-w-2xl py-32 sm:py-48 lg:py-56">

            <div class="text-center px-3 sm:px-0">
                <h1 class="text-balance text-3xl font-bold tracking-tight text-white sm:text-4xl">Naprawa i serwis automatyki przemysłowej</h1>
                <p class="mt-6 text-lg leading-8 text-gray-300">Profesjonalna naprawa i serwis automatyki przemysłowej. Szybkie usuwanie usterek, modernizacja systemów i wsparcie techniczne. Gwarancja jakości!</p>
                <div class="mt-10 flex items-center justify-center gap-x-6">
                    <a href="#" class="rounded-md bg-indigo-500 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-400">Sprawdź szczegóły</a>
                    <a href="#" class="text-sm font-semibold leading-6 text-white">Czytaj więcej <span aria-hidden="true">→</span></a>
                </div>
            </div>
        </div>
        <div class="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
            <div class="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
        </div>
    </div>
</div>




<div>
    <div class="relative isolate z-50 shadow">

        <div class="absolute inset-x-0 top-0 -z-10 bg-white pt-16 shadow-lg ring-1 ring-gray-900/5">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-x-8 gap-y-10 px-6 py-10 lg:grid-cols-[1fr_2fr] lg:px-8">
                <div class="grid grid-cols-1 gap-x-4 sm:gap-x-8">
                    <div>
                        <h3 class="text-sm font-medium leading-6 text-gray-500">Kategorie</h3>
                        <div class="mt-6 flow-root">
                            <div class="-my-2">
                                <a href="#" class="flex gap-x-4 py-2 text-sm font-semibold leading-6 text-gray-900">
                                    Fotowoltaika
                                </a>
                                <a href="#" class="flex gap-x-4 py-2 text-sm font-semibold leading-6 text-gray-900">
                                    Naprawa elektrycznych wózków widłowych
                                </a>
                                <a href="#" class="flex gap-x-4 py-2 text-sm font-semibold leading-6 text-gray-900">
                                    Naprawa podzespołów elektronicznych
                                </a>
                                <a href="#" class="flex gap-x-4 py-2 text-sm font-semibold leading-6 text-gray-900">
                                    Naprawa zasilaczy awaryjnych UPS
                                </a>
                                <!-- Add more links as needed -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-10 sm:gap-8 lg:grid-cols-2">
                    <h3 class="sr-only">Recent posts</h3>
                    <article class="relative isolate flex max-w-2xl flex-col gap-x-8 gap-y-6 sm:flex-row sm:items-start lg:flex-col lg:items-stretch">
                        <div class="relative flex-none">
                            <img class="aspect-[2/1] w-full rounded-lg bg-gray-100 object-cover sm:aspect-[16/9] sm:h-32 lg:h-auto" src="https://images.unsplash.com/photo-1496128858413-b36217c2ce36?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=3603&q=80" alt="">
                            <div class="absolute inset-0 rounded-lg ring-1 ring-inset ring-gray-900/10"></div>
                        </div>
                        <div>
                            <div class="flex items-center gap-x-4">
                                <time datetime="2023-03-16" class="text-sm leading-6 text-gray-600">Mar 16, 2023</time>

                            </div>
                            <h4 class="mt-2 text-sm font-semibold leading-6 text-gray-900">
                                <a href="{{route('article')}}">
                                    <span class="absolute inset-0"></span>
                                    Boost your conversion rate
                                </a>
                            </h4>
                            <p class="mt-2 text-sm leading-6 text-gray-600">Et et dolore officia quis nostrud esse aute cillum irure do esse. Eiusmod ad deserunt cupidatat est magna Lorem.</p>
                        </div>
                    </article>
                    <article class="relative isolate flex max-w-2xl flex-col gap-x-8 gap-y-6 sm:flex-row sm:items-start lg:flex-col lg:items-stretch">
                        <div class="relative flex-none">
                            <img class="aspect-[2/1] w-full rounded-lg bg-gray-100 object-cover sm:aspect-[16/9] sm:h-32 lg:h-auto" src="https://images.unsplash.com/photo-1496128858413-b36217c2ce36?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=3603&q=80" alt="">
                            <div class="absolute inset-0 rounded-lg ring-1 ring-inset ring-gray-900/10"></div>
                        </div>
                        <div>
                            <div class="flex items-center gap-x-4">
                                <time datetime="2023-03-16" class="text-sm leading-6 text-gray-600">Mar 16, 2023</time>

                            </div>
                            <h4 class="mt-2 text-sm font-semibold leading-6 text-gray-900">
                                <a href="{{route('article')}}">
                                    <span class="absolute inset-0"></span>
                                    Boost your conversion rate
                                </a>
                            </h4>
                            <p class="mt-2 text-sm leading-6 text-gray-600">Et et dolore officia quis nostrud esse aute cillum irure do esse. Eiusmod ad deserunt cupidatat est magna Lorem.</p>
                        </div>
                    </article>
                    <article class="relative isolate flex max-w-2xl flex-col gap-x-8 gap-y-6 sm:flex-row sm:items-start lg:flex-col lg:items-stretch">
                        <div class="relative flex-none">
                            <img class="aspect-[2/1] w-full rounded-lg bg-gray-100 object-cover sm:aspect-[16/9] sm:h-32 lg:h-auto" src="https://images.unsplash.com/photo-1496128858413-b36217c2ce36?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=3603&q=80" alt="">
                            <div class="absolute inset-0 rounded-lg ring-1 ring-inset ring-gray-900/10"></div>
                        </div>
                        <div>
                            <div class="flex items-center gap-x-4">
                                <time datetime="2023-03-16" class="text-sm leading-6 text-gray-600">Mar 16, 2023</time>
                            </div>
                            <h4 class="mt-2 text-sm font-semibold leading-6 text-gray-900">
                                <a href="{{route('article')}}">
                                    <span class="absolute inset-0"></span>
                                    Boost your conversion rate
                                </a>
                            </h4>
                            <p class="mt-2 text-sm leading-6 text-gray-600">Et et dolore officia quis nostrud esse aute cillum irure do esse. Eiusmod ad deserunt cupidatat est magna Lorem.</p>
                        </div>
                    </article>
                    <article class="relative isolate flex max-w-2xl flex-col gap-x-8 gap-y-6 sm:flex-row sm:items-start lg:flex-col lg:items-stretch">
                        <div class="relative flex-none">
                            <img class="aspect-[2/1] w-full rounded-lg bg-gray-100 object-cover sm:aspect-[16/9] sm:h-32 lg:h-auto" src="https://images.unsplash.com/photo-1496128858413-b36217c2ce36?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=3603&q=80" alt="">
                            <div class="absolute inset-0 rounded-lg ring-1 ring-inset ring-gray-900/10"></div>
                        </div>
                        <div>
                            <div class="flex items-center gap-x-4">
                                <time datetime="2023-03-16" class="text-sm leading-6 text-gray-600">Mar 16, 2023</time>

                            </div>
                            <h4 class="mt-2 text-sm font-semibold leading-6 text-gray-900">
                                <a href="{{route('article')}}">
                                    <span class="absolute inset-0"></span>
                                    Boost your conversion rate
                                </a>
                            </h4>
                            <p class="mt-2 text-sm leading-6 text-gray-600">Et et dolore officia quis nostrud esse aute cillum irure do esse. Eiusmod ad deserunt cupidatat est magna Lorem.</p>
                        </div>
                    </article>
                    <!-- Add more articles as needed -->



                </div>

            </div>

        </div>
    </div>





</div>






<footer class="bg-gray-900">
    <div class="mx-auto max-w-7xl px-6 pb-8 lg:px-8 ">
        <div class="border-t border-white/10 pt-8 md:flex md:items-center md:justify-between">
            <p class="mt-8 text-xs leading-5 text-gray-400 md:order-1 md:mt-0">&copy; {{ date('Y') }} Serwis elektroniki - Bartłomiej Biernat</p>
        </div>
    </div>
</footer>








</body>
</html>
