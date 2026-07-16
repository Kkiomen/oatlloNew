---
title: "Troubleshooting Redis"
slug: troubleshooting
seo_title: "Redis Troubleshooting: Connection refused, NOAUTH, OOM"
seo_description: "Fix common Redis errors: Connection refused, NOAUTH, OOM maxmemory, WRONGTYPE, and slow commands - and diagnose them with INFO, MONITOR, and SLOWLOG."
---

Redis rarely fails quietly. When something is wrong, it usually tells you with a specific
error message - `Connection refused`, `NOAUTH`, an `OOM` warning, `WRONGTYPE`. This final
lesson walks through the Redis errors you are most likely to hit, what each one actually
means, and how to fix it, plus the three console tools that turn a vague "Redis seems broken"
into an exact diagnosis.

## Connection refused

```text
Connection refused [tcp://127.0.0.1:6379]
```

This means nothing is listening at the host and port you tried. Redis is not "broken" -
your app never reached it. Check these, in order:

- **Is Redis running?** Ping it: `redis-cli ping` should return `PONG`. Under Docker,
  `docker compose ps` should show the redis service as up.
- **Wrong host.** The classic Docker trap. Inside a container, `REDIS_HOST=localhost`
  points at the container itself, not the Redis service. Under Compose the host is the
  **service name** (`redis`), as covered in the
  [Docker stack lesson](/course/redis-basics/beyond-cache-and-production/a-laravel-redis-docker-stack).
- **Wrong port.** Redis listens on `6379` by default. If you changed it or did not publish
  it, the address will not match.

Fix the host and port so they point at where Redis actually is, and the connection comes
back.

## NOAUTH Authentication required

```text
NOAUTH Authentication required.
```

Redis has a password set (`requirepass`), and the client did not send it. The connection
succeeded - it is the authentication that failed. Either you forgot the password or it does
not match.

In `redis-cli`, pass it with `-a`:

```bash
redis-cli -a your-password
```

In Laravel, set `REDIS_PASSWORD` in `.env` to the same value Redis was started with. If you
launched Redis with `redis-server --requirepass ...`, the app's password must match exactly.
This ties straight back to [securing Redis](/course/redis-basics/beyond-cache-and-production/securing-redis).
A related error, `WRONGPASS`, means you sent a password but it is wrong.

## OOM command not allowed when used memory > maxmemory

```text
OOM command not allowed when used memory > 'maxmemory'.
```

Redis is full. It has hit its `maxmemory` limit and cannot accept a write that would use
more memory. What happens next depends on your eviction policy:

- With an eviction policy like `allkeys-lru`, Redis drops old keys to make room and you may
  not see this error at all - which is what you want for a pure cache.
- With `noeviction` (the default), Redis refuses new writes and returns this OOM error.

If Redis is your cache, set a `maxmemory` and an eviction policy so it discards old entries
instead of erroring. If you are storing data you cannot afford to lose, the fix is more
memory or fewer keys, not eviction. This is exactly the trade-off from the
[eviction policies lesson](/course/redis-basics/beyond-cache-and-production/eviction-policies).
Check current usage with:

```bash
redis-cli info memory
```

Look at `used_memory_human` and `maxmemory_human`.

## WRONGTYPE Operation against a key holding the wrong kind of value

```text
WRONGTYPE Operation against a key holding the wrong kind of value
```

You ran a command meant for one data type against a key that holds another. For example,
running `GET` (a string command) on a key that is actually a hash:

```bash
GET user:42
```

```text
(error) WRONGTYPE Operation against a key holding the wrong kind of value
```

The key exists, but it is the wrong type for that command. Check what type it really is:

```bash
TYPE user:42
```

```text
hash
```

Now use the matching command - `HGETALL user:42` for a hash. This is usually a bug in your
code (or a key-naming collision where two features reuse the same key for different types).
Revisit the [core data types](/course/redis-basics/core-data-types/strings) and the
[naming conventions](/course/redis-basics/keys-values-and-expiration/key-naming-conventions)
lesson to keep types and keys straight.

## Slow commands: KEYS and big values

Redis is single-threaded, so one slow command blocks every other client until it finishes.
Two things cause this most often:

- **`KEYS *` on a real database.** It scans every key in one blocking pass. On a big
  keyspace it can freeze Redis for seconds. Use `SCAN` instead, which walks the keyspace in
  small batches - this is exactly why the console chapter taught
  [SCAN vs KEYS](/course/redis-basics/managing-redis-from-the-console/finding-keys-scan-vs-keys).
- **Huge values.** Fetching or deleting a multi-megabyte string, or a list with millions of
  entries, takes real time. Keep values small and split large collections.

If Redis feels laggy, a rogue `KEYS` or an oversized value is the first suspect.

## Diagnosing: INFO, MONITOR, SLOWLOG

When the error message is not enough, three tools from the
[console chapter](/course/redis-basics/managing-redis-from-the-console/server-info-and-monitoring)
tell you what Redis is doing.

**INFO** gives you a full health snapshot - memory, connected clients, hit rate, uptime:

```bash
redis-cli info
```

Pass a section to narrow it down, like `info memory` for the OOM issue above or
`info clients` to see how many clients are connected.

**MONITOR** streams every command Redis receives, live:

```bash
redis-cli monitor
```

```text
1690000000.123456 [0 172.18.0.3:5140] "GET" "cache:user:42"
1690000000.234567 [0 172.18.0.3:5140] "SETEX" "cache:user:42" "3600" "..."
```

It is perfect for "what is my app actually sending?" - you will spot the `KEYS *` or the
`WRONGTYPE`-causing command in real time. Because it shows everything, only run it briefly
on a busy server, and never leave it running. One thing to keep in mind: MONITOR prints
`AUTH` commands too, password and all, in plaintext. Avoid running it where the output could
be logged or shared, and never paste a raw MONITOR dump into a bug report.

**SLOWLOG** records commands that took longer than a threshold, so you can find slow
queries after the fact without watching live:

```bash
redis-cli slowlog get 10
```

That returns the ten most recent slow commands, each with how long it took and the exact
arguments. This is how you catch the occasional slow command that MONITOR would make you
wait for.

## Common mistake

Reaching for `FLUSHALL` or a restart the moment something breaks. That wipes your evidence
(and your data) without telling you what was wrong, and the problem comes straight back.
Read the error message first - Redis names the exact failure - then use `INFO`, `MONITOR`,
or `SLOWLOG` to confirm. Almost every issue in this lesson is diagnosed, not guessed.

## FAQ

### My app worked locally but gets Connection refused in Docker. Why?

Almost always `REDIS_HOST`. Locally Redis is on `localhost`; inside a container `localhost`
is the container itself. Set `REDIS_HOST` to the compose **service name** (`redis`). See the
[Docker stack lesson](/course/redis-basics/beyond-cache-and-production/a-laravel-redis-docker-stack).

### Is it safe to run MONITOR in production?

For a few seconds, yes - to see what is being sent right now. But MONITOR shows every
command from every client, so it adds load and can flood your terminal on a busy server.
Use it briefly, then stop it. For finding slow commands over time, use `SLOWLOG` instead,
which has almost no overhead.

### How do I stop getting OOM errors?

Decide what Redis is for. If it is a cache, set a `maxmemory` limit and an eviction policy
(like `allkeys-lru`) so it drops old keys instead of refusing writes. If it holds data you
cannot lose, add memory or reduce how much you store - do not evict. The
[eviction policies lesson](/course/redis-basics/beyond-cache-and-production/eviction-policies)
walks through the choice.
