---
title: "Keeping the domain framework-free"
slug: keeping-the-domain-framework-free
seo_title: "Keeping the Domain Framework-Free in Laravel"
seo_description: "Build a framework-free domain layer in Laravel: entities, value objects and use cases as plain PHP with no Illuminate imports. Why it pays off - and what it costs."
---

A **framework-free domain layer** has a concrete test in Laravel you can apply to any file
in your core: **does it import `Illuminate\...`?** If it does, the framework has leaked into
the center that hexagonal architecture wants kept pure. This lesson is about holding that
line - and being honest about what it costs.

## The rule: no Illuminate in the domain

Your [entities](/course/software-architecture/ddd-tactical-patterns/entities),
[value objects](/course/software-architecture/ddd-tactical-patterns/value-objects) and
[use cases](/course/software-architecture/application-layer-and-use-cases/the-application-layer)
should be plain PHP classes. No `extends Model`, no facades, no `Illuminate\Support\Str`,
no `request()`, no `Carbon` if a plain `DateTimeImmutable` will do.

```php
namespace App\Billing;

final class Money
{
    public function __construct(
        private readonly int $cents,
        private readonly string $currency = 'USD',
    ) {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Money cannot be negative.');
        }
    }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->cents, $this->currency);
    }

    public function cents(): int { return $this->cents; }
}
```

Nothing here knows Laravel exists. It is just PHP that enforces a rule: money is never
negative, and adding money returns new money. You could paste this class into a plain PHP
script, a Symfony app, or a test with no framework booted, and it would work.

## The framework lives in the adapters

Framework code does not disappear - it moves to the edge. Controllers, Eloquent models,
service providers, jobs and mailers are all **adapters**, and that is exactly where
`Illuminate\...` imports belong.

```text
app/Billing/
  Money.php               <- plain PHP  (domain)
  Invoice.php             <- plain PHP  (domain)
  InvoiceRepository.php   <- plain PHP interface (port)

  Persistence/
    InvoiceRow.php               <- Illuminate\Database\Eloquent\Model
    EloquentInvoiceRepository.php <- uses Eloquent
  Http/
    InvoiceController.php        <- Illuminate\Http\Request
```

Draw an imaginary line: files above it are framework-free, files below it are allowed to
import anything. If an `Illuminate` import creeps above the line, you have a boundary
violation you can see at a glance.

## Why it pays off

**Testable.** A framework-free `Money` or `Invoice` is tested with a plain PHPUnit test and
no database, no `RefreshDatabase`, no booting Laravel. These tests run in milliseconds, so
you write more of them.

```php
public function test_money_adds(): void
{
    $total = (new Money(500))->add(new Money(250));
    $this->assertSame(750, $total->cents());
}
```

**Portable and durable.** The rules survive a framework upgrade or even a rewrite. When
Laravel 12 changes a signature, your domain does not care - only the adapters at the edge
might. The business logic outlives the technology it currently runs on.

**Clear.** Anyone can find the rules, because they are the only things in the framework-free
folders. Nothing is hidden inside a controller or a query.

## The cost: more mapping

This is not free, and pretending otherwise is how people end up hating it. Because the
domain `Invoice` is not the Eloquent `InvoiceRow`, something has to **translate between
them** in the repository - copy fields out of the row into the entity when reading, and back
when saving. That mapping code is boring and it is real work.

You also write more classes: an entity *and* a row, an interface *and* an implementation.
For a rich domain that pays for itself many times over. For a simple CRUD table, it is pure
overhead - which is the whole point of the last lesson in this chapter.

## Common mistake: a "pure" domain that still leaks

It is easy to keep `extends Model` out of your entity and still leak the framework in
quieter ways: type-hinting an Eloquent `Collection`, calling `now()`, throwing a Laravel
`ValidationException`, or reaching for the `Str` helper. Each one re-welds the core to
Illuminate. The honest check is a search: grep your domain folder for `Illuminate` and for
global helpers. If the domain is truly framework-free, that search comes back empty.

Better than grepping by hand: pin the rule with an architecture test so CI fails the moment
a leak sneaks in. Pest can assert it in one line - `arch('domain stays pure')
->expect('App\Billing')->not->toUse('Illuminate')` - and it costs nothing to keep green
once the domain is already clean.

## FAQ

### How do I keep my domain layer framework-free in Laravel?

Write entities, value objects and use cases as plain PHP classes with no `Illuminate`
imports, no facades and no global helpers. Put every framework touchpoint - Eloquent,
controllers, jobs, mail - in adapter classes at the edge, and have the domain depend only on
interfaces it defines.

### Isn't it wasteful to have both an entity and an Eloquent model?

It is extra code, and for a plain CRUD table it usually is not worth it. The two-model split
earns its keep when the entity carries real rules you want to test and protect from the
database schema. Match the effort to the complexity - not every table needs it.

### How do I map between a domain entity and an Eloquent model?

In the repository adapter. On read, pull the columns off the Eloquent row and build the
entity; on write, read the entity's values and set them on the row before saving. Keeping
that translation in one class is what stops Eloquent from spreading into the rest of the app.
