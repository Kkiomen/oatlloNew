---
slug: claude-api-php-carousel
type: carousel
language: en
title: "Claude API from PHP"
topic: ai
source_type: article
source: claude-api-php
link: https://oatllo.com/claude-api-php
publish_at: 2026-09-01 19:00
status: ready
formats: [post, reel]
hashtags: [php, laravel, claude, api, ai]
caption: |
  Calling Claude from PHP is one POST, three headers and a JSON body. That is the whole API.

  The rest - streaming, retries, wrapping it in a Laravel service - is plumbing.
  Runnable code in the full guide, linked in bio.

  Which one cost you an hour first?
---

## max_tokens is required. Forget it and Claude rejects the request.

It caps the response length and it is not optional, which trips up everyone
coming from other APIs. The request never even runs.

<!-- slide -->

## The whole body is three keys

```php
'json' => [
    'model'      => 'claude-sonnet-5',
    'max_tokens' => 1024,
    'messages'   => [$message],
],
```

One endpoint, always: `POST /v1/messages`. Prove it with curl before you write
a line of PHP.

<!-- slide -->

## Three headers, all mandatory

```php
'x-api-key'         => $key,
'anthropic-version' => '2023-06-01',
'content-type'      => 'application/json',
```

Pin the version. A rejected request before it runs is almost always a missing
header or a missing `max_tokens`.

<!-- slide -->

## system is not a message

```php
'system'   => 'You are a terse PHP engineer.',
'messages' => [$userMessage],
```

It is a top-level string that sits outside the messages array. Jamming a system
role into `messages` is not how this API works.

<!-- slide -->

## Two defaults the docs will not force on you

```php
Http::withHeaders($headers)
    ->timeout(60)
    ->retry(3, 200)
    ->post($endpoint, $body);
```

The default 30s timeout cuts off long generations. Retries ride out transient
`429` and `529` responses.

<!-- slide role="cta" -->

## Read the response defensively

```php
$text = $data['content'][0]['text'] ?? '';
```

`content` is an array of blocks, so do not assume there is exactly one forever.
Log `usage.input_tokens` too.
