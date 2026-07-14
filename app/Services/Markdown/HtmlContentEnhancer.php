<?php

declare(strict_types=1);

namespace App\Services\Markdown;

/**
 * Post-processing HTML wygenerowanego z Markdown (CommonMark) — wspólny dla
 * artykułów (.md), kursów plikowych i importu kursów do bazy.
 *
 * Robi dwie rzeczy istotne dla SEO / Core Web Vitals, których goły CommonMark
 * nie ustawia:
 *  1. Obrazy w treści dostają loading="lazy" + decoding="async" (mniej CLS,
 *     szybsze pierwsze malowanie). Obrazki hero w szablonach nie przechodzą
 *     przez tę ścieżkę, więc ich eager/fetchpriority zostaje nietknięty.
 *  2. Nagłówek h1 w treści jest degradowany do h2, bo H1 należy wyłącznie do
 *     tytułu strony (szablon). Autor piszący "# Tytuł" w treści nie zrobi w ten
 *     sposób drugiego H1 na stronie. Konwencja treści to i tak "##" dla sekcji
 *     i "###" dla podsekcji — tych NIE ruszamy, żeby nie zepsuć hierarchii
 *     (kilka h2 na stronie jest w pełni poprawne semantycznie i SEO).
 *
 * Wszystko operuje na zaufanym wyjściu CommonMark, więc proste regexy są tu
 * bezpieczne i tańsze niż budowanie DOM-a przy każdym renderze.
 */
class HtmlContentEnhancer
{
    public static function enhance(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        return self::demoteH1(self::lazyLoadImages($html));
    }

    /**
     * Dokłada loading="lazy" i decoding="async" do obrazów treści, które ich
     * jeszcze nie mają (idempotentne — nie dubluje atrybutów).
     */
    private static function lazyLoadImages(string $html): string
    {
        return (string) preg_replace_callback('/<img\b([^>]*)>/i', function (array $m): string {
            $attrs = $m[1];
            $additions = '';

            if (! preg_match('/\bloading\s*=/i', $attrs)) {
                $additions .= ' loading="lazy"';
            }
            if (! preg_match('/\bdecoding\s*=/i', $attrs)) {
                $additions .= ' decoding="async"';
            }

            return '<img' . $attrs . $additions . '>';
        }, $html);
    }

    /**
     * Degraduje wyłącznie nagłówki h1 w treści do h2 (H1 jest zarezerwowany dla
     * tytułu strony). Poziomy h2-h6 zostają nietknięte, bo to nośnik hierarchii
     * sekcji pisanej przez autorów ("##" / "###").
     */
    private static function demoteH1(string $html): string
    {
        return (string) preg_replace(
            ['#<h1(\s|>)#i', '#</h1>#i'],
            ['<h2$1', '</h2>'],
            $html
        );
    }
}
