<div x-data="articleEditor({{ json_encode($article->contents ?? []) }})" class="px-1 md:p-6">
    <!-- Display Existing Sections -->
    <div id="sections content-articles-list" class="space-y-4">
        <template x-for="(section, index) in sections" :key="section.id">
            <div class="border rounded" x-data="{ section }" x-init="$watch('section', value => sections[index] = value)" draggable="true" @dragstart="dragStart($event, index)" @dragover.prevent @drop="drop($event, index)">
                <!-- Section Content -->
                <div class="flex flex-col md:flex-row justify-between ">
                    <div class="w-full h-full">
                        <!-- Text Section -->
                        <template class="h-full" x-if="section.type === 'text'">
                            <div class="h-full" >
                                <div class="flex items-center">
                                    <button
                                        x-show="!section.isGenerated"
                                        @click="generateText(section.id, index)"
                                        class="bg-black text-white px-3 py-1 rounded mr-3"
                                        :disabled="section.isGenerating"
                                        :class="{ 'opacity-50 cursor-not-allowed': section.isGenerating }"
                                    >
                                        <i
                                            class="fa-solid fa-fan text-white"
                                            :class="{ 'fa-spin-reverse': section.isGenerating }"> <!-- Add the class conditionally here -->
                                        </i>
                                        Dogeneruj tekst
                                    </button>
                                </div>
                                <textarea :data-id="section.id" rows="10" x-model="section.content" class="w-full h-full border contents-textarea p-3 rounded" placeholder="Wpisz treść..."></textarea>
                            </div>
                        </template>

                        <!-- Image Section -->
                        <template x-if="section.type === 'image'">
                            <div class="flex flex-col items-center">

                                <template x-if="section.content">
                                    <div class="relative mt-2 flex justify-center w-full rounded-lg border border-dashed border-gray-900/25 px-6 py-10 text-center">
                                        <!-- Image Preview -->
                                        <img :src="section.content" class="max-w-full max-h-100 object-cover" >
                                    </div>
                                </template>


                                <div class="relative mt-2 w-full">
                                    <input :id="'upload-image-' + index" type="file" @change="uploadImage($event, index)" accept="image/*" class="mb-4 hidden">
                                    <label :for="'upload-image-' + index" class="flex justify-center w-full cursor-pointer rounded-lg border border-dashed border-gray-900/25 px-6 py-10 text-gray-600 hover:bg-gray-100">
                                        <span class="text-center">Ustaw zdjęcie</span>
                                    </label>
                                </div>
                            </div>

                        </template>

                        <!-- Full Width Section (Optional) -->
                        <template x-if="section.type === 'full_width'">
                            <div>
                                <!-- Możesz dodać tutaj specjalną obsługę dla pełnej szerokości -->
                                <p>Pełna szerokość sekcji</p>
                            </div>
                        </template>

                        <!-- Columns Section -->
                        <template x-if="section.type === 'columns'">
                            <div class="flex space-x-4">
                                <template x-for="(column, colIndex) in section.columns" :key="colIndex">
                                    <div class="w-1/2">
                                        <div x-data="{ content: column.content, type: column.type }" x-init="$watch('content', value => section.columns[colIndex].content = value); $watch('type', value => section.columns[colIndex].type = value)">

                                            <div class="mb-3">
                                                <label for="type" class="block text-sm font-medium leading-6 text-gray-900">Typ</label>
                                                <select id="type" x-model="type" name="type" class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                    <option value="">Wybierz typ</option>
                                                    <option value="text">Tekst</option>
                                                    <option value="image">Zdjęcie</option>
                                                </select>
                                            </div>

                                            <!-- Column Text -->
                                            <template x-if="type === 'text'">
                                                <div class="h-full">
                                                    <textarea :data-id="section.id" rows="10" x-model="content" class="w-full h-full border contents-textarea p-3 rounded" placeholder="Wpisz treść..."></textarea>
                                                </div>
                                            </template>

                                            <!-- Column Image -->
                                            <template x-if="type === 'image'">
                                                <div class="flex flex-col items-center">

                                                    <div x-if="section.content" class="relative mt-2 flex justify-center w-full rounded-lg border border-dashed border-gray-900/25 px-6 py-10 text-center">
                                                        <!-- Image Preview -->
                                                        <img :src="section.content" class="max-w-full max-h-100 object-cover" >
                                                    </div>

                                                    <div class="relative mt-2 w-full">
                                                        <input :id="'upload-image-' + index" type="file" @change="uploadImage($event, index)" accept="image/*" class="mb-4 hidden">
                                                        <label :for="'upload-image-' + index" class="flex justify-center w-full cursor-pointer rounded-lg border border-dashed border-gray-900/25 px-6 py-10 text-gray-600 hover:bg-gray-100">
                                                            <span class="text-center">Ustaw zdjęcie</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Section Controls -->
                    <div class="hidden lg:block">
                        <div class="flex flex-col space-y-2 bg-gray-600 p-2 ">
                            <button @click="moveUp(section.id)" :disabled="index === 0" class="p-2 bg-gray-200 hover:bg-gray-300 rounded" :class="{ 'opacity-50 cursor-not-allowed': index === 0 }">
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button @click="moveDown(section.id)" :disabled="index === sections.length - 1" class="p-2 bg-gray-200 hover:bg-gray-300 rounded" :class="{ 'opacity-50 cursor-not-allowed': index === sections.length - 1 }">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                            <button @click="removeSection(section.id)" class="p-2 bg-gray-900 text-white border border-gray-300 rounded">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="block lg:hidden w-full">
                        <div class="flex flex-row bg-gray-600 p-2 gap-4">
                            <button @click="moveUp(section.id)" :disabled="index === 0" class="p-1 w-full bg-gray-200 hover:bg-gray-300 rounded" :class="{ 'opacity-50 cursor-not-allowed': index === 0 }">
                                <i class="fas fa-arrow-up text-sm"></i>
                            </button>
                            <button @click="moveDown(section.id)" :disabled="index === sections.length - 1" class="p-1 w-full bg-gray-200 hover:bg-gray-300 rounded" :class="{ 'opacity-50 cursor-not-allowed': index === sections.length - 1 }">
                                <i class="fas fa-arrow-down text-sm"></i>
                            </button>
                            <button @click="removeSection(section.id)" class="p-1 bg-gray-900 w-full text-white border border-gray-300 rounded">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </template>
    </div>

    <!-- Add New Content Options -->
    <div class="mt-6" x-show="showSectionContentOptions">
        <div class="mx-auto max-w-lg sm:max-w-full sm:m-0">
            <div class="rounded-lg border-2 border-dashed border-gray-300">
                <div class="px-4 pt-4">
                    <h2 class="text-base font-semibold leading-6 text-gray-900">Dodaj treść</h2>
                    <p class="mt-1 text-sm text-gray-500">Wybierz opcje, która Cię interesuje</p>
                </div>
                <ul role="list" class="mt-6 divide-y divide-gray-200 border-b border-t border-gray-200">
                    <!-- Text Option -->
                    <li class="hover:bg-gray-200 cursor-pointer px-4" @click="addTextSection">
                        <div class="group relative flex items-start space-x-3 py-4">
                            <div class="flex-shrink-0">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-pink-500">
                                    <i class="fa-solid fa-font text-white"></i>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    Tekst
                                </div>
                                <p class="text-sm text-gray-500">Dodanie na całą szerokość tekstu</p>
                            </div>
                            <div class="flex-shrink-0 self-center">
                                <svg class="h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 011.06 0l4.25 4.25a.75.75 0 010 1.06L9.28 15.28a.75.75 0 01-1.06-1.06L11.44 10 8.22 6.78a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </li>

                    <!-- Image Option -->
                    <li class="hover:bg-gray-200 cursor-pointer px-4" @click="addImageSection">
                        <div class="group relative flex items-start space-x-3 py-4">
                            <div class="flex-shrink-0">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500">
                                    <i class="fa-regular fa-image text-white"></i>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-900">
                                    Zdjęcie
                                </div>
                                <p class="text-sm text-gray-500">Dodanie na całą szerokość zdjęcie</p>
                            </div>
                            <div class="flex-shrink-0 self-center">
                                <svg class="h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 011.06 0l4.25 4.25a.75.75 0 010 1.06L9.28 15.28a.75.75 0 01-1.06-1.06L11.44 10 8.22 6.78a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </li>

                    <!-- Section Option -->
{{--                    <li class="hover:bg-gray-200 cursor-pointer px-4" @click="onClickShowSectionOptions">--}}
{{--                        <div class="group relative flex items-start space-x-3 py-4">--}}
{{--                            <div class="flex-shrink-0">--}}
{{--                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-500">--}}
{{--                                    <i class="fa-solid fa-border-all text-white"></i>--}}
{{--                                </span>--}}
{{--                            </div>--}}
{{--                            <div class="min-w-0 flex-1">--}}
{{--                                <div class="text-sm font-medium text-gray-900">--}}
{{--                                    Sekcje--}}
{{--                                </div>--}}
{{--                                <p class="text-sm text-gray-500">Umożliwia podzielenie strony</p>--}}
{{--                            </div>--}}
{{--                            <div class="flex-shrink-0 self-center">--}}
{{--                                <svg class="h-5 w-5 text-gray-400 group-hover:text-gray-500" fill="currentColor" viewBox="0 0 20 20">--}}
{{--                                    <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 011.06 0l4.25 4.25a.75.75 0 010 1.06L9.28 15.28a.75.75 0 01-1.06-1.06L11.44 10 8.22 6.78a.75.75 0 010-1.06z" clip-rule="evenodd" />--}}
{{--                                </svg>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </li>--}}
                </ul>
            </div>
        </div>
    </div>

    <!-- Section Type Options -->
    <div class="mt-6" x-show="showSectionOptions" @click.away="showSectionOptions = false">
        <div class="mx-auto max-w-lg sm:max-w-full sm:m-0">
            <div class="rounded-lg border-2 border-dashed border-gray-300 p-4">
                <div class="flex justify-between items-center mb-8">
                    <div>Wybierz opcje:</div>
                    <div class="cursor-pointer hover:text-gray-500" @click="onCloseShowSectionOptions">
                        <i class="fa-solid fa-rotate-left"></i>
                    </div>
                </div>
                <div class="flex justify-between gap-4">
                    <!-- Full Width Option -->
{{--                    <div class="rounded-lg border-2 border-dashed border-gray-300 w-full text-center px-3" @click="addFullWidthSection">--}}
{{--                        <div class="flex flex-col gap-3 py-3 hover:bg-gray-200 cursor-pointer">--}}
{{--                            <div class="bg-black p-3 rounded-xl mx-auto my-4">--}}
{{--                                <img src="{{ asset('assets/images/one-row.png') }}" class="w-10 h-10" />--}}
{{--                            </div>--}}
{{--                            <div class="uppercase">--}}
{{--                                <div class="text-sm">Typ</div>--}}
{{--                                <div class="text-lg font-bold">Cała szerokość</div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </div>--}}

                    <!-- Two Columns Option -->
                    <div class="rounded-lg border-2 border-dashed border-gray-300 w-full text-center px-3" @click="addTwoColumnsSection">
                        <div class="flex flex-col gap-3 py-3 hover:bg-gray-200 cursor-pointer">
                            <div class="bg-black p-3 rounded-xl mx-auto my-4">
                                <img src="{{ asset('assets/images/two-row.png') }}" class="w-10 h-10" />
                            </div>
                            <div class="uppercase">
                                <div class="text-sm">Typ</div>
                                <div class="text-lg font-bold">Dwie kolumny</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="mt-6 w-full">
        <button @click="save" class="px-4 py-5 bg-black w-full text-white rounded">Zapisz </button>
    </div>
</div>


<script>
    let articleId = {{ $article->id }};
    let urlUpdateContents = '{{ route('articles.saveContents', $article) }}';
    let urlUploadImage = '{{ route('articles.saveContentsImage') }}';
    let urlBasic = '{{ route('index') }}';
    let routeNamed = '{{ Route::currentRouteName() }}';
</script>




<script src="{{ asset('assets/js/article.js') }}" ></script>

