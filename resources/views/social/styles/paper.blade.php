{{--
    PAPER — wariant jasny.

    Feed Instagrama dla devów jest w 90% ciemny, więc jasny kafelek wybija się
    natychmiast. Kod ZOSTAJE w ciemnym oknie: to jedyny element, którego nie
    odwracamy, bo ciemny blok kodu jest rozpoznawalny sam w sobie i daje mocny
    punkt ciężkości na jasnej kanwie.
--}}
.canvas.style-paper {
    --ink: #0f172a;
    --muted: #475569;
    --strong: #0f172a;
    --rule: rgba(15,23,42,0.12);
    --chip: rgba(15,23,42,0.07);
    --chip-ink: #64748b;
    /* Kod celowo pozostaje ciemny – patrz komentarz wyżej. */
    --code-bg: #0f172a;
    --code-ink: #e2e8f0;
    --watermark-ink: #0f172a;
    --watermark-opacity: 0.05;
    --glow-top: 0.14;
    --glow-bottom: 0.08;

    background: linear-gradient(160deg, #f8fafc 0%, #e9eef5 100%);
}

/* Inline `code` w jasnej treści potrzebuje ciemnego tekstu, żeby nie zlewał się z prozą.

   `:not(pre) > code` jest KONIECZNE. Samo `.body code` ma wyższą specyficzność niż
   bazowa reguła `.body pre code`, więc trafiałoby też w kod WEWNĄTRZ bloku – a ten
   siedzi na ciemnym tle (--code-bg) i robił się niewidoczny. Blok kodu wychodził
   jako pusty czarny prostokąt. */
.canvas.style-paper .body :not(pre) > code {
    background: rgba(15,23,42,0.08);
    color: #0f172a;
}

/* Na jasnym tle 2px obwódka okna kodu jest zbędna – blok i tak odcina się sam. */
.canvas.style-paper .body pre {
    border-color: rgba(15,23,42,0.10);
}
