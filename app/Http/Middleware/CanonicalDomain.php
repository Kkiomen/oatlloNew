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
 * MINA (kosztowała miesiąc): warunkiem NIE MOŻE być `app()->environment('production')`.
 * Ta wersja wisiała na produkcji od 14.07 i nie przekierowała ani jednego żądania -
 * www i http dalej oddawały 200, a GSC pokazywał 7 duplikatów kanonicznych i ruch
 * równolegle na `www.oatllo.com`. Guard oparty na APP_ENV milczy, gdy zawiedzie coś
 * poza kodem: inna wartość `APP_ENV` w `.env` produkcji albo stary `config:cache`
 * sprzed wdrożenia. Objaw jest przy tym niewidoczny - strona działa, testy przechodzą,
 * a jedyne, co się psuje, to ranking.
 *
 * Dlatego decyduje SAM HOST: przekierowujemy wyłącznie hosty z rodziny kanonicznej
 * (`oatllo.com` i `www.oatllo.com`). Środowisko przestaje mieć znaczenie, bo lokalny
 * `oatllo.test` po prostu nie należy do tej rodziny i nigdy nie złapie warunku.
 *
 * Bezpieczeństwo:
 *  - `local`/`testing` dodatkowo wyłączone wprost, na wypadek `CANONICAL_HOST`
 *    ustawionego lokalnie na własny host (inaczej http://oatllo.test dostałoby 301).
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
        if (app()->environment('local', 'testing')) {
            return false;
        }

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
