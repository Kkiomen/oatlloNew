<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Course;
use App\Services\Library\SitemapGenerator;

class SitemapService
{
    public static function generateSitemap(): void
    {

        $sitemap = new SitemapGenerator(route('index'));
        $sitemap->setPath(public_path('/'));
        $sitemap->setFilename('sitemap');
        $date = now()->toIso8601String();

        $sitemap->addItem('/', '1.0', 'daily', $date);
        $sitemap->addItem(route('blog', [], false), '0.8', 'weekly', $date);

        // Add different category posts
        $categories = static::prepareCategoriesBlog();
        if($categories->count() > 0){
            foreach($categories as $category){
                $sitemap->addItem(route('blog.list.category', ['slug' => $category->slug], false), '0.7', 'weekly', 'Today');
            }
        }

        // Add blog posts
        if(env('LANGUAGE_MODE') == 'strict'){
             $articles = Article::where('is_published', true)->where('language', env('APP_LOCALE'))->get();
        }else{
            $articles = Article::where('is_published', true)->get();
        }

        if($articles->count() > 0){
            foreach($articles as $article){
                $sitemap->addItem(
                    loc: $article->getRoute(false) ,
                    priority: '0.7',
                    changefreq: 'weekly',
                    lastmod: $article->updated_at->toIso8601String()
                );

            }
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


        $defaultLangue = env('APP_LOCALE');
        $courses = Course::where('lang', $defaultLangue)->where('is_published', 1)->get();
        foreach ($courses as $course){
            $sitemap->addItem(
                loc: $course->getRoute(false) ,
                priority: '0.7',
                changefreq: 'weekly',
                lastmod: $article->updated_at->toIso8601String()
            );

            foreach ($course->categories as $category) {
                $sitemap->addItem(
                    loc: $category->getRoute(false),
                    priority: '0.7',
                    changefreq: 'weekly',
                    lastmod: $article->updated_at->toIso8601String()
                );

                foreach ($category->lessons as $lesson) {
                    $sitemap->addItem(
                        loc: $lesson->getRouteCourse($category, false),
                        priority: '0.7',
                        changefreq: 'weekly',
                        lastmod: $article->updated_at->toIso8601String()
                    );
                }
            }
        }

        $sitemap->createSitemapIndex(route('index').'/', 'Today');
    }

    protected static function prepareCategoriesBlog(): mixed
    {
        $uniqueCategoryIds = Article::whereNotNull('category_id')->where('is_published', true)
            ->distinct()
            ->pluck('category_id');

        return Category::whereIn('id', $uniqueCategoryIds)->get();
    }
}
