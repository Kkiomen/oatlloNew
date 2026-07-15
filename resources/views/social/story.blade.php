@extends('social.layout')

{{--
    Story / okładka reela (1080x1920).

    UWAGA na STREFY BEZPIECZNE: interfejs Instagrama zasłania ~250px u góry
    (avatar, pasek postępu) i ~320px u dołu (pole "Send message", strzałka
    udostępniania). Marginesy podaje SocialImageService::canvasMetrics() – NIE
    wpychaj tam treści, bo będzie nieczytelna na realnym telefonie.

    Treść jest wyśrodkowana w pasie bezpiecznym, bo story ogląda się przez
    ułamek sekundy.
--}}

@section('styles')
    .stage { align-items: flex-start; justify-content: center; }

    .story-logo {
        width: 220px;
        height: 220px;
        /* var(--ink), nie sztywna biel – patrz komentarz w announce.blade.php. */
        color: var(--ink);
        opacity: 0.9;
        margin-bottom: 56px;
    }
    .story-logo svg { width: 100%; height: 100%; display: block; }

    /* Logo jest tu jawnym elementem – znak wodny z layoutu tylko by dublował. */
    .watermark { display: none; }

    .story-body { margin-top: 44px; font-size: 40px; }

    .cta {
        margin-top: 72px;
        display: inline-flex;
        align-items: center;
        gap: 16px;
        padding: 24px 40px;
        border-radius: 999px;
        background: var(--accent);
        color: #0b1120;
        font-size: 32px;
        font-weight: 800;
    }

    .story-footer {
        position: absolute;
        left: 90px;
        bottom: {{ $padBottom - 60 }}px;
    }
@endsection

@section('content')

    <div class="pill"><span class="dot"></span>{{ $label }}</div>

    @if(trim($logo) !== '')
        <div class="story-logo" style="margin-top: 64px;">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                {!! $logo !!}
            </svg>
        </div>
    @endif

    @if($slide->headline)
        <h1 class="headline {{ $headlineClass }}" style="margin-top: 40px;">{{ $slide->headline }}</h1>
        <div class="underline" style="margin-top: 40px;"></div>
    @endif

    @if($slide->html)
        <div class="body story-body">{!! $slide->html !!}</div>
    @endif

    <div class="cta">Link in bio</div>

    <div class="story-footer">
        @include('social.partials.branding')
    </div>

@endsection
