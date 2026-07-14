---
name: "Database Connection Pooling Explained (and Why PHP Handles It Differently)"
slug: database-connection-pooling
short_description: "How database connection pooling really works, why PHP-FPM has no built-in pool, and when PgBouncer or ProxySQL actually helps."
language: en
published_at: 2027-02-22 09:00:00
is_published: true
tags: [php, database, performance, postgres, mysql]
---

The first time I got paged at 2 a.m. for a "too many connections" error, I did what a lot of people do: I bumped `max_connections` on the database, went back to sleep, and felt clever. Three weeks later the same alert fired, except now the database itself was falling over from memory pressure. That night taught me more about **database connection pooling** than any docs page ever did, and it exposed a misconception I'd been carrying around for years about how PHP talks to a database.

This article is the explanation I wish someone had handed me back then: what pooling actually is, why traditional PHP does not work the way Java or Go does, what `PDO::ATTR_PERSISTENT` really buys you (and costs you), and when an external pooler like PgBouncer or ProxySQL is the right call.

## What connection pooling actually solves

Opening a database connection is not free. Every new connection pays a setup tax:

- A TCP handshake between the app server and the database.
- Authentication (username, password, sometimes a challenge-response round trip).
- Optionally a TLS negotiation, which is the expensive one.
- Server-side session setup: the database forks or assigns a backend process/thread and allocates memory for it.

On Postgres in particular, each connection spawns a dedicated backend process that reserves a chunk of memory. That cost is small per request but brutal at scale.

**Connection pooling keeps a set of already-established connections open and hands them out to whoever needs one.** Instead of paying the setup tax on every single query batch, your code borrows a warm connection, runs its work, and returns it to the pool. The handshake happened once; everyone after that rides for free.

In a long-running runtime this is straightforward. A Java app or a Go service boots, opens (say) 20 connections, and keeps them in a shared in-process pool for the lifetime of the process. Threads grab and release connections from that shared structure.

Here's where PHP surprises people.

## The PHP model: no in-process pool

Traditional PHP running under PHP-FPM does **not** have an in-process connection pool like Java or Go. This is the misconception I carried for years, and it's worth being blunt about.

The classic request lifecycle looks like this:

1. A request arrives and gets assigned to an idle FPM worker process.
2. Your code opens a database connection during that request.
3. The request finishes, the script tears down, and the connection closes.
4. The worker is handed the next request and starts over from scratch.

Each FPM worker handles one request at a time, and by default each request opens its own fresh connection and drops it at the end. There is no shared pool that survives across requests inside the PHP process, because the PHP execution model is share-nothing by design. That design is a huge part of why PHP is so robust (a fatal error in one request can't corrupt state for the next), but it means the "keep warm connections around" trick has nowhere to live.

So when people say "PHP has connection pooling," what they usually mean is one of a few different things. Let's separate them, because they behave very differently.

## Persistent connections: pooling per worker, with strings attached

PDO offers `PDO::ATTR_PERSISTENT`. It gets called pooling all the time, and it's the closest thing built into PHP, but it's narrower than it sounds.

```php
$pdo = new PDO(
    'pgsql:host=127.0.0.1;dbname=app',
    'app_user',
    $secret,
    [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
    ]
);
```

With this flag on, when the script ends the connection is **not** closed. It's parked and cached inside that specific FPM worker process. The next request that lands on the same worker and asks for a connection with the same DSN gets the parked one back, skipping the handshake.

That's a real win, but read the caveats before you enable it in production:

- **The pool size equals your worker count, not something you tune.** If you run 50 FPM workers across a server, you get up to 50 persistent connections from that server. Multiply by your number of app servers.
- **Stale state travels with the connection.** PHP rolls back an uncommitted transaction when the script ends, but it does not reset the rest of the session: a changed timezone, a `SET` you ran, user variables, or a temp table all survive into the next request that reuses the connection. I've been burned by leftover session state showing up in an unrelated request that did nothing to cause it.
- **Locks can persist too.** A connection that died mid-transaction can leave locks held on the server side until something times out.

My rule of thumb: persistent connections are fine when your session state is boring and your queries are short. The moment you're doing anything stateful per request, test carefully or skip it.

## Doing the connection math (this is where the 2 a.m. page came from)

The "too many connections" error is almost always a counting problem, not a database bug. Postgres and MySQL both cap concurrent connections (`max_connections`). Your job is to make sure the app side can't ask for more than that cap.

The formula that matters:

```ini
; the ceiling you must stay under
db_max_connections = 200

; what your app layer can demand, worst case
app_demand = fpm_workers_per_server * number_of_app_servers
```

If each server runs 50 FPM workers and you have 6 servers, worst-case demand is 300 connections. Your database allows 200. Under load, 100 requests get an ugly connection error, and you find out about it from customers.

People reach for the obvious fix: raise `max_connections`. On Postgres that backfires fast, because each connection is a backend process holding memory. Push the number high enough and you trade connection errors for out-of-memory crashes and context-switching overhead. That was exactly my second outage.

The better move is to put something between the app and the database that multiplexes many app-side connections onto a small number of real database connections. That something is an external pooler.

## External poolers: the real pooling for PHP

Because PHP itself won't hold a pool, you push the pool outside the process. This is the standard architecture for PHP at scale.

### PgBouncer (Postgres)

PgBouncer is a tiny, single-purpose proxy that sits in front of Postgres. Your PHP app connects to PgBouncer; PgBouncer keeps a small pool of real connections to Postgres and shares them across everyone.

It has three pooling modes, and picking the wrong one causes subtle bugs:

- **Session pooling**: a client keeps its backend connection until the client disconnects. Safest, least sharing.
- **Transaction pooling**: a backend is assigned only for the duration of a transaction, then returned. This is the workhorse mode for web apps and gives you the biggest reduction in real connections.
- **Statement pooling**: connection returned after each statement; aggressive, and it breaks multi-statement transactions.

Transaction pooling is what most PHP shops want, but it comes with a constraint worth memorizing: **anything that relies on session-level state across transactions will break.** Session variables, `SET` statements meant to persist, advisory locks held across transactions, and server-side prepared statements can all misbehave. For prepared statements this bites PDO users specifically, so you often set `PDO::ATTR_EMULATE_PREPARES => true` when running behind transaction-mode PgBouncer.

A rough sizing example:

```ini
; pgbouncer.ini
[databases]
app = host=10.0.0.5 port=5432 dbname=app

[pgbouncer]
pool_mode = transaction
; real connections PgBouncer opens to Postgres per user/db
default_pool_size = 20
; how many clients (your FPM workers) can connect to PgBouncer
max_client_conn = 2000
```

Now 2000 FPM workers across your fleet can connect, but Postgres only ever sees 20 busy connections. The database breathes. This is the single highest-leverage change I've made to a struggling Postgres setup.

### ProxySQL and MySQL Router (MySQL)

On the MySQL side the equivalents are ProxySQL and MySQL Router. ProxySQL is the more powerful of the two: it multiplexes connections, and it can also route queries (reads to replicas, writes to the primary), rewrite queries, and cache results. MySQL Router is lighter and focused on routing within an InnoDB Cluster.

The core benefit is the same as PgBouncer's: your many short-lived PHP connections collapse onto a controlled number of real MySQL connections, and the "too many connections" ceiling stops being a landmine.

## Where Laravel Octane fits

Laravel Octane (running on Swoole or RoadRunner) changes the picture, because it keeps your application booted in long-running worker processes instead of tearing everything down per request. That means database connections can stay alive across requests, which is much closer to true in-process pooling than classic PHP-FPM ever gets. If you're already on Octane, read up on how it manages state — I wrote more about the tradeoffs in [Laravel Octane performance](/blog/laravel-octane-performance).

The catch is the same one that haunts persistent connections, just more so: **stale connections**. A connection that's been idle for hours may have been closed by the database's `wait_timeout`, and the first query after that fails with "MySQL server has gone away" or a Postgres equivalent. Long-running workers need to detect dead connections and reconnect, and they need to reset per-request state so one request's leftovers don't poison the next. Laravel handles a lot of this for you, but it's not free of surprises.

## Common pitfalls I've actually hit

- **Bumping `max_connections` as a first response.** It hides the problem for a while, then converts a connection error into a memory crash. Fix the math or add a pooler instead.
- **Enabling `PDO::ATTR_PERSISTENT` without checking session state.** Leaked transactions and lingering locks are miserable to debug because they show up in a request that did nothing wrong.
- **Using PgBouncer transaction mode with server-side prepared statements.** Queries fail intermittently depending on which backend you land on. Switch to emulated prepares or session mode.
- **Forgetting the pooler is a single point of failure.** If PgBouncer or ProxySQL goes down, everything goes down. Run it with a health check and, ideally, redundancy.
- **Sizing the pool by gut feel.** A pool that's too small serializes your requests behind connection waits; too large defeats the purpose. Measure real concurrency, don't guess.
- **Ignoring idle-in-transaction connections.** A slow request holding a transaction open ties up a pooled backend the whole time. Watch for it; it's often a slow external API call inside a DB transaction.

While we're on the subject of finding what's actually slow, most of my "we need pooling" moments turned out to be "we have slow queries holding connections too long." It's worth reading [optimizing SQL queries with EXPLAIN](/blog/optimizing-sql-queries-with-explain) before you assume the connection layer is the bottleneck. And if you're tuning transaction behavior behind a pooler, [database isolation levels](/blog/database-isolation-levels) is directly relevant, because transaction pooling changes what "a session" even means.

## FAQ

### Does PHP have built-in connection pooling?

Not in the way Java or Go do. Traditional PHP under PHP-FPM is share-nothing: each worker opens its own connection per request and closes it at the end, with no shared in-process pool. `PDO::ATTR_PERSISTENT` gives you connection reuse *per worker process*, and Laravel Octane keeps connections alive across requests, but for real pooling at scale you use an external pooler like PgBouncer or ProxySQL.

### How do I fix a "too many connections" error?

First, do the math: `FPM workers per server × number of servers` must stay under the database's `max_connections`. If it doesn't, either reduce worker demand or, better, put a pooler in front of the database so many app connections map to a few real ones. Raising `max_connections` should be a last resort, especially on Postgres where each connection costs memory.

### What's the difference between PgBouncer session and transaction pooling?

Session pooling assigns a real backend connection to a client until it disconnects, preserving all session state but sharing connections less aggressively. Transaction pooling assigns a backend only for the length of a transaction, then reclaims it, so far fewer real connections serve far more clients. Transaction mode is usually what web apps want, but it breaks anything that depends on session state persisting between transactions.

### Are persistent PDO connections safe to use?

They're safe when your per-request session state is minimal and your transactions are short and always cleanly committed or rolled back. They get dangerous when a request can leave a connection in a weird state (open transaction, held lock, changed session settings), because that state carries into the next request on the same worker. Test under real load before trusting them.

## Wrapping up

Connection pooling is about one thing: not paying the connection setup tax over and over. The trap for PHP developers is assuming the runtime pools connections for you the way a long-lived JVM does. It doesn't. Classic PHP-FPM opens and closes a connection per request, persistent connections give you limited per-worker reuse, and genuine pooling at scale comes from an external layer — PgBouncer for Postgres, ProxySQL or MySQL Router for MySQL — or from a long-running runtime like Octane.

If you take one concrete action from this: calculate `workers × servers` against your `max_connections`. If that number is uncomfortable, put a pooler in transaction mode in front of your database and size its pool to your measured concurrency. That's the change that turned my 2 a.m. pages back into full nights of sleep, long before I ever needed to touch `max_connections` again.