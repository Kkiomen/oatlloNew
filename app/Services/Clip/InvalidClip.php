<?php

namespace App\Services\Clip;

use RuntimeException;

/**
 * Plik .md scenariusza jest na tyle uszkodzony, że nie da się z niego zbudować
 * DTO (np. scena z niepoprawnym YAML-em). Błędy "miękkie" (nieznany typ sceny,
 * brak narracji, za długi kod) NIE rzucają — zgłasza je ClipLinter.
 */
class InvalidClip extends RuntimeException
{
    public static function badSceneYaml(string $slug, int $index, string $reason): self
    {
        return new self("Clip '{$slug}': scena #{$index} ma niepoprawny YAML ({$reason}).");
    }
}
