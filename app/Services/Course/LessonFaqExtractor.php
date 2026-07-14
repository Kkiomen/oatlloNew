<?php

namespace App\Services\Course;

/**
 * Wyciąga sekcję FAQ z HTML lekcji (nagłówek <h2>FAQ</h2>, pytania w <h3>,
 * odpowiedzi w kolejnych elementach) i zwraca pary pytanie/odpowiedź.
 *
 * Używane do wygenerowania danych strukturalnych schema.org FAQPage (rich results
 * w Google). Parsowanie jest nietrwałe i uniwersalne dla lekcji z plików i z bazy —
 * działa na tym samym HTML, który widzi użytkownik (po sanityzacji).
 *
 * Bezpiecznik: metoda NIGDY nie rzuca wyjątku — przy błędzie zwraca pustą tablicę,
 * więc render lekcji nie może się wywalić (500) przez FAQ.
 */
class LessonFaqExtractor
{
    /**
     * @return array<int,array{question:string,answer:string}>
     */
    public static function extract(?string $html): array
    {
        if (empty($html) || stripos($html, 'faq') === false) {
            return [];
        }

        try {
            return self::parse($html);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * @return array<int,array{question:string,answer:string}>
     */
    private static function parse(string $html): array
    {
        $dom = new \DOMDocument();

        $previous = libxml_use_internal_errors(true);
        // Owijamy treść w <div id="faq-root">, żeby mieć pewny punkt zaczepienia.
        // Prolog xml wymusza poprawne UTF-8. Bloki CommonMark (p, h2, h3, pre, ul, ...)
        // nie zawierają <div>, więc nasz wrapper to pierwszy <div> w dokumencie.
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="faq-root">' . $html . '</div>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $wrapper = null;
        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('id') === 'faq-root') {
                $wrapper = $div;
                break;
            }
        }
        if ($wrapper === null) {
            return [];
        }

        $items = [];
        $inFaq = false;
        $currentQuestion = null;
        $currentAnswer = [];

        $flush = function () use (&$items, &$currentQuestion, &$currentAnswer): void {
            if ($currentQuestion !== null) {
                $answer = trim(implode(' ', $currentAnswer));
                if ($currentQuestion !== '' && $answer !== '') {
                    $items[] = ['question' => $currentQuestion, 'answer' => $answer];
                }
            }
            $currentQuestion = null;
            $currentAnswer = [];
        };

        foreach ($wrapper->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower($node->nodeName);
            $text = trim(preg_replace('/\s+/', ' ', (string) $node->textContent));

            if ($tag === 'h2') {
                // Wejście w sekcję FAQ.
                if (!$inFaq && preg_match('/^faq\b/i', $text)) {
                    $inFaq = true;
                    continue;
                }
                // Dowolny inny <h2> kończy sekcję FAQ.
                if ($inFaq) {
                    $flush();
                    $inFaq = false;
                }
                continue;
            }

            if (!$inFaq) {
                continue;
            }

            if ($tag === 'h3') {
                $flush();
                $currentQuestion = $text;
                continue;
            }

            if ($currentQuestion !== null && $text !== '') {
                $currentAnswer[] = $text;
            }
        }

        $flush();

        return $items;
    }
}
