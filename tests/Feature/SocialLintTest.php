<?php

namespace Tests\Feature;

use App\Services\Social\MarkdownSocialPostParser;
use App\Services\Social\SocialLintIssue;
use App\Services\Social\SocialPostLinter;
use App\Services\Social\SocialStyleResolver;
use Tests\TestCase;

/**
 * Walidacja postów social media.
 *
 * Podział ERROR/WARNING jest istotą tego lintu: ERROR = Instagram odrzuci albo
 * grafika się nie zbuduje; WARNING = zbuduje się, ale brzydko. Testy pilnują,
 * żeby ktoś nie przesunął czegoś groźnego do warningów (i odwrotnie).
 *
 * Bez RefreshDatabase – moduł social nie ma tabeli.
 */
class SocialLintTest extends TestCase
{
    private SocialPostLinter $linter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->linter = new SocialPostLinter(new MarkdownSocialPostParser(), new SocialStyleResolver());
    }

    /**
     * @return list<SocialLintIssue>
     */
    private function lint(string $frontmatter, string $body = "## Hook\n\nBody."): array
    {
        return $this->linter->lintRaw("---\n{$frontmatter}\n---\n\n{$body}", 'fixture');
    }

    /**
     * @param  list<SocialLintIssue>  $issues
     * @return list<string>
     */
    private function messages(array $issues, string $level): array
    {
        return array_values(array_map(
            fn (SocialLintIssue $i) => $i->message,
            array_filter($issues, fn (SocialLintIssue $i) => $i->level === $level),
        ));
    }

    private function assertHasError(array $issues, string $needle): void
    {
        $errors = $this->messages($issues, SocialLintIssue::ERROR);

        $this->assertNotEmpty(
            array_filter($errors, fn (string $m) => str_contains($m, $needle)),
            "Brak ERRORa zawierającego '{$needle}'. Zgłoszone: " . json_encode($errors, JSON_UNESCAPED_UNICODE),
        );
    }

    private function assertHasWarning(array $issues, string $needle): void
    {
        $warnings = $this->messages($issues, SocialLintIssue::WARNING);

        $this->assertNotEmpty(
            array_filter($warnings, fn (string $m) => str_contains($m, $needle)),
            "Brak WARNINGu zawierającego '{$needle}'. Zgłoszone: " . json_encode($warnings, JSON_UNESCAPED_UNICODE),
        );
    }

    public function test_a_valid_quote_post_is_clean(): void
    {
        $issues = $this->lint("type: quote\nstatus: ready\ncaption: Something short.");

        $this->assertSame([], $issues);
    }

    public function test_unknown_type_is_an_error(): void
    {
        $this->assertHasError($this->lint('type: tiktok'), 'nieznany type');
    }

    public function test_unknown_frontmatter_key_is_an_error(): void
    {
        $issues = $this->lint("type: quote\nhashtag: laravel");

        $this->assertHasError($issues, 'Nieznane klucze frontmattera: hashtag');
    }

    public function test_unknown_status_is_an_error(): void
    {
        $this->assertHasError($this->lint("type: quote\nstatus: scheduled"), "Nieznany status 'scheduled'");
    }

    public function test_carousel_with_a_single_slide_is_an_error(): void
    {
        $issues = $this->lint('type: carousel', "## Only one\n\nBody.");

        $this->assertHasError($issues, "Typ 'carousel' wymaga od 2 do 10 slajdów, jest 1");
        $this->assertHasError($issues, '<!-- slide -->');
    }

    public function test_carousel_over_ten_slides_is_an_error(): void
    {
        $body = implode("\n\n<!-- slide -->\n\n", array_map(
            fn (int $i) => "## Slide {$i}\n\nBody.",
            range(1, 11),
        ));

        $this->assertHasError($this->lint('type: carousel', $body), 'jest 11');
    }

    public function test_quote_with_multiple_slides_is_an_error(): void
    {
        $body = "## A\n\nBody.\n\n<!-- slide -->\n\n## B\n\nBody.";

        $this->assertHasError($this->lint('type: quote', $body), "Typ 'quote' wymaga dokładnie 1 slajdów, jest 2");
    }

    public function test_caption_over_the_instagram_limit_is_an_error(): void
    {
        $caption = str_repeat('a', 2201);

        $this->assertHasError($this->lint("type: quote\ncaption: {$caption}"), 'limit Instagrama to 2200');
    }

    public function test_ready_post_without_a_caption_is_an_error(): void
    {
        $this->assertHasError($this->lint("type: quote\nstatus: ready"), 'pusty caption');
    }

    public function test_draft_without_a_caption_is_allowed(): void
    {
        $issues = $this->lint("type: quote\nstatus: draft");

        $this->assertSame([], $this->messages($issues, SocialLintIssue::ERROR));
    }

    /**
     * Story nie ma na Instagramie pola podpisu, więc "ready bez captionu" nie jest
     * tu żadnym brakiem – a bramka eksportu nie ma prawa go z tego powodu blokować.
     */
    public function test_story_only_post_does_not_need_a_caption(): void
    {
        $issues = $this->lint("type: story\nstatus: ready");

        $this->assertSame([], $this->messages($issues, SocialLintIssue::ERROR));
    }

    /**
     * Reguła wisi na `formats` (CO publikujesz), nie na `type` (kształt slajdów):
     * kadr 9:16 wrzucony do feedu podpis JEDNAK ma gdzie mieć.
     */
    public function test_story_shaped_post_published_to_the_feed_still_needs_a_caption(): void
    {
        $this->assertHasError($this->lint("type: story\nstatus: ready\nformats: [post]"), 'pusty caption');
    }

    /**
     * Bezpiecznik na błąd, który zrodził pole `notes`: notatki produkcyjne autora
     * ("dodaj ankietę przy wrzucaniu") lądowały w `caption`, bo story nie ma podpisu
     * i pole wyglądało na wolne – po czym wychodziły w caption.txt i w panelu
     * recenzji, czyli w jedynych dwóch miejscach znaczących "to wklejasz".
     */
    public function test_caption_on_a_story_only_post_is_a_warning(): void
    {
        $issues = $this->lint("type: story\nstatus: ready\ncaption: Dodaj ankiete przy wrzucaniu.");

        $this->assertHasWarning($issues, 'nie ma gdzie wkleić');
        $this->assertSame([], $this->messages($issues, SocialLintIssue::ERROR));
    }

    public function test_notes_on_a_story_only_post_are_clean(): void
    {
        $issues = $this->lint("type: story\nstatus: ready\nnotes: Dodaj ankiete przy wrzucaniu.");

        $this->assertSame([], $issues);
    }

    /**
     * Limit to 5, nie 30 – Instagram ściął go 18.12.2025 (@creators na Threads:
     * "Starting today, Instagram will allow up to 5 hashtags in a reel or post").
     * Szósty hashtag to ERROR, bo post z nim Instagram po prostu odrzuci, a lint
     * jest bramką eksportu – ma to złapać zanim człowiek pójdzie z tym do apki.
     */
    public function test_more_than_five_hashtags_is_an_error(): void
    {
        $tags = implode(', ', array_map(fn (int $i) => "tag{$i}", range(1, 6)));

        $this->assertHasError($this->lint("type: quote\nhashtags: [{$tags}]"), 'limit Instagrama to 5');
    }

    public function test_exactly_five_hashtags_is_fine(): void
    {
        $tags = implode(', ', array_map(fn (int $i) => "tag{$i}", range(1, 5)));

        $this->assertSame([], $this->messages(
            $this->lint("type: quote\nhashtags: [{$tags}]"),
            SocialLintIssue::ERROR,
        ));
    }

    public function test_non_https_link_is_an_error(): void
    {
        $this->assertHasError($this->lint("type: quote\nlink: http://oatllo.com/x"), 'musi być https');
    }

    /**
     * U+2192 nie mieści się w unicode-range subsetu latin naszego woff2, więc
     * podmienia się na font systemowy w ŚRODKU linii. To błąd renderu, nie gust.
     */
    public function test_arrow_glyph_missing_from_the_font_subset_is_an_error(): void
    {
        $issues = $this->lint('type: quote', "## Read this \u{2192} now\n\nBody.");

        $this->assertHasError($issues, 'nie istnieje w subsecie latin');
    }

    public function test_arrow_in_the_caption_is_also_caught(): void
    {
        $issues = $this->lint("type: quote\ncaption: \"Swipe \u{2192} link in bio\"");

        $this->assertHasError($issues, 'nie istnieje w subsecie latin');
    }

    /**
     * Em dash JEST w foncie (U+2000-206F), więc to tylko kwestia stylu domu –
     * warning, nie error. Ta różnica jest celowa.
     */
    public function test_em_dash_is_only_a_style_warning(): void
    {
        $issues = $this->lint('type: quote', "## Clean code \u{2014} always\n\nBody.");

        $this->assertSame([], $this->messages($issues, SocialLintIssue::ERROR));
        $this->assertHasWarning($issues, 'niezgodny ze stylem domu');
    }

    public function test_ascii_arrow_is_fine(): void
    {
        $issues = $this->lint("type: quote\nstatus: ready\ncaption: Swipe -> link in bio.", "## Read this -> now\n\nBody.");

        $this->assertSame([], $issues);
    }

    public function test_overlong_hook_headline_is_a_warning(): void
    {
        $headline = str_repeat('a', 71);

        $issues = $this->lint('type: quote', "## {$headline}\n\nBody.");

        $this->assertSame([], $this->messages($issues, SocialLintIssue::ERROR));
        $this->assertHasWarning($issues, 'Skróć, nie zmniejszaj fontu');
    }

    public function test_missing_hook_headline_is_a_warning(): void
    {
        $issues = $this->lint('type: quote', 'Just a paragraph with no heading.');

        $this->assertHasWarning($issues, 'Hook jest miniaturą w feedzie');
    }

    public function test_overlong_body_text_is_a_warning(): void
    {
        $issues = $this->lint('type: quote', "## Short\n\n" . str_repeat('word ', 60));

        $this->assertHasWarning($issues, 'Jeden slajd = jedna myśl');
    }

    public function test_overlong_code_block_is_a_warning(): void
    {
        $code = implode("\n", array_map(fn (int $i) => "\$line{$i} = {$i};", range(1, 9)));

        $issues = $this->lint('type: quote', "## Code\n\n```php\n{$code}\n```");

        $this->assertHasWarning($issues, 'linii, budżet to 8');
    }

    public function test_too_wide_code_line_is_a_warning(): void
    {
        $issues = $this->lint('type: quote', "## Code\n\n```php\n\$x = '" . str_repeat('y', 60) . "';\n```");

        $this->assertHasWarning($issues, 'wyjedzie poza kanwę');
    }

    public function test_overlong_caption_hook_is_a_warning(): void
    {
        $issues = $this->lint("type: quote\ncaption: \"" . str_repeat('a', 126) . '"');

        $this->assertHasWarning($issues, "ucina na '... more'");
    }

    /**
     * Posty realnie leżące w repo muszą przechodzić lint – inaczej `social:export`
     * odmówi im budowy, a przykłady formatu przestaną być wzorcem.
     */
    public function test_every_post_committed_in_the_repo_passes(): void
    {
        $repository = app(\App\Services\Social\MarkdownSocialPostRepository::class);
        $files = $repository->files();

        $this->assertNotEmpty($files, 'Brak postów w resources/social – przykłady formatu zniknęły.');

        foreach ($files as $path) {
            $issues = $this->linter->lintRaw(file_get_contents($path), pathinfo($path, PATHINFO_FILENAME));
            $errors = $this->messages($issues, SocialLintIssue::ERROR);

            $this->assertSame([], $errors, "Post {$path} ma błędy lintu.");
        }
    }
}
