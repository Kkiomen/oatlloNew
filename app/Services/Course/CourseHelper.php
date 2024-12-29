<?php

declare(strict_types=1);

namespace App\Services\Course;

use App\Models\Article;
use App\Models\Course;
use App\Models\CourseCategory;

class CourseHelper
{
    public static function lessonGo(Course $course,  Article $article): array
    {
        $lessons = [];
        $founded = false;
        $finish = false;

        foreach ($course->categories as $category){
            foreach ($category->lessons as $lesson){
                $lessons[] = [
                    'name' => $lesson->name,
                    'route' => $lesson->getRouteCourse($category)
                ];

                if ($founded){
                    $finish = true;
                    break;
                }

                if($lesson->name == $article->name){
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
