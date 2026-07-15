<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Przegląd przedstartowy autopublikacji (GET /api/social/health).
 *
 * Powstał, bo hosting współdzielony OVH NIE MA WYJŚCIA W SIEĆ Z KONSOLI
 * (`social:accounts` kończy się "cURL error 7: Connection refused"), a tick
 * publikuje przez WWW. Odpowiedź z CLI nie mówi więc nic o tym, czy publikacja
 * zadziała – trzeba zapytać serwer stamtąd, skąd naprawdę dzwoni.
 *
 * Token musi być w $_SERVER przed bootem: trasa jest rejestrowana warunkowo.
 */
class SocialHealthTest extends TestCase
{
    private const TOKEN = 'cron-token-abc';

    protected function setUp(): void
    {
        $_SERVER['SOCIAL_CRON_TOKEN'] = self::TOKEN;
        $_ENV['SOCIAL_CRON_TOKEN'] = self::TOKEN;

        parent::setUp();

        config([
            'social.auto_publish.token'   => self::TOKEN,
            'social.auto_publish.enabled' => true,
            'social.zernio.key'           => 'sk_test',
            'social.zernio.account_id'    => 'acc_oatllo',
            'social.zernio.base_url'      => 'https://zernio.com/api/v1',
        ]);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['SOCIAL_CRON_TOKEN'], $_ENV['SOCIAL_CRON_TOKEN']);

        parent::tearDown();
    }

    private function fakeAccounts(array $accounts): void
    {
        Http::fake(['zernio.com/*' => Http::response(['data' => $accounts], 200)]);
    }

    public function test_reports_a_healthy_setup(): void
    {
        $this->fakeAccounts([
            ['_id' => 'acc_oatllo', 'platform' => 'instagram', 'username' => 'oatllo_com'],
        ]);

        $this->withToken(self::TOKEN)
            ->getJson('/api/social/health')
            ->assertOk()
            ->assertJson([
                'auto_publish_enabled' => true,
                'zernio' => [
                    'configured'       => true,
                    'reachable'        => true,
                    'account_id_valid' => true,
                    'publishing_as'    => 'oatllo_com',
                ],
            ]);
    }

    /**
     * Sedno: na OVH konsola nie ma sieci. Jeśli WWW też nie ma, tick nic nie
     * opublikuje – i to musi być widać TERAZ, a nie o 19:00, gdy post ma wyjść.
     */
    public function test_reports_when_the_server_cannot_reach_zernio(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException(
            'cURL error 7: Failed to connect to zernio.com port 443: Connection refused'
        ));

        $this->withToken(self::TOKEN)
            ->getJson('/api/social/health')
            ->assertOk()
            ->assertJson(['zernio' => [
                'configured' => true,
                'reachable'  => false,
                'hint'       => 'Serwer nie ma wyjścia na zernio.com. Bez tego tick nic nie opublikuje.',
            ]]);
    }

    /**
     * Klucz widzi kilka marek. Wpisanie cudzego id wysłałoby posty Oatllo na cudzy
     * profil – nieodwracalnie. Lepiej zobaczyć to tutaj niż na obcym feedzie.
     */
    public function test_reports_an_account_id_that_belongs_to_another_brand(): void
    {
        $this->fakeAccounts([
            ['_id' => 'acc_aisello', 'platform' => 'instagram', 'username' => 'aisello_official'],
        ]);

        $this->withToken(self::TOKEN)
            ->getJson('/api/social/health')
            ->assertOk()
            ->assertJson(['zernio' => [
                'reachable'        => true,
                'account_id_valid' => false,
                'hint'             => 'ZERNIO_ACCOUNT_ID nie pasuje do żadnego konta z tego klucza.',
            ]]);
    }

    public function test_reports_a_bad_key(): void
    {
        Http::fake(['zernio.com/*' => Http::response(['message' => 'Unauthorized'], 401)]);

        $this->withToken(self::TOKEN)
            ->getJson('/api/social/health')
            ->assertOk()
            ->assertJson(['zernio' => ['reachable' => true, 'hint' => 'Klucz zły albo cofnięty.']]);
    }

    public function test_without_the_token_the_endpoint_denies_and_calls_nothing(): void
    {
        Http::fake();

        $this->getJson('/api/social/health')->assertNotFound();

        Http::assertNothingSent();
    }
}
