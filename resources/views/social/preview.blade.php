{{--
    Podgląd całego posta (tylko DEV). Każdy slajd ląduje w <iframe> o DOKŁADNYCH
    wymiarach kanwy i jest przeskalowany do oglądania – dzięki temu widzimy
    dokładnie to, co zrzuci rasteryzator, bez zgadywania.
--}}
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>social preview: {{ $post->slug }}</title>
    <style>
        body { margin: 0; padding: 40px; background: #020617; color: #e2e8f0;
               font-family: ui-sans-serif, system-ui, 'Segoe UI', sans-serif; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        .meta { font-size: 14px; color: #64748b; margin-bottom: 28px; }
        .meta code { color: #94a3b8; }
        .slides { display: flex; flex-wrap: wrap; gap: 32px; }
        .slide { }
        .no { font-size: 13px; color: #64748b; margin-bottom: 8px; }
        /* Iframe ma pełną kanwę i jest zmniejszany transformem – żadnego
           przeliczania layoutu, więc podgląd jest wierny. */
        .frame {
            width: {{ $canvas['width'] * 0.36 }}px;
            height: {{ $canvas['height'] * 0.36 }}px;
            overflow: hidden;
            border-radius: 10px;
            border: 1px solid #1e293b;
        }
        .frame iframe {
            width: {{ $canvas['width'] }}px;
            height: {{ $canvas['height'] }}px;
            border: 0;
            transform: scale(0.36);
            transform-origin: top left;
        }
    </style>
</head>
<body>
    <h1>{{ $post->slug }}</h1>
    <div class="meta">
        <code>{{ $post->type->value }}</code> &middot;
        {{ $canvas['width'] }}x{{ $canvas['height'] }} &middot;
        {{ $post->slideCount() }} slajd(ów) &middot;
        status: <code>{{ $post->status }}</code>
    </div>

    <div class="slides">
        @foreach($post->slides as $slide)
            <div class="slide">
                <div class="no">{{ $slide->number() }} &middot; {{ $slide->role }}</div>
                <div class="frame">
                    <iframe src="{{ route('social.slide', ['slug' => $post->slug, 'index' => $slide->index]) }}"
                            title="{{ $slide->number() }}"></iframe>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
