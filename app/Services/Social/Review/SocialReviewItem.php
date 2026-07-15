<?php

namespace App\Services\Social\Review;

use App\Services\Social\SocialPost;

/**
 * Post zestawiony ze swoją recenzją (albo jej brakiem) – to jest jednostka,
 * którą przerzuca panel i którą czyta skill.
 */
final readonly class SocialReviewItem
{
    public function __construct(
        public SocialPost $post,
        public string $path,
        /** Skrót AKTUALNEJ treści pliku posta. */
        public string $fingerprint,
        public ?SocialReview $review,
    ) {
    }

    /**
     * Recenzja jest nieaktualna, gdy post zmienił się po jej wystawieniu.
     * Wtedy post wraca do kolejki – zarówno po poprawce (zielone światło trzeba
     * wydać na nowo), jak i po ręcznej edycji pliku.
     */
    public function isStale(): bool
    {
        return $this->review !== null && ! $this->review->matches($this->fingerprint);
    }

    public function isReviewed(): bool
    {
        return $this->review !== null && ! $this->isStale();
    }

    public function needsWork(): bool
    {
        return $this->isReviewed() && $this->review->needsWork();
    }

    public function isApproved(): bool
    {
        return $this->isReviewed() && $this->review->isApproved();
    }

    /**
     * Stan do wyświetlenia / raportowania w komendzie.
     */
    public function state(): string
    {
        return match (true) {
            $this->review === null => 'pending',
            $this->isStale()       => 'stale',
            default                => $this->review->verdict->value,
        };
    }
}
