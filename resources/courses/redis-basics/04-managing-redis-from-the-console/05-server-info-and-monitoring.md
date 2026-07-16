---
title: "Server info and monitoring"
slug: server-info-and-monitoring
seo_title: "Redis Monitoring: INFO, MONITOR, SLOWLOG and CLI Flags"
seo_description: "Watch a live Redis: read INFO memory and keyspace stats, stream commands with MONITOR, check SLOWLOG, and use --stat, --bigkeys, and --latency."
---

## How to monitor a Redis server from the console

Deleting and inspecting keys tells you about data. To monitor a Redis server you ask different questions: how much memory it holds, what commands it is running right now, and which ones are slow. This lesson covers the four tools for that, `INFO`, `MONITOR`, `SLOWLOG`, and a set of `redis-cli` flags, the ones you reach for when something feels wrong.

## INFO: the health snapshot

`INFO` dumps a big report about the server, grouped into sections:

```text
127.0.0.1:6379> INFO
# Server
redis_version:7.2.4
# Memory
used_memory_human:1.51M
# Keyspace
db0:keys=42,expires=3,avg_ttl=0
```

The full output is long, so ask for one section at a time:

```text
127.0.0.1:6379> INFO memory
127.0.0.1:6379> INFO keyspace
127.0.0.1:6379> INFO stats
```

The three you will use most:

- `memory`: `used_memory_human` is how much RAM Redis holds right now. Watch this to catch leaks or runaway growth.
- `keyspace`: how many keys each database holds, and how many have an expiry. A quick way to see if data is where you expect.
- `stats`: totals like `keyspace_hits` and `keyspace_misses` (how often lookups found data), plus `total_commands_processed`.

One subtlety with those hit and miss numbers: they are running totals counted since the server last started, not a rate. A single reading tells you almost nothing. To judge whether your hit ratio is actually good, take two `INFO stats` samples a minute apart and compare the difference; that delta is the behaviour over that minute, which is what you care about.

## MONITOR: watch commands live

`MONITOR` streams every command the server handles, as it happens:

```text
127.0.0.1:6379> MONITOR
OK
1690000000.123 [0 127.0.0.1:51000] "GET" "user:42:name"
1690000000.456 [0 127.0.0.1:51000] "SET" "session:abc" "..."
```

It is fantastic for debugging: you see exactly what your application is asking Redis to do. Press Ctrl+C to stop.

The cost: `MONITOR` shows you everything, so on a busy server it produces a flood of output and adds real load (Redis has to copy every command to your stream). Use it for a few seconds to catch a problem, then quit. Never leave it running on production.

## SLOWLOG: find slow commands

Redis records commands that took longer than a threshold in the slow log. Read the recent entries:

```text
127.0.0.1:6379> SLOWLOG GET 10
1) 1) (integer) 0
   2) (integer) 1690000000
   3) (integer) 15000
   4) 1) "KEYS"
      2) "*"
```

`10` asks for the last ten entries. Each entry shows an id, a timestamp, the duration in microseconds (`15000` here is 15 ms), and the command itself. Notice the culprit above: someone ran `KEYS *`, exactly the mistake from the finding-keys lesson. `SLOWLOG` is how you catch it after the fact.

## Useful redis-cli flags

These run from your shell, not the prompt, and each is a small monitoring tool on its own.

`--stat` prints a live one-line-per-second dashboard of key counts, memory, and requests:

```bash
redis-cli --stat
```

`--bigkeys` scans the database (safely, using `SCAN`) and reports the largest key of each type, perfect for finding what is eating your memory:

```bash
redis-cli --bigkeys
```

`--latency` measures how fast the server responds by pinging it continuously:

```bash
redis-cli --latency
```

It prints min, max, and average round-trip time in milliseconds. If those numbers climb, the server is under strain.

## Common mistake

Leaving `MONITOR` running on a production server and walking away. It copies every single command to your connection, so on a busy instance it can noticeably slow Redis down and bury you in output. Treat it like a debugger you attach for a moment: turn it on, watch, then Ctrl+C. For ongoing health, use `INFO` and `--stat` instead, which are cheap.

## FAQ

### How do I check Redis memory usage?

Run `INFO memory` and read `used_memory_human`. To find which keys use the most, run `redis-cli --bigkeys`.

### How do I see live Redis commands?

`MONITOR` streams every command as the server runs it. Use it briefly to debug, then stop with Ctrl+C, since it adds load and is not safe to leave on in production.

### How do I find slow Redis commands?

`SLOWLOG GET 10` shows the last ten commands that crossed the slow threshold, with their duration in microseconds and the exact command that was slow.

### What does redis-cli --bigkeys do?

It scans the keyspace with `SCAN` (so it is safe) and reports the biggest key of each type. It is the fastest way to track down what is consuming your memory.
