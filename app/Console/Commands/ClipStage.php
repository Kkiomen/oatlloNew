<?php

namespace App\Console\Commands;

use App\Services\Clip\ClipLinter;
use App\Services\Clip\ClipLintIssue;
use App\Services\Clip\ClipStager;
use App\Services\Clip\MarkdownClipRepository;
use Illuminate\Console\Command;

/**
 * Buduje wsad Remotiona (clip.json + audio) z scenariusza .md.
 *
 * Lint jest bramką z tego samego powodu co przy reelu: nieznany typ sceny albo
 * brak narracji nie rzuci wyjątku, tylko cicho popsuje film — a render to minuty.
 * `--skip-lint` tylko do debugowania.
 */
class ClipStage extends Command
{
    protected $signature = 'clip:stage
                            {slug : Slug scenariusza}
                            {--force : Zregeneruj narrację (inaczej cache)}
                            {--skip-lint : Pomiń bramkę lintu (debug, NIE do renderu)}';

    protected $description = 'Buduje wsad Remotiona (manifest + audio) dla clipa';

    public function handle(
        MarkdownClipRepository $repository,
        ClipLinter $linter,
        ClipStager $stager,
    ): int {
        $slug = (string) $this->argument('slug');
        $clip = $repository->findBySlug($slug);

        if ($clip === null) {
            $this->error("Nie ma scenariusza o slugu \"{$slug}\".");

            return self::FAILURE;
        }

        if (! $this->option('skip-lint')) {
            $errors = 0;
            foreach ($linter->lintClip($clip) as $issue) {
                if ($issue->level === ClipLintIssue::ERROR) {
                    $errors++;
                    $this->line("  <fg=red>ERROR</>   {$issue->message}");
                } else {
                    $this->line("  <fg=yellow>WARNING</> {$issue->message}");
                }
            }
            if ($errors > 0) {
                $this->error("Stage wstrzymany: {$errors} błędów.");

                return self::FAILURE;
            }
        }

        $dir = $stager->stage($clip, (bool) $this->option('force'));

        $this->line("  <fg=green>✓</> wsad: <fg=gray>{$dir}</>");
        $this->info('Podgląd w Studiu: cd social-video && npx remotion studio (kompozycja Clip, slug w propsach).');

        return self::SUCCESS;
    }
}
