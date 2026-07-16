---
title: "Separation of concerns"
slug: separation-of-concerns
seo_title: "Separation of Concerns in PHP - One Job per Class"
seo_description: "Separation of concerns, made concrete: each class handles one job. Why mixing business logic, persistence and formatting makes code hard to change, in PHP."
---

**Separation of concerns** means each part of your code deals with one concern and only that
concern. A "concern" is a distinct responsibility - calculating a price, saving to the
database, formatting output for a screen. Keep these in separate places and each can change
without disturbing the others.

## Why mixing hurts

Here's a class that tries to do everything at once:

```php
final class Invoice
{
    public function __construct(
        private array $items,
        private PDO $db,
    ) {}

    public function process(): string
    {
        // 1. business logic
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item['price'] * $item['qty'];
        }
        $total *= 1.23;

        // 2. persistence
        $stmt = $this->db->prepare('INSERT INTO invoices (total) VALUES (?)');
        $stmt->execute([$total]);

        // 3. formatting
        return '<h1>Invoice</h1><p>Total: $' . number_format($total, 2) . '</p>';
    }
}
```

This one method mixes three concerns: it calculates the total (business logic), writes to the
database (persistence), and builds HTML (formatting). Each pulls in the future in a different
direction. Switch from `PDO` to a different storage and you're editing the same method that
holds your pricing rules. Need the total as JSON for an API instead of HTML? You're back in
the method that also talks to the database. Every unrelated change touches the same fragile
block, and testing the math means dragging a real database along for the ride.

## Splitting the concerns

Give each concern its own home:

```php
final class InvoiceTotal
{
    public function calculate(array $items): float
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['qty'];
        }

        return $total * 1.23;
    }
}

final class InvoiceRepository
{
    public function __construct(private PDO $db) {}

    public function save(float $total): void
    {
        $stmt = $this->db->prepare('INSERT INTO invoices (total) VALUES (?)');
        $stmt->execute([$total]);
    }
}

final class InvoiceHtmlView
{
    public function render(float $total): string
    {
        return '<h1>Invoice</h1><p>Total: $' . number_format($total, 2) . '</p>';
    }
}
```

Now each class has one reason to change. The pricing rule lives in `InvoiceTotal`, and you can
test it with plain numbers and no database. Switching storage touches only
`InvoiceRepository`. Adding a JSON output means a new view class, next to the HTML one, with
everything else untouched. The concerns no longer drag each other around.

One honest caveat: the coordination didn't vanish, it moved. Something still has to build the
three objects and run them in order - calculate, save, render. That job now sits in one small
place, a controller or a service at the edge, instead of being tangled through the logic. That
is the trade separation of concerns makes on purpose: a single, boring wiring point in
exchange for three pieces you can each change alone. Trouble starts again only if that
coordinator slowly grows its own logic.

## Common mistake

The mistake is letting one class quietly grow until it does calculation, storage and
presentation all together - often because each addition felt small at the time. A good signal
is the word "and": if describing a class needs "it calculates the total **and** saves it
**and** renders it", that's three concerns wearing one name.

This idea is the backbone of the SOLID principles in the next chapter, especially the single
responsibility principle - separation of concerns is the same instinct, applied class by
class.

## FAQ

### How is this different from DRY?

[DRY](/course/design-patterns/core-principles/dry) is about not duplicating a piece of
knowledge; separation of concerns is about not *mixing* unrelated responsibilities in one
place. You can have zero duplication and still have a class that tangles business logic with
formatting. They're complementary, not the same rule.

### Doesn't splitting into many classes make things more complicated?

More files, yes; more complexity, usually no. Each small class is simpler to read and test on
its own, and you only look at the one you need to change. The tangled single class is the
genuinely complicated one, because everything in it is connected.

### How do I know where one concern ends and another begins?

Ask what would make each part change, and who cares about it. Pricing changes because the
business changes the rules; storage changes because you switch databases; formatting changes
because the UI changes. Different reasons and different audiences mean different concerns that
belong in different places.
