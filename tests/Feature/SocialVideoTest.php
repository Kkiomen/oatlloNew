<?php

namespace Tests\Feature;

use App\Services\Social\EmbeddedFontProvider;
use App\Services\Social\MarkdownSocialPostParser;
use App\Services\Social\SocialImageService;
use App\Services\Social\SocialPost;
use App\Services\Social\SocialStyleResolver;
use App\Services\Social\Video\ReelStager;
use App\Services\Theme\TechThemeResolver;
use App\Services\Social\SocialPostType;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Wsad dla Remotiona: HTML slajdów + reel.json.
 *
 * Testujemy WYŁĄCZNIE stronę PHP – to ona podejmuje wszystkie decyzje o treści
 * (ile slajdów, jak długo, jaki akcent). Renderu nie ruszamy: wymagałby Node'a
 * i kilku minut na Reela, a i tak sprawdzałby Remotiona, nie nas.
 */
class SocialVideoTest extends TestCase
{
    private string $projectDir;

    private ReelStager $stager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectDir = storage_path('framework/testing/social-video-' . uniqid());
        config(['social.video.project_path' => $this->projectDir]);

        $this->stager = new ReelStager(
            new SocialImageService(new TechThemeResolver(), new EmbeddedFontProvider(), new SocialStyleResolver()),
            new SocialStyleResolver(),
        );
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->projectDir);

        parent::tearDown();
    }

    private function makePost(string $body, string $type = 'carousel'): SocialPost
    {
        return (new MarkdownSocialPostParser())->toPost(
            "---\ntype: {$type}\nslug: demo\ntopic: laravel\nstatus: ready\n"
            . "hashtags: [laravel]\ncaption: The caption.\n---\n\n{$body}",
            'demo',
        );
    }

    private function carousel(int $slides = 3): SocialPost
    {
        return $this->makePost(implode("\n\n<!-- slide -->\n\n", array_map(
            fn (int $i) => "## Slide {$i}\n\nBody {$i}.",
            range(1, $slides),
        )));
    }

    private function manifest(SocialPost $post): array
    {
        $dir = $this->stager->stage($post);

        return json_decode(File::get($dir . '/reel.json'), true);
    }

    public function test_stages_one_html_document_per_slide_plus_manifest(): void
    {
        $dir = $this->stager->stage($this->carousel(3));

        foreach (['01.html', '02.html', '03.html', 'reel.json'] as $name) {
            $this->assertFileExists($dir . '/' . $name);
        }
    }

    /**
     * Slajdy MUSZĄ być tymi samymi dokumentami, co idą na PNG. Gdyby wideo miało
     * własny render, kafelek i Reel rozjechałyby się przy pierwszej zmianie skórki.
     */
    public function test_staged_html_is_the_same_document_that_goes_to_png(): void
    {
        $post = $this->carousel(2);

        $dir = $this->stager->stage($post);
        $expected = (new SocialImageService(new TechThemeResolver(), new EmbeddedFontProvider(), new SocialStyleResolver()))
            ->renderPost($post);

        $this->assertSame($expected[0], File::get($dir . '/01.html'));
        $this->assertSame($expected[1], File::get($dir . '/02.html'));
    }

    /**
     * Skrócenie karuzeli nie może zostawić sierot: manifest wylicza slajdy z
     * osobna, więc stary 05.html po prostu leżałby na dysku i mylił w Studiu.
     */
    public function test_restaging_a_shorter_post_removes_orphan_slides(): void
    {
        $this->stager->stage($this->carousel(5));
        $dir = $this->stager->stage($this->carousel(2));

        $this->assertFileExists($dir . '/02.html');
        $this->assertFileDoesNotExist($dir . '/03.html');
        $this->assertFileDoesNotExist($dir . '/05.html');
    }

    /**
     * Długość slajdu liczy się z objętości treści. Stała długość albo urywałaby
     * kod, albo trzymała pusty hook w nieskończoność.
     */
    public function test_slide_with_code_lasts_longer_than_a_bare_slide(): void
    {
        $bare = $this->manifest($this->makePost(
            "## Hook\n\nShort.\n\n<!-- slide -->\n\n## Two\n\nAlso short."
        ));

        $withCode = $this->manifest($this->makePost(
            "## Hook\n\nShort.\n\n<!-- slide -->\n\n## Two\n\n```php\n\$a = 1;\n\$b = 2;\n\$c = 3;\n\$d = 4;\n```"
        ));

        $this->assertGreaterThan(
            $bare['slides'][1]['durationInFrames'],
            $withCode['slides'][1]['durationInFrames'],
        );
    }

    /**
     * Bezpieczniki długości: poniżej minimum nikt nie zdąży przeczytać, powyżej
     * maksimum widz ucieka.
     */
    public function test_slide_duration_is_clamped_to_configured_bounds(): void
    {
        config(['social.video.timing.min' => 90, 'social.video.timing.max' => 100]);

        $manifest = $this->manifest($this->makePost(
            "## Hook\n\n.\n\n<!-- slide -->\n\n## Long\n\n" . str_repeat('word ', 400)
        ));

        foreach ($manifest['slides'] as $slide) {
            $this->assertGreaterThanOrEqual(90, $slide['durationInFrames']);
            $this->assertLessThanOrEqual(100, $slide['durationInFrames']);
        }
    }

    /**
     * `bodyChildren` zasila stagger wjazdu treści w Remotionie. Liczone jest z
     * WYRENDEROWANEGO dokumentu, więc musi widzieć realne dzieci `.body`.
     */
    public function test_body_children_counts_top_level_elements_of_the_body(): void
    {
        $manifest = $this->manifest($this->makePost(
            "## Hook\n\nOne paragraph.\n\n<!-- slide -->\n\n## Two\n\n```php\n\$a = 1;\n```\n\nProse after code."
        ));

        $this->assertSame(1, $manifest['slides'][0]['bodyChildren']);
        $this->assertSame(2, $manifest['slides'][1]['bodyChildren']);
    }

    /**
     * Kanwa jedzie w manifeście, bo od niej zależy kadrowanie: story jest już
     * natywnie 9:16 i wypełnia Reela, reszta ląduje na podkładzie.
     */
    public function test_manifest_carries_canvas_so_remotion_knows_whether_to_letterbox(): void
    {
        $carousel = $this->manifest($this->carousel(2));
        $story = $this->manifest($this->makePost("## Story\n\nBody.", 'story'));

        $this->assertSame(1350, $carousel['canvas']['height']);
        $this->assertSame(SocialPostType::Story->height(), $story['canvas']['height']);
        $this->assertSame(1920, $story['canvas']['height']);
    }

    /**
     * Akcent musi być tym samym, którym maluje się kafelek – Reel i post o tym
     * samym temacie nie mogą mieć innego koloru.
     */
    public function test_manifest_accent_matches_the_post_theme(): void
    {
        $post = $this->carousel(2);

        $expected = (new SocialImageService(new TechThemeResolver(), new EmbeddedFontProvider(), new SocialStyleResolver()))
            ->theme($post)['accent'];

        $this->assertSame($expected, $this->manifest($post)['accent']);
    }
}
