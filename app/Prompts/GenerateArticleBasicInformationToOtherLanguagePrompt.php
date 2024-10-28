<?php

declare(strict_types=1);

namespace App\Prompts;

use App\Prompts\Abstract\AbstractOpenApiGenerator;

class GenerateArticleBasicInformationToOtherLanguagePrompt extends AbstractOpenApiGenerator
{

    protected static function preparePrompt(array $data = []): string
    {
        return 'Przetłumacz wartości w json w kluczach "value" na język: "' . $data['language'] . '". Nie zmieniaj struktury, i innych kluczy, zwróć go w tej samej formie tylko przetłumacz klucze value';
    }

}
