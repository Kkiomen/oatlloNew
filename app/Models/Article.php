<?php

namespace App\Models;

use App\Services\Generator\InternalUrlsGenerator;
use App\Services\Generator\TagForArticleGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CourseCategoryLesson;
use App\Models\Category;
use App\Models\CourseCategory;
use App\Models\Tag;
use App\Models\ArticleSection;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_published',
        'published_at',
        'json_content',
        'type',
        'view_content',
        'contents',
        'ai_content',
        'short_description',
        'image',
        'schema_ai',
        'options_ai',
        'language',
        'connection_article_id',
        'structure_data_google',
        'keys_link',
        'auto_publish_date',
    ];

    protected $casts = [
        'json_content' => 'array',
        'view_content' => 'array',
        'contents' => 'array',
        'schema_ai' => 'array',
        'options_ai' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'auto_publish_date' => 'datetime',
    ];

    public function sections()
    {
        return $this->hasMany(ArticleSection::class)->orderBy('order');
    }

    public function lesson()
    {
        return $this->hasMany(CourseCategoryLesson::class, 'lesson_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tag_articles');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    /**
     * Zwraca nazwę kategorii
     * @return string|null
     */
    public function getCategoryName(): ?string
    {
        $categoryName = null;
        if ($this->category_id !== null) {
            $category = Category::find($this->category_id);
            $categoryName = $category?->name;
        }

        return $categoryName;
    }

    public function getShortDescriptionToBlogList(): string
    {
        if (empty($this->short_description)) {
            return '';
        }

        if (strlen($this->short_description) > 109) {
            return substr($this->short_description, 0, 109) . '...';
        }

        return $this->short_description;
    }

    public function getRoute(bool $absolute = true): string
    {
        if (!empty($this->category_id)) {
            $category = Category::find($this->category_id);
            if ($category) {
                return route('home.article_with_category', [
                    'categorySlug' => $category->slug,
                    'articleSlug' => $this->slug
                ]);
            }
        }

        return route('home.article', ['articleSlug' => $this->slug], $absolute);
    }

    public function getRouteCourse(?CourseCategory $category = null, bool $absolute = true): string
    {
        if ($category === null) {
            $categoryLesson = $this->lesson->first();
            $category = CourseCategory::find($categoryLesson->course_category_id);
        }

        $language = env('APP_LOCALE');

        if ($language === 'pl') {
            return route(
                'course_lesson_pl',
                ['courseName' => $category->course->slug, 'chapter' => $category->slug, 'lesson' => $this->slug],
                $absolute
            );
        } else {
            return route(
                'course_lesson_en',
                ['courseName' => $category->course->slug, 'chapter' => $category->slug, 'lesson' => $this->slug],
                $absolute
            );
        }
    }

    public function isCourseLesson(): bool
    {
        return false;
    }

    public function getPublishedDate(bool $asString = false): \DateTime|string
    {
        $date = $this->published_at ?? $this->updated_at;

        if ($asString) {
            return $date ? $date->format('Y-m-d') : '';
        }

        // Upewniamy się, że zwracamy obiekt DateTime
        if ($date instanceof \DateTime) {
            return $date;
        }

        // Jeśli to string, konwertujemy na DateTime
        if (is_string($date)) {
            return new \DateTime($date);
        }

        // Fallback do updated_at
        return $this->updated_at ?? new \DateTime();
    }

    public function getTimeRead(): int
    {
        $text = '';
        foreach ($this->contents as $content) {
            if ($content['type'] == 'text' && !empty($content['content'])) {
                $text .= ' ' . strip_tags($content['content']);
            }
        }

        $wordCount = str_word_count($text);
        $wordsPerMinute = 200; // średnia prędkość czytania
        $minutes = ceil($wordCount / $wordsPerMinute);

        return max($minutes, 1); // minimum 1 minuta
    }

    /**
     * Zwraca następny opublikowany artykuł
     * @return Article|null
     */
    public function getNextArticle(): ?Article
    {

        return Article::where('id', '>', $this->id)
            ->where('is_published', true)
            ->where('type', 'normal')
            ->where('language', $this->language)
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Zwraca poprzedni opublikowany artykuł
     * @return Article|null
     */
    public function getPreviousArticle(): ?Article
    {

        return Article::where('id', '<', $this->id)
            ->where('is_published', true)
            ->where('type', 'normal')
            ->where('language', $this->language)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Zwraca powiązane artykuły na podstawie kategorii i tagów
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRelatedArticles(int $limit = 6): \Illuminate\Database\Eloquent\Collection
    {

        $query = Article::where('id', '!=', $this->id)
            ->where('is_published', true)
            ->where('type', 'normal')
            ->where('language', $this->language);

        // Jeśli artykuł ma kategorię, szukaj artykułów z tej samej kategorii
        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }

        // Jeśli artykuł ma tagi, szukaj artykułów z podobnymi tagami
        if ($this->tags->count() > 0) {
            $tagIds = $this->tags->pluck('id')->toArray();
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tag_id', $tagIds);
            });
        }

        // Jeśli nie ma ani kategorii ani tagów, zwróć najnowsze artykuły
        if (!$this->category_id && $this->tags->count() == 0) {
            return Article::where('id', '!=', $this->id)
                ->where('is_published', true)
                ->where('type', 'normal')
                ->where('language', $this->language)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Zwraca popularne artykuły (najnowsze z największą liczbą wyświetleń)
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPopularArticles(int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {

        return Article::where('is_published', true)
            ->where('type', 'normal')
            ->where('language', env('APP_LOCALE'))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Zwraca artykuły z tej samej kategorii
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCategoryArticles(int $limit = 6): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->category_id) {
            return Article::where('id', 0)->get(); // Zwraca pustą Eloquent Collection
        }


        return Article::where('id', '!=', $this->id)
            ->where('is_published', true)
            ->where('type', 'normal')
            ->where('language', $this->language)
            ->where('category_id', $this->category_id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Zwraca najnowsze artykuły (ostatnio opublikowane)
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getLatestArticles(int $limit = 6): \Illuminate\Database\Eloquent\Collection
    {

        return Article::where('is_published', true)
            ->where('type', 'normal')
            ->where('language', env('APP_LOCALE'))
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function publish(self $article, bool $publish): void
    {
        $article->is_published = $publish;
        if($publish === true){
            $article->published_at = Carbon::now();
            TagForArticleGenerator::generate();
            InternalUrlsGenerator::generate();
        }
    }
}
