<?php

namespace App\Services\Clip;

/**
 * Waliduje scenariusze clipów przed renderem.
 *
 * Podział ERROR/WARNING jak w SocialPostLinter:
 *  - ERROR   = clip nie wyrenderuje się poprawnie (nieznany typ sceny, brak
 *              narracji, glif spoza fontu w napisach). Blokuje render — inaczej
 *              stracisz kilka minut renderu, zanim zobaczysz problem.
 *  - WARNING = wyrenderuje się, ale prawdopodobnie źle (kod za krawędź, za długa
 *              narracja, SFX/głos bez pliku).
 *
 * Overflow tekstu to błąd AUTORSKI, nie renderu: napisy i kod zawijają się /
 * przycinają, więc nadmiar znika za krawędzią zamiast rzucić wyjątek. Dlatego
 * budżety siedzą tutaj.
 */
class ClipLinter
{
    /**
     * Glify spoza subsetu latin naszego woff2 (unicode-range nie obejmuje
     * U+2190/U+2192) — w napisach podmieniają się po cichu na font systemowy.
     * Ten sam problem co w reelu/PNG (patrz CLAUDE.md).
     */
    private const GLYPHS_OUT_OF_FONT = ['→' => '->', '←' => '<-', '↔' => '<->', '⇒' => '=>'];

    /** Glify w foncie, ale niezgodne ze stylem domu (jak ContentSanitizer). */
    private const GLYPHS_OFF_STYLE = ['—' => '-', '–' => '-'];

    /**
     * Minimalna zawartość wizualna per typ sceny — bez niej scena jest pustą
     * kanwą z samą narracją (WARNING, nie ERROR: audio wciąż gra).
     */
    private const REQUIRED_PARAM = [
        'title'       => 'text',
        'statement'   => 'text',
        'code-reveal' => 'code',
        'terminal'    => 'code',
        'bullets'     => 'items',
        'callout'     => 'text',
        'outro'       => 'cta',
    ];

    public function __construct(private MarkdownClipParser $parser)
    {
    }

    /**
     * Waliduje surową zawartość pliku. Łapie błąd parsowania, żeby jeden zepsuty
     * plik nie wywalał całego przebiegu lintu.
     *
     * @return list<ClipLintIssue>
     */
    public function lintRaw(string $raw, string $slugFallback): array
    {
        try {
            $clip = $this->parser->toClip($raw, $slugFallback);
        } catch (InvalidClip $e) {
            return [ClipLintIssue::error($slugFallback, $e->getMessage())];
        }

        return array_merge(
            $this->lintFrontmatterKeys($raw, $clip),
            $this->lintClip($clip),
        );
    }

    /**
     * @return list<ClipLintIssue>
     */
    public function lintClip(Clip $clip): array
    {
        return array_merge(
            $this->lintSceneCount($clip),
            $this->lintVoice($clip),
            $this->lintMusic($clip),
            $this->lintTotalLength($clip),
            $this->lintScenes($clip),
        );
    }

    /**
     * @return list<ClipLintIssue>
     */
    private function lintFrontmatterKeys(string $raw, Clip $clip): array
    {
        ['frontmatter' => $fm] = $this->parser->parse($raw);

        $issues = [];
        foreach (array_keys($fm) as $key) {
            if (! in_array($key, MarkdownClipParser::FRONTMATTER_KEYS, true)) {
                $allowed = implode(', ', MarkdownClipParser::FRONTMATTER_KEYS);
                $issues[] = ClipLintIssue::error(
                    $clip->slug,
                    "Nieznany klucz frontmattera '{$key}'. Dozwolone: {$allowed}."
                );
            }
        }

        return $issues;
    }

    /**
     * @return list<ClipLintIssue>
     */
    private function lintSceneCount(Clip $clip): array
    {
        $min = (int) config('clip.limits.scenes_min', 2);
        $max = (int) config('clip.limits.scenes_max', 30);
        $count = $clip->sceneCount();

        if ($count < $min) {
            return [ClipLintIssue::error($clip->slug, "Za mało scen: {$count} (min {$min}).")];
        }

        if ($count > $max) {
            return [ClipLintIssue::error($clip->slug, "Za dużo scen: {$count} (max {$max}).")];
        }

        return [];
    }

    /**
     * @return list<ClipLintIssue>
     */
    private function lintVoice(Clip $clip): array
    {
        $voices = (array) config('clip.voices', []);

        if (! array_key_exists($clip->voice, $voices)) {
            $allowed = implode(', ', array_keys($voices)) ?: '(brak w config/clip.php)';

            return [ClipLintIssue::warning(
                $clip->slug,
                "Głos '{$clip->voice}' nie jest zdefiniowany. Dostępne: {$allowed}. "
                . 'Mock TTS zignoruje to, ale realny ElevenLabs nie znajdzie voice_id.'
            )];
        }

        return [];
    }

    /**
     * @return list<ClipLintIssue>
     */
    private function lintMusic(Clip $clip): array
    {
        if ($clip->music === null) {
            return [];
        }

        $music = (array) config('clip.music', []);

        if (! array_key_exists($clip->music, $music)) {
            return [ClipLintIssue::warning(
                $clip->slug,
                "Podkład '{$clip->music}' nie jest zdefiniowany w config('clip.music'). Clip zagra bez muzyki."
            )];
        }

        return [];
    }

    /**
     * @return list<ClipLintIssue>
     */
    private function lintTotalLength(Clip $clip): array
    {
        $max = (int) config('clip.limits.total_max_words', 320);
        $words = $clip->totalNarrationWords();

        if ($words > $max) {
            return [ClipLintIssue::warning(
                $clip->slug,
                "Narracja całego clipa to {$words} słów (>{$max}). Film będzie długi — rozważ skrócenie."
            )];
        }

        return [];
    }

    /**
     * @return list<ClipLintIssue>
     */
    private function lintScenes(Clip $clip): array
    {
        $types = (array) config('clip.scene_types', []);
        $sfxLibrary = (array) config('clip.sfx', []);
        $wordsMax = (int) config('clip.limits.narration_max_words', 45);
        $codeLines = (int) config('clip.limits.code_lines_max', 10);
        $codeCols = (int) config('clip.limits.code_cols_max', 46);

        $issues = [];

        foreach ($clip->scenes as $scene) {
            $where = "scena #{$scene->index}";

            // Nieznany typ = ERROR: literówka wypadłaby z wideo bez śladu.
            if ($scene->type === '') {
                $issues[] = ClipLintIssue::error($clip->slug, "{$where}: brak `type`.");
            } elseif (! in_array($scene->type, $types, true)) {
                $allowed = implode(', ', $types);
                $issues[] = ClipLintIssue::error(
                    $clip->slug,
                    "{$where}: nieznany type '{$scene->type}'. Dozwolone: {$allowed}."
                );
            }

            // Brak narracji = ERROR: scena bez głosu nie ma z czego liczyć długości
            // ani napisów. To fundament synchronizacji.
            if (! $scene->hasNarration()) {
                $issues[] = ClipLintIssue::error($clip->slug, "{$where}: brak `narration`.");
            } elseif ($scene->narrationWordCount() > $wordsMax) {
                $issues[] = ClipLintIssue::warning(
                    $clip->slug,
                    "{$where}: narracja {$scene->narrationWordCount()} słów (>{$wordsMax}) — scena będzie długa."
                );
            }

            // Wizual bez treści = WARNING (narracja i tak gra).
            $required = self::REQUIRED_PARAM[$scene->type] ?? null;
            if ($required !== null && $this->paramEmpty($scene, $required)) {
                $issues[] = ClipLintIssue::warning(
                    $clip->slug,
                    "{$where}: type '{$scene->type}' bez pola '{$required}' — pusta kanwa pod narracją."
                );
            }

            // Budżety kodu = WARNING (overflow autorski, nie rzuci wyjątku).
            if ($scene->codeLines() > $codeLines) {
                $issues[] = ClipLintIssue::warning(
                    $clip->slug,
                    "{$where}: {$scene->codeLines()} linii kodu (>{$codeLines}) — wyjedzie za kadr."
                );
            }
            if ($scene->codeMaxColumns() > $codeCols) {
                $issues[] = ClipLintIssue::warning(
                    $clip->slug,
                    "{$where}: kod ma {$scene->codeMaxColumns()} kolumn (>{$codeCols}) — utnie się z prawej."
                );
            }

            // SFX bez pliku = WARNING (scena gra bez efektu).
            foreach ($scene->sfx as $cue) {
                if (! array_key_exists($cue['name'], $sfxLibrary)) {
                    $issues[] = ClipLintIssue::warning(
                        $clip->slug,
                        "{$where}: SFX '{$cue['name']}' nie ma w config('clip.sfx') — zagra bez efektu."
                    );
                }
            }

            $issues = array_merge($issues, $this->lintGlyphs($clip->slug, $where, $scene));
        }

        return $issues;
    }

    /**
     * @return list<ClipLintIssue>
     */
    private function lintGlyphs(string $slug, string $where, ClipScene $scene): array
    {
        $issues = [];
        $text = implode(' ', $scene->visibleText());

        foreach (self::GLYPHS_OUT_OF_FONT as $glyph => $replacement) {
            if (str_contains($text, $glyph)) {
                $issues[] = ClipLintIssue::error(
                    $slug,
                    "{$where}: znak '{$glyph}' jest spoza fontu napisów — użyj '{$replacement}'."
                );
            }
        }

        foreach (self::GLYPHS_OFF_STYLE as $glyph => $replacement) {
            if (str_contains($text, $glyph)) {
                $issues[] = ClipLintIssue::warning(
                    $slug,
                    "{$where}: znak '{$glyph}' kłóci się ze stylem — rozważ '{$replacement}'."
                );
            }
        }

        return $issues;
    }

    private function paramEmpty(ClipScene $scene, string $key): bool
    {
        $value = $scene->param($key);

        if ($value === null) {
            return true;
        }

        if (is_array($value)) {
            return $value === [];
        }

        return is_scalar($value) && trim((string) $value) === '';
    }
}
