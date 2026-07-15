{{--
    EDITORIAL — minimalizm z wielkim numerem w tle.

    Czerń zamiast gradientu, cienka kreska zamiast pigułek, dużo światła i jeden
    ogromny numer slajdu jako duch. Trafia na `quote`, bo pojedyncza teza znosi
    (i lubi) pustkę — a numer wypełnia ją, nie zabierając uwagi treści.

    Numer bierze się z atrybutu data-slide-no na .canvas, więc styl nie potrzebuje
    własnego markupu.
--}}
.canvas.style-editorial {
    --ink: #fafafa;
    --muted: #8a8f98;
    --strong: #fafafa;
    --rule: rgba(250,250,250,0.10);
    --chip: rgba(250,250,250,0.07);
    --chip-ink: #6b7280;
    --code-bg: #0e0f13;
    --code-ink: #e5e7eb;
    --glow-top: 0;
    --glow-bottom: 0;
    /* Logo zabrałoby uwagę numerowi – w tym stylu znaku wodnego nie ma. */
    --watermark-opacity: 0;

    background: #08090c;
}

.canvas.style-editorial .watermark { display: none; }

/* Cienka kreska u góry zamiast grubego paska – ten styl mówi szeptem. */
.canvas.style-editorial .bar { height: 3px; }

/* Ogromny numer slajdu jako duch. Treść ma z-index 2, więc numer zostaje pod nią. */
.canvas.style-editorial::after {
    content: attr(data-slide-no);
    position: absolute;
    right: 60px;
    bottom: -60px;
    font-size: 380px;
    font-weight: 800;
    letter-spacing: -0.06em;
    line-height: 1;
    color: transparent;
    -webkit-text-stroke: 3px rgba(250,250,250,0.055);
    z-index: 0;
    pointer-events: none;
}

/* Pigułka schodzi do etykiety z obwódką. */
.canvas.style-editorial .pill {
    background: transparent;
    border: 2px solid var(--accent);
    color: var(--accent);
    border-radius: 4px;
    padding: 10px 22px;
    font-size: 22px;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
.canvas.style-editorial .pill .dot { display: none; }

/* Podkreślenie jako cienka, długa linia – bardziej kreska redakcyjna niż akcent. */
.canvas.style-editorial .underline {
    width: 180px;
    height: 3px;
    border-radius: 0;
}

.canvas.style-editorial .headline { letter-spacing: -0.035em; }
.canvas.style-editorial .body pre { border-radius: 6px; }
.canvas.style-editorial .slide-no { display: none; }
