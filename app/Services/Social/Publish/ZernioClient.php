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
     * Id zasobu z odpowiedzi Zernio.
     *
     * Ich API zwraca mongowe `_id`, nigdy `id` – sprawdzone na żywo zarówno na
     * `/accounts`, jak i `/posts`. Pierwsza wersja czytała `id` i zapisywała
     * `zernio_id: null`, czyli traciła JEDYNY uchwyt do posta po stronie Zernio:
     * bez niego nie da się potem zapytać, czy publikacja doszła do skutku.
     *
     * Warianty zagnieżdżenia zostają, bo POST i GET opakowują odpowiedź inaczej
     * (`{posts:[...]}` przy liście), a zgadywanie jednego kształtu już raz
     * kosztowało nas ten null.
     *
     * @param  array<string, mixed>  $body
     */
    public static function idFrom(array $body): ?string
    {
        foreach ([$body, $body['post'] ?? [], $body['data'] ?? []] as $level) {
            if (! is_array($level)) {
                continue;
            }

            $id = $level['_id'] ?? $level['id'] ?? null;

            if (is_string($id) && $id !== '') {
                return $id;
            }
        }

        return null;
    }

    /**
     * Stan posta po stronie Zernio (`scheduled` -> `publishing` -> `published`).
     *
     * ISTNIEJE, BO "PRZYJĘTE" TO NIE "OPUBLIKOWANE": Zernio odpowiada na POST
     * od razu, a na Instagrama wypycha ASYNCHRONICZNIE. Nasz pierwszy prawdziwy
     * post w chwili odpowiedzi miał `status: publishing` i dopiero po chwili
     * `published`. Gdyby po drodze padł, my mielibyśmy w dzienniku "opublikowane"
     * i nie dowiedzielibyśmy się nigdy.
     *
     * @return array{status: ?string, platform_status: ?string, error: mixed}
     */
    public function postStatus(string $id): array
    {
        $response = $this->http()->get('/posts/' . $id);

        $response->throw();

        $body = (array) $response->json();
        $post = $body['post'] ?? $body['data'] ?? $body;
        $platform = ($post['platforms'] ?? [[]])[0] ?? [];

        return [
            'status'          => $post['status'] ?? null,
            'platform_status' => $platform['status'] ?? null,
            'error'           => $platform['error'] ?? null,
        ];
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
