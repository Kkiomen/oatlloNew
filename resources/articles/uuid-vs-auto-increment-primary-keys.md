---
name: "UUID vs Auto-Increment Primary Keys"
slug: uuid-vs-auto-increment-primary-keys
short_description: "When random UUIDs wreck InnoDB insert speed, why UUIDv7/ULID fix it, and whether to store BINARY(16) or CHAR(36)."
language: en
published_at: 2027-04-19 09:00:00
is_published: true
tags: [database, mysql, laravel, php]
---

The first time a random UUID primary key hurt me, the table had about 40 million rows and inserts had quietly slowed from a few thousand a second to a few hundred. Nothing in the app code had changed. The culprit was the primary key: we had switched from `BIGINT AUTO_INCREMENT` to `CHAR(36)` UUIDs for "security," and InnoDB was punishing us for it on every single write. This article is about that trade-off — why random UUIDs are expensive in a clustered index, how time-ordered IDs (UUIDv7, ULID) give you most of the safety without the write penalty, and what to actually pick.

## Why the storage engine cares about your key order

InnoDB stores every table as a clustered index. That's the part people skip past, and it's the whole story. The rows physically live inside the B-tree of the primary key, sorted by primary key value. There is no separate heap. When you insert a row, InnoDB finds the leaf page where that key belongs and writes it there.

With `AUTO_INCREMENT`, every new key is larger than the last. So every insert lands at the right edge of the tree, in the page you just wrote to — the one already sitting hot in the buffer pool. Pages fill up neatly, roughly 15/16 full, and you march forward. Sequential, cache-friendly, cheap.

A random UUIDv4 lands *anywhere*. Each insert targets a different leaf page, scattered across the whole key space. That page probably isn't in the buffer pool, so InnoDB has to read it from disk before it can modify it — a read-before-write you never wanted. And because you're stuffing a value into the middle of an already-full page, you trigger **page splits**: InnoDB takes a full 16 KB page, allocates a new one, and moves roughly half the rows over to make room. Splits fragment the tree and leave pages ~50% full instead of ~94%.

The compounding effects:

- **Write amplification.** One logical insert becomes multiple page reads, a split, and extra dirty pages to flush.
- **Buffer pool thrashing.** Random access means your working set is effectively the whole index, not just the tail. Once the index outgrows RAM, hit rate collapses.
- **Bloat.** Half-full pages mean the same data occupies noticeably more disk, and a bigger index is slower to scan and slower to cache.

None of this shows up in a laptop benchmark with 10,000 rows — the whole index fits in memory and page splits are cheap. It shows up at scale, on the box that's already IO-bound, usually during your busiest hour.

## The secondary-index tax nobody mentions

Here's the part that bites even if you love your primary key. In InnoDB, every secondary index stores the **primary key value** as its row pointer, because that's how it finds the actual row in the clustered index. So the size of your primary key is multiplied across every secondary index on the table.

A `BIGINT` PK costs 8 bytes per secondary-index entry. A `CHAR(36)` UUID stored as text costs 36+ bytes, in every index, for every row. Put three secondary indexes on a 50-million-row table and that difference is measured in gigabytes of extra index that has to be cached and maintained. This is why "we'll just store it as a string, it's simpler" quietly becomes your biggest table.

## UUIDv7 and ULID: keep the properties, drop the randomness

The security argument for UUIDs is real (more on that below), but you don't need *random* to get it. You need *unpredictable and unique*. **UUIDv7** and **ULID** are both 128-bit identifiers whose leading bits are a millisecond timestamp, with random bits filling the rest.

That prefix is the fix. Because the high-order bytes increase with time, new IDs sort to the right edge of the B-tree just like an auto-increment — same sequential insert pattern, same page locality, same near-full pages — while the trailing random bits keep them unguessable and collision-safe across machines. You get the write profile of an integer and the distributed-generation of a UUID.

UUIDv7 was standardized in RFC 9562 (May 2024), which replaced the old RFC 4122. ULID predates it and is functionally similar; the main practical differences are encoding (ULID uses Crockford base32, 26 chars) and that ULID isn't a formal UUID. If you're on MySQL and want something that reads as a UUID in tools, reach for UUIDv7. If you like the compact string form, ULID is fine. Both solve the locality problem.

The order-preserving property is worth spelling out: sort your rows by a UUIDv7 primary key and you get them in roughly creation order for free, no `created_at` index needed for that.

## Store it as BINARY(16), not CHAR(36)

Whatever flavor you pick, a UUID is 128 bits — 16 bytes. Store it as 16 bytes.

```sql
-- Don't: 36 bytes of text, plus it's the PK of a clustered index
CREATE TABLE orders (
    id CHAR(36) NOT NULL PRIMARY KEY,
    total INT NOT NULL
);

-- Do: 16 raw bytes
CREATE TABLE orders (
    id BINARY(16) NOT NULL PRIMARY KEY,
    total INT NOT NULL
);
```

`CHAR(36)` with a UTF-8 connection can occupy even more than 36 bytes internally and, as we saw, gets copied into every secondary index. `BINARY(16)` more than halves that. MySQL 8 ships helpers to convert:

```sql
-- Text UUID -> 16 bytes for storage
SELECT UUID_TO_BIN('018f4e2a-1c3d-7abc-9def-0123456789ab');

-- 16 bytes -> text for display
SELECT BIN_TO_UUID(id) FROM orders;
```

There's a footnote here: `UUID_TO_BIN(uuid, 1)` with the swap flag was designed for UUIDv1, where it shuffles the time bytes to the front to make v1 sortable. **Do not pass the swap flag for UUIDv7** — v7 is already time-ordered, and swapping would scramble that. Store v7 as-is.

Postgres users get off easier: it has a native `uuid` type that's already 16 bytes on disk, so `column uuid` is the right answer and there's no text-vs-binary decision to get wrong.

## The security argument, stated honestly

The genuine reason to avoid auto-increment in a public API is **enumeration**. If your URLs look like `/orders/1042`, anyone can request `/orders/1043` and probe what's there. Sequential IDs also leak volume — a competitor signing up and seeing user ID `88214` learns roughly how many users you have, and the delta between two signups a week apart tells them your growth rate. That's real business intelligence handed out for free.

A random-tail UUID closes that door: you can't guess `018f4e2a-...-6789ab`, and you can't infer counts from it.

But be precise about what this buys you. An unguessable ID is **not** authorization. If `/orders/{uuid}` returns the order to anyone who holds the UUID, you've built capability-based access by accident, and UUIDs leak — into logs, referrer headers, browser history, Slack messages. You still need a real access check on every request. The UUID stops *enumeration*; your policy layer stops *unauthorized access*. Don't conflate them.

A common middle ground: keep a `BIGINT AUTO_INCREMENT` internal primary key for all the join performance, and expose a separate indexed UUID (or ULID) column as the public identifier. You pay for one extra unique index, and you decouple "what the database joins on" from "what the URL shows."

## Doing it in Laravel

Laravel makes the good version easy. `HasUuids` on a model swaps the key type and generates IDs on create:

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasUuids;
}
```

By default `HasUuids` generates an **ordered** UUID via `Str::orderedUuid()` — that's the time-ordered kind, so out of the box Laravel is already steering you away from the random-v4 insert problem. Good default.

The migration should match with a binary column if you want the storage win. Framework `uuid()` columns are `CHAR(36)`; to store 16 bytes you define the column yourself and cast it. Or, simpler and very common, use ULIDs, which Laravel supports first-class:

```php
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Order extends Model
{
    use HasUlids;
}
```

```php
Schema::create('orders', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->unsignedInteger('total');
    $table->timestamps();
});
```

`HasUlids` uses lexicographically sortable 26-character identifiers, which are monotonic and land nicely at the tail of the index. Route-model binding works the same as with integers.

If you want the internal-BIGINT / public-UUID split, don't put `HasUuids` on the model. Keep the normal auto-increment `id` and add a dedicated column plus a route key:

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();                          // internal BIGINT PK
    $table->uuid('public_id')->unique();   // exposed identifier
    $table->timestamps();
});
```

```php
class Order extends Model
{
    protected static function booted(): void
    {
        static::creating(fn (Order $o) => $o->public_id ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
```

Now `/orders/{order}` resolves by `public_id`, joins still run on the integer, and nothing enumerable ever leaves the server.

## Distributed generation without a central sequence

The other honest reason to reach for UUID-family keys: you can mint them anywhere. Auto-increment needs the database to hand out the next value, which is fine until you're sharding, doing offline-first clients that create records before they can reach the server, or merging data from multiple sources without renumbering everything.

A 128-bit ID with enough entropy lets every app server, worker, and mobile client generate a globally unique key locally, with no round-trip and no coordination. UUIDv7 and ULID keep that property while staying sortable, which is why they've largely won over both v4 and over older schemes like Twitter's Snowflake for most application workloads. Snowflake IDs (64-bit, time + machine-id + sequence) are still great when you specifically want a compact integer and can manage machine-id assignment — but that coordination is exactly the thing UUIDv7 lets you skip.

## So what should you pick

Concrete defaults, not "it depends":

- **Internal app, IDs never leave the server, single database:** `BIGINT AUTO_INCREMENT`. Smallest, fastest, simplest. Don't add complexity you won't use.
- **Public API or client-generated IDs, and you want one key:** UUIDv7 (or ULID) stored as `BINARY(16)`. Time-ordered so inserts stay cheap, unguessable so enumeration is dead.
- **You need both peak join performance and a safe public identifier:** internal `BIGINT` PK plus an indexed UUID/ULID `public_id`. The two-column split.
- **Never, on any table with real write volume:** random UUIDv4 as a `CHAR(36)` clustered primary key. That's the exact combination that slowed my table to a crawl.

## FAQ

**Is UUIDv4 always a bad primary key?**
Not always — on a small or low-write table it's fine, because the index fits in memory and page splits are cheap. It goes bad when the table is large and write-heavy, which is precisely when you can least afford to fix it. If you might get there, start with v7.

**Can I migrate an existing auto-increment table to UUIDs without downtime?**
Carefully. The usual path is to add a nullable UUID column, backfill it in batches, add the unique index, switch your code and foreign keys to use it, then drop the old key last. Every foreign key referencing the old integer PK has to be migrated too — that's the part that turns a one-table change into a multi-day project. Don't start it on a Friday.

**Does MySQL 8 have a native UUID type like Postgres?**
No. MySQL stores UUIDs in `BINARY(16)` (or a char type) and gives you `UUID_TO_BIN` / `BIN_TO_UUID` to convert. Postgres has a real `uuid` type that's 16 bytes on disk, which is one fewer thing to get wrong.

**Are UUIDs enough to keep records private?**
No. An unguessable ID prevents *enumeration*, but it isn't access control. UUIDs end up in logs, referrers, and shared links. Authorize every request with your policy layer regardless of how unpredictable the identifier is.

## The one thing to remember

The performance problem was never "UUIDs." It was *randomly ordered* keys in a clustered index. Once the ID sorts with time — UUIDv7 or ULID, stored in 16 bytes — the write penalty mostly evaporates and you keep the distributed generation and the enumeration resistance you actually wanted. Pick your key by asking who reads it and how the table is written, not by which one sounds more modern.
