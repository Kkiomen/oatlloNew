<?php

namespace App\Services\Social;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Odczyt postów social media z plików .md. Wzorowane na
 * MarkdownArticleRepository, ale CELOWO bez save()/delete() – posty pisze się
 * lokalnie (ręcznie albo skillem) i wypycha commitem. Jedynym writerem jest git.
 *
 * Zero bazy: repozytorium zwraca DTO SocialPost, nie modele Eloquenta.
 */
class MarkdownSocialPostRepository
{
    public function __construct(private MarkdownSocialPostParser $parser)
    {
    }

    public function directory(): string
    {
        return rtrim((string) config('social.path'), '/\\');
    }

    /**
     * Sanityzuje slug do bezpiecznej nazwy pliku (ochrona przed path traversal).
     */
    public function normalizeSlug(string $slug): string
    {
        $slug = Str::slug($slug);

        return $slug !== '' ? $slug : 'post';
    }

    public function pathForSlug(string $slug): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . $this->normalizeSlug($slug) . '.md';
    }

    public function exists(string $slug): bool
    {
        return File::exists($this->pathForSlug($slug));
    }

    public function raw(string $slug): ?string
    {
        $path = $this->pathForSlug($slug);

        return File::exists($path) ? File::get($path) : null;
    }

    /**
     * Ścieżki wszystkich plików .md z postami (posortowane po nazwie).
     *
     * @return list<string>
     */
    public function files(): array
    {
        $dir = $this->directory();

        if (! File::isDirectory($dir)) {
            return [];
        }

        $files = collect(File::files($dir))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'md')
            ->map(fn ($file) => $file->getPathname())
            ->values()
            ->all();

        sort($files);

        return $files;
    }

    /**
     * Wszystkie posty z plików .md.
     *
     * @return Collection<int, SocialPost>
     *
     * @throws InvalidSocialPost gdy któryś plik jest uszkodzony
     */
    public function all(): Collection
    {
        return collect($this->files())->map(fn (string $path) => $this->fromPath($path))->values();
    }

    /**
     * @return Collection<int, SocialPost>
     */
    public function byStatus(string $status): Collection
    {
        return $this->all()->filter(fn (SocialPost $p) => $p->status === $status)->values();
    }

    /**
     * @return Collection<int, SocialPost>
     */
    public function byType(SocialPostType $type): Collection
    {
        return $this->all()->filter(fn (SocialPost $p) => $p->type === $type)->values();
    }

    public function findBySlug(string $slug): ?SocialPost
    {
        $raw = $this->raw($slug);

        if ($raw === null) {
            return null;
        }

        return $this->parser->toPost($raw, $this->normalizeSlug($slug));
    }

    /**
     * @throws InvalidSocialPost
     */
    public function fromPath(string $path): SocialPost
    {
        return $this->parser->toPost(
            File::get($path),
            pathinfo($path, PATHINFO_FILENAME),
        );
    }
}
