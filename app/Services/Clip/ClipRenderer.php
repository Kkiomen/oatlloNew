<?php

namespace App\Services\Clip;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Odpala render kompozycji Clip w Remotionie i zwraca ścieżkę do MP4.
 *
 * Bliźniak RemotionRenderer (reele), ale dla kompozycji `Clip`. Render jest
 * WYŁĄCZNIE lokalny — produkcja nie ma node_modules (deploy = git pull). Przez
 * CLI idzie sam slug; treść i audio jadą plikami z public/clips.
 */
class ClipRenderer
{
    public function available(): bool
    {
        return is_dir($this->projectPath() . DIRECTORY_SEPARATOR . 'node_modules');
    }

    /**
     * @param  callable(string):void|null  $onProgress
     *
     * @throws ProcessFailedException
     */
    public function render(string $slug, string $outPath, ?callable $onProgress = null): string
    {
        $process = new Process(
            [
                $this->npx(), 'remotion', 'render',
                (string) config('clip.video.composition', 'Clip'),
                $outPath,
                '--props=' . json_encode(['slug' => $slug, 'manifest' => null]),
            ],
            $this->projectPath(),
            null,
            null,
            (float) config('clip.video.timeout', 900),
        );

        $process->run(function (string $type, string $buffer) use ($onProgress) {
            if ($onProgress !== null) {
                $onProgress($buffer);
            }
        });

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outPath;
    }

    private function projectPath(): string
    {
        return (string) config('clip.video.project_path');
    }

    /**
     * Na Windowsie `npx` to plik .cmd — Symfony\Process nie odpala go bez
     * rozszerzenia (jak w RemotionRenderer reela).
     */
    private function npx(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'npx.cmd' : 'npx';
    }
}
