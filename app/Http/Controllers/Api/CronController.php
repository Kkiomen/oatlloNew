<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\SitemapService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint "cron tick" uderzany cyklicznie (np. co godzinę z n8n).
 *
 * Odpowiada za:
 *  - publikację artykułów z bazy, których zaplanowana data publikacji już minęła
 *    (is_published = false, published_at <= teraz),
 *  - regenerację statycznego pliku sitemap.xml, aby uwzględnić artykuły, które
 *    właśnie stały się widoczne (dotyczy też artykułów .md – ich widoczność jest
 *    liczona po dacie publikacji przy każdym żądaniu).
 *
 * RSS (/feed) generuje się dynamicznie przy każdym żądaniu i respektuje datę
 * publikacji, więc nie wymaga tu żadnej akcji.
 *
 * Endpoint jest publiczny (GET, bez autoryzacji). Efektem wywołania jest jedynie
 * przyspieszenie publikacji już zaplanowanych treści oraz odświeżenie sitemap –
 * nie przyjmuje żadnych danych wejściowych.
 */
class CronController extends Controller
{
    public function run(): JsonResponse
    {
        $published = $this->publishDueArticles();

        $sitemapOk = $this->regenerateSitemap();

        return response()->json([
            'success' => true,
            'published_count' => count($published),
            'published' => $published,
            'sitemap_regenerated' => $sitemapOk,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Publikuje artykuły z bazy, których zaplanowany termin już minął.
     *
     * Publikacja jest "lekka": ustawiamy wyłącznie flagę is_published i
     * zachowujemy zaplanowaną datę published_at. Świadomie NIE uruchamiamy tu
     * generatorów tagów/linków wewnętrznych (Article::publish), bo wołają one
     * zewnętrzne AI – to zbyt kosztowne i zawodne dla publicznego endpointu
     * odpalanego co godzinę. Tagi/linki powstają przy tworzeniu artykułu.
     *
     * @return array<int, array<string, mixed>>
     */
    private function publishDueArticles(): array
    {
        $due = Article::where('is_published', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', Carbon::now())
            ->orderBy('published_at', 'asc')
            ->get();

        $published = [];

        foreach ($due as $article) {
            $article->is_published = true;
            $article->save();

            $published[] = [
                'id' => $article->id,
                'slug' => $article->slug,
                'name' => $article->name,
                'language' => $article->language,
                'published_at' => optional($article->published_at)->toIso8601String(),
            ];
        }

        return $published;
    }

    /**
     * Regeneruje sitemap. Błąd generowania nie może przerwać całego ticka.
     */
    private function regenerateSitemap(): bool
    {
        try {
            SitemapService::generateSitemap();

            return true;
        } catch (\Throwable $e) {
            Log::warning('Cron: nie udało się zregenerować sitemap: ' . $e->getMessage());

            return false;
        }
    }
}
