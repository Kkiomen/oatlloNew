<?php

namespace Tests\Feature;

use App\Services\Social\Publish\SocialMediaStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mini-cloud: endpoint przyjmujący grafiki wyrenderowane lokalnie.
 *
 * To PUBLICZNY ZAPIS PLIKÓW do katalogu serwowanego po HTTP – najgroźniejsza
 * rzecz w całym module. Te testy są w większości o tym, czego zrobić NIE WOLNO:
 * wgrać skryptu, wyjść z katalogu, wejść bez tokenu.
 *
 * Token MUSI być w $_SERVER przed bootem: routes/api.php rejestruje trasę
 * warunkowo, przy ładowaniu pliku tras (ten sam wzorzec co SOCIAL_PREVIEW).
 * Dzięki temu test przechodzi prawdziwą ścieżką routingu.
 */
class SocialMediaUploadTest extends TestCase
{
    private const TOKEN = 'test-token-123';

    protected function setUp(): void
    {
        $_SERVER['SOCIAL_MEDIA_TOKEN'] = self::TOKEN;
        $_ENV['SOCIAL_MEDIA_TOKEN'] = self::TOKEN;

        parent::setUp();

        Storage::fake('public');

        config([
            'social.media.token'    => self::TOKEN,
            'social.media.disk'     => 'public',
            'social.media.base_url' => 'https://oatllo.com',
        ]);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['SOCIAL_MEDIA_TOKEN'], $_ENV['SOCIAL_MEDIA_TOKEN']);

        parent::tearDown();
    }

    /**
     * Prawdziwy PNG: sam nagłówek nie wystarczy, bo serwer wącha magic bytes.
     */
    private function png(string $name = '01.png'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 1080, 1350);
    }

    public function test_uploads_a_slide_and_reports_its_public_url(): void
    {
        $response = $this->withToken(self::TOKEN)
            ->post('/api/social/media/demo-post', ['file' => $this->png(), 'index' => 1]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'file'    => '01.png',
                'url'     => 'https://oatllo.com/storage/social/demo-post/01.png',
            ]);

        Storage::disk('public')->assertExists('social/demo-post/01.png');
    }

    /**
     * Nazwę pliku skleja SERWER z numeru slajdu. Klient może przysłać cokolwiek –
     * i tak nie ma wpływu ani na nazwę, ani na rozszerzenie. Plik jest prawdziwym
     * PNG-iem po zawartości, a nazwa próbuje i uciec z katalogu, i podszyć się
     * pod skrypt: jedno i drugie ma zostać zignorowane.
     */
    public function test_the_server_names_the_file_not_the_client(): void
    {
        $disguised = UploadedFile::fake()->createWithContent(
            '../../evil.php',
            "\x89PNG\r\n\x1a\n" . str_repeat("\x00", 128),
        );

        $this->withToken(self::TOKEN)
            ->post('/api/social/media/demo-post', ['file' => $disguised, 'index' => 7])
            ->assertOk()
            ->assertJson(['file' => '07.png']);

        $files = Storage::disk('public')->allFiles();

        $this->assertSame(['social/demo-post/07.png'], $files);
    }

    /**
     * Slug idzie przez Str::slug, więc z `../` nie zostaje nic, czym dałoby się
     * wyjść z katalogu.
     *
     * Sprawdzane na sklejaniu ścieżki, a nie przez HTTP: zakodowane `%2F` nie
     * przechodzi nawet przez routing (404), więc test HTTP potwierdzałby warstwę,
     * która broni nas przypadkiem – a nie tego strażnika, na którym stoimy.
     */
    public function test_slug_cannot_escape_the_media_directory(): void
    {
        $store = app(SocialMediaStore::class);

        foreach (['../../etc/passwd', '..\\..\\windows', 'demo/../../root', ''] as $nasty) {
            $path = $store->path($nasty, '01.png');

            $this->assertStringStartsWith('social/', $path);
            $this->assertStringNotContainsString('..', $path);
            // Dokładnie dwa: social/{slug}/{plik}. Każdy dodatkowy = zagnieżdżenie
            // przemycone przez slug.
            $this->assertSame(2, substr_count($path, '/'), "Ścieżka '{$path}' wyszła poza social/{slug}/{plik}.");
        }
    }

    public function test_a_messy_slug_is_normalised_the_same_way_as_in_the_repository(): void
    {
        $this->withToken(self::TOKEN)
            ->post('/api/social/media/' . rawurlencode('Demo Post!!'), ['file' => $this->png(), 'index' => 1])
            ->assertOk()
            ->assertJson(['slug' => 'demo-post']);

        Storage::disk('public')->assertExists('social/demo-post/01.png');
    }

    /**
     * NAJWAŻNIEJSZY test: katalog jest serwowany publicznie, więc o tym, co w nim
     * ląduje, decyduje ZAWARTOŚĆ pliku – nie nazwa i nie Content-Type, bo jedno
     * i drugie pisze klient.
     */
    public function test_a_script_disguised_as_a_png_is_rejected(): void
    {
        $evil = UploadedFile::fake()->createWithContent('01.png', "<?php system(\$_GET['c']); ?>");

        $this->withToken(self::TOKEN)
            ->post('/api/social/media/demo-post', ['file' => $evil, 'index' => 1])
            ->assertStatus(422);

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_an_mp4_is_stored_as_the_reel(): void
    {
        // Minimalne pudełko ftyp – tego szuka sniffer.
        $mp4 = UploadedFile::fake()->createWithContent(
            'reel.mp4',
            "\x00\x00\x00\x20ftypisom" . str_repeat("\x00", 64),
        );

        $this->withToken(self::TOKEN)
            ->post('/api/social/media/demo-post', ['file' => $mp4, 'index' => 1])
            ->assertOk()
            ->assertJson(['file' => 'reel.mp4']);

        Storage::disk('public')->assertExists('social/demo-post/reel.mp4');
    }

    public function test_without_a_token_the_endpoint_denies_and_stores_nothing(): void
    {
        $this->post('/api/social/media/demo-post', ['file' => $this->png(), 'index' => 1])
            ->assertNotFound();

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_a_wrong_token_is_denied(): void
    {
        $this->withToken('nie-ten-token')
            ->post('/api/social/media/demo-post', ['file' => $this->png(), 'index' => 1])
            ->assertNotFound();

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_an_oversized_file_is_rejected(): void
    {
        config(['social.media.max_bytes' => 1024]);

        $this->withToken(self::TOKEN)
            ->post('/api/social/media/demo-post', [
                'file'  => UploadedFile::fake()->createWithContent('01.png', str_repeat('x', 4096)),
                'index' => 1,
            ])
            ->assertStatus(422);

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    /**
     * Żądanie bez pliku to zwykły błąd walidacji (422), a NIE "PHP odrzucił
     * żądanie, podnieś limity" (413).
     *
     * Pierwsza wersja strzelała 413 przy każdym braku pliku i kazała podnosić
     * upload_max_filesize, który na produkcji wynosi 128M – czyli wysyłała
     * człowieka szukać nieistniejącego problemu.
     */
    public function test_a_request_without_a_file_is_a_plain_validation_error(): void
    {
        $this->withToken(self::TOKEN)
            ->postJson('/api/social/media/demo-post', ['index' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_media_store_derives_urls_without_touching_the_markdown(): void
    {
        $store = app(SocialMediaStore::class);

        $this->assertSame('https://oatllo.com/storage/social/demo/01.png', $store->url('demo', '01.png'));
        $this->assertSame('https://oatllo.com/storage/social/demo/reel.mp4', $store->url('demo', 'reel.mp4'));
        $this->assertSame('10.png', $store->fileName(10, 'png'));
    }
}
