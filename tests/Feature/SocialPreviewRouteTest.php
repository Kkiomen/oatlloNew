<?php

namespace Tests\Feature;

use App\Http\Controllers\SocialPreviewController;
use App\Services\Social\MarkdownSocialPostParser;
use App\Services\Social\MarkdownSocialPostRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Trasy podglądu grafik social media.
 *
 * Najważniejszy jest test negatywny: przy domyślnej konfiguracji (produkcja)
 * tras NIE MA w tablicy routingu w ogóle. To celowo rejestracja warunkowa, a nie
 * middleware – nie ma czego sondować ani źle skonfigurować.
 */
class SocialPreviewRouteTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/social-preview-' . uniqid());
        File::ensureDirectoryExists($this->dir);
        config(['social.path' => $this->dir]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    /**
     * Trasy podglądu nie mogą istnieć na produkcji. `social.preview_enabled`
     * domyślnie jest false (env SOCIAL_PREVIEW), więc routes/web.php ich nie
     * rejestruje.
     */
    public function test_preview_routes_do_not_exist_by_default(): void
    {
        $this->assertFalse(config('social.preview_enabled'), 'Podgląd musi być domyślnie wyłączony.');
        $this->assertFalse(Route::has('social.preview'), 'Trasa podglądu nie może istnieć na produkcji.');
        $this->assertFalse(Route::has('social.slide'));

        // Panel akceptacji siedzi za tą samą flagą – i tym bardziej nie może być
        // publiczny, bo jako jedyny w module zapisuje pliki.
        $this->assertFalse(Route::has('social.review'), 'Panel recenzji nie może istnieć na produkcji.');
        $this->assertFalse(Route::has('social.review.store'));
        $this->assertFalse(Route::has('social.styles'), 'Galeria stylów to narzędzie robocze, nie strona serwisu.');
        $this->assertFalse(Route::has('social.calendar'), 'Kalendarz to narzędzie robocze, nie strona serwisu.');
    }

    /**
     * Trasy podglądu są 3-segmentowe i mają prefiks /social, więc nawet po
     * włączeniu nie kolidują z łapaczami /{articleSlug} i /{categorySlug}/{articleSlug}
     * z końca routes/web.php.
     */
    public function test_enabled_preview_serves_the_slides(): void
    {
        File::put($this->dir . '/demo.md', <<<'MD'
            ---
            type: carousel
            slug: demo
            topic: laravel
            status: ready
            caption: Hi.
            ---

            ## Hook

            Body.

            <!-- slide -->

            ## Second

            More.
            MD);

        $this->registerPreviewRoutes();

        $this->get('/social/demo/slide/1')
            ->assertOk()
            ->assertSee('width: 1080px', false)
            ->assertSee('Hook');

        $this->get('/social/demo/preview')
            ->assertOk()
            ->assertSee('demo')
            ->assertSee('1080x1350');
    }

    public function test_unknown_post_returns_404(): void
    {
        $this->registerPreviewRoutes();

        $this->get('/social/nope/slide/1')->assertNotFound();
    }

    public function test_unknown_slide_index_returns_404(): void
    {
        File::put($this->dir . '/solo.md', "---\ntype: quote\nslug: solo\ncaption: Hi.\n---\n\n## A\n\nBody.");

        $this->registerPreviewRoutes();

        $this->get('/social/solo/slide/9')->assertNotFound();
    }

    /**
     * Odwzorowuje to, co routes/web.php robi przy SOCIAL_PREVIEW=true. Trasy są
     * rejestrowane przy starcie aplikacji, więc samo ustawienie configu w teście
     * niczego by nie dodało.
     */
    private function registerPreviewRoutes(): void
    {
        Route::prefix('social')->group(function () {
            Route::get('/{slug}/preview', [SocialPreviewController::class, 'index'])
                ->where('slug', '[A-Za-z0-9\-]+')
                ->name('social.preview');

            Route::get('/{slug}/slide/{index}', [SocialPreviewController::class, 'slide'])
                ->where(['slug' => '[A-Za-z0-9\-]+', 'index' => '[0-9]+'])
                ->name('social.slide');
        });

        // Trasy dodane po starcie aplikacji nie są w mapie nazw, a widok podglądu
        // woła route('social.slide').
        Route::getRoutes()->refreshNameLookups();

        $this->app->forgetInstance(MarkdownSocialPostRepository::class);
        $this->app->instance(
            MarkdownSocialPostRepository::class,
            new MarkdownSocialPostRepository(new MarkdownSocialPostParser()),
        );
    }
}
