<?php

namespace App\Console\Commands;

use App\Services\Clip\ClipLinter;
use App\Services\Clip\ClipLintIssue;
use App\Services\Clip\ClipRenderer;
use App\Services\Clip\ClipStager;
use App\Services\Clip\MarkdownClipRepository;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Renderuje clip (narrowane wideo MP4 1080x1920) ze scenariusza .md.
 *
 * Orkiestrator całej ścieżki: lint (bramka) -> stage (manifest + narracja) ->
 * render Remotiona. Ten sam wzorzec co `social:video` dla reeli.
 */
class ClipRender extends Command
{
    protected $signature = 'clip:render
                            {slug : Slug scenariusza}
                            {--out= : Plik docelowy (domyślnie storage/app/social-export/{slug}/clip.mp4)}
                            {--force : Zregeneruj narrację (inaczej cache)}
                            {--stage-only : Zbuduj sam wsad i nie renderuj (podgląd w Studiu)}
                            {--skip-lint : Pomiń bramkę lintu (debug, NIE do publikacji)}';

    protected $description = 'Renderuje clip (narrowane wideo MP4) ze scenariusza';

    public function handle(
        MarkdownClipRepository $repository,
        ClipLinter $linter,
        ClipStager $stager,
        ClipRenderer $renderer,
    ): int {
        $slug = (string) $this->argument('slug');
        $clip = $repository->findBySlug($slug);

        if ($clip === null) {
            $this->error("Nie ma scenariusza o slugu \"{$slug}\". Lista: resources/clips/*.md");

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
                $this->error("Render wstrzymany: {$errors} błędów.");

                return self::FAILURE;
            }
        }

        $this->line('  <fg=gray>buduję wsad (manifest + narracja)…</>');
        $dir = $stager->stage($clip, (bool) $this->option('force'));
        $this->line("  <fg=green>✓</> wsad: <fg=gray>{$dir}</>");

        if ($this->option('stage-only')) {
            $this->newLine();
            $this->info('Wsad gotowy. Podgląd w Studiu:');
            $this->line('  cd social-video && npx remotion studio');
            $this->line("  (w panelu propsów kompozycji Clip ustaw slug: {$slug})");

            return self::SUCCESS;
        }

        if (! $renderer->available()) {
            $this->error('Brak node_modules w social-video/. Odpal: cd social-video && npm i');

            return self::FAILURE;
        }

        $out = $this->outPath($slug);
        File::ensureDirectoryExists(dirname($out));

        $this->line('  <fg=gray>renderuję… (kilka minut – klatka po klatce)</>');

        try {
            $renderer->render($slug, $out, fn (string $line) => $this->output->write($line));
        } catch (ProcessFailedException $e) {
            $this->newLine();
            $this->error('Render padł. Wyjście Remotiona wyżej.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Gotowe: {$out}");

        return self::SUCCESS;
    }

    private function outPath(string $slug): string
    {
        if ($out = $this->option('out')) {
            return (string) $out;
        }

        return rtrim((string) config('clip.export_path'), '/\\')
            . DIRECTORY_SEPARATOR . $slug
            . DIRECTORY_SEPARATOR . 'clip.mp4';
    }
}
