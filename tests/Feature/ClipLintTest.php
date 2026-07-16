<?php

namespace Tests\Feature;

use App\Services\Clip\ClipLinter;
use App\Services\Clip\ClipLintIssue;
use App\Services\Clip\MarkdownClipParser;
use Tests\TestCase;

/**
 * Walidacja scenariuszy clipów.
 *
 * Podział ERROR/WARNING jest istotą tej bramki: ERROR = clip nie wyrenderuje
 * się poprawnie (nieznany typ sceny, brak narracji, glif spoza fontu); WARNING
 * = wyrenderuje się, ale brzydko (kod za kadr, za długa narracja). Testy pilnują,
 * żeby ktoś nie przesunął czegoś groźnego do warningów.
 *
 * Bez RefreshDatabase — moduł clip nie ma tabeli.
 */
class ClipLintTest extends TestCase
{
    private ClipLinter $linter;

    protected function setUp(): void
    {
        parent::setUp();

        // Ustawiamy znany zestaw głosów/SFX, żeby testy nie zależały od .env.
        config([
            'clip.voices' => ['narrator_en' => 'voice-id'],
            'clip.sfx'    => ['whoosh' => 'whoosh.mp3'],
            'clip.music'  => ['chill' => 'chill.mp3'],
        ]);

        $this->linter = new ClipLinter(new MarkdownClipParser());
    }

    /**
     * @return list<ClipLintIssue>
     */
    private function lint(string $body, string $frontmatter = "title: T\nvoice: narrator_en"): array
    {
        return $this->linter->lintRaw("---\n{$frontmatter}\n---\n\n{$body}", 'fixture');
    }

    /**
     * @param  list<ClipLintIssue>  $issues
     * @return list<string>
     */
    private function messages(array $issues, string $level): array
    {
        return array_values(array_map(
            fn (ClipLintIssue $i) => $i->message,
            array_filter($issues, fn (ClipLintIssue $i) => $i->level === $level),
        ));
    }

    private function twoValidScenes(string $extra = ''): string
    {
        return "type: title\nnarration: First scene.\ntext: A\n\n<!-- scene -->\ntype: outro\nnarration: Second scene.\ncta: oatllo.com{$extra}";
    }

    public function test_valid_clip_has_no_issues(): void
    {
        $this->assertSame([], $this->lint($this->twoValidScenes()));
    }

    public function test_unknown_scene_type_is_error(): void
    {
        $body = "type: teleport\nnarration: X.\n\n<!-- scene -->\ntype: outro\nnarration: Y.\ncta: a";

        $errors = $this->messages($this->lint($body), ClipLintIssue::ERROR);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('teleport', implode(' ', $errors));
    }

    public function test_missing_narration_is_error(): void
    {
        $body = "type: title\ntext: A\n\n<!-- scene -->\ntype: outro\nnarration: Y.\ncta: a";

        $errors = $this->messages($this->lint($body), ClipLintIssue::ERROR);

        $this->assertStringContainsString('narration', implode(' ', $errors));
    }

    public function test_missing_type_is_error(): void
    {
        $body = "narration: no type here.\n\n<!-- scene -->\ntype: outro\nnarration: Y.\ncta: a";

        $errors = $this->messages($this->lint($body), ClipLintIssue::ERROR);

        $this->assertStringContainsString('brak `type`', implode(' ', $errors));
    }

    public function test_too_few_scenes_is_error(): void
    {
        $errors = $this->messages($this->lint("type: title\nnarration: Only one.\ntext: A"), ClipLintIssue::ERROR);

        $this->assertStringContainsString('Za mało scen', implode(' ', $errors));
    }

    public function test_unknown_frontmatter_key_is_error(): void
    {
        $errors = $this->messages(
            $this->lint($this->twoValidScenes(), "title: T\nvoice: narrator_en\nwhoops: 1"),
            ClipLintIssue::ERROR,
        );

        $this->assertStringContainsString('whoops', implode(' ', $errors));
    }

    public function test_arrow_glyph_in_narration_is_error(): void
    {
        $body = "type: title\nnarration: Go from A → B now.\ntext: A\n\n<!-- scene -->\ntype: outro\nnarration: Y.\ncta: a";

        $errors = $this->messages($this->lint($body), ClipLintIssue::ERROR);

        $this->assertStringContainsString('spoza fontu', implode(' ', $errors));
    }

    public function test_arrow_glyph_in_visible_text_is_error(): void
    {
        $body = "type: title\nnarration: Fine.\ntext: \"A → B\"\n\n<!-- scene -->\ntype: outro\nnarration: Y.\ncta: a";

        $errors = $this->messages($this->lint($body), ClipLintIssue::ERROR);

        $this->assertStringContainsString('spoza fontu', implode(' ', $errors));
    }

    public function test_em_dash_is_only_a_warning(): void
    {
        $body = "type: title\nnarration: This — that.\ntext: A\n\n<!-- scene -->\ntype: outro\nnarration: Y.\ncta: a";

        $issues = $this->lint($body);

        $this->assertSame([], $this->messages($issues, ClipLintIssue::ERROR));
        $this->assertNotEmpty($this->messages($issues, ClipLintIssue::WARNING));
    }

    public function test_long_narration_is_a_warning_not_error(): void
    {
        config(['clip.limits.narration_max_words' => 5]);

        $body = "type: title\nnarration: One two three four five six seven eight.\ntext: A\n\n<!-- scene -->\ntype: outro\nnarration: Y.\ncta: a";

        $issues = $this->lint($body);

        $this->assertSame([], $this->messages($issues, ClipLintIssue::ERROR));
        $this->assertStringContainsString('scena będzie długa', implode(' ', $this->messages($issues, ClipLintIssue::WARNING)));
    }

    public function test_code_over_column_budget_is_a_warning(): void
    {
        config(['clip.limits.code_cols_max' => 10]);

        $body = "type: code-reveal\nnarration: Look.\ncode: 'this_is_a_very_long_line_of_code = true;'\n\n<!-- scene -->\ntype: outro\nnarration: Y.\ncta: a";

        $issues = $this->lint($body);

        $this->assertSame([], $this->messages($issues, ClipLintIssue::ERROR));
        $this->assertStringContainsString('kolumn', implode(' ', $this->messages($issues, ClipLintIssue::WARNING)));
    }

    public function test_missing_required_visual_param_is_a_warning(): void
    {
        // title bez text -> pusta kanwa pod narracją (WARNING, audio gra).
        $body = "type: title\nnarration: Has voice but no visual.\n\n<!-- scene -->\ntype: outro\nnarration: Y.\ncta: a";

        $issues = $this->lint($body);

        $this->assertSame([], $this->messages($issues, ClipLintIssue::ERROR));
        $this->assertStringContainsString('pusta kanwa', implode(' ', $this->messages($issues, ClipLintIssue::WARNING)));
    }

    public function test_unknown_voice_is_a_warning(): void
    {
        $issues = $this->lint($this->twoValidScenes(), "title: T\nvoice: ghost");

        $this->assertSame([], $this->messages($issues, ClipLintIssue::ERROR));
        $this->assertStringContainsString("Głos 'ghost'", implode(' ', $this->messages($issues, ClipLintIssue::WARNING)));
    }

    public function test_unknown_sfx_is_a_warning(): void
    {
        $body = "type: title\nnarration: Boom.\ntext: A\nsfx: nonexistent\n\n<!-- scene -->\ntype: outro\nnarration: Y.\ncta: a";

        $issues = $this->lint($body);

        $this->assertSame([], $this->messages($issues, ClipLintIssue::ERROR));
        $this->assertStringContainsString("SFX 'nonexistent'", implode(' ', $this->messages($issues, ClipLintIssue::WARNING)));
    }

    public function test_the_shipped_example_scenario_lints_clean(): void
    {
        $raw = file_get_contents(resource_path('clips/eloquent-n1-explainer.md'));

        // Przy realnym config/clip.php (pusty voices/sfx) mogą być warningi o
        // niezdefiniowanym głosie — ale ZERO błędów. To jest bramka renderu.
        $issues = $this->linter->lintRaw($raw, 'eloquent-n1-explainer');

        $this->assertSame([], $this->messages($issues, ClipLintIssue::ERROR));
    }
}
