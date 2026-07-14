---
name: "PHP match Expression vs switch: What Changed"
slug: php-match-vs-switch
short_description: "PHP match vs switch compared: strict comparison, return values, exhaustiveness, and when each one is actually the right tool."
language: en
published_at: 2026-11-09 09:00:00
is_published: true
tags: [php, php8, control-flow]
---

If you write PHP for a living, the choice of **php match vs switch** comes up more often than you'd think. Every time you map an input to an output, you reach for one of them out of habit. But `match` (added in PHP 8.0) isn't just a prettier `switch`. It changes the comparison rules, it hands you back a value, and it refuses to stay quiet when nothing matches. Those three differences will bite you the first time you swap one for the other without reading the fine print.

I've migrated a fair amount of legacy `switch` code to `match`, and most of the time it was a win. A few times it wasn't. This article walks through what actually changed, with runnable examples, so you can pick the right one instead of guessing.

## The core difference in one example

Here's the same logic written both ways. Look at the shape before we get into the rules.

```php
// switch: a statement, mutates a variable
function labelSwitch(int $code): string
{
    switch ($code) {
        case 200:
        case 201:
            $label = 'Success';
            break;
        case 404:
            $label = 'Not Found';
            break;
        default:
            $label = 'Unknown';
    }

    return $label;
}

// match: an expression, returns directly
function labelMatch(int $code): string
{
    return match ($code) {
        200, 201 => 'Success',
        404      => 'Not Found',
        default  => 'Unknown',
    };
}
```

Same result, but the `match` version has no `break`, no repeated `$label =`, and no intermediate variable. The whole thing *is* the value. That's the headline feature: `match` is an expression, `switch` is a statement.

## Strict vs loose comparison: the part that breaks things

This is the difference that causes real bugs during a migration, so it goes first.

- **`switch` uses loose comparison (`==`)** and performs type juggling.
- **`match` uses strict comparison (`===`)** — same value *and* same type.

That sounds academic until you feed it a string that looks like a number.

```php
$input = '1e1'; // a string

// switch: '1e1' == 10 is true because of numeric string juggling
switch ($input) {
    case 10:
        echo "switch matched 10\n"; // this runs
        break;
    default:
        echo "switch default\n";
}

// match: '1e1' === 10 is false, types differ
echo match ($input) {
    10      => "match matched 10\n",
    default => "match default\n", // this runs
};
```

The `switch` juggles `'1e1'` into the integer `10` and matches. The `match` compares types too, sees a string against an int, and moves on. Neither is "wrong" — but if you migrate a `switch` that was quietly relying on loose comparison, `match` will silently take a different branch. I've seen this specifically with values coming out of `$_GET`, where everything is a string. Check those cases carefully.

A quick reference for what juggles under `==` and therefore behaves differently:

| Compared values | `switch` (`==`) | `match` (`===`) |
|---|---|---|
| `'1' ` vs `1` | matches | no match |
| `'1e1'` vs `10` | matches | no match |
| `0` vs `'foo'` (PHP 8+) | no match | no match |
| `null` vs `''` | matches | no match |
| `true` vs `1` | matches | no match |
| `'abc'` vs `'abc'` | matches | matches |

Note the `0 == 'foo'` row: PHP 8 already fixed that particular loose-comparison trap for `switch` too, so it's no longer the footgun it was in PHP 7. Still, the string-to-number cases remain.

## Exhaustiveness: match won't let you forget a case

With `switch`, if no `case` matches and there's no `default`, execution just falls out the bottom and nothing happens. Silent. Your `$label` might stay undefined.

`match` does the opposite. If no arm matches and you didn't write a `default`, it throws `\UnhandledMatchError`.

```php
function priority(string $level): int
{
    return match ($level) {
        'low'    => 1,
        'medium' => 2,
        'high'   => 3,
        // no default on purpose
    };
}

priority('critical'); // throws \UnhandledMatchError
```

That's a feature, not an annoyance. It turns "I forgot a case" from a silent bug into a loud exception at the exact spot it happened. When I map over a fixed set of statuses or enum cases, I deliberately leave out `default` so a newly added case fails fast instead of falling through to some catch-all. If you genuinely want a fallback, add `default` and it behaves like you'd expect.

This pairs beautifully with enums. If you're modeling a closed set of states, see our [complete guide to PHP enums](/blog/php-enums-complete-guide). A `match` over an enum with no `default` gives you compile-time-ish safety, because adding a case without handling it blows up at runtime immediately.

## No fall-through, so no forgotten break

The classic `switch` bug: you forget a `break` and execution falls through into the next case.

```php
switch ($role) {
    case 'admin':
        grantAdmin();
        // oops, no break, falls into 'editor'
    case 'editor':
        grantEditing();
        break;
}
```

An admin here accidentally runs `grantEditing()` too. Sometimes that's intentional; more often it's a typo that survives code review.

`match` has no fall-through at all. Each arm is independent. To group values you list them with commas on the same arm, which is explicit and readable:

```php
$access = match ($role) {
    'admin', 'editor' => 'write',
    'viewer'          => 'read',
    default           => 'none',
};
```

The comma-separated arm is how you say "these inputs share an outcome" without any of the fall-through risk. One arm, multiple conditions, one result.

## Matching conditions instead of a value

A lesser-known trick: `match (true)`. Because each arm is a strict boolean check, you can use `match` for range logic that a normal `switch` can't express cleanly.

```php
function grade(int $score): string
{
    return match (true) {
        $score >= 90 => 'A',
        $score >= 80 => 'B',
        $score >= 70 => 'C',
        default      => 'F',
    };
}
```

Each arm is evaluated `true === (expression)` top to bottom, and the first match wins. Order matters here, so put the tightest condition first. This is the pattern I reach for instead of a long `if/elseif` ladder when every branch returns a value.

## What switch can still do that match can't

`match` isn't a drop-in replacement. There are jobs it wasn't built for.

- **Running multiple statements per branch.** A `match` arm is a single expression. If a branch needs to log, mutate three things, and call two services, `switch` (or `if`) is the honest choice. You can shove a function call in a `match` arm, but stuffing a block of side effects there reads badly.
- **Intentional fall-through.** Rare, but if you actually want cascading behavior, `switch` expresses it directly.
- **No return value needed.** When a branch is purely about side effects and there's nothing to assign, a `switch` statement is fine and arguably clearer about intent.

The rule of thumb I use: if the whole construct exists to *produce a value*, use `match`. If it exists to *do things*, `switch` or `if` is often the better fit.

## Side-by-side comparison

| Aspect | `switch` | `match` |
|---|---|---|
| Introduced | Always | PHP 8.0+ |
| Kind | Statement | Expression (returns a value) |
| Comparison | Loose (`==`), type juggling | Strict (`===`) |
| Fall-through | Yes (needs `break`) | No |
| `break` required | Yes | No |
| Multiple values per branch | Stacked `case` labels | Comma-separated in one arm |
| Body per branch | Multiple statements | Single expression |
| No match, no default | Silently does nothing | Throws `\UnhandledMatchError` |
| Best for | Side effects, multi-statement branches | Mapping input to a value |

## A realistic migration note

When I convert a `switch` to `match`, I run through a short mental checklist:

1. Are any cases relying on loose comparison? Numeric strings, `null` vs `''`, booleans against ints, and those need explicit casting or they'll change behavior.
2. Does any branch do more than compute one value? If yes, it probably shouldn't be a `match`.
3. Do I want a `default`, or do I want it to throw? For closed sets I skip `default` on purpose.

The strict comparison and the thrown error are the two things that surprise people. Everything else is quality-of-life. And once your branches return typed values, `match` composes nicely with other modern PHP features like [readonly properties](/blog/php-readonly-properties) and [typed class constants](/blog/php-8-3-typed-class-constants) when you're building value objects.

## FAQ

### Is match faster than switch in PHP?

The performance difference is negligible for almost any real workload. Don't choose between them for speed — choose based on the comparison semantics (`===` vs `==`), whether you need a return value, and whether you want the safety of `\UnhandledMatchError`. Correctness and readability are the real deciding factors.

### Can match handle multiple values in one case like switch?

Yes. Separate them with commas in a single arm: `1, 2, 3 => 'small'`. It's the direct equivalent of stacking `case 1: case 2: case 3:` in a `switch`, but without any fall-through and without needing `break`.

### What happens if no arm matches in a match expression?

If you provided a `default`, that arm runs. If you didn't, PHP throws `\UnhandledMatchError`. This is intentional: it prevents the silent "nothing happened" behavior you get from a `switch` with no matching `case` and no `default`.

### Does match do any type juggling like switch?

No. `match` compares with `===`, so both value and type must match. A string `'1'` will not match an integer `1`. If your old `switch` depended on that juggling, you need to cast values explicitly before the `match` or your branches will behave differently.

## Conclusion

Reach for **`match`** whenever the goal is to turn an input into a value: it's shorter, it uses strict `===` comparison, it can't fall through, and it fails loudly instead of silently when you miss a case. Keep **`switch`** for branches that run multiple statements, need intentional fall-through, or exist purely for side effects.

Concretely, next time you write a `switch` just to assign one variable across a few cases, delete it and write a `match` that returns directly. Then double-check any branch that was comparing numeric strings or nulls, because strict comparison is where the behavior quietly shifts. Get those two habits right and the migration is almost always a net improvement.