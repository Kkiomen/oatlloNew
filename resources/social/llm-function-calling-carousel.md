---
slug: llm-function-calling-carousel
type: carousel
language: en
title: "Function calling"
topic: ai
source_type: article
source: llm-function-calling
link: https://oatllo.com/llm-function-calling
publish_at: 2026-09-08 19:00
status: ready
formats: [post, reel]
hashtags: [ai, llm, php, claude, api]
caption: |
  The first tool I wired up "called" a weather API that existed only in my prompt.

  It invented a plausible temperature and moved on. Function calling is the fix:
  the model asks, you run it, you stay in control. Full guide in bio.

  What was your first real tool?
---

## The model never runs your code. It just asks, in JSON, then waits.

It emits a block naming the function and its arguments. You execute it and feed
the result back. Every side effect stays yours to gate.

<!-- slide -->

## A tool is three fields

```json
{
  "name": "get_weather",
  "description": "Call this whenever the user
    asks about conditions for a named place.",
  "input_schema": { "type": "object" }
}
```

The description is your only lever over *when* it reaches for the tool. Be
concrete about the trigger, not the mechanics.

<!-- slide -->

## Do not assume the first block is text

```json
{
  "stop_reason": "tool_use",
  "content": [
    { "type": "text", "text": "Checking." },
    { "type": "tool_use", "id": "toolu_01A" }
  ]
}
```

One turn mixes text and `tool_use` blocks. Filter by `type`, and parse `input`
as JSON - never string-match the serialized form.

<!-- slide -->

## It is a loop, not a follow-up request

```php
if ($data['stop_reason'] !== 'tool_use') {
    break; // end_turn, the answer is here
}
```

Sometimes the model wants another call first. Check `stop_reason` every time,
append the assistant turn, send the results back.

<!-- slide -->

## When your function throws, still answer

```php
['type' => 'tool_result',
 'tool_use_id' => $block['id'],
 'is_error' => true]
```

Drop the block and you leave an unanswered `tool_use` - the request breaks. Send
the error and the model adapts or asks.

<!-- slide role="cta" -->

## The API is stateless, so it all travels every call

Tools, system prompt, full history - resent on every request. That is why prompt
caching matters on a big tool set. Start with one read-only tool. Guide in bio.
