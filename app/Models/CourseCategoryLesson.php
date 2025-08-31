<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CourseCategory;

class CourseCategoryLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_category_id',
        'title',
        'slug',
        'content_html',
        'meta_hash',
        'position',
        'seo_title',
        'seo_description',
        'is_published',
        'sort',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'position' => 'integer',
        'sort' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    public function getRoute(bool $absolute = true): string
    {
        // Wymuszamy angielski URL dla lekcji
        return route('course_lesson_en', ['courseName' => $this->category->course->slug, 'chapter' => $this->category->slug, 'lesson' => $this->slug], $absolute);
    }
}
