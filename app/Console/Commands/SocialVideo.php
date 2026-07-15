<?php

namespace App\Console\Commands;

use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\SocialLintIssue;
use App\Services\Social\SocialPost;
use App\Services\Social\SocialPostLinter;
use App\Services\Social\Video\ReelStager;
use App\Services\Social\Video\RemotionRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Buduje Reela (MP4 1080x1920) z posta .md.
 *
 * Reel to nie nowy byt – to ten sam post co karuzela, tylko w ruchu. Slajdy są
 * DOKŁADNIE tymi dokumentami, które idą na PNG, więc wideo nie może rozjechać
 * się z kafelkiem w feedzie.
 *
 * Lint jest bramką z tego samego powodu co przy eksporcie: tekst poza kanwą nie
 * rzuci wyjątku, tylko po cichu wyjedzie za krawędź – a w wideo kosztowałoby to
 * kilka minut renderu, zanim byś to zobaczył.
 */
class SocialVideo extends Command
{
    protected $signature = 'social:video
                            {slug : Slug posta}
                            {--out= : Plik docelowy (domyślnie storage/app/social-export/{slug}/reel.mp4)}
                            {--stage-only : Zbuduj sam wsad dla Remotiona i nie renderuj (podgląd w Studiu)}
                            {--skip-lint : Pomiń bramkę lintu (do debugowania, NIE do publikacji)}';

    protected $description = 'Renderuje Reela (MP4 1080x1920) z posta social media';

    public function handle(
        MarkdownSocialPostRepository $repository,
        SocialPostLinter $linter,
        ReelStager $stager,
        RemotionRenderer $renderer,
    ): int {
        $slug = (string) $this->argument('slug');
        $post = $repository->findBySlug($slug);

        if ($post === null) {
            $this->error("Nie ma posta o slugu \"{$slug}\". Lista: php artisan social:list");

            return self::FAILURE;
        }

        if (! $this->option('skip-lint') && ! $this->lintGate($post, $linter)) {
            return self::FAILURE;
        }

        $dir = $stager->stage($post);
        $this->line("  <fg=green>✓</> wsad: <fg=gray>{$dir}</>");

        if ($this->option('stage-only')) {
            $this->newLine();
            $this->info('Wsad gotowy. Podgląd w Studiu:');
            $this->line('  cd social-video && npx remotion studio');
            $this->line("  (w panelu propsów ustaw slug: {$slug})");

            return self::SUCCESS;
        }

        if (! $renderer->available()) {
            $this->error('Brak node_modules w social-video/. Odpal: cd social-video && npm i');

            return self::FAILURE;
        }

        $out = $this->outPath($post);
        File::ensureDirectoryExists(dirname($out));

        $this->line('  <fg=gray>renderuję… (kilka minut – to nie zrzut ekranu, tylko klatka po klatce)</>');

        try {
            $renderer->render($post->slug, $out, fn (string $line) => $this->output->write($line));
        } catch (ProcessFailedException $e) {
            $this->newLine();
            $this->error('Render padł. Wyjście Remotiona wyżej.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Gotowe: {$out}");
        $this->line('Podpis jest w caption.txt obok – zrób `social:export`, jeśli go jeszcze nie ma.');

        return self::SUCCESS;
    }

    private function outPath(SocialPost $post): string
    {
        if ($out = $this->option('out')) {
            return (string) $out;
        }

        return rtrim((string) config('social.export_path'), '/\\')
            . DIRECTORY_SEPARATOR . $post->slug
            . DIRECTORY_SEPARATOR . 'reel.mp4';
    }

    private function lintGate(SocialPost $post, SocialPostLinter $linter): bool
    {
        $errors = 0;

        foreach ($linter->lintPost($post) as $issue) {
            if ($issue->level === SocialLintIssue::ERROR) {
                $errors++;
                $this->line("  <fg=red>ERROR</>   {$issue->message}");
            } else {
                $this->line("  <fg=yellow>WARNING</> {$issue->message}");
            }
        }

        if ($errors > 0) {
            $this->error("Render wstrzymany: {$errors} błędów.");

            return false;
        }

        return true;
    }
}
