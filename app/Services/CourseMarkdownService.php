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

        $chapterConfig['title'] = str_replace("—", "-", $chapterConfig['title'] ?? $categoryName);
        $chapterConfig['description'] = str_replace("—", "-", $chapterConfig['description'] ?? '');
        $chapterConfig['full_description'] = str_replace("—", "-", $chapterConfig['full_description'] ?? '');

        // Znajdź lub utwórz kategorię
        $category = CourseCategory::updateOrCreate(
            [
                'course_id' => $course->id,
                'slug' => $chapterConfig['slug'] ?? Str::slug($categoryName)
            ],
            [
                'category_name' => $categoryName,
                'title' => $chapterConfig['title'],
                'description' => $chapterConfig['description'],
                'description_content' => $chapterConfig['full_description'],
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

        $metadata['title'] = str_replace("—", "-", $metadata['title'] ?? $filename);
        $metadata['seo_title'] = str_replace("—", "-", $metadata['seo_title'] ?? '');
        $metadata['seo_description'] = str_replace("—", "-", $metadata['seo_description'] ?? '');

        // Zapisz lub zaktualizuj lekcję
        CourseCategoryLesson::updateOrCreate(
            [
                'course_category_id' => $category->id,
                'slug' => $metadata['slug'] ?? Str::slug($filename)
            ],
            [
                'title' => $metadata['title'],
                'content_html' => $htmlContent,
                'meta_hash' => $contentHash,
                'position' => $metadata['position'] ?? $sort,
                'seo_title' => $metadata['seo_title'],
                'seo_description' => $metadata['seo_description'],
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

        $html = str_replace(["\r\n", "\r"], "\n", $markdown);

        // --- CODE BLOCKS ---
        $esc = fn(string $s) => htmlspecialchars($s, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $phBlock = fn(int $i) => sprintf("\x1EBC%05d\x1F", $i);
        $phInline = fn(int $i) => sprintf("\x1EIC%05d\x1F", $i);

        $codeBlocks = [];
        $inlineCodes = [];
        $cb = 0; $ci = 0;

        // fenced code blocks (```php ... ```)
        $html = preg_replace_callback(
            '/(^|\n)[ ]{0,3}```[ \t]*([A-Za-z0-9_+-]+)?[ \t]*\n([\s\S]*?)\n[ ]{0,3}```[ \t]*(?=\n|$)/',
            function ($m) use (&$codeBlocks, &$cb, $esc, $phBlock) {
                $lang = $m[2] !== '' ? strtolower($m[2]) : 'php';
                $ph   = $phBlock($cb++);
                $codeBlocks[$ph] = '<pre><code class="language-'.$lang.'">'.$esc($m[3]).'</code></pre>';
                return $m[1].$ph;
            },
            $html
        );

        // inline code `
        $html = preg_replace_callback(
            '/`([^`\n]+)`/',
            function ($m) use (&$inlineCodes, &$ci, $esc, $phInline) {
                $ph = $phInline($ci++);
                $inlineCodes[$ph] = '<code>'.$esc($m[1]).'</code>';
                return $ph;
            },
            $html
        );

        // --- LINKS & IMAGES ---
        $html = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $html);
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);

        // --- HEADERS ---
        for ($i=6; $i>=1; $i--) {
            $html = preg_replace('/^'.str_repeat('#',$i).'[ \t]+(.+)$/m', "<h$i>$1</h$i>", $html);
        }

        // --- LISTS ---
        // UL item
        $html = preg_replace('/^[ ]{0,3}(?:-|\*)[ \t]+(.+)$/m', '<li>$1</li>', $html);
        // OL item
        $html = preg_replace('/^[ ]{0,3}\d+\.[ \t]+(.+)$/m', '<li data-ol="1">$1</li>', $html);

        // wrap OL
        $html = preg_replace_callback(
            '/(?:^(?:<li data-ol="1">.*<\/li>)\s*)+/m',
            fn($m) => "<ol>\n".str_replace('<li data-ol="1">','<li>',$m[0])."\n</ol>",
            $html
        );
        // wrap UL
        $html = preg_replace_callback(
            '/(?:^(?:<li>(?:.|\n)*?<\/li>)\s*)+/m',
            fn($m) => "<ul>\n".trim($m[0])."\n</ul>",
            $html
        );

        // --- EMPHASIS ---
        $html = preg_replace('/(?<!\*)\*\*(.+?)\*\*(?!\*)/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/(?<!_)__(.+?)__(?!_)/s', '<strong>$1</strong>', $html);
        // kursywa — ignorujemy "* " na początku (lista)
        $html = preg_replace('/(?<!\*)\*(?!\s)(.+?)(?<!\s)\*(?!\*)/s', '<em>$1</em>', $html);
        $html = preg_replace('/(?<!_)_(.+?)_(?!_)/s', '<em>$1</em>', $html);

        // --- HR ---
        $html = preg_replace('/^(?:---|\*\*\*|___)\s*$/m', '<hr>', $html);

        // --- PARAGRAPHS ---
        $blocks = preg_split("/\n{2,}/", $html);
        foreach ($blocks as &$b) {
            $t = ltrim($b);
            if ($t === '') continue;
            if (!preg_match('/^(<(?:h[1-6]|ul|ol|pre|hr)>)/', $t)) {
                $b = '<p>'.preg_replace("/\n/", "<br>", $b).'</p>';
            }
        }
        $html = implode("\n\n", $blocks);

        // --- RESTORE placeholders ---
        foreach ($inlineCodes as $ph => $val) $html = str_replace($ph,$val,$html);
        foreach ($codeBlocks as $ph => $val) $html = str_replace($ph,$val,$html);

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
