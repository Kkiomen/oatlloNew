<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Article\InternalLinker;
use App\Services\Article\MarkdownArticleParser;
use App\Services\Article\MarkdownArticleRepository;
use App\Services\IndexNowService;
use App\Services\SitemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * API do zarządzania artykułami w formacie Markdown.
 *
 * Artykuł można wgrać na dwa sposoby:
 *  - jako przesłany plik .md   (multipart/form-data, pole "file")
 *  - jako surowa zawartość     (JSON/form, pole "content")
 *
 * Artykuł jest zapisywany lokalnie jako plik .md i renderowany na stronie
 * dynamicznie – nie trafia do bazy danych.
 */
class ArticleImportController extends Controller
{
    public function __construct(
        private MarkdownArticleRepository $repository,
        private MarkdownArticleParser $parser,
    ) {
    }

    /**
     * Wgrywa (tworzy lub aktualizuje) artykuł.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => ['nullable', 'file', 'max:5120'],
                'content' => ['nullable', 'string'],
                'slug' => ['nullable', 'string', 'max:200'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Nieprawidłowe dane wejściowe.',
                'errors' => $e->errors(),
            ], 422);
        }

        // Pobierz surowy Markdown z pliku lub z pola content.
        $raw = null;
        if ($request->hasFile('file')) {
            $raw = (string) file_get_contents($request->file('file')->getRealPath());
        } elseif ($request->filled('content')) {
            $raw = (string) $request->input('content');
        }

        if ($raw === null || trim($raw) === '') {
            return response()->json([
                'success' => false,
                'message' => 'Brak zawartości. Prześlij plik .md w polu "file" lub Markdown w polu "content".',
            ], 422);
        }

        // Sparsuj, aby zwalidować frontmatter i ustalić slug.
        ['frontmatter' => $fm] = $this->parser->parse($raw);

        $name = $fm['name'] ?? $fm['title'] ?? null;
        if (empty($name)) {
            return response()->json([
                'success' => false,
                'message' => 'Frontmatter musi zawierać pole "name" (tytuł artykułu).',
            ], 422);
        }

        // Ustal slug: request > frontmatter > z nazwy pliku > z tytułu.
        $slug = $request->input('slug')
            ?? ($fm['slug'] ?? null)
            ?? ($request->hasFile('file')
                ? pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME)
                : null)
            ?? Str::slug($name);

        $slug = $this->repository->normalizeSlug($slug);
        $existed = $this->repository->exists($slug);

        $path = $this->repository->save($raw, $slug);
        $article = $this->parser->toArticle($raw, $slug);

        // Nowy/zmieniony artykuł .md wpływa na indeks linkowania wewnętrznego.
        InternalLinker::forget();

        $this->regenerateSitemap();

        // Powiadom wyszukiwarki (Bing i spółka) o nowym/zmienionym URL-u.
        // Tylko dla opublikowanych artykułów — szkice nie mają po co iść do indeksu.
        if ($article->is_published) {
            $this->pingIndexNow($article->getRoute());
        }

        return response()->json([
            'success' => true,
            'message' => $existed ? 'Artykuł zaktualizowany.' : 'Artykuł utworzony.',
            'data' => [
                'slug' => $slug,
                'name' => $article->name,
                'language' => $article->language,
                'is_published' => $article->is_published,
                'url' => $article->getRoute(),
                'file' => $path,
                'created' => ! $existed,
            ],
        ], $existed ? 200 : 201);
    }

    /**
     * Lista artykułów z plików .md.
     */
    public function index(): JsonResponse
    {
        $data = $this->repository->all()->map(fn ($a) => [
            'slug' => $a->slug,
            'name' => $a->name,
            'language' => $a->language,
            'is_published' => $a->is_published,
            'published_at' => optional($a->published_at)->toIso8601String(),
            'url' => $a->getRoute(),
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Zwraca surową zawartość pojedynczego artykułu.
     */
    public function show(string $slug): JsonResponse
    {
        $raw = $this->repository->raw($slug);
        if ($raw === null) {
            return response()->json([
                'success' => false,
                'message' => 'Nie znaleziono artykułu.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'slug' => $this->repository->normalizeSlug($slug),
                'content' => $raw,
            ],
        ]);
    }

    /**
     * Usuwa artykuł (plik .md).
     */
    public function destroy(string $slug): JsonResponse
    {
        // Ustal URL PRZED usunięciem (po delete plik już nie istnieje).
        $article = $this->repository->findBySlug($slug);
        $url = $article?->getRoute();

        $deleted = $this->repository->delete($slug);

        if ($deleted) {
            InternalLinker::forget();
            $this->regenerateSitemap();

            // Zgłoś usunięty URL — Bing szybciej wykryje 404/410 i zdejmie go z indeksu.
            if (! empty($url)) {
                $this->pingIndexNow($url);
            }
        }

        return response()->json([
            'success' => $deleted,
            'message' => $deleted ? 'Artykuł usunięty.' : 'Nie znaleziono artykułu.',
        ], $deleted ? 200 : 404);
    }

    /**
     * Regeneruje sitemap, aby uwzględnić zmiany w artykułach .md.
     * Błąd generowania nie może przerwać operacji na artykule.
     */
    private function regenerateSitemap(): void
    {
        // Uruchamiamy po wysłaniu odpowiedzi, aby nie opóźniać ani nie zakłócać
        // odpowiedzi API (generowanie mapy strony może chwilę potrwać).
        app()->terminating(function () {
            try {
                SitemapService::generateSitemap();
            } catch (\Throwable $e) {
                Log::warning('Nie udało się zregenerować sitemap po zmianie artykułu .md: ' . $e->getMessage());
            }
        });
    }

    /**
     * Powiadamia IndexNow o zmianie URL-a. Wykonywane po wysłaniu odpowiedzi,
     * aby ping do zewnętrznego API nie opóźniał odpowiedzi klienta. IndexNowService
     * sam pilnuje, by żaden błąd sieciowy nie przerwał żądania.
     */
    private function pingIndexNow(string $url): void
    {
        app()->terminating(function () use ($url) {
            IndexNowService::submit($url);
        });
    }
}
