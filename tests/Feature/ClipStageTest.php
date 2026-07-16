<?php

namespace Tests\Feature;

use App\Services\Clip\ClipStager;
use App\Services\Clip\MarkdownClipParser;
use App\Services\Clip\Tts\ClipNarrator;
use App\Services\Clip\Tts\MockTtsProvider;
use App\Services\Theme\TechThemeResolver;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Budowa wsadu Remotiona (clip.json + audio).
 *
 * Sedno: długość sceny MUSI wynikać z długości audio (timing z narracji, nie ze
 * stałej), a katalog wsadu musi się czyścić (sieroty mylą przy podglądzie).
 * Bez RefreshDatabase — moduł clip nie ma tabeli.
 */
class ClipStageTest extends TestCase
{
    private string $projectDir;

    private string $cacheDir;

    private ClipStager $stager;

    private MarkdownClipParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectDir = storage_path('framework/testing/clip-project-' . uniqid());
        $this->cacheDir = storage_path('framework/testing/clip-audio-' . uniqid());

        config([
            'clip.video.project_path' => $this->projectDir,
            'clip.tts.cache_path'     => $this->cacheDir,
            'clip.tts.words_per_min'  => 150,
            'clip.fps'                => 30,
            'clip.timing'             => ['min' => 30, 'max' => 360, 'gap' => 0, 'lead' => 6, 'tail' => 9],
        ]);

        $this->parser = new MarkdownClipParser();
        $this->stager = new ClipStager(new ClipNarrator(new MockTtsProvider()), new TechThemeResolver());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->projectDir);
        File::deleteDirectory($this->cacheDir);

        parent::tearDown();
    }

    private function stage(string $body, string $frontmatter = "title: Test\nvoice: narrator_en"): array
    {
        $clip = $this->parser->toClip("---\n{$frontmatter}\n---\n\n{$body}", 'test-clip');
        $dir = $this->stager->stage($clip);

        return json_decode(File::get($dir . '/clip.json'), true);
    }

    public function test_manifest_has_the_expected_shape(): void
    {
        $manifest = $this->stage(
            "type: title\nnarration: Hello world.\ntext: Hi\n\n<!-- scene -->\ntype: outro\nnarration: Bye now.\ncta: oatllo.com",
            "title: Docker deep dive\nvoice: narrator_en\ntopic: docker",
        );

        $this->assertSame('test-clip', $manifest['slug']);
        $this->assertSame(30, $manifest['fps']);
        $this->assertSame(1080, $manifest['canvas']['width']);
        $this->assertSame(1920, $manifest['canvas']['height']);
        $this->assertSame('docker', $manifest['theme']['logo']);
        $this->assertStringStartsWith('#', $manifest['theme']['accent']);
        $this->assertCount(2, $manifest['scenes']);
    }

    public function test_scene_duration_comes_from_narration_length(): void
    {
        // 15 słów / 150 wpm = 6.0 s => 180 klatek + lead 6 + tail 9 = 195.
        $manifest = $this->stage(
            "type: statement\nnarration: one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen\ntext: X\n\n<!-- scene -->\ntype: outro\nnarration: bye.\ncta: a",
        );

        $this->assertSame(195, $manifest['scenes'][0]['durationInFrames']);
    }

    public function test_long_narration_is_not_truncated_by_the_max_cap(): void
    {
        // 100 słów / 150 wpm = 40 s => 1200 klatek, dużo powyżej timing.max (360).
        // Stager NIE może tego uciąć — obciąłby narrację. max pilnuje tylko lint.
        $words = implode(' ', array_fill(0, 100, 'word'));
        $manifest = $this->stage(
            "type: statement\nnarration: {$words}\ntext: X\n\n<!-- scene -->\ntype: outro\nnarration: bye.\ncta: a",
        );

        $this->assertGreaterThan(360, $manifest['scenes'][0]['durationInFrames']);
    }

    public function test_params_pass_through_to_manifest(): void
    {
        $manifest = $this->stage(
            "type: bullets\nnarration: Look at these.\nitems:\n  - First\n  - Second\n\n<!-- scene -->\ntype: outro\nnarration: bye.\ncta: a",
        );

        $this->assertSame(['First', 'Second'], $manifest['scenes'][0]['params']['items']);
    }

    public function test_audio_files_are_copied_next_to_the_manifest(): void
    {
        $this->stage(
            "type: title\nnarration: One.\ntext: X\n\n<!-- scene -->\ntype: outro\nnarration: Two.\ncta: a",
        );

        $audioDir = $this->stager->clipPath('test-clip') . '/audio';

        $this->assertFileExists($audioDir . '/01.wav');
        $this->assertFileExists($audioDir . '/02.wav');
    }

    public function test_narration_block_references_relative_audio_and_carries_words(): void
    {
        $manifest = $this->stage(
            "type: title\nnarration: Alpha beta gamma.\ntext: X\n\n<!-- scene -->\ntype: outro\nnarration: bye.\ncta: a",
        );

        $narration = $manifest['scenes'][0]['narration'];
        $this->assertSame('audio/01.wav', $narration['audio']);
        $this->assertSame(6, $narration['leadIn']);
        $this->assertCount(3, $narration['words']);
        $this->assertSame('Alpha', $narration['words'][0]['text']);
    }

    public function test_staging_clears_orphans_from_a_previous_longer_run(): void
    {
        // Najpierw 3 sceny -> 03.wav istnieje.
        $this->stage(
            "type: title\nnarration: A.\ntext: X\n\n<!-- scene -->\ntype: statement\nnarration: B.\ntext: Y\n\n<!-- scene -->\ntype: outro\nnarration: C.\ncta: a",
        );
        $audioDir = $this->stager->clipPath('test-clip') . '/audio';
        $this->assertFileExists($audioDir . '/03.wav');

        // Potem 2 sceny -> 03.wav musi zniknąć (katalog czyszczony).
        $this->stage(
            "type: title\nnarration: A.\ntext: X\n\n<!-- scene -->\ntype: outro\nnarration: C.\ncta: a",
        );
        $this->assertFileDoesNotExist($audioDir . '/03.wav');
        $this->assertFileExists($audioDir . '/02.wav');
    }

    public function test_no_tech_topic_still_gets_an_accent_and_no_logo(): void
    {
        // Slug wchodzi do haystacka motywu, więc dobieramy taki, który NIC nie
        // trafia (np. 'clip' łapie devops przez podciąg 'cli'). Tu slug jawny.
        $manifest = $this->stage(
            "type: title\nnarration: Something generic.\ntext: X\n\n<!-- scene -->\ntype: outro\nnarration: bye.\ncta: a",
            "slug: mindset-focus\ntitle: Mindset and focus habits\nvoice: narrator_en",
        );

        $this->assertNull($manifest['theme']['logo']);
        $this->assertStringStartsWith('#', $manifest['theme']['accent']);
    }
}
