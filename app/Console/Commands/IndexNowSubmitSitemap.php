<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\IndexNowService;
use App\Services\SitemapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Zgłasza WSZYSTKIE URL-e z sitemapy do IndexNow (Bing i spółka).
 *
 * Artykuły i kursy .md publikujesz commitem + deployem (git pull) — bez runtime
 * eventu. Tę komendę uruchom po deployu, żeby zgłosić nowe/zmienione artykuły,
 * lekcje kursów i resztę mapy strony.
 *
 * UWAGA — hosting bez outboundu (np. OVH mutualisé): serwer produkcyjny nie może
 * łączyć się na zewnątrz, więc ping do IndexNow z produkcji nie przejdzie. W takim
 * wypadku odpal tę komendę z maszyny Z DOSTĘPEM do internetu (np. lokalnie), wskazując
 * PRODUKCYJNĄ sitemapę przez --url:
 *   php artisan indexnow:submit-sitemap --url=https://oatllo.com/sitemap.xml
 */
class IndexNowSubmitSitemap extends Command
{
    protected $signature = 'indexnow:submit-sitemap
        {--regenerate : Najpierw przebuduj lokalny sitemap.xml}
        {--url= : Pobierz sitemapę z tego URL zamiast z lokalnego pliku (do zgłaszania produkcji spoza serwera)}';

    protected $description = 'Zgłasza wszystkie URL-e z sitemap.xml do IndexNow (Bing/Yandex/Seznam).';

    public function handle(): int
    {
        if (! IndexNowService::enabled()) {
            $this->warn('IndexNow wyłączony — ustaw INDEXNOW_KEY w .env.');

            return self::FAILURE;
        }

        $remoteUrl = $this->option('url');

        if ($remoteUrl) {
            $this->info("Pobieram sitemapę z {$remoteUrl}...");
            try {
                $response = Http::timeout(20)->get($remoteUrl);
            } catch (\Throwable $e) {
                $this->error('Nie udało się pobrać sitemapy: ' . $e->getMessage());

                return self::FAILURE;
            }
            if (! $response->successful()) {
                $this->error("Sitemapa zwróciła HTTP {$response->status()}.");

                return self::FAILURE;
            }
            $xml = simplexml_load_string($response->body());
        } else {
            if ($this->option('regenerate')) {
                $this->info('Regeneruję sitemap.xml...');
                SitemapService::generateSitemap();
            }

            $sitemapPath = config('articles.sitemap_path') ?: public_path('/');
            $file = rtrim((string) $sitemapPath, '/\\') . DIRECTORY_SEPARATOR . 'sitemap.xml';

            if (! is_file($file)) {
                $this->error("Nie znaleziono pliku sitemap: {$file}. Uruchom z --regenerate albo --url=.");

                return self::FAILURE;
            }

            $xml = simplexml_load_file($file);
        }

        if ($xml === false) {
            $this->error('Nie udało się sparsować sitemapy (nieprawidłowy XML).');

            return self::FAILURE;
        }

        $urls = [];
        foreach ($xml->url as $entry) {
            $loc = trim((string) $entry->loc);
            if ($loc !== '') {
                $urls[] = $loc;
            }
        }

        if ($urls === []) {
            $this->warn('Brak URL-i w sitemapie.');

            return self::SUCCESS;
        }

        $this->info('Zgłaszam ' . count($urls) . ' URL-i do IndexNow...');
        IndexNowService::submitMany($urls);
        $this->info('Gotowe. (Szczegóły ewentualnych błędów w logu.)');

        return self::SUCCESS;
    }
}
