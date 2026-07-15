<?php

namespace App\Services\Social;

use RuntimeException;

/**
 * Plik .md posta jest na tyle uszkodzony, że nie da się z niego zbudować DTO
 * (np. brak/nieznany `type`). Błędy "miękkie" (za długi caption, za dużo
 * hashtagów) NIE rzucają – zgłasza je SocialPostLinter.
 */
class InvalidSocialPost extends RuntimeException
{
    public static function unknownType(string $slug, mixed $type): self
    {
        $given = is_scalar($type) || $type === null ? var_export($type, true) : gettype($type);
        $allowed = implode(', ', SocialPostType::values());

        return new self("Post '{$slug}': nieznany type {$given}. Dozwolone: {$allowed}.");
    }

    public static function badDate(string $slug, string $field, string $reason): self
    {
        return new self("Post '{$slug}': nie da się sparsować '{$field}' ({$reason}).");
    }
}
