<?php

namespace App\Services\Social\Video;

use App\Services\Social\SocialImageService;
use App\Services\Social\SocialPost;
use App\Services\Social\SocialPostType;
use App\Services\Social\SocialSlide;
use App\Services\Social\SocialStyleResolver;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\File;

/**
 * Przygotowuje wsad dla Remotiona: HTML slajdów + reel.json.
 *
 * Podział ról jest sztywny i celowy: PHP wie WSZYSTKO o treści (ile slajdów, jaka
 * skórka, jaki akcent, ile czasu slajd potrzebuje na przeczytanie), Remotion wie
 * tylko, jak to poruszyć. Inaczej decyzje o treści uciekłyby do TSX-a, gdzie nie
 * sięga ani `social:lint`, ani testy.
 *
 * Slajdy to DOKŁADNIE te same dokumenty, które lecą na PNG (`SocialImageService`),
 * bit w bit. Wideo nie może się rozjechać z kafelkiem, więc nie ma tu drugiego
 * renderu – jest ten sam.
 */
class ReelStager
{
    public function __construct(
        private SocialImageService $images,
        private SocialStyleResolver $styles,
    ) {
    }

    /**
     * Zapisuje slajdy i manifest w public/ projektu Remotiona i zwraca ścieżkę.
     *
     * Katalog jest czyszczony: po skróceniu karuzeli nie mogą zostać sieroty,
     * bo manifest wylicza slajdy z osobna i stary 05.html po prostu zostałby
     * na dysku, myląc przy podglądzie w Studiu.
     */
    public function stage(SocialPost $post): string
    {
        $dir = $this->slidesPath($post->slug);

        File::deleteDirectory($dir);
        File::ensureDirectoryExists($dir);

        $documents = $this->images->renderPost($post);
        $slides = [];

        foreach ($documents as $i => $html) {
            $file = sprintf('%02d.html', $i + 1);
            File::put($dir . DIRECTORY_SEPARATOR . $file, $html);

            $slides[] = [
                'file'             => $file,
                'durationInFrames' => $this->durationFor($post->slides[$i]),
                'bodyChildren'     => $this->bodyChildren($html),
            ];
        }

        File::put(
            $dir . DIRECTORY_SEPARATOR . 'reel.json',
            (string) json_encode([
                'slug'    => $post->slug,
                'type'    => $post->type->value,
                'style'   => $this->styles->resolve($post),
                'accent'  => $this->images->theme($post)['accent'],
                'canvas'  => $post->type->canvas(),
                'fps'     => (int) config('social.video.fps'),
                'slides'  => $slides,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        return $dir;
    }

    public function slidesPath(string $slug): string
    {
        return rtrim((string) config('social.video.project_path'), '/\\')
            . DIRECTORY_SEPARATOR . 'public'
            . DIRECTORY_SEPARATOR . 'slides'
            . DIRECTORY_SEPARATOR . $slug;
    }

    /**
     * Ile klatek trzyma się slajd – liczone z OBJĘTOŚCI TREŚCI, nie na sztywno.
     *
     * Slajd z blokiem kodu czyta się dłużej niż sam hook, więc stała długość
     * albo urywałaby kod, albo trzymała pusty hook w nieskończoność. Kod dostaje
     * osobną stawkę za linię: skanuje się go wolniej niż prozę.
     */
    private function durationFor(SocialSlide $slide): int
    {
        $timing = (array) config('social.video.timing');

        $words = str_word_count($slide->plainText() . ' ' . ($slide->headline ?? ''));

        $codeLines = 0;

        foreach ($slide->codeBlocks() as $code) {
            $codeLines += substr_count($code, "\n") + 1;
        }

        $frames = $timing['base']
            + $words * $timing['per_word']
            + $codeLines * $timing['per_code_line'];

        return (int) max($timing['min'], min($timing['max'], $frames));
    }

    /**
     * Ile elementów najwyższego poziomu ma `.body` – z tego Remotion robi stagger
     * wjazdu treści.
     *
     * Liczone z WYRENDEROWANEGO dokumentu, a nie z `$slide->html`, bo nie każdy
     * widok wsadza markdown do `.body` w całości: `quote` wyciąga kod do osobnego
     * "okna", więc licząc z markdownu dostalibyśmy dzieci, których tam nie ma.
     */
    private function bodyChildren(string $html): int
    {
        // <head> odpada przed parsowaniem: nie niesie treści, a waży ~160 KB
        // wklejonego w base64 fontu.
        $body = preg_replace('/<head>.*?<\/head>/s', '', $html) ?? $html;

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $body, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $node = (new DOMXPath($dom))
            ->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' body ')]")
            ?->item(0);

        if (! $node instanceof DOMElement) {
            return 0;
        }

        $count = 0;

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Czy ten typ ma własną kanwę 9:16 (story) – wtedy Reel nie potrzebuje
     * podkładu ani przesunięcia, bo slajd wypełnia kadr w całości.
     */
    public static function isNativeVertical(SocialPostType $type): bool
    {
        return $type->height() === 1920;
    }
}
