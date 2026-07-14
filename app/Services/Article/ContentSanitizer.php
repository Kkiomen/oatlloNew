<?php

namespace App\Services\Article;

/**
 * Oczyszcza treść artykułu tuż przed wyświetleniem na stronie.
 *
 * Odpowiada za drobne, prezentacyjne poprawki, które chcemy stosować globalnie,
 * niezależnie od źródła artykułu (plik .md czy baza danych):
 *  1. Zamiana myślników em/en (—, –) na zwykły dywiz (-).
 *  2. Słownik "anti-AI" – podmiana słów/zwrotów zdradzających tekst generowany
 *     przez AI (na razie pusty, gotowy do uzupełnienia).
 *
 * Serwis jest bezstanowy – można go bezpiecznie rozwiązywać przez kontener.
 */
class ContentSanitizer
{
    /**
     * Zamiana myślników na dywiz. Obejmuje zarówno surowe znaki UTF-8,
     * jak i encje HTML, które mógłby wygenerować parser Markdown.
     *
     * @var array<string,string>
     */
    private const DASH_REPLACEMENTS = [
        "\u{2014}" => '-', // — em dash
        "\u{2013}" => '-', // – en dash
        "\u{2015}" => '-', // ― horizontal bar
        '&mdash;' => '-',
        '&ndash;' => '-',
        '&#8212;' => '-',
        '&#8211;' => '-',
        '&#x2014;' => '-',
        '&#x2013;' => '-',
    ];

    /**
     * Słownik anti-AI: fraza => zamiennik. Klucze są dopasowywane
     * bez rozróżniania wielkości liter, z zachowaniem granic słów.
     * Uzupełniamy sukcesywnie.
     *
     * @var array<string,string>
     */
    private const ANTI_AI_DICTIONARY = [
        // 'delve'    => 'dig into',
        // 'moreover' => 'also',
    ];

    /**
     * Główne wejście: oczyszcza fragment HTML/tekstu przed wyświetleniem.
     */
    public function sanitize(string $content): string
    {
        if ($content === '') {
            return $content;
        }

        $content = $this->normalizeDashes($content);
        $content = $this->applyAntiAiDictionary($content);

        return $content;
    }

    /**
     * Zamienia wszelkie myślniki em/en na zwykły dywiz.
     */
    private function normalizeDashes(string $content): string
    {
        return strtr($content, self::DASH_REPLACEMENTS);
    }

    /**
     * Podmienia frazy ze słownika anti-AI (case-insensitive, granice słów).
     */
    private function applyAntiAiDictionary(string $content): string
    {
        if (self::ANTI_AI_DICTIONARY === []) {
            return $content;
        }

        foreach (self::ANTI_AI_DICTIONARY as $needle => $replacement) {
            $pattern = '/\b' . preg_quote($needle, '/') . '\b/iu';
            $content = preg_replace($pattern, $replacement, $content) ?? $content;
        }

        return $content;
    }
}
