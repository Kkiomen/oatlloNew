<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Article') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

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

            <form method="POST" action="{{ route('article.update', $article->id) }}">
                @csrf
                @method('PUT')

                <!-- Basic Information Section -->
                <div class="p-4 sm:p-8 bg-white shadow-lg border-2 border-gray-500 sm:rounded-lg mb-6">
                    <h3 class="text-lg font-semibold">Basic Information</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" id="title" value="{{ old('title', $article->title) }}"
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug', $article->slug) }}"
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                            <input type="text" name="language" id="language" value="{{ old('language', $article->language) }}"
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                <!-- SEO and Open Graph Section -->
                <div class="p-4 sm:p-8 bg-white shadow-lg border-2 border-gray-500 sm:rounded-lg mb-6">
                    <h3 class="text-lg font-semibold">SEO and Open Graph</h3>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="seo_title" class="block text-sm font-medium text-gray-700">SEO Title</label>
                            <input type="text" name="seo_title" id="seo_title" value="{{ old('seo_title', $article->seo_title) }}"
                                   class="block w-full p-2 mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="seo_description" class="block text-sm font-medium text-gray-700">SEO Description</label>
                            <textarea name="seo_description" id="seo_description" rows="3"
                                      class="block w-full p-2 mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('seo_description', $article->seo_description) }}</textarea>
                        </div>
                        <div>
                            <label for="open_graph_title" class="block text-sm font-medium text-gray-700">Open Graph Title</label>
                            <input type="text" name="open_graph_title" id="open_graph_title" value="{{ old('open_graph_title', $article->open_graph_title) }}"
                                   class="block w-full p-2 mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="open_graph_description" class="block text-sm font-medium text-gray-700">Open Graph Description</label>
                            <textarea name="open_graph_description" id="open_graph_description" rows="3"
                                      class="block w-full p-2 mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('open_graph_description', $article->open_graph_description) }}</textarea>
                        </div>
                        <div>
                            <label for="open_graph_image" class="block text-sm font-medium text-gray-700">Open Graph Image URL</label>
                            <input type="text" name="open_graph_image" id="open_graph_image" value="{{ old('open_graph_image', $article->open_graph_image) }}"
                                   class="block w-full p-2 mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                <!-- Publication Section -->
                <div class="p-4 sm:p-8 bg-white shadow-lg border-2 border-gray-500 sm:rounded-lg mb-6">
                    <h3 class="text-lg font-semibold">Publication</h3>
                    <div class="flex items-center">
                        <label for="is_published" class="block text-sm font-medium text-gray-700 mr-4">Published</label>
                        <input type="checkbox" name="is_published" id="is_published" value="1" {{ $article->is_published ? 'checked' : '' }} class="form-checkbox">
                        <input type="text" name="published_at" value="{{$article->published_at}}" disabled
{{--                        <input type="text" name="published_at" value="{{ $article->published_at ? $article->published_at->format('d-m-Y H:i') : '' }}" disabled--}}
                               class="ml-4 block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>

                <!-- Article Content Section -->
                <div class="p-4 sm:p-8 bg-white shadow-lg border-2 border-gray-500 sm:rounded-lg mb-6" id="article-content-section">
                    <h3 class="text-lg font-semibold mb-4">Article Content</h3>

                    <div id="content-blocks">
                        @foreach($article->contents->sortBy('order_column') as $content)
                            <div class="content-block mb-4 p-2 bg-white border-b border-gray-200 relative group" data-index="{{ $loop->index }}">
                                <input type="hidden" name="contents[{{ $loop->index }}][id]" value="{{ $content->id }}">
                                <input type="hidden" name="contents[{{ $loop->index }}][order_column]" value="{{ $content->order_column }}" class="order-input">

                                <!-- Floating Type Selection Menu Positioned Above the Content Block -->
                                <div class="absolute -top-6 left-0 hidden group-hover:flex items-center bg-gray-800 text-white rounded-md px-2 py-1 shadow-lg">
                                    <label class="text-xs mr-2">Content Type:</label>
                                    <select name="contents[{{ $loop->index }}][type]"
                                            class="appearance-none bg-gray-700 border border-gray-600 text-white py-1 px-2 pr-6 rounded focus:outline-none focus:border-indigo-500">
                                        <option value="text" {{ $content->type == 'text' ? 'selected' : '' }}>📝 Text</option>
                                        <option value="image" {{ $content->type == 'image' ? 'selected' : '' }}>🖼️ Image</option>
                                    </select>
                                </div>

                                <!-- Content Fields -->
                                <div class="content-fields mt-2">
                                    @if($content->type == 'text')
                                        <textarea name="contents[{{ $loop->index }}][content]" rows="5"
                                                  class="block w-full p-4 bg-transparent border-none focus:outline-none resize-none text-lg leading-relaxed"
                                                  placeholder="Write your article text here..." onfocus="this.classList.add('border', 'border-gray-300', 'rounded-md', 'p-4')"
                                                  onblur="this.classList.remove('border', 'border-gray-300', 'rounded-md', 'p-4')">{{ $content->content }}</textarea>
                                    @elseif($content->type == 'image')
                                        <img src="{{ $content->content }}" alt="Article Image" class="w-full h-auto mb-4 rounded-md">
                                        <input type="text" name="contents[{{ $loop->index }}][content]" value="{{ $content->content }}"
                                               class="block w-full p-2 bg-gray-50 border border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm mt-2"
                                               placeholder="Paste the image URL here..." style="display: none;">
                                    @endif
                                </div>

                                <!-- Hover Actions for Content Block -->
                                <div class="absolute top-0 right-0 p-2 hidden group-hover:flex space-x-2 bg-white rounded-md shadow-md">
                                    <button type="button" class="move-up text-blue-500 hover:text-blue-700" title="Move Up">
                                        <i class="fas fa-arrow-up"></i>
                                    </button>
                                    <button type="button" class="move-down text-blue-500 hover:text-blue-700" title="Move Down">
                                        <i class="fas fa-arrow-down"></i>
                                    </button>
                                    <button type="button" class="edit-content text-yellow-500 hover:text-yellow-700" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="remove-content text-red-500 hover:text-red-700" title="Remove">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-content-block" class="mt-4 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        Add Content Block
                    </button>
                </div>

                <!-- Submit Button -->
                <div class="p-4 sm:p-8 bg-white shadow-lg border-2 border-gray-500 sm:rounded-lg">
                    <button type="submit" class="block w-full rounded-md bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript to handle content block actions -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const contentBlocks = document.getElementById('content-blocks');

            // Update order_column inputs after reordering
            function updateOrder() {
                contentBlocks.querySelectorAll('.content-block').forEach((block, index) => {
                    block.dataset.index = index;
                    block.querySelector('.order-input').value = index + 1;

                    // Update input names based on new index
                    block.querySelectorAll('input, select, textarea').forEach(input => {
                        const name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/\[\d+\]/, `[${index}]`));
                        }
                    });
                });
            }

            // Attach event listeners to move-up, move-down, edit, and remove buttons
            contentBlocks.addEventListener('click', function (event) {
                const target = event.target.closest('button');
                if (!target) return;

                const block = target.closest('.content-block');

                if (target.classList.contains('move-up')) {
                    if (block.previousElementSibling) {
                        block.parentNode.insertBefore(block, block.previousElementSibling);
                        updateOrder();
                    }
                } else if (target.classList.contains('move-down')) {
                    if (block.nextElementSibling) {
                        block.parentNode.insertBefore(block.nextElementSibling, block);
                        updateOrder();
                    }
                } else if (target.classList.contains('remove-content')) {
                    block.remove();
                    updateOrder();
                } else if (target.classList.contains('edit-content')) {
                    const input = block.querySelector('input[type="text"], textarea');
                    input.focus();
                }
            });

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
                    <button type="button" class="edit-content text-yellow-500 hover:text-yellow-700" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="remove-content text-red-500 hover:text-red-700" title="Remove">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;

                contentBlocks.appendChild(newBlock);
                updateOrder();
            });

            updateOrder(); // Initialize the order on page load
        });
    </script>
</x-app-layout>
