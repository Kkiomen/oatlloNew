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

        // Middleware jest wyłączony w `local` i `testing`; udajemy produkcję.
        $this->app->detectEnvironment(fn () => 'production');
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
     * Host spoza rodziny kanonicznej zostaje nietknięty. To jest warunek, na którym
     * stoi bezpieczeństwo dev-a po wyrzuceniu guardu na APP_ENV: `oatllo.test`
     * nie jest ani `oatllo.com`, ani `www.oatllo.com`, więc nigdy nie złapie 301.
     */
    public function test_foreign_host_is_left_alone(): void
    {
        $response = $this->handle(Request::create('http://oatllo.test/course/php'));

        $this->assertSame(200, $response->getStatusCode());
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
     * TO JEST TEST NA WŁAŚCIWĄ AWARIĘ, nie na kod.
     *
     * Middleware nie zadziałał, bo wisiał na `app()->environment('production')`,
     * a produkcja nie spełniała tego warunku (inna wartość `APP_ENV` albo stary
     * `config:cache`). Kod był poprawny, środowisko nie - i nikt się nie dowiedział.
     * Ten przypadek pilnuje, żeby 301 nie zależał od nazwy środowiska: `prod` to
     * najbardziej prawdopodobna literówka i musi działać tak samo jak `production`.
     *
     * Oblewa po przywróceniu guardu na `environment('production')`.
     */
    public function test_enforcement_does_not_depend_on_the_environment_name(): void
    {
        $this->app->detectEnvironment(fn () => 'prod');

        $response = $this->handle(Request::create('https://www.oatllo.com/course/php'));

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('https://oatllo.com/course/php', $response->headers->get('Location'));
    }

    /**
     * `local`/`testing` mają być wyłączone wprost - inaczej `CANONICAL_HOST`
     * ustawiony lokalnie na własny host odsyłałby dev-a na https.
     */
    public function test_local_environment_is_exempt(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $response = $this->handle(Request::create('http://www.oatllo.com/blog'));

        $this->assertSame(200, $response->getStatusCode());
    }

    private function handle(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        return (new CanonicalDomain())->handle(
            $request,
            fn () => new Response('ok', 200)
        );
    }
}
