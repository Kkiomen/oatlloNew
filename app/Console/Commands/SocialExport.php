<?php

namespace App\Console\Commands;

use App\Services\Social\EmbeddedFontProvider;
use App\Services\Social\Export\SocialExporter;
use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\Rasterizer\NullRasterizer;
use App\Services\Social\Rasterizer\RasterizationFailed;
use App\Services\Social\Rasterizer\SocialRasterizer;
use App\Services\Social\SocialImageService;
use App\Services\Social\SocialLintIssue;
use App\Services\Social\SocialPost;
use App\Services\Social\SocialPostLinter;
use App\Services\Social\SocialPostType;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Buduje grafiki i podpisy gotowe do ręcznego wrzucenia na Instagram.
 *
 * Lint jest BRAMKĄ: post z błędami się nie wyeksportuje. Powód jest prosty –
 * grafika z za długim tekstem zbuduje się bez protestu i po prostu wyjedzie poza
 * kanwę, a błąd zobaczylibyśmy dopiero na Instagramie.
 */
class SocialExport extends Command
{
    protected $signature = 'social:export
                            {slug? : Slug posta. Puste = wszystkie pasujące do filtrów}
                            {--all : Wszystkie posty (ignoruje --status)}
                            {--status=ready : Eksportuj tylko posty o tym statusie}
                            {--type= : Tylko ten typ (carousel|quote|announce|story)}
                            {--out= : Katalog docelowy (domyślnie storage/app/social-export)}
                            {--html-only : Nie rasteryzuj – zostaw dokumenty .html do podglądu}
                            {--skip-lint : Pomiń bramkę lintu (do debugowania, NIE do publikacji)}';

    protected $description = 'Eksportuje posty social media do PNG + caption.txt (ręczny upload)';

    public function handle(
        MarkdownSocialPostRepository $repository,
        SocialPostLinter $linter,
        SocialImageService $images,
        EmbeddedFontProvider $fonts,
    ): int {
        $htmlOnly = (bool) $this->option('html-only');

        if (! $fonts->available()) {
            $this->error('Brak plików fontu Montserrat – patrz config/social.fonts. Bez nich grafiki wyszłyby złym fontem.');

            return self::FAILURE;
        }

        $rasterizer = $this->rasterizer($htmlOnly);

        if (! $htmlOnly && ! $rasterizer->available()) {
            $this->error('Nie znaleziono przeglądarki do rasteryzacji. Ustaw SOCIAL_BROWSER_BINARY '
                . 'albo odpal z --html-only, żeby zobaczyć same dokumenty HTML.');

            return self::FAILURE;
        }

        $posts = $this->resolvePosts($repository);

        if ($posts->isEmpty()) {
            $this->error('Nie znaleziono postów do eksportu (sprawdź --status / --type).');

            return self::FAILURE;
        }

        if (! $this->option('skip-lint') && ! $this->lintGate($posts, $linter)) {
            return self::FAILURE;
        }

        $exporter = new SocialExporter($images, $rasterizer);
        $outDir = $this->option('out') ?: null;

        $this->newLine();

        foreach ($posts as $post) {
            try {
                $result = $exporter->export($post, $outDir, $htmlOnly);
            } catch (RasterizationFailed $e) {
                $this->error("  ✗ {$post->slug}: {$e->getMessage()}");

                return self::FAILURE;
            }

            $kind = $htmlOnly ? 'html' : 'png';
            $this->line("  <fg=green>✓</> {$post->slug}  [{$post->type->value}]  {$result->slideCount()} x {$kind}");
            $this->line("      <fg=gray>{$result->directory}</>");
        }

        $this->newLine();

        if ($htmlOnly) {
            $this->info('Tryb --html-only: otwórz pliki .html w przeglądarce, żeby obejrzeć grafiki.');
        } else {
            $this->info("Gotowe. Rasteryzacja: {$rasterizer->describe()}");
            $this->line('Wrzuć slajdy w kolejności nazw plików i wklej treść z caption.txt.');
        }

        return self::SUCCESS;
    }

    private function rasterizer(bool $htmlOnly): SocialRasterizer
    {
        return $htmlOnly ? new NullRasterizer() : app(SocialRasterizer::class);
    }

    /**
     * Bramka lintu: błędy blokują eksport, ostrzeżenia tylko wypisujemy.
     *
     * @param  Collection<int, SocialPost>  $posts
     */
    private function lintGate(Collection $posts, SocialPostLinter $linter): bool
    {
        $errors = 0;

        foreach ($posts as $post) {
            foreach ($linter->lintPost($post) as $issue) {
                if ($issue->level === SocialLintIssue::ERROR) {
                    $errors++;
                    $this->line("  <fg=red>ERROR</>   {$post->slug}: {$issue->message}");
                } else {
                    $this->line("  <fg=yellow>WARNING</> {$post->slug}: {$issue->message}");
                }
            }
        }

        if ($errors > 0) {
            $this->newLine();
            $this->error("Eksport wstrzymany: {$errors} błędów. Napraw je albo odpal z --skip-lint.");

            return false;
        }

        return true;
    }

    /**
     * @return Collection<int, SocialPost>
     */
    private function resolvePosts(MarkdownSocialPostRepository $repository): Collection
    {
        $slug = (string) $this->argument('slug');

        if ($slug !== '') {
            $post = $repository->findBySlug($slug);

            return collect(array_filter([$post]));
        }

        $posts = $repository->all();

        if (! $this->option('all')) {
            $status = (string) $this->option('status');
            $posts = $posts->filter(fn (SocialPost $p) => $p->status === $status)->values();
        }

        if ($type = $this->option('type')) {
            $enum = SocialPostType::tryFrom($type);
            $posts = $posts->filter(fn (SocialPost $p) => $p->type === $enum)->values();
        }

        return $posts;
    }
}
