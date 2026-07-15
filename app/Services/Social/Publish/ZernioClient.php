<?php

namespace App\Services\Social\Publish;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Klient Zernio (https://docs.zernio.com) – jedyne miejsce, które gada z ich API.
 *
 * Świadomie BEZ ich SDK (`zernio-dev/zernio-php`): używamy dwóch endpointów
 * (`GET /accounts`, `POST /posts`), a SDK jest generowane z OpenAPI i wciąga
 * własny model świata. Http facade Laravela daje przy tym `Http::fake()` w
 * testach za darmo, więc zależność kupowałaby nam mniej, niż kosztuje.
 *
 * Uwaga na kształt requestu – to NIE jest Graph API:
 *  - media idą jako `mediaItems[{type,url}]` z PUBLICZNYMI URL-ami,
 *  - story/reel rozróżnia `platformSpecificData.contentType` ("story"/"reels"),
 *    a nie osobny endpoint,
 *  - karuzela to po prostu kilka `mediaItems` (max 10), przy czym pierwszy
 *    element narzuca proporcje całości (u nas i tak wszystkie są 1080x1350).
 */
class ZernioClient
{
    public function configured(): bool
    {
        return trim((string) config('social.zernio.key')) !== ''
            && trim((string) config('social.zernio.account_id')) !== '';
    }

    private function http(): PendingRequest
    {
        return Http::withToken((string) config('social.zernio.key'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('social.zernio.timeout', 30))
            ->baseUrl(rtrim((string) config('social.zernio.base_url'), '/'));
    }

    /**
     * Konta podpięte do klucza – stąd bierze się ZERNIO_ACCOUNT_ID.
     *
     * @return array<int, array<string, mixed>>
     */
    public function accounts(): array
    {
        $response = $this->http()->get('/accounts');

        $response->throw();

        $body = $response->json();

        return $body['data'] ?? $body['accounts'] ?? (is_array($body) ? $body : []);
    }

    /**
     * Publikuje NATYCHMIAST (`publishNow`), bo terminy żyją w `.md`.
     *
     * Zernio ma własny scheduler (`scheduledFor`), ale wtedy plan istniałby w
     * dwóch miejscach i zmiana `publish_at:` w pliku wymagałaby synchronizacji
     * z ich stanem. Tick godzinowy pyta pliki i publikuje to, co dojrzało.
     *
     * @param  list<array{type:string,url:string}>  $mediaItems
     * @return array<string, mixed>
     */
    public function publishNow(string $caption, array $mediaItems, ?string $contentType = null): array
    {
        $platform = [
            'platform'  => 'instagram',
            'accountId' => (string) config('social.zernio.account_id'),
        ];

        if ($contentType !== null) {
            $platform['platformSpecificData'] = ['contentType' => $contentType];

            if ($contentType === 'reels') {
                // Reel ma iść też do feedu – inaczej ląduje wyłącznie w zakładce
                // Reels i post znika z profilu.
                $platform['platformSpecificData']['shareToFeed'] = true;
            }
        }

        $response = $this->http()->post('/posts', [
            'content'    => $caption,
            'mediaItems' => $mediaItems,
            'platforms'  => [$platform],
            'publishNow' => true,
        ]);

        $response->throw();

        return (array) $response->json();
    }
}
