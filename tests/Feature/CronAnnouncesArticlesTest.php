<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tick MUSI zgłaszać do IndexNow artykuły .md, które właśnie weszły na żywo.
 *
 * LUKA, KTÓREJ PILNUJE TEN TEST (znaleziona 06.09.2026): tick ogłaszał KURSY,
 * a artykułów NIE - mimo że kursy dodajemy raz na kilka tygodni, a artykuły
 * wychodzą 3 razy w tygodniu i jest ich 134 w kolejce. Artykuł .md wchodzi na
 * żywo SAM, gdy `published_at` minie: bez deployu, bez eventu, bez commita -
 * więc nie odpalał się też ręczny `indexnow:submit-sitemap` z checklisty.
 * Jedyne treści, które faktycznie publikujemy, były jedynymi, których nikt
 * nie zgłaszał wyszukiwarkom.
 *
 * Test oblewa, jeśli ktoś usunie `announceDueArticles()` z ticka - a objaw
 * w produkcji byłby NIEWIDOCZNY (strona działa, sitemap się generuje, tylko
 * indeksacja jest wolniejsza o tygodnie).
 */
class CronAnnouncesArticlesTest extends TestCase
{
    use RefreshDatabase;

    private string $articlesDir;

    private string $sitemapDir;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Http::fake();

        $this->articlesDir = storage_path('framework/testing/cron-articles-' . uniqid());
        File::ensureDirectoryExists($this->articlesDir);
        config()->set('articles.path', $this->articlesDir);

        $this->sitemapDir = storage_path('framework/testing/cron-sitemap-' . uniqid());
        File::ensureDirectoryExists($this->sitemapDir);
        config()->set('articles.sitemap_path', $this->sitemapDir);

        // IndexNow jest no-opem przy pustym kluczu - bez tego test sprawdzałby ciszę.
        config()->set('services.indexnow.key', 'test-indexnow-key-0123456789');
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

    private function writeArticle(string $slug, string $publishedAt): void
    {
        File::put($this->articlesDir . '/' . $slug . '.md', <<<MD
        ---
        name: "Test {$slug}"
        slug: {$slug}
        language: en
        is_published: true
        published_at: {$publishedAt}
        ---

        Body of {$slug}.
        MD);
    }

    public function test_tick_zglasza_zywy_artykul_md_i_zapisuje_stan(): void
    {
        $this->writeArticle('live-article', now()->subDay()->toDateString());

        $response = $this->getJson('/api/cron');

        $response->assertOk();
        $this->assertContains(
            'live-article',
            $response->json('articles_announced'),
            'Żywy artykuł .md musi zostać zgłoszony do IndexNow w ticku.'
        );

        Storage::assertExists('articles-announced.json');
    }

    public function test_artykul_zaplanowany_na_przyszlosc_nie_jest_zglaszany(): void
    {
        $this->writeArticle('future-article', now()->addMonth()->toDateString());

        $response = $this->getJson('/api/cron');

        $response->assertOk();
        $this->assertNotContains(
            'future-article',
            $response->json('articles_announced'),
            'Artykuł z datą w przyszłości nie jest jeszcze na stronie - zgłoszenie go byłoby kłamstwem wobec Google.'
        );
    }

    /**
     * Bez idempotencji tick pingowałby wszystkie 140 artykułów CO GODZINĘ
     * i spalił limity IndexNow.
     */
    public function test_ten_sam_artykul_nie_jest_zglaszany_dwa_razy(): void
    {
        $this->writeArticle('once-only', now()->subDay()->toDateString());

        $first = $this->getJson('/api/cron');
        $this->assertContains('once-only', $first->json('articles_announced'));

        $second = $this->getJson('/api/cron');
        $this->assertNotContains(
            'once-only',
            $second->json('articles_announced'),
            'Drugi tick nie może ponownie zgłaszać tego samego artykułu.'
        );
    }
}
