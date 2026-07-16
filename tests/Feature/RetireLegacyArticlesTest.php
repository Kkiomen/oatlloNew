<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RetireLegacyArticlesTest extends TestCase
{
    use RefreshDatabase;

    private string $sitemapDir;

    protected function setUp(): void
    {
        parent::setUp();

        // Tick regeneruje sitemap – kierujemy go w katalog tymczasowy, żeby nie
        // nadpisać wersjonowanego public/sitemap.xml.
        $this->sitemapDir = storage_path('framework/testing/retire-sitemap-' . uniqid());
        File::ensureDirectoryExists($this->sitemapDir);
        config()->set('articles.sitemap_path', $this->sitemapDir);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->sitemapDir)) {
            File::deleteDirectory($this->sitemapDir);
        }

        parent::tearDown();
    }

    private function article(string $slug, bool $published = true): Article
    {
        return Article::create([
            'slug' => $slug,
            'name' => "Test {$slug}",
            'contents' => 'test',
            'is_published' => $published,
            'language' => 'en',
            'published_at' => now()->subDay(),
        ]);
    }

    public function test_wygasza_stary_artykul_z_listy(): void
    {
        $legacy = $this->article('it-freelancing-pros-cons');

        $this->artisan('articles:retire-legacy --force')->assertSuccessful();

        $this->assertFalse($legacy->fresh()->is_published);
    }

    public function test_nie_rusza_artykulu_spoza_listy(): void
    {
        $keep = $this->article('php-enums-complete-guide');

        $this->artisan('articles:retire-legacy --force')->assertSuccessful();

        $this->assertTrue($keep->fresh()->is_published);
    }

    public function test_dry_run_niczego_nie_zapisuje(): void
    {
        $legacy = $this->article('disaster-recovery-database-systems');

        $this->artisan('articles:retire-legacy --dry-run')->assertSuccessful();

        $this->assertTrue($legacy->fresh()->is_published);
    }

    public function test_restore_cofa_wygaszenie(): void
    {
        $legacy = $this->article('master-php-enums-use-cases-tips');

        $this->artisan('articles:retire-legacy --force')->assertSuccessful();
        $this->assertFalse($legacy->fresh()->is_published);

        $this->artisan('articles:retire-legacy --restore --force')->assertSuccessful();
        $this->assertTrue($legacy->fresh()->is_published);
    }

    /**
     * `site-map` wygląda jak slug artykułu i JEST w sitemapie, ale to prawdziwa
     * mapa strony (trasa `site.map`, `/mapa` na nią przekierowuje). Wpisanie jej
     * na listę zabrałoby nawigację — i nie zobaczylibyśmy tego po samym slugu.
     */
    public function test_mapa_strony_nie_jest_na_liscie_do_wygaszenia(): void
    {
        $siteMap = $this->article('site-map');

        $this->artisan('articles:retire-legacy --force')->assertSuccessful();

        $this->assertTrue($siteMap->fresh()->is_published);
    }

    public function test_tick_crona_wygasza_stary_artykul(): void
    {
        $legacy = $this->article('it-freelancing-pros-cons');

        $this->getJson('/api/cron')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'retired_count' => 1]);

        $this->assertFalse($legacy->fresh()->is_published);
    }

    /**
     * NAJWAŻNIEJSZY TEST W TYM PLIKU.
     *
     * Warunek publikacji w ticku to "is_published = false + data w przeszłości" –
     * czyli dokładnie stan, w jakim zostaje wygaszony artykuł. Bez `whereNotIn`
     * w publishDueArticles() tick co godzinę cofałby własne wygaszenie, a my
     * zobaczylibyśmy sukces komendy i artykuły z powrotem na stronie.
     */
    public function test_tick_crona_NIE_publikuje_wygaszonego_artykulu_z_data_w_przeszlosci(): void
    {
        $legacy = $this->article('disaster-recovery-database-systems', published: false);

        $this->assertTrue($legacy->published_at->isPast(), 'Test wymaga daty w przeszłości.');

        $this->getJson('/api/cron')
            ->assertStatus(200)
            ->assertJson(['published_count' => 0]);

        $this->assertFalse($legacy->fresh()->is_published);
    }

    public function test_tick_crona_jest_idempotentny(): void
    {
        $this->article('master-php-enums-use-cases-tips');

        $this->getJson('/api/cron')->assertJson(['retired_count' => 1]);
        $this->getJson('/api/cron')->assertJson(['retired_count' => 0]);
        $this->getJson('/api/cron')->assertJson(['retired_count' => 0]);
    }

    public function test_tick_crona_dalej_publikuje_normalny_zaplanowany_artykul(): void
    {
        $normal = $this->article('php-enums-complete-guide', published: false);

        $this->getJson('/api/cron')
            ->assertStatus(200)
            ->assertJson(['published_count' => 1]);

        $this->assertTrue($normal->fresh()->is_published);
    }
}
