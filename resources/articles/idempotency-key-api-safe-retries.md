---
name: "Idempotency Keys: Making API Requests Safe to Retry"
slug: idempotency-key-api-safe-retries
short_description: "Learn how an idempotency key API pattern makes retries safe, prevents duplicate charges, and handles concurrent requests with a key store."
language: en
published_at: 2026-07-24 09:00:00
is_published: true
tags: [api, backend, http, laravel]
---

A network timeout is one of the most misleading failures you will ever debug. The request left your client, the server processed it, the database committed the row, and then the response got lost on the way back. Your client sees a timeout and does the obvious thing: it retries. Now you have charged the customer twice. This is exactly the problem an **idempotency key api** design solves, and it is the difference between a payment endpoint you trust and one that pages you at 3 a.m.

Idempotency means that making the same call once or ten times produces the same result. In HTTP, some methods already promise this. `POST` does not, and `POST` is where the money usually lives. The pattern below lets a client safely retry any request, including a `POST`, without creating duplicate side effects. Stripe popularized this approach in public API design, and it has since become a de facto standard across payment, billing, and messaging APIs.

## What idempotency actually means

An operation is idempotent when repeating it has no additional effect beyond the first execution. Setting a user's email to `a@b.com` is idempotent. Incrementing a balance by 10 is not.

The HTTP spec is explicit about which methods are considered idempotent:

- **`GET`, `HEAD`, `OPTIONS`, `TRACE`**: safe and idempotent. They never change state.
- **`PUT` and `DELETE`**: idempotent by definition. `PUT /users/1` with the same body twice leaves the same resource. `DELETE` twice leaves the resource deleted.
- **`POST`**: *not* idempotent. Two `POST /charges` calls are meant to create two charges.

That last line is the crux. Retries are unavoidable in distributed systems, but `POST` has no built-in protection. We need to add it ourselves, and we do that with an idempotency key.

## Why retries need idempotency keys

Retries are not optional. Mobile networks drop, load balancers time out, and your own [exponential backoff retry](/blog/exponential-backoff-retry) logic will re-send requests by design. That is correct behavior. The failure mode is not the retry. It is the retry landing on an endpoint that treats every call as brand new.

Consider the timeline:

1. Client sends `POST /charges` for $50.
2. Server charges the card and writes the record.
3. The response never arrives (timeout, dropped connection, killed pod).
4. Client retries the identical request.
5. Server charges the card **again**.

Nothing here is a bug in the traditional sense. Every component did its job. The system as a whole still double-charged. An idempotency key breaks the loop by giving the server a way to recognize "I have seen this exact request before" and reply with the original outcome instead of repeating the work.

## The Idempotency-Key header pattern

The mechanism is small and boring, which is exactly what you want in code that guards money.

- The **client** generates a unique value — usually a UUID v4 — for each distinct operation it wants to perform.
- It sends that value in an `Idempotency-Key` HTTP header.
- The **server** stores the key together with the response it produced.
- On any replay of the same key, the server skips the work and returns the stored response.

The key represents *intent*, not a single TCP attempt. All retries of the same logical operation reuse the same key. A genuinely new operation (the customer clicking "Pay" a second time on purpose) gets a fresh key.

```js
// Client side: one key per logical operation, reused across retries
import { randomUUID } from "crypto";

async function createCharge(amount) {
  const idempotencyKey = randomUUID();

  return retryWithBackoff(() =>
    fetch("https://api.example.com/charges", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Idempotency-Key": idempotencyKey, // same key on every retry
      },
      body: JSON.stringify({ amount, currency: "usd" }),
    })
  );
}
```

Note where the key is generated: **outside** the retry loop. If you regenerate it on each attempt, you have defeated the entire pattern.

## Storing keys and responses

The server needs a place to record which keys it has seen and what it returned. A dedicated table works well and keeps the concern isolated from your business tables.

```sql
CREATE TABLE idempotency_keys (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    idem_key      VARCHAR(255) NOT NULL,
    user_id       BIGINT NOT NULL,
    request_hash  CHAR(64) NOT NULL,       -- SHA-256 of the request body
    status_code   SMALLINT NULL,           -- NULL while in-flight
    response_body JSON NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_scope (user_id, idem_key)
);
```

Two design decisions in that schema are worth calling out.

**Uniqueness scope.** The unique constraint is on `(user_id, idem_key)`, not on `idem_key` alone. Keys are client-generated, so two different customers can absolutely collide on the same UUID (rare), but you should not bet your data integrity on UUID entropy across tenants. Scoping the key to the authenticated user (or API key) makes collisions impossible in practice.

**Request hash.** Storing a hash of the request body lets you detect the nasty case where a client reuses a key with a *different* payload. That almost always signals a client bug, and the correct response is `422 Unprocessable Entity` rather than silently returning a stale result.

Here is the server-side handling in PHP. The comments mark the ordering that matters:

```php
public function store(Request $request)
{
    $key = $request->header('Idempotency-Key');

    if (! $key) {
        return response()->json(['error' => 'Idempotency-Key required'], 400);
    }

    $hash = hash('sha256', $request->getContent());

    // 1. Try to claim the key. The unique constraint is the source of truth.
    try {
        $record = IdempotencyKey::create([
            'idem_key'     => $key,
            'user_id'      => $request->user()->id,
            'request_hash' => $hash,
        ]);
    } catch (QueryException $e) {
        // 2. Constraint violation => this key was already claimed.
        $existing = IdempotencyKey::where('user_id', $request->user()->id)
            ->where('idem_key', $key)
            ->first();

        // Same key, different body => client error.
        if ($existing->request_hash !== $hash) {
            return response()->json(['error' => 'Key reused with different payload'], 422);
        }

        // Still processing (no response stored yet) => tell client to retry.
        if (is_null($existing->status_code)) {
            return response()->json(['error' => 'Request in progress'], 409);
        }

        // 3. Completed before => replay the stored response.
        return response()->json($existing->response_body, $existing->status_code);
    }

    // 4. We own the key. Do the real work exactly once.
    $charge = $this->charges->create($request->user(), $request->input('amount'));

    $record->update([
        'status_code'   => 201,
        'response_body' => $charge->toArray(),
    ]);

    return response()->json($charge, 201);
}
```

The flow reads top to bottom: claim, detect a replay, respond appropriately, otherwise do the work and cache the result.

## Handling race conditions

The most common mistake I have made with this pattern is the check-then-act race. The tempting version looks like:

```php
// DON'T: two concurrent requests both pass this check
if (IdempotencyKey::where('idem_key', $key)->exists()) {
    return $cached;
}
$this->charges->create(...); // both threads reach here
```

If two duplicate requests arrive within milliseconds (which is exactly what an aggressive retry does), both `SELECT`s can return "not found" before either `INSERT` runs. You are back to double-charging.

The fix is to let the **database's unique constraint** be the arbiter, not application code. That is why the PHP above inserts *first* and treats the constraint violation as the signal. Only one concurrent request can win the `INSERT`; every other one lands in the `catch` block. The database serializes it for you, atomically, without a separate lock.

For the loser of that race, notice the `409` branch: the winner may still be mid-charge and has not written its response yet. Returning `409 Conflict` tells the client "your operation is already running, back off and retry", and your backoff logic handles the rest. You could instead take a short row lock and make the loser wait, but returning `409` is simpler and pairs naturally with retries.

There is one edge case this leaves open, and it is worth knowing about before it surprises you. If the process claims the key, starts the charge, then dies before writing the response — a killed pod, an OOM, a crashed worker — the row is stranded with a `NULL` status code. Every subsequent retry now hits the `409` branch forever, or at least until the TTL sweeps the row away. If that window matters to you, store a claim timestamp too and treat a stale in-flight row (say, older than a minute) as reclaimable rather than in-progress. Most APIs live with the TTL cleanup; financial ones usually don't.

## Expiry and TTL

Idempotency keys are not permanent. You store them long enough to cover realistic retry windows, then reclaim the space.

- A **24-hour TTL** is a common, sensible default. It comfortably covers client retry storms, brief outages, and queued jobs.
- Run a scheduled job to delete expired rows, or use a store with native TTL such as Redis (`SET key value EX 86400`).
- Keep the window generous enough that a client's [rate limiting](/blog/api-rate-limiting-token-bucket-vs-fixed-window) backoff cannot outlast it. If a client is throttled for an hour and your keys expire in five minutes, its eventual retry looks brand new and bypasses your protection.

Redis is a good fit when the response payload is small and you can tolerate a rebuilt key store after a flush. A relational table is better when you want the key lifecycle audited alongside the business data. I default to the database for anything financial and Redis for high-volume, low-stakes writes.

## Step-by-step: adding idempotency to an endpoint

1. **Add the key store.** Create the table (or Redis namespace) with a unique constraint scoped to the caller.
2. **Require the header** on unsafe, non-idempotent endpoints, chiefly `POST`. Reject requests without it with `400`.
3. **Claim the key by inserting first.** Let the unique constraint catch duplicates instead of a pre-check `SELECT`.
4. **Branch on the collision:** different payload → `422`; still in flight → `409`; completed → replay the stored response with its original status code.
5. **Persist the response** (status + body) as part of the same logical operation once the work succeeds.
6. **Expire keys** on a TTL that outlasts your clients' retry and backoff windows.
7. **Update your client** to generate one key per operation, outside the retry loop, and forward it on every attempt.

## FAQ

### Do I need idempotency keys for `GET` or `PUT`?
No. `GET` is safe and changes nothing, and `PUT`/`DELETE` are already idempotent by their HTTP semantics; repeating them converges on the same state. Reserve idempotency keys for non-idempotent operations, which in practice means `POST` endpoints that create resources or move money.

### What TTL should I use for idempotency keys?
Long enough to cover your worst realistic retry scenario. Twenty-four hours is a safe default for most APIs. If clients retry from background queues or can be rate-limited for extended periods, size the TTL to exceed that window so a delayed retry still matches its stored key.

### Who generates the key, the client or the server?
The client. Only the client knows which retries belong to the same logical operation. A UUID v4 per operation is standard. The server treats the key as opaque: it never generates or interprets it, only stores and matches it.

### What happens if the same key arrives with a different request body?
Return `422 Unprocessable Entity`. A matching key with a mismatched payload almost always means a client bug: a key was reused for a genuinely different operation. Storing a request hash lets you detect this instead of returning a misleading cached response.

## Conclusion

An **idempotency key api** pattern is a small amount of infrastructure that removes an entire class of production incidents. The moving parts are minimal: a client-generated `Idempotency-Key` header, a key store scoped to the caller, a unique constraint doing the concurrency work, and a TTL to keep the table lean. The one detail that people get wrong (and it is worth repeating) is using a check-then-act `SELECT` instead of letting the database constraint arbitrate. Insert first, catch the violation, replay the stored response.

Pair this with sensible [exponential backoff retry](/blog/exponential-backoff-retry) on the client and [rate limiting](/blog/api-rate-limiting-token-bucket-vs-fixed-window) on the server, and your write endpoints become genuinely safe to hammer. Retries stop being a liability and become what they were always supposed to be: a reliability feature.