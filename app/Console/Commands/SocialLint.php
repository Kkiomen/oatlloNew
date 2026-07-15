<?php

namespace App\Console\Commands;

use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\SocialLintIssue;
use App\Services\Social\SocialPostLinter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Waliduje posty social media. To bramka przed eksportem – `social:export`
 * odpala ten sam linter i odmawia budowania grafik z błędami.
 *
 * Exit != 0 przy jakimkolwiek ERRORze (warningi nie blokują).
 */
class SocialLint extends Command
{
    protected $signature = 'social:lint
                            {slug? : Slug posta. Puste = wszystkie}
                            {--all : Wszystkie posty (domyślne, gdy brak slug)}
                            {--strict : Traktuj warningi jak błędy}';

    protected $description = 'Waliduje posty social media z resources/social';

    public function handle(MarkdownSocialPostRepository $repository, SocialPostLinter $linter): int
    {
        $paths = $this->resolvePaths($repository);

        if ($paths === []) {
            $this->error('Nie znaleziono postów do walidacji w ' . $repository->directory());

            return self::FAILURE;
        }

        $errors = 0;
        $warnings = 0;

        foreach ($paths as $path) {
            $slug = pathinfo($path, PATHINFO_FILENAME);
            $issues = $linter->lintRaw(File::get($path), $slug);

            if ($issues === []) {
                $this->line("  <fg=green>✓</> {$slug}");

                continue;
            }

            $this->line("  <fg=yellow>•</> {$slug}");

            foreach ($issues as $issue) {
                if ($issue->isError()) {
                    $errors++;
                    $this->line("      <fg=red>ERROR</>   {$issue->message}");
                } else {
                    $warnings++;
                    $this->line("      <fg=yellow>WARNING</> {$issue->message}");
                }
            }
        }

        $this->newLine();
        $count = count($paths);
        $this->line("Sprawdzono {$count} post(ów): <fg=red>{$errors} błędów</>, <fg=yellow>{$warnings} ostrzeżeń</>.");

        if ($errors > 0) {
            return self::FAILURE;
        }

        if ($warnings > 0 && $this->option('strict')) {
            $this->error('--strict: ostrzeżenia traktowane jak błędy.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function resolvePaths(MarkdownSocialPostRepository $repository): array
    {
        $slug = (string) $this->argument('slug');

        if ($slug === '' || $this->option('all')) {
            return $repository->files();
        }

        $path = $repository->pathForSlug($slug);

        return File::exists($path) ? [$path] : [];
    }
}
