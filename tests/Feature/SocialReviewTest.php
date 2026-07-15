<?php

namespace Tests\Feature;

use App\Services\Social\Review\SocialReviewQueue;
use App\Services\Social\Review\SocialReviewRepository;
use App\Services\Social\Review\SocialReviewVerdict;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Panel akceptacji postów social media.
 *
 * Sedno modułu to dwie rzeczy: (1) w kolejce jest tylko to, czego człowiek nie
 * osądził, (2) poprawka posta unieważnia werdykt i wraca do kolejki. Reszta to
 * dodatki.
 */
class SocialReviewTest extends TestCase
{
    private string $dir;

    private string $reviewsDir;

    /**
     * Flaga MUSI być ustawiona przed bootem aplikacji: routes/web.php rejestruje
     * trasy panelu warunkowo, przy ładowaniu pliku tras. Ustawienie configu po
     * starcie niczego by nie dodało.
     *
     * Dzięki temu test przechodzi PRAWDZIWĄ ścieżką routingu, łącznie z tym, że
     * 2-segmentowe /social/review wyprzedza łapacz /{categorySlug}/{articleSlug}.
     */
    protected function setUp(): void
    {
        $_SERVER['SOCIAL_PREVIEW'] = 'true';
        $_ENV['SOCIAL_PREVIEW'] = 'true';

        parent::setUp();

        $this->dir = storage_path('framework/testing/social-review-' . uniqid());
        $this->reviewsDir = $this->dir . '/reviews';

        File::ensureDirectoryExists($this->dir);

        config([
            'social.path'          => $this->dir,
            'social.reviews_path'  => $this->reviewsDir,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        unset($_SERVER['SOCIAL_PREVIEW'], $_ENV['SOCIAL_PREVIEW']);

        parent::tearDown();
    }

    /**
     * Panel ma 2 segmenty, więc mieści się we wzorcu łapacza artykułów. Wygrywa,
     * bo jest zdefiniowany wcześniej – gdyby ktoś przeniósł blok tras social na
     * koniec routes/web.php, panel zacząłby szukać artykułu o slugu "review".
     */
    public function test_review_route_wins_over_the_article_catch_all(): void
    {
        $this->assertTrue(Route::has('social.review'));

        $this->get('/social/review')->assertOk()->assertDontSee('articles');
    }

    public function test_panel_shows_the_first_unreviewed_post(): void
    {
        $this->writePost('early', '2026-07-20 09:00');
        $this->writePost('late', '2026-08-01 09:00');

        $this->get('/social/review')
            ->assertOk()
            ->assertSee('early')            // najbliższy publish_at idzie pierwszy
            ->assertSee('Do poprawy')
            ->assertSee('OK, nadaje się');
    }

    /**
     * Galeria pokazuje ten sam slajd we WSZYSTKICH skórkach – `?style=` na trasie
     * slajdu wymusza skórkę, inaczej każdy kafelek renderowałby to samo (styl
     * dobrany automatem).
     */
    public function test_style_gallery_renders_every_skin_of_the_pack(): void
    {
        $this->writePost('demo');

        $response = $this->get('/social/demo/styles')->assertOk();

        foreach (app(\App\Services\Social\SocialStyleResolver::class)->names() as $style) {
            $response->assertSee('style=' . $style, false);
        }

        // Kafelek naprawdę dostaje wymuszoną skórkę, a nie tę z automatu.
        $this->get('/social/demo/slide/1?style=brutalist')->assertOk()->assertSee('style-brutalist');
        $this->get('/social/demo/slide/1?style=neon')->assertOk()->assertSee('style-neon');
    }

    /**
     * Bzdura w `?style=` nie może wywalić widoku – SocialImageService ignoruje
     * nieznaną skórkę i wraca do automatu.
     */
    public function test_unknown_style_in_the_query_falls_back_instead_of_failing(): void
    {
        $this->writePost('demo');

        $this->get('/social/demo/slide/1?style=vaporwave')->assertOk()->assertSee('style-');
    }

    /**
     * Kursor `?i=N` pozwala obejrzeć kolejny post BEZ wydawania werdyktu –
     * werdykt zdejmuje post z kolejki, więc bez kursora nie dałoby się niczego
     * odłożyć na później.
     */
    public function test_cursor_walks_the_queue_without_judging(): void
    {
        $this->writePost('early', '2026-07-20 09:00');
        $this->writePost('late', '2026-08-01 09:00');

        $this->get('/social/review')->assertOk()->assertSee('early')->assertDontSee('late');
        $this->get('/social/review?i=1')->assertOk()->assertSee('late');

        // Samo oglądanie niczego nie osądza.
        $this->assertCount(2, $this->queue()->pending());
        $this->assertNull($this->reviews()->find('late'));
    }

    /**
     * Kursor poza zakresem nie może wywalić panelu ani pokazać pustki – kolejka
     * kurczy się przy każdym werdykcie, więc stary link zawsze może być za daleko.
     */
    public function test_cursor_out_of_range_is_clamped(): void
    {
        $this->writePost('only');

        $this->get('/social/review?i=99')->assertOk()->assertSee('only');
        $this->get('/social/review?i=-5')->assertOk()->assertSee('only');
        $this->get('/social/review?i=abc')->assertOk()->assertSee('only');
    }

    /**
     * Zielony werdykt zapisuje plik .md i zdejmuje post z kolejki.
     */
    public function test_approving_writes_a_review_file_and_removes_the_post_from_the_queue(): void
    {
        $this->writePost('demo');

        $this->from('/social/review')
            ->call('POST', '/social/review/demo', ['verdict' => 'approved'])
            ->assertRedirect('/social/review');

        $review = $this->reviews()->find('demo');

        $this->assertNotNull($review);
        $this->assertSame(SocialReviewVerdict::Approved, $review->verdict);
        $this->assertTrue($this->queue()->pending()->isEmpty(), 'Osądzony post znika z kolejki.');
    }

    /**
     * Powód poprawki ląduje w CIELE pliku – wielolinijkowy tekst od człowieka nie
     * przechodzi wtedy przez escaping YAML-a.
     */
    public function test_rejecting_stores_the_reason_in_the_file_body(): void
    {
        $this->writePost('demo');

        $this->call('POST', '/social/review/demo', [
            'verdict' => 'changes',
            'reason'  => "Slajd 3 ma za dużo tekstu.\nHook nie zatrzymuje: \"cudzysłów\" i - myślnik.",
        ])->assertRedirect('/social/review');

        $review = $this->reviews()->find('demo');

        $this->assertSame(SocialReviewVerdict::Changes, $review->verdict);
        $this->assertStringContainsString('Slajd 3 ma za dużo tekstu.', $review->reason);
        $this->assertStringContainsString('"cudzysłów"', $review->reason);
        $this->assertCount(1, $this->queue()->needingWork());
    }

    /**
     * Recenzja bez powodu jest bezużyteczna dla tego, kto ma post poprawić.
     */
    public function test_rejecting_without_a_reason_is_refused(): void
    {
        $this->writePost('demo');

        $this->from('/social/review')
            ->call('POST', '/social/review/demo', ['verdict' => 'changes', 'reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertNull($this->reviews()->find('demo'), 'Nie zapisujemy pustej recenzji.');
    }

    /**
     * TO JEST SEDNO: werdykt dotyczy KONKRETNEJ wersji pliku. Po poprawce posta
     * odcisk się rozjeżdża i post wraca do kolejki – zarówno zielony (trzeba
     * zaakceptować nową wersję), jak i czerwony (poprawka domaga się oceny).
     */
    public function test_editing_a_reviewed_post_puts_it_back_in_the_queue(): void
    {
        $this->writePost('demo');

        $this->call('POST', '/social/review/demo', ['verdict' => 'approved']);
        $this->assertTrue($this->queue()->pending()->isEmpty());

        $this->writePost('demo', body: 'Zupełnie inna treść po poprawce.');

        $item = $this->queue()->find('demo');

        $this->assertTrue($item->isStale(), 'Zmieniony post ma nieaktualny werdykt.');
        $this->assertFalse($item->isApproved(), 'Akceptacja starej wersji nie przechodzi na nową.');
        $this->assertCount(1, $this->queue()->pending());
    }

    /**
     * Opublikowane wiszą już na Instagramie – nie ma czego akceptować.
     */
    public function test_published_posts_are_not_in_the_queue(): void
    {
        $this->writePost('gone', status: 'published');

        $this->assertTrue($this->queue()->pending()->isEmpty());
    }

    /**
     * Katalog recenzji leży wewnątrz resources/social, ale repozytorium postów
     * czyta swój katalog płasko – recenzje nie mogą udawać postów.
     */
    public function test_review_files_are_not_mistaken_for_posts(): void
    {
        $this->writePost('demo');

        $this->call('POST', '/social/review/demo', ['verdict' => 'approved']);

        $this->assertCount(1, $this->queue()->items(), 'Plik recenzji nie jest postem.');
    }

    /**
     * Uszkodzony plik nie może wywalić panelu – od zgłaszania błędów jest lint.
     */
    public function test_a_broken_post_file_is_skipped_not_fatal(): void
    {
        $this->writePost('ok');
        File::put($this->dir . '/broken.md', "---\ntype: nonsense\n---\n\n## X\n\nY.");


        $this->get('/social/review')->assertOk()->assertSee('ok');
        $this->assertSame(1, $this->queue()->brokenCount());
    }

    public function test_empty_queue_shows_the_summary(): void
    {
        $this->writePost('demo');

        $this->call('POST', '/social/review/demo', ['verdict' => 'approved']);

        $this->get('/social/review')
            ->assertOk()
            ->assertSee('Kolejka pusta');
    }

    private function writePost(string $slug, ?string $publishAt = null, string $status = 'ready', string $body = 'Treść slajdu.'): void
    {
        $fm = ["---", "type: carousel", "slug: {$slug}", "topic: laravel", "status: {$status}", "caption: Podpis."];

        if ($publishAt !== null) {
            $fm[] = "publish_at: {$publishAt}";
        }

        $fm[] = '---';

        File::put($this->dir . "/{$slug}.md", implode("\n", $fm) . "\n\n## Hook\n\n{$body}\n\n<!-- slide -->\n\n## Drugi\n\nWięcej.\n");
    }

    private function queue(): SocialReviewQueue
    {
        return app(SocialReviewQueue::class);
    }

    private function reviews(): SocialReviewRepository
    {
        return app(SocialReviewRepository::class);
    }

}
