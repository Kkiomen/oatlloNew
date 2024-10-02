<div class="mb-4">
    <label class="block text-gray-700">Nazwa</label>
    <input type="text" name="name" value="{{ $page->name ?? old('name') }}" class="w-full border rounded px-3 py-2">
</div>
<div class="mb-4">
    <label class="block text-gray-700">Slug</label>
    <input type="text" name="slug" value="{{ $page->slug ?? old('slug') }}" class="w-full border rounded px-3 py-2">
</div>
<div class="mb-4">
    <label class="inline-flex items-center">
        <input type="checkbox" name="is_published" value="1" {{ (isset($page) && $page->is_published) ? 'checked' : '' }} class="form-checkbox">
        <span class="ml-2">Opublikowana</span>
    </label>
</div>
<button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Zapisz</button>
@if(isset($page))
    <a href="{{ route('home.page', ['slug' => $page->slug]) }}" target="_blank"><button type="button" class="bg-gray-500 text-white px-4 py-2 rounded">Podgląd</button></a>
    <button type="button" id="generate-content-btn" class="bg-black text-white px-4 py-2 rounded"><i class="fa-solid fa-rocket text-white"></i> Wygeneruj</button>
@endif
