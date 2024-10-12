<div class="mt-3 border p-3 border-gray-300 rounded-xl">
    <div class="mb-3">
        <i class="fa-regular fa-hand-point-up"></i> - Przycisk
    </div>

    <div class="mt-5">
        <div class="relative mt-3">
            <label for="{{ $element['key'] }}0001000" class="absolute -top-2 left-2 inline-block bg-white px-1 text-xs font-medium text-gray-900">Treść w przycisku</label>
            <input type="text" name="{{ $element['key'] }}0001000" id="{{ $element['key'] }}0001000" value="{{ $element['value'] }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
        </div>

        <div class="mt-3">
            <label for="{{ $element['key'] }}0001000url" class="block text-sm font-medium leading-6 text-gray-900">Url - do którego ma prowadzić po klinięciu</label>
            <div class="mt-2 flex rounded-md shadow-sm">
                <span class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 px-3 text-gray-500 sm:text-sm">Link</span>
                <input type="text" name="{{ $element['key'] }}0001000url" id="{{ $element['key'] }}0001000url" value="{{ $element['href'] }}" class="block w-full min-w-0 flex-1 rounded-none rounded-r-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="https://www.example.com">
            </div>
        </div>
    </div>

</div>

