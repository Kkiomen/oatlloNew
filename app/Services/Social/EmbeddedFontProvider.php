<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Buduje bloki @font-face z fontem wklejonym jako data:base64.
 *
 * DLACZEGO w ogóle: grafiki social renderuje headless Chrome/Edge z lokalnego
 * pliku. Montserrat NIE jest fontem systemowym Windows, a dokument spod file://
 * nie doczyta pewnie zewnętrznego url(...). Bez wklejenia fontu w treść
 * dostalibyśmy podmianę na font systemowy o innych metrykach – czyli inne
 * łamanie linii i inny kerning niż w podglądzie.
 *
 * Efekt uboczny (pożądany): wyeksportowany .html nie ma ŻADNYCH podzasobów, więc
 * `social:export` działa bez `php artisan serve` i bez sieci.
 *
 * Subset "latin" wystarcza – treści są po angielsku. UWAGA: jego unicode-range
 * NIE obejmuje U+2192 ('→') ani U+2190 ('←'); pilnuje tego SocialPostLinter.
 */
class EmbeddedFontProvider
{
    /** @var array<string, string> */
    private array $cache = [];

    /**
     * Zwraca gotowe reguły @font-face dla podanych wag.
     *
     * @param  list<int>|null  $weights  null => wagi z config('social.fonts.weights')
     */
    public function css(?array $weights = null): string
    {
        $weights ??= (array) config('social.fonts.weights', [400, 600, 800]);
        $key = implode(',', $weights);

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $css = '';
        foreach ($weights as $weight) {
            $css .= $this->fontFace((int) $weight);
        }

        return $this->cache[$key] = $css;
    }

    private function fontFace(int $weight): string
    {
        return "@font-face{font-family:'Montserrat';font-style:normal;font-weight:{$weight};"
            // font-display:block – wolimy poczekać niż zrobić zrzut na foncie zastępczym.
            . 'font-display:block;'
            . "src:url({$this->dataUri($weight)}) format('woff2');}";
    }

    private function dataUri(int $weight): string
    {
        return 'data:font/woff2;base64,' . base64_encode(File::get($this->path($weight)));
    }

    /**
     * @throws RuntimeException gdy pliku fontu brakuje – lepiej głośno paść niż
     *                          po cichu wyeksportować grafikę złym fontem.
     */
    public function path(int $weight): string
    {
        $dir = rtrim((string) config('social.fonts.dir'), '/\\');
        $file = str_replace('{weight}', (string) $weight, (string) config('social.fonts.pattern'));
        $path = $dir . DIRECTORY_SEPARATOR . $file;

        if (! File::exists($path)) {
            throw new RuntimeException(
                "Brak pliku fontu: {$path}. Grafiki social wymagają Montserrata wklejonego w base64 – "
                . 'bez niego headless podmieni font na systemowy i popsuje layout.'
            );
        }

        return $path;
    }

    /**
     * Czy wszystkie potrzebne wagi są na dysku (diagnostyka dla social:export).
     */
    public function available(): bool
    {
        foreach ((array) config('social.fonts.weights', []) as $weight) {
            try {
                $this->path((int) $weight);
            } catch (RuntimeException) {
                return false;
            }
        }

        return true;
    }
}
