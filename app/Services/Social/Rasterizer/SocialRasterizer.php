<?php

namespace App\Services\Social\Rasterizer;

/**
 * Zamienia dokument HTML na PNG o DOKŁADNIE zadanych wymiarach.
 *
 * Implementacja musi weryfikować wymiary wyjścia – patrz HeadlessBrowserRasterizer.
 */
interface SocialRasterizer
{
    /**
     * Czy rasteryzator da się w ogóle uruchomić (np. czy jest przeglądarka).
     */
    public function available(): bool;

    /**
     * Krótki opis narzędzia do komunikatów (np. ścieżka do przeglądarki).
     */
    public function describe(): string;

    /**
     * @throws RasterizationFailed
     */
    public function rasterize(string $htmlPath, int $width, int $height, string $outPngPath): void;
}
