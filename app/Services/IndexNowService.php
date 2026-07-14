<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Powiadamianie wyszukiwarek (Bing, Yandex, Seznam...) o zmianach URL przez
 * protokół IndexNow. Jeden ping na hub api.indexnow.org rozsyła informację do
 * wszystkich uczestniczących silników.
 *
 * Weryfikacja własności domeny odbywa się przez plik z kluczem hostowany pod
 * https://oatllo.com/{key}.txt (trasa "indexnow.key", patrz routes/web.php).
 *
 * ZASADA: ping do wyszukiwarki NIGDY nie może wywalić operacji na treści.
 * Każde wywołanie jest owinięte w try/catch i loguje jedynie ostrzeżenie.
 */
class IndexNowService
{
    /**
     * Czy integracja jest aktywna (skonfigurowany klucz).
     */
    public static function enabled(): bool
    {
        return ! empty(config('services.indexnow.key'));
    }

    /**
     * Zgłasza pojedynczy URL (bezwzględny) do IndexNow.
     */
    public static function submit(string $url): void
    {
        static::submitMany([$url]);
    }

    /**
     * Zgłasza wiele URL-i naraz. Wszystkie muszą należeć do tego samego hosta
     * (wymóg protokołu). Puste wejście lub brak klucza = no-op.
     *
     * @param string[] $urls
     */
    public static function submitMany(array $urls): void
    {
        try {
            $key = config('services.indexnow.key');
            if (empty($key)) {
                return;
            }

            // Normalizacja: tylko bezwzględne URL-e, unikalne, niepuste.
            $urls = array_values(array_unique(array_filter(
                array_map('trim', $urls),
                fn ($u) => $u !== '' && str_starts_with($u, 'http'),
            )));

            if ($urls === []) {
                return;
            }

            $host = parse_url($urls[0], PHP_URL_HOST);
            if (empty($host)) {
                return;
            }

            // Protokół IndexNow: max 10 000 URL-i na żądanie.
            foreach (array_chunk($urls, 10000) as $chunk) {
                $response = Http::timeout(10)
                    ->acceptJson()
                    ->post(config('services.indexnow.endpoint'), [
                        'host' => $host,
                        'key' => $key,
                        'keyLocation' => 'https://' . $host . '/' . $key . '.txt',
                        'urlList' => array_values($chunk),
                    ]);

                if (! $response->successful()) {
                    Log::warning('IndexNow: nieudane zgłoszenie URL-i.', [
                        'status' => $response->status(),
                        'count' => count($chunk),
                        'body' => $response->body(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('IndexNow: wyjątek podczas zgłaszania URL-i: ' . $e->getMessage());
        }
    }
}
