<?php

namespace App\Services\Clip;

/**
 * Pojedyncze znalezisko lintu clipa.
 *
 * ERROR   – clip się nie wyrenderuje poprawnie (nieznany typ sceny, brak
 *           narracji, kod za krawędź). Blokuje render.
 * WARNING – wyrenderuje się, ale prawdopodobnie źle (za długa narracja, SFX
 *           bez pliku). Nie blokuje.
 *
 * Osobna klasa od SocialLintIssue — clip to niezależny moduł, nie dziedziczy po
 * social poza współdzielonymi motywami technologii.
 */
final readonly class ClipLintIssue
{
    public const ERROR   = 'error';
    public const WARNING = 'warning';

    public function __construct(
        public string $level,
        public string $slug,
        public string $message,
    ) {
    }

    public static function error(string $slug, string $message): self
    {
        return new self(self::ERROR, $slug, $message);
    }

    public static function warning(string $slug, string $message): self
    {
        return new self(self::WARNING, $slug, $message);
    }

    public function isError(): bool
    {
        return $this->level === self::ERROR;
    }
}
