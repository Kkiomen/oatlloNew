---
title: "Single Responsibility Principle (SRP)"
slug: single-responsibility
seo_title: "Single Responsibility Principle in PHP (SRP) Explained"
seo_description: "The Single Responsibility Principle says a class should have one reason to change. Learn SRP with a clear PHP before-and-after refactoring example."
---

## What is the Single Responsibility Principle?

The **Single Responsibility Principle** is the "S" in SOLID. The usual one-liner is:

> A class should have only one reason to change.

A "reason to change" comes from the people or systems the class serves. If a class handles
business rules *and* formatting *and* saving to a database, then a change in any of those
three areas forces you to open the same class. That's three reasons to change, and they
have nothing to do with each other.

Worth knowing: Robert C. Martin later reworded SRP as "gather together the things that change
for the same reason, and separate those that change for different reasons." A responsibility
is owed to an *actor* - a person or role who asks for changes - not to a task. The billing
team, the reporting team, and the DBA are three actors, so their concerns belong in three
classes even if the code looks related.

## A class doing too much

Here's a class that computes an invoice total, formats it for display, and saves it:

```php
final class Invoice
{
    /** @param array<int, array{price: float, qty: int}> $lines */
    public function __construct(private array $lines) {}

    public function total(): float
    {
        return array_sum(
            array_map(fn (array $l) => $l['price'] * $l['qty'], $this->lines)
        );
    }

    public function asHtml(): string
    {
        return '<strong>Total: $' . number_format($this->total(), 2) . '</strong>';
    }

    public function save(\PDO $db): void
    {
        $stmt = $db->prepare('INSERT INTO invoices (total) VALUES (?)');
        $stmt->execute([$this->total()]);
    }
}
```

It works, but it mixes three concerns. Change the currency format and you edit `Invoice`.
Switch from HTML to JSON and you edit `Invoice`. Move from `PDO` to a different data layer
and you edit `Invoice`. The calculation logic gets disturbed every time.

## One responsibility per class

Split it so each class has a single job:

```php
final class Invoice
{
    /** @param array<int, array{price: float, qty: int}> $lines */
    public function __construct(private array $lines) {}

    public function total(): float
    {
        return array_sum(
            array_map(fn (array $l) => $l['price'] * $l['qty'], $this->lines)
        );
    }
}

final class InvoiceHtmlFormatter
{
    public function format(Invoice $invoice): string
    {
        return '<strong>Total: $' . number_format($invoice->total(), 2) . '</strong>';
    }
}

final class InvoiceRepository
{
    public function __construct(private \PDO $db) {}

    public function save(Invoice $invoice): void
    {
        $stmt = $this->db->prepare('INSERT INTO invoices (total) VALUES (?)');
        $stmt->execute([$invoice->total()]);
    }
}
```

Now `Invoice` only knows about money. Formatting lives in a formatter, persistence in a
repository. Each class changes for one reason, and you can test the calculation without
touching a database. This is
[separation of concerns](/course/design-patterns/core-principles/separation-of-concerns)
applied at the level of a single class.

## Common mistake

Confusing "one responsibility" with "one method." SRP isn't about class size - a class can
have several methods and still have one reason to change, as long as those methods serve
the same concern. Splitting a cohesive class into tiny fragments just to have small classes
hurts [cohesion](/course/design-patterns/why-design-matters/coupling-and-cohesion) and
makes the code harder to follow.

## FAQ

### What is the Single Responsibility Principle?

It's the idea that a class should have only one reason to change - one job, serving one
concern. Calculation, formatting and storage are three concerns, so they belong in three
classes.

### How do I know a class has more than one responsibility?

Ask why it might change. If you can name two unrelated reasons - "because the report layout
changed" and "because we switched databases" - it has more than one responsibility.

### Does SRP mean one method per class?

No. A class can have many methods as long as they all serve the same responsibility. SRP is
about reasons to change, not about counting methods.
