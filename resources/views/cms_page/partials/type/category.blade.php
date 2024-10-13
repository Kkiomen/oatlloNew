<div class="mt-3 border p-3 border-gray-300 rounded-xl" x-data="{ openCategoryPanel: false }">
    <div class="mb-3">
        <i class="fa-solid fa-layer-group"></i> - @if(!empty($element['label'])) {{ $element['label'] }} @else Kategoria @endif
    </div>

    <div class="mt-5">

       @if($element['value'] === null)
            <button  @click="openCategoryPanel = true" type="button" class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-12 text-center hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">

                <span class="mt-2 block text-sm font-semibold text-gray-900 underline" @click="openCategoryPanel = true">Wybierz kategorie</span>
            </button>


        @else

       @endif

    </div>


    @include('pages.partials.category', ['article' => $article])

</div>
