<?php

/*
|--------------------------------------------------------------------------
| Moduł social media (Instagram)
|--------------------------------------------------------------------------
|
| Posty social media to commitowane w repo pliki .md – dokładnie tak jak
| artykuły (resources/articles) i kursy (resources/courses). NIE MA BAZY,
| nie ma migracji, nie ma crona. Jedynym writerem jest git.
|
| Pipeline: plik .md -> render HTML (widoki resources/views/social) ->
| rasteryzacja headless -> PNG + caption.txt w folderze eksportu -> ręczny
| upload na Instagram. `publish_at` i `status` to metadane workflow CZŁOWIEKA,
| nic się samo nie publikuje.
|
| UWAGA: to NIE ma nic wspólnego z App\Models\InstagramPost (stara galeria
| kafelków "follow me" trzymana w bazie). Tamto zostaje nietknięte.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Katalog z postami social media (.md)
    |--------------------------------------------------------------------------
    */

    'path' => env('SOCIAL_MD_PATH', resource_path('social')),

    /*
    |--------------------------------------------------------------------------
    | Katalog werdyktów z panelu recenzji (.md)
    |--------------------------------------------------------------------------
    |
    | Panel /social/review zapisuje tu jeden plik na post: zielone światło albo
    | powód poprawki. Te same zasady co posty – pliki .md, commit, zero bazy.
    |
    | Katalog leży WEWNĄTRZ resources/social, ale MarkdownSocialPostRepository
    | czyta swój katalog płasko (File::files, nie allFiles), więc recenzje nigdy
    | nie zostaną wzięte za posty.
    |
    */

    'reviews_path' => env('SOCIAL_REVIEWS_PATH', resource_path('social/reviews')),

    /*
    |--------------------------------------------------------------------------
    | Katalog eksportu (PNG + caption.txt + post.json)
    |--------------------------------------------------------------------------
    |
    | Domyślnie storage/app/social-export – storage/app/* jest gitignorowane,
    | więc wyeksportowane grafiki NIGDY nie trafią do repo.
    |
    */

    'export_path' => env('SOCIAL_EXPORT_PATH', storage_path('app/social-export')),

    /*
    |--------------------------------------------------------------------------
    | Domyślny język treści
    |--------------------------------------------------------------------------
    */

    'default_language' => env('SOCIAL_LANGUAGE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Motyw dla treści BEZ technologii (caching, kariera, code review, regex...)
    |--------------------------------------------------------------------------
    |
    | Gdy TechThemeResolver nic nie dopasuje, NIE bierzemy motywu 'default' z
    | config/course-covers.php: to czapka absolwenta i etykieta "Free course" –
    | sensowne na okładce kursu, ale na poście o cachingu to zwyczajne kłamstwo.
    | Social ma więc własny fallback: bez logo i z etykietą z `topic:`.
    |
    | Akcent jest ROTOWANY po `crc32(slug)`, a nie stały. Powód ten sam co przy
    | `type_rotation` w social-styles: przy dużym wolumenie wszystkie luźne posty
    | miałyby jeden kolor (emerald) i feed znudziłby się kolorem zamiast stylem.
    | Post z technologią i tak dostaje jej barwę – rotacja dotyczy WYŁĄCZNIE treści,
    | które żadnej marki nie mają, więc niczego nie podszywa.
    |
    | UWAGA: te akcenty trafiają też pod styl `spotlight` (tekst na tle akcentu),
    | więc każdy MUSI spełniać kontrast WCAG z atramentem liczonym przez
    | SocialImageService::inkFor(). Pilnuje tego test.
    |
    */

    'fallback_theme' => [
        // Etykieta, gdy post nie ma nawet `topic:`. Marka, nie zmyślona technologia.
        'label' => env('SOCIAL_FALLBACK_LABEL', 'Oatllo'),

        'accents' => [
            ['accent' => '#fb7185', 'accent_color' => 'rose'],    // akcent bloga
            ['accent' => '#38bdf8', 'accent_color' => 'sky'],
            ['accent' => '#fbbf24', 'accent_color' => 'amber'],
            ['accent' => '#a78bfa', 'accent_color' => 'violet'],
            ['accent' => '#34d399', 'accent_color' => 'emerald'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Formaty publikacji (frontmatter `formats:`) – co z posta wychodzi na dany dzień
    |--------------------------------------------------------------------------
    |
    | Jeden plik .md może iść w świat na kilka sposobów: karuzela w feedzie ORAZ
    | reel z tych samych slajdów (`social:video`). `type` mówi o KSZTAŁCIE slajdów
    | (kanwa, liczba, widok), a `formats` o TYM, CO PUBLIKUJESZ. To dwie różne rzeczy
    | i dlatego to osobne pole – inaczej „reel z karuzeli” nie dałby się zapisać.
    |
    | To pole PLANU dla człowieka, jak `publish_at` i `status`: niczego nie renderuje
    | ani nie publikuje. Karmi kalendarz w panelu recenzji.
    |
    | `reel` produkuje ten moduł (`social:video`). `video` NIE – to etykieta na
    | materiał nagrywany poza modułem, żeby dzień w kalendarzu był kompletny.
    |
    | Dopisanie formatu = jedna linijka tutaj; lint sam zacznie go przyjmować,
    | a kalendarz rysować. Nieznana nazwa w pliku to ERROR (cicho ignorowany klucz
    | to najgorszy tryb awarii).
    |
    */

    'formats' => [
        'post'  => ['label' => 'Post',  'color' => '#38bdf8'],
        'story' => ['label' => 'Story', 'color' => '#a78bfa'],
        'reel'  => ['label' => 'Reel',  'color' => '#f472b6'],
        'video' => ['label' => 'Wideo', 'color' => '#fbbf24'],
    ],

    /*
    | Domyślny format, gdy `formats:` nie ma w pliku – wyliczany z typu, żeby
    | istniejące posty nie wymagały edycji ani migracji.
    */

    'default_formats' => [
        'story'   => ['story'],
        'default' => ['post'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Marka / stopka na grafikach
    |--------------------------------------------------------------------------
    */

    'brand' => [
        'domain' => env('SOCIAL_BRAND_DOMAIN', 'oatllo.com'),
        'handle' => env('SOCIAL_BRAND_HANDLE', '@oatllo'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Limity walidacji (social:lint)
    |--------------------------------------------------------------------------
    |
    | caption_max / hashtags_max to twarde limity Instagrama. Budżety znaków na
    | slajdach to nasze limity czytelności – ich przekroczenie to WARNING, bo
    | tekst realnie rozpycha layout (CSS zawija naprawdę, nic nie rzuci wyjątku).
    |
    */

    'limits' => [
        'caption_max'       => 2200,  // twardy limit Instagrama
        'caption_hook_max'  => 125,   // ile widać przed "... more"

        // 5, nie 30. Instagram ściął limit 18.12.2025 (@creators na Threads:
        // "Starting today, Instagram will allow up to 5 hashtags in a reel or
        // post"). Wcześniej stało tu 30 z komentarzem "twardy limit Instagrama"
        // – i to była prawda do tamtej daty. Hashtagi i tak NIGDY nie dawały
        // zasięgu (Socialinsider, 75 mln postów: "the number of hashtags does
        // not influence post distribution"), więc to nie jest strata. Dowody:
        // .claude/skills/social-growth/references/research.md §3.
        'hashtags_max'      => 5,     // twardy limit Instagrama od 2025-12-18
        'hook_headline_max' => 70,
        'body_headline_max' => 55,
        'body_text_max'     => 180,
        'code_lines_max'    => 8,
        // 46, nie "na oko": przy foncie kodu 30px w kanwę 1080 wchodzi ~50 kolumn
        // (900px minus padding okna, advance monospace ~0.55em). 46 zostawia zapas
        // na inny font zastępczy. Dłuższa linia NIE rzuci błędu – po prostu
        // wyjedzie poza krawędź, bo pre ma overflow:hidden. Dlatego to lint.
        'code_cols_max'     => 46,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rasteryzacja (headless Edge / Chrome)
    |--------------------------------------------------------------------------
    |
    | Pusty `binary` => autodetekcja z listy `candidates`. Ustaw SOCIAL_BROWSER_BINARY
    | jeśli masz przeglądarkę w nietypowym miejscu.
    |
    */

    'browser' => [
        'binary' => env('SOCIAL_BROWSER_BINARY'),

        'candidates' => [
            'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
            'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
            'C:\Program Files\Google\Chrome\Application\chrome.exe',
            'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
            '/usr/bin/microsoft-edge',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        ],

        'timeout' => (int) env('SOCIAL_BROWSER_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Font wklejany w grafiki (base64 @font-face)
    |--------------------------------------------------------------------------
    |
    | Headless renderujący dokument NIE zna Montserrata (nie jest fontem
    | systemowym) – bez wklejenia woff2 w base64 dostalibyśmy podmianę na font
    | systemowy o innych metrykach. Subset "latin" wystarcza (treści są po
    | angielsku) i waży ~38 KB na wagę.
    |
    */

    'fonts' => [
        'dir'     => public_path('assets/fonts/montserrat'),
        'pattern' => 'montserrat-{weight}-latin.woff2',
        'weights' => [400, 600, 800],
    ],

    /*
    |--------------------------------------------------------------------------
    | Podgląd w przeglądarce (tylko DEV)
    |--------------------------------------------------------------------------
    |
    | Eksport NIE potrzebuje HTTP (rasteryzator zrzuca lokalny plik file://).
    | Trasy /social/* to wyłącznie DX. Rejestrowane WARUNKOWO – przy false na
    | produkcji tras fizycznie nie ma w tablicy routingu.
    |
    */

    'preview_enabled' => (bool) env('SOCIAL_PREVIEW', false),

    /*
    |--------------------------------------------------------------------------
    | Publisher
    |--------------------------------------------------------------------------
    |
    | Szew pod przyszłe Instagram Graph API. v1 = FolderPublisher (eksport +
    | checklista ręcznego uploadu).
    |
    | UWAGA: Graph API NIE przyjmuje plików lokalnych – wymaga publicznych URL-i
    | HTTPS do obrazków, konta Business, powiązanej strony FB i długożyciowego
    | tokenu. Podmiana tej klasy to najmniejsza część tamtej roboty.
    |
    */

    'publisher' => env('SOCIAL_PUBLISHER', \App\Services\Social\Publisher\FolderPublisher::class),

    /*
    |--------------------------------------------------------------------------
    | Wideo / Reels (Remotion)
    |--------------------------------------------------------------------------
    |
    | Reel to TEN SAM post .md, tylko w ruchu: Remotion dostaje dokładnie te
    | dokumenty HTML, które idą na PNG, i dokłada wyłącznie animację. Wygląd ma
    | jedno źródło (Blade) – wideo nie może rozjechać się z kafelkiem.
    |
    | Remotion to osobny projekt Node w `social-video/`, NIE zależność Laravela.
    | Render jest wyłącznie lokalny, jak PNG-i: produkcja nie ma node_modules
    | i mieć nie będzie (deploy = git pull).
    |
    | Licencja Remotiona: darmowa dla osób prywatnych i firm do 3 osób.
    | Powyżej – płatna. https://www.remotion.pro/license
    |
    */

    'video' => [

        'project_path' => env('SOCIAL_VIDEO_PROJECT', base_path('social-video')),

        'fps' => (int) env('SOCIAL_VIDEO_FPS', 30),

        // Render kanwy 1080x1920 klatka po klatce jest wolny – to nie jest
        // zrzut ekranu jak przy PNG. Kilka minut na Reela to norma.
        'timeout' => (int) env('SOCIAL_VIDEO_TIMEOUT', 900),

        /*
        | Ile klatek trzyma się slajd. Liczone z OBJĘTOŚCI TREŚCI, nie na sztywno:
        | slajd z blokiem kodu czyta się dłużej niż sam hook, więc stała długość
        | albo urywałaby kod, albo trzymała pusty hook w nieskończoność.
        |
        | `per_code_line` jest wyższe od `per_word`, bo kod się skanuje, a nie
        | czyta. `min`/`max` to bezpieczniki: 75 klatek = 2.5 s (poniżej nikt nie
        | zdąży przeczytać), 210 = 7 s (powyżej widz ucieka).
        */
        'timing' => [
            'base'          => 45,
            'per_word'      => 6,
            'per_code_line' => 12,
            'min'           => 75,
            'max'           => 210,
        ],

    ],

];
