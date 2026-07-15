<?php

namespace App\Services\Social\Rasterizer;

use RuntimeException;

class RasterizationFailed extends RuntimeException
{
    public static function noBrowser(): self
    {
        return new self(
            'Nie znaleziono przeglądarki do rasteryzacji. Ustaw SOCIAL_BROWSER_BINARY '
            . 'na ścieżkę do msedge.exe lub chrome.exe (kandydaci: config/social.php).'
        );
    }

    public static function browserFailed(string $stderr, int $exitCode): self
    {
        return new self("Przeglądarka zwróciła kod {$exitCode}. stderr: " . trim($stderr));
    }

    public static function noOutput(string $path): self
    {
        return new self("Przeglądarka nie zapisała pliku {$path}.");
    }

    /**
     * Najczęstsza przyczyna: HiDPI + brak --force-device-scale-factor=1, przez co
     * --window-size=1080,1350 daje PNG 2160x2700. Instagram przyjąłby to bez słowa,
     * a my byśmy się nie dowiedzieli – dlatego wymiary są twardo weryfikowane.
     */
    public static function wrongSize(string $path, int $gotW, int $gotH, int $wantW, int $wantH): self
    {
        return new self(
            "PNG {$path} ma {$gotW}x{$gotH}, oczekiwano {$wantW}x{$wantH}. "
            . 'Sprawdź, czy rasteryzator przekazuje --force-device-scale-factor=1.'
        );
    }
}
