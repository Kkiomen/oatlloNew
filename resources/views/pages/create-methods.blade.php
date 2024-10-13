<x-app-layout>
    <div class="mx-auto mb-10 max-w-10xl py-12 px-4 sm:px-6 lg:px-8">

        <nav class="flex mb-10" aria-label="Breadcrumb">
            <ol role="list" class="flex items-center space-x-4">
                <li>
                    <div>
                        <a href="{{asset('login')}}" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd" />
                            </svg>
                            <span class="sr-only">Home</span>
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                        <a href="{{ route('pages.index') }}" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">Artykuły</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                        <a href="{{ route('pages.createMethods') }}" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700" aria-current="page">Wybór metody tworzenia</a>
                    </div>
                </li>
            </ol>
        </nav>


        <div class="mb-15 text-gray-500">
            Wybierz tryb tworzenia artykułu:
        </div>
        <div class="mx-auto max-w-10xl mt-4 mb-10">
            <div class="flex gap-10 flex-col sm:flex-row">


                    <div class="overflow-hidden rounded-lg bg-white shadow w-full md:w-1/2 select-none cursor-pointer hover:shadow-2xl md:max-h-96">
                        <a href="{{ route('pages.create') }}">
                            <div class="relative bg-white">
                                <img class="h-32 w-full bg-gray-50 object-cover lg:absolute lg:inset-y-0 lg:left-0 lg:h-full lg:w-1/2" src="{{ asset('/assets/images/generate_ai_article.webp') }}" alt="">
                                <div class="mx-auto grid max-w-7xl lg:grid-cols-2">
                                    <div class="px-6 pb-10 pt-10 sm:pb-32 sm:pt-20 lg:col-start-2 lg:px-8 lg:pt-32">
                                        <div class="mx-auto max-w-2xl lg:mr-0 lg:max-w-lg">
                                            <h2 class="text-base font-semibold leading-8 text-gray-600 hidden md:block">Tryb</h2>
                                            <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">AI</p>
                                            <p class="mt-6 text-lg leading-8 text-gray-600">Artykuł jest tworzony przy pomocy sztucznej inteligencji</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>


                    <div class="overflow-hidden rounded-lg bg-white shadow w-full md:w-1/2 select-none cursor-pointer hover:shadow-2xl md:max-h-96">
                        <a href="{{ route('pages.create') }}">
                        <div class="relative bg-white">
                            <img class="h-32 w-full bg-gray-50 object-cover lg:absolute lg:inset-y-0 lg:left-0 lg:h-full lg:w-1/2" src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=2850&q=80" alt="">
                            <div class="mx-auto grid max-w-7xl lg:grid-cols-2">
                                <div class="px-6 pb-10 pt-10 sm:pb-32 sm:pt-20 lg:col-start-2 lg:px-8 lg:pt-32">
                                    <div class="mx-auto max-w-2xl lg:mr-0 lg:max-w-lg">
                                        <h2 class="text-base font-semibold leading-8 text-gray-600 hidden md:block">Tryb</h2>
                                        <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Normalny</p>
                                        <p class="mt-6 text-lg leading-8 text-gray-600">Wszystkie informacje są wprowadzane ręcznie</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </a>
                    </div>



            </div>
        </div>

    </div>
</x-app-layout>
