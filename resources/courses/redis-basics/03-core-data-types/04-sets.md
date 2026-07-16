---
title: "Sets"
slug: sets
seo_title: "Redis Sets: SADD, SISMEMBER, SINTER, SUNION"
seo_description: "Redis sets hold unique, unordered members and answer membership tests instantly. Learn SADD, SISMEMBER, SINTER, and SUNION with redis-cli examples."
---

## What makes a Redis set unique

Redis sets are collections where every member is unique and nothing is kept in order. Add the same value twice and only one copy survives. Ask for the members back and you cannot count on any particular order.

Two traits make the type earn its keep: it never holds duplicates, and it answers "is this in the set?" instantly. Reach for it for tags, unique visitors, or the "who liked this post" list.

## SADD: add members

`SADD` adds one or more members to a set.

```bash
SADD post:5:likes "user:1" "user:2"
SADD post:5:likes "user:1"
```

```text
(integer) 2
(integer) 0
```

The first command added two members and returned `2`. The second tried to add `user:1` again, but it was already there, so it added `0` new members. The set stays clean with no duplicates.

## SMEMBERS: list every member

`SMEMBERS` returns every member of the set.

```bash
SMEMBERS post:5:likes
```

```text
1) "user:1"
2) "user:2"
```

Remember the order is not guaranteed. Do not rely on it.

One habit worth forming early: `SMEMBERS` pulls the entire set across the wire, so on a set with a million members it is a heavy call. When you only want to know whether one value is present, use `SISMEMBER`. When you only want the count, use `SCARD`. Save `SMEMBERS` for sets you know are small.

## SISMEMBER: check membership in one call

`SISMEMBER` checks whether one value is a member. It returns `1` for yes and `0` for no.

```bash
SISMEMBER post:5:likes "user:1"
```

```text
(integer) 1
```

This is very fast, even on a set with millions of members. It is the reason sets beat lists for "have I seen this before" checks.

## SCARD: how many members

`SCARD` returns the count of members in the set.

```bash
SCARD post:5:likes
```

```text
(integer) 2
```

Perfect for a "42 people liked this" number without pulling the whole set back.

## SREM: remove members

`SREM` removes one or more members.

```bash
SREM post:5:likes "user:1"
```

```text
(integer) 1
```

It returns how many members were actually removed.

## Combine sets with SINTER and SUNION

Because members are unique, Redis can combine two sets in one command.

`SINTER` returns the members that are in both sets (the intersection).

```bash
SADD editors "ada" "ben" "cara"
SADD admins  "ben" "cara" "dan"
SINTER editors admins
```

```text
1) "ben"
2) "cara"
```

`SUNION` returns every member from both sets combined, with duplicates removed.

```bash
SUNION editors admins
```

```text
1) "ada"
2) "ben"
3) "cara"
4) "dan"
```

These let Redis answer questions like "who is both an editor and an admin" without any work in your app.

## Common mistake

Do not use a set when order matters. Sets throw order away by design. If you need items kept in the order you added them, use a [list](/course/redis-basics/core-data-types/lists). If you need them ordered by a score or rank, use a sorted set (next lesson).

## FAQ

### What happens if I add the same member twice?

Nothing changes. Redis keeps one copy and reports that zero new members were added.

### Are set members kept in order?

No. Sets are unordered. If you need order, use a list or a sorted set.

### How do I quickly check if a value is in a set?

Use `SISMEMBER`. It returns `1` if the value is present and `0` if not, and it stays fast even on very large sets.
