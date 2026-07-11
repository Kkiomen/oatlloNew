<?php

namespace App\Services\Article;

use App\Models\Article;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Odczyt i zapis artykułów przechowywanych jako pliki .md.
 * Stanowi drugie – obok bazy danych – źródło artykułów.
 */
class MarkdownArticleRepository
{
    public function __construct(private MarkdownArticleParser $parser)
    {
    }

    public function directory(): string
    {
        return rtrim((string) config('articles.path'), '/\\');
    }

    private function ensureDirectory(): void
    {
        $dir = $this->directory();
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
    }

    /**
     * Sanityzuje slug do bezpiecznej nazwy pliku (ochrona przed path traversal).
     */
    public function normalizeSlug(string $slug): string
    {
        $slug = Str::slug($slug);

        return $slug !== '' ? $slug : 'article';
    }

    public function pathForSlug(string $slug): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . $this->normalizeSlug($slug) . '.md';
    }

    public function exists(string $slug): bool
    {
        return File::exists($this->pathForSlug($slug));
    }

    /**
     * Zapisuje surowy Markdown do pliku. Zwraca ścieżkę zapisanego pliku.
     */
    public function save(string $rawMarkdown, string $slug): string
    {
        $this->ensureDirectory();
        $path = $this->pathForSlug($slug);
        File::put($path, $rawMarkdown);

        return $path;
    }

    public function delete(string $slug): bool
    {
        $path = $this->pathForSlug($slug);

        return File::exists($path) ? File::delete($path) : false;
    }

    /**
     * Zwraca surową zawartość pliku .md dla slug (lub null).
     */
    public function raw(string $slug): ?string
    {
        $path = $this->pathForSlug($slug);

        return File::exists($path) ? File::get($path) : null;
    }

    /**
     * Wszystkie artykuły z plików .md jako niepersystowane modele Article.
     *
     * @return Collection<int, Article>
     */
    public function all(): Collection
    {
        $dir = $this->directory();
        if (! File::isDirectory($dir)) {
            return collect();
        }

        return collect(File::files($dir))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'md')
            ->map(function ($file) {
                $raw = File::get($file->getPathname());
                $slugFallback = $file->getFilenameWithoutExtension();

                return $this->parser->toArticle($raw, $slugFallback);
            })
            ->values();
    }

    /**
     * Opublikowane artykuły z plików .md, opcjonalnie filtrowane po języku.
     *
     * @return Collection<int, Article>
     */
    public function published(?string $language = null): Collection
    {
        return $this->all()
            ->filter(fn (Article $a) => $a->isLive())
            ->when($language !== null, fn ($c) => $c->filter(fn (Article $a) => $a->language === $language))
            ->values();
    }

    /**
     * Znajduje pojedynczy artykuł po slug.
     */
    public function findBySlug(string $slug): ?Article
    {
        $raw = $this->raw($slug);
        if ($raw === null) {
            return null;
        }

        return $this->parser->toArticle($raw, $this->normalizeSlug($slug));
    }
}
