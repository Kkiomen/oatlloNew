<?php

namespace App\Services\Social\Review;

use Carbon\CarbonImmutable;

/**
 * Recenzja jednego posta – niepersystowane DTO czytane z pliku .md
 * (resources/social/reviews/{slug}.md). Zero bazy, dokładnie jak reszta modułu.
 *
 * `fingerprint` to skrót TREŚCI recenzowanego pliku posta w momencie klikania.
 * Dzięki niemu recenzja sama się unieważnia: gdy post zostanie poprawiony,
 * skrót przestaje się zgadzać i post wraca do kolejki do ponownego obejrzenia.
 * Bez tego "zaakceptowane" znaczyłoby "zaakceptowane kiedyś, w nieznanej wersji".
 */
final readonly class SocialReview
{
    public function __construct(
        public string $slug,
        public SocialReviewVerdict $verdict,
        /** Powód poprawki – wymagany dla `changes`, pusty przy akceptacji. */
        public string $reason,
        public ?CarbonImmutable $reviewedAt,
        public string $fingerprint,
    ) {
    }

    /**
     * Czy recenzja dotyczy AKTUALNEJ treści posta (a nie wersji sprzed poprawki).
     */
    public function matches(string $fingerprint): bool
    {
        return $this->fingerprint !== '' && hash_equals($this->fingerprint, $fingerprint);
    }

    public function needsWork(): bool
    {
        return $this->verdict->needsWork();
    }

    public function isApproved(): bool
    {
        return $this->verdict === SocialReviewVerdict::Approved;
    }
}
