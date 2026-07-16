<?php

namespace App\Services\Clip;

use Illuminate\Support\Str;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Parsuje plik .md scenariusza na DTO Clip + listę scen.
 *
 * Format: frontmatter globalny (slug, title, topic, voice…) + ciało z scenami
 * rozdzielonymi `<!-- scene -->`. KAŻDA scena to blok YAML — w pełni strukturalny,
 * bo scena niesie strukturalne dane (type, highlight: [3], items: [...], code: |).
 * To inaczej niż w postach (tam slajd to Markdown), ale scena clipa to spec
 * animacji, nie proza — YAML pasuje lepiej i Claude autoryzuje go niezawodnie.
 *
 * Separator `<!-- scene -->` — jak `<!-- slide -->` w postach: komentarz HTML,
 * zero kolizji z Markdownem. NIE `---` (nieodróżnialne od frontmattera).
 */
class MarkdownClipParser
{
    /**
     * @see MarkdownSocialPostParser — ten sam wzorzec separatora z atrybutami.
     */
    private const SCENE_SEPARATOR = '/^[ \t]*<!--[ \t]*scene\b([^>]*?)-->[ \t]*$/mi';

    /**
     * Klucze dozwolone we frontmatterze GLOBALNYM. Wszystko poza tym to literówka
     * — zgłasza ją lint (cicho ignorowany klucz to najgorszy tryb awarii).
     */
    public const FRONTMATTER_KEYS = [
        'slug', 'language', 'title', 'topic', 'voice',
        'source', 'music', 'platforms', 'caption', 'hashtags',
    ];

    /**
     * Klucze zarezerwowane w scenie (reszta wpada do `params`).
     */
    private const SCENE_RESERVED = ['type', 'narration', 'sfx'];

    private FrontMatterParser $frontMatter;

    public function __construct()
    {
        $this->frontMatter = new FrontMatterParser(new SymfonyYamlFrontMatterParser());
    }

    /**
     * Rozdziela frontmatter od surowej treści (sceny).
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
     * Buduje DTO clipa z surowej zawartości pliku .md.
     *
     * @throws InvalidClip gdy któraś scena ma niepoprawny YAML
     */
    public function toClip(string $raw, ?string $slugFallback = null): Clip
    {
        ['frontmatter' => $fm, 'body' => $body] = $this->parse($raw);

        $slug = $this->stringOrNull($fm['slug'] ?? null) ?? $slugFallback ?? 'clip';

        return new Clip(
            slug: $slug,
            language: $this->stringOrNull($fm['language'] ?? null) ?? (string) config('clip.default_language', 'en'),
            title: $this->stringOrNull($fm['title'] ?? null) ?? Str::headline($slug),
            topic: $this->stringOrNull($fm['topic'] ?? null),
            voice: $this->stringOrNull($fm['voice'] ?? null) ?? (string) config('clip.default_voice', 'narrator_en'),
            source: $this->stringOrNull($fm['source'] ?? null),
            music: $this->normalizeMusic($fm['music'] ?? null),
            platforms: $this->normalizeList($fm['platforms'] ?? null),
            caption: $this->normalizeBlock($fm['caption'] ?? null),
            hashtags: $this->normalizeHashtags($fm['hashtags'] ?? []),
            scenes: $this->splitScenes($body, $slug),
        );
    }

    /**
     * Tnie ciało na sceny po separatorze i parsuje każdą jako YAML.
     *
     * Chunk przed pierwszym markerem, jeśli niepusty, jest sceną 1 — marker przed
     * pierwszą sceną jest więc OPCJONALNY (jak w postach slajd 1 bez markera).
     *
     * @return list<ClipScene>
     *
     * @throws InvalidClip
     */
    private function splitScenes(string $body, string $slug): array
    {
        $parts = preg_split(self::SCENE_SEPARATOR, $body, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            $parts = [$body];
        }

        // DELIM_CAPTURE zwraca [treść0, atrybuty1, treść1, atrybuty2, ...].
        // Bierzemy tylko treści (co drugi element od 0), atrybuty na razie nieużywane.
        $chunks = [];
        for ($i = 0; $i < count($parts); $i += 2) {
            $chunk = $parts[$i];
            if (trim((string) $chunk) !== '') {
                $chunks[] = (string) $chunk;
            }
        }

        $total = count($chunks);
        $scenes = [];

        foreach ($chunks as $i => $chunk) {
            $index = $i + 1;
            $data = $this->parseSceneYaml($chunk, $slug, $index);

            $type = $this->stringOrNull($data['type'] ?? null) ?? '';
            $narration = is_scalar($data['narration'] ?? null) ? trim((string) $data['narration']) : '';
            $sfx = $this->normalizeSfx($data['sfx'] ?? null);

            $params = array_diff_key($data, array_flip(self::SCENE_RESERVED));

            $scenes[] = new ClipScene(
                index: $index,
                total: $total,
                type: strtolower($type),
                narration: $narration,
                params: $params,
                sfx: $sfx,
            );
        }

        return $scenes;
    }

    /**
     * @return array<string,mixed>
     *
     * @throws InvalidClip
     */
    private function parseSceneYaml(string $chunk, string $slug, int $index): array
    {
        try {
            $data = Yaml::parse(trim($chunk));
        } catch (ParseException $e) {
            throw InvalidClip::badSceneYaml($slug, $index, $e->getMessage());
        }

        if (! is_array($data)) {
            throw InvalidClip::badSceneYaml($slug, $index, 'scena musi być mapą klucz: wartość');
        }

        return $data;
    }

    /**
     * SFX: string "whoosh" | lista stringów | lista map {name, at}. Zawsze
     * normalizowane do listy cue'ów {name, at(float)}.
     *
     * @return list<array{name:string,at:float}>
     */
    private function normalizeSfx(mixed $sfx): array
    {
        if ($sfx === null || $sfx === '') {
            return [];
        }

        if (is_scalar($sfx)) {
            return [['name' => trim((string) $sfx), 'at' => 0.0]];
        }

        if (! is_array($sfx)) {
            return [];
        }

        $cues = [];
        foreach ($sfx as $cue) {
            if (is_scalar($cue)) {
                $name = trim((string) $cue);
                if ($name !== '') {
                    $cues[] = ['name' => $name, 'at' => 0.0];
                }
            } elseif (is_array($cue) && isset($cue['name']) && is_scalar($cue['name'])) {
                $name = trim((string) $cue['name']);
                if ($name !== '') {
                    $cues[] = ['name' => $name, 'at' => (float) ($cue['at'] ?? 0.0)];
                }
            }
        }

        return $cues;
    }

    /**
     * @return list<string>
     */
    private function normalizeList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_scalar($item)) {
                $item = strtolower(trim((string) $item));
                if ($item !== '') {
                    $out[] = $item;
                }
            }
        }

        return array_values(array_unique($out));
    }

    /**
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

        $out = [];
        foreach ($tags as $tag) {
            if (! is_scalar($tag)) {
                continue;
            }
            $tag = ltrim(trim((string) $tag), '#');
            if ($tag !== '') {
                $out[] = $tag;
            }
        }

        return array_values(array_unique($out));
    }

    /** `music: none` / pusto => null; inaczej klucz z config('clip.music'). */
    private function normalizeMusic(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        return ($value === null || strtolower($value) === 'none') ? null : $value;
    }

    private function normalizeBlock(mixed $value): string
    {
        return is_scalar($value) ? rtrim((string) $value) : '';
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
     * Normalizuje kodowanie do UTF-8 (BOM, inne strony kodowe). Guard z
     * MarkdownSocialPostParser — YAML/CommonMark odrzucają nie-UTF-8.
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
