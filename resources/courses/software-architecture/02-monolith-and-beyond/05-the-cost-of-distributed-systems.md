---
title: "The cost of distributed systems"
slug: the-cost-of-distributed-systems
seo_title: "The Cost of Distributed Systems (and the 8 Fallacies)"
seo_description: "The network is not free: latency, partial failure, no distributed transactions, eventual consistency, harder debugging - and the 8 fallacies of distributed computing."
---

The moment two parts of your system talk over a network instead of a function call, you
enter a different world with harder rules. The cost of distributed systems is the fine print
behind the
[microservices](/course/software-architecture/monolith-and-beyond/microservices-overview)
decision: what you are really signing up for. None of it is a reason to never distribute -
it is a reason to distribute only when the benefit is worth this bill.

## A function call is not a network call

In a monolith, calling another module looks like this, and it either returns or throws,
instantly and reliably:

```php
$total = $billing->charge($customerId, $amountCents);
```

Turn `Billing` into a separate service and that same line now means: serialize the request,
send it across a network, hope it arrives, hope the other service is up, hope it answers
before you give up waiting, then deserialize the reply. The code can look almost identical.
The reality underneath is completely different.

## Latency: calls take real time

A local call is nanoseconds. A network call is milliseconds - thousands of times slower -
and they add up. If handling one request fans out to five services, and each does the same,
you can accidentally build a chain where a single user click triggers dozens of round trips.

```text
Monolith:   click -> [ do everything in-process ] -> done   (fast)

Distributed: click -> A -> B -> C -> B -> D ...             (each hop = network latency)
```

The subtler trap is the tail. Each hop is usually fast but occasionally slow, so once you
chain several of them, *something* is slow on nearly every request - the wider the fan-out,
the more often you land on somebody's bad moment. Averages look fine while real users
routinely wait. So the fix is deliberate design (fewer, coarser calls; caching; parallel
work), not just making each service faster - it does not come free like it did in the
monolith.

## Partial failure: the hardest new problem

In a monolith the app is either up or down. In a distributed system, **some parts can fail
while others keep running** - and worse, a call can time out so you do not even know whether
it succeeded.

Did the `charge` go through and only the *reply* got lost? If you retry, you might charge the
customer twice. If you do not, you might never charge them at all. This ambiguity does not
exist in a monolith, and handling it (timeouts, retries, and making operations safe to repeat
- "idempotent") is a big part of distributed design.

## No distributed transactions

In a monolith, one database transaction makes a multi-step change all-or-nothing:

```php
DB::transaction(function () use ($order) {
    $order->save();          // step 1
    $this->billing->charge(  // step 2 - same database, same transaction
        $order->customerId,
        $order->totalCents
    );
});
// Either both happen, or neither does. Consistency for free.
```

Split `Orders` and `Billing` into services with separate databases and this guarantee is
**gone**. There is no transaction spanning two databases over a network. You can save the
order and then fail to charge - now your data is inconsistent, and you must repair it in
application code (retries, compensating actions - [the saga pattern](/course/software-architecture/event-driven-architecture/the-saga-pattern), named in a later
chapter).

## Eventual consistency

Because you cannot update everything atomically, distributed systems often accept
**eventual consistency**: after a change, different services are briefly out of sync and
"catch up" a moment later.

A monolith is *strongly* consistent - read right after a write and you see the new value.
Distributed, you might place an order and have it not yet appear in the reporting service for
a few seconds. Sometimes that is fine; sometimes it confuses users or breaks logic that
assumed instant consistency. Either way it is now *your* problem to design around.

## Harder debugging and operations

One request that hops across services has no single stack trace and no single log file. To
follow "what happened to order #4821" you need **distributed tracing** (a shared id threaded
through every hop) and centralized logging, or you are blind - and keeping all those moving
parts deployed, monitored and secure becomes a job of its own.

## The fallacies of distributed computing

These traps are old and well known - the **eight fallacies of distributed computing** (Peter
Deutsch and colleagues at Sun Microsystems). Each is a false assumption that feels true
because it *was* true inside a single process:

```text
1. The network is reliable.
2. Latency is zero.
3. Bandwidth is infinite.
4. The network is secure.
5. Topology doesn't change.
6. There is one administrator.
7. Transport cost is zero.
8. The network is homogeneous.
```

Every one of them is false in a real network, and every distributed-systems bug is, at
bottom, someone having quietly believed one of them. Memorizing the list is less important
than the habit it builds: whenever a call crosses the network, assume it can be slow, fail,
or be tampered with, and design for that.

## Common mistake

Treating a remote call like a local one - assuming it is fast, always succeeds, and returns
instantly. That single assumption is fallacies 1 and 2, and it produces systems that work in
testing (where the network is perfect) and fall over in production (where it is not). If a
call leaves the process, wrap it in a timeout and decide up front what happens when it fails.

## FAQ

### Does this mean distributed systems are a bad idea?

No. It means they have a real cost, so you pay it on purpose for a real benefit (team
autonomy, independent scaling), not by accident. A monolith avoids all of these problems,
which is exactly why it is the right default until you genuinely need distribution.

### What is partial failure, in one sentence?

It is when one part of the system fails while the rest keeps running - including the nasty
case where you cannot tell whether a call succeeded or not - a failure mode that simply does
not exist inside a single process.

### Why can't I just retry a failed network call?

You can, but only if the operation is safe to repeat (idempotent). Blindly retrying a
"charge the customer" call can bill them twice if the first call actually succeeded and only
its reply was lost. Designing for safe retries is a core distributed-systems skill.
