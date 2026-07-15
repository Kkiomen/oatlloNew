<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `social:accounts` – odczyt kont podpiętych do klucza Zernio.
 *
 * Jedyna wartość konfiguracji, której nie da się przepisać z panelu, to
 * ZERNIO_ACCOUNT_ID – i to tę komendę odpala się raz, przy pierwszym setupie.
 * Dlatego testy pilnują głównie tego, żeby błędy tłumaczyły, co zrobić dalej.
 */
class SocialAccountsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'social.zernio.key'      => 'sk_test',
            'social.zernio.base_url' => 'https://zernio.com/api/v1',
        ]);
    }

    /**
     * Kształt odpowiedzi przepisany z PRAWDZIWEGO API: id siedzi pod `_id`
     * (mongowe), nie pod `id` – pierwsza wersja czytała `id` i wypisywała "?".
     */
    public function test_lists_accounts_and_points_at_the_instagram_id(): void
    {
        Http::fake(['zernio.com/*' => Http::response(['data' => [
            ['_id' => 'acc_yt', 'platform' => 'youtube', 'username' => 'oatllo', 'platformStatus' => 'active'],
            ['_id' => 'acc_ig', 'platform' => 'instagram', 'username' => 'oatllo_com', 'platformStatus' => 'active'],
        ]], 200)]);

        $this->artisan('social:accounts')
            ->expectsOutputToContain('ZERNIO_ACCOUNT_ID=acc_ig')
            ->assertSuccessful();

        Http::assertSent(fn ($request) => $request->url() === 'https://zernio.com/api/v1/accounts'
            && $request->hasHeader('Authorization', 'Bearer sk_test'));
    }

    /**
     * NAJWAŻNIEJSZY test w tym pliku. Klucz widzi dwie marki (oatllo_com
     * i aisello_official), a pierwsza wersja brała "pierwszy Instagram z listy"
     * – czyli wskazywała CUDZY profil. Przeklejenie tego id wysłałoby posty
     * Oatllo na konto Aisello i nie dałoby się tego cofnąć.
     */
    public function test_with_two_instagram_accounts_it_refuses_to_guess(): void
    {
        Http::fake(['zernio.com/*' => Http::response(['data' => [
            ['_id' => 'acc_aisello', 'platform' => 'instagram', 'username' => 'aisello_official'],
            ['_id' => 'acc_oatllo', 'platform' => 'instagram', 'username' => 'oatllo_com'],
        ]], 200)]);

        // Artisan::call + output() zamiast łańcucha expectsOutputToContain: ten
        // drugi dopasowuje kolejne ZAPISY po kolei i je konsumuje, więc kilka
        // asercji na jedno wyjście przechodzi albo nie zależnie od tego, ile razy
        // komenda wywołała line() – a nie od tego, co naprawdę wypisała.
        $code = Artisan::call('social:accounts');
        $output = Artisan::output();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('wybierz świadomie, ja nie zgaduję', $output);

        // Oba id MUSZĄ być pokazane wprost przy swoich nazwach – to jedyne, co
        // powstrzymuje człowieka przed przeklejeniem cudzego konta.
        $this->assertStringContainsString('ZERNIO_ACCOUNT_ID=acc_aisello   # aisello_official', $output);
        $this->assertStringContainsString('ZERNIO_ACCOUNT_ID=acc_oatllo   # oatllo_com', $output);
    }

    /**
     * Token Instagrama wygasa – po cichu, a wtedy tick dostaje 401 i posty
     * przestają wychodzić bez żadnego sygnału na profilu.
     */
    public function test_it_shows_when_the_token_expires(): void
    {
        Http::fake(['zernio.com/*' => Http::response(['data' => [
            [
                '_id'            => 'acc_ig',
                'platform'       => 'instagram',
                'username'       => 'oatllo_com',
                'tokenExpiresAt' => now()->addDays(5)->toIso8601String(),
            ],
        ]], 200)]);

        $this->artisan('social:accounts')
            ->expectsOutputToContain('za 5 dni')
            ->assertSuccessful();
    }

    public function test_without_a_key_it_says_where_to_get_one(): void
    {
        config(['social.zernio.key' => '']);
        Http::fake();

        $this->artisan('social:accounts')
            ->expectsOutputToContain('Brak ZERNIO_API_KEY')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_a_bad_key_is_explained_not_just_dumped(): void
    {
        Http::fake(['zernio.com/*' => Http::response(['message' => 'Unauthorized'], 401)]);

        $this->artisan('social:accounts')
            ->expectsOutputToContain('401 = zły albo cofnięty klucz')
            ->assertFailed();
    }

    /**
     * Klucz bez podpiętego konta wygląda jak "działa", a publikacja i tak by padła.
     */
    public function test_a_key_with_no_connected_accounts_fails_loudly(): void
    {
        Http::fake(['zernio.com/*' => Http::response(['data' => []], 200)]);

        $this->artisan('social:accounts')
            ->expectsOutputToContain('nie ma podpiętego ŻADNEGO konta')
            ->assertFailed();
    }

    public function test_a_key_without_instagram_fails(): void
    {
        Http::fake(['zernio.com/*' => Http::response(['data' => [
            ['id' => 'acc_x', 'platform' => 'x', 'username' => 'oatllo', 'status' => 'active'],
        ]], 200)]);

        $this->artisan('social:accounts')
            ->expectsOutputToContain('nie jest Instagramem')
            ->assertFailed();
    }
}
