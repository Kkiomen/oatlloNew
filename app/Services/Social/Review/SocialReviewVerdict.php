<?php

namespace App\Services\Social\Review;

/**
 * Werdykt człowieka z panelu recenzji: zielony (nadaje się) albo czerwony
 * (jest powód do poprawki).
 *
 * To NIE jest `status` z frontmattera posta. `status` mówi, czy post w ogóle
 * wchodzi do eksportu; werdykt mówi, czy CZŁOWIEK go obejrzał i zaakceptował.
 * Dlatego werdykty żyją w osobnych plikach, a nie w poście – autor edytuje post,
 * recenzent edytuje recenzję, nikt nikomu nie nadpisuje pliku.
 */
enum SocialReviewVerdict: string
{
    case Approved = 'approved';
    case Changes = 'changes';

    public static function tryParse(mixed $value): ?self
    {
        if (! is_string($value)) {
            return null;
        }

        return self::tryFrom(strtolower(trim($value)));
    }

    public function needsWork(): bool
    {
        return $this === self::Changes;
    }

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Nadaje się do publikacji',
            self::Changes  => 'Do poprawy',
        };
    }
}
