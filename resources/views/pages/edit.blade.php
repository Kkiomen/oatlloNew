<x-app-layout>
    <div class="mx-auto grid max-w-7xl bg-white shadow shadow-2xl rounded-2xl my-6 p-10">
        <h1 class="text-2xl font-bold mb-4">Edytuj stronę: {{ $page->name }}</h1>
        <form action="{{ route('pages.update', $page) }}" method="POST">
            @csrf
            @method('PUT')
            @include('pages.partials.form')
        </form>

        @include('pages.partials.sections')

        <button id="save-sections-btn" class="bg-blue-500 text-white px-4 py-2 rounded">Zapisz sekcje</button>
    </div>
</x-app-layout>

