---
title: "Strings"
slug: strings
seo_title: "Redis Strings: APPEND, STRLEN, GETSET Explained"
seo_description: "Redis strings hold text, numbers, or JSON up to 512MB. Learn APPEND, STRLEN, and GETSET with copy-paste redis-cli examples built for developers."
---

## What a Redis string actually stores

Redis strings are the value you already met with `SET` and `GET`, and the simplest of the five types. Despite the name, they are not limited to words. A string is a sequence of bytes, so one happily holds text, a counter, or a whole JSON document.

```bash
SET user:42:name "Ada"
GET user:42:name
```

```text
"Ada"
```

One key, one value. That is the string type in a nutshell. Everything below is about doing more with it.

## Do math on a string that looks like a number

When a string looks like a whole number, Redis lets you do math on it directly. You saw `INCR` back in [atomic counters](/course/redis-basics/keys-values-and-expiration/atomic-counters).

```bash
SET views 10
INCR views
```

```text
(integer) 11
```

The value is still stored as a string. Redis just knows how to read it as a number when you ask it to. There is no separate "number" type to worry about.

## APPEND: grow a string in place

`APPEND` sticks text onto the end of an existing string. If the key does not exist yet, it acts like `SET`.

```bash
SET log "start"
APPEND log " > step1"
GET log
```

```text
"start > step1"
```

`APPEND` returns the new length of the string, not the string itself.

## STRLEN: measure the value in bytes

`STRLEN` returns the length of the string in bytes.

```bash
SET user:42:name "Ada"
STRLEN user:42:name
```

```text
(integer) 3
```

Handy for a quick check without pulling the whole value back over the network. One catch worth remembering: the count is bytes, not characters. A name written in a non-Latin script can be several bytes per letter, so `STRLEN` on it will read higher than the number of visible characters.

## GETSET: read and replace atomically

`GETSET` sets a new value and returns the old one, all in a single atomic operation.

```bash
SET counter 5
GETSET counter 0
```

```text
"5"
```

The key now holds `0`, and you got the previous `5` back. This is useful for "read the current total and reset it" in one move, with no gap where another client could sneak in between a `GET` and a `SET`.

## Store a small object as a JSON string

A common pattern is to keep a small object as a JSON string.

```bash
SET user:42 '{"name":"Ada","role":"admin"}'
GET user:42
```

```text
"{\"name\":\"Ada\",\"role\":\"admin\"}"
```

Redis stores the JSON as plain text. It does not understand the fields inside. To change one field you must read the whole value, parse it in your app, edit it, and write it all back. If you need to update fields individually, a hash (next lesson) is the better fit.

## How big a Redis string can get

A single Redis string can hold up to 512MB. That is enormous for a key-value store, far more than you need for a name, a counter, or a JSON blob. In practice you will never come close, and you should keep values small so they move quickly over the network.

## Common mistake

Do not store a large, frequently edited object as one JSON string. Every small change means reading the entire value and writing it all back, which is wasteful and can overwrite a change another request just made. For structured data with separate fields, reach for a hash instead.

## FAQ

### Is a Redis string only for text?

No. It is a byte sequence, so it holds text, numbers, or serialized data like JSON. The name is historical.

### What is the difference between SET and APPEND?

`SET` replaces the whole value. `APPEND` adds to the end of the existing value and creates it if it is missing.

### Why use GETSET instead of GET then SET?

`GETSET` is atomic. No other client can change the value between reading the old one and writing the new one, so you never lose an update.
