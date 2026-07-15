<?php

namespace App\Services\Social\Rasterizer;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Rasteryzuje HTML do PNG headlessowym Chrome/Edge.
 *
 * Świadomie BEZ puppeteera/browsershota: przeglądarka i tak jest na maszynie,
 * a eksport odpalamy lokalnie. Zero nowych zależności composer/npm.
 */
class HeadlessBrowserRasterizer implements SocialRasterizer
{
    private ?string $binary = null;

    private bool $resolved = false;

    public function available(): bool
    {
        return $this->binary() !== null;
    }

    public function describe(): string
    {
        return $this->binary() ?? 'brak przeglądarki';
    }

    /**
     * Ścieżka do przeglądarki: jawny SOCIAL_BROWSER_BINARY, inaczej pierwszy
     * istniejący kandydat z configu.
     */
    public function binary(): ?string
    {
        if ($this->resolved) {
            return $this->binary;
        }

        $this->resolved = true;

        $explicit = config('social.browser.binary');
        if (is_string($explicit) && $explicit !== '') {
            return $this->binary = File::exists($explicit) ? $explicit : null;
        }

        foreach ((array) config('social.browser.candidates', []) as $candidate) {
            if (File::exists($candidate)) {
                return $this->binary = $candidate;
            }
        }

        return $this->binary = null;
    }

    /**
     * @throws RasterizationFailed
     */
    public function rasterize(string $htmlPath, int $width, int $height, string $outPngPath): void
    {
        $binary = $this->binary() ?? throw RasterizationFailed::noBrowser();

        File::ensureDirectoryExists(dirname($outPngPath));
        // Chrome nie nadpisuje pewnie istniejącego zrzutu – usuwamy z góry, żeby
        // brak pliku jednoznacznie znaczył "nie udało się".
        File::delete($outPngPath);

        $profile = $this->tempProfile();

        try {
            $process = new Process($this->arguments($binary, $htmlPath, $width, $height, $outPngPath, $profile));
            $process->setTimeout((float) config('social.browser.timeout', 60));
            $process->run();

            if (! $process->isSuccessful()) {
                throw RasterizationFailed::browserFailed($process->getErrorOutput(), (int) $process->getExitCode());
            }
        } catch (ProcessTimedOutException $e) {
            throw new RasterizationFailed('Przeglądarka nie odpowiedziała w czasie: ' . $e->getMessage(), 0, $e);
        } finally {
            File::deleteDirectory($profile);
        }

        if (! File::exists($outPngPath)) {
            throw RasterizationFailed::noOutput($outPngPath);
        }

        $this->assertSize($outPngPath, $width, $height);
    }

    /**
     * Argumenty headlessa. Każdy z nich siedzi tu z powodu, nie dla ozdoby.
     *
     * @return list<string>
     */
    public function arguments(string $binary, string $htmlPath, int $width, int $height, string $outPngPath, string $profile): array
    {
        return [
            $binary,
            '--headless=new',
            '--disable-gpu',
            // Zrzut nie może złapać paska przewijania.
            '--hide-scrollbars',
            // KRYTYCZNE: bez tego na ekranie HiDPI --window-size=1080,1350 daje
            // PNG 2160x2700. Instagram przyjąłby to bez słowa, a my byśmy się nie
            // dowiedzieli, że eksportujemy w złej skali.
            '--force-device-scale-factor=1',
            // Własny profil – headless potrafi zawisnąć albo nie zapisać zrzutu,
            // gdy trafi na profil żywej przeglądarki użytkownika.
            '--user-data-dir=' . $profile,
            '--no-first-run',
            '--no-default-browser-check',
            // Daje czas na dociągnięcie fontu z data: URI zanim padnie zrzut.
            '--virtual-time-budget=2000',
            "--window-size={$width},{$height}",
            '--screenshot=' . $outPngPath,
            $this->fileUrl($htmlPath),
        ];
    }

    /**
     * Ścieżka Windows -> URL file:// (backslashe i litera dysku).
     */
    public function fileUrl(string $path): string
    {
        $path = str_replace('\\', '/', (string) realpath($path) ?: $path);

        return str_starts_with($path, '/') ? 'file://' . $path : 'file:///' . $path;
    }

    /**
     * @throws RasterizationFailed
     */
    private function assertSize(string $path, int $width, int $height): void
    {
        $size = @getimagesize($path);

        if ($size === false) {
            throw RasterizationFailed::noOutput($path);
        }

        if ($size[0] !== $width || $size[1] !== $height) {
            throw RasterizationFailed::wrongSize($path, $size[0], $size[1], $width, $height);
        }
    }

    private function tempProfile(): string
    {
        $dir = storage_path('app/social-export/.chrome-profile-' . bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($dir);

        return $dir;
    }
}
