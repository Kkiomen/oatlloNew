<?php

namespace App\Services\Social\Review;

use Carbon\CarbonImmutable;

/**
 * Pieczątka weryfikacji merytorycznej – to, co sprawdził Claude, ZANIM post
 * zobaczył człowiek.
 *
 * PO CO, skoro jest lint: lint pilnuje FORMATU. Post może mieć idealne 46 kolumn,
 * zmieścić się w budżecie znaków i kłamać. „Domyślny port Xdebug to 9000",
 * „Anthropic ma endpoint embeddings", literówka w nazwie metody – nic z tego lint
 * nie widzi, a każde skończy się komentarzem pod postem.
 *
 * DLACZEGO Z ODCISKIEM: bez niego „zweryfikowane" znaczyłoby „zweryfikowane
 * kiedyś, w nieznanej wersji" – dokładnie ten błąd, który werdykty człowieka
 * rozwiązują przez `fingerprint`. Poprawka treści MUSI unieważniać weryfikację.
 *
 * ODCISK LICZY SIĘ Z TREŚCI BEZ TEGO BLOKU (`contentFingerprint`), bo inaczej
 * byłby cykliczny: wpisanie odcisku do pliku zmieniałoby plik, czyli i odcisk.
 */
final readonly class SocialVerification
{
    public const APPROVED = 'approved';
    public const ISSUES   = 'issues';

    /**
     * @param list<string> $checks Co konkretnie sprawdzono (dla człowieka w panelu).
     */
    public function __construct(
        public string $verdict,
        public ?CarbonImmutable $at,
        public string $fingerprint,
        public array $checks,
        public string $notes,
    ) {
    }

    public function isApproved(): bool
    {
        return $this->verdict === self::APPROVED;
    }

    /**
     * Czy pieczątka pasuje do AKTUALNEJ treści posta.
     */
    public function matches(string $contentFingerprint): bool
    {
        return $this->fingerprint !== '' && hash_equals($this->fingerprint, $contentFingerprint);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromFrontmatter(mixed $data): ?self
    {
        if (! is_array($data)) {
            return null;
        }

        $verdict = strtolower(trim((string) ($data['verdict'] ?? '')));

        if (! in_array($verdict, [self::APPROVED, self::ISSUES], true)) {
            return null;
        }

        $at = null;

        if (! empty($data['at'])) {
            try {
                $at = CarbonImmutable::parse((string) $data['at']);
            } catch (\Throwable) {
                $at = null;
            }
        }

        $checks = $data['checks'] ?? [];

        return new self(
            verdict: $verdict,
            at: $at,
            fingerprint: (string) ($data['fingerprint'] ?? ''),
            checks: array_values(array_map('strval', is_array($checks) ? $checks : [$checks])),
            notes: rtrim((string) ($data['notes'] ?? '')),
        );
    }

    public function label(): string
    {
        return $this->isApproved() ? 'zweryfikowane' : 'weryfikacja: uwagi';
    }
}
