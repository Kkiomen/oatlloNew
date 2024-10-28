<?php

declare(strict_types=1);

namespace App\Prompts;

use App\Prompts\Abstract\AbstractOpenApiGenerator;

class GenerateArticleContentToOtherLanguagePrompt extends AbstractOpenApiGenerator
{

    protected static function preparePrompt(array $data = []): string
    {
        return 'Przetłumacz tekst na język: "' . $data['language'] . '". Nie zmieniaj struktury i nie usuwaj znaczników HTML, nagłówki, pogrubienia itd chce aby wygląd był ten sam ale tekst w języku "' . $data['language'] . '". ';
    }

}
