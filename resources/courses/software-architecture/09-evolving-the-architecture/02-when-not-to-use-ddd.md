---
title: "When not to use DDD"
slug: when-not-to-use-ddd
seo_title: "When Not to Use DDD or Hexagonal Architecture"
seo_description: "DDD and hexagonal architecture pay off only with real domain complexity. For CRUD, reporting or a tiny team they over-engineer the problem. How to judge, tied to YAGNI."
---

Knowing **when not to use DDD** matters as much as knowing how to apply it. Domain-Driven
Design and hexagonal architecture are not free: entities, value objects, ports, adapters,
repositories, a hand-written mapping layer - all of that is code you write, test and
maintain *instead of* shipping features. It pays for itself only when the domain is
genuinely complex. For a lot of software, it's over-engineering, and reaching for it out of
habit slows you down. This lesson is the counter-weight to the rest of the course.

## The problem: patterns applied by reflex

You finish this course, you're excited, and the next thing you build is a small internal
tool that stores contact forms. You give it entities, value objects, a repository interface
with an Eloquent adapter behind it, command handlers, DTOs mapping in both directions. Three
hundred lines of scaffolding to save a row to a table.

Nobody benefits. There were no business rules to protect - a contact form has no
invariants, no "you can't submit twice", no workflow. You added
[boundaries](/course/software-architecture/what-is-software-architecture/boundaries-and-coupling)
where there was nothing to separate. Every future change now touches five files instead of
one, and a new teammate has to understand your architecture before they can add a field.

## The cost is real, so the value must be too

Every pattern in this course buys you something at a price:

- **Repositories and ports** buy you a domain that doesn't depend on the database - at the
  cost of an interface, an adapter, and mapping code you maintain forever.
- **Entities with behaviour** buy you protected invariants - at the cost of more classes
  than a plain Eloquent model.
- **CQRS and the application layer** buy you separation of reads and writes - at the cost
  of DTOs and handlers around logic that might be one line.

The price is worth paying **when the domain has real complexity**: rules that are easy to
get wrong, invariants that must always hold, language the business argues about, behaviour
that changes often and independently. When there's none of that, you're paying for
insurance against a risk you don't have. This is
[YAGNI](/course/software-architecture/what-is-software-architecture/domain-vs-infrastructure)
- You Aren't Gonna Need It - applied to architecture: don't build structure for complexity
that isn't there yet.

## Where the simple option wins

Reach for Laravel's defaults, not DDD, when the work is:

- **CRUD.** Create, read, update, delete over forms with little logic. An Eloquent model,
  a controller and a form request are the right amount of code. See
  [when Laravel's defaults are enough](/course/software-architecture/putting-it-together-in-laravel/when-laravels-defaults-are-enough).
- **Reporting and read-heavy screens.** Dashboards, exports, admin lists. These are queries
  shaped for the view. Wrapping them in domain objects adds ceremony and buys nothing; a
  well-named query is clearer.
- **A tiny team or a short-lived project.** A prototype, a one-week internal tool, a
  side-project you might delete. The main benefit of these patterns - many people changing
  a large system safely over years - doesn't apply. The main cost - everyone learning the
  structure - hits immediately.

## How to judge

Ask a few honest questions before adding structure:

1. **Is there behaviour, or just storage?** If the "rules" are only "these fields are
   required", it's CRUD. Use a model and a form request.
2. **Would the domain code make sense with the database deleted?** If the interesting part
   *is* the query or the table shape, there's no framework-free domain to protect, and
   [hexagonal](/course/software-architecture/hexagonal-architecture/what-is-hexagonal-architecture)
   ports are separating nothing.
3. **Will this change often, by several people?** Structure pays off over time and across a
   team. A stable tool touched by one person rarely earns it back.
4. **Can you name the invariant you're protecting?** If you can't say the rule out loud
   ("an order can't be paid twice"), an entity has nothing to guard.

If most answers point to "simple", stay simple. You can always add structure later, when a
real rule appears - and by then you'll know exactly where it belongs. The reverse, ripping
out unnecessary layers, is much harder because code tends to stay.

A concrete tell in review: find a repository interface that has exactly one implementation
and no test double using it. That port was never a seam - it exists because the pattern
said so, not because anything swaps behind it. An interface nobody varies is a comment with
extra compile cost.

## A middle path

It's not all-or-nothing. You can keep a rich domain for the one **complex** part of the app
- billing, scheduling, pricing - and use plain Laravel CRUD for the boring 80% around it.
[Bounded contexts](/course/software-architecture/ddd-strategic-design/bounded-contexts)
already told you these parts are different; treat them differently. Applying full DDD to the
whole app, complex core and simple edges alike, is its own kind of over-engineering.

## FAQ

### When should you not use DDD?

When the domain is simple: CRUD apps, reporting and read-heavy tools, prototypes, or
anything maintained by a tiny team for a short time. If there are no real business
invariants to protect, DDD's structure is cost without payoff.

### Is hexagonal architecture over-engineering for small apps?

Often, yes. Ports and adapters protect a framework-free domain, but a small CRUD app has no
meaningful domain to protect - the logic *is* the database work. For those, Eloquent models
and controllers are the right level. Add ports when a real domain appears.

### How does YAGNI apply to architecture?

YAGNI says don't build for needs you don't have yet. Applied to architecture, it means
don't add entities, ports and CQRS in advance for complexity that isn't there. Start simple
and introduce structure when a concrete rule or scaling pain actually shows up.
