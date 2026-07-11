<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Katalog z artykułami w formacie Markdown
    |--------------------------------------------------------------------------
    |
    | Artykuły wgrywane przez API są zapisywane jako pliki .md w tym katalogu
    | i renderowane na stronie dynamicznie (bez zapisu do bazy danych).
    | To jest drugie źródło artykułów obok bazy danych.
    |
    */

    'path' => env('ARTICLES_MD_PATH', storage_path('app/articles')),

    /*
    |--------------------------------------------------------------------------
    | Token API do wgrywania artykułów
    |--------------------------------------------------------------------------
    |
    | Sekret używany do autoryzacji endpointu importu artykułów. Lokalny Claude
    | wysyła go w nagłówku "Authorization: Bearer <token>".
    |
    */

    'api_token' => env('ARTICLE_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Domyślny język
    |--------------------------------------------------------------------------
    */

    'default_language' => env('APP_LOCALE', 'en'),

];
