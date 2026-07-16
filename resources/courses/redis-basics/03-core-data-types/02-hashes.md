---
title: "Hashes"
slug: hashes
seo_title: "Redis Hashes: HSET, HGET, HGETALL, HDEL Guide"
seo_description: "Redis hashes store many fields under one key, like an object or a row. Learn HSET, HGET, HGETALL, and HDEL with plain redis-cli examples for developers."
---

## What a Redis hash holds

Redis hashes store a set of field-value pairs under a single key. If a [string](/course/redis-basics/core-data-types/strings) is one box with one thing in it, a hash is one box with labelled compartments inside.

That shape fits an object or a database row exactly: one user, with a name, an email, and a role, all under one key.

```text
user:42
  name  -> "Ada"
  email -> "ada@example.com"
  role  -> "admin"
```

## HSET: write fields

`HSET` sets one or more fields on a hash. If the key does not exist, Redis creates it.

```bash
HSET user:42 name "Ada" email "ada@example.com" role "admin"
```

```text
(integer) 3
```

The number returned is how many new fields were added.

## HGET: read one field

`HGET` pulls back a single field.

```bash
HGET user:42 email
```

```text
"ada@example.com"
```

You get just the field you asked for, not the whole object. That is the big win over a JSON string.

## HGETALL: read the whole object

`HGETALL` returns every field and value in the hash.

```bash
HGETALL user:42
```

```text
1) "name"
2) "Ada"
3) "email"
4) "ada@example.com"
5) "role"
6) "admin"
```

Fields and values come back alternating: field, value, field, value. That is fine for a small user record. On a hash with thousands of fields it is a different story, since `HGETALL` walks every field and ships them all back at once. When you only need two or three, `HGET` each one instead and leave the rest on the server.

## HDEL: remove a field

`HDEL` removes one or more fields from the hash.

```bash
HDEL user:42 role
```

```text
(integer) 1
```

The rest of the hash stays exactly as it was. When you delete the last field, Redis removes the whole key.

## When to use a hash over many string keys

You could store the same data as separate string keys:

```bash
SET user:42:name "Ada"
SET user:42:email "ada@example.com"
```

That works, but a hash is usually better when the fields belong to one thing:

- **One key to manage.** Set a [TTL](/course/redis-basics/keys-values-and-expiration/expiration-and-ttl) on `user:42` and the whole object expires together.
- **Read it all at once.** `HGETALL` fetches the object in one command instead of several.
- **Less memory.** Redis stores small hashes very compactly.

Reach for a hash when you have an object or a row. Reach for separate string keys when the values are truly independent.

## Common mistake

Do not set an expiry on individual fields. In Redis, TTL lives on the key, not on each field inside a hash. If you need different fields to expire at different times, they probably should not share one hash. Give them their own keys.

## FAQ

### How is a hash different from a JSON string?

With a JSON string you must read and rewrite the whole value to change one field. With a hash you can read or update a single field directly using `HGET` and `HSET`.

### Can a hash field hold another hash?

No. Hash values are flat strings. For nested data you either flatten the keys or store a JSON string in the field.

### What does HGETALL return if the key does not exist?

An empty result. Redis treats a missing hash the same as an empty one.
