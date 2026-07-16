<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Spina kontrakt PHP <-> Remotion: każdy typ sceny dozwolony w
 * config('clip.scene_types') MUSI mieć komponent w registry.ts.
 *
 * Bez tego dałoby się dodać typ do configu (lint by go przepuścił) i zapomnieć
 * komponentu — render pokazałby zaślepkę "Brak komponentu sceny". Ten test łapie
 * rozjazd po stronie PHP; kompletność samych importów pilnuje tsc.
 */
class ClipRegistryTest extends TestCase
{
    public function test_every_configured_scene_type_has_a_component_in_the_registry(): void
    {
        $registryPath = base_path('social-video/src/clip/registry.ts');
        $this->assertFileExists($registryPath, 'Brak registry.ts — biblioteka scen Remotiona.');

        $registry = File::get($registryPath);

        foreach ((array) config('clip.scene_types') as $type) {
            // Klucz w mapie: `type:` lub `"type":` (dla myślnikowych jak code-reveal).
            $pattern = '/(^|\W)("?' . preg_quote((string) $type, '/') . '"?)\s*:/m';

            $this->assertMatchesRegularExpression(
                $pattern,
                $registry,
                "Typ sceny '{$type}' nie ma komponentu w registry.ts.",
            );
        }
    }
}
