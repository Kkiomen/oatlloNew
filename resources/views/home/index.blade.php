<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js" integrity="sha512-6sSYJqDreZRZGkJ3b+YfdhB3MzmuP9R7X1QZ6g5aIXhRvR1Y/N/P47jmnkENm7YL3oqsmI6AK+V6AD99uWDnIw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</head>
<body class="font-sans antialiased">
<div class="bg-white px-6 py-32 lg:px-8">
    <div class="mx-auto max-w-3xl text-base leading-7 text-gray-700">
        <p class="text-base font-semibold leading-7 text-indigo-600">Blog</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $page->name }}</h1>

        @foreach($page->sections as $section)
            <div class="section mb-8">
                @if ($section->type == 1)
                    <!-- Pełna szerokość -->
                    <div class="w-full border rounded-lg p-4">
                        @foreach($section->contents as $content)
                            @if ($content->content_type == 'text')
                                <p class="mb-4">{!! $content->text_content !!}</p>
                            @elseif ($content->content_type == 'image')
                                <img src="{{ asset('storage/'.$content->image_path) }}" alt="{{ $content->alt_text }}" class="w-full h-auto rounded-lg mb-4">
                            @endif
                        @endforeach
                    </div>
                @elseif ($section->type == 2)
                    <!-- Dwie kolumny -->
                    <div class="grid grid-cols-2 gap-4">
                        @foreach($section->contents as $content)
                            <div class="border rounded-lg p-4">
                                @if ($content->content_type == 'text')
                                    <p class="mb-4">{!! $content->text_content !!}</p>
                                @elseif ($content->content_type == 'image')
                                    <img src="{{ asset('storage/'.$content->image_path) }}" alt="{{ $content->alt_text }}" class="w-full h-auto rounded-lg mb-4">
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif ($section->type == 3)
                    <!-- Trzy kolumny -->
                    <div class="grid grid-cols-3 gap-4">
                        @foreach($section->contents as $content)
                            <div class="border rounded-lg p-4">
                                @if ($content->content_type == 'text')
                                    <p class="mb-4">{!! $content->text_content !!}</p>
                                @elseif ($content->content_type == 'image')
                                    <img src="{{ asset('storage/'.$content->image_path) }}" alt="{{ $content->alt_text }}" class="w-full h-auto rounded-lg mb-4">
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

</body>
</html>
