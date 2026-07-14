<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseCategoryLesson;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

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
     * Konwertuje Markdown na HTML za pomocą league/commonmark (CommonMark + GFM).
     *
     * Wcześniej używaliśmy ręcznego parsera na regexach, który błędnie traktował
     * podkreślenia wewnątrz słów jako kursywę (np. UPPER_CASE -> UPPER<em>CASE...</em>)
     * i psuł zagnieżdżone listy. CommonMark rozwiązuje oba problemy i jest zgodny
     * ze specyfikacją (tak samo parsujemy artykuły w MarkdownArticleParser).
     */
    private function convertMarkdownToHtml(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }

        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        $converter = new MarkdownConverter($environment);

        return \App\Services\Markdown\HtmlContentEnhancer::enhance(
            (string) $converter->convert($markdown)->getContent()
        );
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
