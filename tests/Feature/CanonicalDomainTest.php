<?php

namespace Tests\Feature;

use App\Http\Middleware\CanonicalDomain;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Kanonikalizacja hosta: www/http -> https://oatllo.com, jednym 301.
 *
 * Ten test powstał PO fakcie i to jest jego cała racja bytu. Middleware
 * wdrożono 14.07 z warunkiem `app()->environment('production')` i przez prawie
 * miesiąc nie przekierował ani jednego żądania - `https://www.oatllo.com/course/php`
 * oddawał 200 z canonicalem na samego siebie, a GSC raportował 7 duplikatów
 * kanonicznych i wyświetlenia na `www.`. Bez testu awaria była niewidoczna:
 * strona działa, nic się nie wywala, psuje się wyłącznie ranking.
 *
 * Dlatego test sprawdza middleware BEZPOŚREDNIO, na sztucznych żądaniach,
 * a nie przez `$this->get()`. Suite chodzi w środowisku `testing`, w którym
 * middleware jest celowo wyłączony, więc test przez klienta HTTP przechodziłby
 * na zielono nie sprawdzając niczego - dokładnie ta klasa fałszywego spokoju,
 * która pozwoliła błędowi przeżyć miesiąc.
 */
class CanonicalDomainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.canonical_host', 'oatllo.com');
    }

    /** @dataProvider redirectingHosts */
    public function test_redirects_non_canonical_origins_to_https_apex(string $url, string $expected): void
    {
        $response = $this->handle(Request::create($url));

        $this->assertSame(301, $response->getStatusCode(), "Brak 301 dla {$url}");
        $this->assertSame($expected, $response->headers->get('Location'));
    }

    public static function redirectingHosts(): array
    {
        return [
            'www + https' => [
                'https://www.oatllo.com/course/php',
                'https://oatllo.com/course/php',
            ],
            'www + http' => [
                'http://www.oatllo.com/blog/tag/php-enums',
                'https://oatllo.com/blog/tag/php-enums',
            ],
            'apex + http' => [
                'http://oatllo.com/blog',
                'https://oatllo.com/blog',
            ],
            'zachowuje query string' => [
                'https://www.oatllo.com/blog?page=2',
                'https://oatllo.com/blog?page=2',
            ],
            'zachowuje stronę główną' => [
                'http://www.oatllo.com/',
                'https://oatllo.com/',
            ],
        ];
    }

    public function test_canonical_origin_passes_through(): void
    {
        $response = $this->handle(Request::create('https://oatllo.com/course/php'));

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * TO JEST JEDYNA OCHRONA DEV-A i dlatego ma własny zestaw danych.
     *
     * Po wyrzuceniu wszystkich warunków na `APP_ENV` nic poza hostem nie stoi między
     * lokalnym żądaniem a przekierowaniem na produkcyjną domenę. Herd (`oatllo.test`),
     * suite testowy (`localhost`) i dowolna inna podpięta domena muszą przechodzić
     * nietknięte - inaczej `php artisan test` zacząłby odsyłać na oatllo.com.
     *
     * @dataProvider foreignHosts
     */
    public function test_foreign_host_is_left_alone(string $url): void
    {
        $response = $this->handle(Request::create($url));

        $this->assertSame(200, $response->getStatusCode(), "Host z {$url} nie powinien dostać 301.");
    }

    public static function foreignHosts(): array
    {
        return [
            'Herd' => ['http://oatllo.test/course/php'],
            'suite testowy' => ['http://localhost/course/php'],
            'IP' => ['http://127.0.0.1:8000/course/php'],
            'obca domena na tym samym serwerze' => ['https://inna-domena.pl/course/php'],
            'subdomena, ale nie www' => ['https://staging.oatllo.com/course/php'],
        ];
    }

    /**
     * Bez tego zmiana hosta zamieniłaby POST-a w GET-a i zgubiła payload.
     */
    public function test_unsafe_methods_are_not_redirected(): void
    {
        $response = $this->handle(Request::create('https://www.oatllo.com/image/upload', 'POST'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_health_check_is_not_redirected(): void
    {
        $response = $this->handle(Request::create('http://oatllo.com/up'));

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * TO JEST TEST NA WŁAŚCIWĄ AWARIĘ, nie na kod - i musiał być pisany DWA RAZY.
     *
     * Wersja z 14.07 wisiała na `environment('production')` i milczała miesiąc.
     * Pierwsza poprawka (09.08) wymieniła to na bail-out `environment('local','testing')`
     * i **po wdrożeniu na produkcję dalej nie przekierowywała** - odziedziczyła tę samą
     * zależność, a `APP_ENV` na tym serwerze najwyraźniej należy do wykluczonych.
     * Pierwsza wersja tego testu tego nie złapała, bo sama ustawiała środowisko na
     * `production` i tym samym zakładała to, co miała sprawdzać.
     *
     * Dlatego przypadek jest teraz odwrotny: 301 MA PADAĆ NIEZALEŻNIE OD ŚRODOWISKA,
     * łącznie z `local`. Cała ochrona dev-a stoi na hoście (test niżej), nie na `APP_ENV`.
     *
     * Oblewa po dopisaniu jakiegokolwiek warunku na `environment()` w tym middlewarze.
     *
     * @dataProvider environments
     */
    public function test_enforcement_ignores_the_environment_entirely(string $environment): void
    {
        $this->app->detectEnvironment(fn () => $environment);

        $response = $this->handle(Request::create('https://www.oatllo.com/course/php'));

        $this->assertSame(
            301,
            $response->getStatusCode(),
            "Brak 301 przy APP_ENV={$environment} - middleware znowu patrzy na środowisko."
        );
        $this->assertSame('https://oatllo.com/course/php', $response->headers->get('Location'));
    }

    public static function environments(): array
    {
        return [
            'production' => ['production'],
            'literówka w APP_ENV' => ['prod'],
            'staging' => ['staging'],
            'local (najgroźniejszy - ubił poprzednią poprawkę)' => ['local'],
            'testing' => ['testing'],
        ];
    }

    private function handle(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        return (new CanonicalDomain())->handle(
            $request,
            fn () => new Response('ok', 200)
        );
    }
}
