---
name: "How to Mock HTTP Requests in PHP Tests"
slug: mock-http-requests-php
short_description: "Learn to mock HTTP requests in PHP tests with Guzzle MockHandler and Laravel Http::fake for fast, deterministic, offline test suites."
language: en
published_at: 2026-10-14 09:00:00
is_published: true
tags: [php, testing, laravel, guzzle]
---

The first time a CI pipeline of mine went red because a third-party payment API was down for maintenance, I learned the lesson the hard way: tests that reach out to the real network are not tests, they are weather reports. If you want to **mock HTTP requests in PHP** the right way, the goal is a suite that gives the same answer at 3 a.m. on a plane as it does on the build server at noon.

This guide walks through both the Guzzle-native approach (`MockHandler`, `HandlerStack`, history middleware) and Laravel's `Http::fake()` helpers. I'll also cover the one architectural decision that saves the most pain long term, and the mistakes I keep seeing in code review.

## Why you should mock HTTP in tests at all

Hitting a live endpoint from a test couples your suite to things you do not control. Four concrete reasons to stop doing it:

- **Deterministic.** A mocked response returns the same bytes every run, so a failing test means your code changed, not someone else's server.
- **Fast.** No DNS lookup, no TLS handshake, no waiting on a slow upstream. A mocked call resolves in microseconds.
- **Offline.** The suite runs on a train, in a locked-down CI runner, or inside a container with no egress.
- **No rate limits.** You can run the same test 500 times without burning through an API quota or getting your key throttled.

There is a fifth reason that matters more than people admit: you can simulate failures. A `500`, a timeout, malformed JSON, an expired token. Those are the paths that break in production, and a live sandbox almost never lets you trigger them on demand.

## The Guzzle-native approach: MockHandler

If you use Guzzle directly (no framework, or a package that exposes the client), the built-in `MockHandler` is the tool. You queue up canned responses, hand the handler to a `HandlerStack`, and the client pops one response per request.

```php
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

$mock = new MockHandler([
    new Response(200, ['Content-Type' => 'application/json'], '{"id": 42, "status": "active"}'),
    new Response(429, ['Retry-After' => '30']),
    new RequestException('Connection timed out', new Request('GET', 'users/42')),
]);

$stack  = HandlerStack::create($mock);
$client = new Client(['handler' => $stack]);

$response = $client->get('https://api.example.com/users/42');

echo $response->getStatusCode();        // 200
echo (string) $response->getBody();     // {"id": 42, "status": "active"}
```

The queue is FIFO. First request gets the `200`, the second gets the `429`, the third throws a `RequestException`. If a `MockHandler` item is a `Throwable`, Guzzle throws it instead of returning it, which is exactly how you test your retry or error-handling logic without a flaky server.

### Asserting what your code sent with history middleware

Returning fake responses is half the job. The other half is verifying your code sent the *right* request: correct URL, headers, and body. Guzzle ships a history middleware for this. You give it an array by reference, push it onto the stack, and every transaction lands in that array.

```php
use GuzzleHttp\Middleware;

$container = [];
$history   = Middleware::history($container);

$mock  = new MockHandler([new Response(201, [], '{"ok": true}')]);
$stack = HandlerStack::create($mock);
$stack->push($history);

$client = new Client(['handler' => $stack]);

$client->post('https://api.example.com/orders', [
    'headers' => ['Authorization' => 'Bearer secret-token'],
    'json'    => ['sku' => 'ABC-123', 'qty' => 2],
]);

// One transaction recorded
$this->assertCount(1, $container);

$sentRequest = $container[0]['request'];
$this->assertSame('POST', $sentRequest->getMethod());
$this->assertSame('Bearer secret-token', $sentRequest->getHeaderLine('Authorization'));
$this->assertSame(
    ['sku' => 'ABC-123', 'qty' => 2],
    json_decode((string) $sentRequest->getBody(), true)
);
```

Each entry in `$container` is an associative array with `request`, `response`, `error`, and `options` keys. That gives you full inspection of both sides of the exchange.

## The Laravel approach: Http::fake()

Laravel wraps Guzzle in its own `Http` client, and its faking layer is far less ceremony. No handler stacks to assemble; you call `Http::fake()` and pattern-match by URL.

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'github.com/*'    => Http::response(['plan' => 'pro'], 200),
    'api.example.com/*' => Http::response('Server error', 500),
    '*'               => Http::response([], 200), // catch-all
]);

$response = Http::get('https://api.example.com/status');

$response->status();  // 500
$response->body();    // "Server error"
```

Order matters and specificity does not. Laravel checks patterns top to bottom and uses the first match, so put your catch-all `*` last or it swallows everything above it. That one caught me on a Friday afternoon once; every request came back `200` and I could not work out why.

### Faking a sequence of responses

When the same URL should return different things on successive calls (think polling a job status until it flips to `completed`), use a sequence:

```php
Http::fake([
    'api.example.com/jobs/*' => Http::sequence()
        ->push(['status' => 'queued'], 200)
        ->push(['status' => 'running'], 200)
        ->push(['status' => 'completed'], 200)
        ->whenEmpty(Http::response(['status' => 'completed'], 200)),
]);
```

By default a drained sequence throws an `OutOfBoundsException`, which is a useful signal that your code made more calls than you expected. Use `whenEmpty()` when extra polling is legitimate.

### Asserting requests in Laravel

`Http::assertSent()` inspects an outgoing request; the closure receives a request object and returns a boolean.

```php
Http::assertSent(function ($request) {
    return $request->url() === 'https://api.example.com/orders'
        && $request->hasHeader('Authorization', 'Bearer secret-token')
        && $request['sku'] === 'ABC-123';
});

Http::assertNotSent(fn ($request) => str_contains($request->url(), '/admin'));

Http::assertSentCount(1);
```

`assertNotSent()` is the underrated one. It proves a request did *not* happen: that a caching layer stopped a second identical call, say, or that a feature flag correctly suppressed an outbound webhook.

### Catching stray requests

Here is the safety net I now add to every project. `Http::preventStrayRequests()` makes any real HTTP call (one you forgot to fake) throw immediately instead of silently escaping to the network.

```php
Http::preventStrayRequests();

Http::fake([
    'api.example.com/*' => Http::response(['ok' => true]),
]);

// A call to any un-faked URL now throws instead of hitting the network.
```

Drop it in a base test case or a `Pest` `beforeEach` hook and your whole suite is guaranteed offline. No more accidental calls to production because a mock pattern had a typo. If you are weighing test runners, the [Pest vs PHPUnit](/blog/pest-vs-phpunit) tradeoffs are worth a read before you standardise a base case.

## The rule that beats every mocking trick: don't mock what you don't own

Both approaches above intercept Guzzle at the transport layer. That works, but it leaks a third-party detail (Guzzle) into your tests. If you swap Guzzle for Symfony HttpClient next year, every one of those tests breaks even though your business logic never changed.

The more durable pattern is to wrap the third-party client behind an interface you own, then mock *that* interface in the bulk of your tests.

```php
interface WeatherGateway
{
    public function currentTemperature(string $city): float;
}

final class OpenWeatherGateway implements WeatherGateway
{
    public function __construct(private \GuzzleHttp\Client $client) {}

    public function currentTemperature(string $city): float
    {
        $response = $this->client->get('/weather', ['query' => ['q' => $city]]);
        $data = json_decode((string) $response->getBody(), true);

        return (float) $data['main']['temp'];
    }
}
```

Now the service that *uses* weather data depends on `WeatherGateway`, not on Guzzle. Its tests mock a tiny, stable interface:

```php
$gateway = $this->createMock(WeatherGateway::class);
$gateway->method('currentTemperature')->willReturn(21.5);

$advisor = new ClothingAdvisor($gateway);
$this->assertSame('t-shirt', $advisor->recommend('Lisbon'));
```

You still write a handful of Guzzle-level tests, the ones that prove `OpenWeatherGateway` parses the real response shape correctly. But your domain logic no longer cares how the bytes arrive. This is the same seam that makes the rest of your codebase testable; if the idea is new to you, I unpack it further in [writing testable PHP code](/blog/testable-php-code).

## Common pitfalls

- **Forgetting to prevent stray requests.** Without `preventStrayRequests()`, a mistyped fake pattern quietly hits the real API and your test still passes, until it doesn't.
- **Asserting on the response instead of the request.** Checking that you handled a `200` proves nothing about whether you sent the right payload. Assert both.
- **Putting the catch-all first in `Http::fake()`.** A leading `'*'` pattern shadows every specific rule below it.
- **Mocking Guzzle everywhere.** Coupling hundreds of tests to a library you might replace is technical debt with interest. Wrap it behind an interface.
- **Hard-coding full response bodies inline.** For anything beyond a couple of fields, load fixtures from a file so tests stay readable and the JSON stays valid.
- **Ignoring the failure paths.** Mock the timeout, the `500`, and the malformed body. Those are the cases that page you at night.

## FAQ

### Should I mock HTTP requests or use a fake sandbox server?

For unit and most feature tests, mock. Sandboxes are slow, occasionally down, and rarely let you reproduce edge-case failures on demand. Keep a small number of live integration tests behind a separate, opt-in suite so you still catch upstream contract changes.

### Can I use Http::fake() outside Laravel?

No. `Http::fake()` is part of Laravel's HTTP client. In a plain PHP or Symfony project, use Guzzle's `MockHandler` with a `HandlerStack`, or your framework's own client fakes. The wrap-behind-an-interface pattern works everywhere regardless.

### How do I test retry logic with mocked responses?

Queue the failures followed by a success. With Guzzle, push a `RequestException` (or several) into the `MockHandler` before a `200` `Response`. With Laravel, use `Http::sequence()` to push error responses ahead of the successful one, then assert the final result and the call count.

### Why does my mocked request still hit the real network?

Almost always because the URL did not match any fake pattern and no `preventStrayRequests()` guard was set. Add that guard, then check your patterns. Laravel matches on the URL string with `*` wildcards, and a subtle path or subdomain mismatch is enough to slip through.

## Wrapping up

Mocking HTTP is less about a specific helper and more about drawing a clean boundary. Reach for `MockHandler` and history middleware when you are close to Guzzle, and `Http::fake()` when you are in Laravel — both give you deterministic, offline, fast tests. Then go one level up: put a `preventStrayRequests()` guard in your base test case so nothing escapes, and wrap third-party clients behind your own interface so your domain tests survive the day you change vendors.

Start with the guard in your base test case this week. It is a two-line change, and it will surface every accidental live call you already have.