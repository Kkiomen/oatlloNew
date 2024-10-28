<x-app-layout>
    <div class="container mx-auto py-4">

        <div class="p-3 md:p-10">
            <nav class="flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-4">
                    <li>
                        <div>
                            <a href="{{asset('login')}}" class="text-gray-400 hover:text-gray-500">
                                <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor"
                                     aria-hidden="true" data-slot="icon">
                                    <path fill-rule="evenodd"
                                          d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z"
                                          clip-rule="evenodd"/>
                                </svg>
                                <span class="sr-only">Home</span>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor"
                                 aria-hidden="true" data-slot="icon">
                                <path fill-rule="evenodd"
                                      d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                                      clip-rule="evenodd"/>
                            </svg>
                            <a href="{{ route('pages.index') }}"
                               class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">Artykuły</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor"
                                 aria-hidden="true" data-slot="icon">
                                <path fill-rule="evenodd"
                                      d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                                      clip-rule="evenodd"/>
                            </svg>
                            <a href="{{ route('pages.createMethods') }}"
                               class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700" aria-current="page">Panel
                                modyfikacji artykułu</a>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="container mx-auto py-4">
            <div class="md:mx-auto max-w-7xl px-3 py-3">
                <div class="flex justify-end mt-4 gap-3">
                    <a href="{{ $article->getRoute() }}">
                        <button type="button"
                                class="inline-flex items-center rounded-md bg-black px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">
                            Podgląd
                        </button>
                    </a>


                    <div x-data="languageActions()">
                        @if($languages !== null)
                            @foreach($languages as $language)
                                <button type="button"
                                        @click="handleLanguageAction('{{ $language['method'] }}', '{{ $language['name'] }}', '{{ $language['url'] }}')"
                                        class="inline-flex items-center rounded-md bg-black px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 @if($language['method'] === 'toGenerate') underline @endif">
                                    <i class="fa-solid fa-language mr-3"></i> {{ $language['name'] }}
                                </button>
                            @endforeach
                        @endif

                        <div x-show="loading" class="mt-4 text-gray-500">Generating content, please wait...</div>
                    </div>


                </div>
            </div>
        </div>


        @include('pages.partials.default_article_form_builder', ['content' => $contents, 'views_basic.article' => $article])


    </div>

    <script>
        let articleIdd = {{ $article->id }};
        function languageActions() {
            return {
                loading: false,
                handleLanguageAction(method, name, url) {
                    if (method === 'toGenerate') {
                        this.loading = true;
                        try {
                            fetch("{{ route('generate.contentInOtherLanguage') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ name, articleIdd })
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log(data);
                                if (data.url) {
                                    window.location.href = data.url;
                                }
                            })
                        } catch (error) {
                            console.error('Error generating content:', error);
                        } finally {
                            this.loading = false;
                        }
                    } else if (method === 'redirectToUrl' && url) {
                        window.location.href = url;
                    }
                }
            }
        }
    </script>
</x-app-layout>
