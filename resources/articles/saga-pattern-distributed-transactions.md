---
name: "The Saga Pattern for Distributed Transactions"
slug: saga-pattern-distributed-transactions
short_description: "How the saga pattern coordinates distributed transactions across microservices using local transactions, compensations, and eventual consistency."
language: en
published_at: 2027-02-01 09:00:00
is_published: true
tags: [microservices, distributed-systems, architecture, php]
---

The first time I split a monolith into services, I broke a checkout flow in a way that took two days to understand. An order was created, the customer got charged, and then inventory reservation failed because a warehouse service was mid-deploy. There was no shared database transaction to roll everything back. That mess is exactly the problem the **saga pattern** solves: it lets you run one logical transaction across several services without holding a distributed lock, and it gives you a disciplined way to undo the parts that already succeeded.

If you have ever wished for `BEGIN TRANSACTION ... COMMIT` to span three microservices, this article is for you. We will walk through what a saga actually is, the two coordination styles, a concrete order flow with compensations, and the failure handling that separates a working saga from a data-corruption generator.

## Why distributed transactions are hard

In a single database, ACID does the heavy lifting. You wrap several writes in a transaction, and either all of them land or none do. The database holds locks until you commit.

Across service boundaries that guarantee disappears. Each service owns its own database, and there is no shared connection to enroll in a transaction. The classic answer used to be two-phase commit (2PC), where a coordinator asks every participant to *prepare*, then tells them all to *commit*. It works on paper. In practice 2PC holds locks across the network for the whole duration, blocks progress when the coordinator dies mid-round, and most REST or message-based services do not support the prepare/commit protocol at all.

So we give up on atomic-everywhere and accept a different bargain.

## What the saga pattern actually is

A saga is a **sequence of local transactions**. Each step runs entirely inside one service, commits normally in that service's own database, and then triggers the next step. There is no global lock. Each local transaction is small, fast, and independently durable.

The catch is the undo path. Because there is no rollback across the whole chain, a saga defines a **compensating transaction** for each step. If step 4 fails, the saga runs the compensations for steps 3, 2, and 1 in reverse. Compensation is a *semantic* rollback, not a technical one. You do not "un-commit" a payment; you issue a refund. You do not delete a row that another service may already have read; you write a reversing entry.

Two properties fall out of this design and you cannot ignore either one:

- **Eventual consistency.** Between the first local commit and the last, the system is in an intermediate state that is visible to other readers. An order can exist for a few hundred milliseconds before its payment is confirmed. Your data model and your UI have to tolerate that window.
- **No isolation.** Because each step commits immediately, other transactions can see partial results. Sagas trade the "I" in ACID for availability, and you compensate for the loss with careful state design (for example, an `orders` row that starts in a `PENDING` status).

## Choreography vs orchestration

There are two ways to decide what runs next. Picking one is the biggest architectural choice you will make with sagas.

### Choreography

Each service reacts to events and publishes its own. No central brain. The order service emits `OrderCreated`; the inventory service listens, reserves stock, and emits `InventoryReserved`; the payment service listens for that and charges the card. This pairs naturally with an [event-driven architecture](/blog/event-driven-architecture-practical-introduction).

Choreography is genuinely nice for two or three steps. It has no single point of failure and services stay loosely coupled. The trouble starts as the flow grows: the business process is not written down anywhere. It lives implicitly in who-subscribes-to-what. I once traced a six-step choreographed saga across four repos to answer a simple question about when a refund fires, and it was miserable. Cyclic event dependencies also sneak in easily.

### Orchestration

A central **orchestrator** owns the workflow. It holds the saga state, calls each service in turn (via commands or synchronous calls), and decides what to do with each reply, including which compensations to run on failure. The order flow lives in one place you can read top to bottom.

Orchestration costs you a component to build and operate, and that orchestrator must not become a disguised monolith that knows too much about every service's internals. Keep it dumb about business rules and smart about sequencing. For anything past roughly four steps, or any flow with branching, I reach for orchestration. The debuggability is worth it.

## A worked example: placing an order

Here is a three-step order saga: create the order, reserve inventory, charge payment. I will show it as an orchestrator in PHP-flavored pseudocode so the control flow is explicit.

```php
class PlaceOrderSaga
{
    public function execute(OrderRequest $req): void
    {
        // Step 1 — local transaction in the Order service
        $order = $this->orders->create($req, status: 'PENDING');

        try {
            // Step 2 — local transaction in the Inventory service
            $this->inventory->reserve(
                orderId: $order->id,
                items: $req->items,
                idempotencyKey: "reserve:{$order->id}"
            );

            // Step 3 — local transaction in the Payment service
            $this->payments->charge(
                orderId: $order->id,
                amount: $req->total,
                idempotencyKey: "charge:{$order->id}"
            );

            $this->orders->markConfirmed($order->id);
        } catch (StepFailed $e) {
            // Run compensations in reverse for whatever succeeded
            $this->compensate($order, $e);
            throw $e;
        }
    }

    private function compensate(Order $order, StepFailed $e): void
    {
        // Only reverse steps that actually completed.
        if ($e->completed('reserve')) {
            $this->inventory->release(
                orderId: $order->id,
                idempotencyKey: "release:{$order->id}"
            );
        }
        // Step 1 has no external side effect to refund,
        // so we mark the order cancelled rather than delete it.
        $this->orders->markCancelled($order->id, reason: $e->getMessage());
    }
}
```

Read the compensation logic carefully, because that is where sagas live or die. If the payment charge fails, we release the inventory reservation and flip the order to `CANCELLED`. We do not delete the order row. A cancelled order that other systems already saw is a fact; erasing it would lie to anyone who read it.

Notice what each compensation is:

- `charge` is compensated by a **refund**, never by trying to "un-charge". If the money moved, you move it back.
- `reserve` is compensated by `release`, which returns the held units to available stock.
- `create` has no monetary side effect, so its compensation is a status change to `CANCELLED`.

The compensations run in reverse order of the forward steps. That mirrors how a stack unwinds and keeps the reasoning simple.

## Idempotency, retries, and why compensations must be retryable

Networks lose messages. A service will occasionally process the same command twice, or crash after committing but before acknowledging. If your saga cannot survive that, it will double-charge customers.

Three rules keep you safe:

- **Every step must be idempotent.** Reserving inventory for `orderId=42` twice should reserve it once. The standard mechanism is an idempotency key that the receiving service records and checks. We cover the mechanics in [idempotency keys for safe API retries](/blog/idempotency-key-api-safe-retries), and the same key doubles as your dedup guard here.
- **Retries need backoff.** A step that fails on a transient error should be retried, not hammered. Use [exponential backoff with jitter](/blog/exponential-backoff-retry) so a struggling service gets room to recover instead of a thundering herd.
- **Compensations must be retryable and commutative.** A compensation can itself fail and get retried, and it may arrive out of order relative to other events. `release inventory` applied twice must equal applied once, and it must not matter whether it lands before or after some unrelated event. If a compensation is not safe to repeat, your recovery path is a new source of bugs.

There is a subtle trap worth naming: the failure that triggers compensation might happen *because* a step timed out, even though the step actually succeeded on the other side. That is why steps are idempotent and why compensations tolerate "nothing to undo." A `release` for a reservation that was never confirmed should be a no-op, not an error.

## Failure handling and saga state

Someone has to remember where a saga is. If the orchestrator crashes between reserving inventory and charging payment, it must resume, not lose the order. So the saga state itself is persisted, typically as a row per saga instance recording the current step and each step's outcome.

A common and reliable approach is the transactional outbox: within the same local transaction that commits a step, you also write the "next command" or event to an outbox table. A separate relay publishes it. That closes the gap where a service commits its work but dies before telling anyone.

When retries are exhausted, you have two honest options:

- **Backward recovery** runs the compensations and cancels the saga. This is the default for the order flow, since a customer would rather see "payment failed, nothing charged" than a half-order.
- **Forward recovery** parks the saga and retries the failing step until it eventually succeeds, useful when the operation must complete (for example, a downstream ledger posting). This usually needs a human-visible dead-letter queue and an alert.

Pick per flow. Not every saga wants the same recovery direction.

## Common pitfalls

- Treating a compensation as a literal delete. Refund, reverse, or cancel; do not pretend the earlier fact never happened.
- Forgetting idempotency and discovering it in production as duplicate charges.
- Choreography for a long or branching workflow, where the process becomes impossible to see in one place.
- Building an orchestrator that hoards business rules until it is a monolith with extra network hops.
- Modeling entities as if the intermediate state is invisible. Give records an explicit `PENDING` status and design reads around it.
- Assuming compensations always succeed. They fail too, so they need retries, dead-letter handling, and alerting.
- Reaching for a saga when a single database transaction would do. If the work lives in one service, keep it there.

## FAQ

### Is the saga pattern the same as two-phase commit?

No, and they solve the problem from opposite ends. 2PC aims for atomic isolation by holding locks across all participants until a coordinated commit, which blocks and does not scale across independent services. A saga gives up global atomicity and isolation, commits each step locally, and repairs failures with compensating transactions. You choose availability and eventual consistency over strict, locked consistency.

### When should I not use a saga?

When the whole operation fits inside one service and one database, use a plain ACID transaction. Sagas add real complexity: state persistence, idempotency, compensations, and monitoring. Only pay that cost when a business operation genuinely spans multiple services that own their own data.

### How does a saga relate to CQRS and event sourcing?

They complement each other. Sagas often ride on the events that a [CQRS](/blog/cqrs-pattern-when-to-separate-reads-and-writes) or event-sourced system already emits, and orchestrators frequently persist their state as a stream of events. None of the three requires the others, but they show up together because they share an event-driven backbone.

### What happens if a compensation itself fails?

You retry it with backoff, because compensations are designed to be idempotent and retryable. If retries are exhausted, the saga instance goes to a dead-letter state that raises an alert for manual intervention. What you must never do is silently drop it, since that leaves inventory held or money unrefunded.

## Wrapping up

The saga pattern is not a way to get distributed ACID back. It is a deliberate trade: give up cross-service isolation and immediate consistency, and in return get a workflow that survives partial failure without a distributed lock. Model each step as an idempotent local transaction, define a real compensation for every step, persist the saga state, and choose orchestration once the flow outgrows a couple of steps.

Start small. Take one multi-service flow you already have, the order-then-charge kind, and write down its steps and their compensations before touching code. Half the value of the pattern is the clarity that exercise forces. The other half is the two AM page you will not get because a failed charge quietly released its inventory reservation instead of stranding it.