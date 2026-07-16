---
title: "Refactoring a monolith into modules"
slug: refactoring-a-monolith-into-modules
seo_title: "Refactor a Monolith Into a Modular Monolith"
seo_description: "Turn a big ball of mud into a modular monolith: find seams by bounded context, enforce boundaries, and extract modules incrementally. You rarely need a rewrite."
---

**Refactoring a monolith into modules** starts with the mess it's fixing: the **big ball of
mud**, a monolith with no internal structure. Every class can reach every other, business
logic is tangled with controllers and queries, and a change in one corner breaks something
unrelated across the app. The instinct is to rewrite it. Don't. A rewrite throws away years
of hard-won correctness and stops delivery for months. The safer move is to grow structure
*inside* the monolith, one module at a time, while it keeps running. That's the road to a
[modular monolith](/course/software-architecture/monolith-and-beyond/the-modular-monolith).

## The problem: everything touches everything

In a ball of mud, `OrderController` queries the `users` table directly, the invoicing code
reaches into `Order`'s internals, and a shared `Helpers` class is used by all of them. There
are no seams. You can't test one part in isolation, you can't reason about it alone, and you
can't hand a piece to a teammate without them needing to understand the whole thing. The
[coupling](/course/software-architecture/what-is-software-architecture/boundaries-and-coupling)
is total.

The goal isn't microservices. It's to introduce **boundaries** inside the single
deployable - modules with clear edges that you *could* one day pull apart, but usually
never need to.

## Step 1: find the seams by bounded context

You don't invent module boundaries; you discover them. The map you use is
[bounded contexts](/course/software-architecture/ddd-strategic-design/bounded-contexts) -
the natural divisions in the business where the same word means different things. Ordering,
Billing, Shipping, Catalog: each is a candidate module. Look for clusters of classes that
change together and talk mostly to each other, and cuts where a concept changes meaning (a
"customer" in Billing is an account with a payment method; in Shipping it's an address).
Those clusters and cuts are your seams.

A useful test: **who changes together?** If updating the tax rules always drags in the
ordering code, they may belong in one module or the boundary is in the wrong place.

## Step 2: give each module a front door

Once you've named a module, stop other code from reaching into its guts. Everything outside
should go through a single public entry point, and the internals stay private.

```php
<?php
declare(strict_types=1);

namespace App\Billing;

// The one public class other modules are allowed to call.
final class BillingModule
{
    public function __construct(private ChargeCustomer $charge) {}

    public function chargeForOrder(string $orderId, int $amountCents): void
    {
        // Inside here it can use its own entities, repositories, services -
        // none of which the outside world knows about.
        $this->charge->handle($orderId, $amountCents);
    }
}
```

Now the Ordering module calls `BillingModule::chargeForOrder(...)` and nothing else. It has
no idea Billing uses Stripe, has an `Invoice` entity, or stores anything in a `charges`
table. The boundary is the whole point: modules talk through
[ports](/course/software-architecture/hexagonal-architecture/ports)-style entry points, not
by grabbing each other's classes.

## Step 3: enforce the boundary

A rule nobody enforces is a suggestion. Make crossing the boundary the wrong way *fail*:

- **Namespaces per module** (`App\Billing`, `App\Ordering`) so a violation is visible in
  the `use` statements at the top of a file.
- **Static analysis** (a dependency ruleset, e.g. Deptrac) that fails the build when
  `App\Ordering` imports anything under `App\Billing` except the allowed front door.
- **No shared tables.** Each module owns its data; another module reads it through the front
  door, not with a `JOIN`. Shared tables are a hidden boundary crossing that no namespace
  rule catches.

Without enforcement, the ball of mud grows back the first time someone is in a hurry.

Two things bite here in practice. First, turn the analyser on with a **baseline of existing
violations**, not at zero - a real monolith has thousands on day one, and a rule that fails
every build gets switched off by Friday. Fail only on *new* crossings; let the baseline
shrink as you extract. Second, watch the queue: a module dispatching another module's job
class is a boundary crossing that a `use`-based ruleset misses if jobs share a namespace.
Route those through the front door too, or they become the coupling you thought you fenced.

## Step 4: extract one module, then stop

Do this incrementally, and ship after each step. A realistic loop:

1. Pick the module with the clearest boundary and the most pain (often Billing or
   Notifications - they're leafy and depend on little).
2. Move its classes into the module namespace.
3. Route all outside calls through the front door; delete the direct reaches.
4. Add the enforcement rule so it can't regress.
5. Deploy. The app is still one monolith, just cleaner. Repeat for the next module.

Each iteration leaves the system working and a bit more modular. You are never mid-rewrite
with a half-broken app, because you never rewrote anything - you moved and fenced code that
already worked.

## Why you rarely rewrite

The tangled monolith already encodes a decade of edge cases, bug fixes and "we tried that,
it doesn't work". A rewrite starts from zero and rediscovers those the hard way, while the
business waits. Refactoring in place keeps every one of those lessons and improves structure
under continuous delivery. You reach for a rewrite only when the platform itself is dead
(an unsupported language runtime), and even then the
[strangler pattern](/course/software-architecture/evolving-the-architecture/the-strangler-pattern)
usually beats big-bang.

## FAQ

### What is a big ball of mud?

It's a system with no clear structure: classes reference each other freely, business logic
mixes with infrastructure, and changes ripple unpredictably. It's the default state a
monolith drifts into without deliberate internal boundaries.

### How do I find module boundaries in a monolith?

Use bounded contexts. Look for clusters of classes that change together and talk mostly
among themselves, and for places where a business term changes meaning. Those clusters and
seams are your modules - you discover them from how the business works, not by guessing.

### Should I rewrite a messy monolith instead of refactoring it?

Almost never. A rewrite discards years of embedded knowledge and halts delivery. Introducing
modules incrementally - move a bounded context, fence it behind a front door, enforce the
rule, deploy - improves structure while the app keeps running.
