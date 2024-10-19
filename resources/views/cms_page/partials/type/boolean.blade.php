<div class="mt-3 border p-3 border-gray-300 rounded-xl">
    <div class="mb-3">
        <i class="fa-solid fa-square-check"></i> - @if(!empty($element['label'])) {{ $element['label'] }} @else Opcja @endif
    </div>

    <div class="mt-5">
        <div class="relative flex items-start">
            <div class="flex h-6 items-center">
                <input  aria-describedby="{{ $element['key'] }}" name="{{ $element['key'] }}0001000" id="{{ $element['key'] }}0001000" @if($element['value'] === true || $element['value'] == 1 || $element['value'] === '1') checked @endif type="checkbox" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
            </div>
            <div class="ml-3 text-sm leading-6">
                <label for="{{ $element['key'] }}" class="font-medium text-gray-900">@if(!empty($element['label'])) {{ $element['label'] }} @endif</label>
            </div>
        </div>
    </div>

</div>
