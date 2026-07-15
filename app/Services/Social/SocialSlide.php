<?php

namespace App\Services\Social;

/**
 * Pojedynczy slajd posta. Niepersystowane DTO – nie ma tabeli, nie ma Eloquenta.
 *
 * `role` steruje wariantem layoutu w widoku karuzeli:
 *  - hook – slajd 1, największa typografia; to on jest miniaturą w feedzie,
 *  - body – środek,
 *  - cta  – ostatni, z linkiem / "Link in bio".
 *
 * `headline` i `markdown` są ROZŁĄCZNE – nagłówek nie powtarza się w treści.
 * Każde z nich ma osobny budżet znaków w loncie, więc mieszanie ich zawyżałoby
 * budżet treści o długość nagłówka. `html` to wyrenderowany `markdown`.
 */
final readonly class SocialSlide
{
    public const ROLE_HOOK = 'hook';
    public const ROLE_BODY = 'body';
    public const ROLE_CTA  = 'cta';

    public function __construct(
        public int $index,
        public int $total,
        public ?string $headline,
        public string $markdown,
        public string $html,
        public string $role,
    ) {
    }

    public function isHook(): bool
    {
        return $this->role === self::ROLE_HOOK;
    }

    public function isCta(): bool
    {
        return $this->role === self::ROLE_CTA;
    }

    public function isLast(): bool
    {
        return $this->index === $this->total;
    }

    /**
     * Numer slajdu w formacie "03/07" (pigułka w rogu grafiki).
     */
    public function number(): string
    {
        return sprintf('%02d/%02d', $this->index, $this->total);
    }

    /**
     * Długość tekstu slajdu (bez znaczników) – używane przez lint do budżetów
     * czytelności i przez widok do doboru skali typografii.
     */
    public function textLength(): int
    {
        return mb_strlen(trim($this->plainText()));
    }

    /**
     * Treść slajdu jako czysty tekst: bez bloków kodu i bez znaczników Markdown.
     * Bloki kodu wypadają celowo – mają własny budżet (linie / kolumny).
     */
    public function plainText(): string
    {
        $text = preg_replace('/```.*?```/s', '', $this->markdown) ?? $this->markdown;
        $text = preg_replace('/`[^`]*`/', '', $text) ?? $text;
        $text = preg_replace('/[*_>#\[\]]+/', '', $text) ?? $text;

        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    /**
     * Bloki kodu ze slajdu (surowa treść, bez ogrodzenia i bez nazwy języka).
     *
     * @return list<string>
     */
    public function codeBlocks(): array
    {
        if (! preg_match_all('/```[a-z0-9+#-]*\R(.*?)```/is', $this->markdown, $m)) {
            return [];
        }

        return array_map(fn (string $code) => rtrim($code), $m[1]);
    }

    /**
     * Język pierwszego bloku kodu (z ogrodzenia ```php) – trafia na pasek
     * tytułowy "okna kodu".
     */
    public function codeLanguage(): ?string
    {
        if (preg_match('/```([a-z0-9+#-]+)\R/i', $this->markdown, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }

    public function hasCode(): bool
    {
        return $this->codeBlocks() !== [];
    }
}
