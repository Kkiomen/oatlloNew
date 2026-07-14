---
name: "Webhook Design Best Practices: Signatures and Retries That Hold Up"
slug: webhook-design-best-practices
short_description: "Webhook design best practices for reliable delivery: HMAC signatures, replay protection, idempotent handlers, and retries with backoff and a DLQ."
language: en
published_at: 2026-11-23 09:00:00
is_published: true
tags: [webhooks, api, laravel, php, resilience]
---

The webhook I trusted most was the one that quietly lost events for three days. A downstream service was returning a 200 to make our retries stop, then dropping the payload on the floor because a queue behind it was full. Nobody noticed until a customer asked why their subscription never activated. That mess taught me more about **webhook design best practices** than any spec ever did, and most of what I learned lives on both ends of the connection: the sender and the receiver each have jobs, and skipping either one is how you end up debugging a silent data drift weeks later.

This post is the checklist I wish I'd had. We'll sign payloads so the receiver can trust them, stop replay attacks with a timestamp, make handlers survive duplicate deliveries, and build a retry loop on the sending side that doesn't hammer a struggling consumer into the ground. Code is PHP, with a Laravel receiver you can drop in.

## Why webhooks are harder than they look

A webhook is just an HTTP POST from one system to another when something happens. Simple to describe, deceptively easy to get wrong. The trouble is that the network sits between the two systems, and the network lies. Requests time out after the receiver already committed the work. Retries arrive twice. A malicious actor can POST to your public endpoint pretending to be your payment provider.

So the mental model I use is this: **the sender guarantees at-least-once delivery, and the receiver guarantees it can handle that safely.** Nobody promises exactly-once, because exactly-once over an unreliable network is a fairy tale. Once you accept "at least once," almost every good practice below follows naturally.

## Sign every payload with HMAC-SHA256

Your webhook endpoint is public. Anyone who finds the URL can POST to it. Without a way to verify the sender, you're trusting the internet, which is not a plan.

The standard fix is an HMAC signature. The sender computes an HMAC-SHA256 over the **raw request body** using a secret both sides share, then puts the result in a header. The receiver recomputes it and compares. If the payload was tampered with, or the caller doesn't hold the secret, the signatures won't match.

Two details matter enormously here.

First, sign the **raw bytes**, not a re-serialized version. If you parse JSON and then re-encode it to check the signature, key ordering, whitespace, and unicode escaping can all shift, and your comparison fails on payloads that are perfectly valid. Grab the body before anything touches it.

Second, compare with a **constant-time** function. A naive `===` on strings can leak timing information an attacker uses to guess the signature byte by byte. PHP ships `hash_equals` exactly for this.

Here's the sender side computing a signature:

```php
<?php

function signPayload(string $rawBody, string $secret): array
{
    $timestamp = time();
    // Bind the timestamp into the signed material so it can't be swapped later.
    $signedPayload = $timestamp . '.' . $rawBody;
    $signature = hash_hmac('sha256', $signedPayload, $secret);

    return [
        'X-Webhook-Timestamp' => (string) $timestamp,
        'X-Webhook-Signature' => 'sha256=' . $signature,
    ];
}
```

Notice the timestamp is folded into the signed string, not just sent alongside it. That's what makes the next section work.

## Stop replays with a signed timestamp

A valid signed request that an attacker captured is still a valid signed request. If they replay it an hour later, the signature still checks out. That's a replay attack, and the defense is cheap: include a timestamp in the signed material and reject anything older than a small window.

Because the timestamp is part of what you signed, an attacker can't bump it without invalidating the signature. On the receiver you check two things: the signature matches, and the timestamp is recent (five minutes is a common tolerance, generous enough for clock skew, tight enough to matter).

## The Laravel receiver, end to end

Here's a receiver that pulls the raw body, rebuilds the signed string, and verifies it in constant time before trusting anything. I'd wire this as middleware so no controller ever sees an unverified payload.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    private const TOLERANCE_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $secret    = config('services.webhooks.secret');
        $rawBody   = $request->getContent(); // raw bytes, untouched
        $timestamp = $request->header('X-Webhook-Timestamp');
        $header    = $request->header('X-Webhook-Signature', '');

        // 1. Reject stale requests to block replays.
        if (! ctype_digit((string) $timestamp)
            || abs(time() - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            abort(401, 'Timestamp outside tolerance window.');
        }

        // 2. Recompute over the exact same material the sender signed.
        $expected = 'sha256=' . hash_hmac(
            'sha256',
            $timestamp . '.' . $rawBody,
            $secret
        );

        // 3. Constant-time compare so we don't leak timing.
        if (! hash_equals($expected, $header)) {
            abort(401, 'Invalid signature.');
        }

        return $next($request);
    }
}
```

And the controller behind it, which does almost nothing on purpose:

```php
<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookEvent;
use App\Models\ReceivedWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $eventId = $request->input('id');

        // Dedupe: a unique column on event_id turns a race into a no-op.
        $webhook = ReceivedWebhook::firstOrCreate(
            ['event_id' => $eventId],
            ['payload' => $request->getContent()]
        );

        // Only queue work the first time we've seen this event.
        if ($webhook->wasRecentlyCreated) {
            ProcessWebhookEvent::dispatch($webhook->id);
        }

        // Acknowledge fast. Real work happens on the queue.
        return response('', Response::HTTP_ACCEPTED);
    }
}
```

## Return 2xx fast, then work asynchronously

The controller above returns before doing any real processing, and that's deliberate. Senders retry on timeout. If your handler spends eight seconds talking to a payment API and updating three tables, a slow moment tips you over the sender's timeout, it assumes failure, and it retries, while your original request is still running. Now you're processing the same event twice concurrently.

The rule I hold to: **the endpoint's only job is to verify, persist, and acknowledge.** Push the actual work onto a queue and let a worker handle it. In Laravel that's a dispatched job; the HTTP request returns in milliseconds no matter how heavy the downstream work is. If you're fanning out a batch of events at once, [Laravel job batching](/blog/laravel-job-batching) gives you progress tracking and a completion callback for free.

This split also decouples your uptime from your throughput. A traffic spike backs up the queue instead of returning 500s to the sender and triggering a retry storm on top of the spike.

## Make handlers idempotent

Because delivery is at-least-once, your handler *will* see the same event twice eventually. Maybe the sender timed out waiting for your ack. Maybe a worker crashed mid-job and the message got redelivered. Either way, processing an event twice must not charge a card twice or send two emails.

The fix is idempotency keyed on the event's own id. Every well-behaved webhook payload carries a unique event id; use it. The `firstOrCreate` above leans on a unique index on `event_id`, so a duplicate delivery finds the existing row and skips the dispatch. For the deeper pattern of deduping retried operations, including the subtle race conditions, see [idempotency keys for safe API retries](/blog/idempotency-key-api-safe-retries).

One caveat worth stating plainly: a unique index protects the *dispatch*, but your job should still be safe to re-run, because the queue itself can redeliver. Idempotency isn't a single checkpoint, it's a property you keep all the way through.

## The sending side: retries, backoff, and a dead-letter queue

Everything so far was about receiving. If you're the one *emitting* webhooks, you owe your consumers a delivery guarantee, and that means retrying failures without turning into a denial-of-service attack against them.

Retry on the right conditions: connection failures, timeouts, and 5xx responses. Do **not** retry a 4xx, since that's the consumer telling you the request itself is wrong, and hammering them won't fix your payload. Between attempts, wait longer each time using exponential backoff, ideally with jitter so a shared outage doesn't make all your senders retry in lockstep. I've written up the full formula and the thundering-herd trap in [exponential backoff and retry](/blog/exponential-backoff-retry).

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public string $url,
        public string $rawBody,
        public array $headers,
    ) {}

    // Exponential backoff between attempts: 10s, 30s, 60s, 120s.
    public function backoff(): array
    {
        return [10, 30, 60, 120];
    }

    public function handle(): void
    {
        $response = Http::withHeaders($this->headers)
            ->timeout(10)
            ->withBody($this->rawBody, 'application/json')
            ->post($this->url);

        // 4xx (except 429) means the payload is wrong; don't waste retries.
        if ($response->clientError() && $response->status() !== 429) {
            $this->fail("Consumer rejected webhook: {$response->status()}");
            return;
        }

        // 5xx / 429 / timeout throw, and the job retries with backoff.
        $response->throw();
    }

    // After $tries is exhausted, Laravel moves this to failed_jobs.
    // Treat that table as your dead-letter queue: alert and inspect.
    public function failed(\Throwable $e): void
    {
        // notify, record, surface in a dashboard
    }
}
```

After the attempts run out, the job lands in Laravel's `failed_jobs` table, which is effectively your dead-letter queue. Don't let it be a graveyard. Alert on it, review it, and give consumers a way to request redelivery, because a webhook that failed five times is a data-consistency problem waiting to be discovered by a customer, exactly like my three-day outage.

## A webhook design checklist

Run through this before you ship either side:

- **Sign the raw body** with HMAC-SHA256 and a shared secret, sent in a header. Never sign a re-encoded payload.
- **Verify with `hash_hmac` + `hash_equals`** so the comparison is constant-time.
- **Sign a timestamp too** and reject requests outside a short window (about five minutes) to kill replays.
- **Acknowledge with a 2xx quickly**, then process on a queue so slow work never causes a sender timeout.
- **Dedupe by event id** with a unique index, and keep the downstream job re-runnable.
- **Retry only transient failures** (timeouts, 5xx, 429) with exponential backoff and jitter; leave 4xx alone.
- **Dead-letter after N attempts** and actually monitor that queue.
- **Log deliveries and failures** with the event id so you can trace a missing event later.

## FAQ

### How do I test a webhook receiver locally?

Expose your local server with a tunnel like ngrok or Expose, then point the provider's test-delivery button at the public URL. For automated tests, POST a fixture payload with a correctly computed signature straight at your endpoint. That second approach is faster and lets you assert on rejected-signature and stale-timestamp cases too.

### What HTTP status should a webhook receiver return?

Any 2xx tells the sender you accepted the event; I use `202 Accepted` because the work is genuinely still pending on a queue. Return a 4xx only for payloads you'll never be able to process, and a 5xx when something on your side broke and you want the sender to retry.

### Should I retry a webhook that returned a 4xx?

No. A 4xx means the request itself is the problem, so retrying sends the same broken payload again and burns your attempt budget. The exception is `429 Too Many Requests`, which is a "slow down" signal, so back off and retry that one, ideally honoring any `Retry-After` header.

### Do I still need idempotency if I have signature verification?

Yes, they solve different problems. Signatures prove *who* sent the event and that it wasn't tampered with. Idempotency handles the fact that the same authentic event can arrive more than once because of retries. You need both.

## Wrapping up

Reliable webhooks come down to a short list of habits applied on both ends. Sign the raw body and verify it in constant time. Fold a timestamp into the signature and reject stale requests. Acknowledge fast, then do the real work on a queue. Dedupe by event id, and on the sending side retry only what's worth retrying, back off between attempts, and dead-letter the rest.

Start with the signature verification middleware and the fast-ack controller from this post. They're the two pieces that would have caught my three-day outage on day one, and they take an afternoon to wire in. Everything else is refinement on top of a foundation that already refuses to trust the network blindly.