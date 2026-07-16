<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Katalog z artykułami w formacie Markdown
    |--------------------------------------------------------------------------
    |
    | Artykuły to commitowane w repo pliki .md (jak kursy). Tworzone lokalnie,
    | wysyłane na produkcję przez `git pull` i renderowane dynamicznie (bez
    | zapisu do bazy danych). To jest drugie źródło artykułów obok bazy danych.
    |
    */

    'path' => env('ARTICLES_MD_PATH', resource_path('articles')),

    /*
    |--------------------------------------------------------------------------
    | Katalog z kursami w formacie Markdown (commitowane w repo)
    |--------------------------------------------------------------------------
    |
    | Kursy można deklarować jako pliki .md (folder na kurs, podfoldery na
    | rozdziały, pliki .md na lekcje) i renderować dynamicznie – bez zapisu do
    | bazy. Drugie źródło kursów obok bazy (plik ma pierwszeństwo przy tym slug).
    |
    */

    'courses_path' => env('COURSES_MD_PATH', resource_path('courses')),

    /*
    |--------------------------------------------------------------------------
    | Domyślny język
    |--------------------------------------------------------------------------
    */

    'default_language' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Katalog docelowy sitemap
    |--------------------------------------------------------------------------
    |
    | Gdzie zapisywane są pliki sitemap.xml / sitemap-index.xml. Domyślnie
    | katalog public/. Nadpisywane w testach, aby nie modyfikować wersjonowanego
    | pliku sitemap.
    |
    */

    'sitemap_path' => env('SITEMAP_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Linkowanie wewnętrzne (render-time)
    |--------------------------------------------------------------------------
    |
    | Algorytm, który przy wyświetlaniu wstawia w treść artykułu linki do innych
    | istniejących artykułów (baza + pliki .md). Frazy-kotwice pochodzą z keys_link,
    | tytułów i tagów artykułów-celów. Nic nie jest utrwalane – linki dodawane są
    | dynamicznie przez App\Services\Article\InternalLinker.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Stare artykuły z bazy wycofane z serwisu (SEO-first, sprzed plików .md)
    |--------------------------------------------------------------------------
    |
    | 44 artykuły, które stały na produkcji, zanim ruszył harmonogram plików .md.
    | Wszystkie pisane pod algorytm: marka wciśnięta w treść ("Oatllo, a name
    | synonymous with digital business continuity" – w tekście o backupach), fraza
    | z tytułu wepchnięta w zdanie, CTA w każdym meta description. Wg GSC
    | (2026-07-14) dają RAZEM 9 kliknięć z 94 na całej domenie, a 88% ruchu robi
    | kurs PHP – pisany bez żadnego SEO. Przy 48 z 257 stron w indeksie to one są
    | najlepszym kandydatem na przyczynę. Kasują też własnych następców:
    | `master-php-enums-use-cases-tips` bije się o frazy z `php-enums-complete-guide.md`.
    |
    | TO NIE JEST ZWYKŁA LISTA – MA DWÓCH KONSUMENTÓW I OBAJ SĄ KONIECZNI:
    |
    |  1. `php artisan articles:retire-legacy` ustawia im is_published = false,
    |  2. CronController::publishDueArticles() POMIJA te slugi.
    |
    | Bez punktu 2. punkt 1. jest bezużyteczny: tick publikuje wszystko, co ma
    | is_published = false i published_at w przeszłości, a te artykuły mają daty
    | sprzed miesięcy. Samo wygaszenie zostałoby CICHO cofnięte w ciągu godziny.
    | Na tej stronie is_published = false nie znaczy "ukryty", tylko "opublikuj
    | mnie jak najszybciej" – dopiero ta lista robi z tego stan trwały.
    |
    | Wygaszamy, nie kasujemy: te artykuły NIE mają plików .md, więc nie da się
    | ich odtworzyć z gita. Przywrócenie = usuń slug stąd, potem --restore.
    |
    | NIE MA TU `site-map` – wygląda jak slug artykułu i jest w sitemapie, ale to
    | prawdziwa mapa strony (trasa `site.map`, `/mapa` na nią przekierowuje).
    | Pilnuje tego test.
    |
    */

    'retired_slugs' => [
        'abstraction-programming-php',
        'advanced-php-dependency-injection-techniques',
        'agile-methodology-guide-software-teams',
        'agile-vs-scrum-key-differences',
        'avoiding-pitfalls-php-speeding-up-code',
        'career-growth-programming-success',
        'continuous-learning-programmers-tech-evolution',
        'developing-programming-mindset',
        'disaster-recovery-database-systems',
        'dot-dependency-inversion-principle-solid',
        'effective-coding-strategies',
        'enums-php-guide',
        'freelance-developer-tips-beginners',
        'freelance-programming-rates',
        'freelancing-it-career-growth',
        'how-to-write-unit-tests-php',
        'importance-database-backup-business-security',
        'it-freelancing-pros-cons',
        'learn-coding-beginners',
        'letter-i-in-solid-explanation-examples',
        'letter-o-in-solid',
        'letter-s-in-solid-examples',
        'liskov-substitution-principle-solid',
        'master-php-enums-use-cases-tips',
        'nowy-artykul',
        'oop-practices-php',
        'php-api-development-best-practices',
        'php-code-organization-best-practices',
        'php-code-organization-clean-architecture',
        'php-code-refactoring',
        'php-code-refactoring-tools-patterns-best-practices',
        'php-performance-optimization-strategies',
        'php-performance-optimization-techniques',
        'php-security-measures',
        'php-solid-principles-examples',
        'php-unit-testing-guide-best-practices',
        'programmer-burnout-recovery-tips-techniques',
        'programming-beginners-first-step',
        'programming-beginners-first-step-coding',
        'programming-career-development-expert-advice',
        'programming-mindset-think-like-developer',
        'programming-motivation-tips',
        'scrum-framework-boost-team-productivity',
        'work-life-balance-it-tech-professionals',
    ],

    'internal_linking' => [
        // Globalny włącznik.
        'enabled' => (bool) env('INTERNAL_LINKING', true),
        // Maksymalna liczba linków wewnętrznych wstawianych w jeden artykuł.
        'max_links_per_article' => (int) env('INTERNAL_LINKING_MAX', 3),
        // Ile razy można podlinkować ten sam artykuł-cel w obrębie jednego artykułu.
        'max_links_per_target' => 1,
        // Minimalna długość frazy-kotwicy (znaki), by uniknąć zbyt ogólnych dopasowań.
        'min_phrase_length' => 4,
        // Frazy nigdy nielinkowane (zbyt ogólne / szumowe).
        'stopwords' => ['php', 'laravel', 'kod', 'code'],
        // Twardy limit liczby fraz w indeksie (ochrona wydajności).
        'max_index_phrases' => (int) env('INTERNAL_LINKING_MAX_PHRASES', 2000),
        // Czas życia cache indeksu fraz→URL (sekundy).
        'cache_ttl' => (int) env('INTERNAL_LINKING_TTL', 600),
    ],

];
