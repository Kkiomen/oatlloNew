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


}
