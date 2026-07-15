{{--
    BRUTALIST — twarde krawędzie, gruba czarna rama, przesunięty cień.

    Drugi (po paper) styl jasny, ale zupełnie inny w charakterze: paper jest
    delikatny i redakcyjny, brutalist krzyczy geometrią. Zero zaokrągleń, zero
    poświat, wszystko na styk. W ciemnym feedzie dewelopera to najmocniejszy
    kontrast, jaki mamy poza spotlightem – i w przeciwieństwie do niego nie zjada
    całej kanwy kolorem, więc nadaje się pod dłuższy tekst.

    Cień jest PEŁNY (bez rozmycia) i w kolorze akcentu – to znak rozpoznawczy
    tego języka wizualnego: przesunięta kopia kształtu, a nie miękka poświata.
--}}
.canvas.style-brutalist {
    --ink: #0a0a0a;
    --muted: #27272a;
    --strong: #0a0a0a;
    --rule: #0a0a0a;
    --chip: rgba(10,10,10,0.08);
    --chip-ink: #52525b;
    /* Kod zostaje ciemny – tak samo jak w paper. Ciemny blok na jasnej kanwie
       jest rozpoznawalny sam w sobie i daje mocny punkt ciężkości. */
    --code-bg: #0a0a0a;
    --code-ink: #fafafa;
    --watermark-ink: #0a0a0a;
    --watermark-opacity: 0.06;
    --glow-top: 0;
    --glow-bottom: 0;

    background: #f4f4ef;
}

{{-- Gruba rama. Pasek akcentu (.bar) ma z-index 4, więc zostaje nad nią. --}}
.canvas.style-brutalist::before {
    content: '';
    position: absolute;
    inset: 0;
    border: 14px solid #0a0a0a;
    z-index: 1;
    pointer-events: none;
}

{{-- Rama zjada 14px – treść musi się od niej odsunąć, inaczej dotyka czerni. --}}
.canvas.style-brutalist .stage { padding-left: 76px; padding-right: 76px; }

.canvas.style-brutalist .bar { height: 18px; }

{{-- Nic się tu nie zaokrągla. --}}
.canvas.style-brutalist .pill {
    background: var(--accent);
    color: var(--accent-ink);
    border: 4px solid #0a0a0a;
    border-radius: 0;
    padding: 12px 24px;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: 0.10em;
    text-transform: uppercase;
    box-shadow: 8px 8px 0 #0a0a0a;
}
.canvas.style-brutalist .pill .dot { background: var(--accent-ink); border-radius: 0; }

.canvas.style-brutalist .headline { letter-spacing: -0.04em; }

.canvas.style-brutalist .underline {
    width: 160px;
    height: 14px;
    border-radius: 0;
    box-shadow: 8px 8px 0 var(--accent);
    background: #0a0a0a;
}

{{-- Okno kodu: czarny blok z pełnym cieniem w akcencie. --}}
.canvas.style-brutalist .body pre {
    border: 4px solid #0a0a0a;
    border-radius: 0;
    box-shadow: 12px 12px 0 var(--accent);
}

{{-- `:not(pre) > code` jest KONIECZNE: samo `.body code` ma wyższą specyficzność
     niż bazowe `.body pre code`, więc przebarwiłoby też kod WEWNĄTRZ bloku, który
     siedzi na czarnym tle – blok wyszedłby jako pusty prostokąt. Patrz paper. --}}
.canvas.style-brutalist .body :not(pre) > code {
    background: var(--accent);
    color: var(--accent-ink);
    border-radius: 0;
    font-weight: 600;
}

.canvas.style-brutalist .body hr { height: 4px; background: #0a0a0a; }
.canvas.style-brutalist .slide-no { border-radius: 0; border: 3px solid #0a0a0a; }
.canvas.style-brutalist .link-pill { border-radius: 0; border-width: 4px; }
.canvas.style-brutalist .cta { border-radius: 0; box-shadow: 8px 8px 0 #0a0a0a; }
