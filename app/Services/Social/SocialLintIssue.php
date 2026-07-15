<?php

namespace App\Services\Social;

/**
 * Pojedyncze znalezisko lintu.
 *
 * ERROR   – post się nie wyrenderuje albo Instagram go odrzuci. Blokuje eksport.
 * WARNING – wyrenderuje się, ale prawdopodobnie źle wygląda (rozpycha layout,
 *           gubi font). Nie blokuje.
 */
final readonly class SocialLintIssue
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
