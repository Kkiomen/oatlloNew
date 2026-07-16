---
slug: cache-llm-responses-carousel
type: carousel
language: en
title: "Cache LLM responses"
topic: ai
source_type: article
source: cache-llm-responses
link: https://oatllo.com/cache-llm-responses
publish_at: 2026-08-18 19:00
status: ready
formats: [post, reel]
hashtags: [ai, laravel, php, caching, claude]
caption: |
  A third of our support tickets were the same question, phrased five ways.

  We paid full token price every time for output we already had. Cache::remember
  plus a hash of the whole payload turned repeats into a local lookup.

  Full write-up linked in bio.

  Do you actually know your cache hit rate?
---

## A third of our tickets were the same question

Five different phrasings, one answer. We paid full token price on every one of
them for output we had already computed.

<!-- slide -->

## Hash the request. Cache the answer.

```php
$key = 'llm:'.hash('sha256', json_encode($p));

return Cache::remember($key, now()->addDay(),
    fn () => $this->callClaude($p));
```

The key must cover model, prompt and params. Change any of them, get a new key.

<!-- slide -->

## One quiet bug lives in json_encode

PHP arrays keep insertion order. Build the payload differently in two code
paths and two logically identical requests hash differently. Your hit rate
craters and nothing tells you.

<!-- slide -->

## The other layer lives at the provider

```php
'system' => [[
  'type' => 'text',
  'text' => $sharedContext,
  'cache_control' => ['type' => 'ephemeral'],
]],
```

Stable content first, volatile content after the breakpoint.

<!-- slide -->

## It is a byte-for-byte prefix match

A timestamp in the system prompt invalidates the whole thing. Check
`usage.cache_read_input_tokens` in the response: still zero means your prefix
is changing between calls.

<!-- slide role="cta" -->

## Never cache user context under a shared key

That is not a stale-cache bug. That is one user reading another user's answer.

