---
name: "PHP Enums: A Complete Guide with Real Examples"
slug: php-enums-complete-guide
short_description: "A practical guide to PHP enums: pure vs backed, cases(), from(), tryFrom(), methods, interfaces, and real-world usage in Laravel."
language: en
published_at: 2026-08-10 09:00:00
is_published: true
tags: [php, enums, laravel, php8]
---

We have all written the class stuffed with `const STATUS_ACTIVE = 'active'` lines, then spent an afternoon hunting down the bug that turned out to be a method quietly accepting `'aktive'`. **PHP enums**, added in **PHP 8.1**, close that hole. They give you a first-class type for a fixed set of values, so the typo'd string never gets past the type declaration in the first place.

I have spent a fair bit of the last two years migrating older codebases onto them, and the biggest payoff surprised me. It was not the type safety. It was that the IDE finally understands what a "status" actually is: autocomplete, rename-refactoring, and PHPStan all start pulling in the same direction. Everything below is code you can paste into a file and run.

## What is a PHP enum?

An enum defines a type whose values are limited to a predefined set of named cases. You declare it with the `enum` keyword:

```php
enum Suit
{
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;
}

$card = Suit::Hearts;

var_dump($card === Suit::Hearts); // bool(true)
var_dump($card instanceof Suit);  // bool(true)
```

Each case is an object. Here is the part people miss: **cases are singletons**. `Suit::Hearts` is the exact same instance everywhere in your app, so `===` works and means what you expect. There is only ever one `Hearts` in memory, for the whole request.

## Pure enums vs backed enums

There are two flavors, and picking the right one matters.

### Pure enums

A **pure enum** has cases with no underlying scalar value. The `Suit` example above is pure. Use these when the case itself is the only thing you care about and you never need to store or serialize it directly.

### Backed enums

A **backed enum** attaches a scalar value - either `int` or `string` - to every case. You declare the backing type after a colon:

```php
enum Status: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}

echo Status::Published->value; // "published"
```

Backed enums are what you reach for most of the time in real applications, because you usually need to **persist** the value to a database, send it over an API, or read it from a form. A pure enum has no `->value` at all - reach for it anyway and PHP hands you a `Warning: Undefined property` plus a silent `null`, which is exactly the kind of bug that slips through to production. So keep the two kinds straight in your head.

A few rules worth burning into memory:

- The backing type is **either `int` or `string`** - nothing else.
- Every case **must** have a unique value. Duplicate values throw a fatal error at declaration time.
- Backed enum cases expose a readonly `->value` property; pure enum cases do not.
- Both kinds expose a `->name` property (`Status::Draft->name` is `"Draft"`).

## Reading cases: cases(), from(), and tryFrom()

This trio does most of the day-to-day work.

### cases()

Every enum gets a static `cases()` method that returns an array of all its cases, in declaration order:

```php
foreach (Status::cases() as $status) {
    echo $status->name . ' => ' . $status->value . PHP_EOL;
}
// Draft => draft
// Published => published
// Archived => archived
```

This is perfect for building `<select>` dropdowns, validation lists, or seeding data. No manual array to keep in sync.

### from()

`from()` (backed enums only) takes a scalar and returns the matching case. If nothing matches, it **throws a `ValueError`**:

```php
$status = Status::from('published'); // Status::Published

$bad = Status::from('nope');
// ValueError: "nope" is not a valid backing value for enum Status
```

Use `from()` when a missing match is genuinely an exceptional situation - for example, data you control that should never be wrong.

### tryFrom()

`tryFrom()` does the same lookup but **returns `null` instead of throwing** when there is no match:

```php
$status = Status::tryFrom('published'); // Status::Published
$missing = Status::tryFrom('nope');     // null

$status = Status::tryFrom($request->input('status')) ?? Status::Draft;
```

This is my default for anything coming from the outside world - request payloads, query strings, a third-party webhook that swears it only sends known statuses right up until it does not. Pair it with the null coalescing operator and the fallback fits on one line. Reserve `from()` for values you generated yourself, where a mismatch really does mean something upstream is broken.

## Methods and interfaces on enums

Here is where enums stop being glorified constants and start being genuinely useful. **Enums can have methods**, and inside a method `$this` refers to the current case.

```php
enum Status: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            Status::Draft     => 'Draft (not visible)',
            Status::Published => 'Published',
            Status::Archived  => 'Archived',
        };
    }

    public function isVisible(): bool
    {
        return $this === Status::Published;
    }
}

echo Status::Published->label();      // "Published"
var_dump(Status::Draft->isVisible()); // bool(false)
```

Notice how the behavior lives right next to the data it describes. When I add a new case, the `match` expression tells me exactly where I forgot to handle it - because a `match` without a matching arm throws `UnhandledMatchError`. That is a feature, not an annoyance.

Enums can also **implement interfaces**, which is great for enforcing a contract across several enums:

```php
interface HasColor
{
    public function color(): string;
}

enum Priority: int implements HasColor
{
    case Low = 1;
    case Medium = 2;
    case High = 3;

    public function color(): string
    {
        return match ($this) {
            Priority::Low    => 'green',
            Priority::Medium => 'orange',
            Priority::High   => 'red',
        };
    }
}
```

You can also add static methods (factory helpers are a common pattern) and even use traits inside an enum.

## Constants on enums

Enums may declare **constants**, including one that references a case as its default:

```php
enum Status: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    const Default = self::Draft;
}

var_dump(Status::Default === Status::Draft); // bool(true)
```

This reads nicely when you want a canonical "default" without hardcoding a case name all over the codebase.

## The one big restriction: no state

This trips up newcomers constantly, so let me be blunt: **enums cannot have properties or hold mutable state.** You cannot do this:

```php
enum Status: string
{
    case Draft = 'draft';

    public int $count = 0; // Fatal error: not allowed
}
```

Why? Because cases are singletons. If a case could carry mutable state, that state would be shared globally and silently corrupt every other place using the same case. Enums are meant to be constant, comparable values - not little data buckets. If you need per-instance state, you want a plain object, not an enum.

## Using enums in match

Enums and `match` are made for each other. Since cases are singletons compared with strict equality, `match` handles them perfectly:

```php
function nextAction(Status $status): string
{
    return match ($status) {
        Status::Draft     => 'Review and publish',
        Status::Published => 'Nothing to do',
        Status::Archived  => 'Restore if needed',
    };
}

echo nextAction(Status::Draft); // "Review and publish"
```

Because the enum type is fixed, you get an exhaustive-ish check: forget a case and PHP throws at runtime. Combine that with a static analyzer like PHPStan or Psalm and you get the forgotten-case warning at build time instead.

## Enums in Laravel

If you are on Laravel, enums slot right into Eloquent. Cast a backed enum on a model and it converts automatically both ways:

```php
class Post extends Model
{
    protected $casts = [
        'status' => Status::class,
    ];
}

$post = Post::find(1);
$post->status;        // Status instance, e.g. Status::Published
$post->status->label();

$post->status = Status::Archived; // stored as "archived" in the DB
$post->save();
```

Validation understands them too, via `Rule::enum(Status::class)`, and implicit route model binding resolves a backed enum straight from the URL segment. Taken together, that is a whole mapping layer - the `intval`, the `in_array` guard, the manual cast in the controller - that you simply stop writing.

## Common pitfalls

A quick list of things that bite people (they bit me):

- **Calling `->value` on a pure enum.** Only backed enums have it. On a pure enum you get a `Warning: Undefined property` and a `null` back - no exception, so it is easy to miss until something downstream breaks.
- **Using `from()` on user input.** A single bad value crashes the request with a `ValueError`. Use `tryFrom()` and handle `null`.
- **Comparing with `==` and expecting trouble.** It works because of singletons, but stick to `===` so your intent is clear and static analysis is happy.
- **Trying to add properties.** Not allowed. Enums are stateless by design.
- **Duplicate backing values.** Two cases with the same `value` is a fatal error at declaration.
- **Assuming enums exist before 8.1.** They do not. On PHP 8.0 or older you are stuck with class constants or the old `myclabs/php-enum` library.
- **Serializing pure enums to JSON.** `json_encode` on a pure enum returns `false` and sets the error "Non-backed enums have no default serialization" (it only throws if you pass `JSON_THROW_ON_ERROR`). Backed enums serialize to their `->value` automatically. Implement `JsonSerializable` if you need custom output.

## FAQ

### When should I use a pure enum vs a backed enum?
Use a backed enum whenever the value needs to leave your PHP process - saved to a database, sent in an API response, or read from a form. Use a pure enum when the case is purely an in-memory concept and never gets serialized. When in doubt, backed enums are more flexible.

### What version of PHP do I need for enums?
PHP 8.1 or newer. Enums are not available in 8.0 or earlier. If you are stuck on an older version, class constants or a userland enum library are your fallback.

### Can PHP enums have properties?
No. Enums cannot declare properties or hold state, because every case is a shared singleton instance. They can have methods, constants, and implement interfaces - just no mutable data. If you need state, use a regular class.

### What is the difference between from() and tryFrom()?
Both look up a case by its backing value. `from()` throws a `ValueError` when there is no match; `tryFrom()` returns `null`. Use `tryFrom()` for untrusted input and `from()` when a missing value should genuinely be treated as an error.

## Conclusion

PHP enums turn fuzzy strings and scattered constants into a real, type-safe part of your domain model. Start by replacing your status and type constants with **backed enums**, lean on `tryFrom()` at your input boundaries, and move any related logic into methods on the enum so behavior lives beside the data. Add an interface when several enums share a contract, and let `match` guarantee you handle every case.

Once you have shipped a feature or two built on them, going back to loose strings feels genuinely uncomfortable - which is the clearest sign the feature earns its place in your toolbox.