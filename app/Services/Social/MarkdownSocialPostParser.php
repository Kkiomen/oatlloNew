<?php

namespace App\Services\Social;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Parsuje plik .md posta social media na DTO SocialPost + listę slajdów.
 *
 * Różnica wobec MarkdownArticleParser: tam wystarczy "frontmatter + cały HTML".
 * Tutaj treść trzeba najpierw POCIĄĆ NA SLAJDY, a dopiero potem konwertować –
 * dlatego używamy FrontMatterParser (zwraca SUROWE body) zamiast ścieżki
 * MarkdownConverter -> HTML.
 *
 * Konwerter treści slajdu CELOWO nie ma FrontMatterExtension: slajd może zaczynać
 * się od `---` (zwykły <hr>) i rozszerzenie zjadłoby go jako frontmatter.
 */
class MarkdownSocialPostParser
{
    /**
     * Separator slajdów. Komentarz HTML – zero kolizji z jakąkolwiek konstrukcją
     * Markdowna, plik dalej czyta się jako jeden dokument, a atrybuty pozwalają
     * rozszerzać format bez jego łamania (`<!-- slide role="cta" -->`).
     *
     * NIE używamy `---` (nieodróżnialne od frontmattera i chcemy zachować <hr>)
     * ani `===` (to setext H1 – zjada poprzednią linię).
     */
    private const SLIDE_SEPARATOR = '/^[ \t]*<!--[ \t]*slide\b([^>]*?)-->[ \t]*$/mi';

    /**
     * Klucze dozwolone we frontmatterze. Wszystko poza tym to literówka –
     * zgłasza je lint (`social:lint`), bo cicho ignorowany klucz to najgorszy
     * możliwy tryb awarii.
     */
    public const FRONTMATTER_KEYS = [
        'slug', 'type', 'language', 'title', 'topic', 'style',
        'source_type', 'source', 'link',
        'publish_at', 'status', 'formats', 'hashtags', 'caption', 'notes', 'verified',
    ];

    private FrontMatterParser $frontMatter;

    private MarkdownConverter $converter;

    public function __construct()
    {
        $this->frontMatter = new FrontMatterParser(new SymfonyYamlFrontMatterParser());

        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Rozdziela frontmatter od SUROWEJ treści (bez konwersji na HTML).
     *
     * @return array{frontmatter: array<string,mixed>, body: string}
     */
    public function parse(string $raw): array
    {
        $result = $this->frontMatter->parse($this->normalizeEncoding($raw));

        $frontmatter = $result->getFrontMatter();

        return [
            'frontmatter' => is_array($frontmatter) ? $frontmatter : [],
            'body'        => $result->getContent(),
        ];
    }

    /**
     * Buduje DTO posta z surowej zawartości pliku .md.
     *
     * @param string      $raw          Zawartość pliku (z frontmatterem)
     * @param string|null $slugFallback Slug, gdy brak we frontmatterze (np. z nazwy pliku)
     *
     * @throws InvalidSocialPost
     */
    public function toPost(string $raw, ?string $slugFallback = null): SocialPost
    {
        ['frontmatter' => $fm, 'body' => $body] = $this->parse($raw);

        $slug = $this->stringOrNull($fm['slug'] ?? null) ?? $slugFallback ?? 'post';
        $type = $this->resolveType($fm['type'] ?? null, $slug);

        return new SocialPost(
            slug: $slug,
            type: $type,
            language: $this->stringOrNull($fm['language'] ?? null) ?? (string) config('social.default_language', 'en'),
            title: $this->stringOrNull($fm['title'] ?? null) ?? Str::headline($slug),
            topic: $this->stringOrNull($fm['topic'] ?? null),
            style: $this->stringOrNull($fm['style'] ?? null),
            sourceType: $this->stringOrNull($fm['source_type'] ?? null),
            source: $this->stringOrNull($fm['source'] ?? null),
            link: $this->stringOrNull($fm['link'] ?? null),
            publishAt: $this->toDate($fm['publish_at'] ?? null, $slug),
            status: $this->stringOrNull($fm['status'] ?? null) ?? SocialPost::STATUS_DRAFT,
            hashtags: $this->normalizeHashtags($fm['hashtags'] ?? []),
            formats: $this->normalizeFormats($fm['formats'] ?? null, $type),
            caption: $this->normalizeBlock($fm['caption'] ?? null),
            notes: $this->normalizeBlock($fm['notes'] ?? null),
            verified: \App\Services\Social\Review\SocialVerification::fromFrontmatter($fm['verified'] ?? null),
            slides: $this->splitSlides($body),
        );
    }

    /**
     * Tnie treść na slajdy po separatorze. Slajd 1 to wszystko przed pierwszym
     * markerem. W obrębie slajdu pierwszy nagłówek `##` staje się headline'em,
     * reszta idzie przez CommonMark.
     *
     * @return list<SocialSlide>
     */
    private function splitSlides(string $body): array
    {
        $parts = preg_split(self::SLIDE_SEPARATOR, $body, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            $parts = [$body];
        }

        // preg_split z DELIM_CAPTURE zwraca: [treść0, atrybuty1, treść1, atrybuty2, treść2, ...]
        $chunks = [['attrs' => '', 'markdown' => array_shift($parts) ?? '']];

        while ($parts !== []) {
            $attrs = array_shift($parts) ?? '';
            $markdown = array_shift($parts) ?? '';
            $chunks[] = ['attrs' => $attrs, 'markdown' => $markdown];
        }

        // Puste sekcje (np. marker na końcu pliku) nie są slajdami.
        $chunks = array_values(array_filter($chunks, fn (array $c) => trim($c['markdown']) !== ''));

        $total = count($chunks);
        $slides = [];

        foreach ($chunks as $i => $chunk) {
            $index = $i + 1;
            [$headline, $rest] = $this->extractHeadline($chunk['markdown']);

            // `headline` i `markdown` są ROZŁĄCZNE: nagłówek ma własny budżet
            // znaków, więc nie może się doliczać do budżetu treści.
            $slides[] = new SocialSlide(
                index: $index,
                total: $total,
                headline: $headline,
                markdown: trim($rest),
                html: trim($rest) === '' ? '' : trim($this->converter->convert($rest)->getContent()),
                role: $this->resolveRole($chunk['attrs'], $index, $total),
            );
        }

        return $slides;
    }

    /**
     * Wyciąga pierwszy nagłówek `##` slajdu (musi być pierwszą niepustą linią).
     *
     * @return array{0: string|null, 1: string}
     */
    private function extractHeadline(string $markdown): array
    {
        $markdown = ltrim($markdown, "\r\n");
        $lines = preg_split('/\R/', $markdown) ?: [];

        foreach ($lines as $i => $line) {
            if (trim($line) === '') {
                continue;
            }

            if (preg_match('/^[ \t]{0,3}##[ \t]+(.+?)[ \t]*#*[ \t]*$/', $line, $m)) {
                unset($lines[$i]);

                return [trim($m[1]), implode("\n", $lines)];
            }

            break; // pierwsza niepusta linia nie jest nagłówkiem – slajd bez headline'u
        }

        return [null, $markdown];
    }

    /**
     * Rola slajdu: jawny atrybut `role="..."` wygrywa, inaczej wynika z pozycji.
     */
    private function resolveRole(string $attrs, int $index, int $total): string
    {
        if (preg_match('/\brole\s*=\s*["\']?([a-z]+)["\']?/i', $attrs, $m)) {
            $role = strtolower($m[1]);
            if (in_array($role, [SocialSlide::ROLE_HOOK, SocialSlide::ROLE_BODY, SocialSlide::ROLE_CTA], true)) {
                return $role;
            }
        }

        if ($index === 1) {
            return SocialSlide::ROLE_HOOK;
        }

        return $index === $total ? SocialSlide::ROLE_CTA : SocialSlide::ROLE_BODY;
    }

    /**
     * @throws InvalidSocialPost
     */
    private function resolveType(mixed $type, string $slug): SocialPostType
    {
        if (! is_string($type) || $type === '') {
            throw InvalidSocialPost::unknownType($slug, $type);
        }

        return SocialPostType::tryFrom(strtolower(trim($type)))
            ?? throw InvalidSocialPost::unknownType($slug, $type);
    }

    /**
     * Hashtagi: akceptujemy listę lub string ("a, b" / "#a #b"). Zawsze bez '#'.
     *
     * @return list<string>
     */
    private function normalizeHashtags(mixed $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[\s,]+/', $tags) ?: [];
        }

        if (! is_array($tags)) {
            return [];
        }

        $normalized = [];
        foreach ($tags as $tag) {
            if (! is_scalar($tag)) {
                continue;
            }
            $tag = ltrim(trim((string) $tag), '#');
            if ($tag !== '') {
                $normalized[] = $tag;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Formaty publikacji. Akceptujemy listę albo string ("post, reel") – tak samo
     * jak hashtagi, bo ręcznie pisze się jedno i drugie.
     *
     * Brak pola => domyślny zestaw z typu (story => story, reszta => post). Dzięki
     * temu istniejące posty nie wymagają edycji ani migracji, a `formats` zostaje
     * polem opcjonalnym.
     *
     * NIE walidujemy tu nazw – od tego jest lint. Parser ma zbudować DTO nawet
     * z pliku z literówką, inaczej `social:lint` nie miałby czego zgłosić.
     *
     * @return list<string>
     */
    private function normalizeFormats(mixed $formats, SocialPostType $type): array
    {
        if (is_string($formats)) {
            $formats = preg_split('/[\s,]+/', $formats) ?: [];
        }

        if (! is_array($formats) || $formats === []) {
            return $this->defaultFormats($type);
        }

        $normalized = [];
        foreach ($formats as $format) {
            if (! is_scalar($format)) {
                continue;
            }
            $format = strtolower(trim((string) $format));
            if ($format !== '') {
                $normalized[] = $format;
            }
        }

        return $normalized === [] ? $this->defaultFormats($type) : array_values(array_unique($normalized));
    }

    /**
     * @return list<string>
     */
    private function defaultFormats(SocialPostType $type): array
    {
        $map = (array) config('social.default_formats', []);

        $default = $map[$type->value] ?? $map['default'] ?? ['post'];

        return array_values(array_map('strval', (array) $default));
    }

    /** Wielolinijkowy blok tekstu z frontmattera (caption, notes). */
    private function normalizeBlock(mixed $value): string
    {
        if (is_scalar($value)) {
            return rtrim((string) $value);
        }

        return '';
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Data z frontmattera (string / timestamp / DateTime) na CarbonImmutable.
     * Guard przeniesiony z MarkdownArticleParser.
     *
     * @throws InvalidSocialPost
     */
    private function toDate(mixed $value, string $slug): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        try {
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                return CarbonImmutable::createFromTimestamp((int) $value);
            }

            return CarbonImmutable::parse((string) $value);
        } catch (\Throwable $e) {
            throw InvalidSocialPost::badDate($slug, 'publish_at', $e->getMessage());
        }
    }

    /**
     * Normalizuje kodowanie do poprawnego UTF-8 (BOM, inne strony kodowe).
     * Guard przeniesiony z MarkdownArticleParser – CommonMark odrzuca nie-UTF-8.
     */
    private function normalizeEncoding(string $raw): string
    {
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        if (! mb_check_encoding($raw, 'UTF-8')) {
            $detected = mb_detect_encoding($raw, ['UTF-8', 'ISO-8859-2', 'Windows-1252', 'ISO-8859-1'], true) ?: 'ISO-8859-2';
            $raw = mb_convert_encoding($raw, 'UTF-8', $detected);
        }

        return $raw;
    }
}
