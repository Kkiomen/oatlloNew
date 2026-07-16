<?php

namespace App\Services\Clip\Tts;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Realny głos przez ElevenLabs (endpoint `with-timestamps`: audio + wyrównanie
 * na poziomie ZNAKU, z którego składamy timestampy słów).
 *
 * Interfejs jest ten sam co MockTtsProvider, więc włączenie głosu nie rusza
 * stagera, lintu, cache'u ani Remotiona — zmieniasz tylko CLIP_TTS_DRIVER.
 * Guard jak w IndexNow: bez klucza rzuca jasny komunikat, nie produkuje pustki.
 *
 * Odpowiedź endpointu: { audio_base64, alignment: { characters,
 * character_start_times_seconds, character_end_times_seconds } }.
 */
class ElevenLabsTtsProvider implements TtsProvider
{
    public function id(): string
    {
        return 'elevenlabs:' . (string) config('clip.tts.elevenlabs.model', 'v2');
    }

    public function synthesize(string $text, string $voiceId): SynthesizedSpeech
    {
        $key = (string) config('clip.tts.elevenlabs.key', '');

        if ($key === '') {
            throw new RuntimeException(
                'ElevenLabs: brak ELEVENLABS_API_KEY. Ustaw klucz albo wróć na '
                . 'CLIP_TTS_DRIVER=mock (renderuje niemo, z poprawnym timingiem).'
            );
        }

        if ($voiceId === '') {
            throw new RuntimeException(
                'ElevenLabs: pusty voice_id. Uzupełnij config(\'clip.voices\') dla '
                . 'głosu użytego w scenariuszu.'
            );
        }

        $base = rtrim((string) config('clip.tts.elevenlabs.base_url', 'https://api.elevenlabs.io'), '/');

        $response = Http::withHeaders([
            'xi-api-key' => $key,
            'accept'     => 'application/json',
        ])
            ->timeout(120)
            ->post("{$base}/v1/text-to-speech/{$voiceId}/with-timestamps", [
                'text'     => $text,
                'model_id' => (string) config('clip.tts.elevenlabs.model', 'eleven_multilingual_v2'),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "ElevenLabs: HTTP {$response->status()} — {$response->body()}"
            );
        }

        $json = (array) $response->json();

        $audioBase64 = (string) ($json['audio_base64'] ?? '');
        if ($audioBase64 === '') {
            throw new RuntimeException('ElevenLabs: odpowiedź bez audio_base64.');
        }

        $audio = base64_decode($audioBase64, true);
        if ($audio === false) {
            throw new RuntimeException('ElevenLabs: audio_base64 nie jest poprawnym base64.');
        }

        $words = $this->wordsFromAlignment($json['alignment'] ?? null);
        $duration = $words === [] ? 0.0 : (float) end($words)['end'];

        return new SynthesizedSpeech(
            audio: $audio,
            ext: 'mp3',   // domyślny output_format ElevenLabs to mp3_44100_128
            duration: $duration,
            words: $words,
        );
    }

    /**
     * Składa słowa z wyrównania znakowego: idzie po znakach, a na białym znaku
     * domyka bieżące słowo (start = start pierwszego znaku, end = end ostatniego).
     *
     * @return list<array{text:string,start:float,end:float}>
     */
    private function wordsFromAlignment(mixed $alignment): array
    {
        if (! is_array($alignment)) {
            return [];
        }

        $chars = $alignment['characters'] ?? [];
        $starts = $alignment['character_start_times_seconds'] ?? [];
        $ends = $alignment['character_end_times_seconds'] ?? [];

        if (! is_array($chars) || ! is_array($starts) || ! is_array($ends)) {
            return [];
        }

        $words = [];
        $current = '';
        $wordStart = null;
        $lastEnd = 0.0;

        $flush = function () use (&$words, &$current, &$wordStart, &$lastEnd): void {
            if ($current !== '' && $wordStart !== null) {
                $words[] = [
                    'text'  => $current,
                    'start' => round((float) $wordStart, 3),
                    'end'   => round((float) $lastEnd, 3),
                ];
            }
            $current = '';
            $wordStart = null;
        };

        foreach ($chars as $i => $char) {
            $char = (string) $char;

            if (trim($char) === '') {
                $flush();

                continue;
            }

            if ($wordStart === null) {
                $wordStart = $starts[$i] ?? $lastEnd;
            }

            $current .= $char;
            $lastEnd = $ends[$i] ?? $lastEnd;
        }

        $flush();

        return $words;
    }
}
