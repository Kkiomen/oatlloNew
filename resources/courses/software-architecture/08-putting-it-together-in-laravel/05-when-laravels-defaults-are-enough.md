---
title: "When Laravel's defaults are enough"
slug: when-laravels-defaults-are-enough
seo_title: "When to Use DDD in Laravel (and When to Skip It)"
seo_description: "When to use DDD in Laravel and when Laravel's defaults win: for CRUD apps and small teams, fat models and controllers are fine. DDD earns its keep with real complexity."
---

Knowing **when to use DDD in Laravel** matters as much as knowing how. This chapter built a
strict, framework-free module; this lesson is the counterweight: **most of the time, you
should not do that.** The default Laravel way - Eloquent models, resourceful controllers,
form requests - is a genuinely good architecture for a huge number of apps. Reaching past it
without cause is its own kind of mistake.

## The defaults are already an architecture

Laravel's conventions are not the absence of architecture. They are the
[layered architecture](/course/software-architecture/what-is-software-architecture/the-layered-architecture)
from Chapter 1, pre-assembled: a presentation layer (routes, controllers, requests), a data
layer (Eloquent, migrations), and a place for logic in between. For a CRUD app - forms in,
records out, a handful of rules - that stack is fast to build, easy to hire for, and easy to
read.

```php
class ProductController
{
    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return redirect()->route('products.index');
    }
}
```

There is nothing to improve here. Adding a repository interface, a plain `Product` entity,
and a mapping layer around *this* would be pure ceremony - more files, more indirection, and
zero rules protected, because there are no rules yet.

## DDD and hexagonal earn their keep with complexity

The patterns in this course are tools for **managing complexity you actually have**. Their
cost - extra classes, mapping between entity and row, more indirection - is paid up front.
That cost is worth it when the domain has real complexity to organize:

- Rules that are easy to get wrong and expensive when you do (money, tax, scheduling,
  permissions).
- Logic that must hold no matter which controller or job triggers it.
- A domain your team argues about in meetings, with its own vocabulary
  ([ubiquitous language](/course/software-architecture/ddd-strategic-design/ubiquitous-language),
  Chapter 3).
- Behaviour you want to test fast, without a database, in hundreds of cases.

If none of that is true, the machinery has nothing to manage, and you are paying the cost
for a benefit you will not collect.

## A rough guide

```text
Signals the defaults are enough        Signals you may want more structure
-------------------------------        ----------------------------------
mostly CRUD, thin rules                rich rules, invariants, workflows
small team or solo                     several developers on one domain
short-lived or simple app              long-lived core the business depends on
few, obvious edge cases                many rules that interact
"just save the form"                   "this must never be allowed to happen"
```

Most apps live in the left column, and many that start on the left stay there for years.
The right column is where a billing or ordering *core* usually sits - and even then, often
only that one module needs the full treatment while the rest stays plain.

The jump from defaults to full DDD is not a cliff, either. There is a cheap middle rung:
pull a fat controller's logic into a single invokable action class, keep validation in a
form request, and leave Eloquent as-is. You get one obvious home for the behavior and a
thing to unit-test, without an entity, a port, or a mapping layer. Most modules that outgrow
"just save the form" only ever need that much.

## Don't cargo-cult architecture

**Cargo-culting** is copying the visible form of something successful without its reason.
Adding repositories, value objects and a framework-free domain to a simple CRUD app because
a conference talk did it is cargo-culting - you get the ceremony without the payoff, and a
codebase that is *harder* to change than the plain version would have been. Over-engineering
is a real cost, and Chapter 9 will name it directly.

The honest rule: **add structure in response to pain, not in anticipation of prestige.**
Start with the defaults. When a module's rules start slipping through the cracks, when the
same bug keeps coming back because a check was forgotten in one more place, *that* is your
signal to pull a boundary and a domain out of it - and you now know exactly how.

## Common mistake: architecture as a status symbol

The trap is treating ports, adapters and aggregates as a mark of a "serious" codebase and
applying them everywhere to prove it. Architecture is not a badge; it is a trade. Every layer
you add buys isolation and costs indirection. On a rich domain that trade is a bargain; on a
settings table it is a loss. Judge each module on its own complexity, and be willing to leave
most of them plain.

## FAQ

### Do I need DDD and hexagonal architecture in every Laravel app?

No. For CRUD apps and small teams, Laravel's defaults - Eloquent models and resourceful
controllers - are a solid architecture on their own. DDD and hexagonal patterns pay off when
a domain has real, interacting rules worth isolating and testing; without that complexity
they add cost and no benefit.

### Are fat models and fat controllers always bad?

No. "Fat model" is fine when the app is a straightforward CRUD app and the logic is simple -
it keeps everything in one obvious place. The problem appears only when a model or controller
grows many interacting rules; at that point extracting a domain helps. Match the structure to
the complexity.

### How do I know when to move beyond the defaults?

Watch for pain, not milestones: the same rule enforced in several places (and forgotten in
one), bugs that keep returning because logic is scattered, tests that need a full framework
boot to check a simple rule. When those appear in a module, pull its rules into a domain.
Until then, the defaults are the right call.
