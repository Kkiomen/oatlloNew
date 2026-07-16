---
name: "Value Objects in PHP for Safer Domain Code"
slug: php-value-objects
short_description: "How to model email, money and percentages as immutable self-validating types in PHP, and persist them with Laravel Eloquent casts."
language: en
published_at: 2027-06-07 09:00:00
is_published: true
tags: [php, laravel, architecture, ddd]
---

A bug report landed on my desk once that read: "customer charged 100x too much." The cause was a method that took `float $amount` and, three calls deep, someone passed cents where dollars were expected. Nothing in the type system objected. A `float` is a `float`. The database happily stored `19900.00`, the payment gateway happily charged it, and a real person got a real invoice for two hundred dollars instead of two.

That whole class of failure disappears when the thing you pass around isn't a `float` but a `Money`. This article is about building those small immutable types — value objects — in PHP 8.2+, checking their input once at construction, comparing them by value, and getting them in and out of the database with Laravel casts without leaking primitives across the boundary.

## What actually makes something a value object

Three properties, and all three matter:

- **Identity by value, not by id.** Two `Money` objects holding `1000` cents in `USD` are equal, full stop. There is no "which one" — unlike a `User`, where two rows with the same name are still two different people. If you find yourself wanting an `id`, you have an entity, not a value object.
- **Immutable.** Once built, it never changes. Need a different value? You get a new instance. This is the one people skip — "eh, I'll just be careful" — and the one that saves you the day a method three layers down decides to be helpful and mutate the thing you handed it.
- **Self-validating.** An instance that exists is, by definition, valid. A `Email` object cannot hold `"not-an-email"` because the constructor would have thrown before the object came into being. You validate once, at the edge, and every function downstream can stop asking "but is this actually an email?"

That last point is the whole game. The moment a value passes the constructor, its correctness stops being a question anyone else has to answer.

## The problem it fixes: primitive obsession

Primitive obsession is the habit of representing domain concepts with `string`, `int`, `float`, `array` — the language's built-in types — instead of types you named yourself. It reads as harmless. It compounds into bugs.

Look at a signature like this:

```php
function transfer(string $from, string $to, float $amount, string $currency): void
```

Every parameter is a landmine. Swap `$from` and `$to` — the compiler shrugs. Pass an amount in cents to a function expecting dollars — shrugs again. Pass `"EURO"` where the rest of the code expects `"EUR"` — you find out in production. The signature carries four values and zero guarantees.

Now the same intent with value objects:

```php
function transfer(AccountId $from, AccountId $to, Money $amount): void
```

You physically cannot pass a currency string in the wrong slot, because there is no currency slot — it lives inside `Money`. You cannot pass a raw `float`, because the parameter demands a `Money`. The number of ways to call this function wrong dropped from "many" to "almost none," and you did it with types, not with a comment begging the next developer to be careful.

## Building one: Email

Start small. An email is a string that has been validated and, conventionally, lowercased.

```php
final class Email
{
    public function __construct(
        public readonly string $value,
    ) {
        $normalized = strtolower(trim($value));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }

        // readonly means we can only assign inside the constructor, once.
        $this->value = $normalized;
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

A note that trips people up: you can assign to a `readonly` property exactly once, and only from inside the class scope. Here we accept the raw `$value` as the promoted parameter, then reassign `$this->value` to the normalized form in the constructor body. That reassignment is legal because it's still the initializing write. Try to write to it a second time — or from outside — and PHP throws `Error: Cannot modify readonly property`. If the mechanics of `readonly` are new to you, the [readonly properties guide](/php-readonly-properties) walks through the exact rules and where they bite.

The payoff shows up at the boundary of your app — a controller, a queue job, a CLI command:

```php
$email = new Email($request->input('email')); // throws here if malformed
```

One line. From that point on, `$email->domain()` is safe, `$email->value` is guaranteed lowercase and valid, and no function that receives an `Email` ever has to re-check. Validation moved to the edge and stayed there.

## Money: the object that earns its keep

Money is where value objects stop being a nicety and start preventing invoices for the wrong amount. Store the amount as an integer number of the currency's minor unit — cents for USD, grosze for PLN. Never store money as a `float`; `0.1 + 0.2` is `0.30000000000000004` and that error accumulates across a ledger.

```php
final class Money
{
    public function __construct(
        public readonly int $cents,
        public readonly string $currency,
    ) {
        if ($cents < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }

        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException("Bad currency code: {$currency}");
        }
    }

    public static function fromDollars(float $dollars, string $currency = 'USD'): self
    {
        return new self((int) round($dollars * 100), $currency);
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function percentage(Percentage $rate): self
    {
        return new self(
            (int) round($this->cents * $rate->asFraction()),
            $this->currency,
        );
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents
            && $this->currency === $other->currency;
    }

    public function format(): string
    {
        return number_format($this->cents / 100, 2) . ' ' . $this->currency;
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: {$this->currency} vs {$other->currency}",
            );
        }
    }
}
```

Watch what `add()` does: it does not mutate `$this`. It returns a **new** `Money`. That is the immutability rule made concrete, and it buys you something specific.

```php
$price = new Money(1999, 'USD');
$withTax = $price->add($price->percentage(new Percentage(8)));

echo $price->format();    // 19.99 USD — untouched
echo $withTax->format();  // 21.59 USD
```

`$price` is exactly what it was before you computed tax. Nobody's method call, no matter how many layers away, can reach in and change it. Compare that with a mutable design where `$price->add(...)` modifies the object in place: now the original amount depends on the entire history of calls that touched it, and reproducing a bug means reproducing that whole history. Immutable value objects have no history. What they hold is what you constructed them with.

Also notice `assertSameCurrency`. Adding USD to EUR is nonsense, and the object refuses. That rule lives in exactly one place — inside `Money` — instead of being copy-pasted into every service that touches money and forgotten in one of them.

## Percentage, and why the constructor is the right place for rules

```php
final class Percentage
{
    public function __construct(
        public readonly float $value,
    ) {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException(
                "Percentage must be 0-100, got {$value}",
            );
        }
    }

    public function asFraction(): float
    {
        return $this->value / 100;
    }
}
```

A tax rate of `-5` or `150` is a bug somewhere upstream. By rejecting it in the constructor, you turn a silent wrong-answer bug into a loud exception at the exact call site that produced the bad number. That's the trade you keep making with value objects: fail early and precisely, instead of late and mysteriously.

## Equality is by value, and you have to spell it out

PHP's `==` on objects compares properties loosely; `===` checks it's the same instance. For value objects you almost always want "same contents," which is why each class above has an explicit `equals()`:

```php
$a = new Money(500, 'USD');
$b = new Money(500, 'USD');

$a === $b;        // false — different instances
$a == $b;         // true, but == is loose and I don't trust it
$a->equals($b);   // true — and it means exactly what I want
```

Write `equals()` yourself and compare the fields that define identity. It's a few lines and it removes all ambiguity about what "equal" means for this type. This is also the kind of pure, dependency-free logic that's trivial to unit test — a value object is about the easiest thing in a codebase to cover, which is a nice side effect if you care about [testable PHP code](/testable-php-code).

## Persisting them with Eloquent casts

The obvious objection: "great, but my database has columns, not objects." Laravel's cast layer is the bridge. It converts a raw column value into your value object when you read the model, and back to a scalar when you save.

For a single-column value object like `Email`, a small cast class is the clean route. Create it with `php artisan make:cast EmailCast`:

```php
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class EmailCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Email
    {
        return $value === null ? null : new Email($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // Accept an Email, or a raw string and let the VO validate it.
        return $value instanceof Email ? $value->value : (new Email($value))->value;
    }
}
```

Wire it up on the model:

```php
protected function casts(): array
{
    return [
        'email' => EmailCast::class,
    ];
}
```

Now `$user->email` gives you an `Email` object with its `domain()` method, and `$user->email = 'ADA@Example.com'` stores `ada@example.com` — the cast runs the string through the constructor, which validates and normalizes it. A malformed address never reaches the `INSERT`.

`Money` spans two columns (`price_cents`, `currency`), so make the value object itself `Castable` and hand back an anonymous cast:

```php
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class Money implements Castable
{
    // ...constructor and methods from before...

    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes {
            public function get($model, $key, $value, $attributes): ?Money
            {
                if ($attributes['price_cents'] === null) {
                    return null;
                }

                return new Money(
                    (int) $attributes['price_cents'],
                    $attributes['currency'],
                );
            }

            public function set($model, $key, $value, $attributes): array
            {
                if ($value === null) {
                    return ['price_cents' => null, 'currency' => null];
                }

                return [
                    'price_cents' => $value->cents,
                    'currency' => $value->currency,
                ];
            }
        };
    }
}
```

On the model: `'price' => Money::class`. Reading `$product->price` reconstructs `Money` from two columns; assigning a `Money` writes both back. The `set` method returning an array of `column => value` is how one cast maps to multiple columns — a detail the docs mention but is easy to miss the first time.

For a value object that's genuinely one attribute derived on the fly, the lighter `Attribute::make(get:, set:)` accessor works too, but for anything you store I reach for a real cast class or `Castable` — it keeps the conversion logic next to the type instead of scattered across model accessors.

## The serialization boundary

Here's the rule that keeps value objects from leaking everywhere and becoming a burden: **objects live in the middle, primitives live at the edges.**

Inside your domain and services, pass `Money` and `Email` around. But at the two boundaries — the database and the outside world (JSON, forms, queue payloads) — you convert to and from scalars. The Eloquent cast handles the database edge. For the HTTP edge, convert explicitly in your API resource or wherever you serialize:

```php
public function toArray($request): array
{
    return [
        'email' => $this->email->value,
        'price' => [
            'cents' => $this->price->cents,
            'currency' => $this->price->currency,
        ],
    ];
}
```

Resist the temptation to make your value objects implement `JsonSerializable` and auto-encode. It sounds convenient, but it couples your internal model to your public API shape, and the day you want the API to look different from the object you'll be fighting the class. Convert at the boundary, on purpose. The value object's job is to be correct in memory; deciding what the wire format looks like is a separate decision that belongs to the serializer.

## When not to bother

Value objects are cheap but not free, and not every string deserves one. My rough test: does this concept have **rules** (validation, normalization, invariants) or **behavior** (methods that operate on it)? An email has both. A user's free-text bio has neither — it's just a string, leave it a string. Wrapping every scalar in a class is how the pattern earns a bad reputation. Reach for it when there's a rule you're tired of re-checking or an operation that keeps getting reimplemented slightly differently.

## FAQ

**Do value objects hurt performance in PHP?**
Not in any way you'll measure in a normal web app. Constructing a small object and running a `preg_match` is nanoseconds; a single database round-trip dwarfs thousands of them. If you're building millions of them in a tight loop — say, parsing a huge import — profile it, but for request-scoped code the cost is noise next to the bugs it prevents.

**readonly properties or a private property with a getter?**
Since PHP 8.1, `public readonly` is the cleaner choice for value objects. It gives you immutability and public read access without the boilerplate of a private field plus a getter that does nothing but return it. Use a method only when the value is computed (like `Money::format()`) rather than stored.

**How do I change one field of a value object?**
You don't change it — you build a new one. Add a `with*` helper that returns a fresh instance: `public function withCurrency(string $c): self { return new self($this->cents, $c); }`. The original stays untouched, which is the entire point.

**Can I use these with Laravel form request validation?**
Yes, and you should keep both. Form request rules give the user friendly, per-field error messages before anything is constructed. The value object is the last-line guarantee that no invalid instance exists deeper in the system, including from code paths that never touched a form. They protect different layers.

The next time you write a function signature and reach for `string` or `float`, pause on the ones that carry real meaning — money, email, a status, a rate. Give those a name and a constructor. You're not adding ceremony; you're moving the "is this valid?" question to the one place it can be answered once, and deleting it from everywhere else.
