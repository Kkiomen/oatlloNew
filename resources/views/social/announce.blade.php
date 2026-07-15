@extends('social.layout')

{{--
    Zapowiedź artykułu / kursu (1080x1350).

    Kompozycja świadomie rymuje się z okładką KURSU (covers/course-cover.blade.php):
    pigułka + tytuł + podkreślenie + duże logo technologii. Ktoś, kto widział
    okładkę kursu w Google, ma rozpoznać ten sam produkt na Instagramie.

    Tu logo jest BOHATEREM, a nie znakiem wodnym – dlatego pełna nieprzezroczystość
    i osobny blok, a nie .watermark z layoutu.
--}}

@section('styles')
    .hero-logo {
        width: 300px;
        height: 300px;
        /* var(--ink), nie sztywna biel: na skórce `spotlight` tło jest w kolorze
           akcentu i białe logo bywa niewidoczne (np. na amber). */
        color: var(--ink);
        opacity: 0.96;
        margin: 0 0 8px auto;
    }
    .hero-logo svg { width: 100%; height: 100%; display: block; }

    /* Znak wodny z layoutu zbędny – logo jest tu jawnym elementem kompozycji. */
    .watermark { display: none; }

    .announce-body { margin-top: 44px; font-size: 36px; }

    .link-line {
        margin-top: 52px;
        font-size: 30px;
        font-weight: 700;
        color: var(--accent);
    }
@endsection

@section('content')

    <div class="pill"><span class="dot"></span>{{ $announceLabel }}</div>

    @if(trim($logo) !== '')
        <div class="hero-logo" style="margin-top: 56px;">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                {!! $logo !!}
            </svg>
        </div>
    @else
        <div class="spacer"></div>
    @endif

    <h1 class="headline {{ $headlineClass }}" style="margin-top: 40px;">{{ $slide->headline ?? $post->title }}</h1>

    <div class="underline" style="margin-top: 36px;"></div>

    @if($slide->html)
        <div class="body announce-body">{!! $slide->html !!}</div>
    @endif

    @if($linkHost)
        <div class="link-line">{{ $linkHost }}{{ $sourcePath }}</div>
    @endif

    <div class="spacer"></div>

    <div class="footer">
        @include('social.partials.branding')
        <div class="meta">Link in bio</div>
    </div>

@endsection
