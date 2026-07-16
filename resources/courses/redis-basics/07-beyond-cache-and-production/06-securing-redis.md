---
title: "Securing Redis"
slug: securing-redis
seo_title: "Redis Security: Password, bind, and protected-mode"
seo_description: "Secure Redis the right way: set a password with requirepass or ACLs, bind to localhost, use protected-mode, and never expose port 6379 to the internet."
---

Redis is fast partly because it does very little by default: out of the box there is no
password and it happily runs any command it is given. That is fine on your laptop. On a
server it is a problem. To secure Redis you need only a handful of settings, and one rule
you must never break.

## The golden rule: never expose Redis to the internet

Say this out loud: **Redis should never be reachable from the public internet.** Port
`6379` open to the world is one of the most-scanned, most-exploited mistakes in all of
web hosting. Bots sweep the entire IPv4 space looking for open Redis ports. When they
find one with no password, they connect, run `FLUSHALL`, and leave a single key behind
demanding a ransom to get your data back. It is automated, it is constant, and it has
hit thousands of servers.

Your Redis should only be reachable by your own application - nothing else. Everything
below is about enforcing that.

## Set a password with requirepass

The first line of defense is a password. Add this to your `redis.conf`:

```ini
requirepass a-long-random-password-not-this-one
```

Now every client must authenticate before it can run commands. In `redis-cli` you pass
the password with `-a`:

```bash
redis-cli -a a-long-random-password-not-this-one
```

Or authenticate after connecting:

```bash
redis-cli
AUTH a-long-random-password-not-this-one
```

```text
OK
```

Without the password, commands are refused with a `NOAUTH Authentication required`
error. Use a long, random string - this password is often the only thing between an
attacker and your data. In Laravel you set the same value with `REDIS_PASSWORD` in
`.env`, which you saw in
[connecting Redis to Laravel](/course/redis-basics/redis-and-laravel/connecting-redis-to-laravel).

## Redis 6+ ACLs: users with limited powers

`requirepass` gives everyone the same all-powerful login. Redis 6 and later (so
`redis:7`, which this course uses) add **ACLs**: named users, each with their own
password and their own list of allowed commands and keys.

```bash
ACL SETUSER app on >app-password ~cache:* +get +set +del +expire
```

That creates a user `app` that can only touch keys starting with `cache:` and can only
run `GET`, `SET`, `DEL`, and `EXPIRE`. If those credentials leak, the damage is limited -
no `FLUSHALL`, no reading other keys. For a small app `requirepass` is usually enough,
but ACLs are there when you want to lock things down further.

If ACLs feel like too much, there is a lighter trick from the same toolbox: rename or
disable a dangerous command outright. Putting `rename-command FLUSHALL ""` in `redis.conf`
makes `FLUSHALL` stop existing, so even an authenticated client cannot wipe the keyspace by
accident or on a leaked password. It is a blunt instrument, but a useful one for the handful
of commands you never want an app to run.

## Bind to localhost or a private interface

A password stops unauthorized commands. `bind` stops the connection from even being
accepted. It tells Redis which network interfaces to listen on:

```ini
bind 127.0.0.1
```

This makes Redis listen only on localhost, so nothing outside the machine can reach it
at all. If your app runs on the same server, this alone closes the door to the internet.
When Redis and your app are on different machines in a private network, bind to the
private interface instead (for example `bind 127.0.0.1 10.0.0.5`) - never to a public IP.

## protected-mode: the safety net

```ini
protected-mode yes
```

`protected-mode` is on by default. When Redis has no password **and** is not bound to a
specific interface, protected mode refuses connections from other machines. It is a
safety net for the exact "I forgot to configure anything" case that gets servers
ransomed. Do not turn it off to "make things work" - if a remote client cannot connect,
fix the `bind` and `requirepass` settings instead.

## Use a private Docker network

If you run Redis with Docker (as in
[run Redis with Docker](/course/redis-basics/getting-started/run-redis-with-docker)),
Docker gives you isolation for free. When your app and Redis are on the same Docker
network, they reach each other by service name and you **do not publish the Redis port
to the host at all**. Compare these two commands:

```bash
# Bad on a server: 6379 is now open on the host's public interface
docker run -p 6379:6379 redis:7

# Good: no -p, Redis is only reachable inside the Docker network
docker run --network app-net --name redis redis:7
```

Without `-p 6379:6379`, the port is never exposed outside Docker. Your app container,
attached to the same network, still connects to `redis:6379` by name. You will see this
put together in the [full Docker stack](/course/redis-basics/beyond-cache-and-production/a-laravel-redis-docker-stack)
lesson.

## A minimal secure redis.conf

Putting the pieces together, a safe baseline looks like this:

```ini
bind 127.0.0.1
protected-mode yes
requirepass a-long-random-password-not-this-one
```

Localhost only, protected mode on, password required. Layer a private network and ACLs
on top as your setup grows.

## Common mistake

Publishing the Redis port "just to test something" and forgetting to close it. A server
with `-p 6379:6379` and no password can be found and wiped within minutes of coming
online. If you must reach Redis remotely for debugging, do it over an SSH tunnel, never
by opening the port. Treat an exposed, password-less Redis as already compromised.

## FAQ

### Do I still need a password if I bind to localhost?

Yes. Set both. `bind 127.0.0.1` stops outside connections, but a password protects you if
another process on the same machine (or a misconfigured container) can reach Redis. Layers
are the point - do not rely on a single setting.

### Where does the password go in Laravel?

In `.env` as `REDIS_PASSWORD`, which Laravel passes to Redis on connect. See
[connecting Redis to Laravel](/course/redis-basics/redis-and-laravel/connecting-redis-to-laravel).
If it is missing or wrong, you get a `NOAUTH` error - covered in the
[troubleshooting](/course/redis-basics/beyond-cache-and-production/troubleshooting) lesson.

### Is there any harm in leaving protected-mode on?

No. Leave it on. It only ever refuses connections that were already unsafe (no password
and no explicit bind). If it blocks a legitimate client, that client was reaching an
unprotected Redis, which is exactly what you want to prevent.
