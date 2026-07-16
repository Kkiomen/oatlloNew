---
title: "Value objects: immutable, compared by value"
slug: value-objects
seo_title: "Value Objects in DDD: Immutable Types in PHP 8.4"
seo_description: "Learn what a value object is in Domain-Driven Design: immutable, self-validating, compared by value, with a PHP 8.4 readonly example."
---

A **value object** is a small object defined entirely by its values: two are equal when their
values match, they carry no identity, and once created they never change. `Money`, `Email`, and
`DateRange` qualify - there's no "which 10 EUR?", all 10 EUR are the same.

## What is a value object?

Three properties make an object a value object. It's **immutable**, it's **self-validating**,
and it's **compared by value**. Miss any one and you have something else: a mutable value
object is a bug factory, one that skips validation just moves the checks downstream, and one
compared by identity is really an [entity](/course/software-architecture/ddd-tactical-patterns/entities)
in disguise. An entity is the *same* one over time as its data changes; a value object is the
reverse - you never change it, you replace it wholesale.

## The problem: primitive obsession

Look at a method built from raw primitives:

```php
public function charge(float $amount, string $currency, string $email): void
```

Every parameter is a trap. `float $amount` can go negative or carry rounding errors.
`string $currency` can be `'eur'`, `'EUR'`, `'euros'`, or `'banana'`. `string $email` can be
anything, and nothing stops a caller passing the currency where the email belongs. The smell
has a name: **primitive obsession** - modelling domain concepts with bare `string`, `int`, and
`float`. A value object replaces the primitive with a type that can only hold a valid value.

## A value object in PHP 8.4

PHP 8.4 `readonly` gives you immutability almost for free; the constructor handles validation.

```php
<?php
declare(strict_types=1);

final class Money
{
    public function __construct(
        public readonly int $amountInCents,
        public readonly string $currency,
    ) {
        // Self-validating: an invalid Money can never exist.
        if ($amountInCents < 0) {
            throw new InvalidArgumentException('Amount cannot be negative.');
        }
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Currency must be a 3-letter ISO code.');
        }
    }

    // Immutable: operations return a NEW instance, never mutate this one.
    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot add different currencies.');
        }

        return new self($this->amountInCents + $other->amountInCents, $this->currency);
    }

    // Compared by value: same amount + same currency = equal.
    public function equals(self $other): bool
    {
        return $this->amountInCents === $other->amountInCents
            && $this->currency === $other->currency;
    }
}

$price = new Money(1000, 'EUR');
$shipping = new Money(500, 'EUR');
$total = $price->add($shipping); // new Money(1500, 'EUR')
// $price is untouched - it's still 1000 cents.

$a = new Money(1000, 'EUR');
$b = new Money(1000, 'EUR');
$a->equals($b); // true - different instances, same value
```

Because `Money` validates itself in the constructor, an invalid amount cannot exist anywhere in
your program - you validate **once**, at the edge, and every method downstream trusts it. One
aside: PHP's `==` already compares two objects of the same class property by property, so
`$a == $b` is true here too, but a named `equals()` states intent and lets you ignore fields
that shouldn't count.

## Why this cuts primitive obsession

Rewrite that method with value objects:

```php
public function charge(Money $amount, Email $email): void
```

The currency now lives inside `Money`, the address inside `Email`, both validated the moment
they were built - and you physically cannot swap them, because the types don't match.

## Common mistake: the mutable value object

The classic mistake is a value object with setters:

```php
$money->setAmount(2000); // wrong - a value object must never change
```

A value object that changes in place gives you spooky action at a distance: two parts of the
code hold the "same" `Money`, one edits it, the other silently sees a different value - the
exact bug immutability prevents. Never add setters; return a new instance (`add()` above).
`readonly` in PHP 8.4 enforces it: assigning after construction is a fatal error.

## FAQ

### Value object vs entity?

An **entity** has identity and changes over time (an `Order` #4021 stays that order). A
**value object** has no identity and never changes - you replace it wholesale. Ask: "do I care
*which* one, or only *what* it is?" Which one = entity; what it is = value object. See [the
entities lesson](/course/software-architecture/ddd-tactical-patterns/entities).

### Where should validation live?

Inside the value object's constructor. That way an invalid instance can't be created, and
every method that receives it trusts it without re-checking. Validate once, at construction,
not in every service that touches the value.

### Are value objects overkill for small apps?

For a throwaway script, maybe. But `Money`, `Email`, and `DateRange` pay off fast even in
small apps, because they kill whole categories of bug - bad currency, invalid email,
end-before-start dates - in one place instead of scattering checks everywhere.
