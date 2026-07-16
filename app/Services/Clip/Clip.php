<?php

namespace App\Services\Clip;

/**
 * Narrowane wideo ("clip") zbudowane z pliku .md. Niepersystowane DTO — moduł
 * clip NIE MA TABELI i nigdy nie dotyka bazy (jak reszta modułu social).
 *
 * Clip to sekwencja scen; każda scena = (narracja, wizual). Długość clipa
 * wynika z sumy długości narracji scen — timing bierze się z audio, nie z
 * ustawień. Patrz docs/narrated-video-architecture.md.
 */
final readonly class Clip
{
    /**
     * @param list<string>    $hashtags
     * @param list<string>    $platforms  tiktok / shorts / reels — etykieta docelowa
     * @param list<ClipScene> $scenes
     */
    public function __construct(
        public string $slug,
        public string $language,
        public string $title,
        /** Tekst, po którym dobierany jest motyw technologii (logo + akcent). */
        public ?string $topic,
        /** Klucz głosu z config('clip.voices'). */
        public string $voice,
        /** Slug artykułu źródłowego — do weryfikacji faktów, nie renderuje się. */
        public ?string $source,
        /** Klucz podkładu muzycznego z config('clip.music') lub null. */
        public ?string $music,
        public array $platforms,
        public string $caption,
        public array $hashtags,
        public array $scenes,
    ) {
    }

    public function sceneCount(): int
    {
        return count($this->scenes);
    }

    /**
     * Liczba słów całej narracji — do budżetu długości filmu.
     */
    public function totalNarrationWords(): int
    {
        return array_sum(array_map(fn (ClipScene $s) => $s->narrationWordCount(), $this->scenes));
    }

    /**
     * Podpis gotowy do wklejenia: treść + pusta linia + hashtagi.
     */
    public function captionWithHashtags(): string
    {
        $caption = rtrim($this->caption);

        if ($this->hashtags === []) {
            return $caption;
        }

        $tags = implode(' ', array_map(fn (string $t) => '#' . ltrim($t, '#'), $this->hashtags));

        return $caption === '' ? $tags : $caption . "\n\n" . $tags;
    }

    /**
     * Tekst, po którym dobierany jest motyw technologii (logo + akcent).
     * Jawny `topic:` wygrywa; inaczej zgadujemy z tytułu, sluga i źródła.
     */
    public function themeHaystack(): string
    {
        if ($this->topic !== null && trim($this->topic) !== '') {
            return $this->topic;
        }

        return implode(' ', array_filter([
            $this->title,
            $this->slug,
            $this->source,
            implode(' ', $this->hashtags),
        ]));
    }
}
