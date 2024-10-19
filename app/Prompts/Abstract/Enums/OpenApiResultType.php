<?php

declare(strict_types=1);

namespace App\Prompts\Abstract\Enums;

enum OpenApiResultType: string
{
    case NORMAL = 'normal';
    case JSON_OBJECT = 'json_object';
}
