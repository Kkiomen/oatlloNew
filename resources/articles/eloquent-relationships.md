---
name: "Eloquent Relationships: A Complete Deep Dive"
slug: eloquent-relationships
short_description: "A practical guide to Eloquent relationships in Laravel: every relationship type, foreign key conventions, pivot data, eager loading, and the pitfalls."
language: en
published_at: 2027-01-04 09:00:00
is_published: true
tags: [laravel, eloquent, php, database]
---

If you've built anything non-trivial in Laravel, you've leaned on **Eloquent relationships** whether you thought about them or not. A user has posts, a post has comments, an order belongs to a customer. Wiring those associations up correctly is the difference between code that reads like plain English and code that fights you every time the schema shifts. This deep dive walks through every relationship type Eloquent gives you, the conventions behind them, and the mistakes I keep seeing in code review.

I'll assume you know your way around a migration and a model. Everything below is runnable; swap in your own table and column names and it works.

## How Eloquent relationships actually work

One distinction trips people up early, so let's clear it out of the way first.

A relationship is defined as a **method** on your model that returns a relationship object. But you usually *access* it as a **property**. Those are not the same thing, and the difference matters:

```php
$user->posts;    // property: runs the query, returns a Collection
$user->posts();  // method: returns a HasMany builder you can keep querying
```

The property form is a dynamic accessor. The first time you touch it, Eloquent runs the query and caches the result on the model. The method form hands you back the query builder, so you can constrain it further:

```php
// Only this user's published posts, newest first
$posts = $user->posts()
    ->where('status', 'published')
    ->latest()
    ->get();
```

Reach for the method form whenever you need filtering, ordering, or pagination. Reach for the property when you just want the related records as they are.

## One-to-one: hasOne and belongsTo

The simplest pairing. A user has one profile; a profile belongs to a user.

```php
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
}

class Profile extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

The key question is always: **which table holds the foreign key?** The `profiles` table has a `user_id` column, so `Profile` is the one that `belongsTo` `User`, and `User` `hasOne` `Profile`. The foreign key lives on the "belongs to" side. Get that backwards and nothing lines up.

By convention Eloquent guesses the foreign key from the parent model name plus `_id`, so `user_id`. If your column is named differently, spell it out:

```php
return $this->hasOne(Profile::class, 'owner_id', 'id');
// hasOne(Related, foreignKey on related table, localKey on this table)
return $this->belongsTo(User::class, 'owner_id', 'id');
// belongsTo(Related, foreignKey on this table, ownerKey on related table)
```

Watch the argument order there. On `hasOne` the second argument is the foreign key on the *related* table. On `belongsTo` it's the foreign key on *this* table. They read almost the same and mean opposite things.

## One-to-many: hasMany

A post has many comments. Same shape as `hasOne`, just plural on the return.

```php
class Post extends Model
{
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

class Comment extends Model
{
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
```

The `comments` table carries `post_id`. Nothing new to learn here beyond the fact that you get a `Collection` back instead of a single model.

One handy trick people miss: you can create through the relationship and the foreign key gets set for you.

```php
$post->comments()->create([
    'body' => 'First!',
]);
// post_id is filled automatically, no need to pass it
```

## Many-to-many: belongsToMany and the pivot table

This is where people start guessing, and guessing goes badly. A user has many roles, and a role belongs to many users. There's no foreign key that can live on either table, so you need a third table in between. That's the **pivot table**.

By convention Laravel expects the pivot named after the two models, singular, alphabetical, joined by an underscore: `role_user` (not `user_role`, because `r` comes before `u`). It holds `role_id` and `user_id`.

```php
class User extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}

class Role extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
```

If your pivot table doesn't follow the naming convention, pass it explicitly: `belongsToMany(Role::class, 'user_roles')`.

### Extra columns on the pivot

Pivot tables often carry more than two foreign keys. Say each role assignment records who granted it and when. You need to tell Eloquent about those columns or they stay invisible:

```php
return $this->belongsToMany(Role::class)
    ->withPivot('granted_by', 'expires_at')
    ->withTimestamps();
```

`withTimestamps()` tells Eloquent to maintain `created_at` and `updated_at` on the pivot rows. Once declared, the extra data is reachable via the `pivot` attribute on each related model:

```php
foreach ($user->roles as $role) {
    echo $role->pivot->granted_by;
    echo $role->pivot->expires_at;
}
```

### Attaching, detaching, syncing

Managing pivot rows has its own vocabulary, and each verb does something specific:

- **`attach($id)`** adds a pivot row. Pass extra pivot data as a second array: `attach($roleId, ['granted_by' => auth()->id()])`.
- **`detach($id)`** removes the pivot row for that id. Call it with no argument and it removes *all* of them.
- **`sync([1, 2, 3])`** makes the pivot match exactly that list, attaching what's missing and detaching what's not in the array. Use `syncWithoutDetaching()` if you only want to add.
- **`toggle([1, 2])`** flips each id: present ones get detached, absent ones get attached.

```php
$user->roles()->attach($editorRoleId);
$user->roles()->sync([1, 2, 3]);   // now has exactly roles 1, 2, 3
$user->roles()->toggle([2, 5]);    // 2 removed (was present), 5 added
```

`sync` is the one I reach for on a form that submits a full set of checkboxes. It does the diffing so you don't.

## Has-many-through and has-one-through

Sometimes the relationship you want lives one table away. A country has many users, and each user writes posts. You want a country's posts without manually joining through users:

```php
class Country extends Model
{
    public function posts()
    {
        return $this->hasManyThrough(Post::class, User::class);
    }
}
```

Eloquent walks `countries` → `users` (via `country_id` on `users`) → `posts` (via `user_id` on `posts`). `hasOneThrough` is the same idea when the far end is a single record. These save you a raw join, but they're also easy to over-reach with. If you find yourself chaining three throughs, a dedicated query might read clearer.

## Polymorphic relationships

Here's the pattern that unlocks a lot of real designs. Suppose both posts and videos can have comments. You don't want a `post_comments` table and a `video_comments` table. You want one `comments` table that can point at either.

Polymorphic relationships store two columns: an id and a *type*. The migration looks like this:

```php
$table->morphs('commentable');
// creates commentable_id and commentable_type
```

The models:

```php
class Comment extends Model
{
    public function commentable()
    {
        return $this->morphTo();
    }
}

class Post extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

class Video extends Model
{
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
```

`morphTo()` is the polymorphic cousin of `belongsTo`. Eloquent reads `commentable_type` (which stores the model class) to know what to return. Use `morphOne` when the parent has a single related record instead of many.

There's also **`morphToMany`** / **`morphedByMany`** for the many-to-many case. Think tags that attach to posts, videos, and users through a single `taggables` pivot. Same mechanics as `belongsToMany`, plus the type column.

## Querying relationships without N+1 pain

Defining relationships is half the job. Querying them efficiently is the other half, and this is where performance goes to die.

**Eager loading** with `with()` loads a relationship up front in a second query instead of one query per row:

```php
$posts = Post::with('author')->get();

foreach ($posts as $post) {
    echo $post->author->name; // no extra query
}
```

Without `with('author')`, that loop fires one query per post: the classic N+1 problem that quietly wrecks a page as your data grows. It's the most common Eloquent performance bug there is, and it deserves its own read: see [the Eloquent N+1 query problem](/blog/eloquent-n1-query-problem) for how to detect and kill it.

Two more tools you'll use constantly:

- **`withCount('comments')`** adds a `comments_count` attribute without loading the actual comments. Perfect for "42 comments" labels.
- **`whereHas('comments', fn ($q) => $q->where('approved', true))`** filters parents by a condition on their children. Use `has('comments')` when you just need "has at least one."

```php
$activePosts = Post::withCount('comments')
    ->whereHas('comments', fn ($q) => $q->where('approved', true))
    ->get();
```

If the same relationship results get hit on every request and rarely change, layering a cache on top pays off, and [caching Eloquent queries](/blog/laravel-cache-queries) covers the patterns. And when `whereHas` gets slow, the fix is usually [an index on the foreign key](/blog/database-indexing-explained), not more Eloquent.

## Pitfalls I keep running into

- **Foreign key on the wrong side.** `belongsTo` goes on the table that *holds* the foreign key. If you put `hasOne`/`hasMany` where `belongsTo` belongs, the generated SQL looks for a column that isn't there.
- **Calling the property when you meant the method.** `$user->posts->where(...)` filters an already-loaded Collection in PHP; `$user->posts()->where(...)` filters in SQL. On large sets the first one loads everything into memory first.
- **Forgetting `withPivot`.** Extra pivot columns simply won't appear on `$model->pivot` until you declare them. There's no error thrown; the data is just silently absent.
- **Non-alphabetical pivot names.** `user_role` won't be found; Eloquent looks for `role_user`. Either rename the table or pass the name explicitly.
- **Mutating pivots and expecting the parent to refresh.** After `attach`/`sync`, the in-memory relationship is stale. Call `$user->load('roles')` or `$user->refresh()` if you need the updated collection in the same request.
- **N+1 hiding in Blade.** A `@foreach` that touches `$post->author` renders fine and passes tests on ten rows. It's production with ten thousand rows that exposes it.

## FAQ

### What's the difference between `with`, `has`, and `whereHas`?

`with` eager-loads a relationship so you can access it without extra queries. `has` filters parents down to those that have at least one related record. `whereHas` is `has` with a condition on the children. They solve different problems: `with` is about performance, while `has`/`whereHas` are about filtering.

### When should I use a polymorphic relationship instead of separate tables?

When the *same* kind of child (comments, tags, images, likes) needs to attach to several unrelated parent types. If only one parent type will ever own the child, a plain `hasMany` is simpler and keeps your foreign keys enforceable at the database level, which polymorphic columns can't be.

### Why is `$model->pivot` empty even though the pivot row exists?

Almost always a missing `withPivot()`. Eloquent only hydrates the pivot columns you explicitly name (plus the two foreign keys). Add the column to `withPivot(...)` and it appears.

### Does accessing a relationship always hit the database?

Only the first time. Eloquent caches the loaded relation on the model instance, so a second `$user->posts` in the same request reuses it. Force a fresh query with `$user->load('posts')` or by using the method form `$user->posts()->get()`.

## Wrapping up

Eloquent relationships come down to a small set of decisions: which side holds the foreign key, whether you want one record or many, and whether a third table sits in between. Get those right and the rest of the API — `attach`, `sync`, `with`, `whereHas` — falls into place naturally.

My advice: define both sides of every relationship even when you only think you need one direction. It costs three lines and saves you from writing a raw query the day you need the inverse. And keep one eye on your query count from the start, because the difference between `Post::all()` and `Post::with('author')->get()` is invisible in development and brutal in production.