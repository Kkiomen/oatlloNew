<?php

namespace Tests\Feature;

use App\Services\Social\Publish\SocialAutoPublisher;
use App\Services\Social\Publish\SocialMediaStore;
use App\Services\Social\Publish\SocialPublishLog;
use App\Services\Social\Review\SocialReviewRepository;
use App\Services\Social\Review\SocialReviewVerdict;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Automatyczna publikacja na Instagrama przez Zernio (tick z /api/cron).
 *
 * Te testy pilnują rzeczy, których nie da się cofnąć: post opublikowany za
 * wcześnie, opublikowany dwa razy albo opublikowany BEZ zgody człowieka jest
 * już na cudzym profilu. Dlatego prawie każdy przypadek tutaj to "kiedy NIE
 * wysyłamy", a nie "kiedy wysyłamy".
 *
 * Bez RefreshDatabase – moduł social nadal nie ma tabeli, a dziennik wysyłek
 * leży w storage/app (musi przeżyć `git pull` na produkcji).
 */
class SocialAutoPublishTest extends TestCase
{
    private string $dir;

    private string $reviewsDir;

    private string $logDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/social-auto-' . uniqid());
        $this->reviewsDir = $this->dir . '/reviews';
        $this->logDir = storage_path('app/social-published');

        File::ensureDirectoryExists($this->dir);
        File::deleteDirectory($this->logDir);

        Storage::fake('public');

        config([
            'social.path'                       => $this->dir,
            'social.reviews_path'               => $this->reviewsDir,
            'social.media.disk'                 => 'public',
            'social.media.base_url'             => 'https://oatllo.com',
            'social.auto_publish.enabled'       => true,
            'social.auto_publish.grace_minutes' => 180,
            'social.auto_publish.max_per_tick'  => 3,
            'social.zernio.key'                 => 'sk_test',
            'social.zernio.account_id'          => 'acc_123',
            'social.zernio.base_url'            => 'https://zernio.com/api/v1',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        File::deleteDirectory($this->logDir);

        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function writePost(string $slug, string $publishAt, string $formats = '[post]', int $slides = 2): void
    {
        $fm = [
            '---',
            'type: ' . ($slides > 1 ? 'carousel' : 'quote'),
            "slug: {$slug}",
            'topic: laravel',
            'status: ready',
            "formats: {$formats}",
            "publish_at: {$publishAt}",
            'hashtags: [laravel]',
            'caption: Podpis posta.',
            '---',
        ];

        $body = "## Hook\n\nTreść.";

        for ($i = 2; $i <= $slides; $i++) {
            $body .= "\n\n<!-- slide -->\n\n## Slajd {$i}\n\nTreść.";
        }

        File::put($this->dir . "/{$slug}.md", implode("\n", $fm) . "\n\n" . $body . "\n");
    }

    private function approve(string $slug): void
    {
        $repo = app(SocialReviewRepository::class);
        $raw = (string) File::get($this->dir . "/{$slug}.md");

        $repo->save(new \App\Services\Social\Review\SocialReview(
            slug: $slug,
            verdict: SocialReviewVerdict::Approved,
            reason: '',
            reviewedAt: CarbonImmutable::now(),
            fingerprint: SocialReviewRepository::fingerprint($raw),
        ));
    }

    private function putMedia(string $slug, int $slides = 2): void
    {
        $store = app(SocialMediaStore::class);

        for ($i = 1; $i <= $slides; $i++) {
            $store->put($slug, $store->fileName($i, 'png'), 'png-bytes');
        }
    }

    private function publisher(): SocialAutoPublisher
    {
        return app(SocialAutoPublisher::class);
    }

    private function fakeZernioOk(): void
    {
        Http::fake([
            // Ksztalt przepisany z PRAWDZIWEJ odpowiedzi: `_id`, nie `id`,
            // i status `publishing` -- Zernio wypycha asynchronicznie.
            'zernio.com/*' => Http::response(['_id' => 'post_abc', 'status' => 'publishing'], 200),
        ]);
    }

    // ------------------------------------------------------------------ tests

    public function test_publishes_an_approved_post_whose_time_has_come(): void
    {
        $this->fakeZernioOk();
        $this->writePost('demo', '2026-07-16 19:00');
        $this->approve('demo');
        $this->putMedia('demo');

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));

        $this->assertSame('ran', $report['state']);
        $this->assertSame(1, $report['published_count']);
        $this->assertSame('post_abc', $report['published'][0]['zernio_id']);
        $this->assertSame(SocialPublishLog::SENT, $report['published'][0]['status']);

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            $this->assertSame('https://zernio.com/api/v1/posts', $request->url());
            $this->assertTrue($body['publishNow']);
            $this->assertStringContainsString('Podpis posta.', $body['content']);
            $this->assertSame('instagram', $body['platforms'][0]['platform']);
            $this->assertSame('acc_123', $body['platforms'][0]['accountId']);

            // Karuzela = kilka mediaItems z PUBLICZNYMI URL-ami naszego hostingu.
            $this->assertCount(2, $body['mediaItems']);
            $this->assertSame('image', $body['mediaItems'][0]['type']);
            $this->assertSame('https://oatllo.com/storage/social/demo/01.png', $body['mediaItems'][0]['url']);
            $this->assertSame('https://oatllo.com/storage/social/demo/02.png', $body['mediaItems'][1]['url']);

            return true;
        });
    }

    /**
     * Automat nie ma prawa wypuścić czegoś, czego człowiek nie zatwierdził
     * w TEJ wersji. To ta sama zasada, na której stoi kalendarz.
     */
    public function test_never_publishes_a_post_without_an_approved_verdict(): void
    {
        $this->fakeZernioOk();
        $this->writePost('demo', '2026-07-16 19:00');
        $this->putMedia('demo');
        // brak approve()

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));

        $this->assertSame(0, $report['published_count']);
        Http::assertNothingSent();
    }

    /**
     * Poprawka posta rozjeżdża fingerprint => werdykt przestaje pasować do treści.
     * Automat musi wtedy zamilknąć, inaczej "zaakceptowane" znaczyłoby
     * "zaakceptowane kiedyś, w nieznanej wersji".
     */
    public function test_never_publishes_a_post_edited_after_approval(): void
    {
        $this->fakeZernioOk();
        $this->writePost('demo', '2026-07-16 19:00');
        $this->approve('demo');
        $this->putMedia('demo');

        $this->writePost('demo', '2026-07-16 19:00', '[post]', 3); // treść zmieniona po werdykcie

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));

        $this->assertSame(0, $report['published_count']);
        Http::assertNothingSent();
    }

    public function test_does_not_publish_before_the_scheduled_time(): void
    {
        $this->fakeZernioOk();
        $this->writePost('demo', '2026-07-16 19:00');
        $this->approve('demo');
        $this->putMedia('demo');

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 18:59'));

        $this->assertSame(0, $report['published_count']);
        Http::assertNothingSent();
    }

    /**
     * Podwójny post jest gorszy niż spóźniony: nie da się go cofnąć, a followers
     * już go widzieli.
     */
    public function test_never_publishes_the_same_pair_twice(): void
    {
        $this->fakeZernioOk();
        $this->writePost('demo', '2026-07-16 19:00');
        $this->approve('demo');
        $this->putMedia('demo');

        $first = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));
        $second = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:45'));

        $this->assertSame(1, $first['published_count']);
        $this->assertSame(0, $second['published_count']);

        // Liczymy POSTY, nie wszystkie żądania: drugi tick MA prawo zadzwonić –
        // dopytuje GET-em o potwierdzenie wysyłki, bo Zernio publikuje
        // asynchronicznie. Zakazany jest wyłącznie DRUGI POST, bo to on zrobiłby
        // dubla na profilu.
        $posts = 0;

        Http::assertSent(function (Request $request) use (&$posts) {
            if ($request->method() === 'POST') {
                $posts++;
            }

            return true;
        });

        $this->assertSame(1, $posts, 'Drugi POST = dubel na profilu, którego nie da się cofnąć.');
    }

    /**
     * Karuzela z `formats: [post, reel]` to DWIE publikacje, więc wysłanie
     * jednej nie może zdejmować drugiej z kolejki.
     */
    public function test_post_and_reel_are_two_separate_publications(): void
    {
        $this->fakeZernioOk();
        $this->writePost('demo', '2026-07-16 19:00', '[post, reel]');
        $this->approve('demo');
        $this->putMedia('demo');

        $store = app(SocialMediaStore::class);
        $store->put('demo', 'reel.mp4', 'mp4-bytes');

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));

        $this->assertSame(2, $report['published_count']);

        $sentTypes = [];

        Http::assertSent(function (Request $request) use (&$sentTypes) {
            $sentTypes[] = $request->data()['platforms'][0]['platformSpecificData']['contentType'] ?? 'feed';

            return true;
        });

        sort($sentTypes);
        $this->assertSame(['feed', 'reels'], $sentTypes);
    }

    public function test_reel_is_sent_as_a_video_and_shared_to_feed(): void
    {
        $this->fakeZernioOk();
        $this->writePost('demo', '2026-07-16 19:00', '[reel]');
        $this->approve('demo');

        app(SocialMediaStore::class)->put('demo', 'reel.mp4', 'mp4-bytes');

        $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            $this->assertSame('video', $body['mediaItems'][0]['type']);
            $this->assertSame('https://oatllo.com/storage/social/demo/reel.mp4', $body['mediaItems'][0]['url']);
            $this->assertSame('reels', $body['platforms'][0]['platformSpecificData']['contentType']);
            $this->assertTrue($body['platforms'][0]['platformSpecificData']['shareToFeed']);

            return true;
        });
    }

    /**
     * Produkcja nie renderuje grafik – dostaje je z `social:push`. Brak plików to
     * ZWYKŁY STAN (nie wgrałeś paczki), więc ma być zgłoszony, a nie wybuchnąć.
     */
    public function test_missing_media_is_reported_not_published(): void
    {
        $this->fakeZernioOk();
        $this->writePost('demo', '2026-07-16 19:00');
        $this->approve('demo');
        // brak putMedia()

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));

        $this->assertSame(0, $report['published_count']);
        $this->assertCount(1, $report['skipped']);
        $this->assertStringContainsString('brak grafik', $report['skipped'][0]['reason']);
        $this->assertStringContainsString('social:push demo', $report['skipped'][0]['reason']);
        Http::assertNothingSent();
    }

    /**
     * Post przegapiony przez kilkudniową awarię nie ma wyjść po naprawie o 4 rano.
     */
    public function test_a_long_overdue_post_is_skipped_loudly(): void
    {
        $this->fakeZernioOk();
        $this->writePost('demo', '2026-07-10 19:00');
        $this->approve('demo');
        $this->putMedia('demo');

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 04:00'));

        $this->assertSame(0, $report['published_count']);
        $this->assertStringContainsString('termin minął', $report['skipped'][0]['reason']);
        Http::assertNothingSent();
    }

    /**
     * Zernio ODPOWIEDZIAŁO błędem => wiemy, że nic nie poszło w świat, więc
     * wolno ponowić w następnym ticku.
     */
    public function test_a_rejected_post_is_retried_on_the_next_tick(): void
    {
        // Licznik, a nie dwa Http::fake() po sobie: kolejne fake() DOKŁADA stub,
        // nie podmienia go, więc pierwszy nadal by wygrywał i "ponowienie"
        // dostałoby to samo 429.
        $calls = 0;

        Http::fake(function () use (&$calls) {
            $calls++;

            return $calls === 1
                ? Http::response(['message' => 'rate limited'], 429)
                : Http::response(['_id' => 'post_abc'], 200);
        });

        $this->writePost('demo', '2026-07-16 19:00');
        $this->approve('demo');
        $this->putMedia('demo');

        $first = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:10'));

        $this->assertSame(0, $first['published_count']);
        $this->assertSame(SocialPublishLog::FAILED, $first['failed'][0]['status']);

        $second = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:20'));

        $this->assertSame(1, $second['published_count']);
        $this->assertSame(2, $calls, 'Drugi tick musi naprawdę spróbować jeszcze raz.');
    }

    /**
     * NAJWAŻNIEJSZY przypadek. Timeout nie znaczy "nie poszło": żądanie mogło
     * dojść i się wykonać, a urwać się dopiero odpowiedź. Ponowienie zrobiłoby
     * DUBLA na profilu, więc para zostaje zablokowana do decyzji człowieka.
     */
    public function test_a_timeout_blocks_the_pair_instead_of_retrying(): void
    {
        // Licznik jest tu JEDYNYM prawdziwym dowodem. `assertNothingSent` by nie
        // wystarczyło: żądanie, które rzuciło wyjątek, i tak nie trafia do
        // rejestru, więc asercja przeszłaby nawet gdyby tick zadzwonił po raz
        // drugi i znów dostał timeout – czyli dokładnie w sytuacji, przed którą
        // ten test ma chronić.
        $calls = 0;

        Http::fake(function () use (&$calls) {
            $calls++;

            if ($calls === 1) {
                throw new \Illuminate\Http\Client\ConnectionException('timeout');
            }

            return Http::response(['_id' => 'post_abc'], 200);
        });

        $this->writePost('demo', '2026-07-16 19:00');
        $this->approve('demo');
        $this->putMedia('demo');

        $first = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:10'));

        $this->assertSame(SocialPublishLog::UNKNOWN, $first['failed'][0]['status']);
        $this->assertSame(1, $calls);

        // Kolejny tick MUSI milczeć, mimo że Zernio już odpowiada: post mógł wyjść.
        $second = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:20'));

        $this->assertSame(0, $second['published_count']);
        $this->assertSame(1, $calls, 'Po timeoucie tick nie ma prawa zadzwonić drugi raz – to byłby dubel.');
    }

    /**
     * "Przyjęte przez Zernio" to NIE "jest na Instagramie". Ich API odpowiada od
     * razu, a wypycha asynchronicznie: nasz pierwszy prawdziwy post miał w chwili
     * odpowiedzi `publishing`, a `published` dopiero po chwili. Dlatego wysyłka
     * zapisuje `sent`, a dopiero potwierdzenie robi z tego `published`.
     */
    public function test_sent_becomes_published_only_after_zernio_confirms(): void
    {
        $calls = 0;

        Http::fake(function () use (&$calls) {
            $calls++;

            // 1. POST /posts -> przyjete, ale jeszcze w drodze
            // 2. GET /posts/{id} (tick nr 2) -> nadal w drodze
            // 3. GET /posts/{id} (tick nr 3) -> potwierdzone
            if ($calls === 1) {
                return Http::response(['_id' => 'post_abc', 'status' => 'publishing'], 200);
            }

            return Http::response([
                'status'    => $calls >= 3 ? 'published' : 'publishing',
                'platforms' => [['platform' => 'instagram', 'status' => $calls >= 3 ? 'published' : 'processing']],
            ], 200);
        });

        $this->writePost('demo', '2026-07-16 19:00');
        $this->approve('demo');
        $this->putMedia('demo');

        $log = app(SocialPublishLog::class);

        $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));
        $this->assertSame(SocialPublishLog::SENT, $log->get('demo', 'post')['status']);
        $this->assertSame('post_abc', $log->get('demo', 'post')['zernio_id']);

        $this->publisher()->run(CarbonImmutable::parse('2026-07-16 20:30'));
        $this->assertSame(SocialPublishLog::SENT, $log->get('demo', 'post')['status'], 'Jeszcze w drodze – nie wolno ogłaszać sukcesu.');

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 21:30'));

        $this->assertSame(SocialPublishLog::PUBLISHED, $log->get('demo', 'post')['status']);
        $this->assertSame([['slug' => 'demo', 'format' => 'post', 'status' => 'published']], $report['confirmed']);
    }

    /**
     * Porażka PO przyjęciu (wygasły token, odrzucone media) byłaby bez tego
     * NIEWIDZIALNA: dziennik mówiłby "poszło", a profil świeciłby pustką.
     * Nie ponawiamy automatycznie – `partial` znaczy, że część mogła wyjść.
     */
    public function test_a_failure_after_acceptance_is_surfaced_not_retried(): void
    {
        $calls = 0;

        Http::fake(function () use (&$calls) {
            $calls++;

            if ($calls === 1) {
                return Http::response(['_id' => 'post_abc', 'status' => 'publishing'], 200);
            }

            return Http::response([
                'status'    => 'failed',
                'platforms' => [['platform' => 'instagram', 'status' => 'failed', 'error' => 'token expired']],
            ], 200);
        });

        $this->writePost('demo', '2026-07-16 19:00');
        $this->approve('demo');
        $this->putMedia('demo');

        $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));
        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 20:30'));

        $log = app(SocialPublishLog::class);

        $this->assertSame(SocialPublishLog::UNKNOWN, $log->get('demo', 'post')['status']);
        $this->assertSame('failed', $report['confirmed'][0]['status']);

        // I nigdy nie próbuje wysłać drugi raz.
        $posts = 0;
        Http::assertSent(function (Request $request) use (&$posts) {
            if ($request->method() === 'POST') {
                $posts++;
            }

            return true;
        });
        $this->assertSame(1, $posts);
    }

    public function test_disabled_by_default_never_touches_instagram(): void
    {
        $this->fakeZernioOk();
        config(['social.auto_publish.enabled' => false]);

        $this->writePost('demo', '2026-07-16 19:00');
        $this->approve('demo');
        $this->putMedia('demo');

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));

        $this->assertSame('disabled', $report['state']);
        Http::assertNothingSent();
    }

    /**
     * Bezpiecznik na paczkę wrzuconą z przeszłymi datami: bez limitu jeden tick
     * wyplułby na profil wszystko naraz.
     */
    public function test_per_tick_limit_caps_the_burst_and_reports_the_rest(): void
    {
        $this->fakeZernioOk();
        config(['social.auto_publish.max_per_tick' => 2]);

        foreach (['a', 'b', 'c'] as $i => $slug) {
            $this->writePost($slug, '2026-07-16 1' . (7 + $i) . ':00');
            $this->approve($slug);
            $this->putMedia($slug);
        }

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));

        $this->assertSame(2, $report['published_count']);
        $this->assertCount(1, $report['skipped']);
        $this->assertStringContainsString('limit 2 na tick', $report['skipped'][0]['reason']);

        // FIFO: najstarsze idą pierwsze, żeby zaległy post nie czekał w nieskończoność.
        $this->assertSame(['a', 'b'], array_column($report['published'], 'slug'));
        $this->assertSame('c', $report['skipped'][0]['slug']);
    }

    /**
     * `video` to etykieta na materiał nagrywany poza modułem – nie ma pliku,
     * więc nie ma czego wysłać.
     */
    public function test_video_format_is_not_published_by_the_module(): void
    {
        $this->fakeZernioOk();
        $this->writePost('demo', '2026-07-16 19:00', '[video]');
        $this->approve('demo');
        $this->putMedia('demo');

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));

        $this->assertSame(0, $report['published_count']);
        Http::assertNothingSent();
    }

    public function test_post_without_a_date_is_never_published_on_its_own(): void
    {
        $this->fakeZernioOk();

        File::put($this->dir . '/demo.md', "---\ntype: quote\nslug: demo\nstatus: ready\ncaption: Hi.\n---\n\n## A\n\nB.\n");
        $this->approve('demo');
        $this->putMedia('demo', 1);

        $report = $this->publisher()->run(CarbonImmutable::parse('2026-07-16 19:30'));

        $this->assertSame(0, $report['published_count']);
        Http::assertNothingSent();
    }
}
