---
title: "Domain vs infrastructure"
slug: domain-vs-infrastructure
seo_title: "Domain vs Infrastructure: The Core Architecture Split"
seo_description: "The core distinction in software architecture: business logic (domain) vs infrastructure (database, HTTP, framework). Why keeping them apart matters."
---

If you take one idea from this whole course, take this one. Nearly every style you'll meet
later is a different way of enforcing a single split: **domain** on one side,
**infrastructure** on the other.

## The two kinds of code

Look at any application and you'll find two very different kinds of code mixed together.

**Domain** is the business logic - the rules that would be true even if computers didn't
exist. "An order over 100 gets free shipping." "You can't withdraw more than your balance."
"A published article must have a title." This is the *reason the software exists*. It's
about your business, not about technology.

**Infrastructure** is everything technical that lets the domain run in the real world. The
database that stores the order. The HTTP layer that receives the request. The framework
that wires it together. The queue, the email service, the file system. None of it is a
business rule; all of it is plumbing.

```text
   Domain          "an order over 100 ships free"   (why the app exists)
   ----------------------------------------------
   Infrastructure  MySQL, HTTP, Laravel, queues     (how it runs)
```

The rule is the point. The database is a detail of how you keep the rule's data around.

## Why the split matters

Here's the uncomfortable truth about the two: **they change for completely different
reasons, and at completely different speeds.**

Business rules change when the business changes - a new discount policy, a new
regulation. Infrastructure changes when technology changes - a new database version, a
framework upgrade, moving from files to a cloud bucket.

If domain and infrastructure are tangled together, every technical change risks a business
rule, and every business change forces you to wade through technical plumbing. When they're
kept apart, each can change on its own without disturbing the other.

That single benefit - each side free to change without dragging the other along - is the
root of nearly every idea later in this course.

## What tangling looks like

You've probably written this. It works, and that's exactly why it's easy to miss.

```php
// Business rule buried inside a database query
$orders = DB::table('orders')
    ->where('total', '>', 100)
    ->update(['shipping' => 0]);
```

The rule "orders over 100 ship free" is now welded to a specific table and a specific query
builder. To test the rule you need a database. To change databases you must revisit the
rule. To find the rule at all, you have to read SQL.

Now the same rule, kept in the domain and free of any technology:

```php
final class Order
{
    public function shippingCost(): int
    {
        return $this->total > 100 ? 0 : self::FLAT_RATE;
    }
}
```

This method knows nothing about MySQL, HTTP or Laravel. It's a plain object expressing a
plain business rule. You can read it, test it and change it without touching any
infrastructure at all. *Where* the order came from and *where* it gets saved are somebody
else's job.

## The direction to remember

The guiding instinct, which the rest of the course makes concrete: **the domain should not
depend on infrastructure.** Business rules shouldn't import the database, the framework or
the web. Infrastructure is allowed to know about the domain (something has to save the
order), but not the other way around.

Keep the arrow pointing one way and the valuable part of your system - the rules - stays
clean, portable and easy to reason about.

Worth saying plainly: not every app has a thick domain. Plenty are mostly reading and
writing rows, with a handful of real rules. That's fine. The split isn't a demand to invent
business logic you don't have - it's about *direction*. Even one genuine rule is better off
not knowing which database it will be saved in.

## Common mistake: "the framework is my architecture"

It's tempting to let the framework decide everything: models are database rows, controllers
hold the logic, and the business rules live wherever is convenient. That ships fast and
feels productive. But it welds your domain to one framework forever, and scatters the rules
across the plumbing. The framework is infrastructure. It's a fine servant and a poor owner
of your business logic.

## FAQ

### What is the domain in software architecture?

The domain is the business logic - the rules and concepts that define what your software is
about, independent of any technology. "An overdrawn account can't withdraw" is domain. It
would be true whether you stored the data in MySQL, in a file, or on paper.

### Why should business logic be separate from the database?

Because they change for different reasons and at different speeds. Keeping them apart lets
you change a business rule without touching the database, and swap or upgrade the database
without risking a business rule. It also makes the rules easy to test without any
infrastructure.

### Is the framework part of the domain?

No. The framework is infrastructure - it helps the domain run, but it isn't a business
rule. Good architecture keeps the domain from depending on the framework, so your rules
outlive any particular version or vendor.
