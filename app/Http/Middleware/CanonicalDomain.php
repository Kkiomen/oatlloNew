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
 * Bezpieczeństwo:
 *  - Działa TYLKO w produkcji, więc localhost/Herd (i testy) są nietknięte.
 *  - Serwer to Apache terminujący SSL bezpośrednio (bez proxy), więc
 *    $request->isSecure() jest wiarygodne i wymuszenie https nie tworzy pętli.
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
        if (! app()->environment('production')) {
            return false;
        }

        if (! $request->isMethodSafe()) { // tylko GET/HEAD
            return false;
        }

        if (empty(config('app.canonical_host'))) {
            return false;
        }

        // Health check nie powinien dostawać przekierowań.
        if ($request->is('up')) {
            return false;
        }

        return true;
    }
}
