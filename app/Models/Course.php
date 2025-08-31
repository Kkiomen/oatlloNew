<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CourseCategory;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'name',
        'is_published',
        'lang',
        'image',
        'slug',
        'description_seo',
        'title_seo',
        'title_list',
        'description_list',
        'title_full',
        'description_full',
        'content_description_offers',
    ];

    public function categories()
    {
        return $this->hasMany(CourseCategory::class)->where('is_published', true)->orderBy('sort');
    }

    public function getRoute(bool $absolute = true)
    {
        // Wymuszamy angielski URL dla kursu
        return route('course_en', ['courseName' => $this->slug], $absolute);
    }
}
