<?php

namespace Tests\Feature;

use App\Services\Clip\Tts\ElevenLabsTtsProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Provider ElevenLabs — na ZAMOCKOWANYM HTTP (bez realnych wywołań i klucza).
 *
 * Sedno: składanie słów z wyrównania na poziomie znaku (to jest cała trudność),
 * dekodowanie audio i guardy. Interfejs jest ten sam co mock, więc reszta
 * pipeline'u nie zależy od tego, który provider gra.
 */
class ClipElevenLabsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'clip.tts.elevenlabs.key'      => 'test-key',
            'clip.tts.elevenlabs.model'    => 'eleven_multilingual_v2',
            'clip.tts.elevenlabs.base_url' => 'https://api.elevenlabs.io',
        ]);
    }

    public function test_builds_words_from_character_alignment(): void
    {
        // "hi go" — znaki z czasami; spacja domyka słowo "hi".
        Http::fake([
            '*' => Http::response([
                'audio_base64' => base64_encode('FAKEAUDIO'),
                'alignment'    => [
                    'characters'                     => ['h', 'i', ' ', 'g', 'o'],
                    'character_start_times_seconds'  => [0.0, 0.1, 0.2, 0.25, 0.4],
                    'character_end_times_seconds'    => [0.1, 0.2, 0.25, 0.4, 0.55],
                ],
            ]),
        ]);

        $speech = (new ElevenLabsTtsProvider())->synthesize('hi go', 'voice-1');

        $this->assertSame('mp3', $speech->ext);
        $this->assertSame('FAKEAUDIO', $speech->audio);
        $this->assertSame(
            [
                ['text' => 'hi', 'start' => 0.0, 'end' => 0.2],
                ['text' => 'go', 'start' => 0.25, 'end' => 0.55],
            ],
            $speech->words,
        );
        $this->assertEqualsWithDelta(0.55, $speech->duration, 0.001);
    }

    public function test_missing_key_throws_before_any_request(): void
    {
        config(['clip.tts.elevenlabs.key' => '']);
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ELEVENLABS_API_KEY');

        (new ElevenLabsTtsProvider())->synthesize('hi', 'voice-1');

        Http::assertNothingSent();
    }

    public function test_empty_voice_id_throws(): void
    {
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('voice_id');

        (new ElevenLabsTtsProvider())->synthesize('hi', '');
    }

    public function test_http_error_is_surfaced(): void
    {
        Http::fake(['*' => Http::response('nope', 422)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 422');

        (new ElevenLabsTtsProvider())->synthesize('hi', 'voice-1');
    }

    public function test_sends_api_key_header_and_text(): void
    {
        Http::fake([
            '*' => Http::response([
                'audio_base64' => base64_encode('A'),
                'alignment'    => [
                    'characters'                    => ['a'],
                    'character_start_times_seconds' => [0.0],
                    'character_end_times_seconds'   => [0.3],
                ],
            ]),
        ]);

        (new ElevenLabsTtsProvider())->synthesize('a', 'voice-xyz');

        Http::assertSent(function ($request) {
            return $request->hasHeader('xi-api-key', 'test-key')
                && str_contains($request->url(), '/text-to-speech/voice-xyz/with-timestamps')
                && $request['text'] === 'a';
        });
    }

    public function test_provider_id_reflects_model(): void
    {
        $this->assertSame('elevenlabs:eleven_multilingual_v2', (new ElevenLabsTtsProvider())->id());
    }
}
