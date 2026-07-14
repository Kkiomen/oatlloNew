---
name: "PHP 8.4 Property Hooks: What They Are and How to Use"
slug: php-8-4-property-hooks
short_description: "A practical guide to PHP 8.4 property hooks: get/set syntax, virtual properties, interface requirements, and when they beat plain getters."
language: en
published_at: 2027-01-08 09:00:00
is_published: true
tags: [php, php-8-4, oop]
---

If you have ever written a getter that does nothing but `return $this->name;`, PHP 8.4 property hooks are going to feel like a small gift. They let a property carry its own `get` and `set` logic right where it's declared, so you stop shuttling data through a wall of one-line methods. I rewrote a value object the day I upgraded, and the class dropped from 90 lines to about 40 with no loss of behavior.

Property hooks shipped with PHP 8.4 in November 2024. They are not syntactic sugar bolted onto magic methods; they're a first-class part of the property itself. This guide walks through the syntax, the parts that trip people up, and the cases where a plain method is still the better call.

## What a property hook actually is

A hook is a block of code attached to a property that runs when you read from it or write to it. The property keeps looking like a normal property to the outside world. `$user->fullName` reads like a field, not a method call, but under the hood your `get` code runs.

Here is the canonical shape:

```php
class User {
    public string $fullName {
        get => $this->first . ' ' . $this->last;
        set (string $value) {
            [$this->first, $this->last] = explode(' ', $value, 2);
        }
    }

    public function __construct(
        private string $first,
        private string $last,
    ) {}
}

$u = new User('Ada', 'Lovelace');
echo $u->fullName;        // Ada Lovelace
$u->fullName = 'Grace Hopper';
echo $u->first;           // Grace
```

Reading `$u->fullName` runs the `get` hook. Assigning to it runs the `set` hook, which splits the string back into the two private fields. No `getFullName()` or `setFullName()` anywhere.

Two things worth noticing straight away:

- The `get` hook uses the arrow form `get => expression` for a single expression. You can also use a full block with braces and an explicit `return`.
- The `set` hook receives the incoming value as a parameter. If you type-hint it, PHP enforces that type on assignment.

## Virtual properties: hooks with no stored value

The `fullName` example above is what's called a **virtual property**. There is no `$fullName` field sitting in memory. Its value is computed every time from `$first` and `$last`. That's the mental model that clicked for me: a virtual property is a derived view over other state.

A get-only virtual property is the simplest useful case:

```php
class Order {
    public function __construct(
        public private(set) array $items = [],
    ) {}

    public float $total {
        get => array_sum(array_column($this->items, 'price'));
    }
}
```

`$order->total` always reflects the current items. There's nothing to keep in sync, nothing to invalidate, and you can't accidentally assign a stale total because there's no `set` hook to assign to. Try `$order->total = 5` and PHP throws an `Error` — the property is effectively read-only from the outside because it stores nothing.

That last point matters. A property with only a `get` hook is virtual and cannot be written. If you want both computed reads and controlled writes, you declare both hooks.

## The set hook and its shorthand

The `set` hook is where you validate, normalize, or transform data on the way in. The full form gives you a body:

```php
class Temperature {
    public float $celsius {
        set (float $value) {
            if ($value < -273.15) {
                throw new \InvalidArgumentException('Below absolute zero.');
            }
            $this->celsius = $value;
        }
    }
}
```

Notice the assignment `$this->celsius = $value;` inside the hook. When a property has a `set` hook but no `get` hook, it still keeps a real backing field, and writing to `$this->celsius` from inside the hook targets that field rather than looping back into the hook. This is the one bit of the model I'd double-check against the manual for your exact version if you're doing something clever, because the backing-field rules are the part people misread most.

There's also a shorthand when your `set` logic is a single expression that produces the value to store:

```php
class Slug {
    public string $value {
        set => strtolower(trim($value));
    }
}
```

Here `set => ...` takes the expression's result and stores it in the backing field for you. `$value` is the implicit parameter name. It reads cleanly for normalization work like trimming, lowercasing, or casting.

A quick contrast so the two set forms don't blur together:

- Use `set => expr` when you just transform the input and want it stored.
- Use the block form `set (T $value) { ... }` when you need validation, multiple statements, or you're writing to *other* fields (as in the virtual `fullName` case).

## Hooks can be required by an interface

This is the feature that changed how I think about contracts. An interface can declare a property with a hook requirement, forcing implementers to expose it:

```php
interface HasSlug {
    public string $slug { get; }
}

class Article implements HasSlug {
    public string $slug {
        get => strtolower(str_replace(' ', '-', $this->title));
    }

    public function __construct(public string $title) {}
}
```

The interface says "anything that is `HasSlug` must have a readable `slug`." The implementer decides whether that's a real stored field or a computed virtual one. Before 8.4 you'd have needed a `getSlug()` method in the interface, which locked every implementer into method-call syntax. Now the contract is about the property, and the implementation is free.

You can require `{ get; }`, `{ set; }`, or both. The interface only states which operations must exist; it never dictates how they work.

## Pairing with asymmetric visibility

PHP 8.4 also introduced **asymmetric visibility**, and the two features are good friends. Asymmetric visibility lets a property be publicly readable but only writable from inside the class:

```php
class Account {
    public private(set) int $balance = 0;

    public function deposit(int $amount): void {
        $this->balance += $amount;
    }
}

$a = new Account();
echo $a->balance;   // 0, allowed
$a->balance = 100;  // Error: cannot write from outside
```

`public private(set)` means read is public, write is private. Combine that with a `set` hook and you get a property that outsiders can read freely, that only your own methods can assign, and that validates every assignment as it happens. That combination used to require a private field plus a public getter plus a guarded setter. Now it's a few lines on one property.

## What hooks can't do (and where to be careful)

A few honest limits, because pretending they don't exist just leads to confusing errors later:

- **readonly doesn't mix in the way you'd hope.** `readonly` and property hooks don't combine like an ordinary property does; if you need write-once semantics, reach for `readonly` on a plain property or model it explicitly. If your case is subtle, check the manual for your PHP version rather than assuming.
- **No free backing-field access on virtual properties.** A get-only virtual property has no storage. You can't read or write a hidden `$this->fullName` because it doesn't exist — the data lives in whatever other fields you built it from.
- **Hooks run on every access.** A `get` hook that does heavy work runs each time you read. If the result is expensive and stable, cache it in a separate private field yourself; hooks don't memoize for you.
- Don't reach for hooks to hide side effects like logging or database writes behind a `$obj->foo` read. It works, but the next person expects a property read to be cheap and pure, and surprising them is how bugs get born.

If you enjoy this era of the language tightening up its object model, it sits alongside earlier additions like [readonly properties](/blog/php-readonly-properties), [typed class constants](/blog/php-8-3-typed-class-constants), and the [complete guide to PHP enums](/blog/php-enums-complete-guide). Hooks are the piece that finally makes computed and guarded properties feel native.

## When hooks beat plain methods, and when they don't

Reach for property hooks when:

- The value is genuinely derived from other state and you want field-style access (`$box->area` instead of `$box->getArea()`).
- You're validating or normalizing simple scalar input on the way in.
- An interface should guarantee a readable or writable property without forcing method syntax on implementers.

Stick with plain methods when:

- The operation has real side effects or noticeable cost, where a method name like `refreshCache()` sets honest expectations.
- You need parameters. A hook's `get` takes no arguments; if the answer depends on inputs, it's a method, full stop.
- Your team ships across older PHP versions. Hooks are 8.4-only, so a library targeting 8.1 can't use them yet.

My rough rule after a few weeks with them: if I was about to write a getter or setter whose whole job is one line, it's probably a hook. If the method name would contain a verb like "fetch", "calculate over a range", or "send", it stays a method.

## FAQ

### Do property hooks replace `__get` and `__set`?

No, and they solve a different problem. Magic methods are catch-alls for *undefined* properties across the whole object. Hooks are declared on a *specific, typed* property, so you get IDE completion, type checks, and clarity. Use hooks for known properties; magic methods still have their place for truly dynamic access.

### Can I use a property hook without a backing field?

Yes. A property with only a `get` hook is virtual and stores nothing; its value is computed on read. You only get a backing field when the property can be written to, such as when it has a `set` hook that stores into it.

### Are property hooks slower than regular properties?

A plain field read is still cheaper than running hook code, because a hook is real code that executes on each access. For typical validation or simple derivation the cost is negligible. Just don't put expensive work in a `get` hook that gets hit in a tight loop without caching it yourself.

### What PHP version do I need for property hooks?

PHP 8.4, released in November 2024. They aren't available in 8.3 or earlier, so confirm your runtime and your CI matrix before relying on them in shared code.

## Wrapping up

PHP 8.4 property hooks close a gap the language carried for years: the boilerplate getter and setter that existed only to satisfy encapsulation. Now a property can compute itself, guard its own writes, or appear in an interface contract, all without a single throwaway method.

Start small. Find one value object with a computed field and turn it into a get-only virtual property. Then try `public private(set)` with a `set` hook on something that needs validation. Once the field-style access clicks, you'll spot getters worth deleting all over your codebase — and the classes that remain will say what they mean.