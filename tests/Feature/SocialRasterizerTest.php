<?php

namespace Tests\Feature;

use App\Services\Social\Rasterizer\HeadlessBrowserRasterizer;
use App\Services\Social\Rasterizer\NullRasterizer;
use App\Services\Social\Rasterizer\RasterizationFailed;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Rasteryzator HTML -> PNG.
 *
 * CELOWO nie odpalamy prawdziwej przeglądarki: testujemy dobór binarki, budowę
 * argumentów i zamianę ścieżki na URL file://. Sam zrzut sprawdza się okiem
 * (`php artisan social:export`), bo CI nie musi mieć Chrome.
 */
class SocialRasterizerTest extends TestCase
{
    private HeadlessBrowserRasterizer $rasterizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rasterizer = new HeadlessBrowserRasterizer();
    }

    public function test_explicit_binary_from_config_is_used(): void
    {
        // Plik, który na pewno istnieje – sprawdzamy dobór, nie uruchomienie.
        $existing = base_path('composer.json');
        config(['social.browser.binary' => $existing]);

        $this->assertTrue($this->rasterizer->available());
        $this->assertSame($existing, $this->rasterizer->binary());
    }

    public function test_missing_explicit_binary_makes_the_rasterizer_unavailable(): void
    {
        config(['social.browser.binary' => 'C:\nope\nothing-here.exe']);

        $this->assertFalse($this->rasterizer->available());
    }

    public function test_falls_back_to_the_first_existing_candidate(): void
    {
        config([
            'social.browser.binary'     => null,
            'social.browser.candidates' => [
                base_path('this-does-not-exist.exe'),
                base_path('composer.json'),
                base_path('artisan'),
            ],
        ]);

        $this->assertSame(base_path('composer.json'), $this->rasterizer->binary());
    }

    public function test_unavailable_rasterizer_throws_instead_of_writing_a_broken_file(): void
    {
        config(['social.browser.binary' => null, 'social.browser.candidates' => []]);

        $this->expectException(RasterizationFailed::class);
        $this->expectExceptionMessageMatches('/SOCIAL_BROWSER_BINARY/');

        $this->rasterizer->rasterize('whatever.html', 1080, 1350, storage_path('framework/testing/nope.png'));
    }

    /**
     * KRYTYCZNE: bez --force-device-scale-factor=1 ekran HiDPI zamienia
     * --window-size=1080,1350 w PNG 2160x2700. Instagram przyjąłby to bez słowa.
     */
    public function test_arguments_pin_the_device_scale_factor(): void
    {
        $args = $this->arguments();

        $this->assertContains('--force-device-scale-factor=1', $args);
    }

    public function test_arguments_set_the_exact_window_size(): void
    {
        $this->assertContains('--window-size=1080,1350', $this->arguments());
    }

    /**
     * Headless potrafi zawisnąć albo nie zapisać zrzutu, gdy trafi na profil
     * żywej przeglądarki użytkownika.
     */
    public function test_arguments_use_an_isolated_user_profile(): void
    {
        $args = $this->arguments();

        $profileArgs = array_filter($args, fn (string $a) => str_starts_with($a, '--user-data-dir='));

        $this->assertCount(1, $profileArgs);
    }

    public function test_arguments_wait_for_the_embedded_font(): void
    {
        $this->assertContains('--virtual-time-budget=2000', $this->arguments());
    }

    public function test_arguments_hide_scrollbars_and_run_headless(): void
    {
        $args = $this->arguments();

        $this->assertContains('--headless=new', $args);
        $this->assertContains('--hide-scrollbars', $args);
    }

    public function test_windows_path_becomes_a_file_url_with_forward_slashes(): void
    {
        $path = base_path('composer.json');

        $url = $this->rasterizer->fileUrl($path);

        $this->assertStringStartsWith('file:///', $url);
        $this->assertStringNotContainsString('\\', $url);
        $this->assertStringContainsString('composer.json', $url);
    }

    public function test_null_rasterizer_is_always_available_and_writes_nothing(): void
    {
        $null = new NullRasterizer();
        $out = storage_path('framework/testing/null-rasterizer.png');
        File::delete($out);

        $this->assertTrue($null->available());
        $null->rasterize('x.html', 1080, 1350, $out);

        $this->assertFileDoesNotExist($out);
    }

    /**
     * @return list<string>
     */
    private function arguments(): array
    {
        return $this->rasterizer->arguments(
            'msedge.exe',
            base_path('composer.json'),
            1080,
            1350,
            'out.png',
            'C:\tmp\profile',
        );
    }
}
