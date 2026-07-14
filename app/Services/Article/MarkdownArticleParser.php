<?php

namespace App\Services\Article;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Services\Markdown\HtmlContentEnhancer;
use Carbon\Carbon;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Parsuje surowy Markdown (frontmatter YAML + treść) na instancję modelu Article
 * hydrowaną w pamięci (bez zapisu do bazy). Dzięki temu artykuły z plików .md
 * renderują się przez te same widoki co artykuły z bazy danych.
 */
class MarkdownArticleParser
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new FrontMatterExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Rozdziela frontmatter od treści i konwertuje treść na HTML.
     *
     * @return array{frontmatter: array<string,mixed>, html: string}
     */
    public function parse(string $raw): array
    {
        $raw = $this->normalizeEncoding($raw);
        $result = $this->converter->convert($raw);

        $frontmatter = [];
        if ($result instanceof RenderedContentWithFrontMatter) {
            $frontmatter = $result->getFrontMatter() ?? [];
        }

        return [
            'frontmatter' => is_array($frontmatter) ? $frontmatter : [],
            'html' => HtmlContentEnhancer::enhance((string) $result->getContent()),
        ];
    }

    /**
     * Buduje niepersystowaną instancję Article z surowego Markdown.
     *
     * @param string      $raw          Surowa zawartość pliku .md (z frontmatterem)
     * @param string|null $slugFallback Slug używany, gdy brak go we frontmatterze (np. z nazwy pliku)
     */
    public function toArticle(string $raw, ?string $slugFallback = null): Article
    {
        ['frontmatter' => $fm, 'html' => $html] = $this->parse($raw);

        $name = $fm['name'] ?? $fm['title'] ?? $slugFallback ?? 'Untitled';
        $slug = $fm['slug'] ?? $slugFallback ?? Str::slug($name);

        $publishedAt = isset($fm['published_at'])
            ? $this->toDate($fm['published_at'])
            : Carbon::now();

        $article = new Article();
        $article->name = $name;
        $article->slug = $slug;
        $article->short_description = $fm['short_description'] ?? $fm['description'] ?? '';
        // Brak obrazka we frontmatterze (lub jawne "auto") => generowana okładka SVG
        // dopasowana do tematu artykułu, zamiast pustego/losowego obrazka.
        $image = $fm['image'] ?? null;
        $article->image = (empty($image) || $image === 'auto')
            ? route('article.cover', ['slug' => $slug])
            : $image;
        $article->language = $fm['language'] ?? config('articles.default_language');
        $article->type = 'normal';
        // Frazy-kotwice do linkowania wewnętrznego (opcjonalne we frontmatterze).
        // Akceptujemy tablicę lub string; zapisujemy jako listę rozdzieloną przecinkami.
        $keys = $fm['keys_link'] ?? $fm['keywords'] ?? null;
        if (is_array($keys)) {
            $keys = implode(', ', array_map('strval', $keys));
        }
        $article->keys_link = ($keys !== null && trim((string) $keys) !== '') ? (string) $keys : null;
        $article->is_published = array_key_exists('is_published', $fm)
            ? (bool) $fm['is_published']
            : true;
        $article->contents = [
            ['type' => 'text', 'content' => $html],
        ];
        $article->view_content = [];
        $article->published_at = $publishedAt;

        // Ustawiamy timestampy ręcznie – model nie jest zapisywany do bazy.
        $article->created_at = $publishedAt;
        $article->updated_at = isset($fm['updated_at'])
            ? $this->toDate($fm['updated_at'])
            : $publishedAt;

        // Znacznik pozwalający odróżnić artykuły plikowe od tych z bazy.
        $article->source = 'markdown';
        $article->exists = false;

        // Relacje ustawiamy ręcznie, aby uniknąć zapytań do bazy dla modelu bez id.
        $article->setRelation('tags', $this->buildTags($fm['tags'] ?? [], $article->language));
        $article->setRelation('category', $this->resolveCategory($fm['category'] ?? null, $article));

        return $article;
    }

    /**
     * Dopasowuje kategorię z frontmatteru (slug lub nazwa) do istniejącej kategorii w bazie.
     * Ustawia category_id na modelu i zwraca model Category (lub null).
     *
     * @param mixed $value
     */
    private function resolveCategory($value, Article $article): ?Category
    {
        if (empty($value)) {
            return null;
        }

        $slug = Str::slug((string) $value);
        $category = Category::where('slug', (string) $value)
            ->orWhere('slug', $slug)
            ->first();

        if ($category) {
            $article->category_id = $category->id;
        }

        return $category;
    }

    /**
     * Normalizuje kodowanie do poprawnego UTF-8 (usuwa BOM, konwertuje z innych stron kodowych).
     * CommonMark odrzuca treść, która nie jest poprawnym UTF-8.
     */
    private function normalizeEncoding(string $raw): string
    {
        // Usuń BOM UTF-8, jeśli jest.
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        if (! mb_check_encoding($raw, 'UTF-8')) {
            // Uwaga: mbstring nie zna nazwy "Windows-1250" (rzuca ValueError) — dla
            // polskich stron kodowych używamy obsługiwanego ISO-8859-2.
            $detected = mb_detect_encoding($raw, ['UTF-8', 'ISO-8859-2', 'Windows-1252', 'ISO-8859-1'], true) ?: 'ISO-8859-2';
            $raw = mb_convert_encoding($raw, 'UTF-8', $detected);
        }

        return $raw;
    }

    /**
     * Konwertuje wartość daty z frontmatteru (string / timestamp / DateTime) na Carbon.
     *
     * @param mixed $value
     */
    private function toDate($value): Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return Carbon::createFromTimestamp((int) $value);
        }

        return Carbon::parse((string) $value);
    }

    /**
     * Tworzy lekkie, niepersystowane modele Tag na potrzeby widoku.
     *
     * @param mixed $tags
     */
    private function buildTags($tags, string $language): \Illuminate\Support\Collection
    {
        if (is_string($tags)) {
            $tags = array_map('trim', explode(',', $tags));
        }

        if (! is_array($tags)) {
            return collect();
        }

        return collect($tags)
            ->filter()
            ->map(function ($name) use ($language) {
                $tag = new Tag();
                $tag->name = (string) $name;
                $tag->slug = Str::slug((string) $name);
                $tag->language = $language;
                $tag->exists = false;

                return $tag;
            })
            ->values();
    }
}
