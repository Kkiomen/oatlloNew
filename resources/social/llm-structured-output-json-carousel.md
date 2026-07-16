---
slug: llm-structured-output-json-carousel
type: carousel
language: en
title: "Reliable LLM JSON"
topic: ai
source_type: article
source: llm-structured-output-json
link: https://oatllo.com/llm-structured-output-json
publish_at: 2026-09-15 19:00
status: ready
formats: [post, reel]
hashtags: [llm, ai, json, api, backend]
caption: |
  "Sure! Here's the JSON you asked for:" is not JSON, and no prompt wording reliably stops it.

  Force a tool call and the answer arrives as arguments, not prose. Then validate,
  then retry with the actual error fed back. Three cheap layers.

  Full walkthrough linked in bio.

  What was the weirdest thing your model wrapped a payload in?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: ae68c05227716cadb0a45d1d9961e4850061d772
  checks:
    - tool_choice forcing, tool_use input already decoded, additionalProperties false and the two-or-three retry cap all trace to the article
    - code is real Messages API shape; input is an array, no fence stripping needed
    - hook and CTA agree, untrusted-client framing is the article's own
  notes: |
    Hook states the 3% flatly while the article hedges it as maybe 3%. It is the article's own anecdotal number, not invented, but it reads as a measured stat on the slide. Reviewer may want to soften if that bothers them.
---

## json_decode returns null on 3% of requests. Prompts will not fix it.

"Respond ONLY with valid JSON" is a nudge, not a guarantee. Under an odd input
the model still reaches for a markdown fence or a friendly preamble.

<!-- slide -->

## Force the tool. It cannot reply in prose.

```php
'tools' => [[
    'name' => 'record_order',
    'input_schema' => $schema,
]],
// The model cannot opt out into prose:
'tool_choice' => ['type' => 'tool',
    'name' => 'record_order'],
```

<!-- slide -->

## The answer is not a text block

```php
foreach ($body['content'] as $block) {
    if ($block['type'] === 'tool_use') {
        $order = $block['input']; // decoded
    }
}
```

No fences to strip. No prose to cut. `input` is already an array.

<!-- slide -->

## One line makes extra keys loud

```php
'required' => ['order_id', 'total'],
'additionalProperties' => false,
```

Without it the model can bolt on a field that quietly slips past your reads.
With it, an unexpected key becomes a validation error you can log.

<!-- slide -->

## Retry with the reason, not "try again"

```php
// Feed back WHAT failed, not "try again".
$why = 'Invalid: ' . $e->getMessage();
$messages[] = ['role' => 'user',
    'content' => $why];
```

Cap it at two or three attempts. If it fails three times, the input is the
problem and ten more calls just cost money.

<!-- slide role="cta" -->

## Treat model output like an untrusted client

Because functionally that is what it is. Tool call, then schema validation,
then a bounded retry.
