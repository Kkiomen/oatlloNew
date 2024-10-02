<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New Article') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-lg border-2 border-gray-500 sm:rounded-lg">

                <!-- Displaying Global Error Message -->
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border border-red-300 text-red-600 rounded">
                        <ul class="mt-2 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('article.store') }}">
                    @csrf

                    <!-- Article Basic Information -->
                    <div class="grid gap-4 sm:grid-cols-2 mb-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('title')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug') }}" required
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('slug')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                            <input type="text" name="language" id="language" value="{{ old('language') }}"
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('language')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- SEO and Open Graph Information -->
                    <div class="grid gap-4 sm:grid-cols-2 mb-6">
                        <div>
                            <label for="seo_title" class="block text-sm font-medium text-gray-700">SEO Title</label>
                            <input type="text" name="seo_title" id="seo_title" value="{{ old('seo_title') }}"
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('seo_title')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="seo_description" class="block text-sm font-medium text-gray-700">SEO Description</label>
                            <textarea name="seo_description" id="seo_description" rows="3"
                                      class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('seo_description') }}</textarea>
                            @error('seo_description')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="open_graph_title" class="block text-sm font-medium text-gray-700">Open Graph Title</label>
                            <input type="text" name="open_graph_title" id="open_graph_title" value="{{ old('open_graph_title') }}"
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('open_graph_title')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="open_graph_description" class="block text-sm font-medium text-gray-700">Open Graph Description</label>
                            <textarea name="open_graph_description" id="open_graph_description" rows="3"
                                      class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('open_graph_description') }}</textarea>
                            @error('open_graph_description')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label for="open_graph_image" class="block text-sm font-medium text-gray-700">Open Graph Image URL</label>
                            <input type="text" name="open_graph_image" id="open_graph_image" value="{{ old('open_graph_image') }}"
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('open_graph_image')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Publication Settings -->
                    <div class="mb-6">
                        <label for="is_published" class="flex items-center space-x-2">
                            <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}
                            class="form-checkbox">
                            <span class="text-sm font-medium text-gray-700">Publish Now</span>
                        </label>
                    </div>

                    <!-- Article Content Section -->
                    <div class="p-4 bg-white border border-gray-300 rounded-lg mb-6" id="article-content-section">
                        <h3 class="text-lg font-semibold mb-4">Article Content</h3>

                        <div id="content-blocks"></div>

                        <button type="button" id="add-content-block" class="mt-4 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Add Content Block
                        </button>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="rounded-md bg-green-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                        Create Article
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Font Awesome Script (add this in your layout head if not included already) -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const contentBlocks = document.getElementById('content-blocks');

            // Add a new content block
            document.getElementById('add-content-block').addEventListener('click', () => {
                const index = contentBlocks.children.length;
                const newBlock = document.createElement('div');
                newBlock.className = 'content-block mb-4 p-2 bg-white border-b border-gray-200 relative group';
                newBlock.dataset.index = index;

                newBlock.innerHTML = `
                    <input type="hidden" name="contents[${index}][id]" value="">
                    <input type="hidden" name="contents[${index}][order_column]" value="${index + 1}" class="order-input">
                    <div class="absolute -top-6 left-0 hidden group-hover:flex items-center bg-gray-800 text-white rounded-md px-2 py-1 shadow-lg">
                        <label class="text-xs mr-2">Content Type:</label>
                        <select name="contents[${index}][type]"
                                class="appearance-none bg-gray-700 border border-gray-600 text-white py-1 px-2 pr-6 rounded focus:outline-none focus:border-indigo-500">
                            <option value="text">📝 Text</option>
                            <option value="image">🖼️ Image</option>
                        </select>
                    </div>
                    <div class="content-fields mt-2">
                        <textarea name="contents[${index}][content]" rows="5"
                                  class="block w-full p-4 bg-transparent border-none focus:outline-none resize-none text-lg leading-relaxed"
                                  placeholder="Write your article text here..." onfocus="this.classList.add('border', 'border-gray-300', 'rounded-md', 'p-4')"
                                  onblur="this.classList.remove('border', 'border-gray-300', 'rounded-md', 'p-4')"></textarea>
                    </div>
                    <div class="absolute top-0 right-0 p-2 hidden group-hover:flex space-x-2 bg-white rounded-md shadow-md">
                        <button type="button" class="move-up text-blue-500 hover:text-blue-700" title="Move Up">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button type="button" class="move-down text-blue-500 hover:text-blue-700" title="Move Down">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" class="remove-content text-red-500 hover:text-red-700" title="Remove">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                `;

                contentBlocks.appendChild(newBlock);
                updateOrder();
            });

            function updateOrder() {
                contentBlocks.querySelectorAll('.content-block').forEach((block, index) => {
                    block.querySelector('.order-input').value = index + 1;
                    block.querySelectorAll('input, select, textarea').forEach(input => {
                        const name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
                        }
                    });
                });
            }
        });
    </script>
</x-app-layout>
