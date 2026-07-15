<?php

namespace App\Services\Social\Review;

use App\Services\Social\MarkdownSocialPostParser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Odczyt/zapis recenzji z plików .md w resources/social/reviews/.
 *
 * W przeciwieństwie do MarkdownSocialPostRepository ten repozytorium MA save() –
 * werdykt klika człowiek w panelu, a nie pisze go w edytorze. To jedyne miejsce
 * w module social, które zapisuje pliki, i celowo dotyka WYŁĄCZNIE katalogu
 * recenzji: pliku posta nie rusza nikt poza autorem.
 *
 * Katalog `reviews/` leży wewnątrz resources/social, ale MarkdownSocialPostRepository
 * czyta katalog płasko (File::files, nie allFiles), więc recenzje nigdy nie zostaną
 * wzięte za posty.
 *
 * Powód poprawki trzymamy w CIELE pliku, nie we frontmatterze – wielolinijkowy
 * tekst od człowieka nie musi wtedy przechodzić przez escaping YAML-a.
 */
class SocialReviewRepository
{
    public function __construct(private MarkdownSocialPostParser $parser)
    {
    }

    /**
     * Skrót treści pliku posta – tożsamość WERSJI, którą recenzent widział.
     */
    public static function fingerprint(string $raw): string
    {
        return sha1(preg_replace('/\R/', "\n", $raw) ?? $raw);
    }

    public function directory(): string
    {
        return rtrim((string) config('social.reviews_path'), '/\\');
    }

    public function pathForSlug(string $slug): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . $this->normalizeSlug($slug) . '.md';
    }

    public function exists(string $slug): bool
    {
        return File::exists($this->pathForSlug($slug));
    }

    public function find(string $slug): ?SocialReview
    {
        $path = $this->pathForSlug($slug);

        return File::exists($path) ? $this->fromPath($path) : null;
    }

    /**
     * Wszystkie recenzje, kluczowane slugiem posta.
     *
     * @return Collection<string, SocialReview>
     */
    public function all(): Collection
    {
        $dir = $this->directory();

        if (! File::isDirectory($dir)) {
            return collect();
        }

        return collect(File::files($dir))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'md')
            ->map(fn ($file) => $this->fromPath($file->getPathname()))
            ->filter()
            ->keyBy(fn (SocialReview $review) => $review->slug);
    }

    public function save(SocialReview $review): string
    {
        $path = $this->pathForSlug($review->slug);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->toMarkdown($review));

        return $path;
    }

    /**
     * Kasuje recenzję – post wraca do kolejki jako nieprzejrzany.
     */
    public function forget(string $slug): bool
    {
        $path = $this->pathForSlug($slug);

        return File::exists($path) && File::delete($path);
    }

    /**
     * Uszkodzony plik recenzji zwraca null, a nie wyjątek: brak recenzji oznacza
     * "do przejrzenia", czyli najgorsze co się stanie to ponowne pytanie człowieka.
     * Wywalenie panelu przez literówkę w notatce byłoby gorsze.
     */
    private function fromPath(string $path): ?SocialReview
    {
        try {
            ['frontmatter' => $fm, 'body' => $body] = $this->parser->parse(File::get($path));

            $verdict = SocialReviewVerdict::tryParse($fm['verdict'] ?? null);

            if ($verdict === null) {
                return null;
            }

            return new SocialReview(
                slug: $this->normalizeSlug((string) ($fm['slug'] ?? pathinfo($path, PATHINFO_FILENAME))),
                verdict: $verdict,
                reason: trim($body),
                reviewedAt: $this->toDate($fm['reviewed_at'] ?? null),
                fingerprint: is_scalar($fm['fingerprint'] ?? null) ? trim((string) $fm['fingerprint']) : '',
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function toMarkdown(SocialReview $review): string
    {
        $reviewedAt = ($review->reviewedAt ?? CarbonImmutable::now())->format('Y-m-d H:i');

        $frontmatter = implode("\n", [
            '---',
            'slug: ' . $review->slug,
            'verdict: ' . $review->verdict->value,
            'reviewed_at: ' . $reviewedAt,
            'fingerprint: ' . $review->fingerprint,
            '---',
        ]);

        $reason = trim($review->reason);

        if ($reason === '') {
            $reason = $review->verdict === SocialReviewVerdict::Approved
                ? 'Zaakceptowane w panelu recenzji, bez uwag.'
                : 'Bez podanego powodu.';
        }

        return $frontmatter . "\n\n" . $reason . "\n";
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = Str::slug($slug);

        return $slug !== '' ? $slug : 'post';
    }

    private function toDate(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        try {
            return CarbonImmutable::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
