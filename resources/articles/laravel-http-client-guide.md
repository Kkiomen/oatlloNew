---
name: "The Laravel HTTP Client: Retries, Pooling and Testing"
slug: laravel-http-client-guide
short_description: "Why 4xx and 5xx do not throw by default, how retry() backoff really works, running requests in parallel with Http::pool, and faking calls in tests."
language: en
published_at: 2027-06-14 09:00:00
is_published: true
tags: [laravel, php, http, testing]
---

The first time it bit me, a payment webhook had been silently failing for two days. The code called a partner API, got the response, read `$response->json()['id']`, and moved on. The partner had started returning `422` with an error body. No exception was thrown, `json()` returned an array without an `id` key, and the record was saved with a null reference. Everything looked green in the logs.

That is the single most important thing to understand about Laravel's HTTP client: **a `4xx` or `5xx` response is not an error as far as your code is concerned.** The request completed. You got a response. Whether that response is useful is your job to check. Everything below is the stuff you hit once you leave a happy-path `Http::get()` behind — building requests, retrying transient failures without hammering the server, running calls in parallel, and testing all of it without touching the network.

## It is Guzzle, with the sharp edges filed off

The `Http` facade is a fluent wrapper around Guzzle. You still get Guzzle's connection handling and PSR-7 under the hood, but you skip the ceremony of building a client, passing option arrays, and decoding bodies by hand. A GET with a query string and a bearer token is one chain:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken(config('services.github.token'))
    ->withHeaders(['Accept' => 'application/vnd.github+json'])
    ->get('https://api.github.com/user/repos', [
        'per_page' => 100,
        'sort' => 'updated',
    ]);
```

The second argument to `get()` becomes the query string. For writes, pick your body encoding explicitly, because this is where people trip. `post($url, $data)` sends **JSON by default** — `Content-Type: application/json`. If the endpoint wants a classic HTML form body, you have to say so:

```php
// JSON body: {"name":"ada","role":"admin"}
Http::post('https://api.example.com/users', [
    'name' => 'ada',
    'role' => 'admin',
]);

// Form body: name=ada&role=admin
Http::asForm()->post('https://api.example.com/users', [
    'name' => 'ada',
    'role' => 'admin',
]);
```

Sending JSON to an endpoint that only parses `application/x-www-form-urlencoded` gives you an empty `$request->all()` on the other side and a very confusing afternoon. There is also `asMultipart()` for file uploads and `attach()` for streaming a file into a multipart request.

## Timeouts: set both, and set them low

The default request timeout is 30 seconds. That is far too long for a synchronous web request — if a downstream API hangs, you are holding a PHP-FPM worker hostage for half a minute. Set a real timeout, and set a separate, shorter `connectTimeout`:

```php
Http::timeout(10)          // total time for the whole request
    ->connectTimeout(3)    // time allowed just to establish the connection
    ->get('https://api.example.com/status');
```

The distinction matters. `connectTimeout` fires when the host is unreachable or DNS is slow — fail fast there, because no amount of waiting fixes a dead host. `timeout` covers the full round trip including the response body, so a dependency that is up but slow trips that one instead. Set both. A generous total timeout with no connect timeout still lets a black-holed host stall you for the full thirty seconds, which is the exact scenario you were trying to avoid.

## retry(): useful, but read the defaults

`retry()` is the reason a lot of people reach for this client, and it is genuinely good. But the defaults do more than they look like they do.

```php
$response = Http::retry(3, 100)->get('https://api.example.com/data');
```

That is: try up to **3 times total**, sleeping **100 milliseconds** between attempts. The catch is *what* it retries. By default `retry()` catches Guzzle's `ConnectionException` **and** any response that fails `throw()` — meaning `4xx` and `5xx` responses trigger a retry too. Retrying a `422` is pointless: the request was malformed, and it will be malformed the second time. Retrying a `401` just burns your rate limit against a token that is not going to become valid.

So use the `when` callback (the third argument) to retry only what is actually transient:

```php
$response = Http::retry(3, 100, function (\Exception $exception, $request) {
    // Retry connection problems and 429/503 — skip everything else.
    if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
        return true;
    }

    $status = optional($exception->response)->status();

    return in_array($status, [429, 503], true);
})->get('https://api.example.com/data');
```

### Backoff, so you do not make it worse

A fixed 100ms sleep is a linear retry. When a service is overloaded and returning `503`, a wall of clients all retrying at the same fixed interval keeps it overloaded — this is the retry storm. What you want is **exponential backoff**: each attempt waits longer than the last. The second argument to `retry()` can be a closure that receives the attempt number and returns the sleep in milliseconds:

```php
$response = Http::retry(4, function (int $attempt, \Exception $exception) {
    // 200ms, 400ms, 800ms, 1600ms
    return $attempt * $attempt * 100;
})->get('https://api.example.com/data');
```

For anything hitting a shared external service, add jitter (a small random offset) so your retries do not all land on the same tick. The reasoning behind the growth curve — and why jitter matters more than the exact multiplier — is worth a read on its own if you are building anything that talks to a flaky upstream; see [exponential backoff and retry](/exponential-backoff-retry).

One more flag: by default, if every attempt fails, `retry()` throws a `RequestException` after the last one. Pass `throw: false` as the final argument when you would rather inspect the exhausted response yourself instead of catching an exception.

## Making failures loud: throw(), throwIf(), and friends

Back to the silent-failure problem. The client hands you a response object no matter what the status was, and gives you a set of methods to interrogate it:

```php
$response->successful();   // 2xx
$response->failed();       // 4xx or 5xx
$response->clientError();  // 4xx
$response->serverError();  // 5xx
$response->status();       // 201
```

If you want a bad status to behave like an exception — which is usually what you want for an API you depend on — call `throw()`:

```php
$data = Http::post('https://api.example.com/charge', $payload)
    ->throw()               // throws RequestException on 4xx/5xx
    ->json();
```

`throw()` returns the response on success, so you can keep chaining. It only fires on failure. There is also `throwIf($condition)` for conditional escalation and a callback form that lets you inspect the response before deciding:

```php
$response->throwIf($user->isOnFreePlan());

$response->throw(function ($response, $exception) {
    // Runs only on failure — good place for context-rich logging.
    Log::warning('Billing API rejected the charge', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);
});
```

My rule of thumb: for a third-party call whose result I am about to act on, `throw()` right after the request. If I have a genuine reason to handle specific statuses inline — say `404` means "not found, that's fine" — I check `status()` explicitly and skip `throw()`. What I never do anymore is read `->json()` off an unchecked response and assume the keys are there.

## Reading the response

`json()` decodes to an array; pass a key for a single value. `object()` gives you a `stdClass`, `collect()` gives you a Laravel collection you can immediately pipe through `map`/`filter`:

```php
$response->json();              // full array
$response->json('data.0.id');   // dot notation into the body
$response->object()->id;        // stdClass access
$response->collect('items')     // Collection
    ->pluck('name');
$response->body();              // raw string
```

`collect()` is the one I reach for when the payload is a list I am going to reshape anyway — it saves a `collect($response->json('items'))` wrap.

## Http::pool: fan out, then wait once

When you need several independent calls, doing them one after another means you pay the latency of each in series. Three endpoints at 200ms each is 600ms of your user's time for work that could take 200ms. `Http::pool` sends them concurrently and blocks once for all of them:

```php
$responses = Http::pool(fn ($pool) => [
    $pool->get('https://api.example.com/users'),
    $pool->get('https://api.example.com/teams'),
    $pool->get('https://api.example.com/projects'),
]);

$users = $responses[0]->json();
$teams = $responses[1]->json();
```

By default the results come back as a numerically indexed array in the order you queued them. If juggling indexes feels fragile — and it does the moment someone reorders the list — name each request with `as()` and read by key:

```php
$responses = Http::pool(fn ($pool) => [
    $pool->as('users')->get('https://api.example.com/users'),
    $pool->as('teams')->get('https://api.example.com/teams'),
]);

$users = $responses['users']->json();
```

Two things to keep in mind. The pool has no shared retry or throw behaviour — you configure each request inside the closure, and you check each response yourself, because one failing while the others succeed is entirely normal. And pooling only helps for **independent** calls. If call B needs the result of call A, they are sequential by definition and a pool buys you nothing.

## Trimming repetition: withOptions and macros

If every call to a service shares the same base URL, token, and timeout, stop repeating yourself. `withOptions()` passes raw Guzzle options through, and for the common case there is `baseUrl()`:

```php
Http::baseUrl('https://api.example.com/v2')
    ->withToken($token)
    ->timeout(10)
    ->get('/users');   // hits https://api.example.com/v2/users
```

For a service you hit from many places, register a **macro** once (in a service provider's `boot()`) so the configuration lives in exactly one spot:

```php
// AppServiceProvider::boot()
Http::macro('billing', function () {
    return Http::baseUrl(config('services.billing.url'))
        ->withToken(config('services.billing.token'))
        ->timeout(8)
        ->retry(3, 200)
        ->acceptJson();
});
```

Now every caller writes `Http::billing()->post('/charges', $payload)` and inherits the timeout, retry policy, and auth for free. When the token or base URL changes, you edit one closure.

## Testing: fake it, then prove what you sent

The client is built to be tested without a live network. `Http::fake()` intercepts outgoing requests and returns canned responses. Give it a map of URL patterns to responses:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'api.example.com/users' => Http::response([
        ['id' => 1, 'name' => 'Ada'],
    ], 200),
    'api.example.com/*' => Http::response(['error' => 'not found'], 404),
]);
```

Order matters — the first matching pattern wins — so put specific URLs above wildcards. You can also pass a closure to vary the response based on the request, or a sequence (`Http::fakeSequence()`) to return different responses on successive calls, which is exactly how you test that your retry logic recovers after a `503`:

```php
Http::fake([
    'api.example.com/*' => Http::fakeSequence()
        ->push(['error' => 'busy'], 503)
        ->push(['ok' => true], 200),
]);
```

Faking the response is half the test. The other half is asserting you sent the *right* request — correct URL, method, headers, and body. That is what `assertSent` is for:

```php
Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
    return $request->url() === 'https://api.example.com/charges'
        && $request->hasHeader('Authorization', 'Bearer test-token')
        && $request['amount'] === 1000;
});
```

There is a full set: `assertSentCount()`, `assertNotSent()`, and `assertNothingSent()`. A deeper walk through structuring these tests — including how to keep the fakes from drifting out of sync with the real API — is in [mocking HTTP in Laravel tests](/mock-http-laravel-tests).

### The assertion that catches the bug you did not write a test for

Here is the gap. `Http::fake()` with an explicit map stubs the URLs you listed. Any request to a URL you *didn't* list still gets a generic empty `200` fake. So a test can pass while your code fires off a call you never intended — a debug request, a call in a loop that should have been batched, a duplicate. `preventStrayRequests()` turns any unfaked request into a thrown exception:

```php
Http::preventStrayRequests();

Http::fake([
    'api.example.com/users' => Http::response(['id' => 1]),
]);

// If the code under test calls any other URL, the test fails loudly here.
```

I turn this on in the base test case for the whole suite. It has caught more real bugs than any assertion I write on purpose — usually a stray call that was quietly succeeding against a real endpoint during tests because someone forgot to fake it.

## Quick reference: the gotchas

| Behaviour | Default | What bites people |
|---|---|---|
| `4xx` / `5xx` | Does **not** throw | Silent failures — call `throw()` or check `failed()` |
| `retry()` `when` | Retries connection errors and failed responses | Retries `422`/`401` uselessly — pass a `when` callback |
| `post()` body | JSON | Use `asForm()` for form-encoded endpoints |
| `timeout()` | 30s | Too long for web requests; also set `connectTimeout()` |
| `Http::fake()` | Unlisted URLs return empty `200` | Add `preventStrayRequests()` to catch stray calls |

## FAQ

**Why does Laravel's HTTP client not throw on a 404 or 500?**
Because a `404` is a valid, completed HTTP response, not a transport failure. The client only throws on its own when the connection itself breaks (`ConnectionException`). Getting an exception on a bad status is opt-in: call `throw()`, `throwIf()`, or inspect `failed()`/`status()` yourself.

**Does Http::retry sleep between every attempt, including the last?**
It sleeps *between* attempts, not after the final one. `retry(3, 100)` makes at most three attempts with two 100ms pauses in between. If all attempts fail it throws a `RequestException` unless you passed `throw: false`.

**Can I run Http::pool requests with retries?**
Configure retry (and throw, timeout, headers) on each request inside the pool closure — `$pool->get(...)` returns the same fluent builder. The pool itself has no top-level retry; each request carries its own.

**How do I test that my code retried after a failure?**
Use `Http::fakeSequence()` to push a failing response followed by a successful one, then assert the final result succeeded and, if you like, `assertSentCount()` to confirm the number of attempts.

The pattern that keeps HTTP integrations boring: set a tight timeout, retry only the transient statuses with backoff, `throw()` so failures surface instead of hiding, and lock the whole thing down in tests with `preventStrayRequests()`. Do that once in a macro per service and every call site inherits the good behaviour. The next time a partner API starts returning `422`, you will hear about it from a failing test or an exception — not from a customer two days later.
