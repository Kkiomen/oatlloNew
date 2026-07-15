{{--
    Wspólny szkielet grafiki social media.

    STYL = SKÓRKA CSS, nie osobny widok. Wszystko, co skórka może zmienić, jest tu
    zmienną CSS (--bg, --ink, --muted, --rule, --code-bg...). Dzięki temu 6 stylów
    x 4 typy to 6 plików skórek, a nie 24 widoki. Skórki żyją w
    resources/views/social/styles/, dobiera je SocialStyleResolver.

    CELOWO bez Tailwinda: kanwa to cel o STAŁYCH pikselach (1080x1350 / 1080x1920),
    a nie responsywna strona. Wciągnięcie tu tailwind.css oznaczałoby safelistę
    i `npm run css:public` przy każdej zmianie grafiki – zero zysku. Dzięki temu
    moduł social NIGDY nie wymaga rebuildu CSS.

    Font jest wklejony w base64 (EmbeddedFontProvider) – headless renderujący plik
    z dysku nie zna Montserrata i bez tego podmieniłby go na font systemowy.
--}}
<!doctype html>
<html lang="{{ $post->language }}">
<head>
    <meta charset="utf-8">
    <title>{{ $post->slug }} - {{ $slide->number() }} - {{ $style }}</title>
    <style>
        {!! $fontCss !!}

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            background: #0b1120;
            /* Zrzut ekranu nie może złapać paska przewijania. */
            overflow: hidden;
        }

        .canvas {
            /* Wymiary MUSZĄ być dokładne – rasteryzator weryfikuje je getimagesize(). */
            width: {{ $width }}px;
            height: {{ $height }}px;
            position: relative;
            overflow: hidden;

            /* Wartości domyślne. Skórka nadpisuje je niżej – dlatego jej blok
               jest dołączany PO tym. */
            --ink: #f1f5f9;
            --muted: #94a3b8;
            --strong: #f1f5f9;
            --rule: rgba(148,163,184,0.16);
            --chip: rgba(148,163,184,0.10);
            --chip-ink: #64748b;
            --code-bg: #0b1120;
            --code-ink: #e2e8f0;
            --code-chrome: rgba(148,163,184,0.08);
            --watermark-ink: #f8fafc;
            --watermark-opacity: 0.07;
            --glow-top: 0.20;
            --glow-bottom: 0.12;
            --bar: var(--accent);

            background: linear-gradient(135deg, #0b1120 0%, #0f172a 100%);
            font-family: 'Montserrat', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        /* Pasek akcentu u góry – ten sam motyw co okładki artykułów i kursów. */
        .bar {
            position: absolute; top: 0; left: 0; right: 0;
            height: 10px;
            background: var(--bar);
            z-index: 4;
        }

        .glow { position: absolute; border-radius: 50%; z-index: 0; }
        .glow-top {
            width: 760px; height: 760px; top: -300px; right: -220px;
            background: radial-gradient(circle, var(--accent) 0%, transparent 70%);
            opacity: var(--glow-top);
        }
        .glow-bottom {
            width: 560px; height: 560px; bottom: -220px; left: -180px;
            background: radial-gradient(circle, var(--accent) 0%, transparent 70%);
            opacity: var(--glow-bottom);
        }

        /* Logo technologii jako znak wodny – te same logo co okładki kursów
           (config/course-covers.php), tylko w innej skali i wyciszeniu.

           MUSI mieścić się w całości w kanwie. Logo są zwartymi znakami na kanwie
           0 0 100 100 (blokowe "L" Laravela, wieloryb Dockera, ster Kubernetesa) –
           przycięte przy krawędzi przestają czytać się jako logo i wyglądają jak
           przypadkowy prostokąt. */
        .watermark {
            position: absolute;
            right: 72px;
            bottom: {{ $watermarkBottom ?? 168 }}px;
            width: {{ $watermarkSize ?? 400 }}px;
            height: {{ $watermarkSize ?? 400 }}px;
            color: var(--watermark-ink);
            opacity: var(--watermark-opacity);
            z-index: 0;
        }
        .watermark svg { width: 100%; height: 100%; display: block; }

        .stage {
            position: relative;
            z-index: 2;
            height: 100%;
            padding: {{ $padTop ?? 96 }}px 90px {{ $padBottom ?? 96 }}px;
            display: flex;
            flex-direction: column;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            align-self: flex-start;
            padding: 14px 28px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 700;
            font-size: 26px;
            letter-spacing: 0.01em;
        }
        .pill .dot { width: 12px; height: 12px; border-radius: 50%; background: var(--accent); }

        .headline {
            font-weight: 800;
            letter-spacing: -0.025em;
            line-height: 1.07;
            color: var(--ink);
            text-wrap: balance;
        }
        .h-xxl { font-size: 92px; }
        .h-xl  { font-size: 78px; }
        .h-l   { font-size: 64px; }
        .h-m   { font-size: 54px; }

        .underline {
            width: 88px; height: 8px; border-radius: 4px;
            background: var(--accent);
            flex: none;
        }

        /* Treść slajdu (wyrenderowany Markdown). */
        .body { font-size: 34px; line-height: 1.45; font-weight: 400; color: var(--muted); }
        .body p { margin: 0 0 22px; }
        .body p:last-child { margin-bottom: 0; }
        .body strong { color: var(--strong); font-weight: 700; }
        .body em { color: var(--strong); font-style: normal; font-weight: 600; }
        .body a { color: var(--accent); text-decoration: none; }
        .body ul, .body ol { margin: 0 0 22px 30px; }
        .body li { margin-bottom: 12px; }
        .body hr { border: 0; height: 2px; background: var(--rule); margin: 28px 0; }

        .body code {
            font-family: {!! $mono !!};
            font-size: 0.86em;
            background: var(--chip);
            color: var(--strong);
            padding: 4px 10px;
            border-radius: 8px;
        }

        /* Blok kodu jako "okno kodu" – rym wizualny z okładką artykułu. */
        .body pre {
            background: var(--code-bg);
            border: 2px solid var(--rule);
            border-radius: 18px;
            padding: 30px 32px;
            margin: 0 0 24px;
            overflow: hidden;
        }
        .body pre code {
            display: block;
            background: none;
            padding: 0;
            border-radius: 0;
            /* 30px: przy tym rozmiarze w kanwę wchodzi ~50 kolumn, a lint pilnuje 46.
               Zmiana => przelicz social.limits.code_cols_max. */
            font-size: 30px;
            line-height: 1.5;
            color: var(--code-ink);
            white-space: pre;
        }

        .spacer { flex: 1 1 auto; }

        .footer {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
        }

        .brand { font-size: 32px; font-weight: 700; color: var(--ink); letter-spacing: -0.01em; }
        .brand .tld { color: var(--accent); }

        .meta { font-size: 24px; font-weight: 600; color: var(--chip-ink); }

        @yield('styles')

        {{--
            Skórka na samym końcu – musi wygrać z bazą i z sekcją stylów widoku.

            To MUSI być komentarz Blade'a, nie CSS-a. Komentarz CSS z dyrektywą
            w środku zostaje rozwinięty przez Blade'a, a wklejona sekcja stylów
            sama zawiera komentarze – a te w CSS SIĘ NIE ZAGNIEŻDŻAJĄ. Pierwszy
            `*/` zamykał wtedy komentarz za wcześnie, osierocony `*/` był błędem
            parsowania i zjadał całą regułę skórki. Objawiało się to tak, że styl
            działał na jednych typach postów, a na innych nie.
        --}}
        @include('social.styles.' . $style)
    </style>
</head>
<body>
{{-- data-slide-no zasila styl `editorial` (wielki numer w tle) przez attr() –
     dzięki temu skórka nie potrzebuje własnego markupu. --}}
<div class="canvas style-{{ $style }}"
     data-slide-no="{{ sprintf('%02d', $slide->index) }}"
     style="--accent: {{ $accent }}; --accent-soft: {{ $accentSoft }}; --accent-faint: {{ $accentFaint }};
            --accent-ink: {{ $accentInk }}; --accent-ink-soft: {{ $accentInkSoft }};">
    <div class="bar"></div>
    <div class="glow glow-top"></div>
    <div class="glow glow-bottom"></div>

    @includeWhen(trim($logo) !== '', 'social.partials.watermark')

    @includeWhen($styleChrome !== null, 'social.partials.chrome-' . $styleChrome)

    <div class="stage">
        @yield('content')
    </div>
</div>
</body>
</html>
