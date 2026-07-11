<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Weryfikuje statyczny token API (Authorization: Bearer <token>) używany przez
 * lokalne narzędzie (Claude) do wgrywania artykułów.
 */
class VerifyArticleApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('articles.api_token');
        $provided = (string) ($request->bearerToken() ?? $request->header('X-Article-Token', ''));

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
