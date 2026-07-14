---
name: "How to Stream LLM Responses in a Web App"
slug: stream-llm-response
short_description: "Learn how to stream LLM response output in a web app with SSE, EventSource, fetch ReadableStream, and a PHP/Laravel server example."
language: en
published_at: 2026-09-18 09:00:00
is_published: true
tags: [llm, streaming, php, laravel, javascript]
---

The first time I shipped a chat feature that waited for the full model reply before showing anything, a tester asked if it had frozen. It hadn't. It was thinking for eleven seconds. That gap is exactly what you kill when you learn to **stream LLM response** output token by token instead of returning it all at once. The words start appearing in well under a second, and the same eleven-second generation suddenly feels fast.

Nothing about the model got faster. What changed is *when* the user sees the first byte. This post walks through the whole path: why streaming helps, how Server-Sent Events carry the tokens, how the browser reads them, and a PHP/Laravel server that proxies a streaming call to Claude without leaking your API key.

## Why stream at all

Perceived latency is not total latency. A non-streamed response has one visible moment: the end. A streamed response has a much earlier one, **time to first token**, and that's the number users actually feel.

- A long answer that takes 10 seconds to finish but starts rendering in 400ms *reads* as instant.
- The user can start reading the beginning while the model is still writing the end.
- If the answer is going the wrong way, they can stop it early instead of waiting for a wall of text.

There's a cost side too. If you set a high `max_tokens` and don't stream, a slow generation can bump into HTTP request timeouts and idle-connection drops. Streaming keeps bytes flowing, so the connection stays alive for the full length of a large response.

## Server-Sent Events: the transport

The Claude API streams over **Server-Sent Events (SSE)**. SSE is a one-directional text protocol: the server holds the HTTP connection open and pushes `event:`/`data:` lines as they're ready. It's simpler than WebSockets and a natural fit here, because token streaming only flows one way, from server to client.

To turn a Claude request into a stream, you send `"stream": true` in the JSON body. The request goes to a single endpoint:

```bash
curl https://api.anthropic.com/v1/messages \
  -H "content-type: application/json" \
  -H "x-api-key: $ANTHROPIC_API_KEY" \
  -H "anthropic-version: 2023-06-01" \
  -d '{
    "model": "claude-sonnet-5",
    "max_tokens": 1024,
    "stream": true,
    "messages": [{"role": "user", "content": "Write a haiku about latency"}]
  }'
```

Three headers matter: `x-api-key` (your key), `anthropic-version: 2023-06-01`, and `content-type: application/json`. That's the whole authentication story.

### The event types you'll actually see

The response isn't one JSON object; it's a sequence of named SSE events. You don't need all of them, but you should know what each one is:

- **`message_start`**: fires once, carries the message metadata.
- **`content_block_start`**: a new content block begins (text, tool use, etc.).
- **`content_block_delta`**: the one you care about most. Each delta carries a chunk of output; for text, read `delta.text`.
- **`content_block_stop`**: the current block finished.
- **`message_delta`**: top-level updates like `stop_reason` and token usage.
- **`message_stop`**: fires once at the very end.

On the wire it looks like this:

```
event: content_block_delta
data: {"type":"content_block_delta","index":0,"delta":{"type":"text_delta","text":"Hello"}}

event: message_stop
data: {"type":"message_stop"}
```

Your job on the client is to watch for `content_block_delta`, pull `delta.text` out of each one, and append it to whatever you're rendering. Everything else is bookkeeping you can log or ignore depending on how much control you want.

## Reading the stream in the browser

There are two ways to consume an SSE stream from JavaScript, and the right choice depends on whether you need to send custom headers.

### Option 1: the EventSource API

`EventSource` is the purpose-built browser API for SSE. It handles connection management and reconnection for you:

```js
const source = new EventSource("/api/chat/stream?q=hello");
const output = document.getElementById("output");

source.addEventListener("content_block_delta", (event) => {
  const data = JSON.parse(event.data);
  if (data.delta?.type === "text_delta") {
    output.textContent += data.delta.text;
  }
});

source.addEventListener("message_stop", () => {
  source.close(); // done, stop listening
});
```

The catch: `EventSource` can only make GET requests and can't set custom headers. That's fine if your own server exposes a simple GET endpoint (which it should; see below). It's a problem if you were hoping to call an upstream API that needs a POST body directly from the browser. You shouldn't do that anyway, because it exposes your key.

### Option 2: fetch with a ReadableStream

When you need a POST or custom headers, read the response body as a stream instead. `fetch` gives you a `ReadableStream`, and you decode chunks as they arrive:

```js
const res = await fetch("/api/chat/stream", {
  method: "POST",
  headers: { "content-type": "application/json" },
  body: JSON.stringify({ prompt: "Write a haiku about latency" }),
});

const reader = res.body.getReader();
const decoder = new TextDecoder();
const output = document.getElementById("output");

while (true) {
  const { value, done } = await reader.read();
  if (done) break;

  // Each chunk may contain several SSE lines; split and pick out data:
  const chunk = decoder.decode(value, { stream: true });
  for (const line of chunk.split("\n")) {
    if (!line.startsWith("data:")) continue;
    const payload = JSON.parse(line.slice(5).trim());
    if (payload.delta?.type === "text_delta") {
      output.textContent += payload.delta.text;
    }
  }
}
```

One thing to watch: a single `read()` can return part of a line, or several lines glued together. In production you'd buffer partial lines across reads rather than assuming each chunk is a clean set. For most UIs, `TextDecoder` with `{ stream: true }` plus a small line buffer is enough.

**Rule of thumb:** reach for `EventSource` when your own backend exposes a GET SSE endpoint, and `fetch` + `ReadableStream` when you need POST bodies or header control.

## The server: proxy the stream in PHP

Never call the model directly from the browser. Your API key would ship in the page source. The pattern that works is a thin server endpoint: the browser hits *your* server, your server calls Claude with `"stream": true`, and you forward each chunk down to the browser as it arrives.

In Laravel, a streamed response is a first-class thing. `response()->stream()` hands you a closure where anything you `echo` and `flush()` goes straight to the client:

```php
use Illuminate\Support\Facades\Http;

Route::post('/api/chat/stream', function () {
    $prompt = request()->input('prompt', '');

    return response()->stream(function () use ($prompt) {
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->withOptions(['stream' => true])->post(
            'https://api.anthropic.com/v1/messages',
            [
                'model' => 'claude-sonnet-5',
                'max_tokens' => 1024,
                'stream' => true,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]
        );

        $body = $response->toPsrResponse()->getBody();

        while (!$body->eof()) {
            echo $body->read(1024);
            ob_flush();
            flush();
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
    ]);
});
```

A few details that are easy to miss:

- **`Content-Type: text/event-stream`** tells the browser (and `EventSource`) this is SSE, not a normal response.
- **`X-Accel-Buffering: no`** disables nginx response buffering. Skip it and nginx may hold your chunks until the whole thing is done, quietly undoing the streaming.
- **`ob_flush()` then `flush()`** pushes PHP's output buffer out on every read. Without the flush, PHP buffers too.
- The Guzzle `stream => true` option is what makes `Http::post(...)` return before the whole body has downloaded, so you can relay it incrementally.

If you'd rather forward events one at a time instead of raw 1KB reads, parse the upstream stream line by line and re-emit each `content_block_delta`. For a proxy, passing the bytes through untouched is the least code and works with the client parsers above.

For the deeper end-to-end setup (API keys, error handling, and a cleaner service class), I wrote a companion piece on calling the [Claude API from PHP](/blog/claude-api-php) that this endpoint builds on.

## Pitfalls I've hit

- **Buffering silently eats your stream.** Nginx, PHP output buffering, and some CDNs will hold chunks. If tokens arrive all at once at the end, buffering is almost always the cause: set `X-Accel-Buffering: no` and flush explicitly.
- **`EventSource` can't POST.** If you need a request body or custom headers, use `fetch` + `ReadableStream`. Don't fight `EventSource` into doing something it wasn't built for.
- **Assuming one chunk equals one event.** A network read can split an SSE line in half. Buffer partial lines until you hit a newline.
- **Inventing event names.** Only `message_start`, `content_block_start`, `content_block_delta`, `content_block_stop`, `message_delta`, and `message_stop` exist. Text lives in `delta.text` inside `content_block_delta`. Don't guess other field names.
- **Forgetting to close the connection.** On `message_stop`, call `source.close()` (EventSource) or break the read loop. Leaked connections pile up fast under load.
- **Leaking the API key.** The model call belongs on the server. The browser talks to your endpoint; your endpoint holds the key.

## FAQ

### Do I need WebSockets to stream LLM output?

No. LLM token streaming is one-directional (server to client), so SSE is the simpler, correct fit. WebSockets make sense when you also need the client to push messages mid-stream, which token rendering doesn't require.

### What's the difference between EventSource and fetch for streaming?

`EventSource` is a dedicated SSE client: it auto-reconnects and parses events for you, but it's GET-only with no custom headers. `fetch` + `ReadableStream` works with any method and headers but leaves SSE parsing to you. Use `EventSource` for simple GET endpoints, `fetch` when you need a POST body.

### Which Claude model should I use for streaming?

Any current model streams the same way; the transport doesn't change. `claude-sonnet-5` is a solid default for interactive chat, balancing quality and cost. Streaming is also strongly recommended for any request with a large `max_tokens`, since it avoids HTTP timeouts on long generations.

### How do I know when the stream is finished?

Watch for the `message_stop` event, which fires exactly once at the end. `message_delta` before it carries the `stop_reason` (for example `end_turn` or `max_tokens`) if you need to know *why* it stopped.

## Wrapping up

Streaming an LLM response is three moving parts that fit together cleanly: a server that requests `"stream": true` and forwards SSE chunks, a transport (`text/event-stream`) that keeps the connection open, and a browser client that appends each `content_block_delta` as it lands. The model isn't faster — but time to first token drops from seconds to milliseconds, and that's the number your users feel.

Start with the Laravel proxy above and an `EventSource` client. Once that works, swap in `fetch` + `ReadableStream` if you need POST bodies, and add line buffering when you move past a prototype. The hard part was never the streaming — it's remembering to flush.