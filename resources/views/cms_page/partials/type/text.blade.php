<div class="mt-3 border p-3 border-gray-300 rounded-xl">
    <div class="mb-3">
        <i class="fa-solid fa-font"></i> - @if(!empty($element['label'])) {{ $element['label'] }} @else Tekst @endif
    </div>

    <div class="mt-5">
        @if($element['type'] == 'text')
            <div class="relative mt-3">
                <label for="{{ $element['key'] }}0001000" class="absolute -top-2 left-2 inline-block bg-white px-1 text-xs font-medium text-gray-900">Treść</label>
                <input type="text" name="{{ $element['key'] }}0001000" id="{{ $element['key'] }}0001000" value="{{ $element['value'] }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"/>
            </div>
        @elseif($element['type'] == 'textarea')
            <label for="{{ $element['key'] }}0001000" class="block text-sm font-medium leading-6 text-gray-900">Treść</label>
            <div class="mt-2">
                <textarea rows="4" name="{{ $element['key'] }}0001000" id="{{ $element['key'] }}0001000" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{ $element['value'] }}</textarea>
            </div>
        @endif
    </div>

</div>
