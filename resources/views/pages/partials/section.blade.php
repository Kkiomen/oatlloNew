<div class="section border border-white hover:border-gray-300 focus:border-gray-300 rounded-lg mb-6 p-4 transition-all cursor-pointer section-handle" data-section-id="{{ $section->id }}">
    <div class="flex justify-end items-center mb-4 section-header hidden">
        <div class="flex gap-3 bg-gray-600 px-3 py-1 rounded">
            <button class="delete-section-btn text-gray-50 hover:text-gray-400">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    </div>
    <div class="grid gap-1" style="grid-template-columns: repeat({{ $section->type }}, 1fr);">
        @for($i = 1; $i <= $section->type; $i++)
            @php
                $content = $section->contents->where('position', $i)->first();
            @endphp
            <div class="rounded-lg p-1">
                @if($content)
                    @if($content->content_type == 'text')
                        <textarea class="rich-text-editor w-full h-40" data-content-id="{{ $content->id }}" data-type="text">{{ $content->text_content }}</textarea>
                    @elseif($content->content_type == 'image')
                        <div data-content-id="{{ $content->id }}" data-type="image">
                            <div class="mb-2">
                                <img src="{{ asset('storage/'.$content->image_path) }}" alt="{{ $content->alt_text }}" class="w-full h-auto rounded-lg preview-image" data-position-id="{{ $i }}">
                            </div>
                            <input type="file" class="image-input w-full mb-2" data-content-id="{{ $content->id }}" data-position-id="{{ $i }}">
                            <input type="text" class="text_alt w-full border rounded px-2 py-1" placeholder="Alt text" value="{{ $content->alt_text }}" >
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center h-full">
                        <button class="select-content-type-btn bg-blue-500 text-white px-4 py-2 rounded-lg" data-position="{{ $i }}">
                            <i class="fas fa-plus mr-2"></i> Dodaj treść
                        </button>
                    </div>
                @endif
            </div>
        @endfor
    </div>
</div>
