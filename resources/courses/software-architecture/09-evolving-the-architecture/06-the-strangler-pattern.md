---
title: "The strangler pattern"
slug: the-strangler-pattern
seo_title: "The Strangler Fig Pattern: Replace Legacy Safely"
seo_description: "The strangler fig pattern replaces a legacy system incrementally by routing slices of functionality to the new one until the old is gone. Safer than a big-bang rewrite."
---

You have a legacy system you need to replace. The tempting plan is a **big-bang rewrite**:
build the new system in parallel, then switch over on one dramatic day. This almost always
goes badly - the new system takes far longer than expected, the old one keeps changing
while you chase it, and switch-over day discovers a hundred behaviours nobody documented.
The **strangler fig pattern** is the safe alternative: replace the old system a slice at a
time, in production, until nothing is left of it.

## The problem: the big-bang rewrite

A rewrite freezes value for months or years. The business still needs new features, so
either the old system keeps changing (and your new one is aiming at a moving target) or it
freezes (and the business suffers). When you finally flip the switch, everything changes at
once, so if anything is wrong you can't tell which of a thousand changes caused it, and
rolling back means going all the way back. The risk is concentrated into a single, enormous
event. That's exactly backwards from how you want to manage risk.

## The pattern: strangle it slice by slice

The name comes from the strangler fig, a plant that grows around a tree, gradually takes
over, and is eventually self-supporting while the original tree is gone. Applied to
software:

1. **Put a routing layer in front** of the legacy system - a proxy, a gateway, or just a
   controller that decides where each request goes. At first it sends everything to the old
   system.
2. **Pick one slice** of functionality - a single feature or
   [bounded context](/course/software-architecture/ddd-strategic-design/bounded-contexts),
   ideally a small, well-understood one. Build *just that slice* in the new system.
3. **Reroute that slice.** Change the router so requests for that feature go to the new
   implementation, and everything else still goes to the old one. Both run in production,
   side by side.
4. **Repeat**, slice by slice. Each iteration the new system does a little more and the old
   one a little less.
5. **Delete the old system** when the router no longer sends it anything. The strangling is
   complete.

```php
<?php
declare(strict_types=1);

// The routing layer: decide per request where it goes.
final class OrdersFacade
{
    public function __construct(
        private NewOrdersModule $new,   // rebuilt, clean
        private LegacyOrders $legacy,   // untouched old code
    ) {}

    public function place(array $request): Response
    {
        // Checkout has been rebuilt, so it goes to the new module.
        // Everything else still runs on the legacy system, unchanged.
        return $this->new->supports($request)
            ? $this->new->place($request)
            : $this->legacy->place($request);
    }
}
```

The router is where all the control lives. It lets you move one feature at a time and, just
as importantly, move it *back* the moment something's wrong. Keep that decision in one place
and drive it from a flag, not scattered `if` checks across controllers - the whole safety
story rests on being able to reroute a slice by changing one value.

The routing is the easy half. The hard half is data. Routing a read to the new system costs
nothing; the trouble starts when old and new both *write* the same tables. Two writers over
shared state is how strangler projects stall - you get divergent rows nobody trusts. Cut
each slice so that one system owns its data outright, and if both must touch a table during
the overlap, make one the source of truth and sync the other, never let both write freely.

## Why it's safer

Every property of the strangler pattern is about shrinking risk:

- **Small, reversible steps.** Each slice is a normal deploy you can roll back by flipping
  the router. There is no point of no return.
- **Value keeps flowing.** The system is live the whole time; you ship the new slices as you
  finish them instead of waiting for a grand finale.
- **The old system teaches you.** Running side by side, you can compare outputs of old and
  new for the same input and catch differences before the old path is retired - impossible
  with a big-bang cutover.
- **Failure is contained.** If a slice is wrong, only that feature is affected, and you know
  exactly which change caused it because you changed one thing.

The cost is patience and the temporary ugliness of two systems running at once, with a
router straddling them. That's a fair price for never having a catastrophic switch-over day.

## Where it fits: monolith to microservices

If you ever *do* move from a monolith to microservices, this is how you do it - not by
rewriting the monolith as services in one go. You've already learned the first move:
[refactor the monolith into modules](/course/software-architecture/evolving-the-architecture/refactoring-a-monolith-into-modules)
with clean boundaries. A module behind a front door is a ready-made strangler slice: put a
router in front, extract that one module into its own service, reroute its calls across the
network, and leave everything else in the monolith. Repeat only for the modules that
genuinely need it - remembering the
[cost of distributed systems](/course/software-architecture/monolith-and-beyond/the-modular-monolith)
means most modules should stay put. The strangler pattern turns "rewrite as microservices",
a terrifying big-bang, into a series of ordinary, reversible deploys.

## FAQ

### What is the strangler fig pattern?

It's a way to replace a legacy system incrementally: put a routing layer in front, rebuild
one slice of functionality at a time in a new system, reroute that slice to the new code,
and repeat until the old system handles nothing and can be deleted. It's named after a plant
that grows around a tree and eventually replaces it.

### Why is the strangler pattern safer than a rewrite?

Because it replaces the system in small, reversible steps that ship continuously, instead of
one massive switch-over. Failures are contained to a single slice, you can roll back by
flipping the router, and the system keeps delivering value the whole time.

### How does the strangler pattern relate to microservices?

If you extract services from a monolith, you do it strangler-style: route one module's
traffic to a new service while the rest stays in the monolith, then repeat only where
needed. Refactoring the monolith into bounded modules first gives you the slices to strangle.
