@extends('social.layout')

{{--
    Slajd karuzeli (1080x1350). Layout rozgałęzia się po $slide->role:

    hook (slajd 1) – największa typografia, bez numeru slajdu. To on jest
                     miniaturą w feedzie i decyduje, czy ktokolwiek przesunie
                     palcem. Ma być czysty.
    body           – kicker + nagłówek + treść + numer + "Swipe".
    cta (ostatni)  – nagłówek + treść + link + "Link in bio", bez "Swipe".
--}}

@section('styles')
    .kicker {
        font-size: 26px;
        font-weight: 700;
        color: var(--accent);
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 26px;
    }

    .top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 56px;
    }

    .slide-no {
        font-size: 26px;
        font-weight: 700;
        color: #64748b;
        background: rgba(148,163,184,0.10);
        border-radius: 999px;
        padding: 12px 24px;
        letter-spacing: 0.06em;
    }

    .swipe {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 26px;
        font-weight: 700;
        color: var(--accent);
    }
    .swipe svg { width: 26px; height: 26px; }

    .hook-body { margin-top: 40px; font-size: 38px; }

    .link-pill {
        display: inline-flex;
        align-items: center;
        gap: 14px;
        align-self: flex-start;
        margin-top: 44px;
        padding: 20px 34px;
        border-radius: 999px;
        border: 3px solid var(--accent);
        color: var(--accent);
        font-size: 30px;
        font-weight: 800;
    }

    .link-note { margin-top: 20px; font-size: 26px; font-weight: 600; color: #64748b; }
@endsection

@section('content')

    @if($slide->isHook())

        {{-- HOOK: pigułka technologii, wielki nagłówek, podkreślenie, krótka treść. --}}
        <div class="top" style="margin-bottom: 0;">
            <div class="pill"><span class="dot"></span>{{ $label }}</div>
        </div>

        <div class="spacer"></div>

        @if($slide->headline)
            <h1 class="headline {{ $headlineClass }}">{{ $slide->headline }}</h1>
        @endif

        <div class="underline" style="margin-top: 40px;"></div>

        @if($slide->html)
            <div class="body hook-body">{!! $slide->html !!}</div>
        @endif

        <div class="spacer"></div>

        <div class="footer">
            @include('social.partials.branding')
            @include('social.partials.swipe')
        </div>

    @else

        {{-- BODY / CTA --}}
        <div class="top">
            <div class="kicker" style="margin-bottom: 0;">{{ $slide->isCta() ? 'Recap' : $label }}</div>
            @include('social.partials.slide-number')
        </div>

        @if($slide->headline)
            <h2 class="headline {{ $headlineClass }}">{{ $slide->headline }}</h2>
            <div class="underline" style="margin-top: 34px;"></div>
        @endif

        @if($slide->html)
            <div class="body" style="margin-top: 44px;">{!! $slide->html !!}</div>
        @endif

        @if($slide->isCta() && $linkHost)
            <div class="link-pill">{{ $linkHost }}</div>
            <div class="link-note">Link in bio</div>
        @endif

        <div class="spacer"></div>

        <div class="footer">
            @include('social.partials.branding')
            @if($slide->isCta())
                <div class="meta">{{ $brandHandle }}</div>
            @else
                @include('social.partials.swipe')
            @endif
        </div>

    @endif

@endsection
