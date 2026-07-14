---
name: "Getting Reliable LLM Structured Output JSON in PHP"
slug: llm-structured-output-json
short_description: "Stop parsing broken LLM JSON. A practical PHP/Laravel guide to reliable structured output using tool calling, schema validation, and retries."
language: en
published_at: 2026-08-21 09:00:00
is_published: true
tags: [llm, php, laravel, ai]
---

Getting reliable LLM structured output as JSON is one of those problems that looks trivial in a demo and then falls apart the moment real traffic hits it. You ask the model for JSON, it gives you JSON, everyone's happy. Then a week later `json_decode` returns `null` on maybe 3% of requests, your queue fills with parse errors, and you're staring at a response that starts with "Sure! Here's the JSON you asked for:" followed by a code fence.

This post is about closing that gap. I'll walk through why free-text JSON is fragile, why prompt-only formatting isn't enough on its own, and how tool calling plus schema validation plus a retry loop turns "usually works" into something you can put behind a paid feature. Examples are in PHP with Guzzle and the Laravel HTTP client, but the pattern is portable.

## Why free-text JSON from an LLM is unreliable

An LLM generates text token by token. When you ask for JSON in the prompt, you're asking a text predictor to *happen* to produce a string that also parses as valid JSON. Most of the time it does. The failures are the problem, and they're annoyingly varied:

- **Chatty wrappers.** "Here is the JSON:" before the object, or a "Let me know if you need anything else!" after it.
- **Markdown fences.** The response comes wrapped in ` ```json ... ``` `, so a naive `json_decode` on the raw body fails.
- **Trailing commas or comments.** Valid in JavaScript, not in JSON.
- **Truncation.** You hit `max_tokens` mid-object and the closing braces never arrive.
- **Type drift.** A field you expect to be a number arrives as `"42"`, or a boolean shows up as the string `"true"`.

None of these are exotic. They show up naturally as the input text varies. The lesson I keep relearning: never trust that model output is well-formed just because it looked fine in testing. Treat it like input from an untrusted client, because functionally that's what it is.

## Prompt-only formatting: useful, not sufficient

The first instinct is to fix this with wording. "Respond ONLY with valid JSON. Do not include markdown. Do not add explanations." This genuinely helps, and you should still do it, but it's a nudge, not a guarantee. The model can still ignore it under an unusual input, a long conversation, or a higher temperature.

A couple of prompt-level habits that pay off:

- Show the exact shape you want with a small example object in the system prompt.
- Set `temperature` low (0 or close to it) for extraction and classification tasks. You want determinism, not creativity.
- Keep `max_tokens` high enough that the object can't get cut off.

Here's a prompt-only baseline against the Anthropic Messages API using Guzzle. It works, but notice how much we lean on hope:

```php
<?php

use GuzzleHttp\Client;

$client = new Client();

$response = $client->post('https://api.anthropic.com/v1/messages', [
    'headers' => [
        'x-api-key'         => getenv('ANTHROPIC_API_KEY'),
        'anthropic-version' => '2023-06-01',
        'content-type'      => 'application/json',
    ],
    'json' => [
        'model'      => 'claude-sonnet-5',
        'max_tokens' => 1024,
        'system'     => 'You extract data. Respond with a single JSON object and nothing else.',
        'messages'   => [
            ['role' => 'user', 'content' => 'Order #A19 from Jane Doe, total 49.90 EUR, status shipped.'],
        ],
    ],
]);

$body = json_decode((string) $response->getBody(), true);
$text = $body['content'][0]['text'] ?? '';

$data = json_decode($text, true); // may be null if the model added prose or fences
```

The `content[0]['text']` path is where the model's text lives. The weak point is that last line: we're parsing free text and praying. Let's make it deterministic.

## Schema-enforced JSON with tool calling

The robust way to force JSON out of Claude is to define a **tool** with a JSON Schema and let the model "call" it. Instead of writing prose, the model fills in the tool's `input_schema`, and you read the arguments back as a structured object. This is the same function-calling mechanism most providers expose; the field names differ, the idea is identical.

You do two things: pass a `tools` array with an `input_schema`, and set `tool_choice` to force that specific tool so the model can't opt out and reply with plain text.

```php
<?php

use GuzzleHttp\Client;

$client = new Client();

$schema = [
    'type'       => 'object',
    'properties' => [
        'order_id' => ['type' => 'string'],
        'customer' => ['type' => 'string'],
        'total'    => ['type' => 'number'],
        'currency' => ['type' => 'string'],
        'status'   => [
            'type' => 'string',
            'enum' => ['pending', 'shipped', 'delivered', 'cancelled'],
        ],
    ],
    'required'             => ['order_id', 'customer', 'total', 'currency', 'status'],
    'additionalProperties' => false,
];

$response = $client->post('https://api.anthropic.com/v1/messages', [
    'headers' => [
        'x-api-key'         => getenv('ANTHROPIC_API_KEY'),
        'anthropic-version' => '2023-06-01',
        'content-type'      => 'application/json',
    ],
    'json' => [
        'model'      => 'claude-sonnet-5',
        'max_tokens' => 1024,
        'tools'      => [[
            'name'         => 'record_order',
            'description'  => 'Record a parsed order.',
            'input_schema' => $schema,
        ]],
        'tool_choice' => ['type' => 'tool', 'name' => 'record_order'],
        'messages'    => [
            ['role' => 'user', 'content' => 'Order #A19 from Jane Doe, total 49.90 EUR, status shipped.'],
        ],
    ],
]);

$body = json_decode((string) $response->getBody(), true);
```

Now the answer isn't in a text block. It's in a `tool_use` block, and its `input` is already a decoded structure:

```php
<?php

$order = null;

foreach ($body['content'] as $block) {
    if (($block['type'] ?? null) === 'tool_use' && $block['name'] === 'record_order') {
        $order = $block['input']; // associative array matching the schema
        break;
    }
}
```

This removes an entire category of bugs. No fences, no "Sure, here you go", no prose to strip. The model is steered to produce arguments that fit the schema you declared. It's the difference between asking someone to write an address on a blank page versus handing them a form with labeled boxes.

One honest caveat: the schema strongly shapes the output, but you should still treat it as advisory rather than a hard contract. Enums usually hold. Occasionally a `number` field can arrive in a way you didn't expect, or a model on an odd input omits something it shouldn't. So we validate anyway.

## Validating the structure against a schema

Forcing a tool call gets you 90% of the way. Validation covers the rest and, just as importantly, it gives you a clean failure signal you can act on instead of a vague `null`.

For simple cases you can hand-roll checks. For anything with nesting, a real JSON Schema validator is worth the dependency. `justinrainbow/json-schema` is a well-established PHP option:

```php
<?php

use JsonSchema\Validator;

function validateOrder(array $data, object $schema): array
{
    $validator = new Validator();
    // Validator works on objects, so round-trip through json_encode/decode.
    $payload = json_decode(json_encode($data));

    $validator->validate($payload, $schema);

    if ($validator->isValid()) {
        return $data;
    }

    $errors = array_map(
        fn ($e) => sprintf('%s: %s', $e['property'], $e['message']),
        $validator->getErrors()
    );

    throw new \RuntimeException('Schema validation failed: ' . implode('; ', $errors));
}
```

The `additionalProperties => false` in the schema matters more than it looks. Without it, the model can bolt on an extra field that quietly slips past your reads. With it, unexpected keys become a loud validation error you can log and handle.

If you're extracting into a Laravel app, this is also the moment to run the data through Laravel's `Validator` facade with your normal rules, or to hydrate a DTO. The LLM produced a candidate; your app decides whether it's acceptable.

## Retry on invalid: the safety net

Even with tool calling and validation, you'll see the occasional bad response. The fix is boring and effective: catch the failure, feed the error back to the model, and try again a small number of times.

Here's the loop using Laravel's HTTP client. Note how a validation failure becomes a follow-up user message describing what was wrong:

```php
<?php

use Illuminate\Support\Facades\Http;

function extractOrder(string $input, object $schema, int $maxAttempts = 3): array
{
    $messages = [
        ['role' => 'user', 'content' => $input],
    ];

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $response = Http::withHeaders([
            'x-api-key'         => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'       => 'claude-sonnet-5',
            'max_tokens'  => 1024,
            'temperature' => 0,
            'tools'       => [[
                'name'         => 'record_order',
                'input_schema' => (array) json_decode(json_encode($schema), true),
            ]],
            'tool_choice' => ['type' => 'tool', 'name' => 'record_order'],
            'messages'    => $messages,
        ]);

        $content = $response->json('content', []);
        $toolUse = collect($content)->firstWhere('type', 'tool_use');

        try {
            if (! $toolUse) {
                throw new \RuntimeException('No tool_use block returned.');
            }

            return validateOrder($toolUse['input'], $schema);
        } catch (\RuntimeException $e) {
            if ($attempt === $maxAttempts) {
                throw $e;
            }

            // Give the model the previous (bad) output plus the reason it failed.
            $messages[] = ['role' => 'assistant', 'content' => $content];
            $messages[] = [
                'role'    => 'user',
                'content' => 'That did not validate: ' . $e->getMessage() . ' Please correct it.',
            ];
        }
    }

    throw new \RuntimeException('Exhausted retries for structured extraction.');
}
```

A few field notes from running loops like this:

- **Cap the attempts.** Two or three is plenty. If it fails three times, the input is probably the problem, and burning ten calls won't save you. It'll just cost money.
- **Feed back the specific error.** "That did not validate: status: must be one of pending, shipped..." works far better than a generic "try again". The model needs to know what to fix.
- **Log every retry.** If your retry rate climbs, your schema or prompt is drifting from reality, and that's a signal worth watching.
- **Separate transient failures from parse failures.** A 429 or 529 is a network retry with backoff, a different concern from invalid JSON. If you already handle background work, the patterns in [Laravel retry for failed jobs](/blog/laravel-retry-failed-jobs) apply cleanly here.

## The provider-agnostic version of this

Tool calling is the strongest lever, but the underlying recipe doesn't depend on any one vendor:

1. Define a schema for what you want.
2. Ask the model for it, using structured-output features if the provider has them.
3. Parse defensively. If the body is wrapped in fences, strip them before decoding.
4. Validate against the schema.
5. On failure, retry with the error fed back, up to a small limit.

That sequence holds whether you're on Claude, another hosted model, or something self-hosted that only speaks plain text. When you don't have tool calling, steps 3 and 4 do the heavy lifting: extract the first `{...}` span from the text, decode it, and validate. It's less reliable than a forced tool call, but the retry loop absorbs a lot of the noise.

If you're wiring Claude into PHP for the first time, the setup basics live in [calling the Claude API from PHP](/blog/claude-api-php), and caching repeated extractions is covered in [caching queries in Laravel](/blog/laravel-cache-queries).

## FAQ

### Why does my LLM sometimes return JSON wrapped in markdown fences?

Because it was trained on a lot of markdown where code and JSON are shown inside fences, so that formatting is a natural completion. Forcing a tool call sidesteps it entirely, since the output arrives as tool arguments rather than a text block. If you're stuck with plain-text responses, strip a leading ` ```json ` and trailing ` ``` ` before decoding, and validate what's left.

### Is tool calling better than just asking for JSON in the prompt?

For reliability, yes. Prompt instructions are a suggestion the model can ignore under pressure. A forced `tool_choice` constrains generation toward your `input_schema`, which removes prose wrappers and fences. Keep the prompt instructions too, though. They're cheap and they reinforce the behavior.

### What temperature should I use for structured output?

For extraction, classification, and other "there is a correct answer" tasks, use 0 or something very close. You want the same input to produce the same structure. Higher temperature is for creative generation, which is the opposite of what you want when a downstream `json_decode` depends on the shape.

### How many times should I retry on invalid JSON?

Two or three attempts. Most valid-but-malformed responses get fixed on the second try once you feed the error back. Beyond that you're usually looking at a genuinely ambiguous input, and more retries just add latency and cost without changing the outcome.

## Wrapping up

Reliable LLM structured output isn't one trick, it's a short chain of cheap defenses. Force the shape with tool calling and a JSON Schema. Validate the result so unexpected keys and wrong types fail loudly. Wrap the whole thing in a bounded retry that tells the model exactly what went wrong. Each layer is small; together they turn a flaky demo into something you can leave running unattended.

Start with the tool-calling example above, add the validator, then add the retry loop only once you've seen a real failure in your logs. Build it in that order and you'll understand each layer instead of copy-pasting a black box, which is exactly what you want when a parse error wakes you up at 2 a.m.