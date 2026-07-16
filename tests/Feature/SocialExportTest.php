<?php

namespace Tests\Feature;

use App\Services\Social\EmbeddedFontProvider;
use App\Services\Social\Export\SocialExporter;
use App\Services\Social\MarkdownSocialPostParser;
use App\Services\Social\Rasterizer\NullRasterizer;
use App\Services\Social\Rasterizer\SocialRasterizer;
use App\Services\Social\SocialImageService;
use App\Services\Social\SocialPost;
use App\Services\Social\SocialStyleResolver;
use App\Services\Theme\TechThemeResolver;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Eksport do folderu gotowego na ręczny upload.
 *
 * NullRasterizer zamiast prawdziwej przeglądarki – testujemy logikę eksportu,
 * a nie to, czy na maszynie CI jest Chrome.
 */
class SocialExportTest extends TestCase
{
    private string $postsDir;

    private string $outDir;

    private SocialExporter $exporter;

    protected function setUp(): void
    {
        parent::setUp();

        $id = uniqid();
        $this->postsDir = storage_path("framework/testing/social-posts-{$id}");
        $this->outDir = storage_path("framework/testing/social-out-{$id}");

        File::ensureDirectoryExists($this->postsDir);
        config(['social.path' => $this->postsDir]);

        $this->exporter = new SocialExporter(
            new SocialImageService(new TechThemeResolver(), new EmbeddedFontProvider(), new SocialStyleResolver()),
            new NullRasterizer(),
        );
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->postsDir);
        File::deleteDirectory($this->outDir);

        parent::tearDown();
    }

    private function carousel(int $slides = 3): SocialPost
    {
        $body = implode("\n\n<!-- slide -->\n\n", array_map(
            fn (int $i) => "## Slide {$i}\n\nBody {$i}.",
            range(1, $slides),
        ));

        return (new MarkdownSocialPostParser())->toPost(
            "---\ntype: carousel\nslug: demo\ntopic: laravel\nstatus: ready\n"
            . "hashtags: [laravel, php]\ncaption: The caption.\n---\n\n{$body}",
            'demo',
        );
    }

    public function test_html_only_export_writes_one_document_per_slide(): void
    {
        $result = $this->exporter->export($this->carousel(3), $this->outDir, htmlOnly: true);

        $this->assertSame(3, $result->slideCount());
        $this->assertTrue($result->htmlOnly);

        foreach (['01.html', '02.html', '03.html'] as $name) {
            $this->assertFileExists($this->outDir . '/demo/' . $name);
        }
    }

    /**
     * Kolejność slajdów na Instagramie = kolejność nazw plików, więc numer musi
     * być zerowany. Bez tego 10.png wylądowałoby przed 2.png.
     */
    public function test_slide_files_are_zero_padded_and_in_order(): void
    {
        $result = $this->exporter->export($this->carousel(10), $this->outDir, htmlOnly: true);

        $names = array_map('basename', $result->imagePaths);

        $this->assertSame('01.html', $names[0]);
        $this->assertSame('10.html', $names[9]);
        $this->assertSame($names, array_values(collect($names)->sort()->all()), 'Kolejność plików musi być rosnąca.');
    }

    public function test_caption_file_contains_the_caption_and_hashtags(): void
    {
        $result = $this->exporter->export($this->carousel(), $this->outDir, htmlOnly: true);

        $caption = File::get($result->captionPath);

        $this->assertStringContainsString('The caption.', $caption);
        $this->assertStringContainsString('#laravel #php', $caption);
    }

    /**
     * caption.txt to plik "zaznacz wszystko i wklej" – wszystko, co w nim jest,
     * człowiek wysyła w świat. Notatka produkcyjna trafia obok, do post.json.
     */
    public function test_caption_file_never_contains_the_authors_notes(): void
    {
        $post = (new MarkdownSocialPostParser())->toPost(
            "---\ntype: story\nslug: noted\nstatus: ready\nformats: [post]\n"
            . "caption: The caption.\nnotes: NATIVE POLL przy wrzucaniu.\n---\n\n## A\n\nBody.",
            'noted',
        );

        $result = $this->exporter->export($post, $this->outDir, htmlOnly: true);

        $this->assertStringNotContainsString('NATIVE POLL', File::get($result->captionPath));

        $manifest = json_decode(File::get($result->manifestPath), true);

        $this->assertSame('NATIVE POLL przy wrzucaniu.', $manifest['notes']);
    }

    /**
     * NIE JEST TEORETYCZNY. `deleteDirectory` na katalogu eksportu skasowało trzy
     * gotowe reele w trakcie jednego `social:export --status=ready` — po cichu,
     * bo eksport meldował sukces. PNG powstaje w sekundy, reel w minuty, a leżą
     * obok siebie.
     */
    public function test_export_does_not_delete_the_rendered_reel(): void
    {
        $dir = $this->outDir . '/demo';
        File::ensureDirectoryExists($dir);
        File::put($dir . '/reel.mp4', 'drogie-minuty-renderu');

        $this->exporter->export($this->carousel(3), $this->outDir, htmlOnly: true);

        $this->assertFileExists($dir . '/reel.mp4');
        $this->assertSame('drogie-minuty-renderu', File::get($dir . '/reel.mp4'));
    }

    /**
     * Ale sieroty po skróconej karuzeli dalej muszą znikać — inaczej stare 05.png
     * pojechałoby na Instagrama jako szósty slajd.
     */
    public function test_export_still_removes_orphan_slides(): void
    {
        $this->exporter->export($this->carousel(5), $this->outDir, htmlOnly: true);
        $this->assertFileExists($this->outDir . '/demo/05.html');

        $this->exporter->export($this->carousel(3), $this->outDir, htmlOnly: true);

        $this->assertFileDoesNotExist($this->outDir . '/demo/05.html');
        $this->assertFileExists($this->outDir . '/demo/03.html');
    }

    public function test_manifest_describes_the_post(): void
    {
        $result = $this->exporter->export($this->carousel(3), $this->outDir, htmlOnly: true);

        $manifest = json_decode(File::get($result->manifestPath), true);

        $this->assertSame('demo', $manifest['slug']);
        $this->assertSame('carousel', $manifest['type']);
        $this->assertSame(['width' => 1080, 'height' => 1350], $manifest['canvas']);
        $this->assertSame(3, $manifest['slides']);
        $this->assertSame(['01.html', '02.html', '03.html'], $manifest['files']);
        $this->assertTrue($manifest['html_only']);
    }

    /**
     * Po skróceniu karuzeli w folderze nie może zostać sierota po starym slajdzie
     * – ktoś by ją wrzucił razem z resztą.
     */
    public function test_reexport_removes_slides_that_no_longer_exist(): void
    {
        $this->exporter->export($this->carousel(5), $this->outDir, htmlOnly: true);
        $this->assertFileExists($this->outDir . '/demo/05.html');

        $this->exporter->export($this->carousel(2), $this->outDir, htmlOnly: true);

        $this->assertFileExists($this->outDir . '/demo/02.html');
        $this->assertFileDoesNotExist($this->outDir . '/demo/05.html');
    }

    /**
     * Folder eksportu ma zawierać WYŁĄCZNIE to, co człowiek wrzuca na Instagram.
     * Pliki .html z pełnego eksportu idą do katalogu tymczasowego i znikają.
     */
    public function test_full_export_leaves_no_html_or_temp_files_in_the_folder(): void
    {
        $this->exporter->export($this->carousel(2), $this->outDir, htmlOnly: false);

        $entries = array_map('basename', array_merge(
            File::files($this->outDir . '/demo'),
            File::directories($this->outDir . '/demo'),
        ));

        sort($entries);

        // NullRasterizer nie tworzy PNG, więc zostają tylko caption + manifest.
        $this->assertSame(['caption.txt', 'post.json'], $entries);
    }

    public function test_export_writes_nothing_outside_the_target_directory(): void
    {
        $this->exporter->export($this->carousel(), $this->outDir, htmlOnly: true);

        $this->assertSame(['demo'], array_map('basename', File::directories($this->outDir)));
    }

    public function test_command_exports_only_ready_posts_by_default(): void
    {
        File::put($this->postsDir . '/ready-one.md', "---\ntype: quote\nstatus: ready\ncaption: Hi.\n---\n\n## A\n\nBody.");
        File::put($this->postsDir . '/draft-one.md', "---\ntype: quote\nstatus: draft\ncaption: Hi.\n---\n\n## B\n\nBody.");

        $this->app->bind(SocialRasterizer::class, NullRasterizer::class);

        $this->artisan('social:export', ['--html-only' => true, '--out' => $this->outDir])
            ->assertSuccessful();

        $this->assertDirectoryExists($this->outDir . '/ready-one');
        $this->assertDirectoryDoesNotExist($this->outDir . '/draft-one');
    }

    /**
     * Lint jest BRAMKĄ eksportu: grafika z błędem zbudowałaby się bez protestu
     * i zobaczylibyśmy problem dopiero na Instagramie.
     */
    public function test_command_refuses_to_export_a_post_with_lint_errors(): void
    {
        // Karuzela z jednym slajdem – błąd lintu.
        File::put($this->postsDir . '/broken.md', "---\ntype: carousel\nstatus: ready\ncaption: Hi.\n---\n\n## Only\n\nBody.");

        $this->artisan('social:export', ['--html-only' => true, '--out' => $this->outDir])
            ->assertFailed();

        $this->assertDirectoryDoesNotExist($this->outDir . '/broken');
    }

    public function test_command_can_skip_the_lint_gate_for_debugging(): void
    {
        File::put($this->postsDir . '/broken.md', "---\ntype: carousel\nstatus: ready\ncaption: Hi.\n---\n\n## Only\n\nBody.");

        $this->artisan('social:export', ['--html-only' => true, '--skip-lint' => true, '--out' => $this->outDir])
            ->assertSuccessful();

        $this->assertDirectoryExists($this->outDir . '/broken');
    }
}
