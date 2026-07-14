<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Models\Course;
use App\Models\CourseCategoryLesson;

class CourseHelper
{
    public static function lessonGo(Course $course, CourseCategoryLesson $lesson): array
    {
        $lessons = [];
        $founded = false;
        $finish = false;

        foreach ($course->categories as $category){
            foreach ($category->lessons as $categoryLesson){
                $lessons[] = [
                    'name' => $categoryLesson->title,
                    'route' => $categoryLesson->getRoute()
                ];

                if ($founded){
                    $finish = true;
                    break;
                }

                // Porównanie po trasie (unikalna: kurs/rozdział/lekcja) zamiast po id,
                // aby działało też dla kursów z plików .md (id = null).
                if($categoryLesson->getRoute() === $lesson->getRoute()){
                    $founded = true;
                }
            }

            if($finish){
                break;
            }
        }

        $lastTwoElements = array_slice($lessons, -2);
        $lastTwoElements = array_values($lastTwoElements);

        $results = [
            'previous' => $lastTwoElements[0] ?? null,
            'next' => $lastTwoElements[1] ?? null
        ];

        return $results;
    }
}
