---
slug: php-carbon-dates-carousel
type: carousel
language: en
title: "Carbon mutability"
topic: php
source_type: article
source: php-carbon-dates
link: https://oatllo.com/php-carbon-dates
publish_at: 2026-10-27 19:00
status: ready
formats: [post, reel]
hashtags: [php, laravel, carbon, dates, backend]
caption: |
  Carbon is mutable, so addDays() moves the date you called it on. Both variables end up pointing at the same moment.

  The classic version: you compute a report range, reuse the start variable
  further down, and the numbers quietly go wrong with no error to point at.

  How long did that one take you to find?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: d83767662bb41c07a7821253f47841ab7245c5d2
  checks:
    - mutability example verified, both variables really do print 2026-01-08 because Carbon mutates in place
    - "Carbon 3 diff methods are signed floats where Carbon 2 returned an absolute int; absolute: true is the real named argument"
    - copy() and CarbonImmutable are both real fixes; immutable_datetime is a real Eloquent cast
    - hook mutation claim is what every slide delivers
  notes: |
    Carbon 3 ships with Laravel 11 matches the article. Laravel 11 allows ^2.72.2|^3.0, so a fresh install resolves to Carbon 3 - true, but it is the one claim tied to a version if the slot slips far.
---

## addDays() mutates the date you meant to keep

Carbon instances are mutable. `$start->addDays(7)` does not hand you a new date.
It moves `$start`.

<!-- slide -->

## Two variables. One object.

```php
$start = Carbon::parse('2026-01-01');
$end = $start->addDays(7);

$start->toDateString(); // 2026-01-08
$end->toDateString();   // 2026-01-08
```

`$start` was supposed to stay put. It walked forward a week instead.

<!-- slide -->

## Two fixes. One you have to remember.

```php
$end = $start->copy()->addDays(7);

$start = CarbonImmutable::parse('2026-01-01');
$end = $start->addDays(7); // $start is safe
```

`copy()` works until the day you forget it. `CarbonImmutable` returns a new
instance every time and never touches the original.

<!-- slide -->

## Your day counter went negative after the upgrade

```php
// Carbon 3: signed float, not absolute int
$end->diffInDays($start, absolute: true);
```

Carbon 3 ships with Laravel 11. `diffInDays()` is now signed and returns a
float. Carbon 2 returned an absolute integer.

<!-- slide role="cta" -->

## Fix it once, at the model level

Cast the column to `immutable_datetime` and the trap disappears for every read
of that date. Store UTC, convert only for display.
