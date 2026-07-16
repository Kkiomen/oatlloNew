<?php

namespace App\Services\Clip;

use App\Services\Clip\Tts\ClipNarrator;
use App\Services\Theme\TechThemeResolver;
use Illuminate\Support\Facades\File;

/**
 * Buduje wsad dla Remotiona: manifest clip.json + pliki audio narracji.
 *
 * Podział ról jak przy reelu: PHP wie WSZYSTKO o treści (ile scen, jaki typ,
 * jaki akcent, ILE TRWA scena — a to wynika z długości narracji), Remotion wie
 * tylko, jak to poruszyć. Manifest to kontrakt między nimi.
 *
 * Katalog docelowy jest CZYSZCZONY: po skróceniu scenariusza nie mogą zostać
 * sieroty (stare 08.wav myliłoby przy podglądzie w Studiu).
 */
class ClipStager
{
    public function __construct(
        private ClipNarrator $narrator,
        private TechThemeResolver $theme,
    ) {
    }

    /**
     * @param bool $force Wymuś regenerację narracji (inaczej cache)
     *
     * @return string Ścieżka katalogu wsadu
     */
    public function stage(Clip $clip, bool $force = false): string
    {
        $dir = $this->clipPath($clip->slug);
        $audioDir = $dir . DIRECTORY_SEPARATOR . 'audio';

        File::deleteDirectory($dir);
        File::ensureDirectoryExists($audioDir);

        $fps = (int) config('clip.fps');
        $voiceId = (string) (config('clip.voices')[$clip->voice] ?? '');

        $scenes = [];

        foreach ($clip->scenes as $scene) {
            $scenes[] = $this->stageScene($scene, $voiceId, $fps, $audioDir, $force);
        }

        File::put(
            $dir . DIRECTORY_SEPARATOR . 'clip.json',
            (string) json_encode($this->manifest($clip, $fps, $scenes),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return $dir;
    }

    /**
     * @return array<string,mixed>
     */
    private function stageScene(ClipScene $scene, string $voiceId, int $fps, string $audioDir, bool $force): array
    {
        $timing = (array) config('clip.timing');
        $file = sprintf('%02d', $scene->index);

        $narration = null;
        $narrationBlock = null;
        $narrationFrames = 0;

        if ($scene->hasNarration()) {
            $narration = $this->narrator->narrate($scene->narration, $voiceId, $force);

            $audioFile = $file . '.' . $narration->ext;
            File::copy($narration->audioPath, $audioDir . DIRECTORY_SEPARATOR . $audioFile);

            $narrationFrames = $narration->frames($fps);
            $narrationBlock = [
                'audio'    => 'audio/' . $audioFile,
                'leadIn'   => (int) ($timing['lead'] ?? 0),   // klatek ciszy PRZED głosem
                'duration' => $narration->duration,
                'words'    => $narration->words,
            ];
        }

        // Długość sceny = lead + narracja + tail, z podłogą `min`. GÓRNEGO limitu
        // NIE stosujemy: obciąłby narrację. `max` pilnuje lint (warning), nie stager.
        $duration = (int) ($timing['lead'] ?? 0) + $narrationFrames + (int) ($timing['tail'] ?? 0);
        $duration = max((int) ($timing['min'] ?? 1), $duration);

        return [
            'type'             => $scene->type,
            'durationInFrames' => $duration,
            'params'           => (object) $scene->params,
            'narration'        => $narrationBlock,
            'sfx'              => $this->stageSfx($scene, $fps),
        ];
    }

    /**
     * SFX -> {file, atFrame}. `at` to SEKUNDY od początku sceny. Nazwa spoza
     * biblioteki jest pomijana (lint już to zgłosił jako warning).
     *
     * @return list<array{file:string,atFrame:int}>
     */
    private function stageSfx(ClipScene $scene, int $fps): array
    {
        $library = (array) config('clip.sfx', []);
        $out = [];

        foreach ($scene->sfx as $cue) {
            $filename = $library[$cue['name']] ?? null;
            if ($filename === null) {
                continue;
            }

            $out[] = [
                'file'    => 'sfx/' . $filename,
                'atFrame' => (int) round($cue['at'] * $fps),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string,mixed>> $scenes
     * @return array<string,mixed>
     */
    private function manifest(Clip $clip, int $fps, array $scenes): array
    {
        $haystack = $clip->themeHaystack();
        $music = $clip->music;
        $musicFile = $music !== null ? (config('clip.music')[$music] ?? null) : null;

        return [
            'slug'   => $clip->slug,
            'title'  => $clip->title,
            'fps'    => $fps,
            'canvas' => [
                'width'  => (int) config('clip.canvas.width'),
                'height' => (int) config('clip.canvas.height'),
            ],
            'theme' => [
                'accent' => $this->theme->accentHexFromText($haystack),
                'logo'   => $this->theme->keyFromText($haystack),   // null => bez logo
            ],
            'captions' => [
                'enabled' => (bool) config('clip.captions.enabled', true),
                'mode'    => (string) config('clip.captions.mode', 'karaoke'),
            ],
            'music'  => $musicFile !== null ? 'music/' . $musicFile : null,
            'scenes' => $scenes,
        ];
    }

    public function clipPath(string $slug): string
    {
        return rtrim((string) config('clip.video.project_path'), '/\\')
            . DIRECTORY_SEPARATOR . 'public'
            . DIRECTORY_SEPARATOR . 'clips'
            . DIRECTORY_SEPARATOR . $slug;
    }
}
