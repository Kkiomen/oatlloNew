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
