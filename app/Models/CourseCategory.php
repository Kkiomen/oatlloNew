<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->belongsToMany(Article::class, 'course_category_lessons', 'course_category_id', 'lesson_id')->withPivot('sort')->orderBy('sort');
    }

    public function lessonsMore(): array
    {
        $categoriesLessons = CourseCategoryLesson::where('course_category_id', $this->id)->orderBy('sort')->get();
        $lessons = [];

        foreach($categoriesLessons as $categoryLesson){
            $article = Article::find($categoryLesson->lesson_id);
            if(!$article){
                continue;
            }

            $lessons[] = [
                'id' => $categoryLesson->id,
                'name' => $article->name,
                'sort' => $categoryLesson->sort,
                'lesson_id' => $article->id
            ];

        }

        return $lessons;
    }

    public function getRoute(): string
    {
        $language = env('APP_LOCALE');
        if($language === 'pl'){
            return route('course_chapter_pl', ['courseName' => $this->course->slug, 'chapter' => $this->slug]);
        }

        return route('course_chapter_en', ['courseName' => $this->course->slug, 'chapter' => $this->slug]);
    }
}
