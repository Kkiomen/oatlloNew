<x-app-layout>
    <div class="bg-gray-200 py-5 h-full" x-data="contentGeneratorCrud()">
        <div class="mx-auto grid max-w-7xl rounded-2xl my-6 p-10">




            <div class="bg-white">
                <div class=" mb-4 mx-3 mt-3">
                    <button @click="openModal()" class="bg-blue-500 w-full text-white px-4 py-2 rounded">+ Dodaj generator</button>
                    <small class="text-xs text-gray-800 mt-1 text-center">Poniższa funkcjonalność pozwala tworzyć automatycznie treści. Można skonfigurować prompt systemowy, aby automatycznie generować np. posty na fb</small>
                </div>

                <ul role="list" class="divide-y divide-gray-100 overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">


                    <template x-for="generator in contentGenerators" :key="generator.id">
                        <li class="relative flex justify-between gap-x-6 px-4 py-5 hover:bg-gray-50 sm:px-6">
                            <div class="flex min-w-0 gap-x-4">
                                <div class="min-w-0 flex-auto">
                                    <p class="text-sm font-semibold leading-6 text-gray-900" x-text="generator.title">
                                        <a :href="generator.enterUrl"  x-text="generator.title">
                                            <span class="absolute inset-x-0 -top-px bottom-0"></span>
                                        </a>
                                    </p>
                                    <p class="mt-1 flex text-xs leading-5 text-gray-500">
                                        <a :href="generator.enterUrl" class="relative hover:underline" x-text="generator.systemPrompt"></a>
                                    </p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-x-4">
                                <div class="flex items-center gap-3">
                                    <div @click="editGenerator(generator.id)" class="cursor-pointer">
                                        <i class="fa-solid fa-pen-to-square text-blue-500"></i>
                                    </div>
                                    <div @click="deleteGenerator(generator.id)" class="cursor-pointer">
                                        <i class="fa-solid fa-trash text-red-500"></i>
                                    </div>
                                </div>
                                <a :href="generator.enterUrl">
                                    <svg class="h-5 w-5 flex-none text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </a>

                            </div>
                        </li>
                    </template>

                </ul>



                <div x-show="showModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center">
                    <div class="bg-white p-5 rounded">
                        <h2 class="text-xl mb-4" x-text="modalTitle"></h2>
                        <input type="text" placeholder="Nazwa generatora" x-model="form.title" class="border p-2 w-full mb-3 rounded-md">
                        <textarea placeholder="Prompt systemowy - określający co ma wygenerować" x-model="form.systemPrompt" class="border p-2 w-full mb-3 rounded-md"></textarea>
                        <div class="flex justify-end gap-3">
                            <button @click="closeModal()"  class="inline-flex w-full justify-center rounded-md bg-gray-400 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Anuluj</button>
                            <button @click="saveGenerator()"  class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Zapisz</button>
                        </div>
                    </div>
                </div>



            </div>






















        </div>

    </div>

    <script>
        const urlsPrefix = '{{ route('index') }}';
        function contentGeneratorCrud() {
            return {
                contentGenerators: @json($contentGenerators),
                showModal: false,
                isEditMode: false,
                modalTitle: 'Add Content Generator',
                form: {
                    id: null,
                    title: '',
                    systemPrompt: ''
                },
                openModal() {
                    this.showModal = true;
                    this.isEditMode = false;
                    this.modalTitle = 'Dodaj generator';
                    this.form = { id: null, title: '', systemPrompt: '' };
                },
                closeModal() {
                    this.showModal = false;
                },
                saveGenerator() {
                    let url = `${urlsPrefix}/content-generators`;
                    let method = 'POST';
                    if (this.isEditMode) {
                        url = `${urlsPrefix}/content-generators/${this.form.id}`;
                        method = 'PUT';
                    }
                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(this.form)
                    })
                        .then(response => response.json())
                        .then(data => {
                            data.enterUrl = `${urlsPrefix}/content-generators/${data.id}/generated-contents`;

                            if (this.isEditMode) {
                                let index = this.contentGenerators.findIndex(generator => generator.id === data.id);
                                this.contentGenerators.splice(index, 1, data);
                            } else {
                                this.contentGenerators.push(data);
                            }
                            this.closeModal();
                        });
                },
                editGenerator(id) {
                    fetch(`${urlsPrefix}/content-generators/${id}/edit`)
                        .then(response => response.json())
                        .then(data => {
                            this.form = data;
                            this.form.id = id;
                            this.isEditMode = true;
                            this.modalTitle = 'Edycja generatora';
                            this.showModal = true;
                        });
                },
                deleteGenerator(id) {
                    Swal.fire({
                        title: "Jesteś pewny?",
                        text: "Nie będzie można tego przywrócić!",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Tak, usuń generator!",
                        cancelButtonText: "Anuluj"
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                            fetch(`${urlsPrefix}/content-generators/${id}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                this.contentGenerators = this.contentGenerators.filter(generator => generator.id !== id);
                            });
                        }
                    });
                }
            }
        }
    </script>
</x-app-layout>
