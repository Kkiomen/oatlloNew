---
title: "Keys and values"
slug: keys-and-values
seo_title: "Redis SET and GET: Store and Read Key-Value Data"
seo_description: "Learn the Redis SET and GET commands: how storing and reading works, what overwriting a key does, and why every Redis key is just a string."
---

At its heart, Redis is one big dictionary. You store a **value** under a **key** with the
`SET` command, and later hand Redis the same key to `GET` the value back. That is the
whole model, and almost everything else in this course builds on it.

## How Redis stores data as key-value pairs

In most languages you have a structure that maps names to values - a PHP associative
array, a JavaScript object, a Python dict. Redis is that idea, but it lives in memory
as a server that many applications can talk to at once.

- A **key** is a name you choose, like `user:42:name` or `homepage`.
- A **value** is the data you store under that name, like `"Ada"` or `"Hello"`.

You put data in with `SET` and read it back with `GET`. You already met these in
[your first commands](/course/redis-basics/getting-started/first-commands); now let's
look at them properly.

## Storing and reading with SET and GET

Open `redis-cli` and try this:

```bash
SET greeting "Hello Redis"
GET greeting
```

```text
OK
"Hello Redis"
```

`SET` stores the value and replies `OK`. `GET` returns the value. Note the quotes around
`"Hello Redis"`: without them, `redis-cli` reads `Hello` and `Redis` as two separate
arguments and rejects the command with a wrong-number-of-arguments error. Any value
containing a space needs quoting.

If you ask for a key that was never set, Redis replies with `(nil)`, which means "there is
nothing here":

```bash
GET missing-key
```

```text
(nil)
```

`(nil)` is not an error. It is Redis telling you the key does not exist. Your code will
check for this constantly - "is it in the cache or not?"

## What happens when you SET an existing key?

`SET` does not append or merge. If the key already exists, its value is replaced
completely:

```bash
SET greeting "Hello again"
GET greeting
```

```text
OK
"Hello again"
```

The old value is gone. There is no history and no undo. This matters later for caching:
writing fresh data to a key is how you replace stale data.

## Why every Redis key is a string

Two things are worth burning into memory early.

First, **a key is always a string**. `user:42`, `homepage`, `cart:99:total` - Redis does
not care what the name looks like, it just treats it as text. The colons you will see
everywhere are a naming convention, not syntax. More on that in the
[next lesson](/course/redis-basics/keys-values-and-expiration/key-naming-conventions).

Second, the values we are storing right now are also **strings**. Redis can hold richer
structures - lists, hashes, and more - but those come later in the course. For this whole
chapter, every value is a string, and that is plenty.

Even a number is stored as a string:

```bash
SET counter 10
GET counter
```

```text
OK
"10"
```

Notice the quotes: `"10"`, not `10`. Redis knows how to do math on a string that looks
like a number (you will see that in [atomic
counters](/course/redis-basics/keys-values-and-expiration/atomic-counters)), but the
value itself is text.

## Common mistake: SET overwrites without warning

A frequent surprise for beginners: `SET` on an existing key silently overwrites it. No
warning, no "are you sure?". Run `SET session:abc ...` twice and the second write wins,
with the first value lost for good. When you want to write only if the key is new, `SET`
has an `NX` option - `SET key value NX` stores the value **only if it does not already
exist**. Reach for that when overwriting would be a bug. And notice the reply is `OK`
either way, so the command itself never tells you whether you clobbered something.

## FAQ

### Is a Redis key case-sensitive?

Yes. `Greeting`, `greeting`, and `GREETING` are three different keys. Pick one style and
stick to it.

### How big can a value be?

A single Redis string value can hold up to 512 MB. In practice you store far less - a
name, a number, a small blob of JSON - because Redis keeps everything in memory.

### What happens if I GET a key that expired?

You get `(nil)`, exactly as if the key never existed. Once a key expires, Redis treats it
as gone. Expiration is the subject of a lesson later in this chapter.
