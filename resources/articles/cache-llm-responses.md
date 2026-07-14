---
name: "Caching LLM Responses to Cut API Costs"
slug: cache-llm-responses
short_description: "Learn to cache LLM responses in Laravel to reduce LLM API costs, with response caching, prompt caching, TTL choices, and the traps to avoid."
language: en
published_at: 2026-10-21 09:00:00
is_published: true
tags: [laravel, ai, caching, performance]
---

The fastest way to cache LLM responses and stop burning money is also the one most teams skip until the invoice arrives. If your app sends the same or near-identical prompts to a model over and over, you are paying full token price every time for output you already computed. That is money and latency you can claw back with a cache layer you already know how to build.

I run a small Laravel service that classifies support tickets. Roughly a third of the incoming text was effectively duplicated (canned replies, forwarded threads, the same billing question phrased five ways). Adding a cache in front of the model call cut the bill noticeably and made the endpoint feel instant on repeats. Nothing exotic. Just `Cache::remember` and a hash.

This post covers two distinct caching layers, when each one is safe, and the mistakes that quietly corrupt your results.

## Two caching layers, and why they are not the same

People say "cache the LLM" and mean one of two very different things:

- **Application-level response caching.** *You* store the model's full response in your own cache (Redis, database, file) keyed by the request. A repeat request never reaches the API.
- **Provider prompt caching.** The provider caches a reused *prefix* of your prompt on their side so repeated calls skip re-processing that chunk. The request still goes to the API; you pay less for the cached portion.

They stack. You can do both. But they solve different problems, so let's take them one at a time.

## Layer 1: Caching LLM responses in your own app

The idea is simple. Hash everything that determines the output (model, the full prompt, and generation parameters) into a cache key. Store the response under that key. On the next identical request, return the stored value.

The critical safety condition: **only cache when the same input should produce the same output.** If you ask a model for varied, sampled output and then cache it, you hand every user the same answer, which is usually wrong. Note that the newer Claude models used here (`claude-sonnet-5`, `claude-opus-4-8`) no longer accept `temperature` at all, so there is no knob to turn down; the rule reduces to a design choice on your side: cache only what you actually want identical on a repeat.

Here is the pattern with Laravel's `Http` client and `Cache::remember`, calling Claude:

```php
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function cachedCompletion(string $prompt): array
{
    $payload = [
        'model'      => 'claude-sonnet-5',
        'max_tokens' => 1024,
        'messages'   => [
            ['role' => 'user', 'content' => $prompt],
        ],
    ];

    // The cache key must cover everything that changes the output.
    $key = 'llm:' . hash('sha256', json_encode($payload));

    return Cache::remember($key, now()->addHours(24), function () use ($payload) {
        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', $payload);

        $response->throw();

        return $response->json();
    });
}
```

What is happening here:

- `json_encode($payload)` serializes the **model, prompt, and params** together, so a change to any of them produces a new key. Two requests collide in the cache only when they are genuinely identical.
- `Cache::remember` runs the closure on a miss and stores the result; on a hit it returns the stored array and never touches the network.
- The 24-hour TTL is a guess you should tune. More on that below.

One quiet bug lives in that `json_encode` call. PHP associative arrays preserve insertion order, so if you build the payload differently in different code paths, you get different JSON and a cache miss on requests that are logically the same. Normalize first (sort keys, canonical structure) if your payloads are assembled in more than one place.

### Choosing a TTL

TTL is a tradeoff between freshness and hit rate. A few rules of thumb from running this in production:

- **Deterministic, timeless answers** (classification, extraction, translation of fixed text): hours to days. The output will not change.
- **Answers that reference changing data** (anything with "current", prices, inventory): short, or do not cache at all. A stale answer here is worse than a slow one.
- **Prompts that embed a timestamp or request ID:** you cannot cache these usefully, because the key never repeats. Strip volatile fields out of the hashed input, or accept the miss.

If you already lean on Redis for other work, the same store backs this cache cleanly. See [/blog/laravel-cache-queries](/blog/laravel-cache-queries) for the store configuration side of things.

### Semantic caching (the advanced option)

Exact-match caching only fires when two prompts are byte-identical. "Reset my password" and "how do I reset my password" hash differently and both miss. **Semantic caching** tries to close that gap: embed each incoming prompt into a vector, and if a previous prompt sits within some cosine-similarity threshold, reuse its cached answer.

It is powerful and genuinely raises hit rates on free-text input. It is also where correctness goes to die if you are careless. A threshold set too loose will serve the answer for "cancel my subscription" to someone asking "change my subscription", and the two are not the same question. Treat semantic caching as a real feature with its own evaluation, not a config flag. Start strict, measure false hits against real traffic, and loosen only with evidence.

## Layer 2: Anthropic prompt caching

This one lives on the provider side. When a large chunk of your prompt is reused across many calls (a long system prompt, a big document, a fixed set of few-shot examples), you can mark a breakpoint with `cache_control` so the provider reuses that cached prefix on later requests instead of re-processing it.

You add a breakpoint on the last block of the stable prefix:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'x-api-key'         => config('services.anthropic.key'),
    'anthropic-version' => '2023-06-01',
])->post('https://api.anthropic.com/v1/messages', [
    'model'      => 'claude-opus-4-8',
    'max_tokens' => 1024,
    'system'     => [
        [
            'type'          => 'text',
            'text'          => $largeSharedContext, // the reused prefix
            'cache_control' => ['type' => 'ephemeral'],
        ],
    ],
    'messages' => [
        ['role' => 'user', 'content' => $userQuestion], // volatile, comes after
    ],
]);
```

A few things worth being precise about:

- **It is a prefix match.** The provider reuses the cached prefix only when the bytes up to your `cache_control` breakpoint are identical to a previous call. Anything before the breakpoint that changes (a timestamp in the system prompt, reordered JSON) invalidates the cache. Keep the stable content first and the volatile content after the last breakpoint.
- **Order is `tools` then `system` then `messages`.** A breakpoint on the last system block caches the tools plus system together.
- **It reduces cost and latency on the cached portion** of repeated calls. I am deliberately not quoting a discount percentage or a fixed lifetime here, because those are provider details that change; check the current pricing and TTL in the official docs rather than trusting a number from a blog post (including this one).

You can verify it is actually working. The response `usage` object reports `cache_read_input_tokens`. If that stays zero across calls you expect to hit, something in your prefix is changing between requests and quietly breaking the match.

Provider prompt caching and your own response cache are complementary. Response caching skips the call entirely on exact repeats; prompt caching cuts the cost of the calls that do go out. For the SDK-based version of these Claude calls rather than raw `Http`, see [/blog/claude-api-php](/blog/claude-api-php).

## Pitfalls that will bite you

Things I have either broken myself or watched a teammate break:

- **Caching non-deterministic output.** Cache a sampled, deliberately varied response and you freeze one random draw and serve it forever. Cache only when the input is meant to map to a stable output.
- **Leaving parameters out of the key.** If `max_tokens`, model, or system prompt changes but your key does not reflect it, you serve the wrong cached answer. Hash the whole request.
- **Non-canonical payloads.** Unsorted keys or inconsistently built arrays produce different hashes for identical requests, so your hit rate silently craters.
- **Caching per-user data under a shared key.** If the prompt includes user-specific context, that context must be part of the key, or you will leak one user's answer to another. This is a correctness *and* a privacy bug.
- **Never measuring hits.** Log hit vs miss and read `usage` fields. A cache you cannot see is a cache you cannot trust.
- **Unbounded keyspace.** Free-text prompts generate near-infinite unique keys with terrible hit rates. This is exactly where you consider semantic caching instead of exact match, or accept that some traffic just is not cacheable.

## The ROI framing

The point of all this is cutting token spend. Response caching turns a repeated paid API call into a cheap local lookup. Prompt caching shaves the cost off the calls you still make. Neither requires new infrastructure if you already run Laravel with a cache store. The engineering cost is a hash function and a TTL decision; the payoff is a smaller bill and faster responses on the traffic that repeats.

I will not put fake numbers on it, because your savings depend entirely on how repetitive your traffic is. Measure your own hit rate first. If duplicates are rare, response caching does little and you should lean on prompt caching for the shared prefix instead.

## FAQ

### How do I cache LLM responses in Laravel without stale data?

Use `Cache::remember` with a key that hashes the full request, and pick a TTL that matches how fast the underlying answer goes stale. For timeless outputs like classification, a long TTL is fine. For anything referencing current data, use a short TTL or skip caching.

### What is the difference between prompt caching and response caching?

Response caching stores the model's output in *your* cache and skips the API call entirely on exact repeats. Prompt caching happens on the *provider* side and reuses a cached prompt prefix so repeated calls are cheaper, but the call still goes out. They stack.

### When should I not cache LLM responses?

When you run the model at high temperature and want varied output, when the answer depends on real-time data, or when the prompt is unique per request so the cache never hits. In those cases caching adds complexity with no payoff, or serves wrong answers.

### Is semantic caching worth it?

It raises hit rates on free-text prompts that exact matching misses, but a loose similarity threshold serves answers to questions that only *look* similar. Start with a strict threshold, evaluate false hits against real traffic, and loosen only with evidence.

## Conclusion

Start with the layer that fits your traffic. If you send identical requests, add exact-match response caching with `Cache::remember` and a full-request hash today; it is an afternoon of work. If you reuse a large system prompt or document across many calls, add an Anthropic `cache_control` breakpoint on the stable prefix and confirm it with `cache_read_input_tokens`. Keep temperature low wherever you cache output, put every output-affecting field into the key, and log your hit rate so you actually know it is working. Do that and the next model invoice will be smaller than the last one.