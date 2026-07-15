<?php

namespace App\Services\Social\Review;

use App\Services\Social\SocialPost;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Jedna pozycja w kalendarzu: KONKRETNY format KONKRETNEGO posta.
 *
 * Karuzela zaplanowana jako `formats: [post, reel]` daje dwie pozycje tego samego
 * dnia – bo to dwie publikacje, każda do wrzucenia osobno.
 */
final readonly class SocialCalendarEntry
{
    public function __construct(
        public SocialReviewItem $item,
        public string $format,
    ) {
    }

    public function post(): SocialPost
    {
        return $this->item->post;
    }

    public function day(): ?string
    {
        return $this->post()->publishAt?->format('Y-m-d');
    }

    public function time(): ?string
    {
        return $this->post()->publishAt?->format('H:i');
    }

    public function date(): ?CarbonImmutable
    {
        return $this->post()->publishAt;
    }

    /**
     * Etykieta formatu z configu; nieznany format i tak się tu nie pojawi
     * (lint go blokuje), ale gdyby – pokazujemy surową nazwę zamiast wywalać widok.
     */
    public function label(): string
    {
        return (string) config('social.formats.' . $this->format . '.label', Str::headline($this->format));
    }

    public function color(): string
    {
        return (string) config('social.formats.' . $this->format . '.color', '#94a3b8');
    }

    /**
     * `reel` i `video` to ruch – w kalendarzu czyta się to szybciej niż nazwa.
     */
    public function isMotion(): bool
    {
        return in_array($this->format, ['reel', 'video'], true);
    }
}
