<?php

namespace App\Services\Clip\Tts;

/**
 * TTS bez klucza: produkuje CISZĘ o oszacowanej długości + syntetyczne timestampy.
 *
 * Dzięki temu cały pipeline renderuje się od pierwszego dnia — dostajesz niemy
 * film z DOKŁADNIE tym timingiem i tymi napisami, co będzie miał z głosem. Gdy
 * dołożysz ElevenLabs, zmienia się tylko ścieżka dźwiękowa; długości scen,
 * układ napisów i cały Remotion zostają identyczne.
 *
 * Cisza to poprawny plik WAV (PCM, same zera) — bez enkodera i bez zależności.
 * Remotion odtwarza WAV tak samo jak MP3, więc komponent <Audio> jest ten sam.
 */
class MockTtsProvider implements TtsProvider
{
    private const SAMPLE_RATE = 44100;

    public function id(): string
    {
        return 'mock';
    }

    public function synthesize(string $text, string $voiceId): SynthesizedSpeech
    {
        $words = $this->splitWords($text);
        $duration = $this->estimateDuration($words);

        return new SynthesizedSpeech(
            audio: $this->silentWav($duration),
            ext: 'wav',
            duration: $duration,
            words: $this->distributeWords($words, $duration),
        );
    }

    /**
     * @return list<string>
     */
    private function splitWords(string $text): array
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);

        return $words === false ? [] : array_values($words);
    }

    /**
     * Długość ciszy = liczba słów ÷ tempo mówienia. Minimum pół sekundy, żeby
     * nawet jednowyrazowa scena miała audio o niezerowej długości.
     *
     * @param list<string> $words
     */
    private function estimateDuration(array $words): float
    {
        $wpm = max(1, (int) config('clip.tts.words_per_min', 150));
        $seconds = (count($words) / $wpm) * 60.0;

        return round(max(0.5, $seconds), 3);
    }

    /**
     * Rozkłada słowa po długości ciszy PROPORCJONALNIE do długości słowa
     * (dłuższe słowo trwa dłużej) — bliżej realnego rytmu niż równy podział,
     * więc podgląd napisów na mocku wygląda sensownie.
     *
     * @param  list<string> $words
     * @return list<array{text:string,start:float,end:float}>
     */
    private function distributeWords(array $words, float $duration): array
    {
        if ($words === []) {
            return [];
        }

        $weights = array_map(fn (string $w) => mb_strlen($w) + 1, $words);
        $totalWeight = array_sum($weights);

        $timings = [];
        $cursor = 0.0;

        foreach ($words as $i => $word) {
            $span = ($weights[$i] / $totalWeight) * $duration;
            $timings[] = [
                'text'  => $word,
                'start' => round($cursor, 3),
                'end'   => round($cursor + $span, 3),
            ];
            $cursor += $span;
        }

        return $timings;
    }

    /**
     * Minimalny poprawny WAV: 16-bit PCM mono, same zera (cisza).
     */
    private function silentWav(float $duration): string
    {
        $numSamples = (int) round($duration * self::SAMPLE_RATE);
        $dataSize = $numSamples * 2;              // 16-bit = 2 bajty/próbkę
        $byteRate = self::SAMPLE_RATE * 2;
        $blockAlign = 2;

        $header = 'RIFF'
            . pack('V', 36 + $dataSize)
            . 'WAVE'
            . 'fmt '
            . pack('V', 16)                       // rozmiar bloku fmt
            . pack('v', 1)                        // PCM
            . pack('v', 1)                        // mono
            . pack('V', self::SAMPLE_RATE)
            . pack('V', $byteRate)
            . pack('v', $blockAlign)
            . pack('v', 16)                       // bity/próbkę
            . 'data'
            . pack('V', $dataSize);

        return $header . str_repeat("\x00", $dataSize);
    }
}
