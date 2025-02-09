<?php

declare(strict_types=1);

namespace App\Magisterka\Enum;

enum FileType: string
{
    case CONTROLLER = 'controller';
    case SERVICE_APPLICATION = 'service_application';
    case SERVICE_DOMAIN = 'service_domain';
    case SERVICE_PRESENTATION = 'service_presentation';
}
