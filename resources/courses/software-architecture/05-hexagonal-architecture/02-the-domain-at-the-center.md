---
title: "The domain at the center"
slug: the-domain-at-the-center
seo_title: "The Domain at the Center: The Dependency Rule"
seo_description: "The dependency rule of hexagonal architecture: all dependencies point inward, and the domain knows nothing about the database, HTTP or the framework."
---

The single rule that makes hexagonal architecture work is the **dependency rule**: all
dependencies point inward, toward the domain. The domain depends on nothing outside
itself. Everything else depends on the domain, never the other way around.

## What "points inward" means

A dependency is one piece of code needing to know about another - importing its class,
calling its methods, naming its type. The dependency rule says those arrows only ever run
from the outside toward the center.

```text
   Controller  ---->  Domain  <----  MysqlOrderRepository
   (outside)         (center)         (outside)

   arrows point IN. The domain has no arrow pointing out.
```

The controller knows about the domain. The database code knows about the domain. But open
any file inside the domain and you will find no mention of the controller, no MySQL, no
`Illuminate\...`, no HTTP. The center is unaware that any of those things exist.

## The domain knows nothing about the outside

Concretely, code in the domain must not:

- import framework classes (no `Illuminate\Http\Request`, no Eloquent model)
- run SQL or open a database connection
- read the HTTP request or build a response
- call an external API directly

Here is domain code that follows the rule. Notice what is absent.

```php
<?php

final class Order
{
    /** @var OrderLine[] */
    private array $lines = [];

    public function addLine(OrderLine $line): void
    {
        $this->lines[] = $line;
    }

    public function total(): Money
    {
        $total = Money::zero();

        foreach ($this->lines as $line) {
            $total = $total->add($line->subtotal());
        }

        return $total;
    }
}
```

No `save()`, no query, no request. `Order` computes a total from its own data. It could
run in a plain PHP script with no framework booted at all. That is the goal.

## Why point everything inward

The domain is the part you most want to keep stable and testable. If it depended on MySQL,
every database change could ripple into your business rules. By [reversing the arrow](/course/design-patterns/solid/dependency-inversion) -
making the database depend on the domain instead - the rules stay put while technology
changes around them.

This is the practical payoff of the [domain-vs-infrastructure split](/course/software-architecture/what-is-software-architecture/domain-vs-infrastructure) from Chapter 1. It also
makes tests fast: you can construct an `Order`, add lines, and assert the total without a
database, a server, or any setup. When the domain depends on nothing, nothing needs to be
set up to test it.

## How the center reaches the outside

If the domain cannot know about the database, how does an order ever get saved? The domain
declares an **interface** for what it needs - "something that can store orders" - and the
outside world provides the real implementation. The interface belongs to the domain; the
implementation lives outside and depends inward. Those interfaces are **[ports](/course/software-architecture/hexagonal-architecture/ports)**, the
subject of the next lesson.

## Common mistake: importing an Eloquent model into the domain

The most common leak is reaching for a framework class inside the domain "just this once" -
type-hinting an Eloquent model, or accepting the HTTP request to grab one field. The
moment you do, the arrow flips outward and the domain is welded to the framework again. If
the domain needs data from outside, it should receive a plain value or depend on an
interface it defines - never on a concrete framework class.

In Laravel the sneakiest version has no `use` statement at all. A `now()`, a `config()`, or
an `Auth::user()` buried in a domain method reaches straight into the framework through a
global helper or facade, so nothing in the imports gives it away. A quick check: if a domain
class cannot run in a plain PHP script with no service container booted, something outside
is leaking in.

## FAQ

### What is the dependency rule?

All source-code dependencies point inward, toward the domain. Outer code may know about
inner code, but inner code must never know about outer code. It is the one rule the whole
architecture rests on.

### Can the domain use the database if I'm careful?

No. Even careful database code inside the domain flips the dependency arrow outward and
couples your rules to a specific technology. The domain defines an interface for what it
needs; the database code implements it from outside.

### Does "knows nothing about the framework" mean no framework in the project?

No. You still use Laravel for the outside - routing, controllers, Eloquent. The rule is
only about the domain code in the center: those files import no framework classes. The
rest of the app is free to.
