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
verified:
  verdict: issues
  at: 2026-07-16 07:12
  fingerprint: c804b36215dc7ec0180ff2ad4ca53264767a34ea
  notes: |
    The hook is false, and it is false in the exact way the source article avoids. Slide 1 says role_user works. user_role silently does not, and the caption says Eloquent looks for role_user. Your table is called user_roles. Nothing throws. A wrong pivot table name does NOT fail silently - Eloquent builds a query against a table that does not exist and MySQL throws SQLSTATE 42S02 Base table or view not found. It fails loudly on the first access. The article is careful here and the post is not: its pitfalls list gives no error thrown / silently absent ONLY to the withPivot bullet (line 260), while the non-alphabetical pivot name bullet (line 261) just says it will not be found. The post has borrowed the silence from the withPivot pitfall and attached it to the naming pitfall - two different failure modes welded into one. Slide 3 uses silent correctly, which is what makes the hook look plausible. Fix: the naming bug is a loud crash, sell it as that. Everything else traces clean - alphabetical convention and role_user, the belongsToMany(Role::class, user_roles) override, withPivot(granted_by, expires_at) + withTimestamps, property-vs-method filtering in PHP vs SQL, the stale relation after sync needing load(), and the foreign key living on the belongsTo side with profiles.user_id. Minor and separate: the caption says user_roles where slide 1 says user_role. Both appear in the article but they are different examples.
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
SQL hunts a column that isn't there.
