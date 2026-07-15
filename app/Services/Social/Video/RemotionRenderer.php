<?php

namespace App\Services\Social\Video;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Odpala render Remotiona i zwraca ścieżkę do gotowego MP4.
 *
 * Remotion jest osobnym projektem Node (`social-video/`), a nie zależnością
 * Laravela – i tak ma zostać. Renderujemy WYŁĄCZNIE lokalnie, na maszynie
 * autora, dokładnie jak PNG-i: na produkcji nie ma node_modules i nie będzie
 * (deploy = git pull, patrz CLAUDE.md).
 *
 * Przez CLI idzie sam slug. Cała treść jedzie plikami z public/slides – gdyby
 * pchać HTML propsami, na wiersz poleceń poszłoby ~160 KB na slajd.
 */
class RemotionRenderer
{
    public function __construct(private ReelStager $stager)
    {
    }

    public function available(): bool
    {
        return is_dir($this->projectPath() . DIRECTORY_SEPARATOR . 'node_modules');
    }

    /**
     * @param  callable(string):void|null  $onProgress  Surowe linie z Remotiona
     *
     * @throws ProcessFailedException
     */
    public function render(string $slug, string $outPath, ?callable $onProgress = null): string
    {
        $process = new Process(
            [
                $this->npx(), 'remotion', 'render', 'Reel',
                $outPath,
                '--props=' . json_encode(['slug' => $slug, 'manifest' => null, 'html' => []]),
            ],
            $this->projectPath(),
            // Remotion woła Chrome'a; bez tego dziedziczy okrojone środowisko.
            null,
            null,
            (float) config('social.video.timeout'),
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
        return (string) config('social.video.project_path');
    }

    /**
     * Na Windowsie `npx` to plik .cmd – Symfony\Process nie odpala go bez
     * rozszerzenia, bo nie idzie przez powłokę.
     */
    private function npx(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'npx.cmd' : 'npx';
    }
}
