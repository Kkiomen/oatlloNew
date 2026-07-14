---
name: "Mocking HTTP APIs in Laravel Feature Tests"
slug: mock-http-laravel-tests
short_description: "Mock HTTP in Laravel tests end to end: Http::fake with URL patterns, sequences, assertSent, preventStrayRequests, and faked timeouts and failures."
language: en
published_at: 2027-01-29 09:00:00
is_published: true
tags: [laravel, testing, http, pest]
---

The endpoint I was testing looked simple: create a subscriber, then push them to an email provider. My feature test passed locally and failed in CI with a cryptic connection timeout. It turned out the test was firing a real request to the provider's staging API, which was down. The fix, and the reason I now **mock HTTP in Laravel tests** by default, is that a controller which talks to a third-party API should be tested against a fake of that API, not the real thing.

This is the feature-test angle specifically: you hit your own route with `$this->post(...)`, and somewhere down the call stack a controller or job reaches out to an external service. You want to fake that outbound call, assert your code sent the right payload, and check the resulting database row, all in one test. For the Guzzle-native side of mocking (MockHandler, handler stacks, wrapping clients behind an interface), see the companion piece on [how to mock HTTP requests in PHP](/blog/mock-http-requests-php). Here we stay inside Laravel's `Http` facade.

## The endpoint under test

Let's use something realistic. A `POST /api/subscribers` route creates a subscriber and registers them with an external mailing provider. The controller:

```php
namespace App\Http\Controllers\Api;

use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SubscriberController
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'name'  => ['required', 'string'],
        ]);

        $response = Http::withToken(config('services.mailer.key'))
            ->acceptJson()
            ->post('https://api.mailer.test/v1/contacts', [
                'email'      => $data['email'],
                'first_name' => $data['name'],
            ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Provider unavailable'], 502);
        }

        $subscriber = Subscriber::create([
            'email'       => $data['email'],
            'name'        => $data['name'],
            'provider_id' => $response->json('id'),
        ]);

        return response()->json($subscriber, 201);
    }
}
```

Notice there is no way to test this honestly without controlling that outbound `POST`. If you let it run for real, your test depends on someone else's uptime, their rate limits, and their willingness to let you create junk contacts. So we fake it.

## Faking the call with Http::fake()

Laravel's `Http::fake()` intercepts every request the `Http` facade makes for the rest of the test. You pass an array keyed by URL pattern, and `*` acts as a wildcard.

```php
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('creates a subscriber and registers them with the provider', function () {
    Http::fake([
        'api.mailer.test/*' => Http::response(['id' => 'contact_789'], 201),
    ]);

    $response = $this->postJson('/api/subscribers', [
        'email' => 'ada@example.com',
        'name'  => 'Ada',
    ]);

    $response->assertCreated();

    $this->assertDatabaseHas('subscribers', [
        'email'       => 'ada@example.com',
        'provider_id' => 'contact_789',
    ]);
});
```

That is a full round trip. `RefreshDatabase` gives the test a clean schema, `postJson` drives your real route and controller, the fake stands in for the provider, and `assertDatabaseHas` confirms the `provider_id` from the fake response was persisted. If you are still deciding on a runner, the [Pest vs PHPUnit](/blog/pest-vs-phpunit) comparison covers why the `it(...)` syntax above works in either.

The examples use Pest, but the same calls work verbatim in a PHPUnit `TestCase` method with `$this`.

### Matching by URL pattern

The keys of the `fake()` array are matched against the outgoing URL, top to bottom, first match wins. A few patterns I lean on:

- `'api.mailer.test/*'` matches any path on that host.
- `'api.mailer.test/v1/contacts'` matches one exact endpoint, useful when a controller talks to several routes on the same host.
- `'*'` is a catch-all. Put it **last**, or it shadows every specific rule above it.

```php
Http::fake([
    'api.mailer.test/v1/contacts'    => Http::response(['id' => 'contact_789'], 201),
    'api.mailer.test/v1/lists/*'     => Http::response(['subscribed' => true], 200),
    '*'                              => Http::response([], 200),
]);
```

Specificity does not win here, order does. I have wasted an afternoon on a leading `'*'` that quietly answered `200` to everything while I stared at the specific rule below it wondering why it never fired.

## Response sequences for repeated calls

Some flows call the same URL more than once and expect different answers. A job that polls a status endpoint, or a controller that retries. `Http::sequence()` hands out responses in order:

```php
Http::fake([
    'api.mailer.test/v1/jobs/*' => Http::sequence()
        ->push(['status' => 'processing'], 200)
        ->push(['status' => 'processing'], 200)
        ->push(['status' => 'done'], 200),
]);
```

First call gets `processing`, then `processing`, then `done`. Once the sequence is drained it throws an `OutOfBoundsException` by default, which is genuinely helpful: it tells you the code made more calls than you planned for. If extra calls are legitimate, close it off with `->whenEmpty(Http::response(['status' => 'done'], 200))`.

## Closures for dynamic responses

When the response should depend on what was actually sent, pass a closure instead of a static response. It receives the request and returns a response.

```php
Http::fake(function ($request) {
    if ($request->url() === 'https://api.mailer.test/v1/contacts') {
        $email = $request['email'];

        return Http::response([
            'id'    => 'contact_' . md5($email),
            'email' => $email,
        ], 201);
    }

    return Http::response([], 404);
});
```

This is the escape hatch for realistic behaviour: echo back the email that was posted, vary the status by payload, or return a `409` when a duplicate email shows up. The `$request` here is an `Illuminate\Http\Client\Request`, so `$request['email']` reads a field out of the JSON body and `$request->url()` gives the full target.

## Asserting what your code sent

Faking the response proves your code handles a reply. It says nothing about whether you sent the right request. That is what `Http::assertSent()` is for, and it is the half of the test people skip.

```php
Http::assertSent(function ($request) {
    return $request->url() === 'https://api.mailer.test/v1/contacts'
        && $request->hasHeader('Authorization', 'Bearer ' . config('services.mailer.key'))
        && $request['email'] === 'ada@example.com'
        && $request['first_name'] === 'Ada';
});
```

Three more assertions round out the toolkit:

- `Http::assertNotSent(fn ($request) => str_contains($request->url(), '/lists'))` proves a call did **not** happen. Handy for confirming a guard clause or a caching layer suppressed an outbound request.
- `Http::assertSentCount(1)` confirms the total number of outbound requests, so a stray duplicate `POST` gets caught.
- `Http::assertNothingSent()` asserts your code made no HTTP calls at all, which is the right check for a validation-failure path that should short-circuit before the provider.

Here is that last one in a test for the bad-input case:

```php
it('does not contact the provider when validation fails', function () {
    Http::fake();

    $this->postJson('/api/subscribers', ['email' => 'not-an-email'])
        ->assertStatus(422);

    Http::assertNothingSent();
});
```

Calling `Http::fake()` with no arguments fakes everything with an empty `200`, which is fine here because we assert nothing was sent anyway.

## Faking failures and timeouts

The failure paths are exactly what a live sandbox refuses to reproduce on demand, and they are where production breaks. Faking them is a few lines.

A `500` from the provider, to check your `502` branch:

```php
it('returns 502 when the provider errors', function () {
    Http::fake([
        'api.mailer.test/*' => Http::response(['message' => 'boom'], 500),
    ]);

    $this->postJson('/api/subscribers', [
        'email' => 'ada@example.com',
        'name'  => 'Ada',
    ])->assertStatus(502);

    $this->assertDatabaseCount('subscribers', 0);
});
```

The database assertion matters here: a failed provider call must not leave an orphaned subscriber row. That is the kind of bug a response-only assertion sails straight past.

For a connection timeout, throw a `ConnectionException` from the fake:

```php
use Illuminate\Http\Client\ConnectionException;

Http::fake(function () {
    throw new ConnectionException('cURL error 28: Operation timed out');
});
```

If your controller wraps the call in a `try/catch` or uses `Http::retry()`, this is how you exercise that logic without waiting on a real socket to hang. Pair it with `assertSentCount()` to confirm the retry actually retried the expected number of times.

## Locking the suite down with preventStrayRequests()

This is the guard I now put in the base `TestCase` of every project. `Http::preventStrayRequests()` makes any request that does not match a fake throw immediately instead of leaking to the real network.

```php
// tests/TestCase.php
protected function setUp(): void
{
    parent::setUp();

    Http::preventStrayRequests();
}
```

With that in place, a mistyped fake pattern can no longer pass silently by hitting the live API. Instead of a green test that secretly touched production, you get a loud exception naming the URL that escaped. Add it once and every feature test in the suite inherits the safety net. In Pest, the same call fits neatly in a `beforeEach()` in `Pest.php`.

## Common pitfalls

- **Asserting only the response.** A `201` proves you parsed a reply, not that you sent the right body. Always pair `Http::response(...)` with an `Http::assertSent(...)`.
- **Leading catch-all pattern.** A `'*'` at the top of the `fake()` array shadows every specific rule beneath it and answers everything with the same canned reply.
- **No stray-request guard.** Without `preventStrayRequests()`, a typo in a host name sends the call to the real network and the test still passes until the day that network is slow.
- **Skipping the DB side effects.** On a failure branch, assert the row was *not* written. Half the value of a feature test is catching partial writes.
- **Forgetting sequences drain.** A `sequence()` throws once empty; if your code legitimately makes an extra call, add `whenEmpty()` rather than padding the sequence with guesses.
- **Faking with hard-coded secrets.** Assert the `Authorization` header against `config('services.mailer.key')`, not a literal, so a broken config binding shows up in the test.

## FAQ

### Does Http::fake() work if my code uses the Guzzle client directly?

No. `Http::fake()` only intercepts requests made through Laravel's `Http` facade. If a controller instantiates `GuzzleHttp\Client` itself, the fake never sees it. Either route the call through `Http`, or mock at the Guzzle layer as described in the [PHP HTTP mocking guide](/blog/mock-http-requests-php).

### Where should I put Http::fake(), in each test or a global hook?

Response fakes belong in each test because they encode that test's scenario. `preventStrayRequests()` belongs in a global `setUp()` or `beforeEach()` so the whole suite is offline by default. Keeping them separate stops one test's fake from bleeding into another.

### How is this different from mocking a service class?

Faking `Http` tests the real wiring between your controller, its request building, and the response parsing. Mocking a wrapper service skips that wiring in favour of testing pure logic. Both are valid at different layers, which is the [unit vs integration test](/blog/unit-vs-integration-tests) tradeoff. For a controller that calls an API, the feature-level `Http::fake()` is usually the higher-value test.

### Do I still need RefreshDatabase if the test only asserts HTTP calls?

If your endpoint writes to the database, yes, so each run starts clean and `assertDatabaseHas`/`assertDatabaseCount` mean something. If the flow genuinely touches no tables, you can drop it, but most feature tests that call an external API also persist the result, so keep it.

## Wrapping up

A feature test for an API-calling endpoint has three moving parts, and all three deserve an assertion: the fake response your code receives, the request it sends out, and the database state it leaves behind. `Http::fake()` with URL patterns handles the first, `Http::assertSent()` and its siblings handle the second, and `RefreshDatabase` plus `assertDatabaseHas` handle the third. Wrap the whole suite in `Http::preventStrayRequests()` so nothing slips out to the real network.

Start with the guard in your base `TestCase` this week, then go back and add an `assertSent()` to the tests that only check the response today. Both changes are small, and together they turn "the endpoint returned 201" into "the endpoint sent the right payload, handled the reply, and saved the right row."