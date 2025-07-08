<x-app-layout>

    {{--Widoczne na urządzeniach mobilnych--}}


    {{--    Widoczne na urządzaniach desktopowych--}}
    <div class="md:block">
        <div class="sm:mx-auto sm:grid sm:max-w-7xl bg-white shadow shadow-2xl rounded-2xl my-6 p-10">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h1 class="text-base font-semibold leading-6 text-gray-900">Posty Instagram</h1>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                        <a href="{{ route('pages.createMethods') }}">
                            <button type="button"
                                    class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                Dodaj artykuł
                            </button>
                        </a>
                    </div>
                </div>

                <!-- Formularz wyszukiwania -->
                <form method="POST" action="{{ route('instagram_post.add') }}" enctype="multipart/form-data"   class="mt-4">
                    @csrf
                    <div class="flex items-end gap-2">
                        <!-- grupa: plik -->
                        <div class="flex flex-col">
                            <label for="file_input"
                                   class="mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Upload file
                            </label>
                            <input id="file_input" type="file" name="image" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer  bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" />
                        </div>

                        <!-- grupa: url -->
                        <div>
                            <input type="text" name="url" placeholder="Url do posta"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                        </div>

                        <!-- przycisk -->
                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm  hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Dodaj
                        </button>
                    </div>
                </form>
                <div class="mt-8 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                            <table class="min-w-full divide-y divide-gray-300">
                                <thead>
                                <tr>
                                    <th scope="col"
                                        class="py-3 pl-4 pr-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 sm:pl-0">
                                        Zdjęcie
                                    </th>
                                    <th scope="col"
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Url
                                    </th>
                                    <th scope="col" class="relative py-3 pl-3 pr-4 sm:pr-0">
                                        <span class="sr-only">Edit</span>
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($posts as $post)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0"><img src="{{ $post->getUrl() }}" width="300" height="150" class="object-cover object-top"/></td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $post->url }}</td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                            <form x-data @submit.prevent="confirmDelete($el)" action="{{ route('instagram_post.remove', ['post' => $post->id]) }}" method="POST"
                                                  class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded">
                                                    Usuń
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                <!-- More people... -->
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-10">
                            {{ $posts->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
    </script>
</x-app-layout>
