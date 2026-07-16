<?php

namespace App\Services\Clip\Tts;

/**
 * Surowy wynik syntezy mowy z providera TTS: bajty audio + wyrównanie na
 * poziomie słowa. To jest kontrakt, który każdy provider (mock, ElevenLabs)
 * zwraca identycznie — dzięki temu podmiana providera nie rusza reszty pipeline'u.
 *
 * `words` niesie timestampy słów (start/end w sekundach) — z tego powstają napisy
 * i cue'y animacji. ElevenLabs daje je z endpointu `with-timestamps`; mock rozkłada
 * słowa równo po długości ciszy.
 */
final readonly class SynthesizedSpeech
{
    /**
     * @param string                                        $audio    Bajty pliku audio
     * @param string                                        $ext      Rozszerzenie (wav / mp3)
     * @param float                                         $duration Długość w sekundach
     * @param list<array{text:string,start:float,end:float}> $words   Timestampy słów
     */
    public function __construct(
        public string $audio,
        public string $ext,
        public float $duration,
        public array $words,
    ) {
    }
}
