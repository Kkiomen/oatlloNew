<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AutoPublishController extends Controller
{
    /**
     * Automatycznie publikuje najstarszy nieopublikowany artykuł w języku angielskim
     */
    public function publishOldestUnpublished(): JsonResponse
    {
        try {
            // Sprawdź czy dzisiaj już został opublikowany jakiś artykuł
            $todayPublished = Article::where('is_published', true)
                ->where('language', 'en')
                ->whereDate('auto_publish_date', Carbon::today())
                ->exists();

            if ($todayPublished) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dzisiaj już został opublikowany artykuł. Spróbuj ponownie jutro.',
                    'data' => null
                ], 200);
            }

            // Znajdź najstarszy nieopublikowany artykuł w języku angielskim
            $article = Article::where('is_published', false)
                ->where('language', 'en')
                ->orderBy('created_at', 'asc')
                ->first();

            if (!$article) {
                return response()->json([
                    'success' => false,
                    'message' => 'Brak nieopublikowanych artykułów w języku angielskim.',
                    'data' => null
                ], 404);
            }

            // Ustaw datę automatycznej publikacji i opublikuj artykuł
            Article::publish($article, true);
            $article->auto_publish_date = Carbon::now();
            $article->save();

            return response()->json([
                'success' => true,
                'message' => 'Artykuł został pomyślnie opublikowany.',
                'data' => [
                    'article_id' => $article->id,
                    'article_name' => $article->name,
                    'published_at' => $article->auto_publish_date,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wystąpił błąd podczas publikacji artykułu: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
