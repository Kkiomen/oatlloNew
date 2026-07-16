---
title: "The saga pattern"
slug: the-saga-pattern
seo_title: "The Saga Pattern: Transactions Across Services"
seo_description: "The saga pattern manages a transaction across services without a distributed transaction: local steps with compensating actions on failure."
---

Inside one database, a transaction is easy: several changes either all commit or all roll
back. But a business action that spans **several services or aggregates** cannot use one
database transaction - each service owns its own data, and
[distributed transactions are a cost you want to avoid](/course/software-architecture/monolith-and-beyond/the-cost-of-distributed-systems).
So how do you keep "reserve stock, charge card, ship parcel" consistent when any step might
fail? The **saga pattern** is the standard answer.

## A saga is local steps plus compensations

A **saga** breaks the big transaction into a **sequence of local steps**, each in its own
service, each with its own small transaction. There is no global rollback. Instead, every
step that changes something also has a **compensating action** - an operation that undoes it.
If a later step fails, the saga runs the compensations for the steps already done, in
reverse.

```text
Forward:   Reserve stock  ->  Charge card  ->  Ship parcel
                                                   X  fails here
Compensate (reverse):  Refund card  <-  Release stock
   No global rollback. Each undo is its own action.
```

Note that "release stock" is not a database rollback - it is a real business operation that
happens to reverse the earlier one. A refund is not un-charging; it is a new, opposite fact.
This is the mental shift the saga pattern asks for.

```php
final class PlaceOrderSaga
{
    public function __construct(
        private Inventory $inventory,
        private Billing $billing,
        private Shipping $shipping,
    ) {}

    public function run(Order $order): void
    {
        $this->inventory->reserve($order);      // step 1
        try {
            $this->billing->charge($order);     // step 2
            try {
                $this->shipping->ship($order);  // step 3
            } catch (Throwable $e) {
                $this->billing->refund($order);         // compensate 2
                $this->inventory->release($order);      // compensate 1
                throw $e;
            }
        } catch (Throwable $e) {
            $this->inventory->release($order);          // compensate 1
            throw $e;
        }
    }
}
```

The example is deliberately simple and synchronous to show the shape; real sagas usually run
each step and compensation as separate messages so a crash mid-way can be resumed.

## Two flavors: choreography and orchestration

Sagas come in the same two coordination styles from earlier in this chapter,
[choreography vs orchestration](/course/software-architecture/event-driven-architecture/choreography-vs-orchestration):

- **Choreography-based saga.** No coordinator. Each service reacts to the previous step's
  event, does its work, and emits the next event. On failure it emits a failure event that
  triggers the earlier services to compensate. Maximum decoupling, but the whole saga only
  exists as a web of events - hard to see and to debug.
- **Orchestration-based saga.** A **saga orchestrator** owns the sequence: it sends each
  command, waits for the result, and on failure sends the compensating commands in reverse.
  One readable place holds the flow and the recovery logic, at the price of a coordinator
  that knows every step (the code above is an orchestration-style saga).

Short sagas with few steps often work well as choreography. Longer ones, or any where the
failure and compensation logic matters and must be auditable, are usually clearer as
orchestration.

## Consistency is eventual, not instant

A saga does not give you the all-or-nothing guarantee of a database transaction. For a
window of time the system is partially done - stock reserved but card not yet charged. The
saga's job is to drive the system to a **consistent end state**: either fully completed, or
fully compensated back. This is eventual consistency again, and you must design steps to be
**idempotent** (safe to retry) because in a distributed system messages can arrive twice.

The part that catches teams off guard: a compensation can fail too. The refund call times
out, the release-stock service is down. A saga that assumes compensations always succeed has
just moved the problem, not solved it. So compensations get the same treatment as forward
steps - retried, idempotent, and if they keep failing, parked somewhere a human will see
them. "It's charged but we couldn't refund" is not a state that can silently vanish.

## Common mistake

Writing a saga with forward steps but **no compensating actions** - "the happy path always
works, we'll handle failures later". Then step 3 fails in production, the money is already
charged, the stock is already reserved, and nothing undoes them: the customer is charged for
an order that never ships. The compensations are not an optional extra; they are the whole
point of the pattern. If a step has no meaningful way to be undone, that is a design signal -
reorder the saga so the hardest-to-reverse step comes **last**, when everything else has
already succeeded.

## FAQ

### What is the saga pattern?

A way to manage a business transaction that spans multiple services or aggregates without a
distributed transaction. The work is split into local steps, each with a compensating action;
if a step fails, the already-completed steps are undone by running their compensations in
reverse.

### What is a compensating action?

A business operation that reverses the effect of a previous saga step - for example a refund
that undoes a charge, or releasing stock that was reserved. It is a new, opposite action, not
a database rollback.

### Choreography-based or orchestration-based saga - which should I use?

Choreography (services react to events, no coordinator) fits short, simple sagas and keeps
services decoupled. Orchestration (a coordinator drives the steps and compensations) fits
longer sagas where you need one clear, auditable place for the flow and its failure handling.
