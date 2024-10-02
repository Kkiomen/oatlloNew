<x-app-layout>
    <div class="container mx-auto py-4">
        <h1 class="text-2xl font-bold mb-4">Dodaj nową stronę</h1>
        <form action="{{ route('pages.store') }}" method="POST">
            @csrf
            @include('pages.partials.form')
        </form>
    </div>
</x-app-layout>
