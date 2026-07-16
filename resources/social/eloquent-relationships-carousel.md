---
slug: eloquent-relationships-carousel
type: carousel
language: en
title: "Eloquent pivot conventions"
topic: laravel
source_type: article
source: eloquent-relationships
link: https://oatllo.com/eloquent-relationships
publish_at: 2026-08-17 19:00
status: ready
formats: [post, reel]
hashtags: [laravel, php, eloquent, database, webdev]
caption: |
  Eloquent looks for `role_user`. Your table is called `user_roles`. Nothing throws.

  Pivot names sort alphabetically, not by how you think about the relationship.
  Same story with withPivot: columns you never declared just never appear.

  Full deep dive linked in bio.

  Which convention caught you out first?
---

## role_user works. user_role silently does not.

Eloquent guesses the pivot table name by sorting the two model names
alphabetically. Guess differently and it looks for a table you never made.

<!-- slide -->

## The rule is alphabetical, not logical

```php
// r comes before u. Always.
$this->belongsToMany(Role::class);
// looks for the table: role_user
```

Named it something else? Then say so: `belongsToMany(Role::class, 'user_roles')`.

<!-- slide -->

## Your pivot column is there. It won't show.

```php
$this->belongsToMany(Role::class)
    ->withPivot('granted_by', 'expires_at')
    ->withTimestamps();
```

Undeclared pivot columns never reach `$role->pivot`. No error. The data is
just silently absent.

<!-- slide -->

## One is a Collection. One is a query.

```php
$user->posts->where(...);   // filters in PHP
$user->posts()->where(...); // filters in SQL
```

The property form loads every row into memory first, then filters. On a large
set that means the whole table.

<!-- slide -->

## After attach, the parent is stale

```php
$user->roles()->sync([1, 2, 3]);
$user->roles; // still the old collection
$user->load('roles'); // now it's right
```

The in-memory relation was cached on the first touch. It does not refresh
itself.

<!-- slide role="cta" -->

## The foreign key lives on the belongsTo side

`profiles.user_id` exists, so Profile belongsTo User. Get it backwards and the
SQL hunts a column that isn't there. Full deep dive linked in bio.
