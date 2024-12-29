<?php

declare(strict_types=1);

namespace App\Prompts;

use App\Prompts\Abstract\AbstractOpenApiGenerator;

class AidevsPrompt extends AbstractOpenApiGenerator
{

    protected static function preparePrompt(array $data = []): string
    {
        return 'Na podstawie pytania użytkownika zwróć tylko date i nic więcej';
    }

}
