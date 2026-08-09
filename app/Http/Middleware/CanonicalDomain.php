<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wymusza jeden kanoniczny host + https przez trwałe przekierowanie 301.
 *
 * Problem: bez tego te same strony są osiągalne (i indeksowane przez Google)
 * pod kilkoma hostami — https://oatllo.com, https://www.oatllo.com,
 * http://oatllo.com, http://www.oatllo.com — a każdy wariant kanonikalizuje
 * się sam (tag canonical wskazuje bieżący URL). Google traktuje je jak duplikaty
 * i dzieli sygnały rankingowe, co spycha strony na 2. stronę wyników.
 *
 * MINA, ROZBROJONA ZA DRUGIM PODEJŚCIEM: ten middleware NIE MOŻE pytać o `APP_ENV`.
 * Wersja z 14.07 wisiała na `app()->environment('production')` i przez miesiąc nie
 * przekierowała ani jednego żądania. Poprawka z 09.08 wymieniła to na bail-out
 * `environment('local','testing')` - i po wdrożeniu na produkcję **dalej nie działała**,
 * bo dziedziczyła tę samą zależność. Dowód jest w logu: `https://www.oatllo.com/course/php`
 * oddawał 200 jeszcze po deployu commita, który miał to naprawić.
 *
 * Wniosek: cokolwiek jest nie tak z `APP_ENV` na tym serwerze, kod nie ma prawa na tym
 * stać. Guard oparty na środowisku milczy, gdy zawiedzie coś poza kodem (wartość w `.env`,
 * stary `config:cache`), a objaw jest niewidoczny - strona działa, testy przechodzą,
 * psuje się wyłącznie ranking.
 *
 * Dlatego decyduje WYŁĄCZNIE HOST: przekierowujemy tylko hosty z rodziny kanonicznej
 * (`oatllo.com` i `www.oatllo.com`). To jest jednocześnie cała ochrona dev-a - lokalny
 * `oatllo.test` i testowy `localhost` do rodziny nie należą, więc nigdy nie złapią
 * warunku. Środowisko nie występuje w tej klasie ani razu i tak ma zostać.
 *
 * Bezpieczeństwo:
 *  - Obcy host (podpięta inna domena, IP, health check zewnętrznego monitoringu)
 *    przechodzi bez zmian - nie odsyłamy w świat czegoś, czego nie znamy.
 *  - Serwer terminuje SSL bezpośrednio (bez proxy), więc $request->isSecure() jest
 *    wiarygodne i wymuszenie https nie tworzy pętli.
 *  - Przekierowujemy tylko żądania GET/HEAD (SEO dotyczy wyłącznie ich), żeby
 *    nie zamienić POST-a (np. API importu) w GET przy zmianie hosta.
 *  - Health check /up pomijamy, żeby monitoring nie dostawał 301.
 */
class CanonicalDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldEnforce($request)) {
            return $next($request);
        }

        $canonicalHost = (string) config('app.canonical_host');

        $hostMismatch = strtolower($request->getHost()) !== strtolower($canonicalHost);
        $notSecure = ! $request->isSecure();

        if ($hostMismatch || $notSecure) {
            $target = 'https://' . $canonicalHost . $request->getRequestUri();

            return redirect()->away($target, 301);
        }

        return $next($request);
    }

    private function shouldEnforce(Request $request): bool
    {
        if (! $request->isMethodSafe()) { // tylko GET/HEAD
            return false;
        }

        if (! $this->isCanonicalFamily($request)) {
            return false;
        }

        // Health check nie powinien dostawać przekierowań.
        if ($request->is('up')) {
            return false;
        }

        return true;
    }

    /**
     * Czy host żądania to kanoniczna domena albo jej wariant z `www.`.
     *
     * To jest cały warunek włączający middleware. Obcych hostów nie ruszamy,
     * a lokalny `oatllo.test` nie należy do rodziny, więc dev jest bezpieczny
     * bez oglądania się na APP_ENV.
     */
    private function isCanonicalFamily(Request $request): bool
    {
        $canonicalHost = strtolower((string) config('app.canonical_host'));

        if ($canonicalHost === '') {
            return false;
        }

        $host = strtolower($request->getHost());

        return $host === $canonicalHost || $host === 'www.' . $canonicalHost;
    }
}
