<?php

namespace App\Services\Social\Publish;

use App\Services\Social\SocialPost;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mini-cloud na grafiki: jedyne miejsce, które wie, GDZIE leży plik posta i pod
 * jakim publicznym URL-em go widać.
 *
 * Po co w ogóle: Instagram nie przyjmuje plików lokalnych, a Zernio wymaga
 * "publicly accessible" URL-i. PNG-i powstają na Windowsie z headless Edge i są
 * gitignorowane, więc produkcja ich nie ma i nie umie zrobić. Renderujesz
 * lokalnie -> `social:push` wysyła -> serwer hostuje -> Zernio pobiera.
 *
 * Nazwy plików są DOKŁADNIE takie jak w eksporcie (`01.png`, `reel.mp4`), bo to
 * ten sam plik przeniesiony na serwer. Gdyby nazwy się rozjechały, każda strona
 * musiałaby znać tłumaczenie i pierwsza pomyłka byłaby cicha.
 */
class SocialMediaStore
{
    public function disk(): Filesystem
    {
        return Storage::disk((string) config('social.media.disk', 'public'));
    }

    /**
     * Slug NIGDY nie trafia do ścieżki wprost. `Str::slug` zostawia tylko
     * [a-z0-9-], więc `../` czy `\` znikają, zanim dotkną dysku – ten sam
     * strażnik, którego używa MarkdownSocialPostRepository.
     */
    public function normalizeSlug(string $slug): string
    {
        $slug = Str::slug($slug);

        return $slug !== '' ? $slug : 'post';
    }

    /**
     * Nazwa pliku też jest ODTWARZANA, a nie przyjmowana: klient podaje numer
     * slajdu, a serwer skleja nazwę. Dzięki temu do publicznie serwowanego
     * katalogu nie da się wstrzyknąć ani ścieżki, ani rozszerzenia.
     */
    public function fileName(int $index, string $extension): string
    {
        $extension = strtolower($extension);

        if ($extension === 'mp4') {
            return 'reel.mp4';
        }

        return str_pad((string) $index, 2, '0', STR_PAD_LEFT) . '.png';
    }

    public function path(string $slug, string $file): string
    {
        return trim((string) config('social.media.path', 'social'), '/')
            . '/' . $this->normalizeSlug($slug) . '/' . $file;
    }

    public function url(string $slug, string $file): string
    {
        $base = (string) config('social.media.base_url');

        if (trim($base) === '') {
            $base = (string) config('app.url');
        }

        return rtrim($base, '/') . '/storage/' . $this->path($slug, $file);
    }

    /**
     * Pliki, których dany format posta wymaga do publikacji.
     *
     * `story`/`quote`/`announce` to zawsze 1 slajd, karuzela 2-10 (SocialPostType::slideRange).
     * Reel to jeden gotowy mp4 z Remotiona.
     *
     * @return list<string>
     */
    public function filesFor(SocialPost $post, string $format): array
    {
        if ($format === 'reel') {
            return ['reel.mp4'];
        }

        // Story to JEDNA klatka, nawet gdyby post miał więcej slajdów: Instagram
        // Story nie ma karuzeli, a wysłanie kilku obrazków zrobiłoby z nich
        // osobne klatki w nieokreślonej kolejności.
        if ($format === 'story') {
            return ['01.png'];
        }

        return array_map(
            fn (int $i) => $this->fileName($i, 'png'),
            range(1, max(1, $post->slideCount())),
        );
    }

    /**
     * @return list<string>
     */
    public function urlsFor(SocialPost $post, string $format): array
    {
        return array_map(
            fn (string $file) => $this->url($post->slug, $file),
            $this->filesFor($post, $format),
        );
    }

    /**
     * Czego brakuje na serwerze, żeby ten format dało się opublikować.
     *
     * Cron pyta o to PRZED wysyłką: brakujący plik to zwykły stan (nie wgrałeś
     * jeszcze paczki), a nie awaria – ma zostać zgłoszony, nie wybuchnąć.
     *
     * @return list<string>
     */
    public function missingFor(SocialPost $post, string $format): array
    {
        return array_values(array_filter(
            $this->filesFor($post, $format),
            fn (string $file) => ! $this->disk()->exists($this->path($post->slug, $file)),
        ));
    }

    public function hasAllFor(SocialPost $post, string $format): bool
    {
        return $this->missingFor($post, $format) === [];
    }

    public function put(string $slug, string $file, string $contents): string
    {
        $path = $this->path($slug, $file);

        $this->disk()->put($path, $contents);

        return $path;
    }

    /**
     * Typ mediów dla Zernio: ich `mediaItems[].type` zna tylko image/video.
     */
    public function mediaType(string $file): string
    {
        return str_ends_with($file, '.mp4') ? 'video' : 'image';
    }

}
