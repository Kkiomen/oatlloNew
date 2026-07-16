<?php

namespace App\Services\Clip\Tts;

/**
 * Narracja jednej sceny PO cache'owaniu: ścieżka do pliku audio na dysku +
 * długość + timestampy słów. To tego używa stager, budując manifest.
 *
 * Różnica wobec SynthesizedSpeech: tam są surowe BAJTY prosto z providera, tu
 * jest już zapisany PLIK (bo audio trafia do public/ Remotiona i do cache'u).
 */
final readonly class Narration
{
    /**
     * @param list<array{text:string,start:float,end:float}> $words
     */
    public function __construct(
        public string $audioPath,
        public string $ext,
        public float $duration,
        public array $words,
        public bool $fromCache,
    ) {
    }

    /**
     * Długość narracji w klatkach — z tego stager liczy długość sceny.
     */
    public function frames(int $fps): int
    {
        return (int) ceil($this->duration * $fps);
    }
}
