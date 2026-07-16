{{--
    ANNOUNCE-ARTICLE — dedykowana skórka pod story "nowy artykuł na blogu".

    Powód istnienia: te story idą SERIĄ (jedno na każdy publikowany artykuł, 3-4
    w tygodniu). Mają być NATYCHMIAST rozpoznawalne, żeby ktoś, kto regularnie
    ogląda nasze story, po ułamku sekundy wiedział "aha, jest nowy artykuł, mogę
    wejść i przeczytać". Dlatego stały, niezmienny baner ("NEW ON THE BLOG") jest
    znakiem rozpoznawczym serii, a akcent i logo zmieniają się per technologia
    artykułu (przez TechThemeResolver) — kolor mówi O CZYM jest tekst.

    To jest jawna skórka (generator wpisuje `style: announce-article` do .md), więc
    NIE ma jej w rotacji ani w type_rotation — dopisanie tam przetasowałoby style
    wszystkich innych postów (crc32 % liczba pozycji). Stosuje się TYLKO tam, gdzie
    ktoś ją wskaże jawnie.

    Baner używa var(--accent-ink), a nie sztywnej bieli: akcenty bywają jasne
    (amber) i ciemne (czerwień Laravela), a --accent-ink policzył SocialImageService
    z luminancji WCAG, więc tekst na banerze jest czytelny na KAŻDEJ technologii.
    (Ta sama zasada co w spotlight.blade.php.)

    Geometrii .stage NIE ruszamy — .story-footer kotwiczy się do niej (patrz mina
    #3 w CLAUDE.md), więc bezpieczny margines story zostaje policzony raz.
--}}
.canvas.style-announce-article {
    --ink: #f8fafc;
    --muted: rgba(248,250,252,0.86);
    --strong: #ffffff;
    --rule: rgba(148,163,184,0.20);
    --glow-top: 0.34;
    --glow-bottom: 0.18;

    background:
        radial-gradient(circle at 50% 10%, var(--accent-faint) 0%, transparent 55%),
        linear-gradient(160deg, #0b1120 0%, #0e1a2e 100%);
}

{{-- Stały baner serii — pseudo-element, więc tożsamość żyje w skórce, bez markupu
     w widoku. Pierwszy element w kolumnie .stage, nad logo. --}}
.canvas.style-announce-article .stage::before {
    content: "{{ config('social.article_story.kicker', 'NEW ON THE BLOG') }}";
    align-self: flex-start;
    margin: 0 0 44px;
    padding: 18px 34px;
    border-radius: 16px;
    background: var(--accent);
    color: var(--accent-ink);
    font-size: 30px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

{{-- Pigułka z etykietą technologii dublowałaby logo — baner niesie tożsamość,
     logo niesie kolor/temat, pigułka byłaby trzecim komunikatem o tym samym. --}}
.canvas.style-announce-article .pill { display: none; }

{{-- Podkreślenie i CTA na akcencie z atramentem liczonym z kontrastu (WCAG),
     nie na sztywnej bieli — inaczej "Link in bio" znikałby na jasnym akcencie. --}}
.canvas.style-announce-article .cta {
    background: var(--accent);
    color: var(--accent-ink);
}
