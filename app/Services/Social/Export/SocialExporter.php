<?php

namespace App\Services\Social\Export;

use App\Services\Social\Rasterizer\SocialRasterizer;
use App\Services\Social\SocialImageService;
use App\Services\Social\SocialPost;
use Illuminate\Support\Facades\File;

/**
 * Buduje folder gotowy do ręcznego wrzucenia na Instagram:
 *
 *   {out}/{slug}/01.png .. NN.png   slajdy W KOLEJNOŚCI (nazwa pliku = kolejność)
 *   {out}/{slug}/caption.txt        treść + hashtagi, do skopiowania
 *   {out}/{slug}/post.json          manifest (to z niego skorzysta przyszły publisher)
 *
 * W trybie --html-only zamiast PNG zostają dokumenty .html – do oglądania
 * i iterowania nad designem bez uruchamiania przeglądarki.
 *
 * Katalog docelowy to domyślnie storage/app/social-export, które jest
 * gitignorowane. Wyeksportowanych grafik NIGDY nie commitujemy.
 */
class SocialExporter
{
    public function __construct(
        private SocialImageService $images,
        private SocialRasterizer $rasterizer,
    ) {
    }

    /**
     * @param  string|null  $styleOverride  Wymusza skórkę (podgląd pakietu: `social:styles`)
     */
    public function export(
        SocialPost $post,
        ?string $outDir = null,
        bool $htmlOnly = false,
        ?string $styleOverride = null,
    ): SocialExportResult {
        $outDir ??= (string) config('social.export_path');
        $dir = rtrim($outDir, '/\\') . DIRECTORY_SEPARATOR . $post->slug;

        // Czyścimy folder, żeby po skróceniu karuzeli nie zostały sieroty
        // (np. stare 05.png przy nowych czterech slajdach).
        File::deleteDirectory($dir);
        File::ensureDirectoryExists($dir);

        $canvas = $post->type->canvas();
        $documents = $this->images->renderPost($post, $styleOverride);

        $imagePaths = $htmlOnly
            ? $this->writeHtml($documents, $dir)
            : $this->writePng($documents, $dir, $canvas['width'], $canvas['height']);

        $captionPath = $dir . DIRECTORY_SEPARATOR . 'caption.txt';
        File::put($captionPath, $post->captionWithHashtags() . "\n");

        $manifestPath = $dir . DIRECTORY_SEPARATOR . 'post.json';
        File::put($manifestPath, $this->manifest($post, $imagePaths, $htmlOnly));

        return new SocialExportResult(
            slug: $post->slug,
            directory: $dir,
            imagePaths: $imagePaths,
            captionPath: $captionPath,
            manifestPath: $manifestPath,
            htmlOnly: $htmlOnly,
        );
    }

    /**
     * @param  list<string>  $documents
     * @return list<string>
     */
    private function writeHtml(array $documents, string $dir): array
    {
        $paths = [];

        foreach ($documents as $i => $html) {
            $path = $dir . DIRECTORY_SEPARATOR . $this->slideName($i) . '.html';
            File::put($path, $html);
            $paths[] = $path;
        }

        return $paths;
    }

    /**
     * HTML idzie do katalogu tymczasowego, nie do folderu eksportu – ten ma
     * zostać czysty, żeby przy ręcznym uploadzie były w nim wyłącznie obrazki.
     *
     * @param  list<string>  $documents
     * @return list<string>
     */
    private function writePng(array $documents, string $dir, int $width, int $height): array
    {
        $tmp = $dir . DIRECTORY_SEPARATOR . '.html-tmp';
        File::ensureDirectoryExists($tmp);

        $paths = [];

        try {
            foreach ($documents as $i => $html) {
                $name = $this->slideName($i);
                $htmlPath = $tmp . DIRECTORY_SEPARATOR . $name . '.html';
                $pngPath = $dir . DIRECTORY_SEPARATOR . $name . '.png';

                File::put($htmlPath, $html);
                $this->rasterizer->rasterize($htmlPath, $width, $height, $pngPath);

                $paths[] = $pngPath;
            }
        } finally {
            File::deleteDirectory($tmp);
        }

        return $paths;
    }

    /**
     * Kolejność slajdów na Instagramie = kolejność plików, dlatego numer jest
     * zerowany do dwóch cyfr (10.png nie może wylądować przed 2.png).
     */
    private function slideName(int $index): string
    {
        return sprintf('%02d', $index + 1);
    }

    /**
     * @param  list<string>  $imagePaths
     */
    private function manifest(SocialPost $post, array $imagePaths, bool $htmlOnly): string
    {
        return (string) json_encode([
            'slug'      => $post->slug,
            'type'      => $post->type->value,
            'language'  => $post->language,
            'title'     => $post->title,
            'canvas'    => $post->type->canvas(),
            'link'      => $post->link,
            'publish_at' => $post->publishAt?->toIso8601String(),
            'status'    => $post->status,
            'hashtags'  => $post->hashtags,
            'caption'   => $post->captionWithHashtags(),
            'slides'    => count($imagePaths),
            'files'     => array_map('basename', $imagePaths),
            'html_only' => $htmlOnly,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
