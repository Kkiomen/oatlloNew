---
slug: laravel-middleware-complete-practical-guide-carousel
type: carousel
language: en
title: "Middleware in 11"
topic: laravel
source_type: article
source: laravel-middleware-complete-practical-guide
link: https://oatllo.com/laravel-middleware-complete-practical-guide
publish_at: 2026-10-05 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, middleware, backend, webdev]
caption: |
  Kernel.php does not exist in Laravel 11. Half the Stack Overflow answers point at a file that is gone.

  Registration lives in bootstrap/app.php now: append for global, web()/api()
  for groups, alias() for routes. $next is the hinge.

  Full guide linked in bio.

  Still hunting for Kernel.php?
verified:
  verdict: approved
  at: 2026-07-16 07:16
  fingerprint: 05e54150348a9a64e98d527c7510764c3f82aea6
  checks:
    - Kernel.php really is gone from fresh Laravel 11/12 skeletons; registration moved to bootstrap/app.php withMiddleware - correct
    - append / web() / api() / alias() are the real Middleware methods and map to the old Kernel array scopes as claimed
    - next is the hinge - before/after split and the blank page from forgetting to return next(request) both correct
    - terminate() gets a fresh instance by default unless bound as a singleton - correct, matches the docs and the article
    - ordering claim correct - reading request->user() before StartSession yields null
  notes: |
    Version claim is the whole point of the post and it is the right way round.
---

## Kernel.php is gone in Laravel 11. Half of Stack Overflow missed it.

You are looking for `app/Http/Kernel.php` to register your middleware. It no
longer ships with fresh 11 and 12 apps. Registration moved to `bootstrap/app.php`.

<!-- slide -->

## Everything lives in one callback now

```php
->withMiddleware(function (Middleware $m) {
    $m->append(EnsureTokenIsValid::class);
    $m->alias([
        'token' => EnsureTokenIsValid::class,
    ]);
})
```

`append` for global, `web()` / `api()` for a group, `alias()` for per-route
names. The same scopes as the old Kernel arrays, just fluent.

<!-- slide -->

## $next is the hinge

```php
// before: gatekeeping
Log::info($request->path());
return $next($request);

// after: decorate the response
$response = $next($request);
$response->headers->set('X-Version', '2');
return $response;
```

Grab the return value of `$next($request)` and you are holding the response.
Everything after that line runs on the way out.

<!-- slide -->

## Forget the return and you get a blank page

If `handle()` does not return `$next($request)`, the controller ran and you threw
its output away. No error, no warning. Just nothing.

<!-- slide -->

## terminate() gets a different instance

```php
public function terminate($request, $response)
{
    // fresh instance by default:
    // state from handle() is NOT here
}
```

It runs after the response is sent to the client. Laravel resolves a new
instance for it, so whatever you set on `$this` in `handle()` is gone. Bind a
singleton if you need continuity.

<!-- slide role="cta" -->

## Order matters more than you expect

Middleware reading `$request->user()` before `StartSession` gets `null`, and you
chase a ghost. Global auth runs on your login page too.
