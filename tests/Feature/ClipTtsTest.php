<?php

namespace Tests\Feature;

use App\Services\Clip\Tts\ClipNarrator;
use App\Services\Clip\Tts\MockTtsProvider;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Warstwa TTS: mock (cisza) + cache po odcisku treści.
 *
 * Sedno: pipeline musi produkować audio o POPRAWNEJ DŁUGOŚCI bez klucza
 * ElevenLabs (inaczej faza 4 nie da się zrenderować), a cache nie może wołać
 * providera dwa razy dla tej samej narracji (inaczej ElevenLabs kosztowałby
 * przy każdym renderze). Bez RefreshDatabase — moduł clip nie ma tabeli.
 */
class ClipTtsTest extends TestCase
{
    private string $cacheDir;

    private ClipNarrator $narrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = storage_path('framework/testing/clip-audio-' . uniqid());
        config([
            'clip.tts.cache_path'    => $this->cacheDir,
            'clip.tts.words_per_min' => 150,
        ]);

        $this->narrator = new ClipNarrator(new MockTtsProvider());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->cacheDir);

        parent::tearDown();
    }

    public function test_mock_estimates_duration_from_word_count(): void
    {
        // 15 słów przy 150 wpm = 6.0 s.
        $narration = $this->narrator->narrate('one two three four five six seven eight nine ten eleven twelve thirteen fourteen fifteen', 'v');

        $this->assertEqualsWithDelta(6.0, $narration->duration, 0.001);
    }

    public function test_mock_produces_a_valid_wav_of_matching_length(): void
    {
        $narration = $this->narrator->narrate('hello world from oatllo', 'v');

        $this->assertSame('wav', $narration->ext);
        $this->assertFileExists($narration->audioPath);

        $bytes = File::get($narration->audioPath);

        // Nagłówek WAV: RIFF ... WAVE fmt  ... data.
        $this->assertSame('RIFF', substr($bytes, 0, 4));
        $this->assertSame('WAVE', substr($bytes, 8, 4));
        $this->assertSame('fmt ', substr($bytes, 12, 4));
        $this->assertSame('data', substr($bytes, 36, 4));

        // Rozmiar danych = round(duration * 44100) * 2, plik = 44 + dataSize.
        $expectedData = (int) round($narration->duration * 44100) * 2;
        $this->assertSame(44 + $expectedData, strlen($bytes));
    }

    public function test_word_timings_cover_the_whole_duration_in_order(): void
    {
        $narration = $this->narrator->narrate('alpha beta gamma delta', 'v');

        $words = $narration->words;

        $this->assertCount(4, $words);
        $this->assertSame('alpha', $words[0]['text']);
        $this->assertEqualsWithDelta(0.0, $words[0]['start'], 0.001);
        $this->assertEqualsWithDelta($narration->duration, $words[count($words) - 1]['end'], 0.05);

        // Monotoniczne, bez nakładania.
        $prevEnd = 0.0;
        foreach ($words as $w) {
            $this->assertGreaterThanOrEqual($prevEnd - 0.001, $w['start']);
            $this->assertGreaterThanOrEqual($w['start'], $w['end']);
            $prevEnd = $w['end'];
        }
    }

    public function test_same_narration_hits_cache_and_does_not_rewrite(): void
    {
        $first = $this->narrator->narrate('cache me if you can', 'v');
        $this->assertFalse($first->fromCache);

        $mtime = filemtime($first->audioPath);

        $second = $this->narrator->narrate('cache me if you can', 'v');
        $this->assertTrue($second->fromCache);
        $this->assertSame($first->audioPath, $second->audioPath);
        $this->assertSame($mtime, filemtime($second->audioPath));
    }

    public function test_changed_text_produces_a_new_hash(): void
    {
        $a = $this->narrator->narrate('original line', 'v');
        $b = $this->narrator->narrate('original line changed', 'v');

        $this->assertNotSame($a->audioPath, $b->audioPath);
    }

    public function test_different_voice_produces_a_new_hash(): void
    {
        $a = $this->narrator->narrate('same words', 'voice-a');
        $b = $this->narrator->narrate('same words', 'voice-b');

        $this->assertNotSame($a->audioPath, $b->audioPath);
    }

    public function test_force_regenerates_even_on_cache_hit(): void
    {
        $first = $this->narrator->narrate('force me', 'v');
        $this->assertFalse($first->fromCache);

        $forced = $this->narrator->narrate('force me', 'v', force: true);
        $this->assertFalse($forced->fromCache);
    }

    public function test_frames_rounds_up_from_duration(): void
    {
        $narration = $this->narrator->narrate('one two three', 'v');

        // 3 słowa / 150 wpm = 1.2 s; przy 30 fps = 36 klatek.
        $this->assertSame(36, $narration->frames(30));
    }

    public function test_provider_identity_is_in_the_cache_key(): void
    {
        // Zmiana providera musi unieważnić ciszę: inny id() => inny hash.
        $mock = new ClipNarrator(new MockTtsProvider());

        $hashMock = $mock->hash('text', 'v');
        $hashOther = sha1('elevenlabs:v2|v|text');

        $this->assertNotSame($hashOther, $hashMock);
        $this->assertSame(sha1('mock|v|text'), $hashMock);
    }
}
