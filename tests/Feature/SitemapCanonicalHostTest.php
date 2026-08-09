<?php

namespace Tests\Feature;

use App\Services\SitemapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * URL-e z naszej domeny wychodzą do Google wyłącznie na kanonicznym hoście.
 *
 * Kontekst (eksport GSC 2026-08-09): sitemap miał `<loc>` na `https://oatllo.com`,
 * ale 14 `<image:loc>` z okładkami artykułów na `https://www.oatllo.com`. Pole
 * `image` artykułu `.md` powstaje z `route('article.cover')`, czyli z hosta
 * BIEŻĄCEGO ŻĄDANIA, i trafia do cache'u sparsowanych plików. Jedno wejście robota
 * na `www.` zatruwało cache, a sitemap generowany z CLI czytał go i publikował
 * dalej. Efekt: Google widział dwa hosty i raportował duplikaty kanoniczne.
 *
 * Dlatego kanonikalizacja stoi w DWÓCH miejscach i oba są konieczne:
 * `AppServiceProvider` przybija korzeń `route()` (żeby zatrucie nie powstało),
 * a `SitemapService` czyści URL na wyjściu (żeby JUŻ ZATRUTY wpis w cache'u nie
 * wyciekł po wdrożeniu poprawki, zanim ktokolwiek wyczyści cache).
 * Ten test pilnuje tego drugiego.
 */
class SitemapCanonicalHostTest extends TestCase
{
    use RefreshDatabase;

    private string $articlesDir;
    private string $sitemapDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->articlesDir = storage_path('framework/testing/sitemap-host-articles-' . uniqid());
        $this->sitemapDir = storage_path('framework/testing/sitemap-host-out-' . uniqid());
        File::ensureDirectoryExists($this->articlesDir);
        File::ensureDirectoryExists($this->sitemapDir);

        config()->set('articles.path', $this->articlesDir);
        config()->set('articles.sitemap_path', $this->sitemapDir);
        config()->set('app.canonical_host', 'oatllo.com');
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

    private function writeArticle(string $slug, string $image): void
    {
        File::put(
            $this->articlesDir . DIRECTORY_SEPARATOR . $slug . '.md',
            "---\nname: \"Host test\"\nslug: {$slug}\nlanguage: " . env('APP_LOCALE')
                . "\npublished_at: " . Carbon::now()->subDay()->toDateString()
                . "\nimage: \"{$image}\"\n---\n\nbody"
        );
    }

    private function sitemapContents(): string
    {
        SitemapService::generateSitemap();

        $file = $this->sitemapDir . DIRECTORY_SEPARATOR . 'sitemap.xml';
        $this->assertFileExists($file, 'Sitemap nie została wygenerowana.');

        return File::get($file);
    }

    public function test_www_image_url_is_rewritten_to_the_canonical_host(): void
    {
        $this->writeArticle('poisoned', 'https://www.oatllo.com/articles/poisoned/cover.svg');

        $xml = $this->sitemapContents();

        $this->assertStringNotContainsString('https://www.oatllo.com', $xml);
        $this->assertStringContainsString('https://oatllo.com/articles/poisoned/cover.svg', $xml);
    }

    public function test_http_image_url_on_our_host_is_upgraded_to_https(): void
    {
        $this->writeArticle('insecure', 'http://oatllo.com/articles/insecure/cover.svg');

        $this->assertStringContainsString(
            'https://oatllo.com/articles/insecure/cover.svg',
            $this->sitemapContents()
        );
    }

    /**
     * Obcy host zostaje nietknięty. Autor może podać we frontmatterze obrazek
     * z CDN-u i przepisanie go na naszą domenę dałoby martwy URL.
     */
    public function test_external_image_url_is_left_alone(): void
    {
        $external = 'https://cdn.example.com/img/cover.png';
        $this->writeArticle('external', $external);

        $this->assertStringContainsString($external, $this->sitemapContents());
    }
}
