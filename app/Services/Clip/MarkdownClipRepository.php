<?php

namespace App\Services\Clip;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Odczyt scenariuszy clipów z plików .md. Wzorowane na
 * MarkdownSocialPostRepository — CELOWO bez save()/delete(): scenariusze pisze
 * się lokalnie i wypycha commitem. Jedynym writerem jest git. Zero bazy.
 */
class MarkdownClipRepository
{
    public function __construct(private MarkdownClipParser $parser)
    {
    }

    public function directory(): string
    {
        return rtrim((string) config('clip.path'), '/\\');
    }

    public function normalizeSlug(string $slug): string
    {
        $slug = Str::slug($slug);

        return $slug !== '' ? $slug : 'clip';
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
     * @return Collection<int, Clip>
     *
     * @throws InvalidClip
     */
    public function all(): Collection
    {
        return collect($this->files())->map(fn (string $path) => $this->fromPath($path))->values();
    }

    public function findBySlug(string $slug): ?Clip
    {
        $raw = $this->raw($slug);

        if ($raw === null) {
            return null;
        }

        return $this->parser->toClip($raw, $this->normalizeSlug($slug));
    }

    /**
     * @throws InvalidClip
     */
    public function fromPath(string $path): Clip
    {
        return $this->parser->toClip(File::get($path), pathinfo($path, PATHINFO_FILENAME));
    }
}
