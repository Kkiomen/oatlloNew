---
slug: mock-http-requests-php-carousel
type: carousel
language: en
title: "Mocking HTTP in tests"
topic: php
source_type: article
source: mock-http-requests-php
link: https://oatllo.com/mock-http-requests-php
publish_at: 2026-11-03 19:00
status: ready
formats: [post, reel]
hashtags: [php, testing, laravel, guzzle, pest]
caption: |
  Tests that hit the real network are not tests. They are weather reports.

  A third-party API goes down for maintenance and your CI goes red while your
  code sits there unchanged.

  Fakes, guards and the interface rule are in bio.

  What took your build down last?
---

## Tests that hit the real network are weather reports

A payment API goes down for maintenance and your pipeline goes red. Nothing in
your code changed. The failure is somebody else's server.

<!-- slide -->

## Queue the responses. It's FIFO.

```php
$mock = new MockHandler([
    new Response(200, [], '{"id": 42}'),
    new Response(429, ['Retry-After' => '5']),
    new RequestException('Timed out', $req),
]);
```

A `Throwable` in the queue gets thrown, not returned. That is how you test retry
logic without waiting for a flaky server.

<!-- slide -->

## The catch-all swallows everything above it

```php
Http::fake([
    'stripe.com/*' => Http::response([], 500),
    '*' => Http::response([], 200), // last
]);
```

Laravel matches top to bottom and takes the first hit. Specificity counts for
nothing. Put `*` first and every request comes back 200.

<!-- slide -->

## Assert what you sent, not what you got

```php
Http::assertSent(fn ($r) =>
    $r->hasHeader('Authorization')
    && $r['sku'] === 'ABC-123'
);
```

Proving you handled a 200 proves nothing about the payload. `assertNotSent()` is
the underrated one: it proves a call never happened at all.

<!-- slide -->

## One line takes the whole suite offline

```php
Http::preventStrayRequests();
```

Any URL you forgot to fake now throws instead of quietly escaping to the
network. Put it in your base test case and every mistyped pattern surfaces.

<!-- slide role="cta" -->

## Don't mock what you don't own

Both helpers pin your tests to Guzzle. Wrap the client behind your own interface
and mock that - the day you swap vendors, your domain tests survive. Full guide

