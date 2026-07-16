<?php

/*
|--------------------------------------------------------------------------
| Pakiet stylów grafik social media
|--------------------------------------------------------------------------
|
| Styl to SKÓRKA CSS nakładana na wspólny layout (resources/views/social/layout),
| a NIE osobny widok. Inaczej mielibyśmy 4 typy x N stylów widoków do utrzymania.
| Każda skórka to plik w resources/views/social/styles/{nazwa}.blade.php, który
| nadpisuje zmienne CSS (--bg, --ink, --muted, --rule, --code-bg, ...).
|
| Styl jest dobierany AUTOMATYCZNIE przez App\Services\Social\SocialStyleResolver
| na podstawie treści posta (język bloków kodu, temat, typ). Jawny `style:` we
| frontmatterze zawsze wygrywa.
|
| Dobór MUSI być deterministyczny – ten sam post ma wyglądać tak samo przy każdym
| eksporcie. Dlatego fallback losuje przez crc32(slug), a nie przez rand().
|
| Kolor tekstu na stylu 'spotlight' NIE jest tu zapisany: liczy go
| SocialImageService::inkFor() z luminancji akcentu (WCAG), bo akcenty bywają
| jasne (amber) i ciemne (czerwień Laravela) – sztywny kolor byłby nieczytelny
| na połowie z nich.
|
*/

return [

    'default' => 'midnight',

    /*
    | Fallback: gdy żadna afinicja nie zadziała, styl wybierany jest z tej listy
    | deterministycznie po slugu. Dzięki temu feed nie jest monotonny, a jednocześnie
    | ten sam post zawsze renderuje się tak samo.
    |
    | To TU rozstrzyga się, czy seria postów znuży odbiorcę: większość karuzel nie
    | trafia w żadną afinicję i ląduje właśnie w rotacji. Pula miesza ciemne
    | (midnight, neon, aurora), jasne (paper, brutalist) i strukturalnie inne
    | (blueprint, card), żeby dwa kolejne posty nie czytały się jak ten sam kafelek.
    |
    | UWAGA: przydział to crc32(slug) % liczba_pozycji, więc DOPISANIE stylu
    | przetasowuje style WSZYSTKICH postów w rotacji – nie tylko nowych. To nie jest
    | efekt uboczny do przeoczenia: post już zaakceptowany w panelu recenzji zmieni
    | wygląd, a jego .md się nie zmieni, więc panel NIE poprosi o ponowną ocenę.
    | Kto chce przybić wygląd posta na stałe, ustawia `style:` we frontmatterze.
    */
    'rotation' => ['midnight', 'paper', 'blueprint', 'neon', 'aurora', 'card', 'brutalist'],

    /*
    | Pula stylów DLA TYPU. Typ nadal bije temat (patrz SocialStyleResolver), ale
    | nie sprowadza się już do jednego stylu.
    |
    | DLACZEGO: przy 24 postach 12 to story, a afinicja typu dawała każdemu z nich
    | ten sam `spotlight` – czyli dwanaście identycznych kafelków w feedzie. Rotacja
    | tego nie ratowała, bo do niej w ogóle nie dochodziło (typ wygrywa wcześniej).
    | To był największy pojedynczy powód monotonii przy dużym wolumenie publikacji.
    |
    | Pule dobrane pod FORMĘ typu, nie pod widzimisię:
    |  - story ogląda się ułamek sekundy => tylko style, które krzyczą,
    |  - quote to jedna teza => style, które znoszą dużo pustki,
    |  - announce ma logo jako bohatera => style ciemne, które je eksponują
    |    (spotlight barwi kanwę akcentem i logo się w nim gubi).
    |
    | Wybór jest deterministyczny (crc32(slug) % liczba pozycji) – ten sam post
    | zawsze renderuje się tak samo. Pusta pula => stary tryb: afinicja `types`.
    */
    'type_rotation' => [
        // Pięć, nie cztery: przy czterech pozycjach crc32 obecnych slugów wrzucał
        // 5 z 12 story w neon. Hash zawsze się klastruje przy małej próbce, ale
        // szersza pula spłaszcza to bez psucia zasady "story ma krzyczeć"
        // (midnight jest najcichszy w pakiecie i dlatego go tu nie ma).
        'story'    => ['spotlight', 'neon', 'card', 'brutalist', 'aurora'],
        'quote'    => ['editorial', 'brutalist', 'neon'],
        'announce' => ['midnight', 'card', 'aurora'],
    ],

    'styles' => [

        // Bazowy motyw Oatllo – ten sam język wizualny co okładki artykułów i kursów.
        'midnight' => [
            'label'    => 'Midnight',
            'summary'  => 'Ciemny gradient, poświata akcentu, logo jako znak wodny. Motyw bazowy.',
            'chrome'   => null,
            'affinity' => [
                'types' => ['announce'], // zapowiedź ma logo jako bohatera – gradient je eksponuje
            ],
        ],

        // Jasny wariant – w ciemnym feedzie Instagrama wybija się natychmiast.
        'paper' => [
            'label'   => 'Paper',
            'summary' => 'Jasne tło, ciemny tekst, kod nadal w ciemnym oknie. Kontrast dla serii postów.',
            'chrome'  => null,
            'affinity' => [],
        ],

        // Akcent na całej kanwie. Krzykliwe – zatrzymuje scroll w miniaturze.
        'spotlight' => [
            'label'   => 'Spotlight',
            'summary' => 'Kolor technologii wypełnia kanwę. Kolor tekstu liczony z kontrastu.',
            'chrome'  => null,
            'affinity' => [
                'types' => ['story'], // story ogląda się ułamek sekundy – ma krzyczeć
            ],
        ],

        // Okno terminala. Treść czyta się jak sesja shella.
        'terminal' => [
            'label'   => 'Terminal',
            'summary' => 'Cała kanwa jako okno terminala z promptem. Dla treści z komendami.',
            'chrome'  => 'terminal',
            'affinity' => [
                // Post z blokiem ```bash/```dockerfile TO jest sesja terminala.
                'languages' => ['bash', 'sh', 'shell', 'console', 'zsh', 'dockerfile', 'docker'],
                'topics'    => ['docker', 'kubernetes', 'devops', 'git', 'bash', 'linux', 'cli'],
            ],
        ],

        // Techniczna siatka – klimat rysunku konstrukcyjnego.
        'blueprint' => [
            'label'   => 'Blueprint',
            'summary' => 'Ciemne tło z siatką milimetrową i ramką. Dla architektury i baz danych.',
            'chrome'  => null,
            'affinity' => [
                'topics' => ['database', 'sql', 'architecture', 'pattern', 'design', 'scaling', 'queue'],
            ],
        ],

        // Horyzont z siatką i poświatą. Ciemny, ale inaczej niż midnight.
        'neon' => [
            'label'   => 'Neon',
            'summary' => 'Prawie czarne tło, świecąca siatka horyzontu, łuna akcentu. Poświata per technologia.',
            'chrome'  => null,
            'affinity' => [],
        ],

        // Kolorowa mgła – kilka rozmytych plam zamiast jednego gradientu.
        'aurora' => [
            'label'   => 'Aurora',
            'summary' => 'Mesh gradient: akcent + indygo + róż. Cieplejszy, produktowy wariant ciemnego.',
            'chrome'  => null,
            'affinity' => [],
        ],

        // Treść na karcie unoszącej się nad kolorem technologii.
        'card' => [
            'label'   => 'Card',
            'summary' => 'Kanwa w kolorze akcentu, treść na osobnej ciemnej karcie z cieniem.',
            'chrome'  => null,
            'affinity' => [],
        ],

        // Jasny, ale krzyczący geometrią – przeciwieństwo delikatnego paper.
        'brutalist' => [
            'label'   => 'Brutalist',
            'summary' => 'Jasne tło, gruba czarna rama, zero zaokrągleń, pełny cień w akcencie.',
            'chrome'  => null,
            'affinity' => [],
        ],

        // Minimalizm z wielkim numerem slajdu w tle.
        'editorial' => [
            'label'   => 'Editorial',
            'summary' => 'Czerń, wielki numer slajdu jako duch, dużo światła. Minimalizm.',
            'chrome'  => null,
            'affinity' => [
                'types' => ['quote'], // pojedyncza teza znosi (i lubi) dużo pustki
            ],
        ],

        // Dedykowana skórka pod story "nowy artykuł na blogu" (`social:article-stories`).
        // Stały baner "NEW ON THE BLOG" = znak rozpoznawczy serii; akcent i logo
        // zmienne per technologia artykułu. CELOWO BEZ afinicji i BEZ obecności
        // w `rotation`/`type_rotation`: to skórka wyłącznie na jawne `style:`
        // announce-article. Dopisanie jej do rotacji przetasowałoby style wszystkich
        // innych postów (crc32 % liczba pozycji) — a tego ta skórka nie dotyczy.
        'announce-article' => [
            'label'    => 'Announce (article)',
            'summary'  => 'Story "nowy artykuł": stały baner NEW ON THE BLOG, tytuł, logo i akcent per technologia.',
            'chrome'   => null,
            'affinity' => [],
        ],
    ],
];
