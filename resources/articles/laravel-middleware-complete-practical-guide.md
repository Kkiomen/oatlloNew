---
name: "Laravel Middleware: A Complete Practical Guide"
slug: laravel-middleware-complete-practical-guide
short_description: "A hands-on guide to Laravel middleware: before vs after, registration in bootstrap/app.php, aliases, parameters, groups, and terminable logic."
language: en
published_at: 2027-01-15 09:00:00
is_published: true
tags: [laravel, php, middleware, security]
---

Every HTTP request that hits a Laravel app passes through a stack of small classes before your controller ever runs. That stack is where Laravel middleware lives, and once you understand it, a whole category of problems (auth checks, logging, header rewriting, forcing JSON responses) stops being controller clutter and becomes something you configure in one place.

I've spent enough time debugging "why does this run twice" and "why isn't my header set" to know that most middleware confusion comes from two things: not knowing whether your logic runs *before* or *after* the request, and not knowing where to register the thing (which changed in Laravel 11). This guide covers both, with code you can paste and run.

## What middleware actually is

Middleware is a filter that sits between the incoming HTTP request and your application. Think of it as a series of layers wrapped around your route. A request travels inward through each layer to reach the controller, and the response travels back outward through the same layers.

Because of that onion shape, a single middleware class can do work on the way *in* (inspect the request, reject it, redirect) and on the way *out* (modify the response, add headers, log timing). Laravel's built-in `Authenticate`, `VerifyCsrfToken`, and `TrimStrings` are all middleware, and you've been using them without writing a line.

The mental model that matters:

- Request comes in, passes through middleware top to bottom.
- Hits the route/controller.
- Response goes back out, through the same middleware bottom to top.

## Creating your first middleware

Generate one with Artisan:

```bash
php artisan make:middleware EnsureTokenIsValid
```

That drops a class in `app/Http/Middleware`. Here's the skeleton, and it's worth reading every part of the signature:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->input('token') !== 'secret-value') {
            return redirect('/home');
        }

        return $next($request);
    }
}
```

`handle()` receives the current `$request` and a `Closure` called `$next`. Calling `$next($request)` passes control to the *next* layer, eventually the controller. Whatever `$next` returns is the response bubbling back out.

If you return early (like the redirect above) without calling `$next`, the request never reaches your controller. That's the whole point: middleware can short-circuit a request.

## Before vs after middleware

This distinction trips people up, but it's just about *where you call `$next`*.

**Before middleware** does its work first, then hands off:

```php
public function handle(Request $request, Closure $next): Response
{
    // runs BEFORE the controller
    Log::info('Incoming request to ' . $request->path());

    return $next($request);
}
```

**After middleware** hands off first, then works on the response:

```php
public function handle(Request $request, Closure $next): Response
{
    $response = $next($request);

    // runs AFTER the controller produced a response
    $response->headers->set('X-App-Version', config('app.version'));

    return $response;
}
```

Grab the return value of `$next($request)`, and you're holding the response. Anything after that line runs on the way out. Use before-logic for gatekeeping (auth, validation, redirects) and after-logic for decorating what's already been built (headers, response logging).

## Registering middleware in Laravel 11+ (the big change)

Here's the part that catches everyone coming from Laravel 10 or earlier. **The `app/Http/Kernel.php` file is gone.** In Laravel 11 and 12, all middleware registration happens in `bootstrap/app.php` through the `withMiddleware` callback:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // global middleware
        $middleware->append(\App\Http\Middleware\EnsureTokenIsValid::class);
    })
    ->create();
```

> Legacy note: if you're on Laravel 10 or below, registration still lives in `app/Http/Kernel.php` in the `$middleware`, `$middlewareGroups`, and `$routeMiddleware` (or `$middlewareAliases`) arrays. That file no longer ships with fresh 11+ apps.

The `Middleware` object passed into the callback gives you fluent methods for every scope. Let me break those down, because "which scope" is the second most common source of bugs.

### Global middleware

Runs on **every** HTTP request. Use `append` to add it to the end of the global stack, or `prepend` to put it first:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\ForceJsonResponse::class);
    $middleware->prepend(\App\Http\Middleware\TrustProxies::class);
})
```

### Group middleware (web and api)

Laravel ships with two route groups: `web` (sessions, cookies, CSRF) and `api` (stateless). You can push middleware into a specific group instead of running it everywhere:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\LogPageViews::class,
    ]);

    $middleware->api(prepend: [
        \App\Http\Middleware\ForceJsonResponse::class,
    ]);
})
```

You can also define your own named group with `appendToGroup`:

```php
$middleware->appendToGroup('reporting', [
    \App\Http\Middleware\CollectMetrics::class,
    \App\Http\Middleware\LogSlowRequests::class,
]);
```

### Route middleware and aliases

For middleware you want to attach per-route, register an alias so you don't type the full class name everywhere:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'token' => \App\Http\Middleware\EnsureTokenIsValid::class,
    ]);
})
```

Then in your routes:

```php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('token');
```

Or reference the class directly without an alias. Either works; aliases just read better.

## Middleware with parameters

Middleware can accept arguments, which is how the built-in `role:admin` style checks work. Extra parameters come after `$next`:

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    if (! in_array($request->user()?->role, $roles, true)) {
        abort(403);
    }

    return $next($request);
}
```

The variadic `...$roles` matters here: pass one role or several, and each comma-separated value lands as its own argument. Supply them in the route after a colon, comma-separating multiple values:

```php
Route::put('/post/{id}', [PostController::class, 'update'])
    ->middleware('role:editor,author');
```

Role and permission gatekeeping like this pairs naturally with Laravel's authorization layer. For finer-grained rules I usually lean on [policies](/blog/laravel-policies) rather than stuffing everything into middleware. Middleware answers "can this request proceed at all," policies answer "can this user do this to this specific model."

## Terminable middleware

Sometimes you want work to happen *after* the response has already been sent to the browser: think analytics, or writing a session to a slow store. Add a `terminate` method:

```php
public function handle(Request $request, Closure $next): Response
{
    return $next($request);
}

public function terminate(Request $request, Response $response): void
{
    // runs after the response is sent to the client
    Metrics::record($request->path(), $response->status());
}
```

`terminate()` receives both the request and the final response. One caveat that bit me once: by default Laravel resolves a fresh instance of the middleware for `terminate()`, so state you set on `$this` during `handle()` won't be there. Register it as a singleton in the container if you need the same instance across both methods.

## Middleware ordering and priority

Order matters more than people expect. Within a group, middleware runs in the order it's listed. But some middleware *must* run before others. You can't authenticate a user before the session is started, for example.

Laravel keeps a default priority list for this, and you can override it:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->priority([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\Auth\Middleware\Authenticate::class,
        // ...
    ]);
})
```

If a middleware depends on something an earlier one sets up (session, authenticated user, decrypted cookies), priority is how you guarantee the sequence regardless of where individual routes attach things.

## Common pitfalls

A short list of the things that actually cost me time:

- **Forgetting to return `$next($request)`.** If `handle()` doesn't return the response, you get a blank page or a null response. The controller ran; you just threw its output away.
- **Looking for `Kernel.php` on Laravel 11+.** It's not there. Everything is in `bootstrap/app.php`. Half the old Stack Overflow answers point at a file that no longer exists.
- **Putting auth logic in global middleware.** Global runs on *every* request, including your login page and health check. Scope it to a group or route instead.
- **Assuming `terminate()` shares state with `handle()`.** Different instance by default. Bind a singleton if you need continuity.
- **Wrong order for stateful checks.** If your middleware reads `$request->user()` but runs before `StartSession`/`Authenticate`, you'll get `null` and chase a ghost.

## FAQ

### What's the difference between middleware and a form request?

Middleware filters the raw HTTP request before routing logic and applies broadly (auth, headers, rate limits). A form request validates the *input payload* for a specific controller action. They complement each other; see [form request validation](/blog/laravel-form-request-validation) for input-level rules. Don't validate business input in middleware; that's the form request's job.

### Can middleware modify the response?

Yes. Capture `$response = $next($request)`, mutate it (headers, status, content), and return it. That's the "after middleware" pattern. This is exactly how response-header and CORS middleware work.

### How do I apply middleware to a group of routes at once?

Wrap them in `Route::middleware([...])->group(function () { ... })`, or attach middleware to a controller in its constructor via the `HasMiddleware` interface. Both avoid repeating `->middleware()` on every line.

### Is middleware the right place for rate limiting?

For throttling, yes — Laravel's `throttle` middleware handles it. If you want to understand what's happening under the hood before tuning limits, the comparison of [token bucket vs fixed window](/blog/api-rate-limiting-token-bucket-vs-fixed-window) strategies is worth a read.

## Wrapping up

Middleware is one of those Laravel features that feels abstract until the first time it saves you from scattering the same `if` check across fifteen controllers. Start with the mental model — request in, response out, `$next` is the hinge — and the rest is choosing the right scope.

If you're on Laravel 11 or 12, burn one fact into memory: registration lives in `bootstrap/app.php`, not `Kernel.php`. Generate a middleware with `php artisan make:middleware`, decide whether your logic runs before or after `$next`, register it at the right scope, and mind the ordering when session or auth is involved. That covers the vast majority of real-world use.