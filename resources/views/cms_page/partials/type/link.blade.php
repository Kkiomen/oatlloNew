<div class="mt-3 border p-3 border-gray-300 rounded-xl">
    <div class="mb-3">
        <i class="fa-solid fa-link"></i> - @if(!empty($element['label'])) {{ $element['label'] }} @else Link @endif
    </div>

    <div class="mt-5">
        <div class="mt-3">
            <label for="{{ $element['key'] }}0001000href" class="block text-sm font-medium leading-6 text-gray-900">Url - do którego ma prowadzić po klinięciu</label>
            <div class="mt-2 flex rounded-md shadow-sm">
                <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 px-3 text-gray-500 sm:text-sm">Link</span>
                <input type="text" name="{{ $element['key'] }}0001000href" id="{{ $element['key'] }}0001000href" value="{{ $element['href'] }}" class="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="https://www.example.com">
            </div>
        </div>
    </div>

</div>

