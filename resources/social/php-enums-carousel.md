---
slug: php-enums-carousel
type: carousel
language: en
title: "PHP Enums: a complete guide"
topic: php
source_type: article
source: php-enums-complete-guide
link: https://oatllo.com/php-enums-complete-guide
publish_at: 2026-08-11 19:00
status: ready
hashtags: [php, php8, laravel, cleancode, backend]
caption: |
  'pending', 'Pending', 'pendign'. Your status column took all three.

  A backed enum turns the set of valid values into a type. The typo stops being
  a row in your database that surfaces a month later in a report, and starts
  being an error at the boundary where it came in.

  Full write-up linked in bio.

  enum or string for status columns, and what talked you into it?
---

## Your status column takes any string you can typo

'pending', 'Pending', 'pendign'. The database accepted all three.

<!-- slide -->

## The typo is not the problem

It passes validation, passes the ORM, lands in the database. You find it a
month later, in a report that quietly counts wrong.

<!-- slide -->

## Make the valid set a type

```php
enum Status: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
```

Three values exist. Nothing else can.

<!-- slide -->

## Two doors in, and they differ

```php
Status::from('nope');    // ValueError
Status::tryFrom('nope'); // null
```

`from()` throws for data that should never be wrong. `tryFrom()` is for input
you do not trust.

<!-- slide -->

## Eloquent speaks it natively

```php
protected $casts = [
    'status' => Status::class,
];
```

The database stores "archived". Your code gets `Status::Archived`.

<!-- slide role="cta" -->

## What you stop writing

The `in_array` guard. The manual cast in the controller. The constant class
nobody kept in sync with the column.
