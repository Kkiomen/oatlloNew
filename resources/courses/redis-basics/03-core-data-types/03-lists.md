---
title: "Lists"
slug: lists
seo_title: "Redis Lists as a Queue or Stack: LPUSH, LPOP"
seo_description: "Redis lists are ordered collections you can drive as a queue or a stack. Learn LPUSH, RPUSH, LPOP, RPOP, and LRANGE with clear redis-cli examples."
---

## What a Redis list is

Redis lists are sequences of values kept in order under one key. Picture a line of items with a left end and a right end; you add and remove from either side.

Order is the whole point. Whatever order you push items in is the order you get them back. That is what makes a list work as a queue (first in, first out) or a stack (last in, first out), depending on which ends you use.

## LPUSH and RPUSH: add to either end

`LPUSH` adds to the left (the front). `RPUSH` adds to the right (the back).

```bash
RPUSH tasks "email"
RPUSH tasks "resize"
RPUSH tasks "notify"
```

```text
(integer) 3
```

Each push returns the new length of the list. After those three commands the list, left to right, is:

```text
email  resize  notify
```

## LRANGE: read items by position

`LRANGE` returns a range of items by position. Positions start at `0` on the left. Use `-1` to mean the last item.

```bash
LRANGE tasks 0 -1
```

```text
1) "email"
2) "resize"
3) "notify"
```

So `0 -1` means "from the first to the last", a common way to see the whole list. `LRANGE` only reads. It does not remove anything.

## LPOP and RPOP: remove from either end

`LPOP` removes and returns the leftmost item. `RPOP` removes and returns the rightmost item.

```bash
LPOP tasks
```

```text
"email"
```

The item is gone from the list now. The list is `resize notify`.

## Use a list as a FIFO queue

Push on one end, pop from the other, and you have a first-in, first-out queue.

```bash
RPUSH jobs "job1"
RPUSH jobs "job2"
LPOP jobs
```

```text
"job1"
```

Producers `RPUSH` work onto the back. A worker `LPOP`s the oldest item off the front. The first job added is the first one handled.

The queue-versus-stack behaviour hangs entirely on which ends you touch. Pick a direction and stick to it. If one part of your code pushes with `RPUSH` and another accidentally pops with `RPOP`, the newest job jumps ahead of the older ones and your "queue" quietly serves work in the wrong order, with nothing throwing an error to warn you.

## Use a list as a LIFO stack

Push and pop from the same end, and you have a last-in, first-out stack.

```bash
LPUSH history "page-a"
LPUSH history "page-b"
LPOP history
```

```text
"page-b"
```

The most recently added item comes out first, like an "undo" history.

## Common mistake

Do not use `LRANGE key 0 -1` on a huge list just to grab a few items. It copies every element back to your app. If you only need the newest ten, ask for them directly with a small range like `LRANGE key 0 9`.

## FAQ

### What is the difference between LPUSH and RPUSH?

`LPUSH` adds to the left (front) of the list. `RPUSH` adds to the right (back). The only difference is which end grows.

### How do I make a first-in, first-out queue?

Push on one end and pop from the other, for example `RPUSH` to add and `LPOP` to remove. The oldest item always comes out first.

### What happens if I pop from an empty list?

You get a nil (empty) reply, and the key is removed once its last item is gone.
