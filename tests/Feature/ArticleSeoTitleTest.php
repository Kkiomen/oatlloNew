<?php

namespace Tests\Feature;

use App\Services\Article\MarkdownArticleParser;
use Tests\TestCase;

/**
 * Artykuły `.md` mają własny `seo_title` / `seo_description`, niezależny od H1.
 *
 * Kontekst (analiza GSC 2026-08-09): CTR jest wąskim gardłem domeny, a artykuły `.md`
 * konwertują 10-20x lepiej niż lekcje kursów - czyli to na nich najbardziej opłaca się
 * pracować. Tyle że jako jedyny typ treści NIE MIAŁY dźwigni, którą lekcje mają od zawsze:
 * `article.blade.php` brał `$article->name` prosto na `<title>`, więc nagłówek artykułu
 * i tytuł w wynikach wyszukiwania były tym samym ciągiem. 36 ze 140 zakolejkowanych
 * artykułów przekraczało po doklejeniu " | Oatllo" ~60 znaków (Google ucina), a jedyną
 * naprawą było skrócenie H1 - czyli psucie tekstu, żeby naprawić meta.
 *
 * Rozwiązanie wpina się w ISTNIEJĄCY szew: widok od dawna czyta nadpiski SEO z
 * `view_content` (tak działają artykuły z bazy), więc parser tylko go wypełnia i widok
 * nie wymagał zmiany. Ten test pilnuje kontraktu z widokiem - gdyby ktoś przemianował
 * klucze, tytuły po cichu wróciłyby do `name`.
 */
class ArticleSeoTitleTest extends TestCase
{
    private function parse(string $frontmatter): \App\Models\Article
    {
        return app(MarkdownArticleParser::class)->toArticle(
            "---\n{$frontmatter}\n---\n\nTresc artykulu.",
            'fallback-slug'
        );
    }

    public function test_seo_title_lands_where_the_view_reads_it(): void
    {
        $article = $this->parse(
            "name: \"How to Write Meaningful Commit Messages That Your Team Will Actually Read\"\n"
            . "slug: commits\n"
            . "seo_title: \"How to Write Good Commit Messages\""
        );

        // Klucz jest kontraktem z article.blade.php (linia z $seoTitle).
        $this->assertSame(
            'How to Write Good Commit Messages',
            $article->view_content['basic_website_structure_title'] ?? null
        );

        // H1 zostaje pełny - o to w tym całym rozdzieleniu chodzi.
        $this->assertSame(
            'How to Write Meaningful Commit Messages That Your Team Will Actually Read',
            $article->name
        );
    }

    public function test_seo_description_can_override_short_description(): void
    {
        $article = $this->parse(
            "name: \"Test\"\nslug: test\n"
            . "short_description: \"Lead pod artykulem.\"\n"
            . "seo_description: \"Opis pod wyniki wyszukiwania.\""
        );

        $this->assertSame(
            'Opis pod wyniki wyszukiwania.',
            $article->view_content['basic_website_structure_description'] ?? null
        );
        $this->assertSame('Lead pod artykulem.', $article->short_description);
    }

    /**
     * Bez `seo_title` widok ma dalej brać `name`. To jest zachowanie 140 istniejących
     * plików, więc pusty `view_content` musi zostać PUSTY - wpisanie tam `name` na siłę
     * wyglądałoby identycznie, ale zabrałoby widokowi możliwość rozróżnienia.
     */
    public function test_article_without_seo_fields_keeps_view_content_empty(): void
    {
        $article = $this->parse("name: \"Bez SEO\"\nslug: bez-seo");

        $this->assertSame([], $article->view_content);
    }

    /**
     * Pusty/whitespace'owy `seo_title` to nie jest nadpisanie tytułu pustką.
     */
    public function test_blank_seo_title_is_ignored(): void
    {
        $article = $this->parse("name: \"Test\"\nslug: test\nseo_title: \"   \"");

        $this->assertArrayNotHasKey('basic_website_structure_title', $article->view_content);
    }
}
