---
slug: laravel-feature-testing-carousel
type: carousel
language: en
title: "Laravel feature testing"
topic: laravel
source_type: article
source: laravel-feature-testing
link: https://oatllo.com/laravel-feature-testing
publish_at: 2026-08-28 19:00
status: ready
formats: [post]
hashtags: [laravel, testing, php, phpunit, pest]
caption: |
  A green 200 can hide a controller that quietly dropped your data.

  assertOk only proves nothing threw. Pair every write test with
  assertDatabaseHas and the test starts earning its keep.

  Full guide linked in bio.

  How many of your tests assert the status and stop?
---

## A green 200 can hide a controller that drops data

A 200 says the controller didn't throw. It says nothing at all about what
actually got saved.

<!-- slide -->

## Assert the row, not just the status

```php
$response->assertCreated();

$this->assertDatabaseHas('articles', [
    'title'   => 'Feature testing in Laravel',
    'user_id' => $user->id,
]);
```

That second assertion is the whole point of a feature test.

<!-- slide -->

## getJson, not get, on an API

```php
$this->get('/api/articles');     // HTML page
$this->getJson('/api/articles'); // 422 JSON
```

The plain methods drive a browser-style request. On an API you get error pages
and redirects, and `assertInvalid` never matches.

<!-- slide -->

## assertJson vs assertJsonFragment

`assertJson` matches from the top of the response down. `assertJsonFragment`
searches anywhere in the tree, which is what a paginated `data` array needs.

<!-- slide -->

## Fake before the request, never after

```php
Mail::fake(); // must run BEFORE the request

$this->actingAs($user)
    ->postJson("/api/articles/{$id}/publish");

Mail::assertSent(ArticlePublished::class);
```

The fake swaps the implementation. Too late and there was nothing to swap.

<!-- slide role="cta" -->

## Start with one endpoint

Post data, assert the response, assert the row. That single test catches more
real regressions than a dozen status-only checks. Full guide linked in bio.
