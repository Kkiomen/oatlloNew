<x-app-layout>
    <div class="mx-auto mb-10 max-w-10xl py-12 px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb navigation (same as before) -->
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
                <li>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                        <a href="#" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700" aria-current="page">Tryb AI</a>
                    </div>
                </li>
            </ol>
        </nav>


        <div class="mx-auto max-w-10xl mt-4 mb-10">
            <form x-data="articleGenerator()" @submit.prevent="startGenerating">
                <div class="space-y-8 bg-white shadow p-10">
                    <div class="border-b border-gray-900/10 pb-12">
                        <h2 class="text-base font-semibold leading-7 text-gray-900">Generowanie AI</h2>
                        <p class="mt-1 text-sm leading-6 text-gray-600">Pozwól zrozumieć asystentowi, o czym ma napisać artykuł.</p>

                        <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                            <div class="col-span-full">
                                <label for="about" class="block text-sm font-medium leading-6 text-gray-900">Opisz co ma zostać zawarte w artykule (bądź podaj główny temat)</label>
                                <div class="mt-2">
                                    <textarea id="about" name="about" rows="3" x-ref="about" class="block w-full p-5 rounded-md border-0 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm"></textarea>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-gray-600">Pamiętaj, że AI zrobi dokładnie to, co napiszesz dlatego, warto wypunktować co ma zostać uwzględnione</p>

                                <hr class="mt-5 border-2 border-gray-200"/>

                                <div class="mt-10">
                                    <small>Opcje:</small>
                                    <div>

                                        <div class="mt-3">
                                            <label for="options_count_letter" class="block text-xs font-medium leading-6 text-gray-900">Ilość znaków</label>
                                            <div class="mt-2">
                                                <input type="number" name="options_count_letter" x-ref="options_count_letter" id="options_count_letter" class="block w-full type-number rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" value="1000" placeholder="1000" min="0" max="5000" required>
                                            </div>
                                            <p class="mt-3 text-sm leading-6 text-gray-600">Wartość <u><strong>0</strong></u> oznacza, że generator nie ma limitu znaków</p>
                                        </div>
                                        <hr class="mt-5"/>

                                        <div class="mt-5">
                                            <label for="options_count_letter" class="block text-xs font-medium leading-6 text-gray-900">Zdjęcia do artykułu</label>
                                            <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                                                <template x-for="(image, index) in imagePreviews" :key="index">
                                                    <div class="flex flex-col items-center">
                                                        <img :src="image" class="h-full no-select rounded-tl-xl rounded-tr-xl object-cover"/>
                                                        <div @click="removeImage(index)" class="bg-red-400 hover:bg-red-600 select-none cursor-pointer w-full text-center rounded-bl-xl rounded-br-xl py-1">
                                                            <i class="fa-solid text-white fa-trash"></i>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>

                                            <!-- Button to add images -->
                                            <div class="mt-10 w-full">
                                                <input type="file" id="imageInput" accept="image/*" @change="handleImageUpload" class="hidden" multiple>
                                                <div @click="document.getElementById('imageInput').click()" class="text-center bg-black p-3 hover:bg-gray-700 cursor-pointer">
                                                    <i class="fa-solid text-white fa-circle-plus"></i>
                                                </div>
                                            </div>
                                        </div>



                                    </div>
                                </div>

{{--                                <img src="{{ asset('/assets/images/diagram_ai.svg') }}" class="w-full/50 mt-10 no-select"/>--}}

                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-6 flex items-center justify-end gap-x-6">
                    <button type="button" class="text-sm font-semibold leading-6 text-gray-900">Wróć</button>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Generuj artykuł</button>
                </div>

                <!-- Modal -->
                <div x-show="isGenerating" class="fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-75">
                    <div class="bg-white rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Generowanie artykułu</h3>
                        <ul class="space-y-2">
                            <li :class="{'font-bold text-green-600': step >= 1}">
                                1. Tworzenie artykułu <span x-show="step === 1 && isProcessing">(w trakcie...)</span> <span x-show="step > 1">✓</span>
                            </li>
                            <li :class="{'font-bold text-green-600': step >= 2}">
                                2. Generowanie podstawowych informacji o artykule <span x-show="step === 2 && isProcessing">(w trakcie...)</span> <span x-show="step > 2">✓</span>
                            </li>
                            <li :class="{'font-bold text-green-600': step >= 3}">
                                3. Generowanie treści <span x-show="step === 3 && isProcessing">(w trakcie...)</span> <span x-show="step > 3">✓</span>
                                    <div class="ml-10" x-show="isGenerateContent">
                                        <ul class="list-schema-generate"></ul>
                                    </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>

        @include('pages.partials.script_generate')

    </div>
</x-app-layout>
