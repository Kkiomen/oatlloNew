---
name: "Claude API PHP: A Practical Guide to Calling Anthropic from PHP"
slug: claude-api-php
short_description: "Learn how to call the Claude API in PHP: keys, requests, responses, streaming, and a clean Laravel integration with runnable code."
language: en
published_at: 2026-07-31 09:00:00
is_published: true
tags: [php, laravel, claude, api, anthropic]
---

Wiring the **Claude API PHP** side of an app is one of those tasks that looks intimidating until you've done it once. It's a single HTTP endpoint, three headers, and a JSON body. That's it. Once that clicks, everything else - streaming, retries, moving the call into a Laravel service - is just plumbing.

Here's the path: get a key, fire a first request from the shell, parse what comes back, stream a longer response, then fold the call into a Laravel service the way you'd actually ship it. Every sample is runnable. I've flagged the spots that cost me an hour the first time so you don't lose the same one.

## Getting an API key

You authenticate to Anthropic with an API key. Grab one from the [Anthropic Console](https://console.anthropic.com), then treat it like any other secret.

A few ground rules I follow on every project:

- **Never** hardcode the key in source. It ends up in git history and you'll be rotating it at 2am.
- Put it in an environment variable. In Laravel that means a line in `.env`:

```bash
ANTHROPIC_API_KEY=sk-ant-your-real-key-here
```

- Add `.env` to `.gitignore` (Laravel does this for you) and document the variable in `.env.example` with a placeholder.

For quick shell testing, export it into your session so it doesn't leak into your command history:

```bash
export ANTHROPIC_API_KEY="sk-ant-your-real-key-here"
```

## Making a first request

Before touching PHP, prove the key works with `curl`. If this fails, no amount of PHP will save you - and you've cut the surface area in half.

```bash
curl https://api.anthropic.com/v1/messages \
  --header "x-api-key: $ANTHROPIC_API_KEY" \
  --header "anthropic-version: 2023-06-01" \
  --header "content-type: application/json" \
  --data '{
    "model": "claude-sonnet-5",
    "max_tokens": 1024,
    "messages": [
      {"role": "user", "content": "In one sentence, what is PHP good at?"}
    ]
  }'
```

Three things are doing the work here:

- The endpoint is always `POST https://api.anthropic.com/v1/messages`.
- The three required headers: `x-api-key` (your key), `anthropic-version` (pin it to `2023-06-01`), and `content-type: application/json`.
- The body needs `model`, `max_tokens`, and `messages`. `max_tokens` is **required** and caps the response length - it is not optional, which trips up a lot of people coming from other APIs.

The model IDs you can pass today:

- `claude-opus-4-8` - the most capable, for hard reasoning and long agentic tasks.
- `claude-sonnet-5` - the balanced default. It's what I reach for unless there's a reason not to.
- `claude-haiku-4-5-20251001` - fast and cheap, great for classification, extraction, or high-volume calls.

Now the PHP version. If you're not on Laravel, raw Guzzle is the most portable route:

```php
<?php

require 'vendor/autoload.php';

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
        'messages'   => [
            ['role' => 'user', 'content' => 'In one sentence, what is PHP good at?'],
        ],
    ],
]);

$data = json_decode($response->getBody()->getContents(), true);

echo $data['content'][0]['text'];
```

Passing the body under Guzzle's `json` key handles JSON encoding and sets the content type for you, but I keep the explicit `content-type` header anyway - it's cheap insurance and makes the request self-documenting.

## Handling the response

The response is JSON. The piece you almost always want is the assistant's text, and it lives at `content[0].text`.

Here's a trimmed version of what comes back:

```json
{
  "id": "msg_01ABC...",
  "type": "message",
  "role": "assistant",
  "content": [
    { "type": "text", "text": "PHP excels at server-side web development..." }
  ],
  "model": "claude-sonnet-5",
  "stop_reason": "end_turn",
  "usage": {
    "input_tokens": 14,
    "output_tokens": 27
  }
}
```

Two fields matter beyond the text:

- **`content`** is an array of blocks. For a plain text reply you read `content[0]['text']`. It's an array because responses can contain multiple blocks, so don't assume there's exactly one forever.
- **`usage`** reports `input_tokens` and `output_tokens`. Log these. They're how you track cost and catch a prompt that's quietly ballooning.

A defensive read looks like this:

```php
$data = json_decode($response->getBody()->getContents(), true);

$text = $data['content'][0]['text'] ?? '';
$inputTokens  = $data['usage']['input_tokens']  ?? 0;
$outputTokens = $data['usage']['output_tokens'] ?? 0;
```

You can also steer the model's behavior with a top-level `system` prompt and a `temperature`. The `system` field is a string that sits **outside** the messages array - a mistake I made early was jamming a system message into `messages`, which isn't how this API works:

```php
'json' => [
    'model'       => 'claude-sonnet-5',
    'max_tokens'  => 1024,
    'system'      => 'You are a terse senior PHP engineer. Answer in at most three sentences.',
    'temperature' => 0.3,
    'messages'    => [
        ['role' => 'user', 'content' => 'Should I use Guzzle or the Http facade in Laravel?'],
    ],
],
```

Lower `temperature` means more deterministic output - handy for anything you parse programmatically.

## Streaming the response

For chat UIs and long generations, waiting for the whole response feels sluggish. Set `stream: true` and the API sends back server-sent events (SSE), so you can print tokens as they arrive.

The trick with Guzzle is to enable `stream` on the request and read the body as a stream instead of calling `getContents()`:

```php
<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client();

$response = $client->post('https://api.anthropic.com/v1/messages', [
    'stream'  => true,
    'headers' => [
        'x-api-key'         => getenv('ANTHROPIC_API_KEY'),
        'anthropic-version' => '2023-06-01',
        'content-type'      => 'application/json',
    ],
    'json' => [
        'model'      => 'claude-sonnet-5',
        'max_tokens' => 1024,
        'stream'     => true,
        'messages'   => [
            ['role' => 'user', 'content' => 'Write a haiku about PHP.'],
        ],
    ],
]);

$body = $response->getBody();

while (!$body->eof()) {
    $line = trim(fgets_from_stream($body));

    if (!str_starts_with($line, 'data:')) {
        continue;
    }

    $json = json_decode(substr($line, strlen('data:')), true);

    // Text arrives in content_block_delta events.
    if (($json['type'] ?? null) === 'content_block_delta') {
        echo $json['delta']['text'] ?? '';
        flush();
    }
}

// Small helper: read a single line from a PSR-7 stream.
function fgets_from_stream($stream): string
{
    $line = '';
    while (!$stream->eof()) {
        $char = $stream->read(1);
        $line .= $char;
        if ($char === "\n") {
            break;
        }
    }
    return $line;
}
```

The key detail: each SSE line is prefixed with `data:`, and the text you want lives in `content_block_delta` events under `delta.text`. Everything else - message start, block start, ping events - you can safely ignore for a basic streamed UI.

## Using it in Laravel

On Laravel I skip Guzzle boilerplate and use the built-in `Http` facade, then wrap the call in a small service so the rest of the app never sees a raw HTTP request.

First, the config. Reference the env var from `config/services.php`:

```php
// config/services.php
'anthropic' => [
    'key' => env('ANTHROPIC_API_KEY'),
],
```

Reading config through `config()` rather than `env()` directly matters - once you run `php artisan config:cache` in production, `env()` calls outside config files return `null`. That one has burned plenty of people on their first deploy.

Now the service:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeClient
{
    private string $endpoint = 'https://api.anthropic.com/v1/messages';

    public function ask(string $prompt, string $model = 'claude-sonnet-5'): string
    {
        $response = Http::withHeaders([
                'x-api-key'         => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
            ])
            ->timeout(60)
            ->retry(3, 200)
            ->post($this->endpoint, [
                'model'      => $model,
                'max_tokens' => 1024,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Claude API error: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $response->json('content.0.text', '');
    }
}
```

A few things I bake in by default:

- **`->timeout(60)`** because model responses take longer than a typical API call, and the default 30-second timeout will cut off longer generations.
- **`->retry(3, 200)`** to ride out transient `429` and `529` responses with a short backoff.
- **`$response->json('content.0.text')`** uses Laravel's dot-notation accessor to reach straight into the nested array.

Call it from a controller and you're done:

```php
public function summarize(ClaudeClient $claude)
{
    $summary = $claude->ask('Summarize the benefits of dependency injection.');

    return response()->json(['summary' => $summary]);
}
```

Anthropic does publish an official PHP SDK if you'd rather not manage HTTP yourself. I've kept this guide on plain HTTP because it's transparent and version-proof - you can always layer an SDK on top once the mechanics make sense.

## Common pitfalls

The things most likely to trip you up, learned the hard way:

- **Rate limits.** A `429` means you're going too fast; a `529` means the API is briefly overloaded. Both are retryable with backoff - don't hammer.
- **Leaking secrets.** Keep the key in env vars, never in code or client-side JavaScript. Every Claude call must originate server-side.
- **Timeouts.** Raise the client timeout. Long generations legitimately take tens of seconds, and a stingy timeout looks like a bug.
- **Forgetting `max_tokens`.** It's required. Omit it and the request is rejected outright.
- **Misplacing `system`.** It's a top-level string, not an entry in `messages`.
- **Assuming one content block.** Read `content[0]['text']` defensively with a `?? ''` fallback.

## FAQ

### Do I need the official SDK to use the Claude API in PHP?
No. The API is plain HTTPS with JSON. Guzzle or Laravel's `Http` facade cover everything, and both are fully documented above. An SDK is a convenience, not a requirement.

### Which model should I default to?
`claude-sonnet-5` is the balanced choice for most workloads. Reach for `claude-opus-4-8` on hard reasoning or agentic tasks, and `claude-haiku-4-5-20251001` when you need speed and low cost at volume.

### Why is my request rejected before it even runs?
Almost always a missing `max_tokens` (it's required) or a missing header. Double-check `x-api-key`, `anthropic-version: 2023-06-01`, and `content-type: application/json` are all present.

### How do I track what a call costs?
Read `usage.input_tokens` and `usage.output_tokens` from the response and log them. That's your ground truth for cost and for spotting prompts that have grown too large.

## Conclusion

Calling the Claude API from PHP comes down to a single `POST` to `/v1/messages` with three headers and a JSON body containing `model`, `max_tokens`, and `messages`. Prove it with `curl`, port it to Guzzle, then wrap it in a Laravel service with sane timeouts and retries so the rest of your app stays clean.

From here, the natural next steps are streaming responses into a real chat UI, passing multi-turn `messages` arrays to hold a conversation, and logging token usage per request. Start with the `Http` facade example, get one real response back, and build outward from there.