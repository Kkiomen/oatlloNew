<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogArticleSeeder extends Seeder
{
    /**
     * Seed a handful of blog articles with varying publish dates.
     */
    public function run(): void
    {
        $articles = [
            [
                'name' => 'Getting Started with Laravel 11',
                'short_description' => 'A practical walkthrough of the new features and structure introduced in Laravel 11.',
                'image' => 'https://picsum.photos/seed/laravel11/1200/630',
                'published_at' => '2025-01-15 09:30:00',
                'contents' => [
                    ['type' => 'text', 'content' => '<h2>Introduction</h2><p>Laravel 11 streamlines the application skeleton and reduces boilerplate. In this article we walk through the essential changes.</p>'],
                    ['type' => 'text', 'content' => '<h2>Slimmer Structure</h2><p>The default directory layout is now leaner, with configuration consolidated and middleware registered in <code>bootstrap/app.php</code>.</p>'],
                ],
            ],
            [
                'name' => '10 Tips for Faster PHP Applications',
                'short_description' => 'Concrete techniques to squeeze more performance out of your PHP stack.',
                'image' => 'https://picsum.photos/seed/phpperf/1200/630',
                'published_at' => '2025-03-02 14:00:00',
                'contents' => [
                    ['type' => 'text', 'content' => '<h2>Enable OPcache</h2><p>OPcache is the single quickest win for most PHP applications. Enable it and tune the memory limits for your workload.</p>'],
                    ['type' => 'text', 'content' => '<h2>Offload Heavy Work</h2><p>Move slow tasks to queues so requests stay fast and responsive.</p>'],
                ],
            ],
            [
                'name' => 'Understanding Eloquent Relationships',
                'short_description' => 'From hasOne to morphMany, a clear guide to Laravel Eloquent relationships.',
                'image' => 'https://picsum.photos/seed/eloquent/1200/630',
                'published_at' => '2025-04-21 11:15:00',
                'contents' => [
                    ['type' => 'text', 'content' => '<h2>One to Many</h2><p>The most common relationship. A blog has many articles, and each article belongs to a blog.</p>'],
                    ['type' => 'text', 'content' => '<h2>Many to Many</h2><p>Pivot tables let you connect records both ways, such as articles and tags.</p>'],
                ],
            ],
            [
                'name' => 'Deploying Laravel with Docker',
                'short_description' => 'A repeatable, containerized deployment workflow for Laravel applications.',
                'image' => 'https://picsum.photos/seed/dockerlaravel/1200/630',
                'published_at' => '2025-06-10 08:45:00',
                'contents' => [
                    ['type' => 'text', 'content' => '<h2>Why Docker</h2><p>Containers give you a consistent environment from development to production.</p>'],
                    ['type' => 'text', 'content' => '<h2>Multi-stage Builds</h2><p>Keep your final image small by building assets in a separate stage.</p>'],
                ],
            ],
            [
                'name' => 'Writing Testable Code in Laravel',
                'short_description' => 'How to structure your application so tests are easy to write and maintain.',
                'image' => 'https://picsum.photos/seed/testing/1200/630',
                'published_at' => '2025-07-01 16:20:00',
                'contents' => [
                    ['type' => 'text', 'content' => '<h2>Depend on Abstractions</h2><p>Inject dependencies through the constructor so they can be swapped in tests.</p>'],
                    ['type' => 'text', 'content' => '<h2>Feature vs Unit</h2><p>Use feature tests to exercise whole flows and unit tests for isolated logic.</p>'],
                ],
            ],
        ];

        foreach ($articles as $data) {
            Article::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'is_published' => true,
                'type' => 'normal',
                'language' => 'en',
                'short_description' => $data['short_description'],
                'image' => $data['image'],
                'contents' => $data['contents'],
                'published_at' => $data['published_at'],
                'created_at' => $data['published_at'],
                'updated_at' => $data['published_at'],
            ]);
        }
    }
}
