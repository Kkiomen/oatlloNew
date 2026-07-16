---
slug: laravel-model-factories-carousel
type: carousel
language: en
title: "Model factories"
topic: laravel
source_type: article
source: laravel-model-factories
link: https://oatllo.com/laravel-model-factories
publish_at: 2026-09-11 19:00
status: ready
formats: [post]
hashtags: [laravel, testing, php, eloquent, database]
caption: |
  Stop hand-writing `User::create([...])` with fifteen fields spelled out in every test.

  Describe the model once, ask for as many rows as you need. The same factory
  then seeds your dev database.

  Full guide linked in bio.

  Which factory trick took you longest to find?
---

## Faker's unique() throws OverflowException on big batches.

It remembers every value it hands out. Generate tens of thousands of rows and
the pool drains, then it gives up. Widen the source or seed in chunks.

<!-- slide -->

## make() has no id. That is the point.

```php
Post::factory()->make();   // memory only
Post::factory()->create(); // has an id
```

`make()` never touches the database, so no id, no timestamps, nothing the
database assigns. Use it for pure attribute assertions.

<!-- slide -->

## Repeating an override? That is a state.

```php
public function published(): static
{
    return $this->state(fn ($a) => [
        'published_at' => now(),
    ]);
}
```

Now the intent reads straight off the call: `Post::factory()->published()`.
Name states after the concept, never the raw column.

<!-- slide -->

## Relationships in one line each

```php
User::factory()
    ->has(Post::factory()->count(3))
    ->create();
```

`has()` builds children under a parent, `for()` goes the other way. Laravel also
generates `hasPosts(3)` and `forUser()` for you.

<!-- slide -->

## A batch of clones is rarely a good test

```php
->count(6)
->state(new Sequence(
    ['published_at' => now()],
    ['published_at' => null],
))
```

Three published, three drafts, alternating. Pass a closure if you need
`$sequence->index`.

<!-- slide role="cta" -->

## If a callback never runs, this is why

`afterMaking` fires for `make()`. `afterCreating` fires only after `create()`.
That mismatch is almost always the answer.
