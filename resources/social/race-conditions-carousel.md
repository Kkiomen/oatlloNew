---
slug: race-conditions-carousel
type: carousel
language: en
title: "Preventing race conditions in a web app"
topic: php
source_type: article
source: preventing-race-conditions-web-app
link: https://oatllo.com/preventing-race-conditions-web-app
publish_at: 2026-08-12 19:00
status: ready
formats: [post, reel]
hashtags: [php, laravel, concurrency, backend, database]
caption: |
  No amount of staring at this in code review will reveal the bug. The flaw is in the timing.

  You read, you decide, you write. Those are three steps, not one. Another
  request slips into the gap and your decision was made on a snapshot that went
  stale before you acted on it.

  Full write-up linked in bio.

  What is the strangest duplicate your app has ever created?
verified:
  verdict: approved
  at: 2026-07-16 07:13
  fingerprint: 61b641e2b36560fb87b1574b6b7349e4d58441fc
  checks:
    - SELECT-then-if-then-INSERT snippet and the atomic UPDATE ... WHERE remaining > 0 both match the article verbatim
    - check-then-act framing, the double-click duplicate charge and the unique-constraint CTA all trace to the article
    - SQL parses, the guard genuinely closes the read-write gap
---

## Two clicks. One order. Two charges.

The user double clicked. Your code had no opinion about that.

<!-- slide -->

## You read, you decide, you write

Three steps, not one. Between the read and the write, another request slips in
and changes the world out from under you.

<!-- slide -->

## The purest form of it

```php
$user = User::where('email', $email)
    ->first();

if ($user === null) {
    User::create(['email' => $email]);
}
```

Two signups. Both read nothing. Both insert.

<!-- slide -->

## Why review never catches it

The syntax is fine. The logic is fine. The flaw is in the timing, and timing
does not show up in a diff.

<!-- slide -->

## Push the decision into the statement

```sql
UPDATE coupons
SET remaining = remaining - 1
WHERE id = 42 AND remaining > 0;
```

The guard lives inside the write. No gap left to slip into.

<!-- slide role="cta" -->

## Then let the database refuse

A unique constraint turns "we hope this is unique" into a promise the database
keeps, even when your code is wrong.
