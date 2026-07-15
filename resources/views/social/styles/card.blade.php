{{--
    CARD — treść jako karta unosząca się nad kolorem akcentu.

    Jedyny styl w pakiecie, w którym treść NIE dotyka krawędzi kanwy. Kanwa jest
    w kolorze technologii, a tekst siedzi na osobnej, ciemnej karcie z cieniem –
    w miniaturze feedu czyta się jak wycinek interfejsu, a nie jak plakat. To
    najmocniejsza różnica strukturalna, jaką pakiet ma do zaoferowania, więc przy
    serii postów rozbija rytm skuteczniej niż kolejny wariant kolorystyczny.

    Kartą jest .stage – nie potrzeba własnego markupu. Wysokość liczymy calc(),
    bo .stage ma height:100%, a sam margines wypchnąłby ją poza kanwę.
--}}
.canvas.style-card {
    --ink: #f8fafc;
    --muted: #a3adc2;
    --strong: #ffffff;
    --rule: rgba(148,163,184,0.16);
    --chip: rgba(148,163,184,0.12);
    --chip-ink: #7c8598;
    --code-bg: #05070f;
    --code-ink: #e2e8f0;
    --glow-top: 0;
    --glow-bottom: 0;
    /* Karta jest nieprzezroczysta i ma z-index 2, więc znak wodny (z-index 0)
       i tak by za nią zniknął. Nie udajemy, że jest – wyłączamy go, jak editorial. */
    --watermark-opacity: 0;

    background: linear-gradient(150deg, var(--accent) 0%, rgba(2,6,23,0.92) 78%);
}

.canvas.style-card .watermark { display: none; }

{{-- Pasek akcentu na tle akcentu byłby niewidoczny – krawędź niesie karta. --}}
.canvas.style-card .bar { display: none; }

{{-- Wcięcie karty MUSI wynikać z bezpiecznych marginesów kanwy, a nie być stałą.
     Na story .stage dostaje 250px/320px paddingu, bo interfejs Instagrama (avatar
     i pasek postępu u góry, pole odpowiedzi u dołu) zasłania te pasy. Karta
     zastępuje padding własnym marginesem, więc gdyby był stały (56px), wjechałaby
     prosto pod ten interfejs. Zostawiamy zapas: sama karta dokłada jeszcze padding. --}}
@php($cardTop = $padTop >= 200 ? $padTop - 60 : 56)
@php($cardBottom = $padBottom >= 200 ? $padBottom - 60 : 56)

.canvas.style-card .stage {
    height: calc(100% - {{ $cardTop + $cardBottom }}px);
    margin: {{ $cardTop }}px 56px {{ $cardBottom }}px;
    padding: 72px 64px;
    background: #0a0f1e;
    border-top: 10px solid var(--accent);
    border-radius: 34px;
    box-shadow: 0 40px 90px rgba(2,6,23,0.55);
}

{{-- Stopka story jest `position: absolute` z `bottom: $padBottom - 60`, a kotwiczy
     się do .stage – czyli TU DO KARTY, nie do kanwy. Bez tej poprawki bezpieczny
     margines story liczył się dwa razy: stopka wjeżdżała 260px nad dolną krawędź
     KARTY, prosto pod przycisk "Link in bio". Karta sama odsuwa treść od krawędzi,
     więc stopce wystarczy padding karty. --}}
.canvas.style-card .story-footer { left: 64px; bottom: 72px; }

.canvas.style-card .pill { border-radius: 10px; }
.canvas.style-card .underline { border-radius: 2px; }
.canvas.style-card .body pre { border-radius: 16px; }
.canvas.style-card .slide-no { border-radius: 10px; }
