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
verified:
  verdict: approved
  at: 2026-07-16 06:54
  fingerprint: e6e019d067dc96bbfd10ac887c91405c49330de2
  checks:
    - Cache::remember + sha256 of json_encode(payload) matches the article; now()->addDay() is valid Carbon and equivalent to the article's addHours(24)
    - cache_control ephemeral on a system block verified against the Anthropic API reference, not just the article - correct
    - usage.cache_read_input_tokens is the real field name; 'zero means your prefix is changing' is the documented diagnostic
    - byte-exact prefix match + timestamp-invalidates-everything confirmed against the prompt-caching docs
    - json_encode insertion-order bug and the shared-key privacy leak both trace to the article's pitfall list
  notes: |
    Post names no model and quotes no discount percentage, so nothing here ages - good, since the article does name claude-sonnet-5 / claude-opus-4-8. Slide 1 compresses the article's 'a third of incoming text was duplicated (canned replies, forwarded threads, same billing question five ways)' into 'a third were the same question' - directionally true, slightly tighter than the source.
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

