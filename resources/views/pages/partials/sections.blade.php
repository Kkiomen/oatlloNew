<div class="mt-8">
    <h2 class="text-xl font-bold mb-4">Sekcje</h2>
    <button id="add-section-btn" class="bg-green-800 text-white px-4 py-2 rounded mb-4">Dodaj sekcję</button>
    <div class="border-gray-300 border-1 border rounded-2xl p-3 my-5" id="sections-container">
        @foreach($page->sections as $section)
            @include('pages.partials.section', ['section' => $section])
        @endforeach
    </div>
</div>

<!-- Modal wyboru typu sekcji -->
<div id="section-type-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6">
        <h3 class="text-xl font-semibold mb-4">Wybierz typ sekcji</h3>
        <div class="grid grid-cols-3 gap-4">
            <button data-type="1" class="select-section-type p-4 bg-gray-100 hover:bg-gray-200 rounded-lg flex flex-col items-center">
                <i class="fas fa-bars fa-2x mb-2"></i>
                <span>Pełna szerokość</span>
            </button>
            <button data-type="2" class="select-section-type p-4 bg-gray-100 hover:bg-gray-200 rounded-lg flex flex-col items-center">
                <i class="fas fa-columns fa-2x mb-2"></i>
                <span>Dwie kolumny</span>
            </button>
            <button data-type="3" class="select-section-type p-4 bg-gray-100 hover:bg-gray-200 rounded-lg flex flex-col items-center">
                <i class="fas fa-th-large fa-2x mb-2"></i>
                <span>Trzy kolumny</span>
            </button>
        </div>
        <button id="close-modal-btn" class="mt-6 w-full bg-red-500 text-white py-2 rounded-lg">Anuluj</button>
    </div>
</div>

<!-- Modal wyboru typu treści -->
<div id="content-type-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6">
        <h3 class="text-xl font-semibold mb-4">Wybierz typ treści</h3>
        <div class="grid grid-cols-2 gap-4">
            <button data-content-type="text" class="select-content-type-option p-4 bg-gray-100 hover:bg-gray-200 rounded-lg flex flex-col items-center">
                <i class="fas fa-font fa-2x mb-2"></i>
                <span>Tekst</span>
            </button>
            <button data-content-type="image" class="select-content-type-option p-4 bg-gray-100 hover:bg-gray-200 rounded-lg flex flex-col items-center">
                <i class="fas fa-image fa-2x mb-2"></i>
                <span>Obraz</span>
            </button>
        </div>
        <button id="close-content-modal-btn" class="mt-6 w-full bg-red-500 text-white py-2 rounded-lg">Anuluj</button>
    </div>
</div>

<!-- Modal generowanie -->
<div id="content-generate-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6">
        <h3 class="text-xl font-semibold mb-4">Generowanie artykułu</h3>
        <div class="text-center">
            <i class="fa-solid fa-trowel-bricks"></i> <span>W budowie</span>
        </div>
        <button id="close-generate-content-modal-btn" class="mt-6 w-full bg-red-500 text-white py-2 rounded-lg">Anuluj</button>
    </div>
</div>
