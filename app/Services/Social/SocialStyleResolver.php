<?php

namespace App\Services\Social;

use Illuminate\Support\Str;

/**
 * Dobiera SKÓRKĘ (styl wizualny) do posta.
 *
 * Kolejność decyzji – pierwsza pasująca wygrywa:
 *  1. jawny `style:` we frontmatterze,
 *  2. język bloku kodu (```bash => terminal – bo post Z KOMENDAMI to sesja shella),
 *  3. typ posta – PULA stylów pasujących do formy (config: type_rotation),
 *  4. temat / hashtagi (database, architecture => blueprint),
 *  5. deterministyczna rotacja po slugu.
 *
 * Krok 3 jest PULĄ, a nie jednym stylem, i to jest istotne przy dużym wolumenie:
 * pojedyncza afinicja typu znaczyła, że każde story dostaje spotlight – czyli
 * kilkanaście identycznych kafelków w feedzie, których rotacja nawet nie dotyka
 * (typ rozstrzyga wcześniej).
 *
 * TYP IDZIE PRZED TEMATEM i to jest istotne: typ mówi o FORMIE (story ogląda się
 * ułamek sekundy, zapowiedź ma logo jako bohatera), a temat tylko o TREŚCI. Przy
 * odwrotnej kolejności zapowiedź kursu Dockera dostawała chrome terminala, który
 * gryzł się z jej własnym wielkim logo.
 *
 * Dobór MUSI być deterministyczny: ten sam post ma wyglądać identycznie przy
 * każdym eksporcie. Dlatego krok 5 to crc32(slug), a NIE rand() – inaczej każdy
 * `social:export` dawałby inną grafikę i nie dałoby się jej poprawiać.
 */
class SocialStyleResolver
{
    /**
     * Nazwa skórki dla posta.
     */
    public function resolve(SocialPost $post): string
    {
        $styles = $this->all();

        // 1. Jawny wybór autora zawsze wygrywa.
        if ($post->style !== null && isset($styles[$post->style])) {
            return $post->style;
        }

        return $this->byLanguage($post, $styles)
            ?? $this->byType($post, $styles)
            ?? $this->byTopic($post, $styles)
            ?? $this->byRotation($post);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return (array) config('social-styles.styles', []);
    }

    public function exists(string $style): bool
    {
        return isset($this->all()[$style]);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->all());
    }

    /**
     * Nazwa dodatkowego chrome'u (np. pasek okna terminala) albo null.
     */
    public function chrome(string $style): ?string
    {
        $chrome = $this->all()[$style]['chrome'] ?? null;

        return is_string($chrome) && $chrome !== '' ? $chrome : null;
    }

    public function label(string $style): string
    {
        return (string) ($this->all()[$style]['label'] ?? Str::headline($style));
    }

    /**
     * Post z blokiem ```bash / ```dockerfile JEST sesją terminala – to najsilniejszy
     * sygnał, jaki mamy, więc idzie przed tematem i typem.
     *
     * @param  array<string, array<string, mixed>>  $styles
     */
    private function byLanguage(SocialPost $post, array $styles): ?string
    {
        $languages = [];
        foreach ($post->slides as $slide) {
            if ($language = $slide->codeLanguage()) {
                $languages[] = $language;
            }
        }

        if ($languages === []) {
            return null;
        }

        foreach ($styles as $name => $style) {
            $wanted = (array) ($style['affinity']['languages'] ?? []);
            if (array_intersect($languages, $wanted) !== []) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param  array<string, array<string, mixed>>  $styles
     */
    private function byTopic(SocialPost $post, array $styles): ?string
    {
        // Opakowane spacjami – tak samo jak w TechThemeResolver, żeby krótkie
        // słowa kluczowe nie trafiały w środek wyrazu.
        $haystack = ' ' . Str::lower($post->topic . ' ' . implode(' ', $post->hashtags)) . ' ';

        foreach ($styles as $name => $style) {
            foreach ((array) ($style['affinity']['topics'] ?? []) as $topic) {
                if ($topic !== '' && str_contains($haystack, ' ' . Str::lower((string) $topic))) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * Styl dla typu posta.
     *
     * Najpierw PULA z `type_rotation`, dopiero potem pojedyncza afinicja `types`
     * z pakietu. Pula jest tu kluczowa przy dużym wolumenie: pojedynczy styl na typ
     * znaczył, że KAŻDE story wygląda tak samo (12 z 24 postów w repo to story),
     * a rotacja ich nie ratowała, bo typ rozstrzyga wcześniej niż ona.
     *
     * Wybór z puli jest deterministyczny (crc32(slug)) – tak samo jak rotacja
     * i z tego samego powodu: ten sam post musi renderować się identycznie przy
     * każdym eksporcie, inaczej nie dałoby się go poprawiać.
     *
     * @param  array<string, array<string, mixed>>  $styles
     */
    private function byType(SocialPost $post, array $styles): ?string
    {
        $pool = $this->pool('social-styles.type_rotation.' . $post->type->value);

        if ($pool !== []) {
            return $pool[crc32($post->slug) % count($pool)];
        }

        foreach ($styles as $name => $style) {
            if (in_array($post->type->value, (array) ($style['affinity']['types'] ?? []), true)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Lista stylów z configu, przefiltrowana o te, które faktycznie istnieją –
     * literówka w puli ma zawężyć wybór, a nie wyprodukować martwą nazwę skórki.
     *
     * @return list<string>
     */
    private function pool(string $key): array
    {
        return array_values(array_filter(
            array_map('strval', (array) config($key, [])),
            fn (string $name) => $this->exists($name),
        ));
    }

    /**
     * Deterministyczna rotacja: feed nie jest monotonny, a post zawsze wygląda
     * tak samo. crc32, nie rand().
     */
    private function byRotation(SocialPost $post): string
    {
        $rotation = $this->pool('social-styles.rotation');

        if ($rotation === []) {
            return (string) config('social-styles.default', 'midnight');
        }

        return $rotation[crc32($post->slug) % count($rotation)];
    }
}
