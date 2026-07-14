<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCategoryLesson;
use App\Models\Tag;
use App\Services\Article\MarkdownArticleRepository;
use App\Services\Library\SitemapGenerator;
use Illuminate\Support\Str;

class SitemapService
{
    public static function generateSitemap(): void
    {

        $sitemap = new SitemapGenerator(route('index'));
        // Katalog docelowy jest konfigurowalny (domyślnie public/), aby testy
        // mogły kierować wynik do katalogu tymczasowego zamiast nadpisywać
        // wersjonowany public/sitemap.xml.
        $sitemapPath = config('articles.sitemap_path') ?: public_path('/');
        $sitemap->setPath(rtrim((string) $sitemapPath, '/\\') . DIRECTORY_SEPARATOR);
        $sitemap->setFilename('sitemap');
        $date = now()->toIso8601String();

        $sitemap->addItem('/', '1.0', 'daily', $date);
        $sitemap->addItem(route('blog', [], false), '0.8', 'daily', $date);
        $sitemap->addItem(route('site.map', [], false), '0.3', 'weekly', $date);
        if (\Illuminate\Support\Facades\Route::has('about.us')) {
            $sitemap->addItem(route('about.us', [], false), '0.5', 'monthly', $date);
        }

        // Drugie źródło: artykuły z plików .md.
        $language = env('LANGUAGE_MODE') == 'strict' ? env('APP_LOCALE') : null;
        $mdArticles = app(MarkdownArticleRepository::class)->published($language);

        // ============= TAGI ==================
        $tagSlugs = [];
        $tags = Tag::where('language', env('APP_LOCALE'))->get();
        foreach ($tags as $tag){
            $slug = Str::slug($tag->name);
            $tagSlugs[$slug] = true;
            $sitemap->addItem(
                loc: route('blogTag', ['tag' => $slug], false),
                priority: '0.3',
                changefreq: 'weekly',
                lastmod: $tag->updated_at->toIso8601String()
            );
        }

        // Tagi istniejące wyłącznie w plikach .md.
        foreach ($mdArticles as $mdArticle){
            foreach ($mdArticle->tags as $mdTag){
                if (isset($tagSlugs[$mdTag->slug])) {
                    continue;
                }
                $tagSlugs[$mdTag->slug] = true;
                $sitemap->addItem(
                    loc: route('blogTag', ['tag' => $mdTag->slug], false),
                    priority: '0.3',
                    changefreq: 'weekly',
                    lastmod: $date
                );
            }
        }


        // Add different category posts
        $categories = static::prepareCategoriesBlog($mdArticles);
        if($categories->count() > 0){
            foreach($categories as $category){
                $sitemap->addItem(route('blog.list.category', ['slug' => $category->slug], false), '0.7', 'weekly', 'Today');
            }
        }

        // Usuwamy logikę lessonsNotIn - CourseCategoryLesson nie ma już lesson_id
        // i nie powiązuje się z Article

        // Add blog posts
        if(env('LANGUAGE_MODE') == 'strict'){
             $articles = Article::where('is_published', true)->where('language', env('APP_LOCALE'))->get();
        }else{
            $articles = Article::where('is_published', true)->get();
        }

        // Scalanie: pliki .md mają pierwszeństwo przed bazą przy tym samym slug.
        $mergedArticles = [];
        foreach($articles as $article){
            $mergedArticles[$article->slug] = $article;
        }
        foreach($mdArticles as $article){
            $mergedArticles[$article->slug] = $article;
        }

        foreach($mergedArticles as $article){
            $sitemap->addItem(
                loc: $article->getRoute(false) ,
                priority: '0.7',
                changefreq: 'weekly',
                lastmod: $article->getPublishedDate()->toIso8601String()
            );
        }

        $defaultLangue = env('APP_LOCALE');
        if($defaultLangue == 'pl'){
            $sitemap->addItem(
                loc: route('courses', [],false),
                priority: '0.7',
                changefreq: 'weekly',
                lastmod: $date
            );
        }else{
            $sitemap->addItem(
                loc: route('courses_en', [],false),
                priority: '0.7',
                changefreq: 'weekly',
                lastmod: $date
            );
        }


        // Add courses and their content
        $courses = Course::where('is_published', 1)->get();
        foreach ($courses as $course){
            $sitemap->addItem(
                loc: $course->getRoute(false),
                priority: '0.7',
                changefreq: 'weekly',
                lastmod: $course->updated_at->toIso8601String()
            );

            foreach ($course->categories as $category) {
                $sitemap->addItem(
                    loc: $category->getRoute(false),
                    priority: '0.7',
                    changefreq: 'weekly',
                    lastmod: $category->updated_at->toIso8601String()
                );

                foreach ($category->lessons as $lesson) {
                    // Sprawdź czy lekcja ma slug przed dodaniem do sitemap
                    if (!empty($lesson->slug)) {
                        $lessonUrl = $lesson->getRoute(false);
                        if (!empty($lessonUrl)) {
                            $sitemap->addItem(
                                loc: $lessonUrl,
                                priority: '0.7',
                                changefreq: 'weekly',
                                lastmod: $lesson->updated_at->toIso8601String()
                            );
                        }
                    }
                }
            }
        }

        $sitemap->createSitemapIndex(route('index').'/', 'Today');
    }

    protected static function prepareCategoriesBlog($mdArticles = null): mixed
    {
        $uniqueCategoryIds = Article::whereNotNull('category_id')->where('is_published', true)
            ->distinct()
            ->pluck('category_id')
            ->all();

        // Kategorie przypisane do artykułów z plików .md.
        if ($mdArticles !== null) {
            foreach ($mdArticles as $mdArticle) {
                if (!empty($mdArticle->category_id)) {
                    $uniqueCategoryIds[] = $mdArticle->category_id;
                }
            }
        }

        return Category::whereIn('id', array_unique($uniqueCategoryIds))->get();
    }
}
