<?php

namespace Tests\Feature;

use App\Services\Clip\Clip;
use App\Services\Clip\InvalidClip;
use App\Services\Clip\MarkdownClipParser;
use Tests\TestCase;

/**
 * Parsowanie scenariuszy clipów z plików .md.
 *
 * CELOWO bez RefreshDatabase: moduł clip NIE MA TABELI (jak reszta modułu social).
 * Gdyby ten test zaczął wymagać migracji, znaczyłoby to, że ktoś przemycił
 * Eloquenta tam, gdzie mają być zwykłe DTO.
 */
class ClipParsingTest extends TestCase
{
    private MarkdownClipParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new MarkdownClipParser();
    }

    private function make(string $body, string $frontmatter = "title: Test clip\nvoice: narrator_en"): Clip
    {
        return $this->parser->toClip("---\n{$frontmatter}\n---\n\n{$body}", 'fixture-slug');
    }

    public function test_splits_scenes_on_the_scene_marker(): void
    {
        $clip = $this->make(<<<'MD'
            type: title
            narration: First scene narration.
            text: "Hello"

            <!-- scene -->
            type: statement
            narration: Second scene narration.
            text: "World"
            MD);

        $this->assertCount(2, $clip->scenes);
        $this->assertSame('title', $clip->scenes[0]->type);
        $this->assertSame('statement', $clip->scenes[1]->type);
        $this->assertSame('Hello', $clip->scenes[0]->param('text'));
    }

    public function test_leading_scene_marker_is_optional(): void
    {
        $withMarker = $this->make("<!-- scene -->\ntype: title\nnarration: One.\ntext: A");
        $withoutMarker = $this->make("type: title\nnarration: One.\ntext: A");

        $this->assertCount(1, $withMarker->scenes);
        $this->assertCount(1, $withoutMarker->scenes);
        $this->assertSame('title', $withoutMarker->scenes[0]->type);
    }

    public function test_scene_index_and_total_are_set(): void
    {
        $clip = $this->make(<<<'MD'
            type: title
            narration: A.

            <!-- scene -->
            type: statement
            narration: B.

            <!-- scene -->
            type: outro
            narration: C.
            MD);

        $this->assertSame([1, 2, 3], array_map(fn ($s) => $s->index, $clip->scenes));
        $this->assertSame([3, 3, 3], array_map(fn ($s) => $s->total, $clip->scenes));
        $this->assertTrue($clip->scenes[2]->isLast());
        $this->assertFalse($clip->scenes[0]->isLast());
    }

    public function test_narration_and_type_are_pulled_out_of_params(): void
    {
        $scene = $this->make("type: title\nnarration: The voice line.\ntext: Visual")->scenes[0];

        $this->assertSame('The voice line.', $scene->narration);
        $this->assertSame('title', $scene->type);
        $this->assertArrayNotHasKey('type', $scene->params);
        $this->assertArrayNotHasKey('narration', $scene->params);
        $this->assertSame('Visual', $scene->param('text'));
    }

    public function test_type_is_lowercased(): void
    {
        $scene = $this->make("type: Code-Reveal\nnarration: X.\ncode: 'echo 1;'")->scenes[0];

        $this->assertSame('code-reveal', $scene->type);
    }

    public function test_folded_and_literal_narration_blocks_parse(): void
    {
        $clip = $this->make(<<<'MD'
            type: title
            narration: >
              This is a folded
              multi line narration.
            text: A
            MD);

        $this->assertSame('This is a folded multi line narration.', $clip->scenes[0]->narration);
    }

    public function test_code_block_keeps_newlines_and_counts_lines_and_columns(): void
    {
        $scene = $this->make(<<<'MD'
            type: code-reveal
            narration: Look at this.
            code: |
              $users = User::all();

              foreach ($users as $user) {
                  echo $user->name;
              }
            MD)->scenes[0];

        $this->assertStringContainsString("\$users = User::all();", $scene->code());
        $this->assertSame(5, $scene->codeLines());
        $this->assertGreaterThanOrEqual(20, $scene->codeMaxColumns());
    }

    public function test_sfx_string_normalizes_to_a_single_cue(): void
    {
        $scene = $this->make("type: title\nnarration: X.\nsfx: whoosh\ntext: A")->scenes[0];

        $this->assertSame([['name' => 'whoosh', 'at' => 0.0]], $scene->sfx);
        $this->assertArrayNotHasKey('sfx', $scene->params);
    }

    public function test_sfx_list_of_maps_keeps_timing(): void
    {
        $scene = $this->make(<<<'MD'
            type: title
            narration: X.
            text: A
            sfx:
              - name: pop
                at: 0.5
              - whoosh
            MD)->scenes[0];

        $this->assertSame(
            [['name' => 'pop', 'at' => 0.5], ['name' => 'whoosh', 'at' => 0.0]],
            $scene->sfx,
        );
    }

    public function test_frontmatter_maps_to_clip_fields(): void
    {
        $clip = $this->make(
            "type: title\nnarration: X.\ntext: A",
            "slug: my-clip\ntitle: My Clip\ntopic: docker\nvoice: narrator_en\nsource: some-article\nplatforms: [tiktok, shorts]\nhashtags: [php, laravel]",
        );

        $this->assertSame('my-clip', $clip->slug);
        $this->assertSame('My Clip', $clip->title);
        $this->assertSame('docker', $clip->topic);
        $this->assertSame('some-article', $clip->source);
        $this->assertSame(['tiktok', 'shorts'], $clip->platforms);
        $this->assertSame(['php', 'laravel'], $clip->hashtags);
    }

    public function test_music_none_becomes_null(): void
    {
        $withNone = $this->make("type: title\nnarration: X.\ntext: A", "title: T\nmusic: none");
        $withValue = $this->make("type: title\nnarration: X.\ntext: A", "title: T\nmusic: chill");

        $this->assertNull($withNone->music);
        $this->assertSame('chill', $withValue->music);
    }

    public function test_slug_falls_back_to_filename(): void
    {
        $clip = $this->parser->toClip("---\ntitle: T\n---\n\ntype: title\nnarration: X.", 'from-filename');

        $this->assertSame('from-filename', $clip->slug);
    }

    public function test_voice_defaults_from_config(): void
    {
        config(['clip.default_voice' => 'narrator_en']);

        $clip = $this->make("type: title\nnarration: X.\ntext: A", 'title: T');

        $this->assertSame('narrator_en', $clip->voice);
    }

    public function test_total_narration_words_sums_scenes(): void
    {
        $clip = $this->make(<<<'MD'
            type: title
            narration: One two three.

            <!-- scene -->
            type: outro
            narration: Four five.
            MD);

        $this->assertSame(5, $clip->totalNarrationWords());
    }

    public function test_broken_scene_yaml_throws_invalid_clip(): void
    {
        $this->expectException(InvalidClip::class);

        // Niepoprawne wcięcie mapy -> ParseException Symfony YAML.
        $this->make("type: title\nnarration: X\n  bad: : indent");
    }

    public function test_theme_haystack_prefers_topic(): void
    {
        $withTopic = $this->make("type: title\nnarration: X.", "title: Something about caching\ntopic: redis");
        $withoutTopic = $this->make("type: title\nnarration: X.", 'title: Something about caching');

        $this->assertSame('redis', $withTopic->themeHaystack());
        $this->assertStringContainsString('caching', $withoutTopic->themeHaystack());
    }
}
