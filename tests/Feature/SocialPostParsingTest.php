<?php

namespace Tests\Feature;

use App\Services\Social\InvalidSocialPost;
use App\Services\Social\MarkdownSocialPostParser;
use App\Services\Social\MarkdownSocialPostRepository;
use App\Services\Social\SocialPost;
use App\Services\Social\SocialPostType;
use App\Services\Social\SocialSlide;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Parsowanie postów social media z plików .md.
 *
 * CELOWO bez RefreshDatabase: moduł social NIE MA TABELI i nigdy nie dotyka
 * bazy. Gdyby ten test zaczął wymagać migracji, znaczyłoby to, że ktoś
 * przemycił Eloquenta tam, gdzie mają być zwykłe DTO.
 */
class SocialPostParsingTest extends TestCase
{
    private string $dir;

    private MarkdownSocialPostParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/social-' . uniqid());
        File::ensureDirectoryExists($this->dir);
        config(['social.path' => $this->dir]);

        $this->parser = new MarkdownSocialPostParser();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    private function makePost(string $body, array $overrides = []): SocialPost
    {
        $fm = array_merge([
            'type'   => 'carousel',
            'title'  => 'Test post',
            'status' => 'draft',
        ], $overrides);

        $yaml = '';
        foreach ($fm as $key => $value) {
            $yaml .= $key . ': ' . (is_array($value) ? '[' . implode(', ', $value) . ']' : $value) . "\n";
        }

        return $this->parser->toPost("---\n{$yaml}---\n\n{$body}", 'fixture-slug');
    }

    public function test_splits_slides_on_the_slide_marker(): void
    {
        $post = $this->makePost(<<<'MD'
            ## One

            First.

            <!-- slide -->

            ## Two

            Second.

            <!-- slide -->

            ## Three

            Third.
            MD);

        $this->assertSame(3, $post->slideCount());
        $this->assertSame(['One', 'Two', 'Three'], array_map(fn (SocialSlide $s) => $s->headline, $post->slides));
    }

    /**
     * Regresja uzasadniająca wybór separatora: gdybyśmy ciachali po `---`,
     * ten post rozpadłby się na 3 slajdy zamiast zostać jednym z <hr>.
     */
    public function test_horizontal_rule_inside_a_slide_does_not_split_it(): void
    {
        $post = $this->makePost(<<<'MD'
            ## Only slide

            Before the rule.

            ---

            After the rule.

            <!-- slide -->

            ## Second slide

            Body.
            MD);

        $this->assertSame(2, $post->slideCount());
        $this->assertStringContainsString('<hr', $post->slides[0]->html);
    }

    public function test_slide_marker_accepts_an_explicit_role(): void
    {
        $post = $this->makePost(<<<'MD'
            ## Hook

            A.

            <!-- slide role="cta" -->

            ## Call to action

            B.
            MD);

        $this->assertSame(SocialSlide::ROLE_HOOK, $post->slides[0]->role);
        $this->assertSame(SocialSlide::ROLE_CTA, $post->slides[1]->role);
    }

    public function test_roles_default_from_position(): void
    {
        $post = $this->makePost("## A\n\n1.\n\n<!-- slide -->\n\n## B\n\n2.\n\n<!-- slide -->\n\n## C\n\n3.");

        $this->assertSame(
            [SocialSlide::ROLE_HOOK, SocialSlide::ROLE_BODY, SocialSlide::ROLE_CTA],
            array_map(fn (SocialSlide $s) => $s->role, $post->slides),
        );
    }

    public function test_trailing_marker_does_not_create_an_empty_slide(): void
    {
        $post = $this->makePost("## A\n\nBody.\n\n<!-- slide -->\n\n## B\n\nBody.\n\n<!-- slide -->\n\n");

        $this->assertSame(2, $post->slideCount());
    }

    public function test_first_heading_becomes_the_headline_and_leaves_the_body(): void
    {
        $post = $this->makePost("## The headline\n\nThe body text.");

        $slide = $post->slides[0];
        $this->assertSame('The headline', $slide->headline);
        $this->assertStringContainsString('The body text.', $slide->html);
        $this->assertStringNotContainsString('The headline', $slide->html);
    }

    public function test_slide_without_a_leading_heading_has_no_headline(): void
    {
        $post = $this->makePost("Just a paragraph.\n\n## Not the first line");

        $this->assertNull($post->slides[0]->headline);
    }

    public function test_slide_numbers_are_zero_padded(): void
    {
        $post = $this->makePost("## A\n\n1.\n\n<!-- slide -->\n\n## B\n\n2.");

        $this->assertSame('01/02', $post->slides[0]->number());
        $this->assertSame('02/02', $post->slides[1]->number());
    }

    public function test_slug_falls_back_to_the_filename(): void
    {
        $post = $this->parser->toPost("---\ntype: quote\n---\n\n## Hi\n\nBody.", 'from-filename');

        $this->assertSame('from-filename', $post->slug);
    }

    public function test_frontmatter_slug_wins_over_the_filename(): void
    {
        $post = $this->parser->toPost("---\ntype: quote\nslug: explicit\n---\n\n## Hi\n\nBody.", 'from-filename');

        $this->assertSame('explicit', $post->slug);
    }

    public function test_hashtags_accept_a_yaml_list(): void
    {
        $post = $this->makePost('## A', ['hashtags' => ['laravel', 'php']]);

        $this->assertSame(['laravel', 'php'], $post->hashtags);
    }

    public function test_hashtags_accept_a_string_and_strip_the_hash(): void
    {
        $post = $this->makePost('## A', ['hashtags' => '"#laravel #php, docker"']);

        $this->assertSame(['laravel', 'php', 'docker'], $post->hashtags);
    }

    public function test_publish_at_parses_a_datetime_string(): void
    {
        $post = $this->makePost('## A', ['publish_at' => '"2026-07-20 09:00"']);

        $this->assertSame('2026-07-20 09:00', $post->publishAt?->format('Y-m-d H:i'));
    }

    public function test_publish_at_is_optional(): void
    {
        $this->assertNull($this->makePost('## A')->publishAt);
    }

    public function test_unknown_type_throws(): void
    {
        $this->expectException(InvalidSocialPost::class);
        $this->expectExceptionMessageMatches('/nieznany type/');

        $this->makePost('## A', ['type' => 'tiktok']);
    }

    public function test_missing_type_throws(): void
    {
        $this->expectException(InvalidSocialPost::class);

        $this->parser->toPost("---\ntitle: No type\n---\n\n## A", 'x');
    }

    public function test_unparseable_publish_at_throws(): void
    {
        $this->expectException(InvalidSocialPost::class);
        $this->expectExceptionMessageMatches('/publish_at/');

        $this->makePost('## A', ['publish_at' => '"not a date at all"']);
    }

    public function test_utf8_bom_is_stripped(): void
    {
        $raw = "\xEF\xBB\xBF---\ntype: quote\n---\n\n## Hi\n\nBody.";

        $post = $this->parser->toPost($raw, 'bom');

        $this->assertSame(SocialPostType::Quote, $post->type);
        $this->assertSame('Hi', $post->slides[0]->headline);
    }

    public function test_caption_and_hashtags_are_joined_for_pasting(): void
    {
        $post = $this->makePost('## A', ['caption' => '"Line one."', 'hashtags' => ['laravel', 'php']]);

        $this->assertSame("Line one.\n\n#laravel #php", $post->captionWithHashtags());
    }

    /**
     * `notes` to instrukcja dla CZŁOWIEKA na moment wrzucania (ankieta, naklejka,
     * cluster) i NIE MA PRAWA dokleić się do tekstu, który idzie na Instagrama.
     * `captionWithHashtags()` jest jedyną metodą, której wynik tam trafia –
     * zarówno jako caption.txt, jak i podpis w panelu recenzji.
     */
    public function test_notes_never_leak_into_the_caption(): void
    {
        $post = $this->makePost('## A', [
            'caption'  => '"Line one."',
            'notes'    => '"NATIVE POLL sticker przy wrzucaniu."',
            'hashtags' => ['laravel'],
        ]);

        $this->assertSame('NATIVE POLL sticker przy wrzucaniu.', $post->notes);
        $this->assertTrue($post->hasNotes());
        $this->assertStringNotContainsString('POLL', $post->captionWithHashtags());
        $this->assertSame("Line one.\n\n#laravel", $post->captionWithHashtags());
    }

    public function test_post_without_notes_has_none(): void
    {
        $post = $this->makePost('## A', ['caption' => '"Line one."']);

        $this->assertSame('', $post->notes);
        $this->assertFalse($post->hasNotes());
    }

    public function test_theme_haystack_prefers_an_explicit_topic(): void
    {
        $post = $this->makePost('## A', ['topic' => 'docker', 'title' => 'Something about Laravel']);

        $this->assertSame('docker', $post->themeHaystack());
    }

    public function test_theme_haystack_falls_back_to_title_and_hashtags(): void
    {
        $post = $this->makePost('## A', ['title' => 'Eloquent tips', 'hashtags' => ['laravel']]);

        $haystack = $post->themeHaystack();
        $this->assertStringContainsString('Eloquent tips', $haystack);
        $this->assertStringContainsString('laravel', $haystack);
    }

    public function test_code_blocks_are_extracted_without_the_fence(): void
    {
        $post = $this->makePost("## A\n\n```php\n\$x = 1;\n```");

        $this->assertSame(['$x = 1;'], $post->slides[0]->codeBlocks());
    }

    public function test_plain_text_ignores_code_blocks(): void
    {
        $post = $this->makePost("## A\n\nShort text.\n\n```php\n\$aVeryLongVariableNameThatWouldBlowTheBudget = 1;\n```");

        $this->assertSame('Short text.', $post->slides[0]->plainText());
    }

    public function test_repository_reads_posts_from_disk(): void
    {
        File::put($this->dir . '/alpha.md', "---\ntype: quote\nstatus: ready\n---\n\n## Alpha\n\nBody.");
        File::put($this->dir . '/beta.md', "---\ntype: story\nstatus: draft\n---\n\n## Beta\n\nBody.");
        File::put($this->dir . '/ignored.txt', 'not markdown');

        $repository = new MarkdownSocialPostRepository($this->parser);

        $this->assertSame(2, $repository->all()->count());
        $this->assertSame(['alpha'], $repository->byStatus('ready')->pluck('slug')->all());
        $this->assertSame('Beta', $repository->findBySlug('beta')?->slides[0]->headline);
        $this->assertNull($repository->findBySlug('nope'));
    }

    public function test_repository_slug_cannot_escape_the_directory(): void
    {
        $repository = new MarkdownSocialPostRepository($this->parser);

        $path = $repository->pathForSlug('../../../etc/passwd');

        $this->assertStringStartsWith($this->dir, $path);
        $this->assertStringNotContainsString('..', $path);
    }
}
