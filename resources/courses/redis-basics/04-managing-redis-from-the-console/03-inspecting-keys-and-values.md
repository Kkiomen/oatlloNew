---
title: "Inspecting keys and values"
slug: inspecting-keys-and-values
seo_title: "Redis: How to Inspect a Key's Type, TTL and Value"
seo_description: "Inspect any Redis key from the console: EXISTS, TYPE, TTL, OBJECT ENCODING, MEMORY USAGE, and how to read strings, hashes, lists, sets and sorted sets."
---

## How to check the value of a Redis key

You found a key with `SCAN`. Now you want to check its value. The catch: you cannot just `GET` everything. `GET` only works on strings, and Redis throws an error if you point it at a list or a hash. So to read the value of a Redis key you first figure out what type of key it is, then reach for the matching command.

## Check if a key exists and what type it holds

`EXISTS` tells you if a key is there. It returns `1` for yes, `0` for no:

```text
127.0.0.1:6379> EXISTS user:42:name
(integer) 1
```

`TYPE` tells you which data type it holds:

```text
127.0.0.1:6379> TYPE user:42:name
string
```

Possible answers are `string`, `hash`, `list`, `set`, and `zset` (a sorted set), the types you met in [Core data types](/course/redis-basics/core-data-types/strings). The type decides which read command to use.

## How long until it expires

`TTL` shows the remaining time to live in seconds:

```text
127.0.0.1:6379> TTL session:abc
(integer) 274
```

Two answers are special and worth memorising:

- `-1` means the key exists but has no expiry, it lives forever.
- `-2` means the key does not exist (it may have already expired).

We covered this in [Expiration and TTL](/course/redis-basics/keys-values-and-expiration/expiration-and-ttl); `TTL` is how you check it from the console.

## Reading the value, by type

Once `TYPE` tells you what you have, use the matching command:

```text
string   GET key
hash     HGETALL key
list     LRANGE key 0 -1
set      SMEMBERS key
zset     ZRANGE key 0 -1 WITHSCORES
```

Some real output:

```text
127.0.0.1:6379> GET user:42:name
"Ada"

127.0.0.1:6379> HGETALL user:42
1) "name"
2) "Ada"
3) "email"
4) "ada@example.com"

127.0.0.1:6379> LRANGE queue:emails 0 -1
1) "job-1"
2) "job-2"

127.0.0.1:6379> SMEMBERS tags:post:9
1) "redis"
2) "php"

127.0.0.1:6379> ZRANGE leaderboard 0 -1 WITHSCORES
1) "ada"
2) "10"
3) "bob"
4) "25"
```

The `0 -1` on lists and sorted sets means "from the first element to the last": `0` is the first index and `-1` is the last, so it reads the whole thing. `WITHSCORES` asks the sorted set to include each member's score next to it.

Worth remembering: `LRANGE key 0 -1` on a huge list is the same trap as `KEYS *`. It is an O(N) call that has to serialize every element in one pass, and Redis is busy the whole time. On a list with a handful of items it is nothing; on one holding a million jobs it stalls the server. When you only need a peek, ask for a slice, like `LRANGE queue:emails 0 9` for the first ten.

## Going deeper: encoding and memory

Two commands help when you care about performance or size.

`OBJECT ENCODING` reveals how Redis stores the value internally:

```text
127.0.0.1:6379> OBJECT ENCODING user:42
"listpack"
```

Small hashes, lists, and sets use compact encodings like `listpack` or `intset` to save memory; they switch to a bigger encoding once they grow. You rarely need this, but it is useful when tuning.

`MEMORY USAGE` reports how many bytes a key takes, including its overhead:

```text
127.0.0.1:6379> MEMORY USAGE user:42
(integer) 104
```

Handy when hunting for keys that eat too much RAM.

## Common mistake

Running `GET` on a key that is not a string. If `GET` returns:

```text
(error) WRONGTYPE Operation against a key holding the wrong kind of value
```

you skipped the `TYPE` check. Always ask `TYPE` first, then pick the right read command from the table above.

## FAQ

### How do I check what type a Redis key is?

Run `TYPE key`. It returns `string`, `hash`, `list`, `set`, or `zset`, which tells you whether to read it with `GET`, `HGETALL`, `LRANGE`, `SMEMBERS`, or `ZRANGE`.

### How do I read a whole list or sorted set in Redis?

Use `LRANGE key 0 -1` for a list and `ZRANGE key 0 -1 WITHSCORES` for a sorted set. The `0 -1` range means first element to last, so you get everything.

### What does TTL -1 or -2 mean?

`-1` means the key exists but never expires. `-2` means the key does not exist at all (often because it already expired).

### Why does GET return a WRONGTYPE error?

Because the key is not a string. `GET` only reads strings. Check `TYPE` first, then use the matching command for that type.
