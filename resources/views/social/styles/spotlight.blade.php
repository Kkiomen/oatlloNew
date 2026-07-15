{{--
    SPOTLIGHT — akcent technologii na całej kanwie.

    Najgłośniejszy styl w pakiecie: w miniaturze feedu to plama koloru, nie kolejny
    ciemny kafelek. Dlatego trafia na story (ogląda się je ułamek sekundy).

    KLUCZOWE: kolor tekstu (--accent-ink) NIE jest tu zapisany na sztywno. Liczy go
    SocialImageService::inkFor() z luminancji akcentu wg WCAG, bo akcenty bywają
    jasne (amber #fbbf24 chce ciemnego tekstu) i ciemne (czerwień Laravela #ff2d20
    chce jasnego). Sztywny kolor byłby nieczytelny na połowie technologii.
--}}
.canvas.style-spotlight {
    --ink: var(--accent-ink);
    --muted: var(--accent-ink-soft);
    --strong: var(--accent-ink);
    --rule: var(--accent-ink-soft);
    --chip: rgba(0,0,0,0.10);
    --chip-ink: var(--accent-ink-soft);
    /* Kod na kolorowym tle musi mieć własne, ciemne okno – inaczej nie da się go czytać. */
    --code-bg: #0b1120;
    --code-ink: #e2e8f0;
    --watermark-ink: var(--accent-ink);
    --watermark-opacity: 0.12;
    --glow-top: 0;
    --glow-bottom: 0;
    /* Pasek akcentu na tle akcentu byłby niewidoczny – odwracamy go na atrament. */
    --bar: var(--accent-ink);

    background: var(--accent);
}

/* Pigułka: na pełnym akcencie wersja "akcent na przygaszonym akcencie" znika.
   Odwracamy ją na atrament. */
.canvas.style-spotlight .pill {
    background: rgba(0,0,0,0.12);
    color: var(--accent-ink);
}
.canvas.style-spotlight .pill .dot { background: var(--accent-ink); }

/* Podkreślenie i marka też muszą zejść z koloru akcentu na atrament. */
.canvas.style-spotlight .underline { background: var(--accent-ink); }
.canvas.style-spotlight .brand .tld { color: var(--accent-ink-soft); }
.canvas.style-spotlight .body a { color: var(--accent-ink); text-decoration: underline; }

/* `:not(pre) > code` – bez tego trafiałoby też w kod w bloku, który siedzi na
   własnym ciemnym tle (--code-bg) i zniknąłby. Patrz komentarz w paper.blade.php. */
.canvas.style-spotlight .body :not(pre) > code { color: var(--accent-ink); }

/* Elementy widoków, które normalnie niosą kolor akcentu. */
.canvas.style-spotlight .kicker,
.canvas.style-spotlight .swipe,
.canvas.style-spotlight .link-line { color: var(--accent-ink); }

.canvas.style-spotlight .link-pill {
    border-color: var(--accent-ink);
    color: var(--accent-ink);
}

.canvas.style-spotlight .cta {
    background: var(--accent-ink);
    color: var(--accent);
}
