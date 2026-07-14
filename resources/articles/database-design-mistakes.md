---
name: "6 Common Database Design Mistakes to Avoid"
slug: database-design-mistakes
short_description: "Six database design mistakes that quietly wreck performance and data integrity, plus the concrete SQL fix for each one."
language: en
published_at: 2026-10-12 09:00:00
is_published: true
tags: [database, mysql, postgres, sql]
---

Most of the worst production incidents I've cleaned up didn't start as bugs in the code. They started as **database design mistakes** that sat quietly in a schema for a year or two before anyone paid the bill. A column typed wrong here, a missing constraint there, and one day the numbers on the invoice don't add up.

The frustrating part is that none of these are exotic. They're the same handful of decisions, made under deadline pressure, that come back later with interest. Here are six I keep running into, and how I fix them.

## 1. Storing money as a float

This is the one that costs actual money, so I'll start with it.

I once inherited a billing table where `amount` was a `FLOAT`. Everything looked fine in testing. Then finance flagged that a batch of ~40,000 invoices was off by a cent or two, and the totals in the monthly report drifted from the sum of line items. Floating point can't represent `0.1` exactly, and those tiny errors accumulate once you start summing thousands of rows.

Money is not an approximation. Use a fixed-precision type:

```sql
-- Don't
CREATE TABLE invoices (
    amount FLOAT
);

-- Do
CREATE TABLE invoices (
    amount DECIMAL(12, 2) NOT NULL
);
```

`DECIMAL(12, 2)` stores exact values up to ten digits before the decimal point and two after. Both MySQL and Postgres compute sums on `DECIMAL` without rounding surprises. If you deal in currencies with more than two decimal places, or you want to dodge rounding entirely, some teams store amounts as integer minor units (cents) instead. Either works. A float does not.

## 2. Keeping dates and other typed data as strings

The lazy version of this is a `VARCHAR` column holding `"2026-10-12"`, or worse, `"12/10/2026"` in one row and `"October 12, 2026"` in the next because two different import scripts wrote to it.

Once dates live in a text column, sorting breaks, range queries turn into string comparisons, and every timezone conversion becomes a manual parse. You also lose the database's ability to validate the value, so `"2026-13-45"` slides right in.

Pick the real type and let the engine enforce it:

```sql
-- Postgres
CREATE TABLE events (
    starts_at TIMESTAMPTZ NOT NULL,
    event_date DATE NOT NULL
);
```

`TIMESTAMPTZ` in Postgres stores the instant in UTC and handles the offset for you. In MySQL, `DATETIME` and `TIMESTAMP` fill similar roles, though `TIMESTAMP` is bounded to 2038 and `DATETIME` is not, so know which one you actually need. The same rule applies to booleans, JSON, and enums. If the database has a type for it, using a string instead just throws away the guarantees you're paying for.

## 3. No indexes on foreign keys and lookup columns

Your schema will work fine on a laptop with 500 rows. It falls over the week a real customer imports half a million.

The classic offender is a foreign key with no index behind it. You join `orders` to `customers` on `customer_id`, and because `orders.customer_id` isn't indexed, every join scans the whole table. Postgres does *not* create that index automatically when you declare a foreign key, though a lot of people assume it does. MySQL's InnoDB does create one for the FK, but it won't help columns you only filter or sort on.

```sql
-- Index the foreign key
CREATE INDEX idx_orders_customer_id ON orders (customer_id);

-- Index columns you filter or sort on regularly
CREATE INDEX idx_orders_status_created ON orders (status, created_at);
```

That second one is a composite index, and the column order matters: it helps queries filtering on `status`, or on `status` plus `created_at`, but not one filtering on `created_at` alone. If you want to understand why, I wrote up the mechanics in [database indexing explained](/blog/database-indexing-explained).

One caution: don't swing the other way and index everything. Every index slows down writes and eats disk. Index the columns your slow queries actually touch, and confirm it with `EXPLAIN` before and after.

## 4. Comma-separated lists instead of a junction table

I've seen a `tags` column that looked like this: `"php,mysql,laravel"`. It's seductive because it's easy to write from the app. It's miserable forever after.

Want every article tagged `mysql`? You're writing `WHERE tags LIKE '%mysql%'`, which also matches `mysqld` and can't use an index. Want to rename a tag? Good luck. Want to count how many articles carry each tag? You're parsing strings in application code.

The relational answer is a junction table:

```sql
CREATE TABLE articles (
    id BIGINT PRIMARY KEY
);

CREATE TABLE tags (
    id BIGINT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE article_tag (
    article_id BIGINT NOT NULL REFERENCES articles(id),
    tag_id     BIGINT NOT NULL REFERENCES tags(id),
    PRIMARY KEY (article_id, tag_id)
);
```

Now "all articles tagged mysql" is a plain indexed join, renaming a tag is a one-row update, and counts are a `GROUP BY`. It's one extra table and it pays for itself the first time you query it seriously.

There's a narrow exception: if the data is genuinely a blob you only ever read and write whole, a JSON column (native `JSON` in MySQL, `JSONB` in Postgres) beats a fake CSV. But the moment you need to query *inside* the list, you want the junction table.

## 5. A wall of nullable columns and the EAV trap

Two failures live in the same neighborhood here.

The first is making everything nullable "just in case." When most columns allow `NULL`, the database can no longer tell you what a valid row even looks like. `NULL` starts meaning three different things at once (not applicable, unknown, and nobody-filled-it-in-yet), and your `WHERE` clauses grow `IS NULL` checks that break in subtle ways, because `NULL = NULL` is not true in SQL. If a field is required, say so:

```sql
-- Postgres
ALTER TABLE users
    ALTER COLUMN email SET NOT NULL;

-- MySQL (repeat the column's own definition)
ALTER TABLE users
    MODIFY email VARCHAR(255) NOT NULL;
```

The syntax splits here: Postgres flips the flag in place, while MySQL's `MODIFY` wants the full column definition restated. The second, bigger trap is Entity-Attribute-Value. Someone decides the schema should be "flexible," so instead of real columns you get one giant table of `entity_id, attribute_name, attribute_value`, everything stored as text. I spent two days once trying to make a report fast against an EAV table, and the honest answer was that you can't. Reconstructing one logical row means a self-join for every attribute, and the types are gone.

If you truly need flexible, sparse attributes, a `JSONB` column in Postgres gives you that without exploding every read into a dozen joins, and you can index into it with a GIN index. Reserve EAV for the rare case where attributes are genuinely user-defined at runtime, and even then, go in with your eyes open.

## 6. utf8 that isn't really UTF-8 (the MySQL charset trap)

This one has a great villain. In MySQL, the charset named `utf8` is *not* full UTF-8. It's `utf8mb3`, three bytes max per character, and it silently cannot store anything outside the Basic Multilingual Plane. In practice that means emoji, and a chunk of CJK characters, break.

The symptom is unforgettable: a user puts a 🎉 in their display name, the insert fails or truncates with `Incorrect string value`, and half the row vanishes. I lost an afternoon to exactly this before I understood that `utf8` had been lying to me for years.

Use `utf8mb4`, which is the actual four-byte UTF-8, and set it at the column, table, and connection level so nothing downgrades it:

```sql
ALTER TABLE users
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Set your connection charset to `utf8mb4` too, or the client can quietly mangle characters on the way in. Postgres users mostly dodge this, since a Postgres database created with `UTF8` encoding is real UTF-8, but it's worth confirming your database wasn't created with `SQL_ASCII`, which stores bytes without validating them at all.

## A note on catching these early

Every mistake here shares a root cause: pushing responsibility for data correctness up into application code and hoping it holds. It never fully does. Constraints, foreign keys, and the right types are the cheapest insurance you can buy, because the database enforces them across every code path, including the migration script someone runs by hand at 2 a.m.

If your ORM is masking the shape of your queries, it's worth learning what it actually emits — a lot of schema pain hides behind convenient abstractions, the same way the [N+1 query problem in Eloquent](/blog/eloquent-n1-query-problem) hides behind lazy loading.

## FAQ

### Should I always add foreign key constraints?

For a transactional (OLTP) database, yes, almost always. They're the only thing guaranteeing a child row can't point at a parent that doesn't exist. The main exceptions are high-write analytics or sharded setups where the enforcement cost is measured and deliberately traded away. That's a decision to make on purpose, not a default.

### Is DECIMAL slower than FLOAT?

Slightly, because it's exact arithmetic rather than hardware floating point. For money and quantities that difference is irrelevant next to the cost of being wrong. Reserve `FLOAT`/`DOUBLE` for genuinely approximate values like sensor readings or scientific measurements.

### Can I fix these on a live table?

Usually, but type and charset conversions can rewrite the whole table and lock it while they run. On a large production table, use an online schema change tool (like `pt-online-schema-change` or `gh-ost` for MySQL) or test the migration on a copy first and schedule a window. Don't run `CONVERT TO CHARACTER SET` on a 200 GB table at peak traffic and hope.

### When is a JSON column the right call instead of more tables?

When the data is read and written as one opaque unit and you rarely query inside it, like stored API payloads or per-user UI preferences. Once you find yourself filtering, joining, or aggregating on values *within* the JSON, that's the signal to model them as real columns or a related table.

## Wrapping up

None of these six are hard to avoid once you've been burned by them. Type money as `DECIMAL`, dates as dates, index your foreign keys and lookup columns, add real constraints, model lists as junction tables, and use `utf8mb4` from day one. The schema you design in an afternoon lives for years — spend the extra half hour now so future-you isn't reconciling invoices by hand later.

Next time you spin up a new table, run through this list before the first migration ships. It's a lot cheaper than the cleanup.