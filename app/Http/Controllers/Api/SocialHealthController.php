<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Social\Publish\SocialMediaStore;
use App\Services\Social\Publish\ZernioClient;
use App\Services\Social\Review\SocialReviewItem;
use App\Services\Social\Review\SocialReviewQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Przegląd przedstartowy autopublikacji – z WWW, bo tylko stamtąd publikujemy.
 *
 * DLACZEGO TO ISTNIEJE, skoro jest `social:accounts`: na hostingu współdzielonym
 * OVH konsola NIE MA WYJŚCIA W SIEĆ (`php artisan social:accounts` kończy się
 * "cURL error 7: Connection refused"), a tick leci przez WWW – zupełnie inne
 * środowisko sieciowe. Odpowiedź z CLI nie mówi więc NIC o tym, czy publikacja
 * zadziała. To jest jedyny sposób, żeby zapytać o to serwer w tym kontekście,
 * w którym naprawdę pracuje.
 *
 * Nic nie publikuje i nic nie zapisuje: jedyny ruch na zewnątrz to `GET /accounts`.
 *
 * Chroniony tym samym SOCIAL_CRON_TOKEN co socialowa część /api/cron i tak samo
 * rejestrowany warunkowo – bez tokenu trasy nie ma w routingu.
 */
class SocialHealthController extends Controller
{
    public function __construct(
        private readonly ZernioClient $zernio,
        private readonly SocialReviewQueue $queue,
        private readonly SocialMediaStore $media,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $token = (string) config('social.auto_publish.token');

        if (trim($token) === '' || ! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $baseUrl = rtrim((string) (config('social.media.base_url') ?: config('app.url')), '/');

        return response()->json([
            'auto_publish_enabled' => (bool) config('social.auto_publish.enabled'),
            'zernio'               => $this->zernioHealth(),
            'media_base_url'       => $baseUrl,
            'media_base_url_hint'  => $this->baseUrlHint($baseUrl),
            'queue'                => $this->queueHealth(),
        ]);
    }

    /**
     * Zernio wymaga PUBLICZNYCH URL-i HTTPS. Zła baza to cichy zabójca: grafiki
     * leżą na serwerze, health świeci na zielono, a posty padają, bo do Zernio
     * idzie adres, którego nikt nie pobierze.
     *
     * Nie jest to przypadek teoretyczny – literówka `httsp://` naprawdę wjechała
     * do .env (na szczęście lokalnie) i wyglądała dokładnie jak brak plików.
     */
    private function baseUrlHint(string $baseUrl): ?string
    {
        if (! str_starts_with($baseUrl, 'https://')) {
            return 'Baza URL-i grafik NIE zaczyna się od https:// – Zernio nie pobierze plików. '
                . 'Sprawdź SOCIAL_MEDIA_BASE_URL (literówka w schemacie wygląda jak brak grafik).';
        }

        if (str_contains($baseUrl, 'localhost') || str_contains($baseUrl, '.test')) {
            return 'Baza URL-i wskazuje na adres lokalny – Zernio nie ma jak tam wejść.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function zernioHealth(): array
    {
        if (! $this->zernio->configured()) {
            return [
                'configured' => false,
                'hint'       => 'Brak ZERNIO_API_KEY albo ZERNIO_ACCOUNT_ID w .env na produkcji.',
            ];
        }

        $accountId = (string) config('social.zernio.account_id');

        try {
            $accounts = $this->zernio->accounts();
        } catch (RequestException $e) {
            return [
                'configured' => true,
                'reachable'  => true, // odpowiedzieli, tylko źle
                'error'      => 'HTTP ' . $e->response->status(),
                'hint'       => $e->response->status() === 401
                    ? 'Klucz zły albo cofnięty.'
                    : 'Zernio odpowiedziało błędem.',
            ];
        } catch (ConnectionException $e) {
            return [
                'configured' => true,
                'reachable'  => false,
                'error'      => mb_substr($e->getMessage(), 0, 200),
                'hint'       => 'Serwer nie ma wyjścia na zernio.com. Bez tego tick nic nie opublikuje.',
            ];
        }

        // Konto MUSI być na liście. Klucz widzi kilka marek, a wpisanie cudzego id
        // wysłałoby posty Oatllo na cudzy profil – tego się nie cofa, więc lepiej
        // zobaczyć to tutaj niż na obcym feedzie.
        $match = null;

        foreach ($accounts as $account) {
            $id = (string) ($account['_id'] ?? $account['id'] ?? $account['accountId'] ?? '');

            if ($id !== '' && hash_equals($id, $accountId)) {
                $match = $account;

                break;
            }
        }

        $hint = null;

        if ($match === null) {
            $hint = 'ZERNIO_ACCOUNT_ID nie pasuje do żadnego konta z tego klucza.';
        } elseif (($match['platform'] ?? null) !== 'instagram') {
            $hint = 'To konto NIE jest Instagramem – moduł publikuje tylko tam.';
        }

        return [
            'configured'       => true,
            'reachable'        => true,
            'account_id_valid' => $match !== null,
            'publishing_as'    => $match['username'] ?? null,
            'account_platform' => $match['platform'] ?? null,
            'token_expires_at' => $match['tokenExpiresAt'] ?? null,
            'hint'             => $hint,
        ];
    }

    /**
     * Co czeka w kolejce – bez tego "wszystko zielone" nie znaczy jeszcze, że
     * jest co publikować.
     *
     * @return array<string, mixed>
     */
    private function queueHealth(): array
    {
        $approved = $this->queue->approved();
        $formats = (array) config('social.auto_publish.formats', []);

        $withMedia = 0;
        $withoutMedia = [];

        /** @var SocialReviewItem $item */
        foreach ($approved as $item) {
            foreach ($item->post->formats as $format) {
                if (! in_array($format, $formats, true)) {
                    continue;
                }

                $missing = $this->media->missingFor($item->post, $format);

                if ($missing === []) {
                    $withMedia++;
                } else {
                    $withoutMedia[] = $item->post->slug . ' [' . $format . ']: brak ' . implode(', ', $missing);
                }
            }
        }

        return [
            'approved_posts'          => $approved->count(),
            'pairs_ready_to_publish'  => $withMedia,
            'pairs_missing_media'     => array_slice($withoutMedia, 0, 10),
            'missing_media_total'     => count($withoutMedia),
        ];
    }
}
