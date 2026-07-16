---
slug: laravel-feature-flags-pennant-carousel
type: carousel
language: en
title: "Pennant flag traps"
topic: laravel
source_type: article
source: laravel-feature-flags-pennant
link: https://oatllo.com/laravel-feature-flags-pennant
publish_at: 2026-09-14 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, pennant, featureflags, backend]
caption: |
  A Lottery is an object, and every object in PHP is truthy. Your A/B test has one arm.

  Pennant stores the resolved value per user, which is the feature and the trap:
  change the closure and the old answer keeps coming back until you purge.

  Full guide linked in bio.

  Which flag outlived its ticket the longest?
verified:
  verdict: issues
  at: 2026-07-16 07:15
  fingerprint: c771cedf4a48a4fb3205adeb4da0142300102327
  notes: |
    FALSE PREMISE - the whole hook and slide 2 rest on a trap that does not exist. Pennant Drivers/Decorator::resolve() does: value = value instanceof Lottery ? value() : value - it INVOKES the Lottery and stores the resulting bool. Verified in laravel/pennant source on both 1.x and master. So Feature::value() never returns a Lottery object, and a ternary on Lottery::odds(1,2) gives a real 50/50 split, not everyone green. The source article laravel-feature-flags-pennant.md repeats the same wrong claim (section Rich values, not just on/off), so the article needs the fix too. Note Laravel own Pennant docs use Lottery::odds() as the default arm of Feature::active() - which only works BECAUSE Pennant resolves it. Rest of the post (purge, null scope, dead flags) is accurate.
---

## Lottery::odds(1,2) is truthy. A ternary hands everyone green.

Return a `Lottery` from a flag you branch on with `? :` and the split never
happens. Every user lands on the same arm. Nothing throws.

<!-- slide -->

## Return the value, not the lottery

```php
// Lottery::odds() returns an object: truthy.
Feature::define('button', fn (User $u) =>
    Arr::random(['green', 'blue'])
);
```

`Arr::random` picks one string per user and Pennant stores it. Read it with
`Feature::value()`, not `Feature::active()`.

<!-- slide -->

## Your closure changed. The answer did not.

```php
// Changed the closure? Old values persist.
Feature::purge('billing-v2');
```

Pennant resolves once per scope and reuses the stored value forever. That is
why users stop flickering - and why a deploy without a purge changes nothing.

<!-- slide -->

## No auth user means a null scope

```php
// Queued jobs have no auth user.
Feature::active('x');        // null scope!
Feature::for($team)->active('x'); // correct
```

In a job or a command there is nobody logged in. `Feature::active()` resolves
against null, not against the user you meant.

<!-- slide role="cta" -->

## A flag at 100% for two months is debt

Put the removal ticket in the same PR that adds the flag, and add
`pennant:purge` to the deploy.
