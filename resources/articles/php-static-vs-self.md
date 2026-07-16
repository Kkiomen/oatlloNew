---
name: "static vs self in PHP: Late Static Binding"
slug: php-static-vs-self
short_description: "Why self:: resolves to the defining class and static:: to the called class, and how that difference breaks inherited factory and builder methods."
language: en
published_at: 2027-04-28 09:00:00
is_published: true
tags: [php, oop, patterns]
---

A subclass of my query builder threw `Call to undefined method` on a method I could see, fully defined, right there in the child class. The call chain started on the child, so the method existed. It still blew up. The culprit was one word in the parent's factory: `new self()` instead of `new static()`. That one keyword decides whether inheritance actually works, and it trips people who have written PHP for years.

Here is the whole thing in one sentence: `self::` is resolved when PHP compiles the class, and it always means "the class this code was written in." `static::` is resolved when the method actually runs, and it means "the class you called this on." That second behavior — resolving the class at call time instead of definition time — is what the manual calls **late static binding**, and it landed in PHP 5.3 specifically to fix the factory-method problem below.

## What "compile time" and "runtime" actually mean here

Read this and predict the output before you scroll:

```php
class Model
{
    public static function create(): static
    {
        return new static();
    }

    public function origin(): string
    {
        return self::class . ' / ' . static::class;
    }
}

class User extends Model {}

$u = User::create();
echo get_class($u), PHP_EOL;
echo $u->origin(), PHP_EOL;
```

Output:

```
User
Model / User
```

`new static()` built a `User` because the method was *called on* `User`. Inside `origin()`, `self::class` stayed `Model` because that line of code lives in `Model`, while `static::class` reported `User` because that is what the call resolved to at runtime. Same object, two different answers, and both are correct — they answer different questions. `self` answers "where am I written?" `static` answers "who called me?"

Now swap one word and watch inheritance quietly die:

```php
class Model
{
    public static function create(): self   // <- self, not static
    {
        return new self();
    }
}

class User extends Model {}

echo get_class(User::create()), PHP_EOL;
```

Output:

```
Model
```

You asked `User` for an instance and got a `Model`. No error, no warning — just the wrong class handed back, which is worse, because it fails somewhere downstream where the stack trace no longer points at the cause.

## The bug that actually cost me an afternoon

This is the real one. A fluent builder with a static entry point:

```php
class QueryBuilder
{
    protected array $wheres = [];

    public static function make(): self
    {
        return new self();
    }

    public function where(string $column, mixed $value): self
    {
        $this->wheres[] = [$column, $value];
        return $this;
    }
}

class SoftDeleteBuilder extends QueryBuilder
{
    public function withoutTrashed(): self
    {
        $this->wheres[] = ['deleted_at', null];
        return $this;
    }
}

$rows = SoftDeleteBuilder::make()
    ->where('active', 1)
    ->withoutTrashed();
```

Output:

```
PHP Fatal error:  Uncaught Error: Call to undefined method QueryBuilder::withoutTrashed()
```

Trace it. `SoftDeleteBuilder::make()` runs the parent's `make()`, which does `new self()` — and `self` in that file is `QueryBuilder`. So you now hold a plain `QueryBuilder`. `->where('active', 1)` returns `$this`, still a `QueryBuilder`. Then `->withoutTrashed()` looks for a method that exists on the child but not on the object you actually have. Undefined method.

The maddening part is that the method is visibly defined. I kept re-reading the child class before it occurred to me to open the parent's factory — which is where the actual object was being built. Your eyes are on `SoftDeleteBuilder`, but the object never was one. The fix is two edits:

```php
public static function make(): static
{
    return new static();
}

public function where(string $column, mixed $value): static
{
    $this->wheres[] = [$column, $value];
    return $this;
}
```

`new static()` now produces a `SoftDeleteBuilder`, and the `static` return type tells your IDE and static analyzer the same truth so the chain type-checks. This is exactly why fluent builders, static factories, and the active-record pattern all lean on `static` — the whole point of those patterns is that a subclass gets its own type back without rewriting the base method.

## `new static()` vs `new self()`

Use `new self()` when you genuinely mean *this exact class* and want subclasses to receive an instance of the parent — a value object that must never be substituted, for example. That is rare. In practice, when you write `new self()` in a base class you almost always meant `new static()` and just did not notice because you never subclassed it. The day someone extends the class, it breaks.

My rule: in any method that is designed to be inherited — factories, named constructors, `clone`-style helpers, fluent setters returning `$this` — use `static`. Reach for `self` only when you can articulate why a subclass should *not* get its own type. "I couldn't think of a reason" is a vote for `static`.

## Forwarding vs non-forwarding calls

Late static binding tracks a hidden value the manual calls the "called class." Some calls forward it; some reset it. This is the part that surprises even people who know the `self`/`static` distinction:

```php
class A
{
    public static function create(): static
    {
        return new static();
    }

    public static function viaSelf(): static
    {
        return self::create();   // forwarding
    }

    public static function viaName(): static
    {
        return A::create();      // NON-forwarding
    }
}

class B extends A {}

echo get_class(B::viaSelf()), PHP_EOL;
echo get_class(B::viaName()), PHP_EOL;
```

Output:

```
B
A
```

`self::create()`, `parent::create()`, `static::create()`, and `forward_static_call()` are **forwarding** — they pass the called class along, so `static` inside `create()` stays `B`. But naming the class explicitly, `A::create()`, is **non-forwarding**: it resets the called class to `A`, and now `new static()` builds an `A` even though you started from `B`. So writing the class name out — which feels more explicit and safer — is the thing that silently disables late static binding. When you want the chain preserved, forward with `static::` or `self::`, not the literal name.

## `get_called_class()`

Before the `static::class` syntax existed, `get_called_class()` was the way to read the called class inside a static method, and it still works:

```php
class Repository
{
    public static function table(): string
    {
        return strtolower(get_called_class()) . 's';
    }
}

class Order extends Repository {}

echo Order::table(), PHP_EOL;   // orders
```

Today `static::class` gives you the same string and is resolved at compile time into a cheaper operation, so prefer it. `get_called_class()` earns its keep in one spot: a closure or callback where `static::class` is not in scope, or older code you are not ready to touch.

## How Laravel leans on all of this

Open `Illuminate\Database\Eloquent\Model` and you will find `new static` doing the heavy lifting. `newInstance()` runs `$model = new static((array) $attributes);`, which is why `User::create([...])` returns a `User` and not a bare `Model` — every model inherits one factory and each gets its own type back. That is the pattern from the section above, in production, across millions of installs.

The query builder does the same thing with return types. Model methods are annotated `@return static` (and hydration goes through `newModelInstance()` / `newFromBuilder()`, which also use `new static`), so when you write `User::query()->where(...)->first()`, your tooling knows the result is a `User`. Swap those `static` hints for `self` and every Eloquent subclass would type-hint as a base `Model` — autocomplete on your own accessors would vanish, and any inherited factory would hand back the wrong class at runtime. The convenience you take for granted in Eloquent *is* late static binding.

## `final`, `readonly`, and where `new static()` bites back

`static` is not free power. `new static()` couples every subclass to the parent's constructor signature. Add a required constructor argument in a child and the parent's factory throws at runtime:

```php
abstract class Money
{
    public function __construct(public readonly int $minorUnits) {}

    public static function of(int $minorUnits): static
    {
        return new static($minorUnits);
    }
}

final class Usd extends Money {}
```

`Usd::of(500)` works. But if a subclass declared `__construct(int $minorUnits, string $currency)` with the second argument required, `Parent::of()` would call `new static($minorUnits)` and throw `ArgumentCountError`. `static` promises "any subclass can be built the same way" — keep that promise or the abstraction leaks.

Two interactions worth holding in your head:

- **`final` collapses the distinction.** Mark a class `final` and it can have no subclasses, so `static` and `self` resolve to the same class. In a `final` class, `new static()` and `new self()` are behaviorally identical — reach for whichever reads clearer, and know that static analyzers treat `final` as a signal to narrow `static` down to the concrete type.
- **`readonly` properties raise the stakes.** A readonly property must be assigned exactly once, in the declaring scope. When a base factory uses `new static()` and a subclass adds a readonly property the parent's constructor never sets, touching it throws `must not be accessed before initialization`. The failure surfaces far from the factory, so it reads like a mystery. If your hierarchy mixes readonly state with inherited factories, make sure each level's constructor initializes its own readonly fields.

## FAQ

**When should I actually use `self` instead of `static`?**
When the type must not change under inheritance: an immutable value object you never want a subclass instance of, or a singleton whose one instance is deliberately the base class. If you cannot name that reason out loud, use `static`.

**Does `new static()` hurt performance?**
Not in any way you will measure. Late static binding resolves the called class from state PHP already carries on the call stack; it is not a class lookup by string. Correctness is the whole argument — pick `static` for behavior, not speed.

**Why did `A::create()` return the parent even though I called it from a subclass?**
Because naming the class explicitly is a non-forwarding call: it resets the called class to `A`, so `static` inside `create()` becomes `A`. Use `static::create()` or `self::create()` to forward the original called class down the chain.

**Can I use `static` as a return type on non-static methods?**
Yes. `public function where(...): static` on a fluent method is valid and encouraged — it tells the type system that `return $this` gives back the caller's concrete type, which is exactly what makes chained builder calls type-check on subclasses.

## The one-line takeaway

If a method is meant to be inherited and it constructs or returns "itself," write `static`, not `self` — and type-hint the return as `static` too so your tools agree with your runtime. Reserve `self` for the rare case where a subclass genuinely should not get its own type back. Next time you write a base factory or a fluent builder, grep it for `new self` and `: self`; most of those are latent bugs waiting for the first person who subclasses your class.
