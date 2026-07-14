---
name: "API Versioning Strategies: URL, Header, or Media Type"
slug: api-versioning-strategies
short_description: "Compare the four main API versioning strategies (URI, query param, header, media type) with honest trade-offs and Laravel examples."
language: en
published_at: 2026-12-23 09:00:00
is_published: true
tags: [api, versioning, laravel, rest]
---

The first time I broke a production API, I did it by renaming a field. `full_name` became `name`. Seemed harmless in the pull request. Twenty minutes after deploy, three mobile clients started throwing null-pointer errors and a partner integration silently stopped syncing orders. Nobody had told the app team, because I didn't think a "small cleanup" counted as a breaking change. It did.

That afternoon is why I care about **API versioning strategies**. Versioning is not busywork you add because a style guide told you to. It is the contract that lets you change your backend without waking up someone else's on-call engineer. This post walks through the four approaches you'll actually see in the wild, the trade-offs I've hit with each, and how to wire them up in Laravel.

## What Versioning Actually Buys You

Versioning exists so that old clients keep working while new clients get new behavior. That's the whole point. Everything else is implementation detail.

A few things worth being honest about before you pick a scheme:

- **Not every change needs a new version.** Adding a field, adding an endpoint, adding an optional query parameter: all additive, all non-breaking if clients are built to ignore what they don't recognize.
- **Breaking changes are the expensive ones.** Removing or renaming a field, changing a type, tightening validation, altering the meaning of a status code. These force a version bump.
- **The best version bump is the one you avoid.** More on that at the end, because it matters more than the transport mechanism you choose.

So the question isn't really "which versioning strategy is best." It's "where do I put the version number, and what does that decision cost me later."

## Strategy 1: URI Path Versioning

You put the version straight in the path. This is the one you've seen a thousand times.

```http
GET /api/v1/orders HTTP/1.1
Host: api.myapp.com
Accept: application/json
```

Bump to v2 and the URL changes:

```http
GET /api/v2/orders HTTP/1.1
Host: api.myapp.com
```

**Why people reach for it:** it's visible. You can paste the URL into a browser, a `curl` command, or a Slack message and everyone immediately knows which version they're talking about. It caches cleanly, because the URL is the cache key. `/api/v1/orders` and `/api/v2/orders` are two different resources as far as any CDN or proxy is concerned. And it's trivial for clients: no custom headers, no content negotiation, just a different string.

**The honest downside:** REST purists will tell you a URI should identify a resource, not a representation of that resource, and that `v1` and `v2` of an order are the same underlying thing. They're technically right. In four years of shipping APIs, no client has ever complained about this. I mention it because you'll hit the argument in code review, not because it has cost me anything real.

## Strategy 2: Query Parameter Versioning

The version rides along as a query string value.

```http
GET /api/orders?version=1 HTTP/1.1
Host: api.myapp.com
```

It's still reasonably visible, and it keeps a single base path. The trouble starts around caching and defaults. Some proxies strip or reorder query parameters, and you have to decide what happens when `version` is missing. Do you default to v1 forever, or reject the request? Defaulting to the oldest version quietly is how you end up supporting v1 in 2030 because nobody was ever forced to state their intent.

I've used this one for internal tools where the callers were scripts I controlled. I would not pick it for a public API.

## Strategy 3: Custom Header Versioning

The version moves out of the URL entirely and into a header of your own design.

```http
GET /api/orders HTTP/1.1
Host: api.myapp.com
X-API-Version: 2
```

The URL now stays stable across versions, which reads nicely and keeps REST purists happy. But you've traded that for real ergonomic pain. You can no longer test a version by pasting a URL into a browser. Caching needs a `Vary: X-API-Version` header or your CDN will happily serve a v1 response to a v2 client. And every client, every debugging session, every log line now has to carry that header for the request to mean anything.

Headers are also easy to forget. When a request silently falls back to a default because someone omitted the header, the bug is much harder to spot than a wrong number sitting right there in the path.

## Strategy 4: Media Type (Accept Header) Versioning

This is the most REST-orthodox option. The client negotiates the representation it wants through the `Accept` header using a vendor media type.

```http
GET /api/orders HTTP/1.1
Host: api.myapp.com
Accept: application/vnd.myapp.v1+json
```

GitHub famously did this. In theory it's beautiful: one URL per resource, versioning handled through proper HTTP content negotiation, exactly as the spec intended. In practice it's the hardest for clients to get right. Try explaining `application/vnd.myapp.v1+json` to a frontend developer who just wants to fetch some orders, or debugging why a request 406'd because someone typo'd the media type.

I reach for this only when the audience is other backend engineers who value correctness over convenience, or when I'm already doing heavy content negotiation for other reasons.

## API Versioning Strategies Side by Side

| Strategy | Visibility | Cacheability | REST purity | Client simplicity |
|---|---|---|---|---|
| URI path (`/api/v1/...`) | High | Excellent (URL is the key) | Low | High |
| Query param (`?version=1`) | Medium | Fragile (proxies mangle params) | Low | High |
| Custom header (`X-API-Version: 1`) | Low | Needs `Vary` header | Medium | Medium |
| Media type (`Accept: ...vnd.myapp.v1+json`) | Low | Needs `Vary` header | High | Low |

The pattern is hard to miss. As you move down the table you gain theoretical correctness and lose everything that makes an API pleasant to consume and cheap to operate.

## Wiring It Up in Laravel

Laravel makes URI path versioning almost boringly easy, which is part of why I default to it. Route groups do the heavy lifting.

```php
// routes/api.php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('orders', \App\Http\Controllers\Api\V1\OrderController::class);
});

Route::prefix('v2')->group(function () {
    Route::apiResource('orders', \App\Http\Controllers\Api\V2\OrderController::class);
});
```

Mirror that split in your controller namespaces so the code stays as organized as the routes:

```bash
app/Http/Controllers/Api/
  V1/
    OrderController.php
  V2/
    OrderController.php
```

The rule I follow: **each version gets its own controllers and its own API resources, and they never import from each other.** It feels like duplication at first. It saves you from the nightmare where a well-meaning tweak to a shared method changes v1 behavior for clients who never asked for it. Let v2 diverge freely. When v1 is finally retired, you delete a folder and a route group, and nothing else moves.

If you prefer the header approach, keep a single route set and branch inside middleware:

```php
// app/Http/Middleware/ResolveApiVersion.php

public function handle($request, Closure $next)
{
    $version = $request->header('X-API-Version', '1');

    if (! in_array($version, ['1', '2'], true)) {
        return response()->json(['message' => 'Unsupported API version'], 400);
    }

    $request->attributes->set('api_version', $version);

    return $next($request);
}
```

Notice how much more there is to reason about: parsing, validating, defaulting, and then dispatching on that value somewhere downstream. That extra surface area is exactly the cost the comparison table hints at.

Since your response shape often changes between versions, it's worth deciding early how you serialize output. If you're weighing your options there, I wrote up [Laravel API Resources vs Fractal](/blog/laravel-api-resources-vs-fractal), which pairs naturally with a versioned controller layout.

## Changing Without Breaking

The transport mechanism matters far less than the discipline around it. A few habits that have kept me out of trouble:

- **Prefer additive changes.** New fields and new endpoints don't need a version bump if clients ignore unknown data. Design your clients to do exactly that.
- **Announce a deprecation window.** When you must break something, ship the new version, keep the old one running, and give consumers a real date. Six months is a reasonable floor for a public API. Use the `Deprecation` and `Sunset` HTTP headers so machines learn about it too, not just humans reading a changelog.
- **Instrument the old version.** Log usage per version. You cannot retire v1 responsibly if you have no idea who still calls it. I've kept versions alive months past their sunset date purely because I lacked the data to prove they were safe to kill.
- **Version the whole API, not individual endpoints.** Per-endpoint versions sound flexible and become a combinatorial mess. One version number for the surface keeps the mental model simple.

Authentication is one place breaking changes love to hide, since token formats and scopes evolve alongside your API. If you're setting that up, [Laravel Sanctum vs Passport](/blog/laravel-sanctum-vs-passport) covers the trade-offs. And if part of your API pushes data outward, the same versioning care applies to your callbacks — [webhook design best practices](/blog/webhook-design-best-practices) goes deeper on keeping those stable.

## FAQ

### When should I start versioning my API?

Before the first external consumer integrates, add the version segment to your URLs even if there's only ever a v1. Retrofitting versioning onto an unversioned public API is painful, because you have to support the legacy unversioned routes indefinitely. Adding `/v1/` up front costs you nothing and buys you room to move.

### Can I mix versioning strategies?

You can, and you'll occasionally see APIs offer URI versioning as the primary scheme with a header for finer-grained overrides. I'd avoid it unless you have a concrete reason. Two ways to specify a version means two code paths to test and two ways for a client to get it wrong. Pick one and be consistent.

### Do internal APIs need versioning?

Less rigorously. If you own every caller and can deploy them together, you can often skip formal versioning and coordinate changes directly. The moment a service you don't control — or can't redeploy in lockstep — starts calling you, treat it like a public API and version it.

### How many versions should I support at once?

Two is a comfortable number: current and previous. Three is manageable with discipline. Beyond that, each additional version is a tax on every future change, since you're testing and maintaining behavior for clients who had months to migrate and chose not to.

## Recommendation

Use URI path versioning. Start every public API at `/api/v1/`, map each version to its own Laravel route group and controller namespace, and keep versions fully independent so you can delete an old one cleanly. It's the most visible, the most cacheable, and by a wide margin the easiest for clients — and those three things beat theoretical REST purity on every real project I've shipped.

Reach for media type versioning only if your consumers are backend engineers who genuinely value strict content negotiation. Use the header approach when a stable URL is a hard requirement. And whichever you pick, spend more energy on additive changes and honest deprecation windows than on the mechanism itself. That renamed `full_name` field would have broken clients no matter where I'd parked the version number.