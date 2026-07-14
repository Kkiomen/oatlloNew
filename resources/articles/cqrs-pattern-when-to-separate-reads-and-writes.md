---
name: "CQRS Explained: When to Separate Reads and Writes"
slug: cqrs-pattern-when-to-separate-reads-and-writes
short_description: "A practical guide to the CQRS pattern: what it is, when separating reads and writes pays off, the real costs, and when plain CRUD wins."
language: en
published_at: 2026-12-07 09:00:00
is_published: true
tags: [architecture, cqrs, laravel, php]
---

The first time someone on my team proposed the **CQRS pattern**, it was to fix a dashboard that took eleven seconds to load. The reasoning went: our writes are simple, our reads are gnarly joins across six tables, so let's split them. We did. The dashboard got fast. But we also spent two weeks building plumbing that, in hindsight, a materialized view would have handled in an afternoon. That experience is basically this whole article in miniature: CQRS can be exactly the right call, and it can also be the expensive answer to a question you didn't need to ask.

So let's get concrete about what CQRS actually is, where separating reads from writes earns its keep, and where you should just write a controller and move on.

## What CQRS actually means

CQRS stands for **Command Query Responsibility Segregation**. Strip away the ceremony and it's one idea: the model you use to *change* data doesn't have to be the same model you use to *read* it.

- A **command** changes state and returns nothing meaningful (or just a success signal). `PlaceOrder`, `CancelSubscription`, `UpdateProfile`.
- A **query** returns data and changes nothing. `GetOrderSummary`, `ListActiveSubscriptions`.

In a normal CRUD app these two live in the same object. Your `Order` model validates, persists, and also serializes itself for the API response. CQRS says: pull those apart. Have a write path optimized for enforcing rules, and a read path optimized for answering questions fast.

Here's the part people miss. CQRS is a **spectrum**, not a switch you flip. At the light end, it's just separate classes hitting the same database:

```php
// Write side: a command and its handler
final class CancelSubscription
{
    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $reason,
    ) {}
}

final class CancelSubscriptionHandler
{
    public function handle(CancelSubscription $command): void
    {
        $subscription = Subscription::findOrFail($command->subscriptionId);
        $subscription->cancel($command->reason); // domain logic lives here
        $subscription->save();
    }
}
```

```php
// Read side: a dedicated query, tuned for display
final class ActiveSubscriptionsQuery
{
    public function forCustomer(int $customerId): Collection
    {
        return DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->where('subscriptions.customer_id', $customerId)
            ->where('subscriptions.status', 'active')
            ->select('subscriptions.id', 'plans.name', 'subscriptions.renews_at')
            ->get();
    }
}
```

Notice what's happening: the command handler loads a full Eloquent model because it needs behavior and validation. The query skips Eloquent entirely and hits the query builder because it only needs a handful of columns for a screen. Same database, two mindsets. That's already CQRS, and it costs you almost nothing.

At the heavy end, the read and write sides live in **separate datastores**. You write to a normalized Postgres schema, an event updates a denormalized read store (Elasticsearch, a Redis projection, a flattened reporting table), and queries hit that read store. The two are kept in sync asynchronously, which is where the word you've probably heard enters the room: **eventual consistency**.

## CQRS is not event sourcing

This trips up a lot of people, so I'll be blunt about it. CQRS and event sourcing get mentioned in the same breath so often that many developers assume they're the same thing. They are not.

- **CQRS** is about separating the read model from the write model.
- **Event sourcing** is about storing state as a sequence of events instead of as current rows, so you rebuild state by replaying history.

You can do CQRS with a boring relational database and zero events. You can do event sourcing without ever splitting your read and write models. They pair well (an event-sourced write side naturally produces the events you'd use to build read projections), but they solve different problems. If someone tells you "you can't do CQRS without event sourcing," they've conflated the two. If this async, event-driven flavor of CQRS is where you're headed, it's worth understanding the underlying model first; I wrote a [practical introduction to event-driven architecture](/blog/event-driven-architecture-practical-introduction) that covers the moving parts.

## When separating reads and writes actually pays off

I'll only reach for anything beyond light-touch CQRS when at least one of these is actually true.

**Your reads and writes have wildly different shapes.** You write one order with its line items, but you read an aggregated sales report that spans months and joins customers, products, and regions. Forcing both through one model means every read drags around write-side baggage, and every write worries about report-friendly denormalization. Splitting them lets each side be honest about its job.

**Reads and writes scale differently.** Plenty of systems read 100x or 1000x more than they write. A product catalog, a news feed, a public API. When read load dwarfs write load, a separate read store you can replicate and cache independently is a real architectural lever, not a fashion statement.

**The domain is genuinely complex on the write side.** If your write path has thick invariants (money, inventory, regulatory rules), you want that model focused purely on protecting those rules. Dragging read concerns (pagination, display formatting, joins for a UI) into it muddies the domain logic. CQRS keeps the write model clean.

**You need denormalized read models.** Search indexes, precomputed aggregates, per-screen projections. If you're already maintaining a read-optimized copy of your data, you're effectively doing CQRS whether you named it that or not. Making it explicit just gives the pattern a shape.

Notice that none of these say "because it's modern" or "because microservices." The trigger is always a concrete mismatch between how you write and how you read.

## The costs nobody puts on the slide

Every CQRS talk shows the clean diagram. Fewer of them dwell on what you're signing up for.

**More moving parts.** Two models instead of one. Maybe two datastores. A sync mechanism between them. Every one of those is code to write, deploy, monitor, and debug at 2 a.m. The light version keeps this cheap; the heavy version does not.

**Sync complexity.** Once your read store is separate and updated asynchronously, you own a pipeline. Events can fail, arrive out of order, or get processed twice. You need idempotent projectors and a replay strategy for when a projection gets corrupted or you add a new one. This is real engineering, not glue.

**Eventual consistency, and the UX around it.** With an async read model, a user can save something and then not see it on the next screen because the projection hasn't caught up. Milliseconds usually, but not always. You have to design for that: optimistic UI updates, "your change is processing" states, or reading from the write side for the just-mutated record. Consistency questions like this show up all over data-heavy systems; if you want the underlying theory, our piece on [database isolation levels](/blog/database-isolation-levels) is a good companion.

**Cognitive load for the team.** A new developer can understand a CRUD controller in thirty seconds. A command bus, handlers, projectors, and an eventually-consistent read store take serious onboarding. That's a tax you pay on every hire and every context switch.

## When NOT to use CQRS

Here's the opinion the pattern's fans sometimes bury: **most CRUD apps should not use CQRS.**

If your reads and writes look basically the same (you save a record, you fetch a record, maybe with a couple of joins), a single model is not a design smell. It's the correct, boring, maintainable choice. Skip CQRS when:

- Your app is straightforward CRUD with modest traffic. The abstraction buys you nothing and costs you clarity.
- Your team is small and the domain is simple. You'll spend more time maintaining the pattern than it saves.
- The performance problem is local. A slow query is often fixed by an index, a cached result, or a materialized view. Exhaust the simple fixes before restructuring your whole data flow.
- You're adopting it because a conference talk made it sound inevitable. Cargo-culting architecture is how simple systems become unmaintainable ones.

A middle path is underrated here: use the *light* form of CQRS, meaning separate query classes and command handlers against one database, and stop there. You get cleaner code and a natural place to optimize reads, without the sync pipeline or the consistency headaches. That's where a lot of my projects have landed and stayed happily. If you're organizing your write-side data access, the [repository pattern in Laravel](/blog/repository-pattern-laravel) plays nicely with command handlers.

## A realistic Laravel shape

You don't need a framework or a package to start. A tiny command bus dispatching to handlers is enough:

```php
final class CommandBus
{
    public function __construct(private Container $container) {}

    public function dispatch(object $command): mixed
    {
        // Map PlaceOrder -> PlaceOrderHandler by convention
        $handler = $this->container->make(
            $command::class . 'Handler'
        );

        return $handler->handle($command);
    }
}
```

```php
// In a controller: writes go through the bus, reads go direct
public function store(PlaceOrderRequest $request, CommandBus $bus)
{
    $bus->dispatch(new PlaceOrder(
        customerId: $request->user()->id,
        items: $request->validated('items'),
    ));

    return redirect()->route('orders.index');
}

public function index(ActiveOrdersQuery $query)
{
    // No command bus, no domain model, just fast reads
    return view('orders.index', [
        'orders' => $query->forCurrentUser(),
    ]);
}
```

Two things worth calling out. Writes flow through the bus so all your state changes share one entry point, which is handy for logging, transactions, and later moving handlers onto a queue. Reads bypass all of it, because a query has no invariants to protect and no reason to load a domain model. If a read gets slow, you optimize *that query* without touching the write side. That isolation is the everyday payoff, long before you ever consider a second datastore.

## FAQ

### Is CQRS overkill for a small project?

Almost always, yes, in its heavy form. But the light form (separate command handlers and query classes) is cheap enough that it's reasonable even in a small app if you value the separation. The rule of thumb: separate datastores and async sync need justification; separate classes barely do.

### Do I need a message queue to use CQRS?

No. A queue only enters the picture when your read model lives in a separate store updated asynchronously. If both sides hit the same database, there's nothing to synchronize and no queue required. Start synchronous; add async only when read scaling actually demands it.

### What's the difference between CQRS and just using a repository?

A repository abstracts data access behind an interface. CQRS goes further by giving reads and writes *different models entirely*, often different code paths and sometimes different storage. You can use repositories inside a CQRS write side; they're complementary, not competing.

### How does CQRS handle showing a user their own change immediately?

With an async read model, you design around the lag. Common tactics: read the just-changed record from the write side for that one response, update the UI optimistically, or show a brief "processing" state. If you can't tolerate any lag at all, that's a strong signal to keep reads and writes on the same synchronous store.

## The honest conclusion

CQRS is a sharp tool, and like most sharp tools it's dangerous when you grab it for the wrong job. The pattern earns its complexity when your reads and writes genuinely diverge — different shapes, different scale, a rich write-side domain, or denormalized read models you already maintain. In those cases, splitting the two isn't over-engineering; it's the design finally matching the problem.

But if you're building the tenth CRUD admin panel of your career, resist. Use the light version if you like clean seams, or skip it entirely and write the obvious controller. The best architecture is the one your team can still reason about in a year, and for the vast majority of applications that's a single model doing an honest day's work. Reach for CQRS when the pain is real and specific — not because the diagram looked impressive.