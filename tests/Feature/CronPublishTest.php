<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Services\Article\MarkdownArticleRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CronPublishTest extends TestCase
{
    use RefreshDatabase;

    private string $articlesDir;
    private string $sitemapDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Izolacja: własne katalogi tymczasowe na artykuły .md i sitemap,
        // aby nie czytać/nadpisywać realnych plików projektu.
        $this->articlesDir = storage_path('framework/testing/cron-articles-' . uniqid());
        $this->sitemapDir = storage_path('framework/testing/cron-sitemap-' . uniqid());
        File::ensureDirectoryExists($this->articlesDir);
        File::ensureDirectoryExists($this->sitemapDir);

        config()->set('articles.path', $this->articlesDir);
        config()->set('articles.sitemap_path', $this->sitemapDir);
    }

    protected function tearDown(): void
    {
        foreach ([$this->articlesDir, $this->sitemapDir] as $dir) {
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
            }
        }

        parent::tearDown();
    }

    private function makeArticle(bool $isPublished, ?Carbon $publishedAt): Article
    {
        return Article::create([
            'name' => 'Article ' . uniqid(),
            'slug' => 'article-' . uniqid(),
            'is_published' => $isPublished,
            'published_at' => $publishedAt,
            'language' => 'en',
            'type' => 'normal',
            'contents' => [['type' => 'text', 'content' => 'body']],
        ]);
    }

    public function test_endpoint_is_public_and_returns_summary(): void
    {
        $this->getJson('/api/cron')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'sitemap_regenerated' => true]);
    }

    public function test_publishes_article_whose_scheduled_time_passed(): void
    {
        $article = $this->makeArticle(false, Carbon::now()->subHour());

        $this->getJson('/api/cron')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'published_count' => 1])
            ->assertJsonFragment(['slug' => $article->slug]);

        $this->assertTrue($article->fresh()->is_published);
    }

    public function test_does_not_publish_article_scheduled_in_the_future(): void
    {
        $article = $this->makeArticle(false, Carbon::now()->addDay());

        $this->getJson('/api/cron')
            ->assertStatus(200)
            ->assertJson(['published_count' => 0]);

        $this->assertFalse($article->fresh()->is_published);
    }

    public function test_does_not_publish_draft_without_scheduled_date(): void
    {
        $article = $this->makeArticle(false, null);

        $this->getJson('/api/cron')
            ->assertStatus(200)
            ->assertJson(['published_count' => 0]);

        $this->assertFalse($article->fresh()->is_published);
    }

    public function test_regenerates_sitemap_file(): void
    {
        $this->getJson('/api/cron')->assertStatus(200);

        $this->assertTrue(File::exists($this->sitemapDir . DIRECTORY_SEPARATOR . 'sitemap.xml'));
    }

    public function test_markdown_article_scheduled_in_future_is_hidden_until_due(): void
    {
        $repo = app(MarkdownArticleRepository::class);

        // Zaplanowany na przyszłość -> ukryty na liście opublikowanych.
        File::put(
            $this->articlesDir . DIRECTORY_SEPARATOR . 'future.md',
            "---\nname: \"Future\"\nslug: future\nlanguage: en\npublished_at: " . Carbon::now()->addDays(3)->toDateString() . "\n---\n\nbody"
        );

        // Termin już minął -> widoczny.
        File::put(
            $this->articlesDir . DIRECTORY_SEPARATOR . 'past.md',
            "---\nname: \"Past\"\nslug: past\nlanguage: en\npublished_at: " . Carbon::now()->subDays(3)->toDateString() . "\n---\n\nbody"
        );

        $slugs = $repo->published('en')->pluck('slug')->all();

        $this->assertContains('past', $slugs);
        $this->assertNotContains('future', $slugs);
    }
}
