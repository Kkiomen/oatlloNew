---
name: "LLM Function Calling: Building AI Tools with the Claude API"
slug: llm-function-calling
short_description: "A hands-on guide to LLM function calling: define tools, run the tool-use loop, and build real AI tools in PHP with the Claude API."
language: en
published_at: 2026-11-04 09:00:00
is_published: true
tags: [llm, php, ai, claude, api]
---

The first time I wired a language model up to a real function, I expected magic and got a `KeyError`. The model had happily "called" a weather tool that existed only in my prompt, invented a plausible-looking temperature, and moved on. That is the whole problem **LLM function calling** solves: instead of the model pretending it ran your code, it hands you a structured request to run it, waits for the real answer, and continues from there.

This guide walks through function calling end to end with the Claude API: how you describe a tool, what the model sends back, and the loop you write to close the circle. Examples are in PHP (Laravel's `Http` client), because that is what I reach for, and the wire format is identical whatever language you use.

## What function calling actually is

Function calling (Anthropic calls it *tool use*, and the two terms are interchangeable) lets a model decide, mid-conversation, that it needs something it cannot do on its own. Look up an order. Query a database. Send an email. Hit a currency API.

The model does not execute anything. It emits a JSON object naming the function and its arguments. Your code runs the function, feeds the result back, and the model weaves that result into its next reply.

A few things follow from that design:

- The model never touches your systems directly. You stay in control of every side effect.
- Arguments arrive as validated JSON, not free text you have to parse out of a sentence.
- You can gate anything dangerous behind a confirmation step before executing it.

If all you want is a structured JSON *answer* (no external calls), you probably want [structured output](/blog/llm-structured-output-json) instead. Function calling is specifically for the round trip out to your code and back.

## Defining a tool

A tool is three fields: a `name`, a `description`, and an `input_schema` written as JSON Schema. The description is not decoration. It is the single most important lever you have over *when* the model reaches for the tool, so be concrete about the trigger, not just the mechanics.

Here is a tool for looking up the weather:

```json
{
  "name": "get_weather",
  "description": "Get the current weather for a location. Call this whenever the user asks about temperature, conditions, or forecast for a named place.",
  "input_schema": {
    "type": "object",
    "properties": {
      "location": {
        "type": "string",
        "description": "City and country, e.g. 'Kraków, Poland'"
      },
      "unit": {
        "type": "string",
        "enum": ["celsius", "fahrenheit"],
        "description": "Temperature unit"
      }
    },
    "required": ["location"]
  }
}
```

The `enum` pins `unit` to two allowed values. Mark only genuinely-required fields in `required`, and leave the rest optional so the model can omit them.

## The first request

You send tools in a top-level `tools` array on `POST https://api.anthropic.com/v1/messages`. Three headers are mandatory: `x-api-key`, `anthropic-version: 2023-06-01`, and `content-type: application/json`.

```php
use Illuminate\Support\Facades\Http;

$tools = [[
    'name' => 'get_weather',
    'description' => "Get the current weather for a location. Call this whenever the user asks about temperature, conditions, or forecast for a named place.",
    'input_schema' => [
        'type' => 'object',
        'properties' => [
            'location' => ['type' => 'string', 'description' => "City and country"],
            'unit' => ['type' => 'string', 'enum' => ['celsius', 'fahrenheit']],
        ],
        'required' => ['location'],
    ],
]];

$response = Http::withHeaders([
    'x-api-key' => config('services.anthropic.key'),
    'anthropic-version' => '2023-06-01',
    'content-type' => 'application/json',
])->post('https://api.anthropic.com/v1/messages', [
    'model' => 'claude-sonnet-5',
    'max_tokens' => 1024,
    'tools' => $tools,
    'messages' => [
        ['role' => 'user', 'content' => "What's the weather in Kraków right now?"],
    ],
]);

$data = $response->json();
```

An optional `tool_choice` field steers the decision. Leave it off (the default is `auto`, meaning the model decides), set `{"type": "any"}` to force *some* tool, or `{"type": "tool", "name": "get_weather"}` to force one specific tool. I leave it on `auto` for anything conversational and only force a tool when the whole request exists to call it.

## Reading the response

When the model wants a tool, the response comes back with `stop_reason: "tool_use"` and one or more `tool_use` blocks in `content`. Each block carries an `id`, a `name`, and an already-parsed `input` object:

```json
{
  "stop_reason": "tool_use",
  "content": [
    {
      "type": "text",
      "text": "Let me check the current conditions for you."
    },
    {
      "type": "tool_use",
      "id": "toolu_01A9pk3fB2xY",
      "name": "get_weather",
      "input": { "location": "Kraków, Poland", "unit": "celsius" }
    }
  ]
}
```

Note that `content` mixes a `text` block and a `tool_use` block. Do not assume the first block is always text; filter by `type`. And parse `input` as JSON; never string-match against the serialized form.

## Closing the loop

Now you run the actual function and send its result back. The result goes in a **new user message** as a `tool_result` block. Two fields matter: `tool_use_id` (which must match the `id` from the block you are answering) and `content` (a string, whatever your function returned).

You also have to echo the assistant's previous turn back verbatim, because the API is stateless: it reconstructs the whole conversation from what you send.

```php
$toolUse = collect($data['content'])
    ->firstWhere('type', 'tool_use');

// Your real implementation lives here.
$result = weatherLookup($toolUse['input']['location']);

$messages = [
    ['role' => 'user', 'content' => "What's the weather in Kraków right now?"],
    ['role' => 'assistant', 'content' => $data['content']],
    ['role' => 'user', 'content' => [[
        'type' => 'tool_result',
        'tool_use_id' => $toolUse['id'],
        'content' => $result,   // e.g. "8°C, light rain"
    ]]],
];

$final = Http::withHeaders([
    'x-api-key' => config('services.anthropic.key'),
    'anthropic-version' => '2023-06-01',
    'content-type' => 'application/json',
])->post('https://api.anthropic.com/v1/messages', [
    'model' => 'claude-sonnet-5',
    'max_tokens' => 1024,
    'tools' => $tools,
    'messages' => $messages,
])->json();
```

This second call usually comes back with `stop_reason: "end_turn"` and a plain text answer that folds in your real data: *"It's 8°C and lightly raining in Kraków right now — bring a jacket."*

Usually. Sometimes the model wants another tool call first. That is why you loop.

## The tool-use loop, step by step

The pattern that survives contact with production is a loop, not a single follow-up request. Here is the whole cycle:

1. Send the user message plus your `tools` array.
2. Check `stop_reason`. If it is `end_turn`, you are done: return the text.
3. If it is `tool_use`, pull every `tool_use` block out of `content`.
4. Execute each one and collect a matching `tool_result` block for each `id`.
5. Append the assistant turn, then a single user turn holding all the results.
6. Send it back and go to step 2.

In PHP that collapses to a `while`:

```php
$messages = [
    ['role' => 'user', 'content' => "Compare the weather in Kraków and Gdańsk."],
];

while (true) {
    $data = Http::withHeaders([
        'x-api-key' => config('services.anthropic.key'),
        'anthropic-version' => '2023-06-01',
        'content-type' => 'application/json',
    ])->post('https://api.anthropic.com/v1/messages', [
        'model' => 'claude-sonnet-5',
        'max_tokens' => 1024,
        'tools' => $tools,
        'messages' => $messages,
    ])->json();

    if ($data['stop_reason'] !== 'tool_use') {
        break;   // end_turn — final answer is in $data['content']
    }

    $messages[] = ['role' => 'assistant', 'content' => $data['content']];

    $results = [];
    foreach ($data['content'] as $block) {
        if ($block['type'] !== 'tool_use') {
            continue;
        }
        $results[] = [
            'type' => 'tool_result',
            'tool_use_id' => $block['id'],
            'content' => runTool($block['name'], $block['input']),
        ];
    }

    // All results from one turn go back in a single user message.
    $messages[] = ['role' => 'user', 'content' => $results];
}
```

Two details that bite people. First, when a single turn contains multiple `tool_use` blocks, return **all** of their results in one user message — splitting them across separate messages quietly trains the model to stop batching. Second, if a function fails, still return a `tool_result`, but add `"is_error": true` and a plain-language explanation. The model reads that and adapts instead of hanging.

For anything user-facing you will probably want to [stream the response](/blog/stream-llm-response) rather than block on each round trip, but the loop logic is the same underneath.

## Picking a model

Any current Claude model does function calling. My rough rule: `claude-sonnet-5` is the sensible default for tool-heavy production work, `claude-opus-4-8` when the reasoning between calls is hard and correctness beats cost, and `claude-haiku-4-5-20251001` for high-volume, simple dispatching. Use the exact ID strings; the API rejects invented date suffixes.

If you would rather not hand-write the loop at all, the official PHP SDK ships a tool runner that drives it for you; I cover setup in the [Claude API with PHP](/blog/claude-api-php) walkthrough.

## FAQ

**Is "function calling" the same as "tool use"?**
Yes. "Function calling" is the industry term; Anthropic's API and docs call the same mechanism "tool use." The `tools` array, `tool_use` blocks, and `tool_result` blocks are what you actually work with.

**Can the model call several functions in one turn?**
It can. A single response may hold multiple `tool_use` blocks. Run them (concurrently if they are independent), then send every `tool_result` back in one user message keyed by `tool_use_id`.

**What happens if my function throws?**
Return the `tool_result` anyway with `"is_error": true` and a short message describing what went wrong. Dropping the block leaves an unanswered `tool_use` and the request breaks; a proper error block lets the model recover or ask for clarification.

**Do I have to resend the tool definitions every request?**
Yes. The API is stateless, so `tools`, the system prompt, and the full message history all travel on every call. For large, stable tool sets, prompt caching keeps that from getting expensive.

## Wrapping up

Function calling is less about the model and more about the loop you build around it. Describe your tools clearly, check `stop_reason` on every response, execute what the model asks for, and feed the real results back until you get `end_turn`. Once that cycle is solid, adding a new capability is just another entry in the `tools` array — and the model figures out when to use it on its own. Start with one read-only tool, get the round trip working, then grow from there.