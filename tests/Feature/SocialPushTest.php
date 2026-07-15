<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `social:push` – przeniesienie lokalnie wyrenderowanych grafik na serwer.
 *
 * Te testy powstały z PRAWDZIWEGO błędu: serwer odrzucał reela (2.7 MB przy
 * upload_max_filesize=2M), Laravel odpowiadał na to przekierowaniem formularzowym,
 * klient szedł za nim, dostawał 200 ze strony głównej i meldował SUKCES. Grafiki
 * nie było na serwerze, a dowiedzielibyśmy się o tym dopiero przy publikacji –
 * na produkcji, po cichu, tydzień później.
 */
class SocialPushTest extends TestCase
{
    private string $dir;

    private string $exportDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/social-push-' . uniqid());
        $this->exportDir = $this->dir . '/export';

        File::ensureDirectoryExists($this->dir);
        File::ensureDirectoryExists($this->exportDir);

        config([
            'social.path'         => $this->dir,
            'social.push.target'  => 'https://oatllo.com',
            'social.push.token'   => 'push-token',
        ]);

        File::put($this->dir . '/demo.md', implode("\n", [
            '---',
            'type: carousel',
            'slug: demo',
            'status: ready',
            'caption: Podpis.',
            '---',
            '',
            '## A',
            '',
            'Treść.',
            '',
            '<!-- slide -->',
            '',
            '## B',
            '',
            'Treść.',
        ]) . "\n");

        File::put($this->exportDir . '/01.png', 'png-1');
        File::put($this->exportDir . '/02.png', 'png-2');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function test_pushes_every_slide_with_its_index(): void
    {
        Http::fake(['oatllo.com/*' => Http::response(['success' => true, 'url' => 'https://oatllo.com/storage/social/demo/01.png'], 200)]);

        $this->artisan('social:push', ['slug' => 'demo', '--from' => $this->exportDir])
            ->assertSuccessful();

        Http::assertSentCount(2);

        Http::assertSent(function (Request $request) {
            $this->assertSame('https://oatllo.com/api/social/media/demo', $request->url());
            $this->assertTrue($request->hasHeader('Authorization', 'Bearer push-token'));

            // Bez Accept: application/json Laravel odpowiada na błąd walidacji
            // PRZEKIEROWANIEM, a nie 422 – i odrzucona wysyłka udaje udaną.
            $this->assertTrue($request->hasHeader('Accept', 'application/json'));

            return true;
        });
    }

    /**
     * Odpowiedź 200 bez URL-a pliku to NIE nasz endpoint (przekierowanie, proxy,
     * strona błędu z kodem 200). Ma być błędem, nie sukcesem.
     */
    public function test_a_200_without_a_file_url_is_treated_as_failure(): void
    {
        Http::fake(['oatllo.com/*' => Http::response('<html>strona główna</html>', 200)]);

        $this->artisan('social:push', ['slug' => 'demo', '--from' => $this->exportDir])
            ->expectsOutputToContain('bez URL-a pliku')
            ->assertFailed();
    }

    public function test_a_rejected_upload_fails_loudly(): void
    {
        Http::fake(['oatllo.com/*' => Http::response(['message' => 'Upload nieudany (za duży plik).'], 413)]);

        $this->artisan('social:push', ['slug' => 'demo', '--from' => $this->exportDir])
            ->expectsOutputToContain('HTTP 413')
            ->assertFailed();
    }

    public function test_dry_run_sends_nothing(): void
    {
        Http::fake();

        $this->artisan('social:push', ['slug' => 'demo', '--from' => $this->exportDir, '--dry-run' => true])
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_refuses_when_the_post_was_never_exported(): void
    {
        Http::fake();

        $this->artisan('social:push', ['slug' => 'demo', '--from' => $this->dir . '/pusto'])
            ->expectsOutputToContain('social:export demo')
            ->assertFailed();

        Http::assertNothingSent();
    }
}
