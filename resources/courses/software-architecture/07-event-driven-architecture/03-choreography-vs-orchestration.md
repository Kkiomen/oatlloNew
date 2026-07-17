---
title: "Choreography vs orchestration"
slug: choreography-vs-orchestration
seo_title: "Choreography vs Orchestration in Event Systems"
seo_description: "Choreography vs orchestration: coordinate a multi-step process with services reacting to events, or a coordinator directing the steps. Trade-offs inside."
---

A single business action often needs several steps across several components. Placing an
order might mean: reserve stock, charge the card, create an invoice, send a confirmation.
Who makes sure all four happen, in the right order? There are two classic answers, and the
choice shapes how easy the system is to change and to understand: **choreography** and
**orchestration**.

## Choreography: everyone reacts to events

In **choreography** there is no boss. Each service listens for [events](/course/software-architecture/event-driven-architecture/what-is-event-driven-architecture) and reacts by doing
its job and (usually) publishing its own event, which the next service reacts to. The
process emerges from the chain of reactions, like dancers who each know their steps.

```text
[ Orders ]   --OrderPlaced-->   [ Inventory ]
                                     |
                              StockReserved
                                     |
                                [ Billing ]  --PaymentTaken-->  [ Email ]
   No central coordinator. Each service reacts to the event before it.
```

```php
// Billing does not know about the whole flow.
// It just reacts to one event and emits another.
final class ReserveStockThenCharge
{
    public function __construct(private EventBus $bus) {}

    public function onStockReserved(StockReserved $e): void
    {
        // ...charge the customer...
        $this->bus->publish(new PaymentTaken($e->orderId));
    }
}
```

The strength is **decoupling**: each service only knows the events just before and after it.
Adding a step often means subscribing a new listener, without editing the others. The
weakness is **visibility**: the overall process is not written down anywhere. To understand
"what happens when an order is placed", you have to trace events across many services and
hold the whole dance in your head.

## Orchestration: a coordinator directs the steps

In **orchestration** one component - the **orchestrator** - owns the process. It calls each
step in turn (often by sending [commands](/course/software-architecture/event-driven-architecture/events-vs-commands)) and decides what happens next based on the results.
The steps do not need to know about each other; they only know the orchestrator.

```text
              +------------------------------+
              |     Order orchestrator       |
              +------------------------------+
               |          |          |     |
        ReserveStock   Charge   CreateInvoice  SendEmail
   One place holds the whole sequence and its decisions.
```

```php
// The orchestrator holds the whole flow in one readable place.
final class PlaceOrderProcess
{
    public function __construct(
        private Inventory $inventory,
        private Billing $billing,
        private Mailer $mailer,
    ) {}

    public function run(Order $order): void
    {
        $this->inventory->reserve($order);
        $this->billing->charge($order);
        $this->mailer->sendConfirmation($order);
    }
}
```

The strength is **visibility**: the whole process lives in one file you can read top to
bottom, which makes failures and branching logic easy to follow. The weakness is
**coupling**: the orchestrator has to know about every step, so it becomes a busy hub that
changes whenever the process changes.

## The trade-off in one line

- **Choreography** buys maximum decoupling and pays with lost visibility.
- **Orchestration** buys clear visibility and pays with a central point that knows a lot.

Neither is "correct". Short, stable flows with lots of independent reactions lean toward
choreography. Longer processes with branching, retries and business rules that must be
auditable lean toward orchestration. Many real systems mix both.

One asymmetry that only shows up later: adding a cross-cutting rule. "Cancel the whole
process if the customer aborts within a minute" is a one-line change in an orchestrator,
which already sees every step. In choreography there is no such vantage point - you would
have to teach that rule to each service separately. Decoupling has a price, and this is
where you pay it.

## Common mistake

Growing a choreography until nobody can say what the full process does. Each new listener
seems harmless, but after a dozen of them the flow only exists as a web of events with no
single description - and a bug in the ordering is nearly impossible to trace. When a process
has real steps, rules and failure handling that people need to reason about, an explicit
orchestrator is usually worth the coupling.

## FAQ

### What is the difference between choreography and orchestration?

In choreography, services react to each other's events with no central coordinator, so the
process is implicit. In orchestration, one coordinator directs the steps explicitly. The
first maximizes decoupling; the second maximizes visibility.

### Which is better, choreography or orchestration?

Neither in general. Choose choreography for simple, stable flows where decoupling matters
most; choose orchestration when the process is long or branchy and you need one clear,
auditable place that describes it. Real systems often combine them.

### Does choreography mean no coordination at all?

No. The coordination still happens - it is just spread across the services as they react to
events, rather than gathered in one coordinator. There is no boss, but there is still order.
