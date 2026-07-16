---
slug: chatbot-conversation-memory-carousel
type: carousel
language: en
title: "Chatbot conversation memory"
topic: ai
source_type: article
source: chatbot-conversation-memory
link: https://oatllo.com/chatbot-conversation-memory
publish_at: 2026-08-25 19:00
status: ready
formats: [post, reel]
hashtags: [ai, claude, laravel, php, chatbot]
caption: |
  The Claude API has no memory. Your app has to fake it on every single call.

  No conversation_id exists on Anthropic's side. You store every turn and resend
  the whole array. The bug nobody catches: forgetting to save the reply.

  Full build linked in bio.

  How long do your chats run before you trim?
---

## The Claude API has no memory

No session ID, no conversation_id, no server-side store. Every POST to
/v1/messages is a blank slate. A two-message demo hides this perfectly.

<!-- slide -->

## The history IS the memory

```php
$messages = [
  ['role' => 'user', 'content' => "I'm Kuba"],
  ['role' => 'assistant', 'content' => 'Hi!'],
  ['role' => 'user', 'content' => 'My name?'],
];
```

It answers correctly because you resent the first two turns.

<!-- slide -->

## The step people forget

```php
$conversation->messages()->create([
    'role' => 'assistant',
    'content' => $reply,
]);
```

Skip it and the next turn rebuilds a history missing Claude's own answers,
which quietly corrupts the alternation.

<!-- slide -->

## Your cost per message climbs as it grows

You resend a growing transcript every turn. The user's latest question is tiny;
the input token bill is not. Count it, don't guess it.

<!-- slide -->

## Two ways out when history gets long

```php
$messages = $conversation->messages()
    ->orderBy('id', 'desc')->limit(20)
    ->get()->reverse();
```

Truncate the oldest turns, or summarize them into one. Most bots blend both.

<!-- slide role="cta" -->

## Keep the date out of your system prompt

It feels harmless. It changes the cached prefix on every request and quietly
kills your prompt cache hits.
