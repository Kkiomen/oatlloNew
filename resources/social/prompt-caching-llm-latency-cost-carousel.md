---
slug: prompt-caching-llm-latency-cost-carousel
type: carousel
language: en
title: "Prompt caching"
topic: ai
source_type: article
source: prompt-caching-llm-latency-cost
link: https://oatllo.com/prompt-caching-llm-latency-cost
publish_at: 2026-09-22 19:00
status: ready
formats: [post, reel]
hashtags: [llm, ai, caching, performance, api]
caption: |
  A date('c') in your system prompt means every request is byte-unique and nothing has ever cached.

  Prompt caching is a byte-exact prefix match, not a semantic one. It fails
  silently: the request succeeds, the answer is good, you pay full price forever.

  Full guide linked in bio.

  What was hiding in your prefix?
verified:
  verdict: approved
  at: 2026-07-16 07:14
  fingerprint: 0c2a558f0aef0c117019c3d64747415f780bbe70
  checks:
    - byte-exact prefix match, tools then system then messages render order, and the silent-invalidator list all trace to the article
    - cache_control ephemeral is the real field; cacheReadInputTokens is the PHP SDK camelCase the article uses, not invented
    - the sum-of-three-usage-fields point is the article's own and is correct - input_tokens is only the uncached remainder
    - no prices, no token thresholds, no lifetimes on the slides - the article deliberately withholds those and the post did not reinvent them
  notes: |
    Good restraint on aging claims. Nothing here goes stale if Anthropic changes cache pricing or TTLs before the September slot.
---

## One changed character before your marker kills the whole cache.

This is not a fuzzy semantic cache. The API hashes the exact bytes from the
start of the request up to your marker. One byte moves, the prefix is gone.

<!-- slide -->

## The marker ends the stable part

```json
{
  "system": [{
    "type": "text",
    "text": "<long stable instructions>",
    "cache_control": {"type": "ephemeral"}
  }],
  "messages": [{"role": "user", ... }]
}
```

<!-- slide -->

## Order decides what you can cache

```text
Renders in this order:
  tools -> system -> messages

Anything before the marker that moves:
  date('c')        a UUID
  per-user ID      a dynamic tool list
```

Tools render at position zero. Reorder one and the entire cache rebuilds.

<!-- slide -->

## Prove it on the second call

```php
$usage = $message->usage;

// Second call with the same prefix:
// this MUST be non-zero.
echo $usage->cacheReadInputTokens;
```

Zero across repeated calls means you have a silent invalidator. Also: your real
prompt size is the sum of all three usage fields, not `input_tokens` alone.

<!-- slide role="cta" -->

## Freeze the front, let the back move

The volatile bit goes after the marker, or gets sorted, or gets deleted if it
was doing no real work.
