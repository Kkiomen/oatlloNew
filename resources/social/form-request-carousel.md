---
slug: form-request-carousel
type: carousel
language: en
title: "Laravel Form Request validation best practices"
topic: laravel
source_type: article
source: laravel-form-request-validation
link: https://oatllo.com/laravel-form-request-validation
publish_at: 2026-08-05 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, validation, cleancode, backend]
caption: |
  Thirty lines of validate() in store(). The same thirty in update(). They have already drifted.

  A form request moves them into one class that both methods share. Laravel
  resolves it, authorizes, and validates before your controller runs at all.

  Full write-up linked in bio.

  How long is your longest controller method, no lying?
verified:
  verdict: issues
  at: 2026-07-16 07:15
  fingerprint: cf0d1e72b88f09687b8e7376835f3d359b6cd123
  notes: |
    The payoff contradicts the source article, and the article is right. Slide 3 and the caption say the fix is One class, shared by store and update - type-hinting StoreArticleRequest in both. The article says the opposite, twice: its Reusing rules section opens with Duplicated rules between StoreArticleRequest and UpdateArticleRequest are where drift creeps in and prescribes TWO thin request classes sharing a trait, and its FAQ (required only on create but optional on update) answers Split them into StoreArticleRequest and UpdateArticleRequest, with update using sometimes for partial PATCH payloads. So the post advises the thing the source specifically routes around, and it is not just a taste difference: a class literally named StoreArticleRequest, whose rules are required, cannot serve a PATCH that sends a partial payload - it would reject every partial update. The carousel opens on store/update drift and closes by prescribing a shape the article treats as the cause of a different bug. Not traceable to the source and a Laravel dev will say so. Verified positively and worth keeping: the authorize() slide is CORRECT and I checked it against reality rather than the article - vendor/laravel/framework/src/Illuminate/Foundation/Console/stubs/request.stub on this repo (Laravel 11.36.1) does return false, so a fresh form request really does reject everything until changed. Also clean: Laravel resolving the class then running authorize() then rules before the controller body, and validated() returning only ruled keys to keep stray input out of mass assignment - both are the article verbatim.
---

## Thirty lines of validate(). Then again in update().

Same rules, two copies. They drifted apart months ago.

<!-- slide -->

## The controller was never the right home

Rules that live in a method cannot be reused by another method. So they get
copy pasted, and then one of them gets fixed.

<!-- slide -->

## Type-hint it and it is gone

```php
public function store(
    StoreArticleRequest $request
) {
    // already valid here
}
```

One class, shared by store and update.

<!-- slide -->

## It runs before your code does

Laravel resolves the class, runs `authorize()`, then runs the rules. Your
controller body never executes on a bad request.

<!-- slide -->

## The gotcha that stops everyone once

```php
public function authorize(): bool
{
    return false; // the default!
}
```

A fresh form request rejects every request until you change this.

<!-- slide role="cta" -->

## Take the validated data, not the request

`$request->validated()` gives you only the fields that passed rules. No stray
input sneaking into a mass assignment.
