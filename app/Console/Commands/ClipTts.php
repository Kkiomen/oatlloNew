<?php

namespace App\Console\Commands;

use App\Services\Clip\MarkdownClipRepository;
use App\Services\Clip\Tts\ClipNarrator;
use App\Services\Clip\Tts\TtsProvider;
use Illuminate\Console\Command;

/**
 * Generuje (i cache'uje) narrację scenariusza: jeden plik audio + timestampy na
 * scenę. Nie renderuje wideo — tylko grzeje cache, którego użyje `clip:stage`.
 *
 * Cache jest po odcisku treści: druga próba tej samej narracji nie woła providera.
 * Na driverze `mock` audio to cisza o poprawnej długości — pipeline działa bez klucza.
 */
class ClipTts extends Command
{
    protected $signature = 'clip:tts
                            {slug : Slug scenariusza}
                            {--force : Zregeneruj nawet przy trafieniu w cache}';

    protected $description = 'Generuje narrację (audio + timestampy) dla scenariusza clipa';

    public function handle(
        MarkdownClipRepository $repository,
        ClipNarrator $narrator,
        TtsProvider $provider,
    ): int {
        $slug = (string) $this->argument('slug');
        $clip = $repository->findBySlug($slug);

        if ($clip === null) {
            $this->error("Nie ma scenariusza o slugu \"{$slug}\".");

            return self::FAILURE;
        }

        $voiceId = (string) (config('clip.voices')[$clip->voice] ?? '');
        $force = (bool) $this->option('force');

        $this->line("Provider: <fg=cyan>{$provider->id()}</>  Głos: <fg=cyan>{$clip->voice}</>");

        if ($provider->id() === 'mock') {
            $this->line('  <fg=gray>(mock — cisza o oszacowanej długości; render będzie niemy)</>');
        }

        $total = 0.0;

        foreach ($clip->scenes as $scene) {
            if (! $scene->hasNarration()) {
                $this->line("  <fg=yellow>—</> scena #{$scene->index}: brak narracji, pomijam");

                continue;
            }

            $narration = $narrator->narrate($scene->narration, $voiceId, $force);
            $total += $narration->duration;

            $tag = $narration->fromCache ? '<fg=gray>cache</>' : '<fg=green>nowe</>';
            $this->line(sprintf(
                '  <fg=green>✓</> scena #%d: %.2fs (%d słów) %s',
                $scene->index,
                $narration->duration,
                count($narration->words),
                $tag,
            ));
        }

        $this->newLine();
        $this->info(sprintf('Narracja gotowa: %d scen, ~%.1fs łącznie.', $clip->sceneCount(), $total));

        return self::SUCCESS;
    }
}
