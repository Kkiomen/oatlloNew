<x-app-layout>
    {{--    Widoczne na urządzaniach desktopowych--}}
    <div class="hidden md:block">
        <div class="sm:mx-auto sm:grid sm:max-w-7xl bg-white shadow shadow-2xl rounded-2xl my-6 p-10">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h1 class="text-base font-semibold leading-6 text-gray-900">Kursy</h1>
                        <p class="mt-2 text-sm text-gray-700">W tym miejscu możesz zarządzać kursami</p>
                    </div>
                    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                        <a href="{{ route('courses.add') }}">
                            <button type="button"
                                    class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                Dodaj kurs
                            </button>
                        </a>
                    </div>
                </div>

                <div class="mt-8 flow-root">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
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
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 text-center">
                                        Język
                                    </th>
                                    <th scope="col"
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 text-center">
                                        Opublikowana
                                    </th>
                                    <th scope="col" class="relative py-3 pl-3 pr-4 sm:pr-0">
                                        <span class="sr-only">Edit</span>
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($courses as $page)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">{{ $page->name }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $page->slug }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ \App\Services\Helper\LanguageHelper::getNameFromShort($page->lang) ?? 'Polski' }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">{{ $page->is_published ? 'Tak' : 'Nie' }}</td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                            <a href="{{ route('courses.edit', $page) }}"
                                               class="bg-green-500 text-white px-2 py-1 rounded">Edytuj</a>
{{--                                            <form x-data @submit.prevent="confirmDelete($el)" action="{{ route('pages.destroy', $page) }}" method="POST"--}}
{{--                                                  class="inline-block">--}}
{{--                                                @csrf--}}
{{--                                                @method('DELETE')--}}
{{--                                                <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded">--}}
{{--                                                    Usuń--}}
{{--                                                </button>--}}
{{--                                            </form> --}}
                                        </td>
                                    </tr>
                                @endforeach
                                <!-- More people... -->
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-10">
{{--                            {{ $pages->links() }}--}}
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
