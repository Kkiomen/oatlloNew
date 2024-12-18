<x-app-layout>
    {{--    Widoczne na urządzaniach desktopowych--}}

    <div class="sm:mx-auto sm:grid sm:max-w-7xl bg-white shadow shadow-2xl rounded-2xl my-6 p-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-base font-semibold leading-6 text-gray-900">Edycja kursu</h1>
                    <p class="mt-2 text-sm text-gray-700">W tym miejscu możesz zarządzać kursami</p>
                </div>
            </div>

            <div class="mt-8 flow-root">

                <form action="{{ route('courses.store') }}" method="post" enctype="multipart/form-data">
                    @csrf

                    <input type="hidden" name="id" value="{{ $course->id }}">

                    <div class="mt-3">
                        <label for="symbol" class="block text-sm/6 font-medium text-gray-900">Symbol</label>
                        <div class="mt-2">
                            <input type="text" value="{{ $course->symbol }}" name="symbol" id="symbol"
                                   class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="name" class="block text-sm/6 font-medium text-gray-900">Nazwa kursu</label>
                        <div class="mt-2">
                            <input type="text" value="{{ $course->name }}" name="name" id="name"
                                   class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="name" class="block text-sm/6 font-medium text-gray-900">Czy kurs jest
                            dostępny</label>
                        <div class="mt-2">
                            <input
                                type="checkbox"
                                name="is_published"
                                value="1"
                                @checked(old('is_published', $course->is_published))
                            />
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="lang" class="block text-sm/6 font-medium text-gray-900">Język</label>
                        <div class="mt-2 grid grid-cols-1">
                            <select id="lang" name="lang"
                                    class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-1.5 pr-8 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                <option value="pl" @selected(old('lang', 'pl') == $course->lang)>Polski</option>
                                <option value="en" @selected(old('lang', 'en') == $course->lang)>Angielski</option>
                            </select>
                            <svg
                                class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end text-gray-500 sm:size-4"
                                viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" data-slot="icon">
                                <path fill-rule="evenodd"
                                      d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>


                    <div class="mt-10">
                        <label for="title_seo" class="block text-sm/6 font-medium text-gray-900">Tytuł SEO</label>
                        <div class="mt-2">
                            <input type="text" value="{{ $course->title_seo }}" name="title_seo" id="title_seo"
                                   class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="description_seo" class="block text-sm/6 font-medium text-gray-900">Opis SEO</label>
                        <div class="mt-2">
                            <input type="text" value="{{ $course->description_seo }}" name="description_seo"
                                   id="description_seo"
                                   class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="slug" class="block text-sm/6 font-medium text-gray-900">Slug</label>
                        <div class="mt-2">
                            <input type="text" value="{{ $course->slug }}" name="slug" id="slug"
                                   class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                        </div>
                    </div>

                    <div class="mt-10">
                        <label for="title_list" class="block text-sm/6 font-medium text-gray-900">Tytuł kursu</label>
                        <div class="mt-2">
                            <input type="text" value="{{ $course->title_list }}" name="title_list" id="title_list"
                                   class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                            <small class="text-sm text-gray-500">Wyświetlany na liście kursów</small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="description_list" class="block text-sm/6 font-medium text-gray-900">Opis
                            kursu</label>
                        <div class="mt-2">
                            <textarea rows="4" name="description_list" id="description_list"
                                      class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">{{ $course->description_list }}</textarea>
                            <small class="text-sm text-gray-500">Wyświetlany na liście kursów</small>
                        </div>
                    </div>

                    <div class="mt-10">
                        <label for="title_full" class="block text-sm/6 font-medium text-gray-900">Tytuł kursu</label>
                        <div class="mt-2">
                            <input type="text" name="title_full" value="{{ $course->title_full }}" id="title_full"
                                   class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                            <small class="text-sm text-gray-500">Wyświetlany na podstronie kursu na górze</small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="description_full" class="block text-sm/6 font-medium text-gray-900">Opis
                            kursu</label>
                        <div class="mt-2">
                            <textarea rows="4" name="description_full" id="description_full"
                                      class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">{{ $course->description_full }}</textarea>
                            <small class="text-sm text-gray-500">Wyświetlany na podstronie kursu na górze</small>
                        </div>
                    </div>

                    <div class="mt-10">
                        <label for="content_description_offers" class="block text-sm/6 font-medium text-gray-900">Opis
                            HTML kursu</label>
                        <div class="mt-2">
                            <textarea rows="4" name="content_description_offers" id="content_description_offers"
                                      class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">{{ $course->content_description_offers }}</textarea>
                        </div>
                    </div>

                    <div class="mt-10">
                        <label for="image" class="block text-sm/6 font-medium text-gray-900">Zdjęcie kursu</label>
                        <div class="mt-2">
                            <input type="file" name="image" id="image"
                                   class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                        </div>

                        @php
                            $currentImage = empty($course->image) ? 'storage/uploads/empty_image.jpg' : $course->image;
                            $pattern = "/asset\('(.+?)'\)/";
                            if (preg_match($pattern, $currentImage, $matches)) {
                                $currentImage = $matches[1];
                            }
                            $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);
                        @endphp

                        <img src="{{ $currentImage }}"/>
                    </div>

                    <div class="mt-10 flex justify-end">
                        <button type="submit" class="bg-blue-950 text-white rounded-xl p-2 text-center px-6">Zapisz
                        </button>
                    </div>


                </form>
            </div>
        </div>
    </div>

    <div class="sm:mx-auto sm:grid sm:max-w-7xl bg-white shadow shadow-2xl rounded-2xl my-6 p-10">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-base font-semibold leading-6 text-gray-900">Zarządzanie rozdziałami</h1>
                </div>
                <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                    <a href="{{ route('courses.category.add', ['course' => $course->id]) }}">
                        <button type="button" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Dodaj rozdział
                        </button>
                    </a>
                </div>
            </div>

            <div class="mt-8 flow-root">
                <form action="{{ route('courses.category.edit.short') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                        <tr>
                            <th scope="col"
                                class="py-3 pl-4 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 sm:pl-0">
                                Nazwa
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                Slug
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                Kolejność
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-xs font-medium uppercase tracking-wide text-gray-500 text-center">
                                Opublikowana
                            </th>
                            <th scope="col" class="relative py-3 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">Edit</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($courseCategories as $category)
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                                    <input type="text" name="category_name[{{ $category->id }}]" value="{{ $category->category_name }}"
                                           class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                </td>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                                    <input type="text" name="slug[{{ $category->id }}]" value="{{ $category->slug }}"
                                           class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                </td>

                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                                    <input type="number" name="sort[{{ $category->id }}]" value="{{ $category->sort }}"
                                           class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                </td>

                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">
                                    <input
                                        type="checkbox"
                                        name="is_published[{{ $category->id }}]"
                                        value="1"
                                        @checked(old('is_published', $category->is_published))
                                    />
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                    <button type="button" data-id="{{ $category->id }}" class="open_modal_category bg-green-500 text-white px-2 py-1 rounded">Edytuj</button>
                                    <button type="button" data-id="{{ $category->id }}" class="open_modal_category_lessons bg-blue-500 text-white px-2 py-1 rounded">Zarządzanie lekcjami</button>
                                    {{--                                <form x-data @submit.prevent="confirmDelete($el)" action="{{ route('pages.destroy', $page) }}" method="POST"--}}
                                    {{--                                      class="inline-block">--}}
                                    {{--                                    @csrf--}}
                                    {{--                                    @method('DELETE')--}}
                                    {{--                                    <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded">--}}
                                    {{--                                        Usuń--}}
                                    {{--                                    </button>--}}
                                    {{--                                </form>--}}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div class="flex justify-end mt-5">
                        <button type="submit" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Zapisz zmiany
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>







    @foreach($courseCategories as $category)
        <div class="relative z-10 hidden" aria-labelledby="modal-title" id="category_modal_{{ $category->id }}" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">
                        <form action="{{ route('courses.category.edit', ['category' => $category->id]) }}" method="post" enctype="multipart/form-data">

                        <div>
                                @csrf

                                <div class="mt-3">
                                    <label class="block text-sm/6 font-medium text-gray-900">Slug</label>
                                    <div class="mt-2">
                                        <input type="text" value="{{ $category->slug }}" name="slug"
                                               class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="block text-sm/6 font-medium text-gray-900">Nazwa</label>
                                    <div class="mt-2">
                                        <input type="text" value="{{ $category->category_name }}" name="category_name"
                                               class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="block text-sm/6 font-medium text-gray-900">Tytuł SEO</label>
                                    <div class="mt-2">
                                        <input type="text" value="{{ $category->title_seo }}" name="title_seo"
                                               class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="block text-sm/6 font-medium text-gray-900">Opis SEO</label>
                                    <div class="mt-2">
                                        <input type="text" value="{{ $category->description_seo }}" name="description_seo"
                                               class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="block text-sm/6 font-medium text-gray-900">Opis SEO</label>
                                    <div class="mt-2">
                                        <input type="text" value="{{ $category->description_seo }}" name="description_seo"
                                               class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="block text-sm/6 font-medium text-gray-900">Tytuł</label>
                                    <div class="mt-2">
                                        <input type="text" value="{{ $category->title }}" name="title"
                                               class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                    </div>
                                </div>


                                <div class="mt-3">
                                    <label class="block text-sm/6 font-medium text-gray-900">Opis</label>
                                    <div class="mt-2">
                                        <textarea rows="4" name="description"
                                            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">{{ $category->description }}</textarea>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="block text-sm/6 font-medium text-gray-900">Opis kategorii HTML</label>
                                    <div class="mt-2">
                                        <textarea rows="4" name="description_content"
                                            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">{{ $category->description_content }}</textarea>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label for="name" class="block text-sm/6 font-medium text-gray-900">Czy kategoria jest dostępna</label>
                                    <div class="mt-2">
                                        <input
                                            type="checkbox"
                                            name="is_published"
                                            value="1"
                                            @checked(old('is_published', $category->is_published))
                                        />
                                    </div>
                                </div>


                        </div>
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2">Zapisz</button>
                            <button type="button" data-id="{{ $category->id }}" class="open_modal_category mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 shadow-xs ring-gray-300 ring-inset hover:bg-gray-50 sm:col-start-1 sm:mt-0">Zamknij</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach


    @foreach($courseCategories as $category)

        <div class="relative z-10 hidden" aria-labelledby="modal-title" id="category_modal_lesson_{{ $category->id }}" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">


                            <div>

                                <form action="{{ route('courses.category.lesson_to_choose_add') }}" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="course" value="{{ $course->id }}" />
                                    <input type="hidden" name="category" value="{{ $category->id }}" />

                                    <select name="article"
                                            class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-1.5 pr-8 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                        @foreach($articles as $article)
                                            <option value="{{ $article->id }}" >{{ $article->name }}</option>
                                        @endforeach
                                    </select>

                                    <div class="mt-3">
                                        <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2">Dodaj produkt</button>
                                    </div>

                                </form>





                                <form action="{{ route('courses.category.lessons.edit.positions') }}" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <table class="min-w-full divide-y divide-gray-300">
                                        <thead>
                                        <tr>
                                            <th scope="col"
                                                class="py-3 pl-4 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 sm:pl-0">
                                                Nazwa
                                            </th>
                                            <th scope="col"
                                                class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                                Kolejność
                                            </th>
                                            <th scope="col" class="relative py-3 pl-3 pr-4 sm:pr-0">
                                                <span class="sr-only">Edycja</span>
                                            </th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach($category->lessonsMore() as $lesson)
                                            <tr>
                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                                                    {{ $lesson['name'] }}
                                                </td>

                                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">
                                                    <input type="number" name="sort[{{ $lesson['id'] }}]" value="{{ $lesson['sort'] }}"
                                                           class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                                                </td>

                                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                                    <a href="{{ route('courses.category.lessons.remove.position', ['id' => $lesson['id']]) }}"><button type="button" data-id="{{ $lesson['id'] }}" class="bg-red-500 text-white px-2 py-1 rounded">Usuń</button></a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>

                                    <div class="flex justify-end mt-5">
                                        <button type="submit" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                            Zapisz zmiany
                                        </button>
                                    </div>
                                </form>


                            </div>
                            <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                <button type="button" data-id="{{ $category->id }}" class="open_modal_category_lessons mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 shadow-xs ring-gray-300 ring-inset hover:bg-gray-50 sm:col-start-1 sm:mt-0">Zamknij</button>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach







    <script>
        function confirmDelete(formElement) {
            Swal.fire({
                title: 'Czy na pewno chcesz usunąć?',
                text: "Tej operacji nie można cofnąć!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Tak, usuń!',
                cancelButtonText: 'Anuluj'
            }).then((result) => {
                if (result.isConfirmed) {
                    formElement.submit();
                }
            })
        }

        // Pobierz wszystkie przyciski "Edytuj" z klasą .edit-btn
        const modalCategoriesButtons = document.querySelectorAll('.open_modal_category');
        const modalCategoriesLessonsButtons = document.querySelectorAll('.open_modal_category_lessons');

        // Funkcja wywoływana po kliknięciu
        function handleModalCategoriesEditClick(event) {
            // Pobierz ID z atrybutu data-id
            const categoryId = event.currentTarget.getAttribute('data-id');

            // Przykład: Tutaj możesz wykonać dowolne akcje
            const modal = document.getElementById("category_modal_" + categoryId);
            if (!modal.classList.contains("hidden")) {
                modal.classList.add("hidden");
            } else {
                modal.classList.remove("hidden");
            }
        }


        function handleModalCategoriesLessonsEditClick(event) {
            const categoryId = event.currentTarget.getAttribute('data-id');

            // Przykład: Tutaj możesz wykonać dowolne akcje
            const modal = document.getElementById("category_modal_lesson_" + categoryId);
            if (!modal.classList.contains("hidden")) {
                modal.classList.add("hidden");
            } else {
                modal.classList.remove("hidden");
            }
        }

        // Dodaj event listener dla każdego przycisku
        modalCategoriesButtons.forEach((button) => {
            button.addEventListener('click', handleModalCategoriesEditClick);
        });
        // Dodaj event listener dla każdego przycisku
        modalCategoriesLessonsButtons.forEach((button) => {
            button.addEventListener('click', handleModalCategoriesLessonsEditClick);
        });

    </script>

</x-app-layout>
