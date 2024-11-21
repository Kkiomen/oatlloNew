<x-app-layout>
    <div x-data="generatedContentCrud({{ $contentGenerator->id }}, `{{ $contentGenerator->systemPrompt }}`)">
        <div class="bg-gray-200 py-5 h-full">
            <!-- Back Button -->
            <div class="mb-4 flex justify-end mx-5">
                <a href="{{ route('content_generators.index') }}" class="text-gray-600">← Wróć do listy generatorów</a>
            </div>

            <!-- User Prompt Input -->
            <div class="bg-white p-5 rounded m-5">
                <div class="col-span-full">
                    <label for="userPrompt" class="block text-sm font-medium leading-6 text-gray-900">Opisz co Cię
                        interesuje <span class="text-xs text-gray-400 ml-3">Prompt użytkownika</span></label>
                    <div class="mt-2">
                        <textarea x-model="userPrompt" id="userPrompt" name="userPrompt" rows="3"
                                  class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 px-3"></textarea>
                    </div>
                </div>

                <div class="flex items-center mt-4">
                    <!-- Toggle button to show/hide the system prompt -->
                    <button @click="toggleSystemPrompt" type="button"
                            :class="{'bg-indigo-600': showSystemPrompt, 'bg-gray-200': !showSystemPrompt}"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                            role="switch" aria-checked="false" aria-labelledby="systemPromptToggleLabel">
                        <span :class="{'translate-x-5': showSystemPrompt, 'translate-x-0': !showSystemPrompt}"
                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                    <span class="ml-3 text-sm" id="systemPromptToggleLabel">
                        <span class="font-medium text-gray-900">Czy chcesz zmodyfikować system prompt?</span>
                    </span>
                </div>

                <div class="col-span-full mt-5" x-show="showSystemPrompt">
                    <label for="systemPrompt" class="block text-sm font-medium leading-6 text-gray-900">Prompt Systemowy
                        (możliwy do edycji)</label>
                    <div class="mt-2">
                        <textarea x-model="systemPrompt" id="systemPrompt" name="systemPrompt" rows="3"
                                  class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 px-3"></textarea>
                    </div>
                </div>
                <div class="mt-3 flex justify-end">
                    <button id="generateButton" @click="generateContent()" type="button"
                            :disabled="isGenerating"
                            class="rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                            :class="{
                                'bg-indigo-600 hover:bg-indigo-500 focus-visible:outline-indigo-600': !isGenerating,
                                'bg-gray-400 cursor-not-allowed': isGenerating
                            }">
                        Generuj
                    </button>
                </div>
            </div>

            {{--            <!-- Generated Contents List -->--}}
            {{--            <table class="min-w-full bg-white mx-5">--}}
            {{--                <thead>--}}
            {{--                <tr>--}}
            {{--                    <th class="py-2">User Prompt</th>--}}
            {{--                    <th class="py-2">Generated Content</th>--}}
            {{--                    <th class="py-2">Akcje</th>--}}
            {{--                </tr>--}}
            {{--                </thead>--}}
            {{--                <tbody>--}}
            {{--                <template x-for="content in generatedContents" :key="content.id">--}}
            {{--                    <tr>--}}
            {{--                        <td class="border px-4 py-2" x-text="content.user_prompt"></td>--}}
            {{--                        <td class="border px-4 py-2" x-text="content.generated_content"></td>--}}
            {{--                        <td class="border px-4 py-2 flex flex-col gap-3">--}}
            {{--                                        <div class="bg-gray-800 cursor-pointer select-none hover:bg-gray-700 text-center p-1" @click="regenerateContent(content.id)">--}}
            {{--                                            <i class="fa-solid fa-repeat text-white"></i> <span class="text-white ml-2">Wygeneruj ponownie</span>--}}
            {{--                                        </div>--}}
            {{--                                        <div class="bg-gray-800 cursor-pointer select-none hover:bg-gray-700 text-center p-1" @click="deleteContent(content.id)">--}}
            {{--                                            <i class="fa-solid fa-trash text-white"></i>--}}
            {{--                                        </div>--}}
            {{--                        </td>--}}
            {{--                    </tr>--}}
            {{--                </template>--}}
            {{--                </tbody>--}}
            {{--            </table>--}}
            <div class="bg-gray-400 mx-5">
                <template x-for="content in generatedContents" :key="content.id">

                    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                        <div class="mx-auto grid max-w-2xl lg:mx-0 lg:max-w-none ">

                            <div class="px-4 pt-2 pb-4 shadow-sm ring-1 ring-gray-900/5 bg-white sm:mx-0 sm:rounded-lg lg:col-span-2">
                                <div class="flex justify-end text-xs text-gray-600">
                                    <div x-text="formatDate(content.created_at)"></div>
                                </div>
                                <dl class="mt-6 grid grid-cols-1 text-sm leading-6 sm:grid-cols-2">
                                    <div class="sm:pr-4 text-gray-800" x-text="content.generated_content"></div>
                                </dl>
                                <div class="flex justify-between mt-10 flex-col gap-3 md:flex-row">
                                    <div class="flex flex-row justify-center gap-3">
                                        <div @click="showInformation(content, 'user')"
                                             class="text-white bg-gray-400 text-sm text-center p-1 px-3 rounded">Prompt
                                            użytkownika
                                        </div>
                                        <div @click="showInformation(content, 'system')"
                                             class="text-white bg-gray-400 text-sm text-center p-1 px-3 rounded">Prompt
                                            Systemowy
                                        </div>
                                    </div>
                                    <div class="flex flex-row justify-center gap-3">
                                        <div
                                            :id="`regenerateButton-${content.id}`"
                                            :class="{
                                                'bg-gray-800 cursor-pointer hover:bg-gray-700': regeneratingId !== content.id,
                                                'bg-gray-400 cursor-not-allowed': regeneratingId === content.id
                                            }"
                                            class="select-none text-center p-1 px-3 rounded"
                                            @click="regenerateContent(content.id)"
                                            :disabled="regeneratingId === content.id"
                                            >
                                            <template x-if="regeneratingId !== content.id">
                                                <i class="fa-solid fa-repeat text-white"></i>
                                            </template>
                                            <template x-if="regeneratingId === content.id">
                                                <i class="fa fa-spinner fa-spin text-white"></i>
                                            </template>
                                        </div>
                                        <div
                                            class="bg-gray-800 cursor-pointer select-none hover:bg-gray-700 text-center p-1 px-3 rounded"
                                            @click="deleteContent(content.id)">
                                            <i class="fa-solid fa-trash text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </template>
            </div>

        </div>

        <div x-show="showModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center">
            <div class="bg-white p-5 sm:w-full sm:max-w-md rounded">
                <div x-text="contentModalInformation"></div>
                <div class="flex justify-end gap-3 mt-5">
                    <button @click="closeModal()"
                            class="inline-flex w-full justify-center rounded-md bg-gray-400 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        Zamknij
                    </button>
                </div>
            </div>
        </div>


    </div>

    <script>
        const urlsPrefix = '{{ route('index') }}';

        function generatedContentCrud(contentGeneratorId, defaultSystemPrompt) {
            return {
                contentGeneratorId: contentGeneratorId,
                systemPrompt: defaultSystemPrompt,
                showModal: false,
                contentModalInformation: '',
                userPrompt: '',
                isGenerating: false,
                showSystemPrompt: false,
                regeneratingId: null,
                generatedContents: @json($generatedContents),
                toggleSystemPrompt() {
                    this.showSystemPrompt = !this.showSystemPrompt;
                },
                generateContent() {
                    this.isGenerating = true;
                    const button = document.getElementById('generateButton');
                    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

                    fetch(`${urlsPrefix}/generated-contents`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            content_generator_id: this.contentGeneratorId,
                            user_prompt: this.userPrompt,
                            system_prompt: this.systemPrompt
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.generatedContents.unshift(data);
                        this.userPrompt = '';
                        this.isGenerating = false;
                        button.innerHTML = 'Generuj';
                    });

                },
                regenerateContent(id) {
                    if (this.regeneratingId) return;
                    this.regeneratingId = id;
                    const button = document.getElementById(`regenerateButton-${id}`);


                    // Logic to regenerate content (similar to generateContent)
                    // For now, we'll just alert
                    Swal.fire({
                        title: "Jesteś pewny?",
                        text: "Nie będzie można tego przywrócić!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Tak, wygeneruj ponownie!",
                        cancelButtonText: "Anuluj"
                    }).then(async (result) => {
                        if(result.isConfirmed) {
                            if(this.regeneratingId !== id) return;
                            button.innerHTML = '<i class="fa fa-spinner fa-spin text-white"></i>';


                            fetch(`${urlsPrefix}/generated-contents/${id}/regenerate`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                                }
                            })
                                .then(response => response.json())
                                .then(data => {
                                    const index = this.generatedContents.findIndex(content => content.id === id);

                                    if (index !== -1) {
                                        this.generatedContents.splice(index, 1, data);
                                    }
                                })
                                .finally(() => {
                                    this.regeneratingId = null;
                                    button.innerHTML = '<i class="fa-solid fa-repeat text-white"></i>';

                                });
                        }
                    }).finally(() => {
                        this.regeneratingId = null;
                        button.innerHTML = '<i class="fa-solid fa-repeat text-white"></i>';

                    });
                },
                deleteContent(id) {
                    Swal.fire({
                        title: "Jesteś pewny?",
                        text: "Nie będzie można tego przywrócić!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Tak, usuń generator!",
                        cancelButtonText: "Anuluj"
                    }).then(async (result) => {
                        if(result.isConfirmed) {
                            fetch(`${urlsPrefix}/generated-contents/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                this.generatedContents = this.generatedContents.filter(content => content.id !== id);
                            });
                        }
                    });
                },
                showInformation(content, type) {
                    this.showModal = true;
                    if (type === 'system') {
                        this.contentModalInformation = content.used_system_prompt;
                    } else {
                        this.contentModalInformation = content.user_prompt;
                    }
                    console.log(type, contentModalInformation, showModal);
                },
                closeModal() {
                    this.showModal = false;
                },
                formatDate(dateString) {
                    const date = new Date(dateString);
                    const options = {
                        year: 'numeric',
                        month: 'long', // 'short' for abbreviated month names
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                    };
                    return date.toLocaleString(undefined, options);
                }
            }
        }
    </script>
</x-app-layout>
