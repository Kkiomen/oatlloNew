---
title: "Redis vs a database"
slug: redis-vs-a-database
seo_title: "Redis vs a Database: RAM vs Disk, When to Use Each"
seo_description: "Redis vs a traditional database like MySQL or Postgres. RAM vs disk, why Redis complements your database instead of replacing it, and when to use each."
---

## The question every beginner asks

If Redis is so fast, why not store everything in it and drop MySQL?

It is a fair question, and it is really the heart of Redis vs a database. The short answer: Redis and a relational database solve different problems. In almost every real app they work together, not against each other. Here is why.

## RAM vs disk: the core trade-off

We touched on this in [What is Redis?](/course/redis-basics/getting-started/what-is-redis). It is the key to everything here.

- A traditional database (MySQL, PostgreSQL) stores data on **disk**. Disk is slower to read, but it is large and it keeps data safely when the power goes off.
- Redis keeps data in **RAM**. RAM is much faster, but it is smaller and more expensive, and it is cleared when the process stops.

So the trade-off is speed versus durability and size. Disk is your permanent filing cabinet. RAM is the notepad on your desk.

## What a relational database is good at

Your main database (MySQL, Postgres) is where the truth lives. It shines at:

- **Durability.** Data survives restarts and crashes.
- **Relationships.** Users have orders, orders have items. `JOIN` ties them together.
- **Rich queries.** "All orders over $100 from last week, sorted by date." SQL handles this.
- **Large data.** Disk is cheap, so you can store a lot.

This is where your users, orders, and invoices belong.

## What Redis is good at

Redis shines at a different set of jobs:

- **Speed.** Sub-millisecond reads and writes.
- **Simple lookups by key.** Fast, but no `JOIN` and no SQL.
- **Temporary data.** Caches, sessions, counters.

Redis trades rich querying and permanent storage for raw speed.

There is a second, quieter consequence of the RAM choice. Because everything lives in memory, and memory is the smaller, pricier resource, your whole dataset in Redis has to fit in RAM. A disk-backed database can happily hold far more data than it has memory. Redis cannot. That ceiling rarely bites beginners, but it is exactly why Redis is a home for a hot slice of data, not all of it.

## How Redis and your database work together

Here is the mental model that makes it click.

Your relational database is the **source of truth**. It holds the real, permanent data. Redis sits **in front of it** as a fast helper: it caches the expensive answers, holds sessions, and counts things.

A typical request looks like this:

```text
1. Ask Redis: do you have this answer already?
2. If yes  -> return it instantly (fast path).
3. If no   -> ask the database, then store the answer in Redis for next time.
```

Redis makes the common case fast. The database keeps everyone honest.

## When to reach for each

Use a **relational database** when:

- The data must not be lost.
- You need relationships, `JOIN`s, or complex queries.
- It is your core business data (users, orders, payments).

Use **Redis** when:

- You need an answer fast and you can rebuild it if it disappears.
- The data is temporary by nature (cache, session, counter).
- You are coordinating between parts of a system (queues, pub/sub).

## Common mistake

Do not make Redis the only home for data you cannot afford to lose. Because it lives in RAM, treat Redis data as something you could rebuild from your real database. If losing it would be a disaster, it belongs in your durable database first.

## FAQ

### Can Redis replace MySQL or Postgres?

For most apps, no. Redis complements your database. It handles speed-sensitive, temporary data while the relational database keeps the permanent source of truth.

### Is Redis always faster than MySQL?

For simple key lookups, yes, by a lot, because it reads from RAM. But it cannot run the rich SQL queries a relational database can, so "faster" only applies to the jobs it is built for.

### Do I still need a database if I use Redis?

Almost always yes. Redis is the fast layer in front; your database is the durable store behind it.
