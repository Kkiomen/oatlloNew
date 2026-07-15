{{--
    NEON — horyzont z siatką i poświata.

    Najbardziej "wieczorny" styl w pakiecie: prawie czarne tło, świecąca siatka
    uciekająca do horyzontu i łuna pod nim. W feedzie czyta się zupełnie inaczej
    niż midnight (który jest po prostu ciemnym gradientem), więc dwa ciemne posty
    z rzędu przestają wyglądać jak ten sam post.

    Siatka i łuna są budowane z --accent-soft / --accent-faint, a NIE z wpisanego
    na sztywno fioletu: dzięki temu neon zostaje per-technologia (post o Dockerze
    świeci na niebiesko, o Laravelu na czerwono) i nie kłóci się z własnym logo.

    Wszystko rysują gradienty – zero obrazków, więc dokument dalej nie ma żadnych
    podzasobów i renderuje się spod file://.
--}}
.canvas.style-neon {
    --ink: #f8fafc;
    --muted: #a1a1aa;
    --strong: #ffffff;
    --rule: rgba(148,163,184,0.18);
    --chip: rgba(148,163,184,0.10);
    --chip-ink: #71717a;
    --code-bg: rgba(2,2,8,0.72);
    --code-ink: #e4e4e7;
    --watermark-opacity: 0.05;
    /* Łunę robi tu ::before + przesunięty glow-bottom, więc górna jest zbędna. */
    --glow-top: 0;
    --glow-bottom: 0.42;

    background: linear-gradient(180deg, #05040c 0%, #090717 55%, #0c0820 100%);
}

{{-- Siatka horyzontu. Maska wygasza ją ku górze, więc linie "uciekają" w dal
     zamiast kończyć się twardą krawędzią. --}}
.canvas.style-neon::before {
    content: '';
    position: absolute;
    left: 0; right: 0; bottom: 0;
    height: 30%;
    background:
        repeating-linear-gradient(90deg, var(--accent-soft) 0 2px, transparent 2px 96px),
        repeating-linear-gradient(0deg, var(--accent-faint) 0 2px, transparent 2px 64px);
    -webkit-mask-image: linear-gradient(to top, #000 0%, transparent 100%);
    mask-image: linear-gradient(to top, #000 0%, transparent 100%);
    z-index: 0;
    pointer-events: none;
}

{{-- Łuna jako "słońce" na horyzoncie: przenosimy istniejący glow na środek dołu. --}}
.canvas.style-neon .glow-bottom {
    width: 940px; height: 940px;
    left: 50%; bottom: -520px;
    transform: translateX(-50%);
}

{{-- ŻADNEJ twardej linii horyzontu na stałej wysokości.

     Była tu i wyglądała dobrze na jednym slajdzie, ale treść siedzi na różnych
     wysokościach zależnie od typu i kanwy: na story linia szła PRZEZ przycisk
     "Link in bio", na quote muskała ostatnią linijkę akapitu i czytała się jak
     przekreślenie. Każdy stały procent w coś w końcu trafi. Horyzont wystarczająco
     niesie sama siatka wygaszona maską – miękki gradient nie udaje przekreślenia. --}}

{{-- Nagłówek świeci. Delikatnie: mocniejsza poświata rozmywa krawędzie liter
     i przy 92px robi z nagłówka plamę. --}}
.canvas.style-neon .headline {
    text-shadow: 0 0 48px var(--accent-soft), 0 0 96px var(--accent-faint);
}

.canvas.style-neon .pill {
    border: 2px solid var(--accent);
    box-shadow: 0 0 28px var(--accent-soft);
}

.canvas.style-neon .underline {
    box-shadow: 0 0 24px var(--accent);
}

.canvas.style-neon .body pre {
    border-color: var(--accent-soft);
    box-shadow: 0 0 40px rgba(0,0,0,0.45);
}
