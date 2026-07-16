---
title: "Sorted sets"
slug: sorted-sets
seo_title: "Redis Sorted Set Leaderboard: ZADD, ZRANGE, ZSCORE"
seo_description: "Build a Redis sorted set leaderboard: unique members ranked by score, kept sorted for you. Learn ZADD, ZRANGE, ZREVRANGE, ZSCORE, and ZRANK."
---

## What a Redis sorted set adds

A Redis sorted set is the [set](/course/redis-basics/core-data-types/sets) from the last lesson with one addition: every member carries a number called a score, and Redis keeps the members sorted by it.

You get both properties at once. Members stay unique, and they are always ordered from lowest score to highest. That combination is exactly what a leaderboard, a ranking, or a priority queue needs.

## ZADD: add members with scores

`ZADD` adds a member along with its score. The score comes first, then the member.

```bash
ZADD leaderboard 100 "ada"
ZADD leaderboard 250 "ben"
ZADD leaderboard 175 "cara"
```

```text
(integer) 1
(integer) 1
(integer) 1
```

Even though we added them out of order, Redis stores them sorted by score: ada (100), cara (175), ben (250).

Add an existing member again and you update its score instead of creating a duplicate.

```bash
ZADD leaderboard 300 "ada"
```

```text
(integer) 0
```

The `0` means no new member was added. Ada's score just changed to 300.

Keep one thing in mind about scores: Redis stores them as floating-point numbers, not exact integers. For points, view counts, and timestamps that is fine. But if you try to use a very large integer as a score, say a 19-digit ID, it can lose precision past about 15-16 significant digits and two different IDs may collide on the same score. Scores are for ranking, not for stashing exact large integers.

## ZRANGE: read in score order

`ZRANGE` returns members by position, ordered from lowest score to highest. Positions start at `0`, and `-1` means the last one.

```bash
ZRANGE leaderboard 0 -1
```

```text
1) "cara"
2) "ben"
3) "ada"
```

Add `WITHSCORES` to see the scores too.

```bash
ZRANGE leaderboard 0 -1 WITHSCORES
```

```text
1) "cara"
2) "175"
3) "ben"
4) "250"
5) "ada"
6) "300"
```

## ZREVRANGE: top scores first

A leaderboard usually wants the top score first. `ZREVRANGE` is the same as `ZRANGE` but ordered from highest score to lowest.

```bash
ZREVRANGE leaderboard 0 2 WITHSCORES
```

```text
1) "ada"
2) "300"
3) "ben"
4) "250"
5) "cara"
6) "175"
```

That `0 2` gives you the top three, the classic "leaderboard top N".

## ZSCORE: one member's score

`ZSCORE` returns the score of a single member.

```bash
ZSCORE leaderboard "ben"
```

```text
"250"
```

## ZRANK: one member's position

`ZRANK` returns the position of a member, counting from the lowest score at position `0`.

```bash
ZRANK leaderboard "ada"
```

```text
(integer) 2
```

Ada has the highest score, so from the bottom she is at position `2`. For the position counting from the top (rank 1 on the board), use `ZREVRANK`.

## Build a leaderboard with sorted sets

Put it together and a full leaderboard is just a few commands:

- `ZADD` to set or update a player's score.
- `ZREVRANGE ... 0 9` to show the top ten.
- `ZSCORE` to show one player's points.
- `ZREVRANK` to show "you are ranked #4".

Redis keeps everything sorted for you as scores change, with no re-sorting in your app.

## Common mistake

Do not confuse position with score. `ZRANK` gives a position in the ordering (0, 1, 2...), while `ZSCORE` gives the actual number you stored. They are different questions: "where does this member sit?" versus "how many points does it have?".

## FAQ

### How is a sorted set different from a set?

A plain set is unordered and has no scores. A sorted set attaches a score to every member and keeps them ordered by that score.

### What happens if I ZADD a member that already exists?

Its score is updated to the new value. No duplicate is created, and Redis reports zero new members added.

### How do I get the top scores first?

Use `ZREVRANGE`, which orders from highest score to lowest. For example `ZREVRANGE key 0 9` returns the top ten.
