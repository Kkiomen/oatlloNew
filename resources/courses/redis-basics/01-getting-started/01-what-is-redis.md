---
title: "What is Redis?"
slug: what-is-redis
seo_title: "What is Redis? In-Memory Key-Value Store Explained"
seo_description: "Redis is an in-memory key-value store. Learn what in-memory and key-value mean, and why Redis is so fast, in plain language for developers."
---

## The one-sentence answer: what is Redis?

Redis is an in-memory key-value store.

Three words, three ideas, and each one carries weight. We will take them one at a time, and by the end the word "fast" will mean something concrete instead of marketing.

## What is a key-value store?

A key-value store is the simplest kind of database. You give it a name (the key) and a piece of data (the value), and it hands the value back when you ask for the key.

Think of a coat check at a theater. You hand over your coat and get a numbered ticket. Later you show the ticket and get your coat back. The ticket is the key. The coat is the value.

That is the whole idea:

```text
key: "user:42:name"   value: "Ada"
key: "cart:99:total"  value: "128.50"
```

There are no tables, no rows, no columns, and no `JOIN`. You look things up by their key, and that is it. This makes lookups extremely simple and extremely fast.

We will meet real commands to set and get values in a later lesson. For now, hold the picture: a name points to a value.

## What does "in-memory" mean in Redis?

Most databases (MySQL, PostgreSQL) store their data on disk, the SSD or hard drive in your server. Disk keeps data safe when the power goes off, but reading from it is relatively slow.

Redis keeps its data in RAM, the computer's memory, instead. RAM is the fast, temporary workspace a computer uses while it is running.

Reading from RAM is thousands of times faster than reading from disk. That single design choice is the main reason Redis feels instant.

The trade-off: RAM is cleared when the process stops. So plain in-memory data is temporary by nature. (Redis can also save to disk so data survives a restart. That is an advanced topic we cover much later, in the persistence lesson.)

## Why is Redis so fast?

Three things combine to make Redis quick:

1. **Data lives in RAM.** No slow disk reads on the hot path.
2. **Lookups are by key.** No scanning tables, no query planning.
3. **The core is simple.** Redis does a small set of jobs and does them well.

The result: a typical Redis command completes in well under a millisecond.

One nuance worth setting straight early: "sub-millisecond" describes the work Redis does on a single command, not the whole round trip from your app. The network hop between your code and Redis still costs time. That is why the speed shows up most when you replace something genuinely slow (a disk read, a heavy query) rather than something already quick.

## Common mistake

Do not think of Redis as a faster MySQL. It is not a drop-in replacement for a relational database. It stores data in a different shape (keys and values, not tables) and it favors speed over the rich querying you get from SQL. We compare the two properly in [Redis vs a database](/course/redis-basics/getting-started/redis-vs-a-database).

## FAQ

### Is Redis a database or a cache?

Both. Redis is a key-value data store. One of its most popular uses is caching, but that is a use case, not its definition.

### Does Redis lose my data when it restarts?

By default the data lives in RAM, so a restart clears it unless you enable saving to disk. We cover persistence near the end of the course.

### What does "in-memory" actually mean?

It means the data is held in RAM (the computer's fast working memory) rather than on the disk. That is what makes reads and writes so quick.
