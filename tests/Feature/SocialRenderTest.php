<?php

namespace Tests\Feature;

use App\Services\Social\EmbeddedFontProvider;
use App\Services\Social\MarkdownSocialPostParser;
use App\Services\Social\SocialImageService;
use App\Services\Social\SocialPost;
use App\Services\Social\SocialStyleResolver;
use App\Services\Theme\TechThemeResolver;
use Tests\TestCase;

/**
 * Render grafik social media do HTML.
 *
 * Testujemy DOKUMENT, nie piksele: że kanwa ma dokładne wymiary, że font jest
 * wklejony, że nie ma zewnętrznych zasobów i że nie przemyciliśmy glifów spoza
 * subsetu. Sama rasteryzacja to SocialRasterizerTest – tam też nie odpalamy
 * przeglądarki.
 *
 * Bez RefreshDatabase – moduł social nie ma tabeli.
 */
class SocialRenderTest extends TestCase
{
    private SocialImageService $images;

    private MarkdownSocialPostParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new MarkdownSocialPostParser();
        $this->images = new SocialImageService(new TechThemeResolver(), new EmbeddedFontProvider(), new SocialStyleResolver());
    }

    private function makePost(string $frontmatter, string $body): SocialPost
    {
        return $this->parser->toPost("---\n{$frontmatter}\n---\n\n{$body}", 'render-fixture');
    }

    private function carousel(): SocialPost
    {
        return $this->makePost(
            "type: carousel\ntitle: Fix N+1\ntopic: laravel\nlink: https://oatllo.com/x\ncaption: Hi.",
            "## Hook line\n\nBody.\n\n<!-- slide -->\n\n## Second\n\nMore.\n\n<!-- slide role=\"cta\" -->\n\n## Last\n\nEnd.",
        );
    }

    public function test_all_four_types_render(): void
    {
        $cases = [
            'carousel' => $this->carousel(),
            'quote'    => $this->makePost("type: quote\ntopic: laravel", "## Tip\n\n```php\n\$x = 1;\n```\n\nDone."),
            'announce' => $this->makePost("type: announce\ntopic: docker\nsource_type: course\nlink: https://oatllo.com/course/d", "## New free course\n\nBody."),
            'story'    => $this->makePost("type: story\ntopic: docker", "## Story headline\n\nBody."),
        ];

        foreach ($cases as $name => $post) {
            $documents = $this->images->renderPost($post);

            $this->assertNotEmpty($documents, "Typ {$name} nic nie wyrenderował.");
            $this->assertStringContainsString('<!doctype html>', strtolower($documents[0]), "Typ {$name}: to nie jest dokument HTML.");
        }
    }

    public function test_canvas_has_the_exact_instagram_dimensions(): void
    {
        $feed = $this->images->renderPost($this->carousel())[0];

        $this->assertStringContainsString('width: 1080px', $feed);
        $this->assertStringContainsString('height: 1350px', $feed);

        $story = $this->images->renderPost($this->makePost("type: story\ntopic: docker", "## S\n\nB."))[0];

        $this->assertStringContainsString('width: 1080px', $story);
        $this->assertStringContainsString('height: 1920px', $story);
    }

    /**
     * Bez wklejonego fontu headless podmieniłby Montserrata na font systemowy –
     * inne metryki, inne łamanie linii. To jest ta asercja, która tego pilnuje.
     */
    public function test_font_is_embedded_as_base64(): void
    {
        $html = $this->images->renderPost($this->carousel())[0];

        $this->assertStringContainsString('@font-face', $html);
        $this->assertStringContainsString('data:font/woff2;base64,', $html);
        $this->assertStringContainsString("font-family:'Montserrat'", $html);
        $this->assertStringContainsString('font-display:block', $html);
    }

    /**
     * Dokument musi być samowystarczalny: renderuje się spod file:// bez sieci
     * i bez `php artisan serve`. Każdy zewnętrzny zasób to potencjalny pusty
     * prostokąt na gotowej grafice.
     */
    public function test_document_has_no_external_resources(): void
    {
        $html = $this->images->renderPost($this->carousel())[0];

        $this->assertDoesNotMatchRegularExpression('/<link\b/i', $html);
        $this->assertDoesNotMatchRegularExpression('/<script\b/i', $html);
        $this->assertDoesNotMatchRegularExpression('/<img\b/i', $html);
        // url(...) dozwolone wyłącznie dla data: URI.
        $this->assertDoesNotMatchRegularExpression('/url\(\s*(?!data:)[^)]/i', $html);
    }

    /**
     * U+2192/U+2190 wypadają z subsetu latin. Podpowiedź "Swipe" MUSI używać
     * inline SVG, a nie znaku strzałki.
     */
    public function test_rendered_output_contains_no_glyphs_outside_the_font_subset(): void
    {
        foreach ($this->images->renderPost($this->carousel()) as $html) {
            $this->assertStringNotContainsString("\u{2192}", $html);
            $this->assertStringNotContainsString("\u{2190}", $html);
            $this->assertStringNotContainsString("\u{2014}", $html);
        }
    }

    public function test_swipe_hint_uses_an_inline_svg_chevron(): void
    {
        $html = $this->images->renderPost($this->carousel())[0];

        $this->assertStringContainsString('Swipe', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function test_branding_is_on_every_slide(): void
    {
        foreach ($this->images->renderPost($this->carousel()) as $i => $html) {
            $this->assertStringContainsString('oatllo', $html, "Slajd {$i} bez marki.");
            $this->assertStringContainsString('.com', $html);
        }
    }

    public function test_carousel_shows_slide_numbers_but_not_on_the_hook(): void
    {
        $documents = $this->images->renderPost($this->carousel());

        // Sprawdzamy WIDOCZNĄ pigułkę, nie cały dokument: numer slajdu jest też
        // w <title>, więc szukanie "01/03" w całym HTML dałoby fałszywy alarm.
        $this->assertStringNotContainsString('class="slide-no"', $documents[0], 'Hook jest miniaturą w feedzie i ma być czysty.');

        $this->assertStringContainsString('class="slide-no"', $documents[1]);
        $this->assertStringContainsString('>02/03<', $documents[1]);
        $this->assertStringContainsString('>03/03<', $documents[2]);
    }

    public function test_accent_and_label_come_from_the_theme(): void
    {
        $html = $this->images->renderPost($this->carousel())[0];

        $this->assertStringContainsString('#ff2d20', $html); // Laravel
        $this->assertStringContainsString('Laravel', $html);
    }

    public function test_accent_soft_is_an_rgba_not_a_css_color_function(): void
    {
        $html = $this->images->renderPost($this->carousel())[0];

        $this->assertMatchesRegularExpression('/--accent-soft:\s*rgba\(\d+,\d+,\d+,[\d.]+\)/', $html);
        // color-mix() to zbędna zależność od wersji przeglądarki – liczymy w PHP.
        $this->assertStringNotContainsString('color-mix', $html);
    }

    /**
     * Motyw 'php' ma CELOWO puste logo. Bez guardu w layoucie dostalibyśmy pusty
     * <svg> udający znak wodny.
     */
    public function test_theme_with_an_empty_logo_renders_without_a_watermark(): void
    {
        $post = $this->makePost("type: quote\ntopic: php", "## Composer tip\n\nBody.");

        $html = $this->images->renderPost($post)[0];

        $this->assertStringNotContainsString('class="watermark"', $html);
        $this->assertStringContainsString('#34d399', $html); // akcent PHP nadal jest
    }

    public function test_theme_with_a_logo_renders_the_watermark(): void
    {
        $html = $this->images->renderPost($this->carousel())[0];

        $this->assertStringContainsString('class="watermark"', $html);
        $this->assertStringContainsString('viewBox="0 0 100 100"', $html);
    }

    public function test_story_uses_instagram_safe_areas(): void
    {
        $html = $this->images->renderPost($this->makePost("type: story\ntopic: docker", "## S\n\nB."))[0];

        // Interfejs Instagrama zasłania te pasy – treść nie może tam wejść.
        $this->assertStringContainsString('padding: 250px 90px 320px', $html);
    }

    public function test_quote_splits_code_into_a_window_and_prose_below(): void
    {
        $post = $this->makePost("type: quote\ntopic: laravel", "## Tip\n\n```php\n\$x = 1;\n```\n\nThe takeaway.");

        $html = $this->images->renderPost($post)[0];

        $this->assertStringContainsString('window-bar', $html);
        $this->assertStringContainsString('php', $html);         // nazwa na pasku okna
        $this->assertStringContainsString('The takeaway.', $html);
    }

    public function test_html_is_escaped_so_a_post_cannot_break_the_document(): void
    {
        $post = $this->makePost('type: quote', "## <script>alert(1)</script>\n\nBody.");

        $html = $this->images->renderPost($post)[0];

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
