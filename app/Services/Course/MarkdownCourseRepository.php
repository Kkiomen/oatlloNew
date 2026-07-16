<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseCategoryLesson;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use App\Services\Markdown\HtmlContentEnhancer;

/**
 * Odczyt kursów zadeklarowanych jako pliki .md (drugie źródło kursów obok bazy).
 *
 * Struktura katalogu (config('articles.courses_path')):
 *   {course-slug}/
 *     course.md                 – frontmatter kursu + (opcjonalnie) treść oferty
 *     NN-{chapter}/             – rozdział (prefiks NN- ustala kolejność)
 *       _chapter.md             – frontmatter rozdziału + (opcjonalnie) opis
 *       NN-{lesson}.md          – lekcja: frontmatter + treść Markdown -> HTML
 *
 * Buduje niepersystowane modele Course -> CourseCategory -> CourseCategoryLesson
 * z ustawionymi wzajemnymi relacjami, dzięki czemu getRoute() działa i renderują
 * się przez te same widoki co kursy z bazy.
 */
class MarkdownCourseRepository
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new FrontMatterExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    public function directory(): string
    {
        return rtrim((string) config('articles.courses_path'), '/\\');
    }

    /**
     * Wszystkie kursy z plików jako niepersystowane modele Course.
     *
     * @return Collection<int, Course>
     */
    public function all(): Collection
    {
        $dir = $this->directory();
        if (! File::isDirectory($dir)) {
            return collect();
        }

        return collect(File::directories($dir))
            ->sort()
            ->map(fn ($courseDir) => $this->buildCourseCached($courseDir))
            ->filter()
            ->values();
    }

    /**
     * buildCourse() renderuje CommonMark KAŻDEJ lekcji (400+ plików ~2 s) — leciało przy
     * każdym requeście strony głównej i /kursy, choć listingi używają tylko metadanych kursu.
     * Cache'ujemy PER KURS (nie jedną wielką paczką — bezpieczniej dla limitu pakietu i lżejszy
     * przy wzroście liczby lekcji). Klucz = podpis plików danego kursu (nazwa+mtime+rozmiar),
     * więc deploy (git pull zmienia mtime) unieważnia tylko zmienione kursy. Błąd cache =>
     * budujemy wprost (strona nigdy nie pada przez cache).
     */
    private function buildCourseCached(string $courseDir): ?Course
    {
        try {
            $signature = collect(File::allFiles($courseDir))
                ->map(fn ($file) => $file->getRelativePathname().':'.$file->getMTime().':'.$file->getSize())
                ->implode('|');

            return Cache::remember(
                'md_course:'.md5($courseDir.'|'.$signature),
                now()->addDay(),
                fn () => $this->buildCourse($courseDir)
            );
        } catch (\Throwable $e) {
            return $this->buildCourse($courseDir);
        }
    }

    /**
     * Opublikowane kursy, opcjonalnie filtrowane po języku.
     *
     * @return Collection<int, Course>
     */
    public function published(?string $language = null): Collection
    {
        return $this->all()
            ->filter(fn (Course $c) => $c->isLive())
            ->when($language !== null, fn ($c) => $c->filter(fn (Course $x) => $x->lang === $language))
            ->values();
    }

    /**
     * Pojedynczy kurs po slug (nazwa folderu lub slug z frontmatteru).
     */
    public function findCourse(string $slug): ?Course
    {
        $dir = $this->directory();
        if (! File::isDirectory($dir)) {
            return null;
        }

        // Najpierw po nazwie folderu; jeśli nie ma, szukamy po slug z course.md.
        $direct = $dir . DIRECTORY_SEPARATOR . $this->normalizeSlug($slug);
        if (File::isDirectory($direct)) {
            return $this->buildCourse($direct);
        }

        return $this->all()->first(fn (Course $c) => $c->slug === $slug);
    }

    public function normalizeSlug(string $slug): string
    {
        $slug = Str::slug($slug);

        return $slug !== '' ? $slug : 'course';
    }

    // ------------------------------------------------------------------ budowa

    private function buildCourse(string $coursePath): ?Course
    {
        $folder   = basename($coursePath);
        $metaPath = $coursePath . DIRECTORY_SEPARATOR . 'course.md';
        $rawMeta  = File::exists($metaPath) ? File::get($metaPath) : '';
        ['fm' => $fm, 'html' => $offersHtml] = $this->parse($rawMeta);

        $course = new Course();
        $course->slug        = $fm['slug'] ?? $folder;
        $course->symbol      = $fm['symbol'] ?? strtoupper(str_replace('-', '_', $folder));
        $course->name        = $fm['name'] ?? Str::title(str_replace('-', ' ', $folder));
        $course->lang        = $fm['lang'] ?? config('articles.default_language');
        // Brak 'image' lub 'auto' -> generowana okładka SVG (motyw "logo technologii").
        $course->image       = (empty($fm['image']) || ($fm['image'] ?? null) === 'auto')
            ? route('course.cover', ['slug' => $course->slug])
            : $fm['image'];
        $course->title_list  = $fm['title_list'] ?? $course->name;
        $course->description_list = $fm['description_list'] ?? ($fm['description'] ?? '');
        $course->title_full  = $fm['title_full'] ?? $course->name;
        $course->description_full = $fm['description_full'] ?? ($fm['description'] ?? '');
        $course->title_seo   = $fm['title_seo'] ?? $course->title_list;
        $course->description_seo = $fm['description_seo'] ?? $course->description_list;
        $course->content_description_offers = $fm['content_description_offers'] ?? $offersHtml;
        $course->is_published = array_key_exists('is_published', $fm) ? (bool) $fm['is_published'] : true;

        // Data planowanej publikacji (opcjonalna). Kurs z datą w przyszłości jest
        // ukryty aż do terminu (Course::isLive()) - tak jak artykuły .md. Brak pola
        // = null = żywy od razu, więc istniejące kursy nie wymagają zmian.
        $course->published_at = array_key_exists('published_at', $fm)
            ? $this->toDate($fm['published_at'])
            : null;

        $course->source = 'markdown';
        $course->exists = false;

        // Timestampy (z mtime pliku course.md) – używane m.in. w sitemap.
        $mtime = File::exists($metaPath) ? Carbon::createFromTimestamp(File::lastModified($metaPath)) : Carbon::now();
        $course->created_at = $mtime;
        $course->updated_at = $mtime;

        // Rozdziały = podfoldery (posortowane po nazwie – prefiks NN- ustala kolejność).
        $categories = collect(File::directories($coursePath))
            ->sort()
            ->values()
            ->map(function ($chapterDir, $index) use ($course) {
                $category = $this->buildCategory($chapterDir, $index + 1);
                $category->setRelation('course', $course);

                return $category;
            });

        $course->setRelation('categories', $categories);

        return $course;
    }

    private function buildCategory(string $chapterPath, int $sort): CourseCategory
    {
        $folder   = basename($chapterPath);
        $metaPath = $chapterPath . DIRECTORY_SEPARATOR . '_chapter.md';
        $rawMeta  = File::exists($metaPath) ? File::get($metaPath) : '';
        ['fm' => $fm, 'html' => $descHtml] = $this->parse($rawMeta);

        $baseSlug = $this->stripOrderPrefix($folder);

        $category = new CourseCategory();
        $category->slug        = $fm['slug'] ?? $baseSlug;
        $category->title       = $fm['title'] ?? Str::title(str_replace('-', ' ', $baseSlug));
        $category->description = $fm['description'] ?? '';
        $category->description_content = $fm['description_content'] ?? $descHtml;
        $category->title_seo   = $fm['title_seo'] ?? $category->title;
        $category->description_seo = $fm['description_seo'] ?? $category->description;
        $category->sort        = (int) ($fm['sort'] ?? $sort);
        $category->is_published = array_key_exists('is_published', $fm) ? (bool) $fm['is_published'] : true;
        $category->exists = false;

        $mtime = File::exists($metaPath) ? Carbon::createFromTimestamp(File::lastModified($metaPath)) : Carbon::now();
        $category->created_at = $mtime;
        $category->updated_at = $mtime;

        // Lekcje = pliki .md w folderze rozdziału (poza _chapter.md), posortowane po nazwie.
        $lessons = collect(File::files($chapterPath))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'md' && $file->getFilename() !== '_chapter.md')
            ->sortBy(fn ($file) => $file->getFilename())
            ->values()
            ->map(function ($file, $index) use ($category) {
                $lesson = $this->buildLesson($file, $index + 1);
                $lesson->setRelation('category', $category);

                return $lesson;
            });

        $category->setRelation('lessons', $lessons);
        $category->setRelation('allLessons', $lessons);

        return $category;
    }

    private function buildLesson(\SplFileInfo $file, int $sort): CourseCategoryLesson
    {
        $raw = File::get($file->getPathname());
        ['fm' => $fm, 'html' => $html] = $this->parse($raw);

        $baseSlug = $this->stripOrderPrefix($file->getBasename('.' . $file->getExtension()));

        $lesson = new CourseCategoryLesson();
        $lesson->slug         = $fm['slug'] ?? $baseSlug;
        $lesson->title        = $fm['title'] ?? Str::title(str_replace('-', ' ', $baseSlug));
        $lesson->content_html = $html;
        $lesson->seo_title    = $fm['seo_title'] ?? $lesson->title;
        $lesson->seo_description = $fm['seo_description'] ?? '';
        $lesson->sort         = (int) ($fm['sort'] ?? $sort);
        $lesson->position     = (int) ($fm['position'] ?? $sort);
        $lesson->is_published = array_key_exists('is_published', $fm) ? (bool) $fm['is_published'] : true;
        $lesson->exists = false;

        // Timestampy z czasu modyfikacji pliku (widok lekcji używa created_at/updated_at).
        $mtime = Carbon::createFromTimestamp($file->getMTime());
        $lesson->created_at = $mtime;
        $lesson->updated_at = $mtime;

        return $lesson;
    }

    // ------------------------------------------------------------------ pomoc

    /**
     * Rozdziela frontmatter od treści i konwertuje treść na HTML.
     *
     * @return array{fm: array<string,mixed>, html: string}
     */
    private function parse(string $raw): array
    {
        if (trim($raw) === '') {
            return ['fm' => [], 'html' => ''];
        }

        $raw    = $this->normalizeEncoding($raw);
        $raw    = $this->normalizeDashes($raw);
        $result = $this->converter->convert($raw);

        $fm = [];
        if ($result instanceof RenderedContentWithFrontMatter) {
            $fm = $result->getFrontMatter() ?? [];
        }

        return [
            'fm'   => is_array($fm) ? $fm : [],
            'html' => HtmlContentEnhancer::enhance((string) $result->getContent()),
        ];
    }

    private function stripOrderPrefix(string $name): string
    {
        // "01-what-is-php" -> "what-is-php"
        return (string) preg_replace('/^\d+[-_]/', '', $name);
    }

    /**
     * Kursy (inaczej niż artykuly) NIE przechodza przez ContentSanitizer, wiec pilnujemy
     * tu jednej rzeczy: zaden "ladny" myslnik nie trafia na strone - ani w tresci, ani w
     * naglowkach/SEO (frontmatter jest parsowany z tego samego $raw). Wywolane w parse()
     * PRZED konwersja, wiec obejmuje wszystkie pola. Zamieniamy tylko myslniki (em/en/
     * figure/horizontal-bar oraz znak minus), nic wiecej - zeby nie ruszac kodu ani terminow.
     */
    private function normalizeDashes(string $raw): string
    {
        return str_replace(
            ["\u{2014}", "\u{2013}", "\u{2012}", "\u{2015}", "\u{2212}"],
            '-',
            $raw
        );
    }

    /**
     * Zamienia wartość published_at z frontmattera na Carbon (albo null, gdy pusto
     * lub nieparsowalne). Akceptuje datę YAML (DateTime), "2026-07-24" czy
     * "2026-07-24 09:00". Nieparsowalna wartość => null => kurs żywy od razu
     * (bezpieczniej pokazać niż w nieskończoność ukrywać przez literówkę w dacie).
     */
    private function toDate(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeEncoding(string $raw): string
    {
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        if (! mb_check_encoding($raw, 'UTF-8')) {
            // mbstring nie zna nazwy "Windows-1250" (ValueError) — używamy ISO-8859-2.
            $detected = mb_detect_encoding($raw, ['UTF-8', 'ISO-8859-2', 'Windows-1252', 'ISO-8859-1'], true) ?: 'ISO-8859-2';
            $raw = mb_convert_encoding($raw, 'UTF-8', $detected);
        }

        return $raw;
    }
}
