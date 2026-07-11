<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\Article\CoverImageService;
use App\Services\Article\MarkdownArticleRepository;
use Illuminate\Http\Response;

/**
 * Serwuje wygenerowaną okładkę artykułu jako SVG (motyw "okno kodu").
 *
 * Grafika jest budowana dynamicznie na podstawie danych artykułu (tytuł,
 * kategoria, tagi), więc pasuje do tematu. Nie zależy od żadnych rozszerzeń
 * graficznych PHP – działa identycznie lokalnie i na produkcji.
 */
class CoverController extends Controller
{
    public function __construct(
        private MarkdownArticleRepository $repository,
        private CoverImageService $covers,
    ) {
    }

    public function show(string $slug): Response
    {
        // Źródło 1: plik .md (jak w pozostałych trasach – ma pierwszeństwo).
        $article = $this->repository->findBySlug($slug)
            ?? Article::where('slug', $slug)->first();

        if (!$article) {
            abort(404);
        }

        $svg = $this->covers->renderForArticle($article);

        return response($svg, 200)
            ->header('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
