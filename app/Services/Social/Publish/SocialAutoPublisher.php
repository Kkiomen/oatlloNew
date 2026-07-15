<?php

namespace App\Services\Social\Publish;

use App\Services\Social\Review\SocialReviewItem;
use App\Services\Social\Review\SocialReviewQueue;
use App\Services\Social\SocialPost;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Tick autopublikacji: pyta PLIKI, co dojrzało, i wysyła to przez Zernio.
 *
 * Kręgosłup decyzji:
 *
 * 1. **Terminy żyją w `.md`, nie w Zernio.** Zernio ma własny scheduler, ale
 *    wtedy plan istniałby w dwóch miejscach i zmiana `publish_at:` wymagałaby
 *    synchronizacji ze stanem u nich. Tick + `publishNow` zostawia plik jedynym
 *    źródłem prawdy – zmiana terminu to commit, jak wszystko inne w tym repo.
 *
 * 2. **Publikujemy WYŁĄCZNIE zaakceptowane** (zielony werdykt pasujący do
 *    aktualnej treści) – dokładnie to, co pokazuje kalendarz. Post „do poprawy"
 *    albo nieoceniony nie jest zaplanowany, tylko w robocie, a automat nie ma
 *    prawa wypuścić czegoś, czego człowiek nie widział w tej wersji.
 *
 * 3. **Jednostką jest para (post × format)**, jak w kalendarzu: karuzela
 *    z `formats: [post, reel]` to dwie osobne publikacje.
 *
 * 4. **Podwójny post jest gorszy niż spóźniony.** Stąd dziennik wysyłek,
 *    `max_per_tick` i traktowanie timeoutu jako `unknown` (nie ponawiamy).
 */
class SocialAutoPublisher
{
    public function __construct(
        private readonly SocialReviewQueue $queue,
        private readonly SocialMediaStore $media,
        private readonly ZernioClient $zernio,
        private readonly SocialPublishLog $log,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();

        if (! (bool) config('social.auto_publish.enabled')) {
            return $this->report('disabled');
        }

        if (! $this->zernio->configured()) {
            return $this->report('not_configured');
        }

        // Jeden tick naraz. Dwa nakładające się (n8n retry, ktoś ciekawski z
        // curlem) czytałyby ten sam dziennik, zanim pierwszy zdąży cokolwiek
        // zapisać – i ten sam post poszedłby dwa razy. Zamek jest NIEBLOKUJĄCY:
        // drugi tick ma odpaść, a nie czekać i wysłać wszystko z opóźnieniem.
        $lock = $this->acquireLock();

        if ($lock === null) {
            return $this->report('busy');
        }

        try {
            return $this->publishDue($now);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function publishDue(CarbonImmutable $now): array
    {
        $due = $this->due($now);
        $skipped = $due['skipped'];
        $candidates = $due['candidates'];

        $max = max(0, (int) config('social.auto_publish.max_per_tick', 3));
        $overflow = array_slice($candidates, $max);
        $candidates = array_slice($candidates, 0, $max);

        $published = [];
        $failed = [];

        foreach ($candidates as $candidate) {
            /** @var SocialPost $post */
            $post = $candidate['post'];
            $format = $candidate['format'];

            $result = $this->publishOne($post, $format);

            if ($result['status'] === SocialPublishLog::PUBLISHED) {
                $published[] = $result;
            } else {
                $failed[] = $result;
            }
        }

        // Nadmiar NIE jest cicho gubiony: bez tego dzień z pięcioma zaległymi
        // postami wyglądałby w odpowiedzi tak samo jak dzień z trzema.
        foreach ($overflow as $candidate) {
            $skipped[] = [
                'slug'   => $candidate['post']->slug,
                'format' => $candidate['format'],
                'reason' => 'limit ' . $max . ' na tick – pójdzie w następnym',
            ];
        }

        return $this->report('ran', $published, $failed, $skipped);
    }

    /**
     * Co dojrzało do wysyłki.
     *
     * @return array{candidates: list<array{post: SocialPost, format: string, at: CarbonImmutable}>, skipped: list<array<string,string>>}
     */
    private function due(CarbonImmutable $now): array
    {
        $formats = (array) config('social.auto_publish.formats', []);
        $grace = (int) config('social.auto_publish.grace_minutes', 180);
        $maxAttempts = 3;

        $candidates = [];
        $skipped = [];

        /** @var SocialReviewItem $item */
        foreach ($this->queue->approved() as $item) {
            $post = $item->post;

            // Post bez terminu nie dostaje dnia na siłę – kalendarz go nie zmyśla
            // i automat też nie może. Trafia do sekcji "Bez terminu" i czeka.
            if ($post->publishAt === null) {
                continue;
            }

            $at = CarbonImmutable::parse($post->publishAt);

            foreach ($post->formats as $format) {
                // `video` to etykieta na materiał nagrywany poza modułem – nie ma
                // pliku, więc nie ma czego wysłać.
                if (! in_array($format, $formats, true)) {
                    continue;
                }

                if ($at->greaterThan($now)) {
                    continue; // jeszcze nie czas
                }

                if (! $this->log->shouldAttempt($post->slug, $format, $maxAttempts)) {
                    continue; // już poszło albo czeka na człowieka
                }

                // Okno spóźnienia: tick leci co godzinę, więc post na 19:00 wyjdzie
                // ok. 19:00-19:59. Bez okna post przegapiony przez kilkudniową awarię
                // wyszedłby po naprawie o losowej porze – lepiej go pominąć głośno.
                if ($at->lessThan($now->subMinutes($grace))) {
                    $skipped[] = [
                        'slug'   => $post->slug,
                        'format' => $format,
                        'reason' => 'termin minął o ponad ' . $grace . ' min (' . $at->toIso8601String() . ') – wypuść ręcznie',
                    ];

                    continue;
                }

                $missing = $this->media->missingFor($post, $format);

                if ($missing !== []) {
                    $skipped[] = [
                        'slug'   => $post->slug,
                        'format' => $format,
                        'reason' => 'brak grafik na serwerze: ' . implode(', ', $missing) . ' – odpal `social:push ' . $post->slug . '`',
                    ];

                    continue;
                }

                $candidates[] = ['post' => $post, 'format' => $format, 'at' => $at];
            }
        }

        // Najstarsze idą pierwsze: przy limicie na tick kolejka ma być FIFO,
        // inaczej zaległy post mógłby przepuszczać przed siebie świeższe w kółko.
        usort($candidates, fn (array $a, array $b) => $a['at']->getTimestamp() <=> $b['at']->getTimestamp());

        return ['candidates' => $candidates, 'skipped' => $skipped];
    }

    /**
     * @return array<string, mixed>
     */
    private function publishOne(SocialPost $post, string $format): array
    {
        $items = array_map(
            fn (string $url) => ['type' => $this->media->mediaType($url), 'url' => $url],
            $this->media->urlsFor($post, $format),
        );

        try {
            $response = $this->zernio->publishNow(
                $post->captionWithHashtags(),
                $items,
                $this->contentType($format),
            );

            $remoteId = $response['id'] ?? $response['data']['id'] ?? null;

            $this->log->record($post->slug, $format, SocialPublishLog::PUBLISHED, [
                'zernio_id'    => $remoteId,
                'published_at' => CarbonImmutable::now()->toIso8601String(),
                'media'        => array_column($items, 'url'),
            ]);

            return [
                'slug'      => $post->slug,
                'format'    => $format,
                'status'    => SocialPublishLog::PUBLISHED,
                'zernio_id' => $remoteId,
            ];
        } catch (RequestException $e) {
            // Zernio ODPOWIEDZIAŁO błędem – wiemy, że nic nie poszło w świat,
            // więc wolno ponawiać w kolejnych tickach (do limitu prób).
            $message = 'HTTP ' . $e->response->status() . ': ' . mb_substr($e->response->body(), 0, 300);

            $this->log->record($post->slug, $format, SocialPublishLog::FAILED, ['error' => $message]);
            Log::warning("Social: publikacja {$post->slug} [{$format}] odrzucona – {$message}");

            return ['slug' => $post->slug, 'format' => $format, 'status' => SocialPublishLog::FAILED, 'error' => $message];
        } catch (ConnectionException $e) {
            // NIE WIEMY, czy post wyszedł: żądanie mogło dojść i zdążyć się
            // wykonać, a urwać się dopiero odpowiedź. Ponowienie ryzykuje
            // DUBLA na profilu, więc para zostaje zablokowana do decyzji człowieka.
            $message = 'brak odpowiedzi (' . mb_substr($e->getMessage(), 0, 200) . ')';

            $this->log->record($post->slug, $format, SocialPublishLog::UNKNOWN, ['error' => $message]);
            Log::error(
                "Social: publikacja {$post->slug} [{$format}] – BRAK ODPOWIEDZI. Post mógł wyjść. "
                . 'Sprawdź profil; żeby wysłać ponownie, skasuj ' . $this->log->path($post->slug, $format)
            );

            return ['slug' => $post->slug, 'format' => $format, 'status' => SocialPublishLog::UNKNOWN, 'error' => $message];
        }
    }

    /**
     * Format Oatllo -> `platformSpecificData.contentType` Zernio.
     * `post` nie ma contentType: to domyślny wpis w feedzie (karuzela, gdy
     * mediaItems jest więcej).
     */
    private function contentType(string $format): ?string
    {
        return match ($format) {
            'story' => 'story',
            'reel'  => 'reels',
            default => null,
        };
    }

    /**
     * @return resource|null
     */
    private function acquireLock()
    {
        $dir = storage_path('app/social-published');
        File::ensureDirectoryExists($dir);

        $handle = @fopen($dir . DIRECTORY_SEPARATOR . 'tick.lock', 'cb');

        if ($handle === false) {
            return null;
        }

        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return null;
        }

        return $handle;
    }

    /**
     * @param  list<array<string, mixed>>  $published
     * @param  list<array<string, mixed>>  $failed
     * @param  list<array<string, mixed>>  $skipped
     * @return array<string, mixed>
     */
    private function report(string $state, array $published = [], array $failed = [], array $skipped = []): array
    {
        return [
            'state'           => $state,
            'published_count' => count($published),
            'published'       => $published,
            'failed'          => $failed,
            'skipped'         => $skipped,
        ];
    }
}
