---
name: "Laravel Collections: The Methods You Are Not Using"
slug: laravel-collections-deep-dive
short_description: "The Laravel collection methods that replace nested loops: groupBy, keyBy, mapWithKeys, partition, reduce, plus lazy collections and the PHP-vs-SQL trap."
language: en
published_at: 2027-05-24 09:00:00
is_published: true
tags: [laravel, php, collections, eloquent, performance]
---

Most Laravel code I review uses maybe five collection methods: `map`, `filter`, `each`, `pluck`, and `first`. That's it. Everything else gets rebuilt by hand with a `foreach`, an accumulator array, and a comment that says `// group by user`.

The `Illuminate\Support\Collection` class ships with roughly 130 methods. A big chunk of them exist precisely to delete the loop you just wrote. This is a tour of the ones that earn their keep in real code, plus two things that will bite you if nobody told you: the difference between an Eloquent collection and a base collection, and the fact that a collection filters in PHP memory, not in your database.

## The shape-changing methods you keep re-implementing by hand

`map` and `filter` keep the collection roughly the same shape. The interesting methods change the shape, and those are the ones people skip because the method name doesn't announce what it does.

`groupBy` turns a flat list into buckets keyed by whatever you return. Say you have a list of orders and you want them grouped by status:

```php
$byStatus = $orders->groupBy('status');
// ['paid' => Collection[...], 'pending' => Collection[...], 'refunded' => Collection[...]]

$byStatus->get('pending')->count();
```

You can pass a closure instead of a key when the grouping is computed:

```php
$byMonth = $orders->groupBy(fn ($order) => $order->created_at->format('Y-m'));
```

`keyBy` is the one I reach for most. It re-indexes a collection so you can look items up by a field instead of scanning. The classic case is turning a query result into a lookup table:

```php
$usersById = User::all()->keyBy('id');
$usersById->get(42); // O(1), no linear search
```

`mapWithKeys` is `map` when you need to control the key too. It expects each iteration to return a single-element `[key => value]` array. This builds a config-style map in one pass:

```php
$rates = $currencies->mapWithKeys(fn ($c) => [$c->code => $c->rate]);
// ['USD' => 1.0, 'EUR' => 0.92, ...]
```

`flatMap` maps and flattens one level in the same step. It's what you want when each item expands into several items. Collect every tag across every post without a nested loop:

```php
$allTags = $posts->flatMap(fn ($post) => $post->tags);
```

`partition` splits a collection into two by a predicate and returns both halves. This is the method I most often see rebuilt as two separate `filter` calls that walk the list twice:

```php
[$active, $inactive] = $users->partition(fn ($user) => $user->is_active);
```

One pass, two collections, and the intent reads straight off the line.

`mapToGroups` is `groupBy` and `mapWithKeys` fused: each item returns a `[key => value]` pair, and matching keys collect into a list. Useful when the group key and the stored value are both derived:

```php
$emailsByDomain = $users->mapToGroups(fn ($u) => [
    Str::after($u->email, '@') => $u->email,
]);
// ['gmail.com' => ['a@gmail.com', 'b@gmail.com'], ...]
```

## reduce, when there's no dedicated method

`reduce` is the escape hatch. When you need to fold a collection down to a single value and no built-in method fits, `reduce` handles it. The second argument is the initial carry:

```php
$total = $lineItems->reduce(
    fn ($carry, $item) => $carry + ($item->price * $item->quantity),
    0
);
```

That said, don't reach for `reduce` when a named method exists. If you're summing a single field, `$lineItems->sum(fn ($i) => $i->price * $i->quantity)` says the same thing more plainly. I keep `reduce` for genuinely custom accumulation, like building a running balance or merging structures.

## partition, pipe, and tap: the flow-control trio

`tap` runs a side effect and returns the original collection untouched. It's for logging or debugging mid-chain without breaking the fluent flow:

```php
$result = $orders
    ->filter->isPaid()
    ->tap(fn ($paid) => Log::info("Paid orders: {$paid->count()}"))
    ->map->toReceipt();
```

The chain keeps flowing. `tap` sees the value, does its thing, hands the same collection along.

`pipe` is the opposite: it passes the whole collection to a callback and returns whatever that callback returns. Use it to fold a bit of external logic into a chain without an intermediate variable:

```php
$stats = $responseTimes->pipe(fn ($times) => [
    'p50' => $times->median(),
    'max' => $times->max(),
]);
```

`when` and `unless` add conditional steps without breaking out of the chain into an `if`. This matters a lot when building a query or a filtered list from request input:

```php
$filtered = $products
    ->when($request->onlyInStock, fn ($c) => $c->where('stock', '>', 0))
    ->when($request->sort === 'price', fn ($c) => $c->sortBy('price'));
```

If `$request->onlyInStock` is falsy, the closure never runs and the collection passes through unchanged. No temporary variable, no branching mess.

## The methods with narrow but sharp use cases

`sole` returns the single item matching a condition and throws if there are zero or more than one. That "or more than one" is the point. When your logic assumes exactly one match, `sole` turns a silent bug into a loud exception:

```php
$admin = $users->sole(fn ($u) => $u->role === 'owner');
// throws MultipleItemsFoundException if two owners exist
```

`first()` would have quietly handed you one of the two owners and hidden the data problem. `sole` refuses to guess.

`zip` merges collections positionally, pairing the first item of each, then the second, and so on:

```php
$labels = collect(['CPU', 'RAM', 'Disk']);
$values = collect([73, 61, 44]);

$labels->zip($values)->map(fn ($pair) => "{$pair[0]}: {$pair[1]}%");
// ['CPU: 73%', 'RAM: 61%', 'Disk: 44%']
```

`chunkWhile` groups consecutive items as long as a condition holds between neighbors. It's built for runs and streaks — think grouping consecutive log lines from the same second, or splitting a sorted list at gaps:

```php
$runs = collect([1, 2, 3, 7, 8, 20])
    ->chunkWhile(fn ($value, $key, $chunk) => $value === $chunk->last() + 1);
// [[1, 2, 3], [7, 8], [20]]
```

Rebuilding that by hand means tracking the previous value across iterations, and it's always slightly off the first time.

## Higher-order messages: the shorthand that confuses people

You've seen `->filter->isPaid()` and `->map->toReceipt()` above. Those arrows aren't typos. Laravel exposes many collection methods as **higher-order messages**: a magic property that lets you call a method on every item without writing a closure.

```php
// These two lines do the same thing:
$paid = $orders->filter(fn ($order) => $order->isPaid());
$paid = $orders->filter->isPaid();
```

The methods that support this include `map`, `filter`, `each`, `reject`, `sortBy`, `groupBy`, `partition`, `sum`, and a handful more. It reads cleanly when you're calling a method or reading a property, and it falls apart the moment you need an argument — then you go back to the closure. I use it for the trivial cases and don't force it.

## Eloquent collections are not base collections

Here's a distinction that trips people up. When a query returns results, you get an `Illuminate\Database\Eloquent\Collection`, which extends the base `Illuminate\Support\Collection`. When you call `collect([...])` or map an Eloquent collection down to scalars, you often drop back to the base class.

The Eloquent collection adds model-aware methods. `find` looks up by primary key, `modelKeys` returns all the IDs, `load` eager-loads a relation onto an already-fetched set, and `fresh` re-pulls the models from the database:

```php
$users = User::where('active', true)->get(); // Eloquent collection

$users->modelKeys();          // [1, 4, 9, ...]
$users->find(4);              // the model with id 4, or null
$users->load('subscription'); // one extra query, relation attached to all
```

That `load` is the one worth remembering. If you already have the models in memory and then realize you need a relation, `load` fetches it for the whole set in a single query instead of triggering N lazy loads. Call `pluck('name')` on that same collection, though, and the result is a base collection of strings — the model methods are gone because there are no models left. Knowing which class you're holding tells you which methods are on the table.

## The trap: collections run in PHP, not in SQL

This is the mistake that turns a fast page into a slow one, and it hides because the code looks clean. A collection method operates on data that is already in PHP memory. The database is done by the time the collection sees anything.

So this is a disaster on a large table:

```php
// Loads EVERY user into memory, then filters in PHP
$admins = User::all()->where('role', 'admin');
```

`User::all()` pulls every row across the wire, hydrates every one into a model object, and only then does the collection's `where` walk them in PHP. On 100,000 users you've just moved 100,000 rows and built 100,000 objects to keep a few hundred.

The database is built to filter. Let it:

```php
// Filters in SQL, returns only what matches
$admins = User::where('role', 'admin')->get();
```

The tell is calling `->all()`, `->get()`, or `->pluck()` and *then* chaining `where`, `filter`, `sortBy`, or `take`. If a query builder method exists for what you're doing, do it before the data leaves the database. The collection is for shaping results you legitimately need in memory — not for work MySQL or Postgres would do faster with an index.

There's a real exception: when you already have the full set loaded for another reason, filtering it again in memory beats a second round-trip. The rule isn't "never filter in a collection." It's "don't pull rows into a collection solely to throw most of them away."

## Lazy collections for data that doesn't fit in memory

Sometimes you genuinely need to iterate a huge result set — a nightly export, a data backfill, a report over every order ever placed. Loading all of it with `get()` will exhaust memory. `User::all()` on a few million rows dies with an allocation error.

`LazyCollection`, backed by `cursor()`, keeps only one model in memory at a time by streaming rows from the database using a PHP generator:

```php
use App\Models\Order;

Order::where('created_at', '>=', now()->subYear())
    ->cursor()
    ->each(function ($order) {
        ExportRow::write($order->toCsvLine());
    });
```

`cursor()` returns a `LazyCollection`, so the whole fluent API still works — `filter`, `map`, `chunk` — but nothing materializes until you iterate, and each row is discarded before the next arrives. Memory stays flat regardless of how many rows come back. I've watched this take a job from "killed at 512 MB" to a steady 30 MB.

The trade-off is honest: a cursor holds a live database connection open for the duration, and you can't go back or count without a second query. For medium jobs, `chunk` (or `chunkById`) is often the safer middle ground — it pages through in batches, releasing the connection between pages:

```php
Order::where('status', 'pending')
    ->chunkById(1000, function ($orders) {
        foreach ($orders as $order) {
            $order->update(['status' => 'processing']);
        }
    });
```

Rule of thumb: read-only stream over everything, reach for `cursor()`. Updating rows as you go, use `chunkById` so paging isn't thrown off by your own writes.

## FAQ

### When should I use a collection instead of a plain array?

Whenever you're about to write a loop that transforms, groups, or reduces data. Collections make the intent readable and chainable. For a hot inner loop over millions of primitive values where every microsecond counts, a raw `foreach` over an array avoids method-call overhead — but that's a rare, measured case, not a default.

### Does collect() hit the database?

No. `collect()` just wraps an existing array or iterable in a `Collection`. It does zero database work. The database work happened in the query builder or Eloquent call that produced the data you're wrapping. This is exactly why filtering a collection can't use your indexes.

### What's the difference between map and each?

`map` returns a new collection built from what your callback returns, and it doesn't touch the original. `each` runs a callback for its side effects and returns the original collection unchanged — use it for things like sending mail or writing logs, not for transforming values. If you find yourself pushing into an outside array inside `each`, you probably wanted `map`.

### Why did my collection method not filter the database?

Because collection methods run after the query. `Model::all()->where(...)` loads the whole table, then filters in PHP. Move the condition onto the query builder — `Model::where(...)->get()` — so the database does the filtering with an index and returns only the rows you asked for.

## Where this leaves you

The next time you catch yourself opening a `foreach` with an empty `$result = []` above it, stop and ask which collection method already does this. Grouping is `groupBy`. Lookups are `keyBy`. A split is `partition`. Building a keyed map is `mapWithKeys`. The loop you were about to write is usually one method that reads like a sentence.

And keep the two hard rules in your head: filter in SQL before the data reaches the collection, and stream with `cursor()` when the result set won't fit in memory. Get those two right and collections stay a convenience instead of quietly becoming a performance bug.
