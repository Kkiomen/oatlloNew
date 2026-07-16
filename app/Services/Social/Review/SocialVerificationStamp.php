<?php

namespace App\Services\Social\Review;

use Carbon\CarbonImmutable;

/**
 * Wstawia i czyta pieczątkę `verified:` we frontmatterze posta.
 *
 * DLACZEGO W PLIKU POSTA, a nie w katalogu obok (jak werdykty człowieka):
 * werdykt jest DECYZJĄ o pliku i musi dać się cofnąć bez dotykania treści,
 * a weryfikacja jest WŁAŚCIWOŚCIĄ tej treści – jedzie z nią w tym samym commicie
 * i w tym samym diffie. Recenzent widzi w panelu jedno i drugie.
 *
 * DLACZEGO ODCISK POMIJA SAM BLOK `verified:`:
 *  1. inaczej byłby cykliczny (wpisanie odcisku zmienia plik, czyli i odcisk),
 *  2. odcisk RECENZJI (SocialReviewRepository::fingerprint) też go pomija –
 *     człowiek ocenia TREŚĆ, nie moją pieczątkę. Bez tego dopisanie weryfikacji
 *     do 150 postów skasowałoby 81 gotowych zielonych werdyktów.
 */
class SocialVerificationStamp
{
    /**
     * Wycina blok `verified:` z surowego pliku. Bez zmian, gdy go nie ma –
     * dzięki temu odciski plików sprzed tej funkcji zostają nietknięte.
     */
    public static function strip(string $raw): string
    {
        $normalized = preg_replace('/\R/', "\n", $raw) ?? $raw;

        // Blok kończy się na pierwszej linii, która NIE jest wcięta (kolejny klucz
        // frontmattera albo domykające ---).
        return preg_replace('/^verified:\n(?:[ \t]+.*\n|\n)*/m', '', $normalized) ?? $normalized;
    }

    /**
     * Tożsamość TREŚCI posta – to ona jest przedmiotem weryfikacji.
     */
    public static function contentFingerprint(string $raw): string
    {
        return sha1(self::strip($raw));
    }

    /**
     * Zwraca surowy plik z wstawioną/podmienioną pieczątką.
     *
     * @param list<string> $checks
     */
    public static function apply(
        string $raw,
        string $verdict,
        array $checks = [],
        string $notes = '',
        ?CarbonImmutable $at = null,
    ): string {
        $at ??= CarbonImmutable::now();
        $clean = self::strip($raw);

        if (! preg_match('/^---\n(.*?\n)---\n(.*)$/s', $clean, $m)) {
            throw new \InvalidArgumentException('Plik posta nie ma frontmattera.');
        }

        [$frontmatter, $body] = [$m[1], $m[2]];

        $block = "verified:\n"
            . '  verdict: ' . $verdict . "\n"
            . '  at: ' . $at->format('Y-m-d H:i') . "\n"
            . '  fingerprint: ' . sha1($clean) . "\n";

        if ($checks !== []) {
            $block .= "  checks:\n";

            foreach ($checks as $check) {
                $block .= '    - ' . self::yamlScalar($check) . "\n";
            }
        }

        if (trim($notes) !== '') {
            $block .= "  notes: |\n";

            foreach (preg_split('/\R/', rtrim($notes)) ?: [] as $line) {
                $block .= '    ' . $line . "\n";
            }
        }

        return "---\n" . $frontmatter . $block . "---\n" . $body;
    }

    private static function yamlScalar(string $value): string
    {
        $value = trim($value);

        // Cudzysłów tylko gdy trzeba – inaczej YAML dostaje zbędny szum.
        if (preg_match('/^[\w ()\/\.,:;\'\-\+\#\$\!\?=>&%*]+$/u', $value) && ! str_contains($value, ': ')) {
            return $value;
        }

        return '"' . str_replace('"', '\"', $value) . '"';
    }
}
