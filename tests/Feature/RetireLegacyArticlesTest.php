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

    /**
     * `contents` jest castowane na `array` i renderowane blok po bloku
     * (`Article::getDisplayContents()`), więc fixture musi mieć kształt bloków.
     * Ze stringiem artykuł zapisuje się poprawnie, ale render trasy wywala się
     * na `array_map()` – co ukrywało fakt, że wygaszony artykuł w ogóle się renderuje.
     */
    private function article(string $slug, bool $published = true): Article
    {
        return Article::create([
            'slug' => $slug,
            'name' => "Test {$slug}",
            'contents' => [['type' => 'text', 'content' => 'test']],
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

    /**
     * DRUGI NAJWAŻNIEJSZY TEST W TYM PLIKU (dopisany 2026-07-27).
     *
     * Do tego dnia wygaszenie działało "wszędzie poza tym jednym miejscem, które się liczy":
     * artykuł znikał z list, z sitemapy i z wyszukiwarki na stronie, ale trasa /{articleSlug}
     * czytała bazę BEZ warunku `is_published`, więc bezpośredni URL dalej oddawał 200
     * z pełną treścią. Wg GSC (19-25.07.2026) wygaszone artykuły zbierały nadal 4 z 15
     * kliknięć domeny – czyli Google trzymał je w indeksie, bo miał je czym karmić.
     *
     * Test oblewa po usunięciu `where('is_published', true)` w HomeController::article().
     */
    public function test_wygaszony_artykul_nie_jest_dostepny_pod_bezposrednim_urlem(): void
    {
        $legacy = $this->article('disaster-recovery-database-systems');

        $this->get('/disaster-recovery-database-systems')->assertStatus(200);

        $this->artisan('articles:retire-legacy --force')->assertSuccessful();

        $this->assertFalse($legacy->fresh()->is_published);
        $this->get('/disaster-recovery-database-systems')->assertStatus(410);
    }

    /**
     * 410 jest zarezerwowane dla świadomego wycofania. Artykuł po prostu jeszcze
     * nieopublikowany (zaplanowany) ma zostać przy 404 – „nie ma", a nie „usunięte
     * na stałe", bo za godzinę tick może go opublikować.
     */
    public function test_niewygaszony_ale_nieopublikowany_artykul_daje_404_a_nie_410(): void
    {
        $this->article('php-enums-complete-guide', published: false);

        $this->get('/php-enums-complete-guide')->assertStatus(404);
    }

    public function test_nieistniejacy_slug_dalej_daje_404(): void
    {
        $this->get('/nie-ma-takiego-artykulu')->assertStatus(404);
    }

    /**
     * Trasa z kategorią (/{categorySlug}/{articleSlug}) to drugi wjazd na ten sam artykuł
     * i miała dokładnie tę samą dziurę. Bez tego testu naprawa jednej trasy wyglądałaby
     * na kompletną.
     */
    public function test_wygaszony_artykul_nie_przechodzi_takze_trasa_z_kategoria(): void
    {
        $legacy = $this->article('master-php-enums-use-cases-tips');

        $this->artisan('articles:retire-legacy --force')->assertSuccessful();
        $this->assertFalse($legacy->fresh()->is_published);

        $this->get('/php/master-php-enums-use-cases-tips')->assertStatus(410);
    }
}
