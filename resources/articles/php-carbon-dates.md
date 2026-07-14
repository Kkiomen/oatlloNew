---
name: "Working with Dates in PHP Using Carbon"
slug: php-carbon-dates
short_description: "A practical guide to PHP Carbon dates: parsing, formatting, timezones, comparisons, and the mutability bug that bites everyone once."
language: en
published_at: 2026-12-25 09:00:00
is_published: true
tags: [php, laravel, carbon, dates]
---

If you have ever tried to add a month to a date with raw `DateTime` and then spent twenty minutes fighting timezone offsets, you already know why PHP Carbon dates became the default way most of us handle time. Carbon wraps PHP's native `DateTime`, keeps all of its power, and adds a readable API on top. It ships with every Laravel install through the `nesbot/carbon` package, so most PHP developers already have it sitting in `vendor/` whether they reached for it or not.

This guide walks through the parts I actually use week to week: creating instances, moving dates around, comparing them, formatting output, and the timezone rules that keep production data sane. I'll also cover the one bug that eventually catches everyone.

## Creating Carbon instances

There are a handful of entry points, and picking the right one saves you from string-parsing surprises later.

```php
use Carbon\Carbon;

$now = Carbon::now();                 // current moment, app timezone
$today = Carbon::today();             // today at 00:00:00
$parsed = Carbon::parse('2026-12-25 09:00:00');
$fromFormat = Carbon::createFromFormat('d/m/Y', '25/12/2026');
```

`Carbon::parse()` is the flexible one. It swallows almost anything a human or an API might throw at it: ISO strings, `next monday`, `+2 weeks`, timestamps. That flexibility is also its weakness. If the input format is fixed and you control it, prefer `Carbon::createFromFormat()`, because it fails loudly when the string doesn't match instead of guessing.

In Laravel you rarely call these directly. The framework gives you global helpers:

```php
$now = now();       // Carbon::now()
$today = today();   // Carbon::today()
```

Both respect the timezone set in your `config/app.php`, which matters more than people expect. More on that below.

## Manipulating dates

This is where Carbon earns its keep. The method names read like English, and they chain.

```php
$invoice = Carbon::parse('2026-12-25');

$dueDate = $invoice->copy()
    ->addDays(30)
    ->startOfDay();

$lastQuarter = now()->subMonths(3);
```

Notice the `copy()` call. I put it there on purpose, and it's the crux of the mutability issue I'll get to. A quick reference for the manipulation methods that come up most:

- `addDays()`, `addWeeks()`, `addMonths()`, `addYears()` and their `sub` counterparts
- `startOfDay()` and `endOfDay()` to pin a timestamp to midnight or 23:59:59
- `startOfMonth()`, `endOfMonth()` for billing cycles and reports
- `next()` / `previous()` for things like "the next Friday"

Month math has one honest gotcha worth knowing. Adding a month to January 31 lands on February 28 (or 29), not some overflowed March date. Carbon clamps to the end of the target month, which is usually what you want but occasionally surprises people migrating from hand-rolled logic.

## Comparing dates

Comparisons are where readable code pays off during a 2 a.m. debugging session.

```php
$start = Carbon::parse('2026-01-01');
$end = Carbon::parse('2026-12-31');
$check = now();

if ($check->between($start, $end)) {
    // inside the range
}

$check->gt($start);        // greater than
$check->lt($end);          // less than
$check->isPast();          // already happened?
$check->isFuture();        // still to come?
$check->equalTo($start);   // same moment?
```

The boolean helpers (`isPast()`, `isFuture()`, `isToday()`, `isWeekend()`) tend to make intent obvious in a way that a raw `>` comparison never quite does. When a reviewer can read the line out loud and understand the business rule, you've written good code.

For measuring gaps between two dates, Carbon gives you `diffInDays()`, `diffInHours()`, `diffInMonths()`, and friends:

```php
$signup = Carbon::parse('2026-01-01');
$today = Carbon::parse('2026-07-08');

$daysActive = $signup->diffInDays($today);
```

One heads-up if you're on Carbon 3 (Laravel 11 and up ship it): these `diff` methods now return a **float** and are **signed** by default, meaning the result respects direction and can be negative. Carbon 2 returned an absolute integer. If you upgraded and your "days remaining" counter suddenly went negative, this is why. Pass `absolute: true` to get the old behaviour:

```php
$days = $end->diffInDays($start, absolute: true); // always positive
```

## Formatting output

Storage and display are two different jobs. Store machine-readable, display human-readable.

```php
$date = Carbon::parse('2026-12-25 09:00:00');

$date->toDateString();          // "2026-12-25"
$date->toDateTimeString();      // "2026-12-25 09:00:00"
$date->format('D, M j Y');      // "Fri, Dec 25 2026"
$date->diffForHumans();         // "in 5 months" (relative to now)
```

`diffForHumans()` is the crowd-pleaser. It's what powers every "posted 3 hours ago" label you've ever seen. It reads the difference against the current time and phrases it naturally, and it's locale-aware if you configure the locale.

For anything stored in a database or sent over an API, stick to `toDateString()` / `toDateTimeString()` or an explicit ISO format. Locale-flavoured `format()` output belongs in the view layer, not the payload.

## Timezones, and why you should store UTC

Here's the rule I wish someone had hammered into me earlier: store everything in UTC, convert to the user's timezone only when you display it. Skip this and you get bugs that only appear for users east of London or during daylight-saving changes, which are miserable to reproduce.

```php
$utc = Carbon::parse('2026-12-25 09:00:00', 'UTC');

$warsaw = $utc->copy()->setTimezone('Europe/Warsaw'); // 10:00 in winter
$tokyo = $utc->copy()->setTimezone('Asia/Tokyo');     // 18:00
```

`setTimezone()` shifts the wall-clock representation without changing the actual moment in time. The instant is identical; only the label changes. Set your database and `config/app.php` timezone to `UTC`, keep the display conversion at the edge, and a whole category of problems disappears.

## The mutability bug everyone hits once

Carbon instances are **mutable**. When you call `addDays()`, you are modifying the original object, not getting a fresh copy back. This trips up almost everyone the first time.

```php
$start = Carbon::parse('2026-01-01');
$end = $start->addDays(7);

echo $start->toDateString(); // "2026-01-08"  <-- start moved too!
echo $end->toDateString();   // "2026-01-08"
```

Both variables point at the same object. `$start` was supposed to stay put, and instead it walked forward a week. The classic place this bites: you compute a date range for a report, reuse the "start" variable further down, and the numbers quietly go wrong with no error to point at.

Two fixes. The quick one is `copy()` before you mutate:

```php
$end = $start->copy()->addDays(7); // $start untouched
```

The better one, especially in new code, is `CarbonImmutable`. Every operation returns a new instance and the original never changes:

```php
use Carbon\CarbonImmutable;

$start = CarbonImmutable::parse('2026-01-01');
$end = $start->addDays(7);

echo $start->toDateString(); // "2026-01-01"  <-- safe
echo $end->toDateString();   // "2026-01-08"
```

I default to `CarbonImmutable` on new projects now. The mental overhead of remembering `copy()` everywhere isn't worth it, and immutability matches how most developers assume the code behaves anyway.

## Carbon and Laravel model casts

If you work in Laravel, you get Carbon almost for free. Any column listed in a model's `$casts` as `datetime` (and the default `created_at` / `updated_at`) comes back as a Carbon instance, not a string.

```php
class Order extends Model
{
    protected $casts = [
        'shipped_at' => 'datetime',
    ];
}

$order = Order::find(1);
$order->shipped_at->diffForHumans(); // works directly
$order->shipped_at->isPast();        // it's already Carbon
```

You can even use `immutable_datetime` as the cast type to get `CarbonImmutable` back and sidestep the mutability trap at the model level. This pairs nicely with typed properties. If you enjoy tightening up model definitions, the same mindset shows up in [PHP readonly properties](/blog/php-readonly-properties).

## Common pitfalls

A short list of the things that have actually cost me time:

- Forgetting `copy()` before mutating a shared instance, then wondering why an earlier date changed
- Trusting `Carbon::parse()` with user input that has an ambiguous format like `03/04/2026` (is that March or April?)
- Storing local time in the database instead of UTC, then debugging phantom one-hour shifts twice a year
- Assuming `diffInDays()` returns a positive integer after a Carbon 2 to 3 upgrade
- Running `format()` with a locale string in API responses, then breaking a consumer that expected ISO

## FAQ

### Is Carbon part of PHP or a separate library?

Separate. Carbon is the `nesbot/carbon` package built on top of PHP's native `DateTime`. It isn't in core PHP, but it ships bundled with Laravel, so if you use the framework it's already installed.

### What's the difference between Carbon and CarbonImmutable?

`Carbon` objects change in place when you call methods like `addDays()`. `CarbonImmutable` objects never change; each method returns a brand-new instance. Use `CarbonImmutable` when you want to avoid accidentally mutating a date you meant to keep.

### How do I get the difference between two dates in days?

Use `$dateA->diffInDays($dateB)`. On Carbon 3 the result is a signed float, so add `absolute: true` if you only care about the magnitude: `$dateA->diffInDays($dateB, absolute: true)`.

### Why is my Carbon date off by a few hours?

Almost always a timezone mismatch. Confirm your app timezone in `config/app.php`, store timestamps as UTC in the database, and convert with `setTimezone()` only for display.

## Wrapping up

PHP Carbon dates come down to a few habits that keep time handling boring, which is exactly what you want. Reach for `Carbon::createFromFormat()` when the input format is fixed and `parse()` when it isn't. Store UTC, display local. Prefer `CarbonImmutable` so `copy()` stops being something you have to remember. Lean on the readable comparison helpers so the next person can understand your business rules at a glance.

Start with one change on your current project: switch a model's date cast to `immutable_datetime` and watch a class of subtle bugs stop happening. From there, the rest of the API is just discoverability.