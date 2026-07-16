---
slug: idempotency-key-api-safe-retries-carousel
type: carousel
language: en
title: "Idempotency keys"
topic: php
source_type: article
source: idempotency-key-api-safe-retries
link: https://oatllo.com/idempotency-key-api-safe-retries
publish_at: 2026-11-06 19:00
status: ready
formats: [post]
hashtags: [api, backend, http, laravel, php]
caption: |
  The card was charged, the row committed, and then the response got lost. Your client retries. You just double-billed.

  Nothing there is a bug. Every component did its job. The fix is a key the
  client generates once per intent and a unique constraint. Full guide in bio.

  Which endpoint of yours is still safe to retry?
---

## A lost response after a successful charge is how you double-bill

The card was charged. The row committed. The response died on the way back. The
client sees a timeout and does the obvious thing.

<!-- slide -->

## Nothing here is a bug. Both charges are real.

Client POSTs $50. Server charges and writes the record. The response never
arrives. Client retries the identical request. Server charges again. Every
component did its job.

<!-- slide -->

## The key goes outside the retry loop

```js
// one key per operation, not per attempt
const key = randomUUID();

return retryWithBackoff(() =>
  post(url, body, { "Idempotency-Key": key })
);
```

Regenerate it on each attempt and you have defeated the whole pattern. The key
represents intent, not a single TCP attempt.

<!-- slide -->

## Check-then-act still double-bills

```php
// DON'T: both requests pass this check
if (Key::where('idem_key', $k)->exists()) {
    return $cached;
}
$this->charges->create(...); // both get here
```

Two retries land within milliseconds. Both `SELECT`s return "not found" before
either `INSERT` runs.

<!-- slide -->

## Let the unique constraint arbitrate

```sql
UNIQUE KEY uniq_scope (user_id, idem_key)
```

Insert first, catch the violation. Only one request can win; every other one
lands in the catch block. The database serializes it for you, atomically, with
no separate lock.

<!-- slide role="cta" -->

## Branch on the collision, don't guess

Different payload -> 422. Still in flight -> 409, back off and retry. Completed
-> replay the stored response with its original status. 24h TTL. Full guide
linked in bio.
