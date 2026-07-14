---
name: "Zero-Downtime Database Migrations in Laravel"
slug: zero-downtime-migration-laravel
short_description: "Ship schema changes without a maintenance page. A practical expand/contract guide to zero downtime migration Laravel teams can actually run in prod."
language: en
published_at: 2026-12-02 09:00:00
is_published: true
tags: [laravel, database, migrations, mysql, devops]
---

The first time a `php artisan migrate` locked our orders table for 40 seconds during a Friday deploy, I learned what a zero downtime migration Laravel setup is really worth. Checkout was throwing 500s. Support was pinging. And the migration was "just adding a column."

That column was on a 30-million-row MySQL table, and the naive `ALTER` grabbed a lock the whole time it rewrote the table. This guide is the playbook I wish I'd had that afternoon: how to change schemas on a live database without a maintenance page, using the expand/contract pattern and the online DDL tricks that keep locks off the hot path.

## Why "just run the migration" causes downtime

A migration feels instant on your laptop because your laptop has a hundred rows. Production doesn't.

The problem is what the database engine does under the hood for certain `ALTER TABLE` operations. On older MySQL (5.6 and earlier especially) many alters copy the entire table into a new one, holding a metadata lock the whole time. While that lock is held, writes queue up. If the table is big, "queue up" becomes "time out," and your app starts returning errors.

A few operations that have historically been expensive:

- Changing a column type (for example `VARCHAR(255)` to `TEXT`)
- Adding a column in the middle of the table rather than at the end
- Adding an index on a large table without an online option
- Anything that forces a full table rebuild

The dangerous part is that it's silent. The migration works. It just also blocks every other query against that table until it finishes. So the goal isn't to avoid schema changes. It's to make every change small, additive, and non-blocking, then split the risky reshaping across multiple deploys.

## The expand/contract pattern in one picture

Expand/contract (you'll also see it called parallel change) rests on a single rule: **never change and remove in the same deploy.** You grow the schema, move the app onto the new shape while the old shape still works, then shrink.

Here's the flow I run every time, broken into concrete steps.

### Step 1: Expand, adding the new shape while keeping the old

Ship an additive migration only. Add a nullable column, or a whole new table. Nothing that rewrites existing rows, nothing that drops anything.

```php
// database/migrations/2026_12_02_090000_add_full_name_to_users.php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        // Nullable + no default = cheap, instant-ish metadata change on MySQL 8
        $table->string('full_name')->nullable()->after('email');
    });
}
```

Adding a **nullable** column with no default is the cheapest change you can make. On MySQL 8 it runs as an instant metadata change for most cases, so there's no table rewrite and no long lock. Deploy this on its own. The app doesn't even know the column exists yet.

### Step 2: Deploy code that writes to both

Now update the application so every write populates the old and the new representation at the same time. If you're splitting a `name` column into `first_name` and `last_name`, keep writing `name` and start writing the two new columns.

```php
public function save(): void
{
    // Old field, still the source of truth for reads
    $this->user->name = $this->fullName;

    // New fields, populated in parallel so they stay fresh
    [$first, $last] = $this->splitName($this->fullName);
    $this->user->first_name = $first;
    $this->user->last_name = $last;

    $this->user->save();
}
```

At this point new and updated rows are correct in both shapes. Old rows are still stale. That's fine, we fix them next.

### Step 3: Backfill existing rows in batches

You need the historical rows filled in, and you must not do it in one giant `UPDATE`. A single statement touching millions of rows holds locks and bloats the transaction log. Chunk it.

```php
User::whereNull('first_name')
    ->orderBy('id')
    ->chunkById(1000, function ($users) {
        foreach ($users as $user) {
            [$first, $last] = $this->splitName($user->name);
            $user->forceFill([
                'first_name' => $first,
                'last_name'  => $last,
            ])->save();
        }
    });
```

`chunkById` walks the table in slices of 1,000 keyed on the primary key, so each batch is a small, fast transaction. Run it from a queued job rather than inside the migration, because backfills can take minutes or hours and you don't want that blocking a deploy. If you're spreading the work across many jobs, [Laravel job batching](/blog/laravel-job-batching) gives you progress tracking and a clean `finally` hook for when the whole backfill completes.

One note from experience: throttle it. A `sleep` between chunks, or a small delay in the job, keeps you from saturating the database and starving live traffic. A backfill that finishes in 20 minutes but doesn't wake anyone at night beats one that finishes in 5 and pages the on-call.

### Step 4: Switch reads to the new shape

Once the backfill reports zero remaining null rows, flip reads over. Point your queries, serializers, and views at `first_name` / `last_name`. Deploy that. Keep writing the old column for now, just in case you need to roll back fast.

Ship this, watch your dashboards, let it soak for a day or a week depending on how nervous the table makes you.

### Step 5: Contract, dropping the old shape

Only after reads are stable on the new columns do you remove the old one. This is a separate migration in a later deploy.

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('name');
    });
}
```

Dropping a column on MySQL 8 is also an instant operation in most cases, so the contract step is cheap too. The expensive, risky data movement already happened in the background during step 3, where it couldn't hurt anyone.

## Online DDL: making the unavoidable alters safe

Some changes can't be pure metadata tweaks. When you genuinely have to rebuild a table or add an index to a huge one, use the engine's online tooling instead of a blind `ALTER`.

### MySQL 8

MySQL 8 lets you request an algorithm and a lock level explicitly:

```sql
ALTER TABLE orders
  ADD INDEX idx_orders_status (status),
  ALGORITHM=INPLACE, LOCK=NONE;
```

`LOCK=NONE` tells MySQL to reject the statement rather than silently take a blocking lock. That's the behavior you want: fail loud in review instead of stalling prod. Not every operation supports `INPLACE` with `LOCK=NONE`, so check the docs for your specific change. If it can't be done online, you'll get an error and can plan around it.

Laravel doesn't expose these flags in the schema builder, so drop to raw SQL for the alter:

```php
public function up(): void
{
    DB::statement('
        ALTER TABLE orders
        ADD INDEX idx_orders_status (status),
        ALGORITHM=INPLACE, LOCK=NONE
    ');
}
```

For alters that MySQL can't do online at all, reach for **pt-online-schema-change** (Percona) or **gh-ost** (GitHub). Both build a shadow copy of the table, sync changes via triggers or the binlog, and swap it in with a brief lock at the very end. They turn a 40-second stall into a sub-second cutover. If your team runs big MySQL tables and isn't using one of these yet, that's the highest-leverage thing to adopt.

### PostgreSQL

Postgres has its own sharp edges. Two that bite people:

Building an index normally locks the table against writes. Use the concurrent form:

```sql
CREATE INDEX CONCURRENTLY idx_orders_status ON orders (status);
```

`CONCURRENTLY` can't run inside a transaction, and Laravel wraps migrations in one by default. Disable it for that migration:

```php
public $withinTransaction = false;

public function up(): void
{
    DB::statement('CREATE INDEX CONCURRENTLY idx_orders_status ON orders (status)');
}
```

Adding a `NOT NULL` constraint is the other trap. On older Postgres it scanned the whole table under a lock. The safe modern approach is a two-step: add a `CHECK (col IS NOT NULL) NOT VALID` constraint, `VALIDATE` it separately (which takes a weaker lock), then promote to a real `NOT NULL`. If you're picking indexes to add in the first place, [database indexing explained](/blog/database-indexing-explained) covers which ones actually earn their keep.

## Keeping data consistent during the switch

The window where both shapes are live is where subtle bugs hide. A couple of habits keep it boring:

- **Make the app tolerant of both shapes** during steps 2 through 4. Reads should cope with a row that has the new column populated or still null.
- **Watch your isolation level** if the backfill runs alongside heavy writes. Concurrent updates to rows you're backfilling can produce surprises depending on how transactions overlap; [database isolation levels](/blog/database-isolation-levels) is worth a read before you assume `chunkById` and live traffic won't collide.
- **Verify before contracting.** A quick `SELECT COUNT(*) WHERE first_name IS NULL` before step 5 is cheap insurance against dropping the old column while rows still depend on it.

## FAQ

### Can I do a zero-downtime migration without expand/contract?

For genuinely trivial, instant changes on MySQL 8 (a nullable column at the end of the table), a single additive migration is effectively zero downtime on its own. The full expand/contract dance is for changes that move or reshape existing data. If nothing has to be rewritten or backfilled, you don't need all five steps.

### Does Laravel run migrations in a transaction?

On engines that support transactional DDL, like PostgreSQL, Laravel wraps each migration in a transaction so a failure rolls back cleanly. MySQL does not support transactional DDL, so a failed migration there can leave you half-applied. Either way, set `public $withinTransaction = false` when you need an operation, such as `CREATE INDEX CONCURRENTLY`, that can't live inside a transaction.

### How big is "too big" to alter directly?

There's no hard number, but I get cautious past a few hundred thousand rows and I assume anything in the millions needs online DDL or a tool like gh-ost. The honest answer: test the alter against a production-sized copy and time it. If it locks for longer than your slowest acceptable request, treat it as a blocking change.

### What about rolling back?

The reason you keep writing the old column through step 4 is exactly this. If reads on the new shape misbehave, you revert the read-switch deploy and you're back on the old column, which never stopped being current. Rollback becomes a code deploy, not a frantic data restore. That safety net is the whole point of contracting last.

## Wrapping up

Zero-downtime migrations aren't a trick, they're a sequencing discipline. Split every risky change into expand, dual-write, backfill, switch, and contract. Keep each schema step additive and instant. Push the slow data movement into chunked background jobs where it can take its time. And when an alter genuinely has to rebuild a table, let MySQL's `ALGORITHM=INPLACE, LOCK=NONE`, gh-ost, or Postgres's `CONCURRENTLY` do the heavy lifting off the hot path.

Do that, and the Friday-afternoon deploy that used to make your stomach drop becomes just another deploy. Start with the next schema change on your board: write down which of the five phases it needs, and ship the expand step on its own. The rest follows.