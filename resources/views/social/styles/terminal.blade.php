{{--
    TERMINAL — cała kanwa jako okno powłoki.

    Trafia na posty z blokiem ```bash / ```dockerfile, bo taki post JEST sesją
    terminala – opakowanie go w okno powłoki to nie dekoracja, tylko zgodność formy
    z treścią. Pasek okna dokłada partial `chrome-terminal`.

    Nagłówek jest tu monospace'em, nie Montserratem. To świadome złamanie systemu:
    proporcjonalny nagłówek w oknie terminala wygląda jak wklejka z innego programu.
    Font mono NIE jest wklejony w base64 (repo go nie ma), więc spada na Consolas –
    fallback monospace->monospace jest nieszkodliwy, w przeciwieństwie do braku
    Montserrata, który spadał na font PROPORCJONALNY i psuł łamanie linii.
--}}
.canvas.style-terminal {
    --ink: #e2e8f0;
    --muted: #94a3b8;
    --strong: #f8fafc;
    --rule: rgba(148,163,184,0.14);
    --chip: rgba(148,163,184,0.12);
    --chip-ink: #64748b;
    --code-bg: transparent;
    --code-ink: #e2e8f0;
    --glow-top: 0.10;
    --glow-bottom: 0;
    --watermark-opacity: 0.045;

    background: linear-gradient(180deg, #05080f 0%, #080d16 100%);
}

/* Pasek okna (partial chrome-terminal) zajmia górę – treść musi zejść niżej. */
.canvas.style-terminal .stage { padding-top: 190px; }

/* Cały styl mówi monospace'em. */
.canvas.style-terminal .headline,
.canvas.style-terminal .body,
.canvas.style-terminal .kicker,
.canvas.style-terminal .brand,
.canvas.style-terminal .pill,
.canvas.style-terminal .swipe,
.canvas.style-terminal .slide-no {
    font-family: {!! $mono !!};
}

/* Mono jest szersze niż Montserrat przy tym samym rozmiarze – bez zejścia
   ze skalą nagłówki wyjechałyby poza kanwę. */
.canvas.style-terminal .headline { font-weight: 700; letter-spacing: -0.01em; line-height: 1.15; }
.canvas.style-terminal .h-xxl { font-size: 64px; }
.canvas.style-terminal .h-xl  { font-size: 56px; }
.canvas.style-terminal .h-l   { font-size: 48px; }
.canvas.style-terminal .h-m   { font-size: 40px; }
.canvas.style-terminal .body  { font-size: 30px; line-height: 1.5; }

/* Prompt przed nagłówkiem – to on sprzedaje cały koncept. */
.canvas.style-terminal .headline::before {
    content: '$ ';
    color: var(--accent);
    font-weight: 700;
}

/* Blok kodu jest już wewnątrz terminala – własne okno byłoby oknem w oknie. */
.canvas.style-terminal .body pre {
    background: rgba(148,163,184,0.06);
    border: 0;
    border-left: 4px solid var(--accent);
    border-radius: 0;
    padding: 24px 28px;
}

/* Kanciasto, jak w powłoce. */
.canvas.style-terminal .pill { border-radius: 4px; font-size: 24px; }
.canvas.style-terminal .underline { display: none; }
.canvas.style-terminal .slide-no { border-radius: 4px; }

/* Pasek okna – markup dokłada partial `chrome-terminal`. */
.canvas.style-terminal .term-bar {
    position: absolute;
    top: 10px; left: 0; right: 0;
    height: 96px;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 0 44px;
    background: rgba(148,163,184,0.07);
    border-bottom: 2px solid rgba(148,163,184,0.12);
    z-index: 3;
}
.canvas.style-terminal .term-light { width: 20px; height: 20px; border-radius: 50%; flex: none; }
.canvas.style-terminal .term-light-1 { background: #ff5f57; }
.canvas.style-terminal .term-light-2 { background: #febc2e; }
.canvas.style-terminal .term-light-3 { background: #28c840; }
.canvas.style-terminal .term-title {
    margin-left: 22px;
    font-family: {!! $mono !!};
    font-size: 26px;
    color: #64748b;
}
