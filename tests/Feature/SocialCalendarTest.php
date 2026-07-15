<?php

namespace Tests\Feature;

use App\Services\Social\MarkdownSocialPostParser;
use App\Services\Social\Review\SocialCalendar;
use App\Services\Social\SocialPostLinter;
use App\Services\Social\SocialStyleResolver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Kalendarz zaakceptowanych treści.
 *
 * Sedno: jednostką jest para (post × format), a nie post. Jeden plik .md bywa tego
 * samego dnia i postem w feedzie, i reelem – to dwie osobne publikacje.
 */
class SocialCalendarTest extends TestCase
{
    private string $dir;

    /** Flaga musi być przed bootem – routes/web.php rejestruje trasy warunkowo. */
    protected function setUp(): void
    {
        $_SERVER['SOCIAL_PREVIEW'] = 'true';
        $_ENV['SOCIAL_PREVIEW'] = 'true';

        parent::setUp();

        $this->dir = storage_path('framework/testing/social-calendar-' . uniqid());
        File::ensureDirectoryExists($this->dir);

        config([
            'social.path'         => $this->dir,
            'social.reviews_path' => $this->dir . '/reviews',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        unset($_SERVER['SOCIAL_PREVIEW'], $_ENV['SOCIAL_PREVIEW']);

        parent::tearDown();
    }

    /**
     * TO JEST SEDNO: `formats: [post, reel]` to dwie publikacje tego samego dnia,
     * więc dwie pozycje w kalendarzu – jedna nie zastępuje drugiej.
     */
    public function test_one_post_with_two_formats_is_two_entries_on_the_same_day(): void
    {
        $this->writePost('dual', '2026-08-10 09:00', formats: '[post, reel]');
        $this->approve('dual');

        $entries = app(SocialCalendar::class)->approvedByDay()->get('2026-08-10');

        $this->assertCount(2, $entries);
        $this->assertSame(['post', 'reel'], $entries->pluck('format')->sort()->values()->all());
    }

    /**
     * Wiele różnych rodzajów treści jednego dnia – po to ten kalendarz powstał.
     */
    public function test_a_single_day_lists_every_kind_of_content(): void
    {
        $this->writePost('a-post', '2026-08-11 09:00', formats: '[post]');
        $this->writePost('b-reel', '2026-08-11 12:00', formats: '[reel]');
        $this->writePost('c-video', '2026-08-11 17:00', formats: '[video]');
        $this->writePost('d-story', '2026-08-11 20:00', type: 'story', formats: '[story]');

        foreach (['a-post', 'b-reel', 'c-video', 'd-story'] as $slug) {
            $this->approve($slug);
        }

        $entries = app(SocialCalendar::class)->day(\Carbon\CarbonImmutable::parse('2026-08-11'));

        $this->assertCount(4, $entries);
        $this->assertSame(['post', 'reel', 'video', 'story'], $entries->pluck('format')->all(), 'Kolejność ma iść po godzinie publikacji.');
        $this->assertSame(['Post', 'Reel', 'Wideo', 'Story'], $entries->map(fn ($e) => $e->label())->all());
    }

    /**
     * Post „do poprawy” albo nieobejrzany NIE jest zaplanowany – wpisanie go do
     * kalendarza sugerowałoby gotowość, której nie ma.
     */
    public function test_calendar_shows_only_approved_content(): void
    {
        $this->writePost('green', '2026-08-12 09:00');
        $this->writePost('pending', '2026-08-12 10:00');
        $this->writePost('rejected', '2026-08-12 11:00');

        $this->approve('green');
        $this->reject('rejected', 'Za długi hook.');

        $entries = app(SocialCalendar::class)->approvedByDay()->get('2026-08-12');

        $this->assertCount(1, $entries);
        $this->assertSame('green', $entries->first()->post()->slug);
    }

    /**
     * ...ale dziura w planie nie może być niewidzialna: dzień z samymi
     * nieocenionymi postami wyglądałby jak wolny.
     */
    public function test_unsettled_posts_are_counted_so_a_day_does_not_look_free(): void
    {
        $this->writePost('waiting', '2026-08-13 09:00');

        $this->assertSame(1, app(SocialCalendar::class)->unsettledCountByDay()->get('2026-08-13'));

        $this->approve('waiting');

        $this->assertNull(app(SocialCalendar::class)->unsettledCountByDay()->get('2026-08-13'));
    }

    /**
     * Kalendarz ma pokazywać plan, a nie go zmyślać: post bez `publish_at` nie
     * dostaje dnia na siłę.
     */
    public function test_approved_post_without_a_date_lands_in_undated_not_on_today(): void
    {
        $this->writePost('someday', null);
        $this->approve('someday');

        $calendar = app(SocialCalendar::class);

        $this->assertTrue($calendar->approvedByDay()->isEmpty());
        $this->assertCount(1, $calendar->undated());
        $this->assertSame('someday', $calendar->undated()->first()->post()->slug);
    }

    public function test_month_grid_covers_full_weeks_and_marks_foreign_days(): void
    {
        $days = app(SocialCalendar::class)->month(\Carbon\CarbonImmutable::parse('2026-08-01'));

        $this->assertSame(0, count($days) % 7, 'Siatka ma się składać z pełnych tygodni.');
        $this->assertSame('Monday', $days[0]['date']->format('l'), 'Tydzień zaczyna się w poniedziałek.');
        $this->assertFalse($days[0]['inMonth'], '1 sierpnia 2026 to sobota, więc siatka zaczyna się w lipcu.');
    }

    public function test_calendar_page_renders_the_day_panel(): void
    {
        $this->writePost('dual', '2026-08-10 09:00', formats: '[post, reel]');
        $this->approve('dual');

        $this->get('/social/calendar?m=2026-08&day=2026-08-10')
            ->assertOk()
            ->assertSee('dual')
            ->assertSee('Reel')
            ->assertSee('Post');
    }

    /**
     * Ręcznie sklejony link nie ma prawa wywalić panelu.
     */
    public function test_broken_query_params_fall_back_instead_of_failing(): void
    {
        $this->get('/social/calendar?m=nonsense&day=zupelnie-nie-data')->assertOk();
        $this->get('/social/calendar?m=2026-13&day=2026-02-31')->assertOk();
    }

    /**
     * Brak `formats:` => zestaw domyślny z typu. Dzięki temu istniejące posty nie
     * wymagają edycji ani migracji.
     */
    public function test_formats_default_from_the_post_type(): void
    {
        $parser = new MarkdownSocialPostParser();

        $carousel = $parser->toPost("---\ntype: carousel\nslug: c\n---\n\n## A\n\nB.\n\n<!-- slide -->\n\n## C\n\nD.", 'c');
        $story = $parser->toPost("---\ntype: story\nslug: s\n---\n\n## A\n\nB.", 's');

        $this->assertSame(['post'], $carousel->formats);
        $this->assertSame(['story'], $story->formats);
        $this->assertTrue($story->hasFormat('story'));
        $this->assertFalse($story->hasFormat('reel'));
    }

    /**
     * Literówka w `formats:` wypadłaby z kalendarza BEZ ŚLADU – autor byłby
     * przekonany, że zaplanował reela, a dzień świeciłby pustką.
     */
    public function test_unknown_format_is_a_lint_error(): void
    {
        $linter = new SocialPostLinter(new MarkdownSocialPostParser(), new SocialStyleResolver());

        $issues = $linter->lintRaw("---\ntype: quote\nslug: x\nformats: [reels]\ncaption: Hi.\n---\n\n## A\n\nB.", 'x');
        $messages = implode(' ', array_map(fn ($i) => $i->message, array_filter($issues, fn ($i) => $i->isError())));

        $this->assertStringContainsString("Nieznany format 'reels'", $messages);
    }

    public function test_valid_formats_pass_lint(): void
    {
        $linter = new SocialPostLinter(new MarkdownSocialPostParser(), new SocialStyleResolver());

        $issues = $linter->lintRaw(
            "---\ntype: quote\nslug: x\nstatus: ready\nformats: [post, reel]\ncaption: Hi.\n---\n\n## A\n\nB.",
            'x',
        );

        $this->assertSame([], $issues);
    }

    /**
     * W tej klasie flaga JEST włączona (setUp ustawia ją przed bootem), więc trasa
     * musi istnieć. Testu „nie ma jej na produkcji” pilnuje SocialPreviewRouteTest,
     * bo tam flaga jest domyślna.
     */
    public function test_calendar_route_exists_when_the_flag_is_on(): void
    {
        $this->assertTrue(Route::has('social.calendar'));
    }

    private function writePost(string $slug, ?string $publishAt, string $type = 'carousel', ?string $formats = null): void
    {
        $fm = ['---', "type: {$type}", "slug: {$slug}", 'topic: laravel', 'status: ready', 'caption: Podpis.'];

        if ($publishAt !== null) {
            $fm[] = "publish_at: {$publishAt}";
        }

        if ($formats !== null) {
            $fm[] = "formats: {$formats}";
        }

        $fm[] = '---';

        $body = $type === 'story'
            ? "\n\n## Hook\n\nTreść.\n"
            : "\n\n## Hook\n\nTreść.\n\n<!-- slide -->\n\n## Drugi\n\nWięcej.\n";

        File::put($this->dir . "/{$slug}.md", implode("\n", $fm) . $body);
    }

    private function approve(string $slug): void
    {
        $this->call('POST', "/social/review/{$slug}", ['verdict' => 'approved']);
    }

    private function reject(string $slug, string $reason): void
    {
        $this->call('POST', "/social/review/{$slug}", ['verdict' => 'changes', 'reason' => $reason]);
    }
}
