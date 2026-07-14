<?php

namespace App\Models;

use App\Services\Article\ContentSanitizer;
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

    /**
     * Treść lekcji (content_html) oczyszczona tuż przed wyświetleniem
     * (myślniki em/en -> dywiz, słownik anti-AI).
     */
    public function getDisplayContentHtml(): string
    {
        if (empty($this->content_html)) {
            return '';
        }

        return app(ContentSanitizer::class)->sanitize((string) $this->content_html);
    }

    /**
     * Fallbackowe bloki treści (contents) oczyszczone przed wyświetleniem.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getDisplayContents(): array
    {
        $sanitizer = app(ContentSanitizer::class);

        return array_map(function ($content) use ($sanitizer) {
            if (($content['type'] ?? null) === 'text' && !empty($content['content'])) {
                $content['content'] = $sanitizer->sanitize((string) $content['content']);
            }

            return $content;
        }, $this->contents ?? []);
    }

    public function getRoute(bool $absolute = true): string
    {
        // Sprawdź czy wszystkie wymagane pola istnieją
        if (empty($this->slug) || empty($this->category) || empty($this->category->course) || empty($this->category->course->slug) || empty($this->category->slug)) {
            return '';
        }

        // Wymuszamy angielski URL dla lekcji
        return route('course_lesson_en', ['courseName' => $this->category->course->slug, 'chapter' => $this->category->slug, 'lesson' => $this->slug], $absolute);
    }
}
