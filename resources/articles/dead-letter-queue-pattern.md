---
name: "Dead Letter Queues: Handling Poison Messages"
slug: dead-letter-queue-pattern
short_description: "How a dead letter queue isolates poison messages so one bad payload stops blocking your workers, with SQS, RabbitMQ, and Laravel queue examples."
language: en
published_at: 2027-04-16 09:00:00
is_published: true
tags: [architecture, queues, laravel, devops, messaging]
---

A single malformed message once took down an entire order-processing pipeline I was on call for. Not because it was dangerous — because it was *undeliverable*. The consumer threw on a null field, the broker re-delivered it, the consumer threw again, and that loop ran flat out while every legitimate order queued up behind it. The queue depth chart looked like a wall. Nothing was actually broken except our willingness to admit that some messages will never succeed.

That message is called a poison message, and the standard cure is a dead letter queue. This article covers what makes a message "poison," why unbounded retries are a self-inflicted outage, how DLQs work in SQS and RabbitMQ, the equivalent mechanics in Laravel's queue system, and — the part most tutorials skip — what you actually do with a message once it lands there.

## What a poison message actually is

A poison message is one your consumer can never process successfully, no matter how many times it tries. The failure is *deterministic*: same input, same crash, every single time.

Common sources:

- A payload that doesn't match the schema your code expects (missing field, wrong type, truncated JSON).
- A reference to a row that was deleted before the job ran, so every lookup throws.
- A message that trips a genuine bug in the consumer — the code is wrong, and it'll stay wrong until you deploy a fix.
- Data that violates an invariant downstream: a negative quantity, a currency your billing provider rejects.

The defining trait is that retrying changes nothing. Contrast that with a *transient* failure — a timed-out HTTP call, a database that's briefly overloaded, an S3 hiccup — where the exact same message will very likely succeed if you just wait and try again. Retries are the right tool for transient failures. They are the wrong tool for poison, and confusing the two is where the trouble starts.

## Why infinite retries block the queue

Most brokers redeliver a message that wasn't acknowledged. That's a feature — it's how at-least-once delivery survives a consumer that crashes mid-work. But without a limit, redelivery of a poison message becomes a hot loop.

Two things go wrong, and they compound:

**Head-of-line blocking.** With a single consumer processing in order, the poison message sits at the front and never clears. Everything behind it waits. On a FIFO queue this is total: throughput drops to zero.

**Burned resources.** Even with many consumers pulling concurrently, each redelivery costs a full processing attempt — a database connection, an API call, CPU. A message failing 50 times a second isn't idle; it's actively spending the capacity you needed for real work. I've watched a poison message peg worker CPU while the "real" jobs starved.

The fix isn't to retry harder. It's to *count* the retries and, past a threshold, get the message out of the path so the queue can drain.

## The dead letter queue concept

A dead letter queue is a separate queue where messages go after they've failed too many times. It's a quarantine, not a graveyard.

The flow is simple. Every time a message is delivered and not acknowledged, the broker increments a receive count. When that count crosses a configured threshold — the **max receive count** or max attempts — the broker stops redelivering to the main queue and moves the message to the DLQ instead. The main queue is now unblocked. The problem message is preserved, out of the way, waiting for a human or a repair process to look at it.

The threshold is a real decision, not a default to leave alone. Set it too low and a message that hit two transient network blips gets dead-lettered when a third attempt would have worked. Too high and a true poison message wastes attempts and delays the alert. For work that's mostly transient-failure-prone (external APIs), I lean toward 3–5 attempts *combined with backoff* so the retries are spread out rather than hammered back-to-back. For deterministic work where a failure almost certainly means bad data, a lower ceiling gets the message quarantined faster.

## DLQs in SQS and RabbitMQ

The concept is universal; the wiring differs.

**Amazon SQS** treats the DLQ as a first-class setting. You create an ordinary second queue and attach it to the source queue via a redrive policy. `maxReceiveCount` is the threshold.

```json
{
  "deadLetterTargetArn": "arn:aws:sqs:eu-central-1:123456789012:orders-dlq",
  "maxReceiveCount": 5
}
```

SQS tracks how many times each message has been received. Once a message is received more than five times without being deleted (SQS treats a successful ack as a delete), it's moved to `orders-dlq` automatically. There's no consumer logic involved — the broker does the counting and the moving. SQS also has a *redrive* feature to push messages back from the DLQ to the source queue once you've fixed the cause, which we'll come back to.

**RabbitMQ** does it through exchanges. You declare a queue with a `x-dead-letter-exchange` argument; when a message is rejected (nacked without requeue), expires via TTL, or exceeds a length limit, RabbitMQ republishes it to that exchange, which routes it to your DLQ.

```php
$channel->queue_declare('orders', false, true, false, false, false, new AMQPTable([
    'x-dead-letter-exchange' => 'dlx',
    'x-dead-letter-routing-key' => 'orders.dead',
]));
```

One RabbitMQ gotcha worth knowing: classic RabbitMQ doesn't natively count delivery attempts the way SQS does. It dead-letters on *rejection*, not on an attempt threshold, so if your consumer keeps requeuing on failure you can still loop forever. You either track attempts yourself (a header you increment before republishing) or use the quorum queue delivery-limit feature, which does enforce a hard cap. Assuming RabbitMQ behaves like SQS out of the box is a classic trap.

## The equivalent in Laravel queues

Laravel doesn't call it a dead letter queue, but the `failed_jobs` table is exactly that: a quarantine for jobs that exhausted their attempts. Once you see the mapping, the SQS wiring and the Laravel worker stop looking like two different problems.

A job fails when it throws an uncaught exception and runs out of tries. `$tries` (or `--tries` on the worker) is your `maxReceiveCount`. When the attempts are used up, Laravel writes a row to `failed_jobs` and stops touching the job — it does not redeliver forever. That row *is* the dead-lettered message. It stores the connection, the queue, the serialized payload, and the exception.

```php
class ProcessOrder implements ShouldQueue
{
    public int $tries = 5;

    // Spread retries out instead of hammering: 10s, 30s, 60s.
    public array $backoff = [10, 30, 60];

    public function handle(): void
    {
        // ...
    }

    // Runs once the job is finally dead-lettered.
    public function failed(\Throwable $e): void
    {
        Log::error('Order job dead-lettered', [
            'order_id' => $this->orderId,
            'error' => $e->getMessage(),
        ]);
    }
}
```

The `backoff` array matters here for the same reason it matters everywhere: it turns five back-to-back retries into five spaced-out ones, which gives transient failures room to clear before you give up. If you want the reasoning behind picking those delay values, I wrote about that separately in [exponential backoff and retry strategy](/exponential-backoff-retry).

Inspecting and replaying dead-lettered jobs is done with artisan:

```bash
# See what's in quarantine.
php artisan queue:failed

# Replay one job by its UUID.
php artisan queue:retry 9a3f...c21

# Replay everything.
php artisan queue:retry all

# Give up on one for good.
php artisan queue:forget 9a3f...c21
```

`queue:retry` pushes the job back onto its original queue for another run — the direct analog of SQS redrive. `queue:forget` deletes it, which you do once you've decided the message is genuinely unrecoverable. The full mechanics of the retry knobs are covered in [retrying failed Laravel queue jobs](/laravel-retry-failed-jobs); here the point is that `failed_jobs` *is* your DLQ, and you should treat it like one instead of letting it silently fill up.

## What to do with dead-lettered messages

A DLQ that nobody watches is just a slower way to lose data. The whole value of the pattern is that it turns a silent failure loop into something you can act on. Three things need to happen.

**Alert.** A message landing in the DLQ should page someone or at least fire a notification. An empty DLQ is healthy; a growing one is an incident. Wire an alarm on DLQ depth — in SQS, a CloudWatch alarm on `ApproximateNumberOfMessagesVisible` for the DLQ; in Laravel, hook the `JobFailed` event or the job's `failed()` method to your alerting channel. The failure that bit me went unnoticed for twenty minutes precisely because nothing was watching the quarantine.

**Inspect.** Read the payload and the exception before you do anything else. The DLQ preserves the original message, which is your evidence. Is it a schema mismatch (bad producer)? A deleted reference (a race between two services)? A genuine bug (the same stack trace across many messages)? The shape of what's in the DLQ tells you where the fix belongs, and it's usually not in the consumer that happened to crash.

**Replay.** Once the root cause is fixed — you deployed a patch, the referenced row is back, the producer stopped sending garbage — you push the messages back to the main queue to be processed. That's `queue:retry` in Laravel, redrive in SQS, or a small consumer that republishes from the DLQ in RabbitMQ. Replay is the whole reason the message got parked instead of dropped: nothing is lost, it's just deferred until it can succeed.

## Idempotency on replay is not optional

Here's the part that turns a good recovery into a bad one. When you replay from a DLQ, you have no guarantee the message didn't *partially* succeed the first time.

Picture the poison order that dead-lettered: maybe it charged the customer's card, then threw while writing the order row. Retrying naively charges the card twice. At-least-once delivery plus replay means your consumer will, sooner or later, see the same logical message more than once — and the DLQ replay is the moment it's most likely.

So the consumer has to be idempotent: processing the same message twice must produce the same result as processing it once. The usual approach is a business or message key you record the first time you complete work, and check on every subsequent attempt.

```php
public function handle(): void
{
    // messageId is a stable key from the producer, not a random per-attempt value.
    $alreadyDone = DB::table('processed_messages')
        ->where('message_id', $this->messageId)
        ->exists();

    if ($alreadyDone) {
        return; // Safe no-op on replay.
    }

    DB::transaction(function () {
        $this->chargeCard();
        $this->createOrder();

        DB::table('processed_messages')->insert([
            'message_id' => $this->messageId,
            'processed_at' => now(),
        ]);
    });
}
```

The key has to be stable across deliveries — derived from the message content or an ID the producer sets, never a per-attempt random value, or the dedupe check never matches. Do the side effect and the dedupe insert in the same transaction so a crash between them can't leave you half-committed. If you're building this out properly, an [idempotency key for safe API retries](/idempotency-key-api-safe-retries) uses the same mechanism at the HTTP layer.

## A short checklist before you ship

- Every queue that can fail has a DLQ or a `failed_jobs` equivalent attached.
- The attempt threshold is set deliberately, paired with backoff for transient-heavy work.
- DLQ depth is alarmed, not just logged.
- Consumers are idempotent so replay is safe.
- You have a documented way to inspect and replay — not a Slack thread where someone remembers the artisan command.

## FAQ

**What's the difference between a dead letter queue and just retrying?**
Retrying handles transient failures — the same message will probably succeed on a later attempt. A DLQ handles the messages that *won't* ever succeed, by moving them out of the main queue after a set number of attempts so they stop blocking everything else. You want both: retries for the recoverable failures, a DLQ as the floor beneath them.

**How do I set the max receive count / number of tries?**
Base it on how transient your failures are. Work that fails mostly on flaky external calls justifies 3–5 attempts with exponential backoff, so temporary blips get several spaced-out chances. Work that fails deterministically on bad data should have a lower ceiling — extra attempts just burn resources and delay the alert. There's no universal number; there's the number that fits your failure mode.

**Does Laravel have a real dead letter queue?**
Not by that name, but the `failed_jobs` table plays the exact role: jobs that exhaust their tries land there instead of redelivering forever, and you replay them with `queue:retry` or discard them with `queue:forget`. If your queue driver is SQS, you can *also* attach a native SQS DLQ via a redrive policy — the two layers coexist.

**Why do replayed messages sometimes cause duplicates?**
Because a message can partially complete before it fails — say it charges a card, then crashes before saving the order. When you replay it, the completed part runs again. The fix is idempotency: record a stable message key when you finish the work, and short-circuit if you see that key again. Without it, DLQ replay is a duplicate-side-effect machine.

Dead letter queues don't make failures go away — nothing does. What they do is convert a silent, resource-eating retry loop into a visible pile of messages you can inspect, fix, and replay on your own terms. Get the threshold, the alerting, and the idempotency right, and one poison message becomes a ticket instead of an outage. The next step, if you haven't already: go find every queue in your system that doesn't have a DLQ attached, and give it one.
