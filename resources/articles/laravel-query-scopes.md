---
name: "Local and Global Query Scopes in Laravel"
slug: laravel-query-scopes
short_description: "How local and global scopes work in Laravel, the Laravel 11 Scope attribute, removing global scopes, multi-tenancy, and the invisible-filter trap."
language: en
published_at: 2027-05-10 09:00:00
is_published: true
tags: [laravel, php, eloquent, database]
---

I lost most of an afternoon once to a query that returned the wrong count. `Order::count()` said 1,240. The database said 3,900. Nothing in the controller filtered anything, nothing in the repository did either. The filter was three files away, in a global scope someone had added to the model months earlier, and it applied to every query silently. That afternoon is the reason this article spends as much time on how scopes *hide* as on how they work.

Scopes are Eloquent's way of naming a reusable query constraint so you write `Article::published()` instead of copying `where('is_published', true)->where('published_at', '<=', now())` into fifteen places. There are two kinds, and the difference is entirely about who opts in. Local scopes are opt-in per query. Global scopes are opt-out, applied to every query on the model whether you asked for them or not. That single distinction decides everything else.

## Local scopes: constraints you call by name

A local scope is a method on your model prefixed with `scope`. You call it without the prefix and without the parentheses when there are no arguments.

```php
class Article extends Model
{
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                     ->where('published_at', '<=', now());
    }
}
```

```php
Article::published()->latest()->get();
```

Eloquent strips the `scope` prefix, lowercases the first letter, and hands the query builder in as the first argument. The method returns the builder, so scopes chain with everything else — `where`, `orderBy`, other scopes. That chaining is the whole point. `Article::published()->where('category_id', 4)->get()` reads like a sentence, and the definition of "published" lives in exactly one place.

Scopes take parameters too. Everything after the query argument is what you pass at the call site.

```php
public function scopeOfType($query, string $type)
{
    return $query->where('type', $type);
}
```

```php
Article::published()->ofType('tutorial')->get();
```

The convention I follow: a scope is a `where`-shaped thing, not a full query. It should narrow results and return the builder. Don't call `->get()` or `->first()` inside a scope — that turns a composable fragment into a dead end, and the next person who wants to add an `orderBy` after it can't.

### The Laravel 11 `#[Scope]` attribute

Laravel 11 added an alternative that drops the `scope` prefix in favor of a PHP attribute. The method name becomes the scope name directly.

```php
use Illuminate\Database\Eloquent\Attributes\Scope;

class Article extends Model
{
    #[Scope]
    protected function published($query)
    {
        return $query->where('is_published', true)
                     ->where('published_at', '<=', now());
    }
}
```

You still call it as `Article::published()`. The behavior is identical; this is purely about readability. The old `scopePublished` naming isn't deprecated and won't be — you'll see it in every codebase written before 2024, and mixing both styles in one project works fine. Pick one per model so a reader isn't hunting for two conventions. I lean toward the attribute on new code because `published` reads better than `scopePublished` at the definition, and the magic-prefix thing always confused people new to Eloquent.

## Global scopes: the constraint you don't call

A global scope applies to every query on a model automatically. You never call it. The moment you attach one, `Model::all()`, `Model::find()`, relationship loads, everything — they all carry the constraint.

There are two ways to write one. The class-based form implements `Illuminate\Database\Eloquent\Scope`:

```php
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PublishedScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('is_published', true);
    }
}
```

You attach it in the model's `booted` method:

```php
class Article extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new PublishedScope);
    }
}
```

For something short, the closure form skips the class entirely. Laravel 11 gives it a cleaner attribute-based registration too, but the closure inside `booted` is the version you'll meet most often:

```php
protected static function booted(): void
{
    static::addGlobalScope('published', function (Builder $builder) {
        $builder->where('is_published', true);
    });
}
```

The string key (`'published'`) matters — it's the handle you'll use later to remove the scope for a specific query. Anonymous closures without a key can't be selectively removed, so name them.

## You already use a global scope: SoftDeletes

If the concept feels abstract, you've been relying on it for years. The `SoftDeletes` trait is a global scope. When you add it to a model, `SoftDeletingScope` gets attached, and it appends `where deleted_at is null` to every query. That's why a soft-deleted row vanishes from `Article::all()` without you writing any filter — and why `withTrashed()` exists to switch it off. `withTrashed()`, `onlyTrashed()`, and `restore()` are all just methods for manipulating that one global scope.

This is worth internalizing because it tells you exactly what the mechanism costs and what it buys. It buys invisibility: every query is correct by default and nobody can forget the filter. It costs discoverability: the reason your query is filtered is nowhere near the query.

## Removing a global scope for one query

Global scopes are opt-out via `withoutGlobalScope`. You pass either the class name or the string key.

```php
// Class-based scope
Article::withoutGlobalScope(PublishedScope::class)->get();

// Named closure scope
Article::withoutGlobalScope('published')->get();

// Drop every global scope on the model
Article::withoutGlobalScopes()->get();
```

`withoutGlobalScope` peels off exactly one constraint and leaves the rest. `withoutGlobalScopes()` (plural) removes all of them — reach for it in an admin panel or a data-export job where you genuinely want the raw table. In application code, prefer the single-scope version. Blanket-removing every scope in a tenant-scoped app is how you leak one customer's data into another's report.

## Where global scopes actually earn their keep: multi-tenancy

The textbook use case, and the one where the trade-off is worth it, is multi-tenancy. Every row belongs to a tenant (an account, an organization, a workspace), and no query should ever cross that boundary. Enforcing it with a `where('tenant_id', ...)` in every controller is a leak waiting to happen — miss it once and a customer sees another customer's data.

A global scope makes the boundary structural instead of remembered:

```php
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($tenantId = auth()->user()?->tenant_id) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}
```

Now `Invoice::all()`, `Invoice::find($id)`, an eager-loaded `$customer->invoices` — all of them are tenant-bound whether the developer remembered or not. The security property no longer depends on discipline.

Two things bit me here. First, qualify the column with the table name (`$model->getTable() . '.tenant_id'`). Without it, a join between two tenant-scoped tables throws `SQLSTATE[23000]: Integrity constraint violation: 1052 Column 'tenant_id' in where clause is ambiguous`. Second, guard the null case. If the scope runs during a console command or a queued job where there's no authenticated user, an unguarded scope either filters on `null` and returns nothing, or crashes. Decide deliberately what "no tenant" means — usually you want the queue job to set the tenant context explicitly rather than rely on `auth()`.

## The gotcha: "why is this query filtered and I can't see why"

Here's the failure mode that cost me the afternoon. A global scope is action at a distance. The query is in your controller; the constraint is in a trait, a `booted` method, or a scope class you've never opened. When results are wrong, nothing at the call site explains it.

A few habits keep this survivable:

- **Dump the SQL, don't trust the code you can see.** `Article::toRawSql()` (Laravel 10.15+) or the older `->toSql()` shows the actual `where` clauses including every global scope. When the count is wrong, this is the first move, not the last. The phantom `where` clause will be sitting right there.
- **Keep global scopes rare and boring.** Tenancy, soft deletes, maybe an `active` flag on a config table. If you find yourself adding business logic to a global scope — date math, conditional joins, anything a reader would need to think about — it probably wants to be a local scope you call explicitly.
- **Name your closure scopes.** An unnamed closure can't be removed and doesn't show up usefully when you're trying to figure out what's attached. The string key is documentation.

The rule I settled on: a global scope is acceptable only when *forgetting it would be a bug*. Tenant isolation qualifies — forgetting is a data leak. "Only show published articles on the public site" does not, because plenty of legitimate queries (the admin list, the sitemap generator, a report) want unpublished rows, and now every one of them has to remember `withoutGlobalScope`. That's the opt-out tax, paid on every query that's an exception. When exceptions are common, use a local scope and pay nothing.

## Testing scopes

Scopes are query logic, so test them against a real database — SQLite in memory is fine and fast. A local scope test asserts that the right rows come back and the wrong ones don't:

```php
public function test_published_scope_excludes_future_and_draft_articles(): void
{
    $live   = Article::factory()->create(['is_published' => true,  'published_at' => now()->subDay()]);
    $future = Article::factory()->create(['is_published' => true,  'published_at' => now()->addWeek()]);
    $draft  = Article::factory()->create(['is_published' => false, 'published_at' => now()->subDay()]);

    $results = Article::published()->pluck('id');

    $this->assertTrue($results->contains($live->id));
    $this->assertFalse($results->contains($future->id));
    $this->assertFalse($results->contains($draft->id));
}
```

The `$future` and `$draft` rows are the point. Anyone can assert the happy path returns the published row; the test that catches regressions is the one that proves the excluded rows stay excluded.

For a global scope, the assertion I care about most is that the constraint is impossible to escape by accident. Create data for two tenants, authenticate as one, and assert a bare `all()` never sees the other:

```php
public function test_tenant_scope_hides_other_tenants_rows(): void
{
    $mine   = Invoice::factory()->for($this->tenant)->create();
    $theirs = Invoice::factory()->for(Tenant::factory()->create())->create();

    $this->actingAs($this->user); // belongs to $this->tenant

    $ids = Invoice::all()->pluck('id');

    $this->assertTrue($ids->contains($mine->id));
    $this->assertFalse($ids->contains($theirs->id));
}
```

That test is worth more than most in the suite, because the thing it protects — one customer never seeing another's data — is the kind of bug you find out about from an angry email rather than a stack trace.

## FAQ

**What's the difference between a local and a global scope in Laravel?**
A local scope is opt-in: you call it explicitly (`Article::published()`) and it only affects that query. A global scope is opt-out: once attached to a model it applies to every query automatically, and you have to call `withoutGlobalScope()` to exclude it. Use local scopes for reusable filters, global scopes for constraints that would be a bug to forget (like tenant isolation or soft deletes).

**Do I have to use the `scope` prefix on Laravel 11?**
No. Laravel 11 added the `#[Scope]` attribute, so you can name the method `published` and mark it with `#[Scope]` instead of naming it `scopePublished`. Both work and both are called the same way (`Article::published()`). The prefix style isn't going away, so existing code keeps working untouched.

**Why is my Eloquent query returning fewer rows than the table has?**
A global scope is almost always the cause — most often `SoftDeletes` filtering out `deleted_at` rows, or a tenant scope. Run `Model::toRawSql()` to see the actual SQL with every scope applied; the extra `where` clause will be visible. Use `withTrashed()` for soft deletes or `withoutGlobalScope(TheScope::class)` to bypass a specific one.

**Can I remove all global scopes at once?**
Yes, `Model::withoutGlobalScopes()` drops every global scope for that query. Be careful in tenant-scoped applications — removing all scopes can expose other tenants' data. Prefer `withoutGlobalScope(SpecificScope::class)` so you only lift the one you mean to.

## Where this leaves you

Reach for a local scope by default. It's a named `where`, it composes, and it never surprises anyone because they typed its name. Promote a constraint to a global scope only when forgetting it would be an actual bug — tenant isolation, soft deletes, the short list of things that must always be true. And the first time a query returns a number you can't explain, dump the raw SQL before you touch anything else. The filter you can't find is usually a global scope you forgot was there.
