---
title: "Why we cache"
slug: why-we-cache
seo_title: "Why We Cache: The Speed vs Freshness Trade-off"
seo_description: "Why caching matters: do expensive work once, master the speed-versus-freshness trade-off, and learn what makes a good cache candidate in Redis."
---

Caching is simple to say and easy to get wrong: keep the answer to expensive work so you
do not have to redo it. The storing is trivial. The hard part is deciding what to cache and
living with the speed-versus-freshness trade-off that comes attached to every cached value.

## Do the expensive work once

Some work is slow. A report that sums a year of orders. A page that runs a dozen
database queries. A call to a third-party API that takes 300 ms every time. If the
answer is the same for many requests, redoing that work for every visitor is pure waste.

A cache lets you compute the answer **once**, store it in Redis, and hand out that stored
copy to everyone who asks next. The first request pays the cost. The next thousand get
the answer in under a millisecond.

```text
Without cache:  request -> run 12 queries -> build page   (slow, every time)
With cache:     request -> read one Redis key            (fast, after the first)
```

Nothing about the work changed. You just stopped repeating it.

## The trade-off: speed vs freshness

Here is the catch, and it never goes away: **a cached answer is a copy, and copies go
stale.**

The moment you store a value, the real data can change while your copy does not. A cached
product price shows 49.00 after someone edited it to 39.00 in the database. For as long as
that copy lives, your app is a little bit wrong.

So caching is a trade. You give up **freshness** (always showing the very latest data) to
buy **speed** (answering fast and cheaply). Every caching decision is really a question
about how much staleness you can live with:

- A live bank balance? Almost none. Be careful.
- A blog post's view count? A minute or two of lag is invisible.
- A "top 10 articles" list? An hour is fine.

There is no universal right answer. There is only the answer for **this** piece of data.

## What makes a good cache candidate

Not everything should be cached. The best candidates share three traits:

1. **Read-heavy.** The data is read far more often than it changes. A homepage read
   thousands of times an hour but edited once a day is perfect.
2. **Expensive to produce.** It costs real time or money each time - heavy queries,
   aggregations, external API calls. Caching something that is already instant just adds
   moving parts.
3. **Tolerant of slight staleness.** You can accept the copy being a few seconds or
   minutes behind. If the data must be exact to the millisecond, a cache fights you.

When all three line up, caching is a clear win. When they do not, you are often better off
without it.

The trait people underestimate is the first one, read-heavy, because it is really about
**hit rate**. A cache only pays off on the reads it serves from memory. Cache something that
gets read once before it changes and you paid the write, the memory, and the invalidation
risk to collect almost no reads - a cache with a low hit rate is slower and more fragile than
no cache at all. Before you reach for Redis, ask not just "is this slow?" but "will the same
answer be asked for again and again before it changes?"

## Common mistake

Beginners reach for caching to "make things faster" without asking whether the data is a
good candidate. Caching a value that changes on every request, or one that is already
cheap to compute, adds complexity and a whole new class of bugs (stale reads, wrong
invalidation) while saving almost nothing. Cache the slow, stable, read-heavy things
first. Leave the rest alone until you have measured a real problem.

## FAQ

### Is caching always about speed?

Mostly, but not only. It also reduces **load** - fewer database queries, fewer API calls -
which protects your database and can save money on paid APIs. Speed is the visible win;
taking pressure off your systems is the quiet one.

### How do I know if something is slow enough to cache?

Measure before you cache. Look at your slow queries and your response times. Cache the
things that actually hurt, not the things you assume are slow. Guessing leads to caching
the wrong stuff.

### Can a cache ever make things slower?

Yes, if you cache the wrong things. Every cache read is a network round-trip to Redis. For
data that is already in memory or trivially cheap, that hop can cost more than it saves.
Caching pays off when the work you skip is much more expensive than the lookup.
