{{--
    AURORA — kolorowa mgła (mesh gradient).

    Różnica wobec midnight jest celowa i konkretna: midnight to jeden gradient
    granatu z dwiema łunami akcentu, aurora to kilka rozmytych plam RÓŻNYCH barw.
    Efekt jest cieplejszy i bardziej "produktowy" – przy dużym wolumenie publikacji
    to on rozbija monotonię serii ciemnych kafelków.

    Plamy poza akcentem (indygo, róż) mają niską alfę i siedzą w rogach: przy
    mocniejszych wartościach gryzłyby się z akcentem technologii (emerald PHP na
    różowej mgle wygląda jak błąd), a w tej dawce tylko ocieplają tło.
--}}
.canvas.style-aurora {
    --ink: #f8fafc;
    --muted: #b4b9c9;
    --strong: #ffffff;
    --rule: rgba(226,232,240,0.16);
    --chip: rgba(226,232,240,0.10);
    --chip-ink: #8b91a5;
    --code-bg: rgba(6,9,20,0.78);
    --code-ink: #e2e8f0;
    --watermark-opacity: 0.06;
    /* Tło ma już własne plamy – dokładanie łun akcentu robiłoby z tego papkę. */
    --glow-top: 0;
    --glow-bottom: 0;

    background:
        {{-- Plama akcentu jest nałożona DWA razy: --accent-soft to rgba(akcent, 0.16),
             a pojedyncza warstwa gubiła się w mgle i post tracił kolor technologii.
             Alfa liczy się w PHP (SocialImageService::rgba), więc nie da się jej tu
             podbić inaczej niż warstwą – i to jest tańsze niż color-mix(). --}}
        radial-gradient(60% 50% at 18% 8%, var(--accent-soft) 0%, transparent 60%),
        radial-gradient(45% 38% at 16% 6%, var(--accent-soft) 0%, transparent 55%),
        radial-gradient(50% 40% at 88% 18%, rgba(99,102,241,0.26) 0%, transparent 60%),
        radial-gradient(55% 45% at 78% 88%, rgba(236,72,153,0.20) 0%, transparent 60%),
        radial-gradient(45% 40% at 12% 92%, rgba(56,189,248,0.18) 0%, transparent 60%),
        linear-gradient(165deg, #0a0e1c 0%, #10142a 100%);
}

{{-- Miękki język: wszystko bardziej zaokrąglone niż w bazie. --}}
.canvas.style-aurora .pill {
    background: rgba(255,255,255,0.10);
    border: 1px solid rgba(255,255,255,0.16);
    color: var(--ink);
}
.canvas.style-aurora .pill .dot { background: var(--accent); }

.canvas.style-aurora .underline {
    width: 120px;
    height: 6px;
    background: linear-gradient(90deg, var(--accent), rgba(236,72,153,0.85));
}

.canvas.style-aurora .body pre {
    border-radius: 24px;
    border-color: rgba(255,255,255,0.10);
}

.canvas.style-aurora .slide-no { border-radius: 999px; }

{{-- Pasek akcentu u góry gryzł się z mgłą – zastępujemy go przejściem w róż,
     tym samym, co podkreślenie. --}}
.canvas.style-aurora .bar {
    height: 8px;
    background: linear-gradient(90deg, var(--accent), rgba(236,72,153,0.9), rgba(99,102,241,0.9));
}
