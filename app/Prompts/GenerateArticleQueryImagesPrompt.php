<?php

declare(strict_types=1);

namespace App\Prompts;

use App\Prompts\Abstract\AbstractOpenApiGenerator;

class GenerateArticleQueryImagesPrompt extends AbstractOpenApiGenerator
{

    protected static function preparePrompt(array $data = []): string
    {
        return '
                Stwórz zapytanie (query) na podstawie tytułu artykułu podanego przez użytkownika, które zostanie użyte do pobrania zdjęć z Unsplash jako główne zdjęcie artykułu. Zdjęcie, które zostanie odnalezione na podstawie tego zapytania, ma jak najdokładniej dotyczyć tematu posta.

                # Steps

                1. Przeanalizuj podany tytuł artykułu pod kątem jego kluczowych tematów i słów kluczowych.
                2. Zidentyfikuj najważniejsze słowa i frazy, które najlepiej opisują treść artykułu.
                3. Sformułuj zapytanie (query) w taki sposób, aby najlepiej pasowało do algorytmu wyszukiwania obrazów i tematów dostępnych na Unsplash.
                4. Dokonaj oceny czy wynikające ze sformułowanego zapytania obrazy z dużym prawdopodobieństwem będą adekwatne do tematu artykułu.
                5. Zwróć tylko query aby można go było wkleić w zapytanie api
        ';
    }
}
