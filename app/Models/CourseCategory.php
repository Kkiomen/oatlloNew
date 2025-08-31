<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Course;
use App\Models\CourseCategoryLesson;

class CourseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'category_name',
        'lang',
        'is_published',
        'slug',
        'title_seo',
        'description_seo',
        'title',
        'description',
        'description_content',
        'sort',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(CourseCategoryLesson::class)->where('is_published', true)->orderBy('sort');
    }

    public function allLessons()
    {
        return $this->hasMany(CourseCategoryLesson::class)->orderBy('sort');
    }

    public function getRoute(bool $absolute = true): string
    {
        // Wymuszamy angielski URL dla kategorii
        return route('course_chapter_en', ['courseName' => $this->course->slug, 'chapter' => $this->slug], $absolute);
    }
}
