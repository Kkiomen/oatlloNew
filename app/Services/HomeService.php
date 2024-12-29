<?php

declare(strict_types=1);

namespace App\Services;

class HomeService
{

    public static function getRouteCourses(): string
    {
        $defaultLangue = env('APP_LOCALE');
        if($defaultLangue == 'pl'){
            return route('courses');
        }

        return route('courses.en');
    }
}
