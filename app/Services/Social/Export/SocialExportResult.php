<?php

namespace App\Services\Social\Export;

/**
 * Wynik eksportu jednego posta: folder gotowy do ręcznego uploadu.
 */
final readonly class SocialExportResult
{
    /**
     * @param list<string> $imagePaths Pliki slajdów W KOLEJNOŚCI publikacji
     */
    public function __construct(
        public string $slug,
        public string $directory,
        public array $imagePaths,
        public string $captionPath,
        public string $manifestPath,
        public bool $htmlOnly,
    ) {
    }

    public function slideCount(): int
    {
        return count($this->imagePaths);
    }
}
