---
name: "Prompt Caching: Cutting LLM Latency and Cost"
slug: prompt-caching-llm-latency-cost
short_description: "A practical guide to prompt caching with the Claude API: cache_control, cache-hit verification, and the mistakes that quietly break it."
language: en
published_at: 2027-02-05 09:00:00
is_published: true
tags: [llm, prompt-caching, claude-api, performance]
---

If you send the same long system prompt on every request, you are paying to reprocess it every single time. Prompt caching fixes that. It lets you mark a stable chunk at the front of your request so the model skips re-reading it on the next call, which cuts both latency and the input-token bill for that chunk. This post walks through how it actually works on the Claude API, the code to turn it on, and the handful of quiet mistakes that make it silently do nothing.

I have shipped this into a couple of RAG-heavy services now, and the pattern that trips people up is always the same one. So let's get the mental model right first, then the code.

## What prompt caching actually caches

The single fact everything else follows from: **prompt caching is a prefix match.** The API hashes the exact bytes of your request from the start up to a marker you place, and reuses the cached work if those bytes match a previous request.

Requests are rendered in a fixed order:

- `tools` first
- then `system`
- then `messages`

So the cache is a prefix of that whole rendered blob. A marker on the last system block caches your tools and your system prompt together. A marker on the last message caches the conversation so far.

The consequence is blunt: **any byte change anywhere before your marker invalidates everything after it.** Change one character in the system prompt and the cache for that request is gone. This is not a fuzzy semantic cache. It is byte-for-byte.

That is also the difference between this and ordinary app-level response caching, where you store a full answer keyed by the input and return it verbatim. Prompt caching still runs the model and still generates a fresh completion. It only skips the *prefill* work on the cached prefix. If you want the "same question, same stored answer" behavior instead, that is a separate technique, covered in [caching LLM responses](/blog/cache-llm-responses). The two stack well: response cache for exact repeats, prompt cache for the shared context underneath everything else.

## Turning it on

The mechanism is one field: `cache_control: {"type": "ephemeral"}` on a content block. You can put it on a `system` text block, a tool definition, or a message content block.

Here is the raw request shape against the Messages endpoint (`POST https://api.anthropic.com/v1/messages`, with the usual `x-api-key` and `anthropic-version: 2023-06-01` headers):

```json
{
  "model": "claude-sonnet-5",
  "max_tokens": 1024,
  "system": [
    {
      "type": "text",
      "text": "<your long, stable instructions and reference material>",
      "cache_control": {"type": "ephemeral"}
    }
  ],
  "messages": [
    {"role": "user", "content": "Summarize the incident from the attached log."}
  ]
}
```

The marker sits on the **last block of the stable prefix**. Everything up to and including it becomes the cache key. The volatile user message comes after, so it never enters the key.

In a PHP project (the Anthropic PHP SDK uses camelCase keys, and the same request via the SDK is documented in the [Claude API PHP guide](/blog/claude-api-php)):

```php
$message = $client->messages->create(
    model: 'claude-sonnet-5',
    maxTokens: 1024,
    system: [
        [
            'type' => 'text',
            'text' => $largeSystemPrompt,   // stable across requests
            'cacheControl' => ['type' => 'ephemeral'],
        ],
    ],
    messages: [
        ['role' => 'user', 'content' => $userQuestion],  // varies every call
    ],
);
```

That is the whole feature at the API level. The hard part is not writing it. The hard part is making sure it keeps hitting.

## Prove it works before you trust it

Every response reports what the cache did, in the `usage` object:

- `cache_creation_input_tokens` — tokens written to the cache on this request (you paid a small write premium)
- `cache_read_input_tokens` — tokens served from the cache on this request (billed far below the base input rate)
- `input_tokens` — the uncached remainder, at full price

```php
$usage = $message->usage;

echo "written to cache: {$usage->cacheCreationInputTokens}\n";
echo "read from cache:  {$usage->cacheReadInputTokens}\n";
echo "full price:       {$usage->inputTokens}\n";
```

On the first request with a fresh prefix you expect a non-zero `cache_creation_input_tokens` and a zero read. On the second identical-prefix request you expect the mirror: a large `cache_read_input_tokens` and a small `input_tokens`. One more thing that surprises people: `input_tokens` is only the *uncached* part. Your real prompt size is the sum of all three fields. If an agent ran for an hour and `input_tokens` reads 4,000, the rest was served from cache. Check the sum, not the one field.

**If `cache_read_input_tokens` stays at zero across repeated calls, caching is not working and you have a silent invalidator.** That is the failure mode to hunt.

## The invalidators that get everyone

None of these throw an error. The request succeeds, you get a good answer, and you quietly pay full price forever. Grep your prompt-building code for these:

- **A timestamp in the prefix.** `date('c')`, `now()`, "Current time: ..." baked into the system prompt. Every request is byte-unique, so nothing ever matches.
- **A UUID or request ID early in the content.** Same problem, different source.
- **Non-deterministic JSON serialization.** Serializing a map or associative array whose key order is not stable produces different bytes each time. Sort keys before you interpolate.
- **A per-user or per-session ID interpolated into the system prompt.** This gives every user their own prefix and kills cross-request sharing. Push that value later, into the messages.
- **A tool set that varies per request.** Tools render at position zero. Add, remove, or reorder a tool and the entire cache rebuilds. If your tool list is dynamic, that alone can explain a permanent zero hit rate. If you are working with function calling, keep the tool array stable and sorted; more on that in the [LLM function calling](/blog/llm-function-calling) guide.
- **Switching models mid-conversation.** Caches are scoped to a model. A fallback that swaps `claude-sonnet-5` for another model starts cold.

The fix is nearly always the same move: take the volatile bit out of the prefix and put it after your marker, or make it deterministic, or delete it if it was not doing any real work.

## A few limits worth knowing

A handful of behavioral constraints shape how you design around this. I am giving you the shape, not exact figures, because the thresholds and lifetimes shift over time. **Check the current Anthropic docs for the precise numbers before you tune against them.**

- **There is a minimum cacheable prefix.** Below some model-dependent token count, the marker is simply ignored and nothing caches, with no error. If your prefix is tiny, caching will not fire. Larger prefixes are where the whole feature pays off anyway.
- **A cache entry has a short lifetime and is refreshed on each hit.** If your traffic has gaps longer than that window, entries expire between requests and you pay the write cost again. There is also a longer-lived option at a higher write premium for bursty or spread-out traffic. Which one wins depends on your request cadence, so measure.
- **You get a small number of cache breakpoints per request**, not unlimited. Enough to cache tools, a system prompt, and a conversation turn, but plan placement rather than sprinkling markers.
- **Cost shape:** reads are much cheaper than base input tokens, writes carry a modest premium over base input. The break-even is low. If a cached prefix is reused even a couple of times, you come out ahead. Again, exact multipliers live in the docs.

## Where it earns its keep

Prompt caching is worth wiring in when you have a large, stable chunk reused across many calls:

- **Long system prompts.** Detailed instructions, style guides, few-shot examples that never change between users.
- **RAG document context.** Retrieved passages that stay constant across a batch of questions about the same source. This is the biggest win in a [PHP RAG pipeline](/blog/rag-pipeline-php), where you often ask several questions against one large retrieved context.
- **Multi-turn conversations.** Cache the growing history so each turn only pays full price for the newest message.
- **Tool-heavy calls.** A big, deterministic tool schema reused on every agentic step.

The design rule that ties it together: **freeze the front of your prompt, and let the volatile content live at the back.** Get the ordering right and caching mostly works for free. Get it wrong and no amount of `cache_control` markers will save you.

## FAQ

### Does prompt caching change the model's output?

No. It only skips reprocessing the cached prefix during prefill. The model still generates a fresh completion each call, so the same request can still produce different answers. If you want identical stored answers for identical inputs, use response caching instead.

### Why is my cache_read_input_tokens always zero?

Something in your prefix changes between requests. The usual culprits are a timestamp, a UUID, a per-user ID interpolated into the system prompt, unsorted JSON, or a tool list that varies. Diff the exact rendered bytes of two consecutive requests and you will find the difference.

### Can I cache the conversation history in a chatbot?

Yes. Put the cache marker on the last block of the most recent turn. Each new request reuses the entire prior conversation as a cached prefix, and only the newest user message is processed at full price.

### How long does a cached prefix stay alive?

There is a short default lifetime that refreshes on every hit, plus a longer-lived option at a higher write cost for traffic with gaps. The exact durations change, so confirm them in the current Anthropic documentation and match the choice to how frequently your requests arrive.

## Wrapping up

Prompt caching is one field, `cache_control: {"type": "ephemeral"}`, placed at the end of the stable part of your request. The value is real and immediate: repeated large prefixes stop costing full price and start returning faster. But it is a byte-exact prefix match, so the entire game is keeping that prefix identical across requests. Add the caching, then immediately watch `cache_read_input_tokens` on your second call. If it is non-zero, you are done. If it is zero, you have an invalidator to hunt, and it is almost always a timestamp, an ID, or a shifting tool list sitting where it does not belong.