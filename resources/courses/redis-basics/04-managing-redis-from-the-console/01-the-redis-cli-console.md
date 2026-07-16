---
title: "The redis-cli console"
slug: the-redis-cli-console
seo_title: "redis-cli: How to Connect to Redis From the Console"
seo_description: "Connect to Redis with redis-cli: locally, in Docker, or a remote host. Learn the interactive prompt, one-shot commands, SELECT, and DBSIZE."
---

## How to connect to Redis with redis-cli

Sooner or later you need to poke at a live Redis by hand: check if a key is there, look at a value, clear a cache. The tool for that is `redis-cli`, the command-line client that ships with Redis. To connect to Redis you run `redis-cli` and it drops you at an interactive prompt where every command from earlier chapters works.

You met the client briefly in [Run Redis with Docker](/course/redis-basics/getting-started/run-redis-with-docker). This is the proper tour: connecting locally, into a Docker container, and to a remote host.

## Connect to Redis locally

If Redis runs on the same machine, just type:

```bash
redis-cli
```

You get a prompt that shows the host, port, and current database:

```text
127.0.0.1:6379>
```

That means you are connected to Redis on `localhost`, port `6379` (the default), database `0`. Type a command and press Enter:

```text
127.0.0.1:6379> PING
PONG
```

`PONG` means the server is alive and answering. Type `exit` (or press Ctrl+D) to leave.

## Connect to redis-cli in a Docker container

When Redis runs inside a Docker container, run `redis-cli` inside that container:

```bash
docker exec -it redis redis-cli
```

Here `redis` is the container name (whatever you called it with `--name`), `-it` gives you an interactive terminal, and the second `redis-cli` is the command to run inside. You land on the same prompt as before.

## Connect to a remote Redis host

To reach a Redis on a different machine, pass the host, port, and password:

```bash
redis-cli -h HOST -p 6379 -a PASSWORD
```

- `-h` is the hostname or IP address of the server.
- `-p` is the port (`6379` unless someone changed it).
- `-a` is the password, if the server requires one.

Redis warns that passing `-a` on the command line is not fully secret (it can show up in your shell history). For quick jobs that is fine; for anything sensitive, leave `-a` off and Redis will prompt you for the password.

## Interactive prompt vs one-shot

There are two ways to run a command.

Interactive: open the prompt, then type commands one after another. Good for exploring.

One-shot: put the command right after `redis-cli` and it runs, prints the answer, and exits:

```bash
redis-cli GET foo
```

One-shot is what you use in scripts and when you only need a single answer. Everything you can type at the prompt also works as a one-shot argument.

One thing to watch: a one-shot always runs on database `0`. There is no prompt to hold a `SELECT`, so if your data lives in another database you must add `-n`, as in `redis-cli -n 1 GET foo`. Miss that flag and the command answers about a keyspace you never meant to touch.

## Switching between the 16 databases

A single Redis server has 16 numbered databases, `0` to `15`. They are separate keyspaces: a key in database `0` is invisible from database `1`. You start in `0`.

Switch with `SELECT`:

```text
127.0.0.1:6379> SELECT 1
OK
127.0.0.1:6379[1]>
```

The prompt now shows `[1]`, so you always know where you are. To count the keys in the current database:

```text
127.0.0.1:6379[1]> DBSIZE
(integer) 0
```

Two more handy prompt commands:

- `CLEAR` wipes the terminal screen (like `clear` in your shell). It does not touch data.
- `redis-cli --help` lists every flag the client accepts.

## Common mistake

Forgetting which database you are on. If a `GET` returns `(nil)` for a key you are sure exists, check the prompt: you may have `SELECT`ed a different database. Run `DBSIZE` to confirm whether the current database has any keys at all before assuming the data is gone.

## FAQ

### How do I connect to Redis running in Docker?

Run `docker exec -it redis redis-cli`, replacing `redis` with your container's name. This runs the client inside the container and drops you at the prompt.

### What is the default Redis port?

`6379`. `redis-cli` uses it automatically, so you only need `-p` when the server listens on a different port.

### How many databases does Redis have?

Sixteen by default, numbered `0` through `15`. Use `SELECT n` to switch and `DBSIZE` to count keys in the one you are on.

### How do I run a single Redis command without opening the prompt?

Put the command after `redis-cli`, for example `redis-cli GET foo`. It runs once, prints the result, and exits.
