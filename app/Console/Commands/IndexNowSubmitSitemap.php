<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\IndexNowService;
use App\Services\SitemapService;
use Illuminate\Console\Command;

/**
 * Zgłasza WSZYSTKIE URL-e z sitemapy do IndexNow (Bing i spółka).
 *
 * Pojedyncze artykuły .md pingują się same przez API (ArticleImportController),
 * ale kursy publikujesz commitem + deployem — bez runtime eventu. Tę komendę
 * uruchom po deployu (patrz checklist w CLAUDE.md), żeby zgłosić nowe/zmienione
 * lekcje kursów i resztę mapy strony.
 */
class IndexNowSubmitSitemap extends Command
{
    protected $signature = 'indexnow:submit-sitemap {--regenerate : Najpierw przebuduj sitemap.xml}';

    protected $description = 'Zgłasza wszystkie URL-e z sitemap.xml do IndexNow (Bing/Yandex/Seznam).';

    public function handle(): int
    {
        if (! IndexNowService::enabled()) {
            $this->warn('IndexNow wyłączony — ustaw INDEXNOW_KEY w .env.');

            return self::FAILURE;
        }

        if ($this->option('regenerate')) {
            $this->info('Regeneruję sitemap.xml...');
            SitemapService::generateSitemap();
        }

        $sitemapPath = config('articles.sitemap_path') ?: public_path('/');
        $file = rtrim((string) $sitemapPath, '/\\') . DIRECTORY_SEPARATOR . 'sitemap.xml';

        if (! is_file($file)) {
            $this->error("Nie znaleziono pliku sitemap: {$file}. Uruchom z --regenerate.");

            return self::FAILURE;
        }

        $xml = simplexml_load_file($file);
        if ($xml === false) {
            $this->error("Nie udało się sparsować {$file}.");

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
