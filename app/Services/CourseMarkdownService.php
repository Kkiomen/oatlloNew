<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseCategoryLesson;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseMarkdownService
{
    private string $coursesPath;
    private array $markdownExtensions = ['md', 'markdown'];

    public function __construct()
    {
        $this->coursesPath = storage_path('app/private/cources');
    }

    /**
     * Przetwarza wszystkie kursy z plików Markdown
     */
    public function processAllCourses(bool $force = false): array
    {
        $results = [];

        if (!File::exists($this->coursesPath)) {
            throw new \Exception("Katalog kursów nie istnieje: {$this->coursesPath}");
        }

        $courseDirectories = File::directories($this->coursesPath);

        foreach ($courseDirectories as $courseDir) {
            $courseSymbol = basename($courseDir);
            $results[$courseSymbol] = $this->processCourse($courseSymbol, $force);
        }

        return $results;
    }

    /**
     * Przetwarza konkretny kurs
     */
    public function processCourse(string $courseSymbol, bool $force = false): array
    {
        $coursePath = $this->coursesPath . '/' . $courseSymbol;

        if (!File::exists($coursePath)) {
            throw new \Exception("Katalog kursu nie istnieje: {$coursePath}");
        }

        // Znajdź lub utwórz kurs w bazie
        $course = $this->findOrCreateCourse($courseSymbol);

        $results = [
            'course' => $course->symbol,
            'categories_processed' => 0,
            'lessons_processed' => 0,
            'errors' => []
        ];

        // Przetwórz kategorie (rozdziały)
        $categoryDirectories = File::directories($coursePath);

        foreach ($categoryDirectories as $categoryDir) {
            try {
                $categoryResults = $this->processCategory($course, $categoryDir, $force);
                $results['categories_processed'] += $categoryResults['categories_processed'];
                $results['lessons_processed'] += $categoryResults['lessons_processed'];
            } catch (\Exception $e) {
                $results['errors'][] = "Błąd w kategorii " . basename($categoryDir) . ": " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Przetwarza kategorię (rozdział) kursu
     */
    private function processCategory(Course $course, string $categoryPath, bool $force = false): array
    {
        $categoryName = basename($categoryPath);
        $chapterConfigPath = $categoryPath . '/chapter.json';

        if (!File::exists($chapterConfigPath)) {
            throw new \Exception("Brak pliku chapter.json w kategorii: {$categoryName}");
        }

        $chapterConfig = json_decode(File::get($chapterConfigPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Błędny format JSON w chapter.json: " . json_last_error_msg());
        }

        // Znajdź lub utwórz kategorię
        $category = CourseCategory::updateOrCreate(
            [
                'course_id' => $course->id,
                'slug' => $chapterConfig['slug'] ?? Str::slug($categoryName)
            ],
            [
                'category_name' => $categoryName,
                'title' => $chapterConfig['title'] ?? $categoryName,
                'description' => $chapterConfig['description'] ?? '',
                'description_content' => $chapterConfig['full_description'] ?? '',
                'sort' => $chapterConfig['position'] ?? 1,
                'is_published' => true,
                'lang' => 'pl', // domyślnie polski
            ]
        );

        $results = [
            'categories_processed' => 1,
            'lessons_processed' => 0
        ];

        // Przetwórz lekcje (pliki .md)
        $markdownFiles = $this->getMarkdownFiles($categoryPath);

        // Debug: wyświetl znalezione pliki
        if (empty($markdownFiles)) {
            throw new \Exception("Nie znaleziono plików Markdown w kategorii: {$categoryName}");
        }

        foreach ($markdownFiles as $index => $markdownFile) {
            try {
                $lessonResults = $this->processLesson($category, $markdownFile, $index + 1, $force);
                $results['lessons_processed'] += $lessonResults['processed'] ? 1 : 0;
            } catch (\Exception $e) {
                throw new \Exception("Błąd w lekcji " . basename($markdownFile) . ": " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Przetwarza pojedynczą lekcję
     */
    private function processLesson(CourseCategory $category, string $markdownFile, int $sort, bool $force = false): array
    {
        $content = File::get($markdownFile);
        $filename = basename($markdownFile, '.md');

        // Parsuj metadane
        $metadata = $this->parseMetadata($content);
        $markdownContent = $this->extractMarkdownContent($content);

        // Oblicz hash treści (bez metadanych)
        $contentHash = md5($markdownContent);

        // Sprawdź czy lekcja się zmieniła (pomiń jeśli --force)
        if (!$force) {
            $existingLesson = CourseCategoryLesson::where('course_category_id', $category->id)
                ->where('slug', $metadata['slug'] ?? Str::slug($filename))
                ->first();

            if ($existingLesson && $existingLesson->meta_hash === $contentHash) {
                // Aktualizuj tylko sort jeśli się zmienił
                if ($existingLesson->sort !== $sort) {
                    $existingLesson->update(['sort' => $sort]);
                }
                return ['processed' => false, 'reason' => 'Lekcja nie zmieniła się'];
            }
        }

        // Konwertuj Markdown na HTML
        $htmlContent = $this->convertMarkdownToHtml($markdownContent);

        // Zapisz lub zaktualizuj lekcję
        CourseCategoryLesson::updateOrCreate(
            [
                'course_category_id' => $category->id,
                'slug' => $metadata['slug'] ?? Str::slug($filename)
            ],
            [
                'title' => $metadata['title'] ?? $filename,
                'content_html' => $htmlContent,
                'meta_hash' => $contentHash,
                'position' => $metadata['position'] ?? $sort,
                'seo_title' => $metadata['seo_title'] ?? '',
                'seo_description' => $metadata['seo_description'] ?? '',
                'is_published' => true,
                'sort' => $sort,
            ]
        );

        return ['processed' => true, 'reason' => 'Lekcja została przetworzona'];
    }

    /**
     * Parsuje metadane z pliku Markdown
     */
    private function parseMetadata(string $content): array
    {
        $metadata = [];

        if (preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
            $yamlContent = $matches[1];

            // Prosty parser YAML dla podstawowych przypadków
            $lines = explode("\n", $yamlContent);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }

                if (preg_match('/^([^:]+):\s*(.+)$/', $line, $matches)) {
                    $key = trim($matches[1]);
                    $value = trim($matches[2]);

                    // Usuń cudzysłowy jeśli są
                    if (preg_match('/^["\'](.+)["\']$/', $value, $quoteMatches)) {
                        $value = $quoteMatches[1];
                    }

                    $metadata[$key] = $value;
                }
            }
        }

        return $metadata;
    }

    /**
     * Wyciąga treść Markdown bez metadanych
     */
    private function extractMarkdownContent(string $content): string
    {
        // Usuń sekcję metadanych
        $content = preg_replace('/^---\s*\n.*?\n---\s*\n/s', '', $content);
        return trim($content);
    }

    /**
     * Konwertuje Markdown na HTML
     */
    private function convertMarkdownToHtml(string $markdown): string
    {
        if ($markdown === '') {
            return '';
        }

        // Normalizacja CRLF -> LF
        $html = str_replace(["\r\n", "\r"], "\n", $markdown);

        // Mapy placeholderów
        $codeBlocks = [];
        $inlineCodes = [];
        $cb = 0;
        $ci = 0;

        // Helper do bezpieczeństwa kodu
        $esc = fn(string $s) => htmlspecialchars($s, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // BEZPIECZNE PLACEHOLDERY (znaki kontrolne ASCII 30/31)
        $phBlock  = fn(int $i) => sprintf("\x1EBC%05d\x1F", $i);
        $phInline = fn(int $i) => sprintf("\x1EIC%05d\x1F", $i);

        // 1) Bloki kodu z językiem  ```lang\n...\n```
        $html = preg_replace_callback('/```([A-Za-z0-9_+-]+)[ \t]*\n([\s\S]*?)\n```/', function ($m) use (&$codeBlocks, &$cb, $esc, $phBlock) {
            $lang = strtolower($m[1]);
            $code = $esc($m[2]);
            $ph = $phBlock($cb++);
            $codeBlocks[$ph] = '<pre><code class="language-' . $lang . '">' . $code . '</code></pre>';
            return $ph;
        }, $html);

        // 2) Bloki kodu bez języka  ```\n...\n```
        $html = preg_replace_callback('/```[ \t]*\n([\s\S]*?)\n```/', function ($m) use (&$codeBlocks, &$cb, $esc, $phBlock) {
            $code = $esc($m[1]);
            $ph = $phBlock($cb++);
            // Domyślnie PHP gdy brak języka:
            $codeBlocks[$ph] = '<pre><code class="language-php">' . $code . '</code></pre>';
            return $ph;
        }, $html);

        // 3) Kod inline `...`  — wycinamy wcześnie, żeby nie psuły go inne regexy
        $html = preg_replace_callback('/`([^`\n]+)`/', function ($m) use (&$inlineCodes, &$ci, $esc, $phInline) {
            $code = $esc($m[1]);
            $ph = $phInline($ci++);
            // jeśli nie chcesz klasy na inline, zmień na <code>…</code>
            $inlineCodes[$ph] = '<code class="language-php">' . $code . '</code>';
            return $ph;
        }, $html);

        // 4) Obrazy
        $html = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $html);

        // 5) Linki
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);

        // 6) Nagłówki
        $html = preg_replace('/^######[ \t]+(.+)$/m', '<h6>$1</h6>', $html);
        $html = preg_replace('/^##### [ \t]+(.+)$/m', '<h5>$1</h5>', $html);
        $html = preg_replace('/^####[ \t]+(.+)$/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^### [ \t]+(.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^##  [ \t]+(.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^#   [ \t]+(.+)$/m', '<h1>$1</h1>', $html);
        // elastyczne dopasowanie 1–6 # (w razie nietypowych odstępów)
        $html = preg_replace('/^#{1}[ \t]+(.+)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^#{2}[ \t]+(.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^#{3}[ \t]+(.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^#{4}[ \t]+(.+)$/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^#{5}[ \t]+(.+)$/m', '<h5>$1</h5>', $html);
        $html = preg_replace('/^#{6}[ \t]+(.+)$/m', '<h6>$1</h6>', $html);

        // 7) Pogrubienie / kursywa (ostrożniej, żeby nie łapać środka słów)
        $html = preg_replace('/(?<!\*)\*\*(.+?)\*\*(?!\*)/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/(?<!_)__(.+?)__(?!_)/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/(?<!\*)\*(.+?)\*(?!\*)/s', '<em>$1</em>', $html);
        $html = preg_replace('/(?<!_)_(.+?)_(?!_)/s', '<em>$1</em>', $html);

        // 8) Linie poziome
        $html = preg_replace('/^(?:---|\*\*\*|___)\s*$/m', '<hr>', $html);

        // 9) Listy UL
        $html = preg_replace_callback('/(?:^(?:-|\*)\s+.+\n?)+/m', function ($m) {
            $items = preg_split('/\n/', trim($m[0]));
            $lis = [];
            foreach ($items as $line) {
                if (preg_match('/^(?:-|\*)\s+(.+)$/', $line, $mm)) {
                    $lis[] = '<li>' . $mm[1] . '</li>';
                }
            }
            return $lis ? "<ul>\n" . implode("\n", $lis) . "\n</ul>" : $m[0];
        }, $html);

        // 10) Listy OL
        $html = preg_replace_callback('/(?:^\d+\.\s+.+\n?)+/m', function ($m) {
            $items = preg_split('/\n/', trim($m[0]));
            $lis = [];
            foreach ($items as $line) {
                if (preg_match('/^\d+\.\s+(.+)$/', $line, $mm)) {
                    $lis[] = '<li>' . $mm[1] . '</li>';
                }
            }
            return $lis ? "<ol>\n" . implode("\n", $lis) . "\n</ol>" : $m[0];
        }, $html);

        // 11) Akapity (bez psucia bloków)
        $blocks = preg_split("/\n{2,}/", $html);
        foreach ($blocks as &$b) {
            $t = ltrim($b);
            if ($t === '') continue;
            if (!preg_match('/^(<h[1-6]|<ul>|<ol>|<pre>|<hr>)/', $t)) {
                $b = '<p>' . preg_replace("/\n/", "<br>", $b) . '</p>';
            }
        }
        $html = implode("\n\n", $blocks);

        // 12) Przywróć inline code i code blocks – TERAZ placeholders dalej są nienaruszone
        foreach ($inlineCodes as $ph => $val) {
            $html = str_replace($ph, $val, $html);
        }
        foreach ($codeBlocks as $ph => $val) {
            $html = str_replace($ph, $val, $html);
        }

        return $html;
    }



    /**
     * Znajduje lub tworzy kurs w bazie danych
     */
    private function findOrCreateCourse(string $courseSymbol): Course
    {
        $course = Course::where('symbol', $courseSymbol)->first();

        if (!$course) {
            // Utwórz podstawowy kurs
            $course = Course::create([
                'symbol' => $courseSymbol,
                'name' => ucfirst(str_replace('_', ' ', $courseSymbol)),
                'slug' => Str::slug($courseSymbol),
                'is_published' => true,
                'lang' => 'pl',
            ]);
        }

        return $course;
    }

    /**
     * Pobiera pliki Markdown z katalogu
     */
    private function getMarkdownFiles(string $directory): array
    {
        $files = [];

        foreach (File::files($directory) as $file) {
            $extension = $file->getExtension();
            if (in_array($extension, $this->markdownExtensions)) {
                $files[] = $file->getPathname();
            }
        }

        // Sortuj pliki alfabetycznie
        sort($files);

        return $files;
    }
}
