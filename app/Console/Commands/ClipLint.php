<?php

namespace App\Console\Commands;

use App\Services\Clip\ClipLinter;
use App\Services\Clip\MarkdownClipRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Waliduje scenariusze clipów. To bramka przed renderem — `clip:render` odpali
 * ten sam linter i odmówi budowania wideo z błędami (jak social:lint przy eksporcie).
 *
 * Exit != 0 przy jakimkolwiek ERRORze (warningi nie blokują bez --strict).
 */
class ClipLint extends Command
{
    protected $signature = 'clip:lint
                            {slug? : Slug scenariusza. Puste = wszystkie}
                            {--strict : Traktuj warningi jak błędy}';

    protected $description = 'Waliduje scenariusze clipów z resources/clips';

    public function handle(MarkdownClipRepository $repository, ClipLinter $linter): int
    {
        $paths = $this->resolvePaths($repository);

        if ($paths === []) {
            $this->error('Nie znaleziono scenariuszy do walidacji w ' . $repository->directory());

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
        $this->line("Sprawdzono {$count} scenariusz(y): <fg=red>{$errors} błędów</>, <fg=yellow>{$warnings} ostrzeżeń</>.");

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
    private function resolvePaths(MarkdownClipRepository $repository): array
    {
        $slug = (string) $this->argument('slug');

        if ($slug === '') {
            return $repository->files();
        }

        $path = $repository->pathForSlug($slug);

        return File::exists($path) ? [$path] : [];
    }
}
