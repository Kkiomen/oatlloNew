<?php

namespace App\Console\Commands;

use App\Services\CourseMarkdownService;
use Illuminate\Console\Command;

class ProcessCourseMarkdown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'course:process
                            {course? : Symbol kursu do przetworzenia (opcjonalnie, jeśli nie podano - przetwarza wszystkie)}
                            {--force : Wymusza przetworzenie wszystkich lekcji, nawet jeśli się nie zmieniły}
                            {--details : Wyświetla szczegółowe informacje}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Przetwarza kursy z plików Markdown do bazy danych';

    /**
     * Execute the console command.
     */
    public function handle(CourseMarkdownService $markdownService)
    {
        $courseSymbol = $this->argument('course');
        $showDetails = $this->option('details');
        $force = $this->option('force');

        $this->info('🚀 Rozpoczynam przetwarzanie kursów z plików Markdown...');

        try {
            if ($courseSymbol) {
                $this->info("📚 Przetwarzam kurs: {$courseSymbol}");
                $results = $markdownService->processCourse($courseSymbol, $force);
                $this->displayResults([$courseSymbol => $results], $showDetails);
            } else {
                $this->info('📚 Przetwarzam wszystkie kursy...');
                $results = $markdownService->processAllCourses($force);
                $this->displayResults($results, $showDetails);
            }

            $this->info('✅ Przetwarzanie zakończone pomyślnie!');

        } catch (\Exception $e) {
            $this->error('❌ Błąd podczas przetwarzania: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Wyświetla wyniki przetwarzania
     */
    private function displayResults(array $results, bool $showDetails): void
    {
        foreach ($results as $courseSymbol => $courseResults) {
            $this->line('');
            $this->info("📖 Kurs: {$courseSymbol}");
            $this->line("   Kategorie przetworzone: {$courseResults['categories_processed']}");
            $this->line("   Lekcje przetworzone: {$courseResults['lessons_processed']}");

            if (!empty($courseResults['errors'])) {
                $this->warn("   Błędy:");
                foreach ($courseResults['errors'] as $error) {
                    $this->line("     ❌ {$error}");
                }
            }

            if ($showDetails && isset($courseResults['details'])) {
                $this->line("   Szczegóły:");
                foreach ($courseResults['details'] as $detail) {
                    $this->line("     ℹ️  {$detail}");
                }
            }
        }
    }
}
