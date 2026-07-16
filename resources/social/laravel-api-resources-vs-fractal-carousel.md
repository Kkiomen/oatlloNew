---
slug: laravel-api-resources-vs-fractal-carousel
type: carousel
language: en
title: "Resources vs Fractal"
topic: laravel
source_type: article
source: laravel-api-resources-vs-fractal
link: https://oatllo.com/laravel-api-resources-vs-fractal
publish_at: 2026-08-31 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, api, json, backend]
caption: |
  `$user->toArray()` is not an API contract. It is a list of whatever columns exist today.

  Add a migration, ship a secret. A serialization layer is where you decide
  what goes out. Full comparison linked in bio.

  Resources or Fractal on your last API?
---

## toArray() forgot one column and your API leaked a reset token.

Skip the serialization layer and your API contract becomes "whatever columns
happen to be in the table today". A migration is all it takes.

<!-- slide -->

## A Resource is a whitelist you can read

```php
public function toArray(Request $r): array
{
    return [
        'id'   => $this->id,
        'name' => $this->name,
    ];
}
```

Nothing you did not name gets out. `$this->id` works because the base class
forwards access to the model underneath.

<!-- slide -->

## whenLoaded is the quiet hero

```php
'email' => $this->when($admin, $this->email),
'posts' => PostResource::collection(
    $this->whenLoaded('posts')
),
```

It refuses to serialize a relation that was not eager-loaded, so a resource
cannot quietly fire a wall of extra queries.

<!-- slide -->

## Fractal earns its keep on includes

```php
protected $availableIncludes = ['posts'];
// GET /users/1?include=posts.comments
```

Opt-in relationship loading, parsed for you, nested transformers included. With
Resources you hand-roll that query parsing yourself.

<!-- slide -->

## Two questions decide it, not speed

Does serialization have to run outside Laravel too - a CLI tool, a Symfony
service? Is `?include=` central to the product? Two nos means Fractal is a
dependency you carry for nothing.

<!-- slide role="cta" -->

## Serialization is never your bottleneck

Queries are. If your JSON endpoints feel slow, look at the database first,
caching second, serialization approximately never. Full comparison in bio.
