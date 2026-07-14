<?php

namespace App\Services\Course;

use App\Models\Course;
use Illuminate\Support\Str;

/**
 * Generuje okładkę KURSU jako grafikę SVG (motyw "logo technologii").
 *
 * W przeciwieństwie do okładki artykułu ("okno kodu", CoverImageService) okładka
 * kursu eksponuje DUŻE LOGO technologii (np. Docker), pigułkę "Free course" oraz
 * kropki rozdziałów – dzięki temu kursy różnią się wizualnie od artykułów, a
 * grafika pasuje do tematu (kolor akcentu + logo dobrane po nazwie/slug/opisie).
 *
 * Motywy: config/course-covers.php. Czysty SVG – bez rozszerzeń graficznych PHP.
 */
class CourseCoverImageService
{
    private const WIDTH = 1200;
    private const HEIGHT = 630;

    // Obszar tekstu (lewa kolumna – prawą zajmuje logo).
    private const TEXT_X = 90;
    private const USABLE_WIDTH = 660.0;      // szerokość na tytuł (px)
    private const TITLE_CENTER_Y = 300.0;    // pion: środek bloku tytułu
    private const TITLE_HEIGHT_BUDGET = 250.0;
    private const MAX_TITLE_LINES = 4;
    private const CHAR_WIDTH_RATIO = 0.60;   // Montserrat 800 (przybliżenie)
    private const LINE_HEIGHT_RATIO = 1.16;

    private const FONT_SIZES = [66, 58, 52, 46, 40, 36];

    /**
     * Zwraca SVG (string) okładki dla kursu.
     */
    public function renderForCourse(Course $course): string
    {
        $theme = $this->resolveTheme($course);
        $layout = $this->layoutTitle((string) $course->name);

        return view('covers.course-cover', [
            'title'      => (string) $course->name,
            'width'      => self::WIDTH,
            'height'     => self::HEIGHT,
            'textX'      => self::TEXT_X,
            'accent'     => $theme['accent'],
            'label'      => $theme['label'],
            'logo'       => $theme['logo'],
            'fontSize'   => $layout['fontSize'],
            'titleLines' => $layout['lines'],
            'underlineY' => $layout['underlineY'],
            'meta'       => $this->buildMeta($course),
            'dots'       => $this->countDots($course),
        ])->render();
    }

    /**
     * Nazwa palety Tailwind (np. 'emerald', 'sky', 'red') dla akcentu CAŁEJ strony
     * kursu – dobierana tym samym motywem co okładka. Używane w widokach kursu do
     * dynamicznych klas `text-{{ $accent }}-400` itd. (klasy w safelist Tailwinda).
     */
    public function accentColor(Course $course): string
    {
        return $this->resolveTheme($course)['accent_color']
            ?? config('course-covers.default.accent_color', 'emerald');
    }

    /**
     * Dobiera motyw (accent + label + logo) po słowach kluczowych w nazwie,
     * slug, symbolu i opisie kursu. Pierwszy pasujący wygrywa; inaczej 'default'.
     *
     * @return array{accent:string, label:string, logo:string}
     */
    public function resolveTheme(Course $course): array
    {
        $haystack = ' ' . Str::lower($this->buildHaystack($course)) . ' ';

        foreach ((array) config('course-covers.themes', []) as $theme) {
            foreach ((array) ($theme['keywords'] ?? []) as $keyword) {
                if ($keyword !== '' && str_contains($haystack, Str::lower((string) $keyword))) {
                    return $theme;
                }
            }
        }

        return config('course-covers.default');
    }

    private function buildHaystack(Course $course): string
    {
        return implode(' ', array_filter([
            (string) $course->name,
            (string) $course->slug,
            (string) $course->symbol,
            (string) $course->title_list,
            (string) $course->description_list,
            (string) $course->title_seo,
        ]));
    }

    private function buildMeta(Course $course): string
    {
        $chapters = 0;
        try {
            $chapters = $course->categories->count();
        } catch (\Throwable $e) {
            // Brak relacji – pomijamy liczbę rozdziałów.
        }

        if ($chapters > 0) {
            $word = $chapters === 1 ? 'chapter' : 'chapters';

            return "Free course · {$chapters} {$word}";
        }

        return 'Free course';
    }

    private function countDots(Course $course): int
    {
        try {
            $count = $course->categories->count();
        } catch (\Throwable $e) {
            $count = 0;
        }

        return max(3, min(7, $count ?: 4));
    }

    /**
     * Dobiera rozmiar czcionki i zawija tytuł (word wrap + skalowanie), lewy
     * blok wyrównany do TEXT_X. Zwraca też pozycję podkreślenia pod tytułem.
     *
     * @return array{fontSize:int, lines:array<int, array{text:string, y:float}>, underlineY:float}
     */
    private function layoutTitle(string $title): array
    {
        $title = trim(preg_replace('/\s+/', ' ', $title));
        if ($title === '') {
            $title = 'Course';
        }

        $fontSize = null;
        $lines = null;

        foreach (self::FONT_SIZES as $size) {
            $charWidth = $size * self::CHAR_WIDTH_RATIO;
            $maxChars = (int) floor(self::USABLE_WIDTH / $charWidth);
            if ($maxChars < 6) {
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

        if ($fontSize === null) {
            $fontSize = (int) end(self::FONT_SIZES);
            $charWidth = $fontSize * self::CHAR_WIDTH_RATIO;
            $maxChars = max(6, (int) floor(self::USABLE_WIDTH / $charWidth));
            [$lines] = $this->wrap($title, $maxChars, self::MAX_TITLE_LINES);
        }

        $lineHeight = $fontSize * self::LINE_HEIGHT_RATIO;
        $count = count($lines);
        $startBaseline = self::TITLE_CENTER_Y - (($count - 1) * $lineHeight) / 2 + $fontSize * 0.34;

        $positioned = [];
        foreach ($lines as $i => $text) {
            $positioned[] = ['text' => $text, 'y' => round($startBaseline + $i * $lineHeight, 1)];
        }

        $underlineY = round($startBaseline + ($count - 1) * $lineHeight + $fontSize * 0.42, 1);

        return ['fontSize' => $fontSize, 'lines' => $positioned, 'underlineY' => $underlineY];
    }

    /**
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
     * @return array<int, string>
     */
    private function wrapWords(string $title, int $maxChars): array
    {
        $lines = [];
        $current = '';

        foreach (explode(' ', $title) as $word) {
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
}
