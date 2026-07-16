---
slug: laravel-octane-performance-carousel
type: carousel
language: en
title: "Octane state leaks"
topic: laravel
source_type: article
source: laravel-octane-performance
link: https://oatllo.com/laravel-octane-performance
publish_at: 2026-10-26 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, octane, performance, backend]
caption: |
  Octane keeps your app in memory between requests. Everything you left lying around is still there for the next user.

  Static props, singletons that captured the request, boot-time bindings: all
  harmless under FPM, all leaks under Octane.

  Full audit list linked in bio.

  Which one bit you first?
---

## A static array leaks one user's rows into another's

Under FPM the process dies at the end of the request and scrubs everything
clean. Octane keeps the worker alive, so request #2 sees what request #1 left.

<!-- slide -->

## This class never starts fresh again

```php
class ReportBuilder
{
    // survives across requests in the worker
    protected static array $rows = [];
}
```

Request #2 renders its report with another user's rows glued to the bottom.
Nothing throws.

<!-- slide -->

## The second tenant gets the first tenant's data

```php
$this->app->singleton(TenantContext::class,
    fn ($app) => new TenantContext(
        $app['request']->header('X-Tenant')
    ));
```

Resolved once at construction, frozen forever. Resolve the request inside the
method that needs it, not in the constructor.

<!-- slide -->

## Your fix is on disk. Not in memory.

Deploy pulls the new code. The warm workers keep serving what they booted with,
and the file on disk looks correct the whole time. `php artisan octane:reload`
cycles them without dropping in-flight requests.

<!-- slide role="cta" -->

## Test it under load, not one request

A state leak is invisible with one request and obvious with a hundred concurrent
ones. `--max-requests` is a net for slow growth, not a cure.
