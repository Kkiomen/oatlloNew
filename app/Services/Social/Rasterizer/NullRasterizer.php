<?php

namespace App\Services\Social\Rasterizer;

/**
 * Rasteryzator, który niczego nie rasteryzuje.
 *
 * Używany przez `social:export --html-only` (iteracja designu bez odpalania
 * przeglądarki) i przez testy – dzięki temu SocialExportTest sprawdza logikę
 * eksportu, nie uruchamiając prawdziwego Chrome w CI.
 */
class NullRasterizer implements SocialRasterizer
{
    public function available(): bool
    {
        return true;
    }

    public function describe(): string
    {
        return 'NullRasterizer (bez rasteryzacji)';
    }

    public function rasterize(string $htmlPath, int $width, int $height, string $outPngPath): void
    {
        // Celowo nic. Eksport w trybie --html-only zostawia same dokumenty HTML.
    }
}
