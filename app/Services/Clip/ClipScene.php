<?php

namespace App\Services\Clip;

/**
 * Pojedyncza scena clipa. Niepersystowane DTO — moduł clip NIE MA TABELI.
 *
 * Zasada nadrzędna: SCENA = (narracja, wizual). `narration` jest pierwszorzędne
 * i uniwersalne, bo niesie trzy rzeczy naraz:
 *  1. tekst wysyłany do ElevenLabs,
 *  2. WYZNACZNIK długości sceny (scena trwa tyle, ile jej audio),
 *  3. źródło napisów (timestampy na poziomie słowa).
 *
 * `type` steruje komponentem w bibliotece Remotiona (title, code-reveal, …).
 * `params` to reszta pól sceny zależnych od typu (text, code, lang, items, cta…).
 * Parser NIE waliduje typu ani params — od tego jest lint. DTO ma powstać nawet
 * z niedokończonej sceny, inaczej `clip:lint` nie miałby czego zgłosić.
 */
final readonly class ClipScene
{
    /**
     * @param array<string,mixed>                  $params  Pola zależne od typu
     * @param list<array{name:string,at:float}>    $sfx     Cue'y efektów dźwiękowych
     */
    public function __construct(
        public int $index,
        public int $total,
        public string $type,
        public string $narration,
        public array $params,
        public array $sfx,
    ) {
    }

    public function isLast(): bool
    {
        return $this->index === $this->total;
    }

    public function hasNarration(): bool
    {
        return trim($this->narration) !== '';
    }

    /**
     * Liczba słów narracji — z tego mock TTS szacuje długość, a lint budżetuje.
     */
    public function narrationWordCount(): int
    {
        return str_word_count($this->narration);
    }

    /**
     * Wartość parametru sceny (np. text, lang, cta) lub null.
     */
    public function param(string $key): mixed
    {
        return $this->params[$key] ?? null;
    }

    /**
     * Kod sceny (dla type: code-reveal / terminal) — surowy, bez ogrodzenia.
     */
    public function code(): ?string
    {
        $code = $this->params['code'] ?? null;

        return is_scalar($code) && trim((string) $code) !== '' ? rtrim((string) $code) : null;
    }

    /**
     * Linie kodu — do budżetu lintu (za dużo linii = overflow za krawędź).
     */
    public function codeLines(): int
    {
        $code = $this->code();

        return $code === null ? 0 : substr_count($code, "\n") + 1;
    }

    /**
     * Najdłuższa linia kodu w kolumnach — do budżetu lintu.
     */
    public function codeMaxColumns(): int
    {
        $code = $this->code();

        if ($code === null) {
            return 0;
        }

        $max = 0;

        foreach (preg_split('/\R/', $code) ?: [] as $line) {
            $max = max($max, mb_strlen($line));
        }

        return $max;
    }

    /**
     * Cały widoczny tekst sceny (narracja + text + items + cta) — do kontroli
     * glifów spoza fontu. Kod pomijamy: ma własny font i własny budżet.
     *
     * @return list<string>
     */
    public function visibleText(): array
    {
        $texts = [$this->narration];

        foreach (['text', 'cta', 'title', 'label'] as $key) {
            $value = $this->params[$key] ?? null;
            if (is_scalar($value)) {
                $texts[] = (string) $value;
            }
        }

        foreach ((array) ($this->params['items'] ?? []) as $item) {
            if (is_scalar($item)) {
                $texts[] = (string) $item;
            }
        }

        return array_values(array_filter($texts, fn (string $t) => trim($t) !== ''));
    }
}
