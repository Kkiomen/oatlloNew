{{--
    BLUEPRINT — rysunek konstrukcyjny.

    Siatka milimetrowa + ramka ze znacznikami w narożnikach. Klimat techniczny,
    "to jest projekt, nie post" — dlatego trafia na treści o architekturze, bazach
    danych i wzorcach, gdzie chodzi o strukturę, a nie o pojedynczą komendę.

    Siatka jest rysowana gradientami (zero obrazków), więc dokument dalej nie ma
    żadnych podzasobów i renderuje się spod file://.
--}}
.canvas.style-blueprint {
    --rule: rgba(148,163,184,0.20);
    --glow-top: 0.10;
    --glow-bottom: 0.06;
    --watermark-opacity: 0.05;

    background:
        repeating-linear-gradient(0deg, rgba(148,163,184,0.06) 0 1px, transparent 1px 60px),
        repeating-linear-gradient(90deg, rgba(148,163,184,0.06) 0 1px, transparent 1px 60px),
        linear-gradient(150deg, #080d18 0%, #0d1424 100%);
}

/* Ramka rysunku – odsunięta od krawędzi, żeby siatka była widoczna dookoła. */
.canvas.style-blueprint::before {
    content: '';
    position: absolute;
    inset: 44px;
    border: 2px solid rgba(148,163,184,0.18);
    border-radius: 6px;
    z-index: 1;
    pointer-events: none;
}

/* Znacznik pomiarowy w narożniku – w kolorze akcentu, jak ołówek na kalce. */
.canvas.style-blueprint::after {
    content: '';
    position: absolute;
    top: 44px; left: 44px;
    width: 64px; height: 64px;
    border-top: 4px solid var(--accent);
    border-left: 4px solid var(--accent);
    border-radius: 6px 0 0 0;
    z-index: 3;
    pointer-events: none;
}

/* Ramka zjada 44px, więc treść musi się od niej odsunąć. */
.canvas.style-blueprint .stage { padding-left: 108px; padding-right: 108px; }

/* Kanciaste elementy pasują do kreślarskiego charakteru bardziej niż pigułki. */
.canvas.style-blueprint .pill { border-radius: 4px; }
.canvas.style-blueprint .underline { border-radius: 0; }
.canvas.style-blueprint .body pre { border-radius: 4px; }
.canvas.style-blueprint .slide-no { border-radius: 4px; }
