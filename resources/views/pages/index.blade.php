<x-app-layout>

{{--Widoczne na urządzeniach mobilnych--}}
    <div class="block md:hidden">
        <div class="bg-white px-4 sm:px-6 lg:px-8 pb-5">

            <div class="sm:flex sm:items-center">
                <div class="sm:flex-auto">
                    <h1 class="text-base font-semibold leading-6 text-gray-900">Artykuły</h1>
                    <p class="mt-2 text-sm text-gray-700">W tym miejscu możesz zarządzać artykułami</p>
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
            <form method="GET" action="{{ route('pages.index') }}" class="mt-10">
                <div class="flex items-center">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Szukaj artykułu..."
                           class="block w-full sm:w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <button type="submit"
                            class="ml-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        Szukaj
                    </button>
                </div>
            </form>
        </div>

        <div class="px-4 sm:px-6 lg:px-8 mt-10">
            <ul role="list" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

                @foreach($pages as $page)
                <li class="col-span-1 divide-y divide-gray-200 rounded-lg bg-white shadow">
                    <div class="flex w-full items-center justify-between space-x-6 p-6">
                        <div class="flex-1 truncate">
                            <p class="mt-1 truncate text-xs text-gray-900 font-bold">{{ $page->name }}</p>

                            <div class="flex gap-4 items-center">
                                <p class="mt-1 truncate text-xs text-gray-500 font-bold pt-2">{{ $page->slug }}</p>
                                <div class="flex items-center space-x-3 mt-3">
                                    @if($page->is_published)
                                        <span class="inline-flex flex-shrink-0 items-center rounded-full bg-green-50 px-1.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Opublikowane</span>
                                    @else
                                        <span class="inline-flex flex-shrink-0 items-center rounded-full bg-green-50 px-1.5 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20">Do publikacji</span>
                                    @endif
                                </div>
                            </div>

                            <div class="text-xs text-gray-400 mt-2">
                                <i class="fa-regular fa-calendar-plus mr-2"></i> {{ $page->created_at->format('d.m.Y') }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="-mt-px flex divide-x divide-gray-200">

                            <div class="-ml-px flex w-0 flex-1">
                                <a href="{{ route('pages.edit', $page) }}" class="relative inline-flex w-0 flex-1 items-center justify-center gap-x-3 rounded-br-lg border border-transparent py-4 text-sm font-semibold text-gray-900">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                    Przejdź do edycji
                                </a>
                            </div>
                        </div>
                    </div>
                </li>
                @endforeach

                <!-- More people... -->
            </ul>
        </div>

        <div class="bg-white mt-10 px-3 py-3">
            {{ $pages->links() }}
        </div>
    </div>


{{--    Widoczne na urządzaniach desktopowych--}}
    <div class="hidden md:block">
        <div class="sm:mx-auto sm:grid sm:max-w-7xl bg-white shadow shadow-2xl rounded-2xl my-6 p-10">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="sm:flex sm:items-center">
                    <div class="sm:flex-auto">
                        <h1 class="text-base font-semibold leading-6 text-gray-900">Artykuły</h1>
                        <p class="mt-2 text-sm text-gray-700">W tym miejscu możesz zarządzać artykułami</p>
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
                <form method="GET" action="{{ route('pages.index') }}" class="mt-4">
                    <div class="flex items-center">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Szukaj artykułu..."
                               class="block w-full sm:w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <button type="submit"
                                class="ml-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Szukaj
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
                                        Nazwa
                                    </th>
                                    <th scope="col"
                                        class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Slug
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
                                @foreach($pages as $page)
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">{{ $page->name }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $page->slug }}</td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">{{ $page->is_published ? 'Tak' : 'Nie' }}</td>
                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                            <a href="{{ route('pages.edit', $page) }}"
                                               class="bg-green-500 text-white px-2 py-1 rounded">Edytuj</a>
                                            <form action="{{ route('pages.destroy', $page) }}" method="POST"
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
                            {{ $pages->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
