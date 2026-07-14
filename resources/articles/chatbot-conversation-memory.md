---
name: "Building a Chatbot with Conversation Memory in Laravel"
slug: chatbot-conversation-memory
short_description: "Give your Claude-powered chatbot real conversation memory in Laravel: store turns, rebuild the messages array, and budget tokens."
language: en
published_at: 2027-01-22 09:00:00
is_published: true
tags: [laravel, claude, chatbot, php]
---

The first time I wired a chatbot up to Claude, it felt broken. I told it my name, asked a follow-up two messages later, and it had no idea who I was. Nothing was wrong with the code. I just hadn't built any **chatbot conversation memory** yet, and that is a thing you build, not a thing the API hands you.

Here is the mental model that fixes everything: the Claude API is stateless. Each call to `POST /v1/messages` is a blank slate. If you want the assistant to "remember" earlier turns, you resend the whole conversation every single time. That's the trick. Your app is the memory; the API is just the reasoning engine on top of whatever history you feed it.

This guide walks through how to do that properly in Laravel using the `Http` client: storing turns in a database, rebuilding the `messages` array on each request, and keeping the whole thing from blowing past the context window as chats get long.

## Why the Claude API forgets everything

There is no session ID, no `conversation_id`, no server-side store of your chat. The API does not persist state between requests. People new to this often assume otherwise, and it leads to bugs that are hard to spot because a two-message demo works fine.

What actually happens on each turn:

- You send an array of messages that **alternates** between `role: "user"` and `role: "assistant"`.
- The model reads that array top to bottom as the full conversation so far.
- It replies. That reply is *not* saved anywhere on Anthropic's side.
- To continue, you append the reply to your array and send the whole thing again.

So conversation memory is really a persistence problem plus a serialization problem. Store the turns, then rebuild them into the exact shape the API wants. The [Laravel guide to calling the Claude API](/blog/claude-api-php) covers the basic request plumbing; here we're focused on the memory layer that sits above it.

## The shape of a request

A single call looks like this. The `system` prompt carries the persona and lives at the top level, separate from the `messages` array. That separation matters later for caching.

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'x-api-key' => config('services.anthropic.key'),
    'anthropic-version' => '2023-06-01',
    'content-type' => 'application/json',
])->post('https://api.anthropic.com/v1/messages', [
    'model' => 'claude-sonnet-5',
    'max_tokens' => 1024,
    'system' => 'You are a friendly support assistant for Oatllo. Keep answers short.',
    'messages' => [
        ['role' => 'user', 'content' => 'Hi, my name is Kuba.'],
        ['role' => 'assistant', 'content' => 'Hi Kuba! How can I help?'],
        ['role' => 'user', 'content' => "What's my name?"],
    ],
]);

$reply = $response->json('content.0.text');
```

Because that third user message arrives with the first two turns as context, the model answers "Kuba" correctly. Drop the history and it can't. **The history is the memory.**

Three headers are required: `x-api-key`, `anthropic-version`, and `content-type`. `max_tokens` is mandatory too, and `system` is optional but this is where your persona belongs, not stuffed into a user message.

## Storing conversation turns

You need somewhere durable to keep turns between HTTP requests to your own app. A simple table does the job.

```php
Schema::create('messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('conversation_id')->constrained();
    $table->string('role'); // 'user' or 'assistant'
    $table->text('content');
    $table->timestamps();
});
```

When a user sends a message, save it. When Claude replies, save that too. The flow per turn:

1. Persist the incoming user message.
2. Load the conversation's turns in order.
3. Rebuild the `messages` array and call the API.
4. Persist the assistant's reply.

Saving the assistant reply is the step people forget. Skip it and the next request rebuilds a history that's missing Claude's own answers, which quietly corrupts the alternation and confuses the model.

## Rebuilding the messages array

This is the heart of it. Pull the stored rows and map them into the payload format.

```php
class ChatService
{
    public function reply(Conversation $conversation, string $userText): string
    {
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $userText,
        ]);

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(fn ($m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->all();

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-5',
            'max_tokens' => 1024,
            'system' => 'You are a helpful assistant for Oatllo.',
            'messages' => $messages,
        ]);

        $reply = $response->json('content.0.text');

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $reply,
        ]);

        return $reply;
    }
}
```

Notice the ordering: `orderBy('id')` keeps turns chronological. The API reads the sequence as the conversation order and requires the list to begin with a `user` turn, so if your rows come back shuffled and an `assistant` turn lands first, you'll get a 400. That's a useful failure to hit, because it usually points at your query rather than your logic.

## The context window and token budgeting

Every model has a context window: the maximum number of tokens it can read in one request. Claude Sonnet 5 gives you a large window, but "large" is not "infinite," and you pay for input tokens on every call. A chat that runs for an hour resends a growing transcript each turn, so your cost per message climbs as the conversation grows even though the user's latest question is tiny.

Two numbers to keep in your head:

- **Input tokens** — everything you send: system prompt, full history, the new message. Grows every turn.
- **Output tokens** — capped by `max_tokens`, priced higher than input.

You can measure input size before sending with the token counting endpoint (`POST /v1/messages/count_tokens`), which takes the same `model` and `messages` payload and returns an `input_tokens` count. Use it to decide when a conversation has grown large enough to need trimming.

The point isn't to obsess over pennies. It's to have a plan for the moment a conversation gets long, because it will.

## Strategies when history gets long

There are two well-worn approaches, and most production bots use some blend of both.

**Truncate the oldest turns.** Keep the last N messages and drop the rest. Simple, cheap, and predictable. The downside is real: anything the user said early on is gone, so "as I mentioned at the start..." falls flat.

```php
$messages = $conversation->messages()
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get()
    ->reverse() // back to chronological
    ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
    ->all();
```

**Summarize older turns.** When the history crosses a threshold, make a separate API call asking Claude to condense the early part into a short summary, then replace those turns with a single message holding the summary. You keep the gist of the whole conversation while spending a fraction of the tokens.

A rough shape for the summarize step:

```php
public function summarize(array $oldMessages): string
{
    $transcript = collect($oldMessages)
        ->map(fn ($m) => "{$m['role']}: {$m['content']}")
        ->implode("\n");

    $response = Http::withHeaders([
        'x-api-key' => config('services.anthropic.key'),
        'anthropic-version' => '2023-06-01',
        'content-type' => 'application/json',
    ])->post('https://api.anthropic.com/v1/messages', [
        'model' => 'claude-sonnet-5',
        'max_tokens' => 512,
        'system' => 'Summarize this conversation in a few sentences. '
            . 'Preserve names, decisions, and unresolved questions.',
        'messages' => [
            ['role' => 'user', 'content' => $transcript],
        ],
    ]);

    return $response->json('content.0.text');
}
```

Then you'd rebuild the live conversation as: the summary (as an early `user` or `assistant` turn), followed by the most recent handful of real turns. Store the summary so you're not regenerating it on every request.

There's a nice optimization worth knowing about: **prompt caching**. Your `system` prompt and the stable front of your conversation rarely change between turns, and Claude can cache that prefix so you pay a reduced rate on the repeated tokens. It only helps if the prefix stays byte-for-byte identical, so keep dynamic values (timestamps, user IDs) out of the system prompt. There's a deeper writeup on [caching LLM responses](/blog/cache-llm-responses) if you want to squeeze cost down further.

## Common pitfalls

A short list of things that have bitten me or people I've helped:

- **Not saving the assistant reply.** The next turn rebuilds a lopsided history and the model loses track. Save both roles, every time.
- **Starting with the wrong role.** The `messages` list has to begin with a `user` turn; a history that opens with `assistant` gets rejected with a 400. The API now tolerates two same-role turns in a row by merging them, but a clean user/assistant rhythm is still worth keeping. Order by a monotonic column so the earliest stored turn is always the user's.
- **Interpolating the date into the system prompt.** It feels harmless, but it changes the cached prefix on every request and quietly kills your cache hits.
- **Assuming the API remembers.** No `conversation_id` exists on Anthropic's side. If you didn't send it, the model didn't see it.
- **Letting `max_tokens` sit too low.** If replies get cut off mid-sentence, the response `stop_reason` will read `max_tokens`. Raise it.
- **Never trimming.** A bot with no truncation or summarization strategy gets slower and pricier until it eventually hits the context ceiling and errors out.

## FAQ

**Does the Claude API remember previous messages on its own?**
No. It's stateless. Each `POST /v1/messages` call only knows what's in the `messages` array you send. Your application stores the history and resends it, which is what creates the feeling of memory.

**How many messages should I keep in the context?**
There's no fixed answer, it depends on your window and budget. A common pattern is to keep the last 15 to 30 turns verbatim and summarize anything older. Measure with the token counting endpoint rather than guessing.

**Where should conversation history live?**
A database table keyed by conversation is the standard choice, and it's what the examples here use. It survives restarts, supports multiple users, and lets you rebuild the `messages` array on demand. A cache layer works for short-lived sessions but you'll usually want durable storage.

**What happens when a conversation exceeds the context window?**
The request fails rather than silently dropping messages. That's why you truncate or summarize *before* you hit the limit, using a token count as your trigger instead of waiting for the error.

## Wrapping up

Conversation memory with Claude comes down to one habit: the API is stateless, so your app owns the history. Store every user and assistant turn, rebuild them into the `messages` array in order, and send the whole thing on each call with your persona in the top-level `system` field.

Once that's solid, layer on the cost controls. Watch input token growth, truncate or summarize old turns before the window fills, and cache the stable prefix. Start with the simple database-backed loop from the `ChatService` above, get it working end to end, then add trimming the day a real conversation gets long enough to need it. That order will save you a lot of premature optimization.