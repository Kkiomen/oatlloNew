<div class="grid bg-gray-100 rounded-2xl my-6 p-10">
    <div class="border-b-2 border-gray-200 mb-5 pb-3">
        Wyszczególnione podsekcje
    </div>

    <div class="relative isolate " id="contact">
        <div class="grid grid-cols-1 lg:grid-cols-{{ count($section['subsections']) }}">

            @foreach($section['subsections'] as $subSection)
                <div class="shadow shadow-lg m-2 rounded-2xl p-3 border border-white-200 bg-white">

                    @if(!empty($subSection['content']))

                        @foreach($subSection['content'] as $content)
                            @include('cms_page.partials.content', ['content' => $content])
                        @endforeach

                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
