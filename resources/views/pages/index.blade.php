<x-app-layout>
    <div class="container mx-auto py-4">
        <h1 class="text-2xl font-bold mb-4">Podstrony</h1>
        <a href="{{ route('pages.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded">Dodaj nową stronę</a>

        <table class="table-auto w-full mt-4">
            <thead>
            <tr>
                <th class="px-4 py-2">Nazwa</th>
                <th class="px-4 py-2">Slug</th>
                <th class="px-4 py-2">Opublikowana</th>
                <th class="px-4 py-2">Akcje</th>
            </tr>
            </thead>
            <tbody>
            @foreach($pages as $page)
                <tr>
                    <td class="border px-4 py-2">{{ $page->name }}</td>
                    <td class="border px-4 py-2">{{ $page->slug }}</td>
                    <td class="border px-4 py-2">{{ $page->is_published ? 'Tak' : 'Nie' }}</td>
                    <td class="border px-4 py-2">
                        <a href="{{ route('pages.edit', $page) }}" class="bg-green-500 text-white px-2 py-1 rounded">Edytuj</a>
                        <form action="{{ route('pages.destroy', $page) }}" method="POST" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded">Usuń</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
