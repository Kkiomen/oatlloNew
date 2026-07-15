<?php

namespace App\Console\Commands;

use App\Services\Social\EmbeddedFontProvider;
use App\Services\Social\Export\SocialExporter;
use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\Rasterizer\NullRasterizer;
use App\Services\Social\Rasterizer\RasterizationFailed;
use App\Services\Social\Rasterizer\SocialRasterizer;
use App\Services\Social\SocialImageService;
use App\Services\Social\SocialStyleResolver;
use Illuminate\Console\Command;

/**
 * Przegląd pakietu stylów.
 *
 * Bez argumentu: wypisuje style i to, który post dostał który (dobór jest
 * automatyczny, więc trzeba go dać się zobaczyć).
 * Ze slugiem: renderuje TEN SAM post we WSZYSTKICH stylach, żeby porównać je
 * na realnej treści zamiast na wyobrażeniu.
 */
class SocialStyles extends Command
{
    protected $signature = 'social:styles
                            {slug? : Wyrenderuj ten post we wszystkich stylach}
                            {--out= : Katalog docelowy}
                            {--html-only : Nie rasteryzuj, zostaw .html}';

    protected $description = 'Pokazuje pakiet stylów i dobór stylu dla postów';

    public function handle(
        MarkdownSocialPostRepository $repository,
        SocialStyleResolver $styles,
        SocialImageService $images,
    ): int {
        $slug = (string) $this->argument('slug');

        return $slug === ''
            ? $this->overview($repository, $styles)
            : $this->renderAllStyles($slug, $repository, $styles, $images);
    }

    /**
     * Pakiet + który post dostał który styl.
     */
    private function overview(MarkdownSocialPostRepository $repository, SocialStyleResolver $styles): int
    {
        $rows = [];
        foreach ($styles->all() as $name => $style) {
            $rows[] = [$name, $style['label'] ?? $name, $style['summary'] ?? ''];
        }

        $this->table(['Styl', 'Nazwa', 'Opis'], $rows);

        $posts = $repository->all();

        if ($posts->isEmpty()) {
            return self::SUCCESS;
        }

        $this->newLine();
        $this->line('Dobór dla istniejących postów (jawny <info>style:</info> wygrywa nad automatem):');

        $assigned = [];
        foreach ($posts as $post) {
            $style = $styles->resolve($post);
            $assigned[] = [
                $post->slug,
                $post->type->value,
                $style,
                $post->style !== null ? 'jawny' : 'auto',
            ];
        }

        $this->table(['Post', 'Typ', 'Styl', 'Skąd'], $assigned);

        return self::SUCCESS;
    }

    /**
     * Ten sam post w każdym stylu – jedyny uczciwy sposób na porównanie pakietu.
     */
    private function renderAllStyles(
        string $slug,
        MarkdownSocialPostRepository $repository,
        SocialStyleResolver $styles,
        SocialImageService $images,
    ): int {
        $post = $repository->findBySlug($slug);

        if ($post === null) {
            $this->error("Nie ma posta '{$slug}'.");

            return self::FAILURE;
        }

        $htmlOnly = (bool) $this->option('html-only');
        $rasterizer = $htmlOnly ? new NullRasterizer() : app(SocialRasterizer::class);

        if (! $htmlOnly && ! $rasterizer->available()) {
            $this->error('Brak przeglądarki do rasteryzacji. Użyj --html-only albo ustaw SOCIAL_BROWSER_BINARY.');

            return self::FAILURE;
        }

        $outDir = rtrim($this->option('out') ?: storage_path('app/social-styles'), '/\\');
        $exporter = new SocialExporter($images, $rasterizer);

        foreach ($styles->names() as $style) {
            try {
                // Eksportujemy do podkatalogu per styl, więc slug się nie nadpisuje.
                $result = $exporter->export($post, $outDir . DIRECTORY_SEPARATOR . $style, $htmlOnly, $style);
            } catch (RasterizationFailed $e) {
                $this->error("  ✗ {$style}: {$e->getMessage()}");

                return self::FAILURE;
            }

            $this->line("  <fg=green>✓</> {$style}  ({$result->slideCount()} slajd) -> {$result->directory}");
        }

        $this->newLine();
        $this->info("Post '{$slug}' w {$this->countStyles($styles)} stylach: {$outDir}");
        $this->line('Automat wybrałby dla niego: <info>' . $styles->resolve($post) . '</info>');

        return self::SUCCESS;
    }

    private function countStyles(SocialStyleResolver $styles): int
    {
        return count($styles->names());
    }
}
