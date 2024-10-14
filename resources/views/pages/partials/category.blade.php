<div class="fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16"
     x-data="categoryPanel()"
     x-show="openCategoryPanel"
     x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full">
    <div class="pointer-events-auto w-screen max-w-md">
        <form class="flex h-full flex-col divide-y divide-gray-200 bg-white shadow-xl" @submit.prevent="saveCategory()">
            <div class="h-0 flex-1 overflow-y-auto">
                <div class="bg-blue-700 px-4 py-6 sm:px-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold leading-6 text-white">Zarządzanie kategoriami</h2>
                        <div class="ml-3 flex h-7 items-center">
                            <button type="button" @click="closePanel" class="rounded-md bg-blue-700 text-blue-200 hover:text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Category Form / List View Toggle -->
                <div class="p-5" x-show="!isFormVisible">
                    <!-- Search Input -->
                    <div class="relative border border-gray-300 rounded-2xl">
                        <svg class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                        </svg>
                        <input type="text" x-model="searchTerm" @input="searchCategories" class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm" placeholder="Wyszukaj kategorię.." role="combobox" aria-expanded="false" aria-controls="options">
                    </div>

                    <div class="flex justify-end items-center gap-3 mt-3">
                        <div class="cursor-pointer select-none" @click="openForm()">
                            <i class="fa-solid fa-plus text-blue-400"></i> Dodaj kategorie
                        </div>
                    </div>



                    <!-- Categories List -->
                    <table class="min-w-full divide-y divide-gray-200 mt-4">
                        <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">Nazwa kategorii</th>
                            <th class="px-4 py-2 text-right">Akcje</th>
                        </tr>
                        </thead>
                        <tbody>
                        <template x-for="category in filteredCategories" :key="category.id">
                            <tr>
                                <td class="px-4 py-2" x-text="category.name"></td>
                                <td class="px-4 py-2 flex justify-end gap-3">
                                    <i class="fa-solid fa-hand-pointer text-emerald-600 hover:text-emerald-900 cursor-pointer" @click="chooseCategory(category)"></i>
                                    <i class="fa-solid fa-pen-to-square text-indigo-600 hover:text-indigo-900 cursor-pointer" @click="editCategory(category)"></i>
                                    <i class="fa-solid fa-trash text-red-500 hover:text-red-900 cursor-pointer" @click="confirmDelete(category.id)"></i>
                                </td>
                            </tr>
                        </template>
                        </tbody>
                    </table>

                    <!-- Add Button -->
                </div>

                <!-- Category Form -->
                <div x-show="isFormVisible">
                    <div class="p-4">
                        <label class="block text-sm font-medium">Nazwa</label>
                        <input type="text" x-model="categoryForm.name" class="mt-1 w-full p-2 border">

                        <label class="block text-sm font-medium mt-4">Slug</label>
                        <input type="text" x-model="categoryForm.slug" class="mt-1 w-full p-2 border">
                        <small>Url kategorii</small>
                    </div>

                    <div class="flex-shrink-0 justify-end px-4 py-4">
                        <button type="button" @click="closeForm()" class="rounded-md bg-white px-3 py-2">Anuluj</button>
                        <button type="submit" class="ml-4 inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-white">Zapisz</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
    const urlWEB = '{{ url('/') }}';

    function categoryPanel() {
        return {
            isPanelOpen: true,
            isFormVisible: false,
            categories: [],  // Categories fetched from server
            filteredCategories: [],  // Displayed after search
            searchTerm: '',
            categoryForm: {
                id: null,
                name: '',
                slug: '',
            },
            openForm() {
                this.isFormVisible = true;
                this.clearForm();
            },
            closeForm() {
                this.isFormVisible = false;
            },
            clearForm() {
                this.categoryForm = {
                    id: null,
                    name: '',
                    slug: '',
                };
            },
            openPanel() {
                this.openCategoryPanel = true;
            },
            closePanel() {
                this.openCategoryPanel = false;
            },
            async fetchCategories() {
                const response = await fetch('{{ route('categories.fetch') }}');  // Fetch categories via AJAX
                this.categories = await response.json();
                this.filteredCategories = this.categories;
            },
            searchCategories() {
                if (this.searchTerm === '') {
                    this.filteredCategories = this.categories;
                } else {
                    this.filteredCategories = this.categories.filter(category =>
                        category.name.toLowerCase().includes(this.searchTerm.toLowerCase())
                    );
                }
            },
            editCategory(category) {
                this.categoryForm = {...category};
                this.isFormVisible = true;
            },
            async saveCategory() {
                const method = this.categoryForm.id ? 'PUT' : 'POST';
                const url = this.categoryForm.id ? `${urlWEB}/categories/${this.categoryForm.id}` : '{{ route('categories.fetch') }}';

                await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.categoryForm),
                });
                this.fetchCategories();
                this.closeForm();
            },
            async confirmDelete(categoryId) {
                Swal.fire({
                    title: "Jesteś pewny?",
                    text: "Nie będzie można tego przywrócić!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Tak, usuń kategorię!",
                    cancelButtonText: "Anuluj"
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        await fetch(`${urlWEB}/categories/${categoryId}`, {method: 'DELETE'});
                        this.fetchCategories();
                    }
                });

            },
            chooseCategory(category) {
                var notyf = new Notyf();
                const formData = {};
                formData['basic_article_information_category0001000'] = category.id;


                fetch('{{ route('pages.updateKey', ['article' => $article->id]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Dane zapisane:', data);
                        if(data.result.changes){
                            notyf.success('Zapisano informacje');
                            document.getElementById("buttonCategory").innerHTML  = "<span class=\"mt-2 text-sm/6 font-medium tracking-tight text-blue-600 mr-4\">Wybrana kategoria:</span> <span class=\"mt-2 max-w-lg text-lg/7 text-gray-600\">" + category.name + "</span>";
                        }
                        this.closePanel();
                    })
                    .catch(error => {
                        console.error('Błąd:', error);
                        notyf.error('Wystąpił błąd podczas zapisywania danych');
                    });
            },
            async init() {
                this.fetchCategories();
            }
        }
    }

</script>
