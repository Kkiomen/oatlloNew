<?php

namespace App\Services\Social\Publisher;

final readonly class SocialPublishResult
{
    /**
     * @param list<string> $instructions Kroki dla człowieka (pusto, gdy publisher zrobił wszystko sam)
     */
    public function __construct(
        public bool $published,
        public string $publisher,
        public string $summary,
        public array $instructions = [],
        public ?string $url = null,
    ) {
    }

    /**
     * Post NIE został opublikowany – czeka na człowieka.
     *
     * @param list<string> $instructions
     */
    public static function manual(string $publisher, string $summary, array $instructions): self
    {
        return new self(false, $publisher, $summary, $instructions);
    }
}
