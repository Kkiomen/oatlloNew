<?php

declare(strict_types=1);

namespace App\Services\Helper;

class GeneratorHelper
{
    public static function randomPassword( $length = 8 ) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = substr( str_shuffle( $chars ), 0, $length );
        return $password;
    }

    public static function preparePromptForApi(string $prompt): string
    {
        $prompt = preg_replace('/\s+/', ' ', $prompt);

        return trim($prompt);
    }
}
