---
title: "What is event-driven architecture?"
slug: what-is-event-driven-architecture
seo_title: "What Is Event-Driven Architecture? A Clear Intro"
seo_description: "Event-driven architecture: components communicate by publishing and subscribing to events instead of direct calls. Learn loose coupling and brokers."
---

In most code, one part calls another directly and waits for an answer. The order module
calls the email module, the email module calls the invoice module, and so on. This is
**request/response**: the caller knows exactly who it is talking to and pauses until the
callee returns. It is simple, but it also means everyone is wired to everyone else.
**Event-driven architecture** proposes a different style: parts of the system announce
that something happened, and other parts react - without the announcer knowing who is
listening.

## Publish and subscribe instead of call

The core idea is **publish/subscribe**. When something important happens, a component
**publishes** an event (a message describing the fact). Any number of components can
**subscribe** to that event and do their own work. The publisher never names the
subscribers.

```text
Request/response (direct calls):

  [ Orders ] --calls--> [ Email ]
           \--calls--> [ Invoicing ]
           \--calls--> [ Analytics ]
   Orders must know all three, and wait for each.

Event-driven (publish/subscribe):

  [ Orders ] --publishes "OrderPlaced"--> ( broker )
                                             |  |  |
                        [ Email ] [ Invoicing ] [ Analytics ]
   Orders knows none of them. They subscribe.
```

In the second picture, adding an `Analytics` subscriber does not touch the `Orders` code
at all. You just subscribe a new listener to `OrderPlaced`. That is the payoff.

## Loose coupling in time and space

Event-driven systems are loosely coupled in two ways worth naming:

- **Coupling in space.** The publisher does not know the subscribers' names, locations, or
  even how many exist. You add or remove reactions without editing the source of the event.
- **Coupling in time.** With a broker in the middle, the subscriber does not have to be
  running at the exact moment the event is published. The message waits until it can be
  handled.

Compare that to a direct call, which fails if the callee is down and forces the caller to
wait for a reply. Events let the pieces move independently.

## A message broker carries the events

In a single process, "publishing an event" can be a simple in-memory dispatch (you saw the
seed of this with [domain events](/course/software-architecture/ddd-tactical-patterns/domain-events)).
Across services or processes, the events usually travel through a **message broker** - a
piece of infrastructure like **RabbitMQ** or Kafka that receives published messages and
delivers them to subscribers. The broker is what buys you time decoupling: it holds the
message until a consumer is ready.

```php
// The publisher only knows the event and a generic bus.
final class PlaceOrder
{
    public function __construct(private EventBus $bus) {}

    public function handle(Order $order): void
    {
        // ...persist the order...
        $this->bus->publish(new OrderPlaced($order->id, $order->totalCents));
    }
}
```

`PlaceOrder` has no reference to email, invoicing, or analytics. It hands one fact to the
bus and moves on. Whether that bus is an in-memory dispatcher or a RabbitMQ connection is
an infrastructure detail. (Oatllo has a separate **RabbitMQ course** if you want to carry
these events over a real broker; here we stay at the architecture level.)

One thing that surprises people the first time: most brokers deliver **at least once**, not
exactly once. A network hiccup between the broker and a consumer can cause the same
`OrderPlaced` to be delivered twice. So subscribers have to be **idempotent** - handling the
same event a second time must not send two emails or write two invoices. Plan for the
duplicate; it will happen eventually.

## Common mistake

Reaching for a broker and event-driven style everywhere, including inside a small app that
would be perfectly happy with a direct method call. Events add real cost: the flow is no
longer visible in one stack trace, ordering and failures get harder to reason about, and
you now operate a broker. Use events where the decoupling earns its keep - across module or
service boundaries, or where many independent reactions must fan out from one fact. Inside a
tight, well-understood flow, a plain call is clearer.

## FAQ

### What is event-driven architecture in simple terms?

A style where components communicate by publishing events ("this happened") and subscribing
to the ones they care about, instead of calling each other directly. The publisher does not
know who reacts, which keeps the parts loosely coupled.

### What is a message broker?

Infrastructure that sits between publishers and subscribers. It receives published messages
and delivers them to interested consumers, holding a message until a consumer is ready.
RabbitMQ and Kafka are common examples.

### How is this different from request/response?

In request/response the caller names the callee and waits for a reply. In event-driven
communication the publisher announces a fact and continues; subscribers react on their own
time. One is a direct conversation, the other is a broadcast.
