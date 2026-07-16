---
name: "Cursor vs Offset Pagination in Laravel"
slug: laravel-cursor-vs-offset-pagination
short_description: "Why paginate() crawls on big tables, how cursorPaginate() stays fast, and the trade-offs that decide which one your API should use."
language: en
published_at: 2027-06-23 09:00:00
is_published: true
tags: [laravel, php, database, performance]
---

We had an admin table of audit logs that grew past four million rows, and someone noticed that page 1 loaded instantly while page 9,000 took almost two seconds. Same query, same indexes, same everything - just a bigger `OFFSET`. That gap is the whole story of why cursor pagination exists, and it's the thing nobody tells you when you reach for `paginate()` out of habit.

Most of the time we ignored it. Page 9,000 of an audit log isn't a page anyone clicks by hand. But the same shape shows up in exports and infinite-scroll feeds, where deep pages are the normal case, not the edge, and there it stops being ignorable. So let's start with why the database slows down at all, then look at what Laravel actually hands you to fix it - and where reaching for the fix is a mistake.

## Why OFFSET gets slower as you go deeper

Here's the part that surprises people: `LIMIT 20 OFFSET 180000` does not jump to row 180,000. The database has no way to teleport there. It walks the ordered result, counts off 180,000 rows, throws every one of them away, and only then keeps the 20 you asked for.

So the cost isn't the 20 rows you return. It's the 180,000 you read and discard. Deeper page, bigger discard, slower query. It scales linearly with how far you scroll.

You can watch it happen with `EXPLAIN ANALYZE`:

```sql
EXPLAIN ANALYZE
SELECT * FROM audit_logs
ORDER BY created_at DESC
LIMIT 20 OFFSET 180000;
```

On MySQL/Postgres you'll see the row estimate climb into the hundreds of thousands even though the final output is 20 rows. A covering index on `created_at` helps - the engine can skip through the index instead of the full table - but it still has to *count* through 180,000 index entries. The index removes the sort and the table lookups, not the counting. That's the ceiling offset can't break.

For the first few pages none of this matters. The pain is real only in the deep tail, which is exactly where infinite-scroll feeds and data exports live.

## The three methods Laravel gives you

Laravel's query builder has three paginators, and they are not interchangeable.

**`paginate()`** runs your query plus a second `COUNT(*)` query to work out the total number of pages. That's what powers "Page 3 of 412" and the numbered links in the Blade paginator. Two queries per request, and the count itself can be expensive on a big filtered table.

```php
$logs = AuditLog::query()
    ->where('team_id', $teamId)
    ->orderByDesc('created_at')
    ->paginate(20);

// $logs->total(), $logs->lastPage() available
```

**`simplePaginate()`** drops the `COUNT(*)`. It fetches `perPage + 1` rows to figure out whether there's a next page, and gives you "Previous / Next" only - no total, no last page. Still offset-based under the hood, so the deep-page slowdown is unchanged, but you save the count query.

```php
$logs = AuditLog::query()
    ->where('team_id', $teamId)
    ->orderByDesc('created_at')
    ->simplePaginate(20);

// No $logs->total() - it was never computed
```

**`cursorPaginate()`** is the different beast. It doesn't use `OFFSET` at all. Instead it remembers the last row you saw and asks for rows *after* that one using a `WHERE` clause. Constant cost per page, no matter how deep.

```php
$logs = AuditLog::query()
    ->where('team_id', $teamId)
    ->orderByDesc('created_at')
    ->orderByDesc('id') // tiebreaker - more on this below
    ->cursorPaginate(20);
```

A quick way to hold them in your head:

| Method | Extra COUNT query | Page numbers | Jump to page N | Deep-page cost |
| --- | --- | --- | --- | --- |
| `paginate()` | Yes | Yes | Yes | Grows linearly |
| `simplePaginate()` | No | No (prev/next) | No | Grows linearly |
| `cursorPaginate()` | No | No (prev/next) | No | Flat |

## How keyset pagination actually works

Cursor pagination is sometimes called keyset pagination, and the second name explains it better. Instead of "skip 180,000 rows," it says "give me the rows that come after this key."

If you're ordering by `id` ascending, page two is simply:

```sql
SELECT * FROM audit_logs
WHERE id > 4021        -- last id from page one
ORDER BY id ASC
LIMIT 20;
```

There's no counting. With an index on `id`, the database seeks straight to the entry just past 4021 and reads 20 rows. Page 2 and page 9,000 cost the same, because both are a single index seek plus a short scan.

The catch is the ordering column has to be part of a usable index and it has to produce a **stable, unique** order. `id` is perfect. `created_at` alone is not, because two rows can share a timestamp - and the moment your `WHERE created_at > ?` sits on a boundary where several rows share that value, you either skip some or repeat some. That's why the real query needs a tiebreaker:

```sql
SELECT * FROM audit_logs
WHERE (created_at, id) < ('2027-06-20 14:03:00', 4021)
ORDER BY created_at DESC, id DESC
LIMIT 20;
```

Laravel builds exactly this compound comparison for you when you chain `orderByDesc('created_at')->orderByDesc('id')`. The cursor it encodes (that opaque `?cursor=eyJ...` string) carries the values of *every* column in your `orderBy`, so it can reconstruct the boundary precisely. If you order by a non-unique column and forget the tiebreaker, `cursorPaginate()` can silently drop or duplicate rows at page edges. This is the one requirement you cannot skip.

For this to actually be fast, that ordering needs index support. A composite index matching your sort - `(created_at, id)` here - lets the engine do a pure index seek. Without it you're back to scanning. If index design is fuzzy for you, the mechanics in [database indexing explained](/database-indexing-explained) are worth a detour before you ship this.

## The bug offset has that nobody mentions

There's a correctness problem with offset that's separate from speed, and it's nastier because it's invisible in testing.

Picture a feed sorted newest-first. A user loads page one (rows 1-20). While they're reading, three new rows get inserted at the top. Now they tap "next page," which fetches `OFFSET 20 LIMIT 20`. But the list shifted down by three. Rows that were positions 18, 19, 20 have been pushed to 21, 22, 23 - so the user sees three rows they already saw on page one. **Duplicates.**

The mirror image happens with deletions: delete rows near the top and offset 20 now points *past* rows the user never saw. **Skipped rows.** Silent, no error, and almost impossible to reproduce unless writes happen mid-scroll - which on a busy production table is constantly.

Cursor pagination is immune to this. The cursor is anchored to a specific row's key (`WHERE id < 4021`), not to a positional count. New rows inserted above 4021 don't change what "after 4021" means. The user keeps scrolling through a consistent window even while the table churns underneath them. For any live feed, this stability alone is a stronger argument than the speed.

## What this means for your API design

This is where the choice stops being academic, because it dictates the shape of your endpoint.

If you expose numbered pages - `?page=5`, a footer with "1 2 3 ... 412", a "jump to last page" link - you are committed to offset. Cursor pagination has no concept of page 5. You can only go forward and backward from where you are, because the cursor *is* your position. There's no total either, so you can't render "412 pages" without a separate count query that defeats the point.

If your API is a feed - mobile infinite scroll, a "load more" button, a webhook consumer draining events - cursor is the better fit. The client doesn't want page numbers; it wants "give me the next batch, here's my cursor." Laravel's JSON output makes this natural:

```php
return AuditLog::query()
    ->orderByDesc('created_at')
    ->orderByDesc('id')
    ->cursorPaginate(20);

// Response includes:
// "next_cursor": "eyJjcmVhdGVkX2F0Ijoi...",
// "prev_cursor": null,
// "next_page_url": "https://.../logs?cursor=eyJ..."
```

The client just follows `next_page_url` or passes `next_cursor` back. Treat the cursor as opaque - don't parse it, don't build one by hand. It's base64 JSON today, but it's an implementation detail and decoding it in a client is how you build something that breaks on the next framework upgrade.

One real constraint: because the cursor encodes your `orderBy` columns, you can't let the client freely re-sort a cursor-paginated result mid-stream. Change the sort and the old cursor is meaningless. If your endpoint offers sortable columns, either regenerate the cursor on each sort change or accept that sorting resets to the first page.

## When offset is genuinely fine

Cursor pagination isn't a straight upgrade, and reaching for it everywhere is its own mistake. Plain `paginate()` is the right call when:

- **The table is small or bounded.** A few thousand rows, or a per-user table that never grows large - offset's discard cost is nothing you'll ever measure. Optimizing it is wasted effort.
- **Users genuinely need page numbers or jump-to-page.** An admin report where someone types "go to page 40," or anything with a "last page" link. Cursor can't do this. Don't contort your UX to save milliseconds nobody notices.
- **You need the total count anyway.** If the UI shows "1,240 results," you're paying for `COUNT(*)` regardless, so `paginate()` isn't costing you an extra query you wouldn't already run.
- **Data doesn't change under the user.** A static reference table or a snapshot won't hit the skip/duplicate bug, so that argument for cursor drops away too.

My rule of thumb: offset for admin panels and reports, cursor for public feeds and large exports. When the table crosses roughly a hundred thousand rows *and* the access pattern is forward-scrolling, switch. Below that, or when page numbers are a real feature, offset earns its keep.

## FAQ

**Can I add a "jump to page 5" button with cursorPaginate?**
No. Cursor pagination only knows the row you're currently anchored to, so there's no way to address an arbitrary page. If jump-to-page is a hard requirement, you need offset pagination and you'll accept its deep-page cost - or build page anchors some other way.

**Does cursorPaginate work with joins and complex where clauses?**
Yes, as long as your `orderBy` columns resolve to real, indexed, unambiguous columns. Ordering by a computed column or an aggregate that has no index will still work logically but won't be fast, and ambiguous column names across joined tables can break the cursor comparison. Qualify them (`orderByDesc('audit_logs.id')`) and make sure the sort is index-backed.

**Why do I get duplicate or missing rows with cursorPaginate?**
Almost always a non-deterministic sort. If you order by a column with repeated values and no unique tiebreaker, the boundary comparison is ambiguous. Add a unique column - usually the primary key - as the final `orderBy`, and the duplicates disappear.

**Is simplePaginate faster than paginate?**
It skips the `COUNT(*)` query, so on a large or heavily filtered table it saves a real chunk of work. But it's still offset-based, so it does nothing for the deep-page slowdown. It's a middle ground: cheaper than `paginate()`, but not immune to the linear cost the way cursor is.

## Where to land

The decision isn't "which is faster" - it's "does this endpoint need page numbers, and does the table get big while data changes underneath it." Answer those two and the method picks itself. Feeds and large forward-only lists get `cursorPaginate()` with a unique tiebreaker in the sort; admin tables and reports keep `paginate()`.

If you have one endpoint that's already slow on deep pages, start there: add the composite index matching your sort, switch to `cursorPaginate()`, and run `EXPLAIN ANALYZE` on the before and after. Watching the discarded-row count collapse to zero is the moment the whole thing clicks.
