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
