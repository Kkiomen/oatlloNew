<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseCategoryLesson;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Wyświetla listę wszystkich kursów
     */
    public function index()
    {
        $courses = Course::where('is_published', true)
            ->with(['categories' => function($query) {
                $query->withCount('lessons');
            }])
            ->get();

        return view('courses.index', compact('courses'));
    }

    /**
     * Wyświetla szczegóły kursu
     */
    public function show($courseName)
    {
        $course = Course::where('slug', $courseName)
            ->where('is_published', true)
            ->with(['categories' => function($query) {
                $query->with(['lessons' => function($q) {
                    $q->orderBy('sort');
                }]);
            }])
            ->firstOrFail();

        return view('courses.show', compact('course'));
    }

    /**
     * Wyświetla rozdział kursu
     */
    public function showChapter($courseName, $chapter)
    {
        $course = Course::where('slug', $courseName)
            ->where('is_published', true)
            ->firstOrFail();

        $category = CourseCategory::where('course_id', $course->id)
            ->where('slug', $chapter)
            ->where('is_published', true)
            ->with(['lessons' => function($query) {
                $query->orderBy('sort');
            }])
            ->firstOrFail();

        return view('courses.chapter', compact('course', 'category'));
    }

    /**
     * Wyświetla lekcję
     */
    public function showLesson($courseName, $chapter, $lesson)
    {
        $course = Course::where('slug', $courseName)
            ->where('is_published', true)
            ->firstOrFail();

        $category = CourseCategory::where('course_id', $course->id)
            ->where('slug', $chapter)
            ->where('is_published', true)
            ->firstOrFail();

        $lesson = CourseCategoryLesson::where('course_category_id', $category->id)
            ->where('slug', $lesson)
            ->where('is_published', true)
            ->firstOrFail();

        // Pobierz poprzednią i następną lekcję
        $previousLesson = CourseCategoryLesson::where('course_category_id', $category->id)
            ->where('sort', '<', $lesson->sort)
            ->orderBy('sort', 'desc')
            ->first();

        $nextLesson = CourseCategoryLesson::where('course_category_id', $category->id)
            ->where('sort', '>', $lesson->sort)
            ->orderBy('sort')
            ->first();

        return view('courses.lesson', compact('course', 'category', 'lesson', 'previousLesson', 'nextLesson'));
    }
}
