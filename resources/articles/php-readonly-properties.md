---
name: "Readonly Properties in PHP 8: Practical Use Cases"
slug: php-readonly-properties
short_description: "A practical guide to PHP readonly properties: value objects, DTOs, immutable config, constructor promotion, and the gotchas that bite in production."
language: en
published_at: 2026-09-09 09:00:00
is_published: true
tags: [php, oop, immutability, php8]
---

The first time I reached for **php readonly properties** was on a payment DTO that kept getting mutated three layers deep in a service I didn't write. Someone was reassigning `$order->total` after tax calculation, and by the time the bug reached the invoice generator, the number was wrong in a way that only showed up on refunds. Making that property `readonly` turned a silent data corruption bug into a loud, obvious `Error` at the exact line that caused it. That's the whole pitch, really.

Readonly properties landed in PHP 8.1. They give you write-once fields without hand-rolling private properties plus getters plus a defensive setter that throws. This article walks through where they actually earn their keep, the syntax that trips people up, and the sharp edges you'll hit once real objects start flowing through your code.

## What readonly properties actually guarantee

A `readonly` property can be written exactly once, and only from inside the scope of the class that declares it. After that first initialization, any attempt to write to it throws an `Error`. Not a warning. Not a silent no-op. A fatal `Error` you have to catch or fix.

```php
<?php

class Money
{
    public readonly int $amount;
    public readonly string $currency;

    public function __construct(int $amount, string $currency)
    {
        $this->amount = $amount;      // first write, allowed
        $this->currency = $currency;
    }
}

$price = new Money(1999, 'EUR');
echo $price->amount;                  // 1999

$price->amount = 2500;                // Error: Cannot modify readonly property Money::$amount
```

A few rules worth committing to memory:

- **They must be typed.** `public readonly int $amount` works; `public readonly $amount` is a fatal error at compile time. The engine needs the type to enforce the uninitialized-then-initialized lifecycle.
- **Initialization must happen inside the declaring class.** You can assign the value in the constructor, or in any method of that class, but you cannot set it from outside or from a subclass.
- **No default values.** `public readonly int $amount = 0;` is illegal. A readonly property with a default would already be initialized, which defeats the point.
- **Reassigning the same value still throws.** Setting `$this->amount = 1999` twice fails on the second write even though the value is identical. There is no "no change" exception.

That last one surprises people. Readonly tracks whether a write happened, not whether the value changed.

## Constructor promotion makes them almost free

The syntax above is verbose. In practice, most readonly properties are constructor arguments, and PHP 8.1 lets you promote and mark them readonly in one shot:

```php
<?php

final class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {}
}

$price = new Money(1999, 'EUR');
```

This is the form I use for roughly 90% of value objects. The declaration, the type, the assignment, and the immutability guarantee all live on one line. Notice I also marked the class `final`. That's a habit for value objects, not a requirement of readonly, but the two pair well: you rarely want someone subclassing an immutable value type and adding mutable state.

## Use case 1: value objects

Value objects are the textbook fit. A `Money`, an `EmailAddress`, a `DateRange` has no identity beyond its contents, and its contents should never change after creation. Before 8.1 you'd write private properties and getters. Now the properties can be public and readonly, so callers read them directly without a setter ever existing.

```php
<?php

final class EmailAddress
{
    public function __construct(public readonly string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }
    }

    public function domain(): string
    {
        return substr(strrchr($this->value, '@'), 1);
    }
}
```

The validation runs once, in the constructor, before the property is ever set. Once you hold an `EmailAddress`, it is guaranteed valid and guaranteed unchanged for its entire lifetime. That's a strong invariant to carry around, and it removes a whole category of "but is this actually a valid email at this point?" checks scattered through your code.

## Use case 2: DTOs at the boundary

Data transfer objects that carry request payloads, API responses, or messages between layers benefit hugely. A DTO's job is to move data, not to be edited in transit. Marking every field readonly documents that intent and enforces it.

```php
<?php

final class CreateUserRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $referralCode = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            referralCode: $data['referral_code'] ?? null,
        );
    }
}
```

Wait, didn't I say readonly properties can't have defaults? The `= null` here is a default on the *constructor parameter*, not on the property. That distinction matters. Promoted parameters can have defaults because the default lives on the argument; the property still gets exactly one write when the constructor runs. This is one of the genuinely confusing corners, and it's worth reading that twice.

## Use case 3: immutable configuration

Config objects that get built once at boot and read everywhere else are a natural fit. If your `DatabaseConfig` can be mutated after the container wires it up, you've got a debugging session waiting to happen. Readonly closes that door.

```php
<?php

final class DatabaseConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $database,
    ) {}
}
```

If you find yourself building whole config trees like this, it pairs nicely with typed constants for the values that truly never vary between environments. I wrote separately about [PHP 8.3 typed class constants](/blog/php-8-3-typed-class-constants), which cover the "constant, not per-instance" case that readonly properties don't.

## readonly classes (PHP 8.2)

Marking every single property readonly by hand gets tedious. PHP 8.2 added readonly *classes*: declare the class readonly and every declared property is automatically readonly, with no need to repeat the keyword.

```php
<?php

readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}
}
```

Every property here is readonly. A readonly class also forbids declaring any non-readonly or untyped property, and it can't have dynamic properties added at runtime. For a value object where nothing should ever be mutable, this is cleaner than sprinkling the keyword on each line. I default to readonly classes now for anything that's conceptually a value, and drop to per-property readonly only when a class genuinely mixes mutable and immutable state.

## The trap nobody warns you about: shallow immutability

Here's the one that actually bites in production. **`readonly` prevents reassigning the property. It does not freeze the object that property points to.**

```php
<?php

final class Cart
{
    public function __construct(
        public readonly ArrayObject $items,
    ) {}
}

$cart = new Cart(new ArrayObject(['apple']));

$cart->items = new ArrayObject([]);   // Error: cannot modify readonly property
$cart->items->append('banana');       // totally fine, no error at all
```

You can't point `$cart->items` at a different object, but you can absolutely reach through it and mutate the `ArrayObject` it already holds. Same story with arrays that contain objects, or any nested mutable structure. Readonly is shallow. If you want deep immutability, the objects your readonly properties hold need to be immutable too, all the way down.

This is exactly the kind of thing that makes readonly look like a bulletproof guarantee when it's really a one-level one. I've watched a code review approve a "fully immutable" object that leaked a mutable collection right through a readonly property.

For the arrays-of-scalars case there's a saving grace: PHP arrays are value types, so a readonly property holding a plain array can't have its elements changed from outside either. `$obj->list[] = 'x'` on a readonly array property throws, because modifying an element counts as modifying the property. The leak only happens through objects.

## Cloning and "modifying" readonly objects

Since you can't reassign a readonly property, the usual immutable-update pattern of `clone` plus tweak needs care. A plain `clone` copies the object fine, but you cannot reinitialize a readonly property inside `__clone()` in PHP 8.1 or 8.2. The property is already initialized on the original, so the clone treats it as already-written.

The practical workaround for a "with" style change is to build a fresh instance rather than clone-and-mutate:

```php
<?php

final class Temperature
{
    public function __construct(public readonly float $celsius) {}

    public function withCelsius(float $celsius): self
    {
        return new self($celsius);   // new object, don't try to mutate this one
    }
}

$cold = new Temperature(4.0);
$warm = $cold->withCelsius(21.0);    // $cold is untouched
```

This "return a new instance" approach is the idiomatic way to model changes on immutable objects, and it sidesteps the clone limitation entirely. If your objects have many fields, a `with*` method per field gets noisy, so pass the values you're keeping straight from the current instance into the new constructor call.

## Common pitfalls

- **Forgetting the type.** `public readonly $x` is a compile error. Readonly requires a declared type, always.
- **Trying to set a default value.** Put defaults on the promoted constructor parameter, never on the property declaration itself.
- **Assuming deep immutability.** A readonly property holding a mutable object or collection is still mutable through that reference. Freeze what it holds, or hold value types.
- **Initializing from outside the class.** Even a subclass can't write a parent's readonly property. Initialization is scoped to the declaring class only.
- **Expecting a "same value" write to pass.** The second write throws regardless of whether the value changed. Readonly counts writes, not diffs.
- **Reaching for `clone` to produce a modified copy.** Build a new instance with a `with*` method instead; you can't reinitialize the readonly field during a clone in 8.1 or 8.2.

## FAQ

### Can a readonly property be public?

Yes, and that's often the point. Because the value can't change after construction, exposing it publicly is safe. You get direct property access with `$obj->value` and no need to write a getter. Encapsulation here protects against writes, not reads.

### What's the difference between readonly and const?

A class constant belongs to the class and is the same for every instance, fixed at definition time. A readonly property belongs to an instance and can hold a different value per object, set once when that object is built. Use a constant for a value that never varies; use readonly for per-instance data that shouldn't change after creation.

### Do readonly properties work with enums?

Enum cases are already immutable singletons, so you rarely need readonly inside an enum. But an enum makes an excellent *type* for a readonly property, giving you a value that's both constrained to a fixed set and write-once. If you haven't used enums yet, the [complete guide to PHP enums](/blog/php-enums-complete-guide) covers where they fit.

### Can I make an existing property readonly without breaking things?

Only if nothing writes to it after construction. Adding `readonly` to a property that gets reassigned anywhere will turn that reassignment into a fatal `Error` at runtime. Grep for every write to the property first, confirm they all happen during construction, then add the keyword. Static analysis tools catch most of these before you ship.

## Conclusion

Readonly properties are one of those small language features that quietly remove a class of bugs. Use them for value objects, DTOs, and immutable config, lean on constructor promotion to keep the syntax tight, and reach for readonly classes in 8.2+ when everything should be locked down. Just keep the shallow-immutability trap in mind: readonly guards the reference, not the thing it references, so if you need genuine deep immutability, the objects you hold have to be immutable too.

Concretely, next time you write a class whose fields shouldn't change after construction, mark them readonly from the start. It costs one keyword and it turns a whole category of "who mutated this?" bugs into an immediate, obvious error at the exact line responsible.