<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Tag;
use App\Services\SitemapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Strony tagów są noindex i CELOWO nie trafiają do sitemapy.
 *
 * Kontekst: tagi stanowiły 256 z 393 URL-i (65%) sitemapy i Google odmawiał
 * indeksacji 203 z nich, przepalając crawl budget należny artykułom. Ten test
 * pilnuje, żeby tagi nie wróciły do mapy — ani z bazy, ani z plików .md.
 */
class SitemapTagExclusionTest extends TestCase
{
    use RefreshDatabase;

    private string $articlesDir;
    private string $sitemapDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->articlesDir = storage_path('framework/testing/sitemap-articles-' . uniqid());
        $this->sitemapDir = storage_path('framework/testing/sitemap-out-' . uniqid());
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

    private function sitemapContents(): string
    {
        SitemapService::generateSitemap();

        $file = $this->sitemapDir . DIRECTORY_SEPARATOR . 'sitemap.xml';
        $this->assertFileExists($file, 'Sitemap nie została wygenerowana.');

        return File::get($file);
    }

    public function test_sitemap_excludes_tag_pages_from_database(): void
    {
        Tag::create(['name' => 'Php Enums', 'language' => env('APP_LOCALE'), 'slug' => 'php-enums']);

        $this->assertStringNotContainsString('/blog/tag/', $this->sitemapContents());
    }

    public function test_sitemap_excludes_tag_pages_coming_only_from_markdown_articles(): void
    {
        File::put(
            $this->articlesDir . DIRECTORY_SEPARATOR . 'tagged.md',
            "---\nname: \"Tagged\"\nslug: tagged\nlanguage: " . env('APP_LOCALE')
                . "\npublished_at: " . Carbon::now()->subDay()->toDateString()
                . "\ntags: [laravel, queues]\n---\n\nbody"
        );

        $xml = $this->sitemapContents();

        $this->assertStringNotContainsString('/blog/tag/', $xml);
        // Sam artykuł nadal musi być w mapie — wycinamy tagi, nie treść.
        $this->assertStringContainsString('/tagged', $xml);
    }

    public function test_sitemap_still_contains_published_database_article(): void
    {
        $article = Article::create([
            'name' => 'Real Article',
            'slug' => 'real-article',
            'is_published' => true,
            'published_at' => Carbon::now()->subDay(),
            'language' => env('APP_LOCALE'),
            'type' => 'normal',
            'contents' => [['type' => 'text', 'content' => 'body']],
        ]);

        $this->assertStringContainsString($article->slug, $this->sitemapContents());
    }
}
