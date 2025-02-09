<?php

declare(strict_types=1);

namespace App\Magisterka\Enum;

enum MethodType: string
{
    case GET_DETAILS = 'GET_DETAILS';
    case GET_LIST = 'GET_LIST';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
