<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCategoryLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_category_id',
        'lesson_id',
        'sort',
    ];

    public function lesson()
    {
        return $this->belongsTo(Article::class);
    }

    public function category()
    {
        return $this->belongsTo(CourseCategory::class);
    }

    public function getRoute(): string
    {
        $language = env('APP_LOCALE');
        if($language === 'pl'){
            return route('course_lesson_pl', ['courseName' => $this->category->course->slug, 'chapter' => $this->category->slug, 'lesson' => $this->lesson->slug]);
        }else{
            return route('course_lesson_en', ['courseName' => $this->category->course->slug, 'chapter' => $this->category->slug, 'lesson' => $this->lesson->slug]);
        }
    }

}
