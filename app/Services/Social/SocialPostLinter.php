<?php

namespace App\Services\Social;

/**
 * Waliduje posty social media przed eksportem.
 *
 * Podział na ERROR/WARNING jest celowy:
 *  - ERROR   = Instagram odrzuci albo grafika się nie zbuduje,
 *  - WARNING = zbuduje się, ale prawdopodobnie brzydko (rozpycha layout, gubi font).
 *
 * Overflow tekstu to NIE błąd renderu, tylko autorski: CSS zawija naprawdę, więc
 * za długi tekst rozpycha kanwę zamiast rzucić wyjątek. Dlatego budżety siedzą tutaj.
 */
class SocialPostLinter
{
    /**
     * Glify, które WYPADAJĄ z subsetu latin naszego woff2 (unicode-range nie
     * obejmuje U+2190/U+2192) – w środku nagłówka podmieniają się po cichu na
     * font systemowy. To błąd, nie kwestia gustu.
     */
    private const GLYPHS_OUT_OF_FONT = [
        '→' => '->',
        '←' => '<-',
        '↔' => '<->',
        '⇒' => '=>',
    ];

    /**
     * Glify obecne w foncie, ale niezgodne ze stylem domu – ContentSanitizer
     * zamienia je w artykułach na '-' (patrz CLAUDE.md, pass anty-AI).
     */
    private const GLYPHS_OFF_STYLE = [
        '—' => '-',
        '–' => '-',
    ];

    public function __construct(
        private MarkdownSocialPostParser $parser,
        private SocialStyleResolver $styles,
    ) {
    }

    /**
     * Waliduje surową zawartość pliku. Łapie też błędy parsowania, żeby jeden
     * zepsuty plik nie wywalał całego przebiegu lintu.
     *
     * @return list<SocialLintIssue>
     */
    public function lintRaw(string $raw, string $slugFallback): array
    {
        try {
            $post = $this->parser->toPost($raw, $slugFallback);
        } catch (InvalidSocialPost $e) {
            return [SocialLintIssue::error($slugFallback, $e->getMessage())];
        }

        return array_merge(
            $this->lintFrontmatterKeys($raw, $post),
            $this->lintPost($post),
        );
    }

    /**
     * @return list<SocialLintIssue>
     */
    public function lintPost(SocialPost $post): array
    {
        return array_merge(
            $this->lintStatus($post),
            $this->lintStyle($post),
            $this->lintFormats($post),
            $this->lintSlideCount($post),
            $this->lintCaption($post),
            $this->lintHashtags($post),
            $this->lintLink($post),
            $this->lintGlyphs($post),
            $this->lintBudgets($post),
        );
    }

    /**
     * Literówka w `style:` byłaby cicho zignorowana (resolver wróciłby do
     * automatu), a autor myślałby, że wymusił skórkę.
     *
     * @return list<SocialLintIssue>
     */
    private function lintStyle(SocialPost $post): array
    {
        if ($post->style === null || $this->styles->exists($post->style)) {
            return [];
        }

        return [SocialLintIssue::error(
            $post->slug,
            "Nieznany style '{$post->style}'. Dostępne: " . implode(', ', $this->styles->names()) . '.',
        )];
    }

    /**
     * Literówka w `formats:` (np. `reels`) wypadłaby z kalendarza BEZ ŚLADU –
     * autor byłby przekonany, że zaplanował reela, a dzień świeciłby pustką.
     *
     * @return list<SocialLintIssue>
     */
    private function lintFormats(SocialPost $post): array
    {
        $known = array_keys((array) config('social.formats', []));
        $unknown = array_values(array_diff($post->formats, $known));

        if ($unknown === []) {
            return [];
        }

        return [SocialLintIssue::error(
            $post->slug,
            "Nieznany format '" . implode("', '", $unknown) . "'. Dostępne: " . implode(', ', $known) . '.',
        )];
    }

    /**
     * Nieznany klucz to prawie zawsze literówka. Cicho ignorowany klucz jest
     * najgorszym trybem awarii – autor myśli, że coś ustawił.
     *
     * @return list<SocialLintIssue>
     */
    private function lintFrontmatterKeys(string $raw, SocialPost $post): array
    {
        $fm = $this->parser->parse($raw)['frontmatter'];
        $unknown = array_diff(array_keys($fm), MarkdownSocialPostParser::FRONTMATTER_KEYS);

        if ($unknown === []) {
            return [];
        }

        return [SocialLintIssue::error(
            $post->slug,
            'Nieznane klucze frontmattera: ' . implode(', ', $unknown)
                . '. Dozwolone: ' . implode(', ', MarkdownSocialPostParser::FRONTMATTER_KEYS) . '.',
        )];
    }

    /**
     * @return list<SocialLintIssue>
     */
    private function lintStatus(SocialPost $post): array
    {
        if (in_array($post->status, SocialPost::STATUSES, true)) {
            return [];
        }

        return [SocialLintIssue::error(
            $post->slug,
            "Nieznany status '{$post->status}'. Dozwolone: " . implode(', ', SocialPost::STATUSES) . '.',
        )];
    }

    /**
     * @return list<SocialLintIssue>
     */
    private function lintSlideCount(SocialPost $post): array
    {
        [$min, $max] = $post->type->slideRange();
        $count = $post->slideCount();

        if ($count >= $min && $count <= $max) {
            return [];
        }

        $expected = $min === $max
            ? "dokładnie {$min}"
            : "od {$min} do {$max}";

        return [SocialLintIssue::error(
            $post->slug,
            "Typ '{$post->type->value}' wymaga {$expected} slajdów, jest {$count}."
                . ($count < $min ? ' Slajdy rozdziela się markerem <!-- slide -->.' : ''),
        )];
    }

    /**
     * @return list<SocialLintIssue>
     */
    private function lintCaption(SocialPost $post): array
    {
        $issues = [];
        $max = (int) config('social.limits.caption_max', 2200);
        $length = mb_strlen($post->captionWithHashtags());

        if ($length > $max) {
            $issues[] = SocialLintIssue::error(
                $post->slug,
                "Caption + hashtagi mają {$length} znaków, limit Instagrama to {$max}.",
            );
        }

        if (trim($post->caption) === '' && $post->isReady()) {
            $issues[] = SocialLintIssue::error($post->slug, 'Post ma status ready, ale pusty caption.');
        }

        $hookMax = (int) config('social.limits.caption_hook_max', 125);
        $hookLength = mb_strlen($post->captionHook());

        if ($hookLength > $hookMax) {
            $issues[] = SocialLintIssue::warning(
                $post->slug,
                "Pierwsza linia captionu ma {$hookLength} znaków – po {$hookMax} Instagram ucina na '... more'.",
            );
        }

        return $issues;
    }

    /**
     * @return list<SocialLintIssue>
     */
    private function lintHashtags(SocialPost $post): array
    {
        $max = (int) config('social.limits.hashtags_max', 30);
        $count = count($post->hashtags);

        if ($count > $max) {
            return [SocialLintIssue::error($post->slug, "Hashtagów jest {$count}, limit Instagrama to {$max}.")];
        }

        return [];
    }

    /**
     * @return list<SocialLintIssue>
     */
    private function lintLink(SocialPost $post): array
    {
        if ($post->link === null) {
            return [];
        }

        if (! str_starts_with($post->link, 'https://')) {
            return [SocialLintIssue::error($post->slug, "Link '{$post->link}' musi być https://.")];
        }

        return [];
    }

    /**
     * @return list<SocialLintIssue>
     */
    private function lintGlyphs(SocialPost $post): array
    {
        $issues = [];
        $haystack = $post->caption;

        foreach ($post->slides as $slide) {
            $haystack .= ' ' . (string) $slide->headline . ' ' . $slide->markdown;
        }

        foreach (self::GLYPHS_OUT_OF_FONT as $glyph => $replacement) {
            if (str_contains($haystack, $glyph)) {
                $issues[] = SocialLintIssue::error(
                    $post->slug,
                    "Znak '{$glyph}' nie istnieje w subsecie latin naszego woff2 – wypadnie do fontu "
                        . "systemowego w środku linii. Użyj '{$replacement}'.",
                );
            }
        }

        foreach (self::GLYPHS_OFF_STYLE as $glyph => $replacement) {
            if (str_contains($haystack, $glyph)) {
                $issues[] = SocialLintIssue::warning(
                    $post->slug,
                    "Znak '{$glyph}' jest niezgodny ze stylem domu (ContentSanitizer zamienia go w artykułach). Użyj '{$replacement}'.",
                );
            }
        }

        return $issues;
    }

    /**
     * Budżety czytelności. Przekroczenie = tekst rozepchnie kanwę (widok ma
     * overflow:hidden, więc po prostu zniknie poza krawędzią).
     *
     * @return list<SocialLintIssue>
     */
    private function lintBudgets(SocialPost $post): array
    {
        $issues = [];
        $limits = (array) config('social.limits', []);

        foreach ($post->slides as $slide) {
            $where = "slajd {$slide->index}";

            $headlineMax = (int) ($slide->isHook()
                ? ($limits['hook_headline_max'] ?? 70)
                : ($limits['body_headline_max'] ?? 55));

            if ($slide->headline === null) {
                if ($slide->isHook()) {
                    $issues[] = SocialLintIssue::warning(
                        $post->slug,
                        "{$where}: brak nagłówka (##). Hook jest miniaturą w feedzie – bez nagłówka nikt nie kliknie.",
                    );
                }
            } elseif (mb_strlen($slide->headline) > $headlineMax) {
                $issues[] = SocialLintIssue::warning(
                    $post->slug,
                    "{$where}: nagłówek ma " . mb_strlen($slide->headline) . " znaków, budżet to {$headlineMax}. Skróć, nie zmniejszaj fontu.",
                );
            }

            $textMax = (int) ($limits['body_text_max'] ?? 180);
            if ($slide->textLength() > $textMax) {
                $issues[] = SocialLintIssue::warning(
                    $post->slug,
                    "{$where}: treść ma {$slide->textLength()} znaków, budżet to {$textMax}. Jeden slajd = jedna myśl.",
                );
            }

            $issues = array_merge($issues, $this->lintCode($post, $slide, $where, $limits));
        }

        return $issues;
    }

    /**
     * @param  array<string,mixed>  $limits
     * @return list<SocialLintIssue>
     */
    private function lintCode(SocialPost $post, SocialSlide $slide, string $where, array $limits): array
    {
        $issues = [];
        $linesMax = (int) ($limits['code_lines_max'] ?? 8);
        $colsMax = (int) ($limits['code_cols_max'] ?? 52);

        foreach ($slide->codeBlocks() as $code) {
            $lines = preg_split('/\R/', $code) ?: [];

            if (count($lines) > $linesMax) {
                $issues[] = SocialLintIssue::warning(
                    $post->slug,
                    "{$where}: blok kodu ma " . count($lines) . " linii, budżet to {$linesMax}.",
                );
            }

            $longest = 0;
            foreach ($lines as $line) {
                $longest = max($longest, mb_strlen(rtrim($line)));
            }

            if ($longest > $colsMax) {
                $issues[] = SocialLintIssue::warning(
                    $post->slug,
                    "{$where}: najdłuższa linia kodu ma {$longest} znaków, budżet to {$colsMax} – reszta wyjedzie poza kanwę.",
                );
            }
        }

        return $issues;
    }
}
