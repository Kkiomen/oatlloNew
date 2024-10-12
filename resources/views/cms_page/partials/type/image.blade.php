<div class="mt-3 border p-3 border-gray-300 rounded-xl">
    <div class="mb-3">
        <i class="fa-solid fa-image"></i> - @if(!empty($element['label'])) {{ $element['label'] }} @else Obraz @endif
    </div>


    <div class="mt-5" x-data="{ open: true }">
        <div class="mt-3">
            @php
                $currentImage = empty($element['file']) ? 'storage/uploads/empty_image.jpg' : $element['file'];
                $pattern = "/asset\('(.+?)'\)/";
                if (preg_match($pattern, $currentImage, $matches)) {
                    $currentImage = $matches[1];
                }
                $currentImage = str_contains($currentImage, 'http') ? $currentImage : asset($currentImage);
            @endphp

            <div class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10 text-center ">
                <img src="{{ $currentImage }}" id="preview-image-{{ $element['key'] }}" class="h-48"/>
            </div>
            <div class="bg-black hover:bg-gray-800 text-white text-center p-2 mt-2 rounded cursor-pointer select-none" @click="open = !open">
                Zaaktualizuj obraz
            </div>
        </div>
        <div class="mt-3" x-show="!open" >
{{--        <div class="mt-3">--}}
            {{-- Kontener Drag and drop or click upload files --}}
            <div class="relative mt-2">
                <input type="file" name="image" id="image-upload-{{ $element['key'] }}" class="hidden" accept="image/*" />
                <label for="image-upload-{{ $element['key'] }}" class="flex justify-center w-full cursor-pointer rounded-lg border border-dashed border-gray-900/25 px-6 py-10 text-gray-600 hover:bg-gray-100">
                    <span class="text-center">Drag and drop or click to upload</span>
                </label>
            </div>
        </div>


        <div class="relative mt-10">
            <label for="{{ $element['key'] }}0001000alt" class="absolute -top-2 left-2 inline-block bg-white px-1 text-xs font-medium text-gray-900">Tekst alternatywny</label>
            <input type="text" name="{{ $element['key'] }}0001000alt" id="{{ $element['key'] }}0001000alt" value="{{ $element['alt'] }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
        </div>
    </div>

</div>
