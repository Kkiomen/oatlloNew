<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Motywy okładek artykułów (design "okno kodu")
    |--------------------------------------------------------------------------
    |
    | Okładka jest dobierana do tematu artykułu: na podstawie kategorii, tagów
    | i tytułu wyszukujemy pierwszy motyw, którego słowo kluczowe pasuje.
    | Jeśli nic nie pasuje, używany jest motyw 'default'.
    |
    | Każdy motyw definiuje:
    |  - accent   : kolor akcentu (ramka okna, podkreślenie, marka),
    |  - filename : nazwa "pliku" na pasku tytułu okna (np. article.php),
    |  - header   : pierwsza linia "kodu" (np. <?php),
    |  - comment  : token komentarza poprzedzający tytuł (// , # , -- ),
    |  - footer   : linia "kodu" pod tytułem (podpis wizualny),
    |  - label    : etykieta w pigułce (kategoria/technologia).
    |
    | Kolejność ma znaczenie – bardziej szczegółowe motywy (laravel) muszą być
    | przed ogólnymi (php).
    |
    */

    'default' => [
        'accent' => '#fb7185', // rose-400 – spójne z motywem strony
        'filename' => 'article.php',
        'header' => '<?php',
        'comment' => '//',
        'footer' => '$oatllo->publish();',
        'label' => 'oatllo',
    ],

    'themes' => [

        'laravel' => [
            'keywords' => ['laravel', 'eloquent', 'blade', 'artisan', 'livewire'],
            'accent' => '#ff2d20',
            'filename' => 'Article.php',
            'header' => '<?php',
            'comment' => '//',
            'footer' => 'Oatllo::publish();',
            'label' => 'Laravel',
        ],

        'php' => [
            'keywords' => ['php', 'composer', 'symfony', 'php 8', 'oop'],
            'accent' => '#8892bf',
            'filename' => 'article.php',
            'header' => '<?php',
            'comment' => '//',
            'footer' => '$oatllo->publish();',
            'label' => 'PHP',
        ],

        'javascript' => [
            'keywords' => ['javascript', 'typescript', 'node', 'react', 'vue', 'js', 'ts'],
            'accent' => '#f7df1e',
            'filename' => 'app.js',
            'header' => "'use strict';",
            'comment' => '//',
            'footer' => 'oatllo.publish();',
            'label' => 'JavaScript',
        ],

        'devops' => [
            'keywords' => ['devops', 'docker', 'kubernetes', 'k8s', 'ci/cd', 'deploy', 'linux', 'bash', 'nginx'],
            'accent' => '#2496ed',
            'filename' => 'deploy.sh',
            'header' => '#!/usr/bin/env bash',
            'comment' => '#',
            'footer' => './oatllo deploy',
            'label' => 'DevOps',
        ],

        'database' => [
            'keywords' => ['sql', 'mysql', 'postgres', 'postgresql', 'database', 'baza danych', 'query', 'index'],
            'accent' => '#00b4d8',
            'filename' => 'query.sql',
            'header' => '-- SQL',
            'comment' => '--',
            'footer' => 'SELECT * FROM oatllo;',
            'label' => 'Database',
        ],

        'ai' => [
            'keywords' => ['ai', 'llm', 'gpt', 'openai', 'claude', 'machine learning', 'sztuczna inteligencja', 'agent'],
            'accent' => '#10b981',
            'filename' => 'agent.php',
            'header' => '<?php',
            'comment' => '//',
            'footer' => '$oatllo->think();',
            'label' => 'AI',
        ],

    ],

];
