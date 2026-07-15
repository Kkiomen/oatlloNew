{{--
    Galeria stylów (tylko DEV): ten sam slajd we wszystkich skórkach obok siebie.

    Każdy kafelek to <iframe> z DOKŁADNĄ kanwą, przeskalowany transformem – tak samo
    jak podgląd i panel recenzji. Dzięki temu porównujemy to, co realnie zrzuci
    rasteryzator, a nie przybliżenie.

    Widoki social nie używają Tailwinda (patrz CLAUDE.md), więc ten plik też nie.
--}}
@php($scale = 0.26)
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>style: {{ $post->slug }}</title>
    <style>
        :root { --bg: #020617; --panel: #0b1220; --border: #1e293b; --text: #e2e8f0; --muted: #64748b; }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 32px 24px 64px; background: var(--bg); color: var(--text);
            font-family: ui-sans-serif, system-ui, 'Segoe UI', sans-serif;
        }
        h1 { font-size: 20px; margin: 0 0 6px; }
        .lead { font-size: 13px; color: var(--muted); margin-bottom: 6px; }
        .lead code { color: #94a3b8; }
        .slides { font-size: 13px; color: var(--muted); margin-bottom: 26px; }
        .slides a { color: #94a3b8; margin-right: 10px; }
        .slides a.on { color: #38bdf8; font-weight: 700; }

        .grid { display: flex; flex-wrap: wrap; gap: 26px; }
        .item { width: {{ $canvas['width'] * $scale }}px; }
        .head { display: flex; align-items: baseline; gap: 8px; margin-bottom: 8px; }
        .name { font-size: 14px; font-weight: 700; }
        .tag {
            font-size: 10px; text-transform: uppercase; letter-spacing: .08em;
            color: #38bdf8; border: 1px solid #0e7490; border-radius: 999px; padding: 2px 8px;
        }
        .new { color: #34d399; border-color: #065f46; }
        .summary { font-size: 11px; color: var(--muted); line-height: 1.45; margin-top: 8px; min-height: 32px; }
        .frame {
            width: {{ $canvas['width'] * $scale }}px;
            height: {{ $canvas['height'] * $scale }}px;
            overflow: hidden; border-radius: 8px; border: 1px solid var(--border); background: #000;
        }
        .frame iframe {
            width: {{ $canvas['width'] }}px; height: {{ $canvas['height'] }}px;
            border: 0; transform: scale({{ $scale }}); transform-origin: top left;
        }
    </style>
</head>
<body>
    <h1>{{ $post->slug }} &middot; {{ count($styles) }} stylów</h1>
    <div class="lead">
        <code>{{ $post->type->value }}</code> &middot; {{ $canvas['width'] }}x{{ $canvas['height'] }} &middot;
        automat wybiera: <code>{{ $auto }}</code> &middot;
        żeby przybić styl na stałe, wpisz <code>style: {{ $auto }}</code> we frontmatterze
    </div>

    @if($post->slideCount() > 1)
        <div class="slides">
            slajd:
            @foreach($post->slides as $slide)
                <a class="{{ $slide->index === $index ? 'on' : '' }}"
                   href="{{ route('social.styles', ['slug' => $post->slug, 'slide' => $slide->index]) }}">{{ $slide->index }}</a>
            @endforeach
        </div>
    @endif

    <div class="grid">
        @foreach($styles as $style)
            <div class="item">
                <div class="head">
                    <span class="name">{{ $resolver->label($style) }}</span>
                    @if($style === $auto)<span class="tag">auto</span>@endif
                    @if(in_array($style, ['neon', 'aurora', 'card', 'brutalist'], true))
                        <span class="tag new">nowy</span>
                    @endif
                </div>
                <div class="frame">
                    {{-- ?style= wymusza skórkę; ten sam slajd w każdym kafelku. --}}
                    <iframe src="{{ route('social.slide', ['slug' => $post->slug, 'index' => $index, 'style' => $style]) }}"
                            title="{{ $style }}" loading="eager"></iframe>
                </div>
                <div class="summary">{{ config('social-styles.styles.' . $style . '.summary') }}</div>
            </div>
        @endforeach
    </div>
</body>
</html>
