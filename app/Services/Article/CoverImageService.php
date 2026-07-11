<?php

namespace App\Services\Article;

use App\Models\Article;
use Illuminate\Support\Str;

/**
 * Generuje okładkę artykułu jako grafikę SVG w motywie "okno kodu".
 *
 * Okładka jest dobierana do tematu artykułu (kolor akcentu, nazwa pliku,
 * język "kodu") na podstawie kategorii, tagów i tytułu – dzięki temu grafika
 * pasuje do treści, zamiast być losowa. Konfiguracja motywów: config/covers.php.
 */
class CoverImageService
{
    private const WIDTH = 1200;
    private const HEIGHT = 630;

    // Obszar na tytuł wewnątrz "okna kodu".
    private const TEXT_X = 132;              // lewy margines tekstu
    private const USABLE_WIDTH = 940.0;      // dostępna szerokość linii (px)
    private const TITLE_CENTER_Y = 350.0;    // pion: środek bloku tytułu
    private const TITLE_HEIGHT_BUDGET = 210.0; // maks. wysokość bloku tytułu (px)
    private const MAX_TITLE_LINES = 4;
    private const CHAR_WIDTH_RATIO = 0.62;   // szerokość znaku monospace / rozmiar czcionki
    private const LINE_HEIGHT_RATIO = 1.25;

    // Rozmiary czcionki tytułu od największego do najmniejszego – wybieramy
    // największy, przy którym cały tytuł mieści się bez ucinania.
    private const FONT_SIZES = [58, 52, 46, 40, 34, 30];

    /**
     * Zwraca SVG (string) okładki dla artykułu.
     */
    public function renderForArticle(Article $article): string
    {
        return $this->render((string) $article->name, $this->resolveTheme($article));
    }

    /**
     * Renderuje SVG na podstawie tytułu i motywu. Rozmiar czcionki i liczba
     * linii są dobierane automatycznie, aby długi tytuł skalował się i mieścił
     * w oknie zamiast być ucinany.
     *
     * @param array<string, string> $theme
     */
    public function render(string $title, array $theme): string
    {
        $layout = $this->layoutTitle($title, $theme['comment']);

        return view('covers.code-window', [
            'title' => $title,
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'textX' => self::TEXT_X,
            'accent' => $theme['accent'],
            'filename' => $theme['filename'],
            'header' => $theme['header'],
            'comment' => $theme['comment'],
            'footer' => $theme['footer'],
            'label' => $theme['label'],
            'labelWidth' => max(90, strlen($theme['label']) * 15 + 44),
            'fontSize' => $layout['fontSize'],
            'titleLines' => $layout['lines'], // [['text' => ..., 'y' => ...], ...]
        ])->render();
    }

    /**
     * Dobiera rozmiar czcionki i rozmieszcza linie tytułu (word wrap + skalowanie).
     *
     * @return array{fontSize:int, lines:array<int, array{text:string, y:float}>}
     */
    private function layoutTitle(string $title, string $comment): array
    {
        $title = trim(preg_replace('/\s+/', ' ', $title));
        if ($title === '') {
            $title = 'Artykuł';
        }

        $prefixLen = mb_strlen($comment) + 1; // np. "// "

        $fontSize = null;
        $lines = null;

        foreach (self::FONT_SIZES as $size) {
            $charWidth = $size * self::CHAR_WIDTH_RATIO;
            $maxChars = (int) floor(self::USABLE_WIDTH / $charWidth) - $prefixLen;
            if ($maxChars < 8) {
                continue;
            }

            [$candidate, $truncated] = $this->wrap($title, $maxChars, self::MAX_TITLE_LINES);
            $totalHeight = count($candidate) * $size * self::LINE_HEIGHT_RATIO;

            if (!$truncated && $totalHeight <= self::TITLE_HEIGHT_BUDGET) {
                $fontSize = $size;
                $lines = $candidate;
                break;
            }
        }

        // Awaryjnie: najmniejsza czcionka, z ewentualnym ucięciem (…).
        if ($fontSize === null) {
            $fontSize = (int) end(self::FONT_SIZES);
            $charWidth = $fontSize * self::CHAR_WIDTH_RATIO;
            $maxChars = max(8, (int) floor(self::USABLE_WIDTH / $charWidth) - $prefixLen);
            [$lines] = $this->wrap($title, $maxChars, self::MAX_TITLE_LINES);
        }

        $lineHeight = $fontSize * self::LINE_HEIGHT_RATIO;
        $count = count($lines);
        $startBaseline = self::TITLE_CENTER_Y - (($count - 1) * $lineHeight) / 2 + $fontSize * 0.34;

        $positioned = [];
        foreach ($lines as $i => $text) {
            $positioned[] = ['text' => $text, 'y' => round($startBaseline + $i * $lineHeight, 1)];
        }

        return ['fontSize' => $fontSize, 'lines' => $positioned];
    }

    /**
     * Zawija tytuł do linii o zadanej maks. długości. Zwraca [linie, czyUcięto].
     * Jeśli potrzeba więcej niż $maxLines linii, ostatnia dostaje wielokropek.
     *
     * @return array{0: array<int, string>, 1: bool}
     */
    private function wrap(string $title, int $maxChars, int $maxLines): array
    {
        $all = $this->wrapWords($title, $maxChars);

        if (count($all) <= $maxLines) {
            return [$all, false];
        }

        $lines = array_slice($all, 0, $maxLines);
        $lastIndex = $maxLines - 1;
        $lines[$lastIndex] = rtrim(mb_substr($lines[$lastIndex], 0, max(1, $maxChars - 1))) . '…';

        return [$lines, true];
    }

    /**
     * Word wrap bez limitu linii. Bardzo długie pojedyncze słowa są dzielone.
     *
     * @return array<int, string>
     */
    private function wrapWords(string $title, int $maxChars): array
    {
        $lines = [];
        $current = '';

        foreach (explode(' ', $title) as $word) {
            // Twarde dzielenie słowa dłuższego niż linia (np. długi URL/nazwa).
            while (mb_strlen($word) > $maxChars) {
                if ($current !== '') {
                    $lines[] = $current;
                    $current = '';
                }
                $lines[] = mb_substr($word, 0, $maxChars);
                $word = mb_substr($word, $maxChars);
            }

            if ($word === '') {
                continue;
            }

            $candidate = $current === '' ? $word : $current . ' ' . $word;

            if (mb_strlen($candidate) > $maxChars && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    /**
     * Dobiera motyw do artykułu po słowach kluczowych w kategorii, tagach i tytule.
     *
     * @return array<string, string>
     */
    public function resolveTheme(Article $article): array
    {
        $haystack = Str::lower($this->buildHaystack($article));

        foreach ((array) config('covers.themes', []) as $theme) {
            foreach ((array) ($theme['keywords'] ?? []) as $keyword) {
                if ($keyword !== '' && str_contains($haystack, Str::lower((string) $keyword))) {
                    return $theme;
                }
            }
        }

        return config('covers.default');
    }

    /**
     * Buduje tekst, w którym szukamy słów kluczowych motywu.
     */
    private function buildHaystack(Article $article): string
    {
        $parts = [(string) $article->name];

        try {
            foreach ($article->tags as $tag) {
                $parts[] = (string) $tag->name;
            }
        } catch (\Throwable $e) {
            // Brak relacji tagów (np. model bez id) – ignorujemy.
        }

        try {
            $category = $article->getCategoryName();
            if (!empty($category)) {
                $parts[] = (string) $category;
            }
        } catch (\Throwable $e) {
            // Brak kategorii – ignorujemy.
        }

        return implode(' ', $parts);
    }
}
