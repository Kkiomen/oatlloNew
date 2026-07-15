<?php

namespace App\Services\Social\Publish;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Dziennik wysyłek: co, kiedy i z jakim skutkiem poszło na Instagrama.
 *
 * DLACZEGO TO NIE JEST TABELA, mimo że moduł ma bazę pod ręką: to jedyny stan
 * w module, który powstaje na PRODUKCJI i musi przeżyć `git pull`. `storage/app`
 * przeżywa, jest gitignorowane i nie wymaga migracji – a przy okazji testy social
 * dalej nie potrzebują bazy (świadoma cecha tego modułu).
 *
 * DLACZEGO NIE `status: published` W .md: plik na produkcji jest kopią z gita.
 * Zapis do niego rozjechałby working tree i pierwszy `git pull` albo by go
 * cofnął, albo wywalił konflikt. Stan runtime'u nie ma prawa mieszkać w treści.
 *
 * JEDNOSTKĄ JEST PARA (post × format), tak samo jak w kalendarzu: karuzela
 * z `formats: [post, reel]` to dwie osobne publikacje i wysłanie jednej nie może
 * zdejmować drugiej.
 */
class SocialPublishLog
{
    public const PUBLISHED = 'published';
    public const FAILED    = 'failed';

    /**
     * Wysyłka poszła, ale nie wiemy z jakim skutkiem (timeout / zerwane
     * połączenie). To NIE jest błąd do ponowienia: post mógł się opublikować,
     * a my byśmy go wysłali drugi raz. Wymaga człowieka.
     */
    public const UNKNOWN = 'unknown';

    public function directory(): string
    {
        return storage_path('app/social-published');
    }

    private function key(string $slug, string $format): string
    {
        return Str::slug($slug) . '__' . Str::slug($format);
    }

    public function path(string $slug, string $format): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . $this->key($slug, $format) . '.json';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $slug, string $format): ?array
    {
        $path = $this->path($slug, $format);

        if (! File::exists($path)) {
            return null;
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Czy tę parę wolno jeszcze próbować wysłać.
     *
     * `published` i `unknown` blokują NA ZAWSZE (do ręcznej decyzji) – w obu
     * przypadkach post mógł już wyjść. `failed` wolno ponawiać do limitu: to
     * jawne odrzucenie przez API, więc wiemy, że nic nie poszło w świat.
     */
    public function shouldAttempt(string $slug, string $format, int $maxAttempts = 3): bool
    {
        $state = $this->get($slug, $format);

        if ($state === null) {
            return true;
        }

        if (($state['status'] ?? '') !== self::FAILED) {
            return false;
        }

        return (int) ($state['attempts'] ?? 0) < $maxAttempts;
    }

    public function isPublished(string $slug, string $format): bool
    {
        return ($this->get($slug, $format)['status'] ?? null) === self::PUBLISHED;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function record(string $slug, string $format, string $status, array $extra = []): void
    {
        File::ensureDirectoryExists($this->directory());

        $previous = $this->get($slug, $format);

        $payload = array_merge([
            'slug'       => $slug,
            'format'     => $format,
            'status'     => $status,
            'attempts'   => (int) ($previous['attempts'] ?? 0) + 1,
            'updated_at' => CarbonImmutable::now()->toIso8601String(),
        ], $extra);

        File::put($this->path($slug, $format), (string) json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if (! File::isDirectory($this->directory())) {
            return [];
        }

        $entries = [];

        foreach (File::files($this->directory()) as $file) {
            $decoded = json_decode((string) File::get($file->getPathname()), true);

            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return $entries;
    }
}
