<?php

namespace App\Services\Clip\Tts;

use Illuminate\Support\Facades\File;

/**
 * Zamienia tekst narracji na plik audio + timestampy, z CACHE'em po odcisku treści.
 *
 * Audio jest ARTEFAKTEM, nie źródłem: wyliczalne ze scenariusza, ale kosztuje API
 * (przy ElevenLabs). Dlatego cache po sha1(provider + głos + tekst): niezmieniona
 * narracja między renderami = zero kosztu, zmiana choćby jednego słowa = nowy hash
 * i regeneracja. To ten sam wzorzec, co `fingerprint` w weryfikacji postów.
 *
 * Cache leży w storage/app/clip-audio (gitignorowane, jak PNG-i i reele).
 */
class ClipNarrator
{
    public function __construct(private TtsProvider $provider)
    {
    }

    /**
     * Zwraca narrację dla tekstu — z cache'u albo świeżo zsyntezowaną i zapisaną.
     *
     * @param bool $force Wymuś regenerację nawet przy trafieniu w cache
     */
    public function narrate(string $text, string $voiceId, bool $force = false): Narration
    {
        $dir = $this->cacheDir();
        File::ensureDirectoryExists($dir);

        $hash = $this->hash($text, $voiceId);
        $metaPath = $dir . DIRECTORY_SEPARATOR . $hash . '.json';

        if (! $force && File::exists($metaPath)) {
            $meta = json_decode(File::get($metaPath), true);

            if (is_array($meta) && isset($meta['ext'])) {
                $audioPath = $dir . DIRECTORY_SEPARATOR . $hash . '.' . $meta['ext'];

                if (File::exists($audioPath)) {
                    return new Narration(
                        audioPath: $audioPath,
                        ext: (string) $meta['ext'],
                        duration: (float) ($meta['duration'] ?? 0.0),
                        words: $this->normalizeWords($meta['words'] ?? []),
                        fromCache: true,
                    );
                }
            }
        }

        $speech = $this->provider->synthesize($text, $voiceId);

        $audioPath = $dir . DIRECTORY_SEPARATOR . $hash . '.' . $speech->ext;
        File::put($audioPath, $speech->audio);
        File::put($metaPath, (string) json_encode([
            'ext'      => $speech->ext,
            'duration' => $speech->duration,
            'words'    => $speech->words,
            'provider' => $this->provider->id(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return new Narration(
            audioPath: $audioPath,
            ext: $speech->ext,
            duration: $speech->duration,
            words: $speech->words,
            fromCache: false,
        );
    }

    /**
     * Odcisk treści narracji. Provider wchodzi do hashu, żeby zmiana
     * mock -> elevenlabs unieważniła ciszę.
     */
    public function hash(string $text, string $voiceId): string
    {
        return sha1($this->provider->id() . '|' . $voiceId . '|' . $text);
    }

    private function cacheDir(): string
    {
        return rtrim((string) config('clip.tts.cache_path'), '/\\');
    }

    /**
     * @return list<array{text:string,start:float,end:float}>
     */
    private function normalizeWords(mixed $words): array
    {
        if (! is_array($words)) {
            return [];
        }

        $out = [];
        foreach ($words as $word) {
            if (is_array($word) && isset($word['text'])) {
                $out[] = [
                    'text'  => (string) $word['text'],
                    'start' => (float) ($word['start'] ?? 0.0),
                    'end'   => (float) ($word['end'] ?? 0.0),
                ];
            }
        }

        return $out;
    }
}
