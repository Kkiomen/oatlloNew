<?php

namespace App\Services\Article;

use App\Models\Article;
use Illuminate\Support\Facades\Cache;

/**
 * Uniwersalne linkowanie wewnętrzne działające PRZY WYŚWIETLANIU (render-time).
 *
 * Wstawia w treść artykułu linki do innych istniejących artykułów. Działa tak
 * samo dla artykułów z bazy i z plików .md, bo operuje na blokach `contents`
 * (wspólny format obu źródeł) i niczego nie utrwala.
 *
 * Frazy-kotwice (tekst zamieniany w link) pochodzą z: keys_link, tytułu (name)
 * oraz nazw tagów artykułów-celów. Indeks fraz→URL jest cache'owany per język.
 *
 * Bezpieczeństwo: nie linkuje wewnątrz <a>, <pre>, <code> ani nagłówków <h1..6>,
 * nie tworzy zagnieżdżonych linków, pomija self-linki, respektuje limity.
 */
class InternalLinker
{
    public function isEnabled(): bool
    {
        return (bool) config('articles.internal_linking.enabled', true);
    }

    /**
     * Wstawia linki wewnętrzne do (już zsanityzowanych) bloków treści artykułu.
     *
     * @param array<int,array<string,mixed>> $blocks
     * @return array<int,array<string,mixed>>
     */
    public function linkContents(array $blocks, Article $article): array
    {
        if (! $this->isEnabled() || empty($blocks)) {
            return $blocks;
        }

        // Linkowanie to tylko wzbogacenie treści — jakikolwiek błąd NIE MOŻE
        // wywalić renderu artykułu (500). W razie problemu zwracamy treść bez linków.
        try {
            $lang  = $article->language ?: (string) config('articles.default_language');
            $index = $this->index($lang);
            if (empty($index)) {
                return $blocks;
            }

            $selfUrl   = $article->getRoute();
            $remaining = max(0, (int) config('articles.internal_linking.max_links_per_article', 3));
            $usedUrls  = [];

            foreach ($blocks as &$block) {
                if ($remaining <= 0) {
                    break;
                }
                if (($block['type'] ?? null) === 'text' && ! empty($block['content'])) {
                    $block['content'] = $this->linkHtml((string) $block['content'], $index, $selfUrl, $remaining, $usedUrls);
                }
            }
            unset($block);

            return $blocks;
        } catch (\Throwable $e) {
            report($e);

            return $blocks;
        }
    }

    /**
     * Zwraca (cache'owany) indeks fraz→URL dla danego języka.
     *
     * @return array<string,array{url:string,label:string}>
     */
    public function index(string $lang): array
    {
        $ttl = (int) config('articles.internal_linking.cache_ttl', 600);

        try {
            return Cache::remember(self::cacheKey($lang), $ttl, fn () => $this->buildIndex($lang));
        } catch (\Throwable $e) {
            // Awaria cache/budowy indeksu nie może przerwać renderu — brak linków.
            report($e);

            return [];
        }
    }

    public static function cacheKey(string $lang): string
    {
        return 'internal_link_index:' . $lang;
    }

    /**
     * Czyści cache indeksu (dla wszystkich znanych języków lub jednego).
     */
    public static function forget(?string $lang = null): void
    {
        if ($lang !== null) {
            Cache::forget(self::cacheKey($lang));

            return;
        }

        $langs = array_unique(array_filter([
            (string) config('articles.default_language'),
            (string) env('APP_LOCALE'),
            'en',
            'pl',
        ]));

        foreach ($langs as $l) {
            Cache::forget(self::cacheKey($l));
        }
    }

    /**
     * Buduje indeks fraz→URL ze wszystkich opublikowanych artykułów
     * (baza ∪ pliki .md), scalonych po slug (.md ma pierwszeństwo).
     *
     * @return array<string,array{url:string,label:string}>
     */
    private function buildIndex(string $lang): array
    {
        $targets = collect();

        Article::query()
            ->where('is_published', true)
            ->where('type', 'normal')
            ->where('language', $lang)
            ->with('tags')
            ->get()
            ->each(fn (Article $a) => $targets->put($a->slug, $a));

        app(MarkdownArticleRepository::class)->published($lang)
            ->each(fn (Article $a) => $targets->put($a->slug, $a));

        $minLen  = (int) config('articles.internal_linking.min_phrase_length', 4);
        $stop    = array_map('mb_strtolower', (array) config('articles.internal_linking.stopwords', []));
        $maxKeys = max(1, (int) config('articles.internal_linking.max_index_phrases', 2000));

        // Deterministyczna kolejność (po slug), by kolizje fraz rozstrzygać stabilnie.
        $sorted = $targets->values()->sortBy('slug');

        $map = [];
        foreach ($sorted as $target) {
            // Pojedynczy wadliwy artykuł (np. brak slug / błąd getRoute) nie może
            // zepsuć całego indeksu — pomijamy go i idziemy dalej.
            try {
                if (empty($target->slug)) {
                    continue;
                }
                $url = $target->getRoute();
                if ($url === '') {
                    continue;
                }

                foreach ($this->phrasesFor($target) as $phrase) {
                    $phrase = $this->normalize($phrase);
                    if ($phrase === '' || mb_strlen($phrase) < $minLen) {
                        continue;
                    }
                    if (! mb_check_encoding($phrase, 'UTF-8')) {
                        continue;
                    }
                    $lower = mb_strtolower($phrase);
                    if (in_array($lower, $stop, true) || isset($map[$lower])) {
                        continue;
                    }
                    $map[$lower] = ['url' => $url, 'label' => $phrase];
                }
            } catch (\Throwable $e) {
                report($e);
                continue;
            }
        }

        // Najdłuższe frazy najpierw – pełniejsze dopasowania mają priorytet.
        uksort($map, static fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        // Twardy limit rozmiaru indeksu (ochrona wydajności na dużych zbiorach).
        if (count($map) > $maxKeys) {
            $map = array_slice($map, 0, $maxKeys, true);
        }

        return $map;
    }

    /**
     * Zbiera frazy-kotwice dla artykułu-celu: keys_link + tytuł + tagi.
     *
     * @return array<int,string>
     */
    private function phrasesFor(Article $target): array
    {
        $phrases = [];

        if (! empty($target->keys_link)) {
            foreach (explode(',', (string) $target->keys_link) as $key) {
                $phrases[] = $key;
            }
        }

        if (! empty($target->name)) {
            $phrases[] = (string) $target->name;
        }

        foreach ($target->tags as $tag) {
            if (! empty($tag->name)) {
                $phrases[] = (string) $tag->name;
            }
        }

        return $phrases;
    }

    private function normalize(string $s): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $s));
    }

    /**
     * Wstawia linki w pojedynczy fragment HTML.
     *
     * @param array<string,array{url:string,label:string}> $index
     * @param array<int,string> $usedUrls
     */
    private function linkHtml(string $html, array $index, string $selfUrl, int &$remaining, array &$usedUrls): string
    {
        // Regex /u wymaga poprawnego UTF-8 — w innym wypadku pomijamy linkowanie
        // tego bloku (zwracamy oryginał), zamiast ryzykować błąd/500.
        if (! mb_check_encoding($html, 'UTF-8')) {
            return $html;
        }

        // Tokenizacja na naprzemienne: tekst / tag.
        $parts = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false || $parts === null) {
            return $html;
        }

        $skipTags  = ['a', 'pre', 'code', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        $skipDepth = 0;

        foreach ($parts as &$part) {
            if ($part === '' || $part === null) {
                continue;
            }

            // Tag – aktualizuj głębokość stref, w których NIE linkujemy.
            if ($part[0] === '<') {
                if (preg_match('/^<\s*(\/?)\s*([a-zA-Z0-9]+)/', $part, $m)) {
                    $tag = strtolower($m[2]);
                    if (in_array($tag, $skipTags, true)) {
                        $isClose     = $m[1] === '/';
                        $selfClosing = str_ends_with(rtrim($part), '/>');
                        if ($isClose) {
                            $skipDepth = max(0, $skipDepth - 1);
                        } elseif (! $selfClosing) {
                            $skipDepth++;
                        }
                    }
                }
                continue;
            }

            if ($skipDepth > 0 || $remaining <= 0) {
                continue;
            }

            // Segment tekstowy – najwyżej JEDEN link (brak zagnieżdżeń w segmencie).
            foreach ($index as $data) {
                if ($data['url'] === $selfUrl || in_array($data['url'], $usedUrls, true)) {
                    continue;
                }

                $pattern  = '/(*UCP)\b' . preg_quote($data['label'], '/') . '\b/iu';
                $count    = 0;
                // @ – ewentualne ostrzeżenie PCRE nie może zamienić się w wyjątek;
                // przy błędzie regex $replaced === null i po prostu pomijamy frazę.
                $replaced = @preg_replace_callback(
                    $pattern,
                    static fn ($mm) => '<a href="' . $data['url'] . '" class="internal-link">' . $mm[0] . '</a>',
                    $part,
                    1,
                    $count
                );

                if ($replaced === null || preg_last_error() !== PREG_NO_ERROR) {
                    continue; // wadliwa fraza/regex — pomijamy, nie przerywamy
                }

                if ($count > 0) {
                    $part       = $replaced;
                    $usedUrls[] = $data['url'];
                    $remaining--;
                    break; // jeden link na segment tekstowy
                }
            }
        }
        unset($part);

        return implode('', $parts);
    }
}
