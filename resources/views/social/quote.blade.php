@extends('social.layout')

{{--
    Pojedynczy kafelek z kodem / tezą (1080x1350).

    Kompozycja jest rymem wizualnym z okładką ARTYKUŁU ("okno kodu",
    covers/code-window.blade.php): pasek tytułowy z trzema kropkami, w środku
    kod. Dzięki temu post na Instagramie i miniatura artykułu w Google są
    rozpoznawalnie z tej samej rodziny.
--}}

@section('styles')
    .kicker {
        font-size: 26px;
        font-weight: 700;
        color: var(--accent);
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 56px;
    }

    /* Pasek tytułowy okna kodu z "światłami" – ten sam motyw co okładka artykułu. */
    .window-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 22px 30px;
        background: rgba(148,163,184,0.08);
        border-bottom: 2px solid rgba(148,163,184,0.16);
        border-radius: 18px 18px 0 0;
    }
    .window-bar .light { width: 16px; height: 16px; border-radius: 50%; }
    .light-1 { background: #ff5f57; }
    .light-2 { background: #febc2e; }
    .light-3 { background: #28c840; }
    .window-name {
        margin-left: 16px;
        font-family: {!! $mono !!};
        font-size: 22px;
        color: #64748b;
    }

    .window { border: 2px solid rgba(148,163,184,0.16); border-radius: 20px; overflow: hidden; }

    /* Kod jest bohaterem tego formatu, więc okno przejmuje styl <pre> z layoutu. */
    .window .body pre {
        border: 0;
        border-radius: 0;
        margin: 0;
        padding: 40px 34px;
        background: rgba(2,6,23,0.55);
    }
    /* 30px, identycznie jak w karuzeli. Przy 32px w kanwę wchodzi tylko 47 kolumn
       i dłuższe linie po cichu wyjeżdżały za prawą krawędź (pre ma overflow:hidden,
       więc nic nie protestuje). Zmiana tej wartości = przelicz code_cols_max. */
    .window .body pre code { font-size: 30px; }

    .takeaway { margin-top: 48px; font-size: 36px; }
@endsection

@section('content')

    <div class="top">
        <div class="kicker">{{ $label }}</div>
        <div class="pill" style="padding: 12px 24px; font-size: 24px;">
            <span class="dot"></span>{{ $post->sourceType === 'course' ? 'Free course' : 'Tip' }}
        </div>
    </div>

    @if($slide->headline)
        <h1 class="headline {{ $headlineClass }}">{{ $slide->headline }}</h1>
        <div class="underline" style="margin-top: 34px;"></div>
    @endif

    @if($codeHtml)
        <div class="window" style="margin-top: 52px;">
            <div class="window-bar">
                <span class="light light-1"></span>
                <span class="light light-2"></span>
                <span class="light light-3"></span>
                <span class="window-name">{{ $fileName }}</span>
            </div>
            <div class="body">{!! $codeHtml !!}</div>
        </div>
    @endif

    @if($proseHtml)
        <div class="body takeaway">{!! $proseHtml !!}</div>
    @endif

    <div class="spacer"></div>

    <div class="footer">
        @include('social.partials.branding')
        <div class="meta">{{ $brandHandle }}</div>
    </div>

@endsection
