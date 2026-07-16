---
slug: api-versioning-strategies-carousel
type: carousel
language: en
title: "Where the version goes"
topic: laravel
source_type: article
source: api-versioning-strategies
link: https://oatllo.com/api-versioning-strategies
publish_at: 2026-10-14 19:00
status: ready
formats: [post, reel]
hashtags: [api, laravel, rest, versioning, backend]
caption: |
  `full_name` became `name`. Twenty minutes later, three mobile clients were down.

  Nobody told the app team, because a "small cleanup" did not feel like a
  breaking change. It was one. Versioning is the contract that lets you
  change a backend without waking someone else's on-call.

  Full comparison linked in bio.

  Where do you park your version number?
verified:
  verdict: approved
  at: 2026-07-16 06:53
  fingerprint: 690ebf055c2d71e7fc66cc74abee7c41df00fc70
  checks:
    - full_name->name, three mobile clients, twenty minutes, partner integration all traced to the article opener
    - Route::prefix('v1')->group + apiResource matches article code; URL-as-cache-key claim matches
    - "header slide: header('X-API-Version','1'), in_array, 400, Vary: X-API-Version all in article"
    - Deprecation and Sunset are real HTTP headers; six-month window matches article
  notes: |
    No version-pinned or 'latest' claims - ages well despite the October publish_at.
---

## Renaming one JSON field broke three mobile apps in twenty minutes.

`full_name` became `name`. It looked harmless in the pull request. A
partner integration silently stopped syncing orders too.

<!-- slide -->

## Most changes need no version at all

Adding a field or an endpoint is additive, safe if clients ignore what
they do not recognise. Renaming or retyping a field forces a bump. Those
are the expensive ones.

<!-- slide -->

## Laravel makes the path version boring

```php
Route::prefix('v1')->group(function () {
    Route::apiResource(
        'orders', V1\OrderController::class
    );
});
```

The URL is the cache key, so a CDN treats `/api/v1/orders` and
`/api/v2/orders` as two resources for free.

<!-- slide -->

## Let v2 diverge. Never share.

Each version gets its own controllers, and they never import from each
other. It feels like duplication until a shared method changes v1 for
clients who never asked.

<!-- slide -->

## The header costs more than it looks

```php
$version = $request->header(
    'X-API-Version', '1'
);
if (! in_array($version, ['1', '2'], true)) {
    return response()->json([...], 400);
}
```

Now you parse, validate and default it. And your CDN needs
`Vary: X-API-Version` or it serves a v1 body to a v2 client.

<!-- slide role="cta" -->

## You cannot retire what you never measured

Log usage per version, ship `Deprecation` and `Sunset` headers, give six
months. I have kept versions alive past sunset purely for lack of data.

