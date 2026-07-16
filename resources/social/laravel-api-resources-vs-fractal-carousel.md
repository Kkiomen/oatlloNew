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
  what goes out.

  Full comparison linked in bio.

  Resources or Fractal on your last API?
verified:
  verdict: approved
  at: 2026-07-16 07:18
  fingerprint: 34d0c22b050f1cb234f194520c3644555fb43664
  checks:
    - 'the leak premise is the article premise: skip serialization and the contract becomes whatever columns are in the table today, which is how a migration exposes a password reset token'
    - 'JsonResource toArray(Request): array is the real signature, and this->id works because the base class forwards property access to the wrapped model - the article says exactly this and it is true of JsonResource'
    - 'when() and whenLoaded() are real APIs used correctly; whenLoaded the quiet hero and refusing to serialize a relation that was not eager-loaded is the article verbatim'
    - 'Fractal side is accurate: availableIncludes is a real TransformerAbstract property, include=posts.comments nested parsing is Fractal first-class behaviour, and Resources genuinely have no built-in ?include= parsing (article FAQ confirms you write that yourself)'
    - 'the two-questions slide matches the article two cases for Fractal - serialization needed outside Laravel (CLI, Symfony) and include control central to the product'
    - 'CTA is the article performance note verbatim: queries first, caching second, serialization approximately never'
  notes: |
    topic laravel is right. Nothing here ages - no version numbers, and the post wisely leaves behind the article only dated line (its FAQ asks whether Fractal is worth learning in 2026). One hair worth noting: the post says whenLoaded means a resource cannot quietly fire a wall of extra queries, slightly firmer than the article, which adds that Resources make the mistake easy to spot but do not prevent it on their own. Scoped to a field actually guarded by whenLoaded the post claim is true, so not a defect.
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
caching second, serialization approximately never.
