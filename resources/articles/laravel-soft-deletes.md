---
name: "Laravel Soft Deletes: Pitfalls and Best Practices"
slug: laravel-soft-deletes
short_description: "A practical guide to Laravel soft deletes: the unique constraint trap, relationships, pruning old rows, and when not to use them."
language: en
published_at: 2027-02-03 09:00:00
is_published: true
tags: [laravel, eloquent, database]
---

Laravel soft deletes look like a free undo button. You add a trait, run a migration, and suddenly `delete()` stops throwing rows away. It just hides them. The first time a support ticket comes in asking "can you get my account back?", you feel like a genius.

Then a user tries to re-register with an email they deleted last month, and your app blows up with a duplicate key error. That is the moment most people realise soft deletes are not a checkbox. They are a design decision with edges.

This post walks through how soft deletes actually work, the traps that catch people (the unique constraint one bites almost everyone), how they interact with relationships, and the part nobody wants to talk about: when you should just delete the row for real.

## What soft deletes actually do

A soft delete never removes the row. Instead, Eloquent stamps a `deleted_at` timestamp on it. A global query scope then quietly filters out any row where `deleted_at` is not null, so from your application's point of view the record is gone.

To turn it on you need two things. First, the migration column:

```php
Schema::table('articles', function (Blueprint $table) {
    $table->softDeletes(); // adds a nullable deleted_at timestamp
});
```

Second, the trait on the model:

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;
}
```

That is the whole setup. `$table->softDeletes()` creates the nullable `deleted_at` column, and the `SoftDeletes` trait wires up the behaviour and the global scope.

### The methods you get

Once the trait is in place, the vocabulary changes a little:

- `delete()` sets `deleted_at` to the current time. The row stays in the table.
- `forceDelete()` runs a real `DELETE` statement. The row is gone for good.
- `restore()` sets `deleted_at` back to null, bringing the record back into normal queries.
- `trashed()` returns true if the model instance has been soft deleted.

And for querying around the global scope:

- `withTrashed()` includes both live and soft-deleted rows.
- `onlyTrashed()` returns only the soft-deleted rows.

A quick tour:

```php
$article = Article::find(1);

$article->delete();        // deleted_at is now set
$article->trashed();       // true

Article::count();                       // excludes trashed rows
Article::withTrashed()->count();        // counts everything
Article::onlyTrashed()->get();          // just the trash

$article->restore();       // deleted_at back to null
$article->forceDelete();   // actually removed from the database
```

If you have ever run a query in tinker and been confused why a row you "deleted" is missing from a raw count but shows up in the database GUI, this scope is why. The data is there. Eloquent is just hiding it from you by default.

## The big gotcha: unique constraints

Here is the one that ships to production and then explodes.

Say your `users` table has a unique index on `email`. A user signs up as `jane@example.com`, later deletes their account, and the row is soft deleted. The `deleted_at` column is set, but **the row is still physically in the table**. The unique index does not know or care about your global scope. It only sees rows.

So when Jane comes back and tries to register with the same email, your insert hits the unique index, finds the old soft-deleted row already sitting on `jane@example.com`, and rejects it with a duplicate key violation. Eloquent's scope hid the row from your `SELECT`, but the database engine enforcing the constraint is looking at raw storage.

This surprises people because the app genuinely believes that email is free. `User::where('email', 'jane@example.com')->exists()` returns false. The insert still fails.

There are two sane fixes.

**Option 1: a composite unique index that includes `deleted_at`.**

```php
$table->unique(['email', 'deleted_at']);
```

This works because a soft-deleted row keeps its `deleted_at` timestamp while a fresh row has null, so the two no longer share the same `(email, deleted_at)` pair and the resurrection conflict goes away. Jane can register again.

But be honest about what this does and does not guarantee. In MySQL and PostgreSQL, `NULL` values are treated as distinct inside a unique index, which means two *live* rows both with `deleted_at = NULL` do **not** collide. The composite index reliably solves the re-registration problem, but on its own it does not stop two active rows from sharing an email. In most apps your application logic already prevents that second live row, so this is usually fine, but it is the reason the partial index below is the stronger guarantee where you can use it.

**Option 2: a partial (filtered) unique index.**

On PostgreSQL you can express the intent directly and enforce uniqueness only on live rows:

```sql
CREATE UNIQUE INDEX users_email_active_unique
ON users (email)
WHERE deleted_at IS NULL;
```

This is the cleaner solution when your database supports it, because the index says exactly what you mean: emails are unique among rows that have not been deleted. MySQL did not support partial indexes for a long time, so the composite-column approach is the more portable option there.

If you want to go deeper on how these indexes behave under the hood, we covered the mechanics in [database indexing explained](/blog/database-indexing-explained).

## Soft deletes and relationships

The second thing people assume incorrectly: that soft deleting a parent cascades to its children. It does not.

If you soft delete a `Post`, its `Comment` rows are untouched. There is no automatic soft-delete cascade the way a database foreign key can cascade a hard `DELETE`. This is a feature as much as a limitation, since Eloquent has no way to know whether you want the comments hidden too, but it means orphaned-looking data is easy to create by accident.

If you want children to follow the parent, you handle it yourself. A model event is the usual place:

```php
class Post extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleting(function (Post $post) {
            if (! $post->isForceDeleting()) {
                $post->comments()->each(fn ($comment) => $comment->delete());
            }
        });
    }
}
```

Note the `isForceDeleting()` check, because you probably want different behaviour when the parent is being permanently removed versus soft deleted. This kind of side-effect-on-model-lifecycle logic is exactly what observers are built for; if this block grows, move it out of the model. We compared the approaches in [Laravel observers vs events](/blog/laravel-observers-vs-events).

One more querying wrinkle. When you load a relationship, the related model's own global scope applies. So `$post->comments` will exclude soft-deleted comments if `Comment` uses the trait. To include them you reach for `withTrashed()` on the relationship query, and constraints like eager loading need the same treatment. If your relationships are getting complicated, the fundamentals in [Eloquent relationships](/blog/eloquent-relationships) are worth a refresher.

## Pruning: soft deletes are not forever

A table that only ever accumulates rows is a table that eventually hurts. Soft deletes are storage. If you keep soft-deleting rows and never clean up, your indexes grow, your `withTrashed()` queries slow down, and your backups bloat with data nobody will ever restore.

Laravel gives you `Prunable` for this. Add the trait and define what "old enough to really delete" means:

```php
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes, Prunable;

    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subMonth());
    }
}
```

Then schedule `model:prune` in your console kernel and it will `forceDelete` anything the `prunable()` query matches. For soft-deleted models, pruning actually removes them from the table, which is the whole point.

If you need to run cleanup or free resources when a row is finally pruned, override `pruning()` on the model. It is handy for deleting associated files from storage before the row disappears.

## When you should NOT use soft deletes

This is the section I wish someone had shown me earlier, because I over-applied soft deletes for about a year before I felt the cost.

**Soft deletes are not an audit log.** They tell you *that* a row was deleted and roughly when, but nothing about who did it, why, or what the row looked like across its edits. If you need real accountability, use a proper audit/versioning package or an events table. Bolting an audit trail onto `deleted_at` is a trap. You will keep adding columns until you have reinvented event sourcing badly.

**GDPR and "right to be forgotten" cut the other way.** When a user asks to be deleted under GDPR, a soft delete does not satisfy that request. The personal data is still sitting in your table, fully intact, just hidden from your app. If anything, soft deletes make compliance harder because the data lingers. For those flows you often want a genuine `forceDelete`, or anonymisation of the personal fields, not a `deleted_at` stamp.

**High-churn tables get expensive.** A table with millions of soft-deleted rows carries dead weight in every index. If deletion is genuinely permanent from a business perspective, a real delete (or an archive-then-delete pattern) keeps the hot table small.

The honest heuristic: use soft deletes when "undo" is a real product feature or when downstream data would break without the parent around. Reach for real deletion when gone means gone.

## Common pitfalls checklist

- **Unique constraints ignore the global scope.** Soft-deleted rows still occupy their unique values. Use a composite index with `deleted_at` or a partial index.
- **No automatic cascade.** Soft deleting a parent leaves children live unless you handle it in a `deleting` hook or observer.
- **Raw queries bypass the scope.** `DB::table('users')->get()` and raw SQL see soft-deleted rows. The global scope is Eloquent-only.
- **`firstOrCreate` can resurrect trash.** It queries through the scope, misses the soft-deleted row, then tries to insert, running straight into the unique constraint problem.
- **Forgetting to prune.** Trash accumulates silently until a query gets slow. Schedule pruning early.
- **Assuming soft delete equals privacy.** The data is still there. For deletion-of-personal-data requirements, it is not enough.

## FAQ

### Does `delete()` remove the row when a model uses SoftDeletes?

No. With the `SoftDeletes` trait, `delete()` sets the `deleted_at` timestamp and leaves the row in place. Use `forceDelete()` to run an actual SQL `DELETE` and remove it permanently.

### How do I query soft-deleted records?

Use `withTrashed()` to include both live and trashed rows, or `onlyTrashed()` to fetch just the deleted ones. Both temporarily lift the global scope that otherwise hides trashed rows. On a loaded instance, `trashed()` tells you whether that specific record is soft deleted.

### Why does creating a record fail after I soft deleted one with the same unique value?

Because the soft-deleted row is still physically in the table and still occupies the unique index value. The database enforces the constraint against real storage, not your Eloquent scope. Fix it with a composite unique index including `deleted_at`, or a partial unique index that only applies where `deleted_at IS NULL`.

### Do soft deletes work with GDPR "right to be forgotten" requests?

Not on their own. A soft delete keeps the personal data intact and merely hides it. To satisfy an erasure request you generally need a genuine `forceDelete` or to anonymise the personal fields, so the data no longer exists in a recoverable, identifiable form.

## Wrapping up

Soft deletes are a good tool aimed at a specific problem: making deletion recoverable. The trait plus a `deleted_at` column gives you that in a few minutes, and `withTrashed()`, `onlyTrashed()`, `restore()`, and `forceDelete()` cover the day-to-day.

The trouble starts when you treat them as automatic. They do not cascade, they do not respect your unique indexes for free, they do not clean up after themselves, and they do not make data private. Handle the unique constraint with a composite or partial index, cascade to children deliberately, schedule pruning, and be clear-eyed about the cases — audit trails and GDPR erasure among them — where a real delete is the correct answer. Get those four right and soft deletes stay the helpful undo button they were meant to be, instead of the thing that pages you at 2am.