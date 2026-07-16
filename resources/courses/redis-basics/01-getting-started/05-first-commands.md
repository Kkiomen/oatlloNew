---
title: "Your first Redis commands"
slug: first-commands
seo_title: "First Redis Commands: PING, SET, GET, DEL in redis-cli"
seo_description: "Run your first Redis commands in redis-cli: PING to check the connection, SET and GET to store and read a value, and DEL to remove it."
---

## Getting back to the redis-cli prompt

Time for your first Redis commands. In the last lesson we started Redis with Docker and opened a shell, so let's get back to it:

```bash
docker exec -it redis redis-cli
```

You should see the interactive prompt:

```text
127.0.0.1:6379>
```

Everything in this lesson is typed at that prompt. It is interactive: you type a command, press Enter, and Redis replies right away. No files, no save button. Let's learn four commands.

## PING: is Redis there?

We met this one already. It is the quickest way to check the connection:

```bash
PING
```

Redis answers:

```text
PONG
```

If you see `PONG`, you are connected and ready. Commands in Redis are not case-sensitive, so `PING` and `ping` both work. This course writes them in uppercase by convention.

## SET: store a value in Redis

Remember from the first lesson that Redis is a key-value store: a name points to a value. `SET` creates that link. Store a value under a key:

```bash
SET greeting "hello"
```

Redis replies:

```text
OK
```

Let's read the parts:

- `SET` is the command.
- `greeting` is the **key**, the name you will look it up by.
- `"hello"` is the **value** you are storing.

`OK` means it worked. You just wrote your first piece of data to Redis.

## GET: read a value back by its key

Now ask for it by key:

```bash
GET greeting
```

Redis returns the value:

```text
"hello"
```

That is the whole loop of a key-value store: `SET` puts a value in under a key, `GET` reads it back out. If you `GET` a key that does not exist, Redis returns `(nil)`, its way of saying "nothing is stored here":

```bash
GET missing
```

```text
(nil)
```

Watch one edge here: `(nil)` means the key does not exist, but an empty value is not the same thing. Run `SET blank ""` and then `GET blank`, and Redis hands back an empty `""`, not `(nil)`. The key is there; it just holds nothing. When you check whether something was ever stored, "empty" and "missing" are two different answers.

## DEL: remove a value

When you no longer need a key, delete it:

```bash
DEL greeting
```

Redis replies with the number of keys it removed:

```text
(integer) 1
```

`1` means one key was deleted. If the key was not there, you get `0`. Read again to confirm it is gone:

```bash
GET greeting
```

```text
(nil)
```

Gone. You have now created, read, and removed data in Redis.

## Common mistake

If your value has a space in it, wrap it in quotes: `SET note "buy milk"`. Without the quotes, Redis treats each word as a separate argument and you get an error about the wrong number of arguments. Single words like `hello` do not need quotes, but using them is always safe.

## What you can do now

With just these four commands you can already store and fetch data. In the next chapter we will go deeper: naming keys well, and making them expire on their own so temporary data cleans itself up.

## FAQ

### Are Redis commands case-sensitive?

The command names are not: `SET` and `set` behave the same. But your keys and values are case-sensitive, so `greeting` and `Greeting` are two different keys.

### What does (nil) mean?

It means there is no value for that key, either because it was never set or because it was deleted. It is Redis saying "nothing here".

### How do I leave the redis-cli prompt?

Type `exit` or press `Ctrl+D`. Your Redis container keeps running in the background, so your data stays until you stop the container.

### Does SET overwrite an existing key?

Yes. If the key already has a value, `SET` replaces it with the new one without warning.
