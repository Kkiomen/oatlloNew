---
title: "What Redis is used for"
slug: what-redis-is-used-for
seo_title: "What Is Redis Used For? Cache, Sessions, Queues, More"
seo_description: "A quick map of what Redis is used for: caching, sessions, queues, rate limiting, and pub/sub. A plain overview for developers new to Redis."
---

## Why one tool has so many uses

So what is Redis used for in a real application? More than you might expect from a store this simple. In the last lesson we learned that Redis is an in-memory key-value store that is very fast, and speed opens doors. A lot of jobs in a web app are not about storing data forever; they are about answering a small question very quickly, over and over.

Redis is great at exactly that. Treat this lesson as a map, not a manual. We name the main uses so you recognize them, and each one gets its own deep dive later in the course.

## Caching: the number-one use for Redis

This is the number one reason people install Redis.

A cache stores the answer to an expensive question so you do not have to ask again. Imagine a product page that runs ten slow database queries to render. Compute it once, store the result in Redis, and the next visitor gets it instantly.

If your database is the bottleneck, a cache is usually the first fix. We spend a whole chapter on caching later.

The catch nobody mentions on day one: the moment you cache an answer, you now have two copies of it. When the real data changes, the cached copy is stale until something clears it. That "when do I invalidate?" question is the hard part of caching, not the storing. Keep it in the back of your mind; we tackle it head-on in the caching chapter.

## Storing user sessions in Redis

A session is the little bit of memory that remembers a logged-in user between page loads (who they are, what is in their cart).

Sessions are read on nearly every request and do not need to live forever, so RAM is a perfect home for them. This is why so many Laravel and PHP apps store sessions in Redis.

## Queues and background jobs

Some work should not happen while the user waits: sending an email, resizing an image, generating a report.

Instead, you drop a "job" onto a list and let a separate worker process pick it up and do the work in the background. Redis is a common place to keep that list of pending jobs. The user gets a fast response; the slow work happens out of sight.

## Rate limiting API requests

Rate limiting means capping how often someone can do something, for example "max 100 API requests per minute".

To enforce that you need a fast counter you can bump on every request and reset on a schedule. Redis counts quickly and can expire values automatically, which makes it a natural fit.

## Pub/sub (messaging)

Pub/sub is short for publish/subscribe. One part of your system announces "this happened" (publish), and other parts that care are listening (subscribe) and react.

It is a way for separate services to talk without being wired directly to each other. Redis has this built in.

## The big picture

Notice the pattern. Every use above is either:

- **Temporary data** that does not need permanent storage (cache, sessions), or
- **Fast coordination** between parts of a system (queues, rate limits, pub/sub).

That is the sweet spot for Redis. It is not where you keep your orders, users, and invoices forever. Your main database still does that job, as we will see in the next lesson.

## FAQ

### Do I need all of these to use Redis?

No. Most teams start with just caching and grow from there. Pick one problem and solve it.

### Is Redis only for big applications?

No. Even a small app benefits from caching a slow page or storing sessions in Redis. It scales down as well as up.

### Which use should I learn first?

Caching. It is the most common and gives the clearest, fastest win. We build up to it after covering the basics.
