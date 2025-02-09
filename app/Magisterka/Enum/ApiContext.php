<?php

namespace App\Magisterka\Enum;

enum ApiContext: string
{
    case ADMIN_API = 'admin_api';
    case UI_API = 'ui_api';
}
