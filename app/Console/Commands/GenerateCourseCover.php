<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Services\Course\CourseCoverImageService;
use App\Services\Course\MarkdownCourseRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Podgląd/eksport wygenerowanej okładki kursu do pliku SVG.
 *
 * Okładki serwuje na żywo trasa /courses/{slug}/cover.svg (course.cover) – ta
 * komenda jest wyłącznie do podglądu offline (np. sprawdzenie doboru motywu/logo
 * dla nowego kursu bez uruchamiania HTTP). NIE jest potrzebna do wdrożenia.
 */
class GenerateCourseCover extends Command
{
    protected $signature = 'course:cover
                            {slug? : Slug kursu (np. docker-basics). Puste + --all = wszystkie kursy z plików}
                            {--all : Wygeneruj okładki dla wszystkich kursów z plików .md}
                            {--out= : Katalog wyjściowy (domyślnie storage/app/course-covers)}';

    protected $description = 'Zapisuje wygenerowaną okładkę kursu (SVG) do pliku – podgląd offline';

    public function handle(MarkdownCourseRepository $repository, CourseCoverImageService $covers): int
    {
        $outDir = rtrim($this->option('out') ?: storage_path('app/course-covers'), '/\\');
        File::ensureDirectoryExists($outDir);

        $courses = $this->resolveCourses($repository);

        if ($courses->isEmpty()) {
            $this->error('Nie znaleziono kursu. Podaj {slug} lub użyj --all.');

            return self::FAILURE;
        }

        foreach ($courses as $course) {
            $svg = $covers->renderForCourse($course);
            $path = $outDir . DIRECTORY_SEPARATOR . $course->slug . '.svg';
            File::put($path, $svg);

            $theme = $covers->resolveTheme($course);
            $this->line("  <info>✓</info> {$course->slug}  [{$theme['label']}]  -> {$path}");
        }

        $this->info("Gotowe: {$courses->count()} okładek w {$outDir}");

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Course>
     */
    private function resolveCourses(MarkdownCourseRepository $repository): \Illuminate\Support\Collection
    {
        $slug = (string) $this->argument('slug');

        // Brak slug albo --all -> wszystkie kursy z plików .md.
        if ($slug === '' || $this->option('all')) {
            return $repository->all();
        }

        $course = $repository->findCourse($slug) ?? Course::where('slug', $slug)->first();

        return collect(array_filter([$course]));
    }
}
