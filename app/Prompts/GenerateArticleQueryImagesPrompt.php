<?php

declare(strict_types=1);

namespace App\Prompts;

use App\Prompts\Abstract\AbstractOpenApiGenerator;

class GenerateArticleQueryImagesPrompt extends AbstractOpenApiGenerator
{

    protected static function preparePrompt(array $data = []): string
    {
        return '
 Na podstawie tytuły jaki poda użytkownik wygeneruj query jakie zostanie użyte w api pixabay/unsplash do pobrania zdjęcia,
 które zostanie użyte jako główne zdjęcie artykułu. Podawaj w języku angielskim max 100 znaków
        ';
    }
}
