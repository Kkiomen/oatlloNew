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
 * Pakiet stylów: automatyczny dobór skórki i jej poprawne nałożenie.
 *
 * Bez RefreshDatabase – moduł social nie ma tabeli.
 */
class SocialStyleTest extends TestCase
{
    private SocialStyleResolver $styles;

    private SocialImageService $images;

    private MarkdownSocialPostParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new MarkdownSocialPostParser();
        $this->styles = new SocialStyleResolver();
        $this->images = new SocialImageService(new TechThemeResolver(), new EmbeddedFontProvider(), $this->styles);
    }

    private function makePost(string $frontmatter, string $body = "## Headline\n\nBody."): SocialPost
    {
        return $this->parser->toPost("---\n{$frontmatter}\n---\n\n{$body}", 'style-fixture');
    }

    public function test_explicit_style_wins_over_everything(): void
    {
        // Story normalnie dostaje spotlight – jawny wybór autora musi to przebić.
        $post = $this->makePost("type: story\nstyle: paper\ntopic: docker");

        $this->assertSame('paper', $this->styles->resolve($post));
    }

    public function test_unknown_explicit_style_falls_back_to_the_automatic_choice(): void
    {
        $post = $this->makePost("type: story\nstyle: nonsense\ntopic: docker");

        // Chodzi o to, że automat W OGÓLE zadziałał – konkretny styl wybiera pula typu.
        $this->assertContains($this->styles->resolve($post), config('social-styles.type_rotation.story'));
    }

    /**
     * Post z blokiem ```bash JEST sesją terminala – to najsilniejszy sygnał o formie.
     */
    public function test_shell_code_picks_the_terminal_style(): void
    {
        $post = $this->makePost(
            "type: carousel\ntopic: laravel",
            "## A\n\n```bash\ndocker ps\n```\n\n<!-- slide -->\n\n## B\n\nBody.",
        );

        $this->assertSame('terminal', $this->styles->resolve($post));
    }

    public function test_dockerfile_code_also_picks_terminal(): void
    {
        $post = $this->makePost('type: quote', "## A\n\n```dockerfile\nFROM php:8.3-fpm-alpine\n```");

        $this->assertSame('terminal', $this->styles->resolve($post));
    }

    public function test_php_code_does_not_trigger_terminal(): void
    {
        $post = $this->makePost('type: quote', "## A\n\n```php\n\$x = 1;\n```");

        $this->assertNotSame('terminal', $this->styles->resolve($post));
    }

    /**
     * TYP IDZIE PRZED TEMATEM. Regresja: przy odwrotnej kolejności zapowiedź kursu
     * Dockera dostawała chrome terminala, który gryzł się z jej wielkim logo.
     */
    public function test_type_beats_topic(): void
    {
        $announce = $this->makePost("type: announce\ntopic: docker\nsource_type: course");
        $this->assertContains($this->styles->resolve($announce), config('social-styles.type_rotation.announce'));

        $story = $this->makePost("type: story\ntopic: docker");
        $this->assertContains($this->styles->resolve($story), config('social-styles.type_rotation.story'));

        // Sedno regresji: temat 'docker' ciągnie na terminal, a typ ma go przebić.
        $this->assertNotSame('terminal', $this->styles->resolve($announce));
        $this->assertNotSame('terminal', $this->styles->resolve($story));
    }

    public function test_quote_gets_a_style_from_the_quote_pool(): void
    {
        $this->assertContains(
            $this->styles->resolve($this->makePost('type: quote')),
            config('social-styles.type_rotation.quote'),
        );
    }

    /**
     * Pula typu istnieje PO TO, żeby kilkanaście story z rzędu nie wyglądało tak
     * samo – to był największy pojedynczy powód monotonii feedu. Pojedynczy styl
     * na typ nie przechodzi tego testu.
     */
    public function test_type_pool_spreads_posts_of_the_same_type_across_styles(): void
    {
        $picked = [];

        foreach (['story-alpha', 'story-beta', 'story-gamma', 'story-delta', 'story-epsilon', 'story-zeta'] as $slug) {
            $post = $this->parser->toPost("---\ntype: story\nslug: {$slug}\ntopic: docker\n---\n\n## A\n\nB.", $slug);
            $picked[] = $this->styles->resolve($post);
        }

        $this->assertGreaterThan(1, count(array_unique($picked)), 'Wszystkie story dostają ten sam styl – feed będzie monotonny.');
    }

    /**
     * Dobór z puli typu musi być tak samo deterministyczny jak rotacja – inaczej
     * każdy eksport dawałby inną grafikę.
     */
    public function test_type_pool_is_deterministic(): void
    {
        $post = $this->makePost("type: story\ntopic: docker");
        $first = $this->styles->resolve($post);

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($first, $this->styles->resolve($post));
        }
    }

    /**
     * Literówka w puli ma zawężyć wybór, a nie wyprodukować nazwę skórki, której
     * nie ma – widok wywaliłby się wtedy na @include.
     */
    public function test_unknown_style_in_a_type_pool_is_ignored(): void
    {
        config(['social-styles.type_rotation.quote' => ['vaporwave', 'editorial']]);

        $this->assertSame('editorial', $this->styles->resolve($this->makePost('type: quote')));
    }

    /**
     * Pusta pula => stary tryb, czyli pojedyncza afinicja `types` z pakietu.
     */
    public function test_empty_type_pool_falls_back_to_the_pack_affinity(): void
    {
        config(['social-styles.type_rotation' => []]);

        $this->assertSame('editorial', $this->styles->resolve($this->makePost('type: quote')));
        $this->assertSame('spotlight', $this->styles->resolve($this->makePost('type: story')));
    }

    public function test_topic_picks_blueprint_for_structural_subjects(): void
    {
        $post = $this->makePost("type: carousel\ntopic: database", "## A\n\nB.\n\n<!-- slide -->\n\n## C\n\nD.");

        $this->assertSame('blueprint', $this->styles->resolve($post));
    }

    /**
     * Dobór MUSI być deterministyczny – inaczej każdy `social:export` dawałby inną
     * grafikę i nie dałoby się jej poprawiać.
     */
    public function test_fallback_rotation_is_deterministic(): void
    {
        $post = $this->makePost("type: carousel\nslug: rotation-check", "## A\n\nB.\n\n<!-- slide -->\n\n## C\n\nD.");

        $first = $this->styles->resolve($post);

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame($first, $this->styles->resolve($post), 'Dobór stylu nie może się zmieniać między wywołaniami.');
        }

        $this->assertContains($first, config('social-styles.rotation'));
    }

    public function test_rotation_spreads_posts_across_styles(): void
    {
        $picked = [];

        foreach (['alpha-post', 'beta-post', 'gamma-post', 'delta-post', 'epsilon-post', 'zeta-post'] as $slug) {
            $post = $this->parser->toPost(
                "---\ntype: carousel\nslug: {$slug}\n---\n\n## A\n\nB.\n\n<!-- slide -->\n\n## C\n\nD.",
                $slug,
            );
            $picked[] = $this->styles->resolve($post);
        }

        $this->assertGreaterThan(1, count(array_unique($picked)), 'Rotacja ma dawać zróżnicowany feed, a nie jeden styl.');
    }

    public function test_every_style_in_the_pack_has_a_skin_view(): void
    {
        foreach ($this->styles->names() as $style) {
            $this->assertTrue(
                view()->exists('social.styles.' . $style),
                "Styl '{$style}' nie ma pliku skórki resources/views/social/styles/{$style}.blade.php",
            );
        }
    }

    public function test_every_style_renders_every_post_type(): void
    {
        $types = [
            'carousel' => ["type: carousel\ntopic: laravel", "## A\n\nB.\n\n<!-- slide -->\n\n## C\n\nD."],
            'quote'    => ["type: quote\ntopic: laravel", "## A\n\n```php\n\$x = 1;\n```\n\nDone."],
            'announce' => ["type: announce\ntopic: docker\nsource_type: course\nlink: https://oatllo.com/course/d", "## A\n\nB."],
            'story'    => ["type: story\ntopic: docker", "## A\n\nB."],
        ];

        foreach ($this->styles->names() as $style) {
            foreach ($types as $type => [$fm, $body]) {
                $html = $this->images->renderPost($this->makePost($fm, $body), $style)[0];

                $this->assertStringContainsString("style-{$style}", $html, "Styl {$style} na typie {$type}: brak klasy skórki.");
                $this->assertStringContainsString(".canvas.style-{$style}", $html, "Styl {$style} na typie {$type}: brak reguł skórki.");
            }
        }
    }

    /**
     * REGRESJA: komentarz CSS z dyrektywą Blade'a w środku był rozwijany, a wklejona
     * sekcja stylów sama zawiera komentarze – a te w CSS SIĘ NIE ZAGNIEŻDŻAJĄ.
     * Pierwszy domykacz komentarza zamykał go za wcześnie, osierocony domykacz był
     * błędem parsowania i ZJADAŁ całą regułę skórki. Objawiało się to tak, że styl
     * działał na jednych typach postów, a na innych nie.
     *
     * (Tego akapitu nie da się napisać dosłownym domykaczem – zamknąłby ten docblock.
     * Ta sama klasa błędu, inny język.)
     */
    public function test_rendered_css_has_no_orphan_comment_terminator(): void
    {
        $types = [
            ["type: carousel\ntopic: laravel", "## A\n\nB.\n\n<!-- slide -->\n\n## C\n\nD."],
            ["type: quote\ntopic: laravel", "## A\n\nB."],
            ["type: announce\ntopic: docker", "## A\n\nB."],
            ["type: story\ntopic: docker", "## A\n\nB."],
        ];

        foreach ($this->styles->names() as $style) {
            foreach ($types as [$fm, $body]) {
                $html = $this->images->renderPost($this->makePost($fm, $body), $style)[0];
                $stripped = preg_replace('#/\*.*?\*/#s', '', $html);

                $this->assertStringNotContainsString(
                    '*/',
                    (string) $stripped,
                    "Styl {$style}: osierocony '*/' w CSS – skórka zostanie odrzucona przez parser.",
                );

                // Reguła skórki musi być poza komentarzem, inaczej nic nie robi.
                $this->assertStringContainsString(".canvas.style-{$style}", (string) $stripped);
            }
        }
    }

    /**
     * REGRESJA: `.body code` w skórce ma wyższą specyficzność niż bazowe
     * `.body pre code`, więc trafiało też w kod WEWNĄTRZ bloku i barwiło go na
     * ciemno na ciemnym tle. Blok kodu wychodził jako pusty prostokąt.
     */
    public function test_skins_do_not_clobber_code_inside_code_blocks(): void
    {
        foreach (['paper', 'spotlight'] as $style) {
            $html = $this->images->renderPost(
                $this->makePost("type: quote\ntopic: laravel", "## A\n\n```php\n\$x = 1;\n```"),
                $style,
            )[0];

            $this->assertDoesNotMatchRegularExpression(
                '/\.canvas\.style-' . $style . ' \.body code\s*\{/',
                $html,
                "Styl {$style}: reguła `.body code` trafia też w `pre code` – użyj `:not(pre) > code`.",
            );
        }
    }

    /**
     * REGRESJA: styl `card` robi z .stage kartę odsuniętą od krawędzi kanwy, a
     * `.story-footer` (jedyny element w pakiecie z position:absolute) kotwiczy się
     * właśnie do .stage. Bez własnej reguły bezpieczny margines story liczył się
     * DWA razy – stopka lądowała 260px nad krawędzią KARTY, czyli pod przyciskiem
     * "Link in bio". Kto zmienia geometrię .stage w skórce, musi tę stopkę przeliczyć.
     */
    public function test_card_style_reanchors_the_absolutely_positioned_story_footer(): void
    {
        $html = $this->images->renderPost($this->makePost("type: story\ntopic: docker"), 'card')[0];

        $this->assertMatchesRegularExpression(
            '/\.canvas\.style-card \.story-footer\s*\{/',
            $html,
            'Styl card przesuwa .stage, więc MUSI przeliczyć kotwicę .story-footer.',
        );
    }

    public function test_terminal_style_adds_its_window_chrome(): void
    {
        $html = $this->images->renderPost($this->makePost('type: quote'), 'terminal')[0];

        $this->assertStringContainsString('term-bar', $html);
        $this->assertStringContainsString('term-title', $html);
    }

    public function test_styles_without_chrome_do_not_add_it(): void
    {
        $html = $this->images->renderPost($this->makePost('type: quote'), 'midnight')[0];

        $this->assertStringNotContainsString('term-bar', $html);
    }

    /**
     * Styl 'spotlight' stawia tekst na tle w kolorze akcentu. Akcenty bywają jasne
     * (amber) i ciemne (czerwień Laravela) – sztywny kolor tekstu byłby nieczytelny
     * na połowie technologii, więc liczymy go luminancją.
     */
    public function test_ink_is_chosen_by_contrast_not_by_guessing(): void
    {
        // Jasne tła => ciemny tekst. Bezdyskusyjne.
        $this->assertSame('#0b1120', $this->images->inkFor('#ffffff'));
        $this->assertSame('#0b1120', $this->images->inkFor('#fbbf24')); // amber
        $this->assertSame('#0b1120', $this->images->inkFor('#34d399')); // emerald

        // Ciemne tła => jasny tekst.
        $this->assertSame('#f8fafc', $this->images->inkFor('#000000'));
        $this->assertSame('#f8fafc', $this->images->inkFor('#326ce5')); // niebieski Kubernetesa: 4.76 vs 4.41

        // Te dwa są NIEOCZYWISTE i dlatego liczymy, a nie zgadujemy:
        // czerwień Laravela wygląda na "ciemny kolor", ale ciemny tekst daje na niej
        // 5.66:1, a jasny tylko 3.71:1. Niebieski Dockera podobnie (6.65 vs 3.16).
        $this->assertSame('#0b1120', $this->images->inkFor('#ff2d20')); // czerwień Laravela
        $this->assertSame('#0b1120', $this->images->inkFor('#2496ed')); // niebieski Dockera
    }

    /**
     * Zepsuty kolor => zakładamy ciemne tło (bazowy motyw Oatllo jest ciemny),
     * czyli jasny tekst. Bezpieczny domyślny wybór, nie wyjątek na produkcji.
     */
    public function test_ink_falls_back_safely_for_a_broken_color(): void
    {
        $this->assertSame('#f8fafc', $this->images->inkFor('not-a-color'));
        $this->assertSame('#f8fafc', $this->images->inkFor(''));
    }

    /**
     * Wybrany atrament musi realnie spełniać kontrast WCAG AA dla dużego tekstu
     * (3:1) na KAŻDYM akcencie z pakietu motywów – inaczej styl spotlight byłby
     * ładny i nieczytelny.
     */
    public function test_chosen_ink_meets_wcag_contrast_on_every_accent(): void
    {
        $accents = array_map(
            fn (array $theme) => $theme['accent'],
            array_merge(array_values((array) config('course-covers.themes')), [config('course-covers.default')]),
        );

        foreach ($accents as $accent) {
            $ink = $this->images->inkFor($accent);
            $ratio = $this->contrast($accent, $ink);

            $this->assertGreaterThanOrEqual(
                3.0,
                $ratio,
                "Akcent {$accent} z atramentem {$ink} daje kontrast " . round($ratio, 2) . ':1 – poniżej WCAG AA dla dużego tekstu.',
            );
        }
    }

    private function contrast(string $a, string $b): float
    {
        $la = $this->luminance($a);
        $lb = $this->luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private function luminance(string $hex): float
    {
        [$r, $g, $b] = array_map('hexdec', str_split(ltrim($hex, '#'), 2));

        $lin = static function (int $c): float {
            $c /= 255;

            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);
    }

    public function test_spotlight_inverts_the_accent_bar_so_it_stays_visible(): void
    {
        $html = $this->images->renderPost($this->makePost("type: story\ntopic: docker"), 'spotlight')[0];

        // Pasek akcentu na tle akcentu byłby niewidoczny.
        $this->assertMatchesRegularExpression('/--bar:\s*var\(--accent-ink\)/', $html);
    }

    public function test_unknown_style_in_frontmatter_is_a_lint_error(): void
    {
        $linter = new \App\Services\Social\SocialPostLinter($this->parser, $this->styles);

        $issues = $linter->lintRaw("---\ntype: quote\nstyle: vaporwave\ncaption: Hi.\n---\n\n## A\n\nB.", 'x');

        $errors = array_filter($issues, fn ($i) => $i->isError());
        $messages = implode(' ', array_map(fn ($i) => $i->message, $errors));

        $this->assertStringContainsString("Nieznany style 'vaporwave'", $messages);
    }

    public function test_valid_style_in_frontmatter_passes_lint(): void
    {
        $linter = new \App\Services\Social\SocialPostLinter($this->parser, $this->styles);

        $issues = $linter->lintRaw("---\ntype: quote\nstyle: terminal\nstatus: ready\ncaption: Hi.\n---\n\n## A\n\nB.", 'x');

        $this->assertSame([], $issues);
    }
}
