---
title: "Using the Redis facade"
slug: using-the-redis-facade
seo_title: "Laravel Redis Facade: run set, get, hset commands"
seo_description: "Use the Laravel Redis facade to run raw commands: set, get, hset, del. Covers the default connection and the automatic REDIS_PREFIX on every key."
---

## Run raw Redis commands from Laravel

The cache is the easy way to reach Redis in Laravel, and the next lessons get you there. Sometimes, though, you want the raw commands you learned in [the console](/course/redis-basics/managing-redis-from-the-console/the-redis-cli-console): `SET`, `GET`, `HSET`, `INCR`. The Laravel Redis facade exposes every one of them, and the method names are just the commands you already know.

Import the facade at the top of your class:

```php
use Illuminate\Support\Facades\Redis;
```

## set and get through the facade

Every Redis command is a method on the facade. Here are the two you met first:

```php
Redis::set('user:42:name', 'Ada');

$name = Redis::get('user:42:name');
// "Ada"
```

Same commands as `redis-cli`, same arguments, just written in PHP. If a key does not exist, `get` returns `null`.

## Hashes, lists, sets, and counters

Every type from the [core data types chapter](/course/redis-basics/core-data-types/strings) is here too. A hash stores fields under one key:

```php
Redis::hset('user:42', 'name', 'Ada');
Redis::hset('user:42', 'role', 'admin');

Redis::hget('user:42', 'role');
// "admin"

Redis::hgetall('user:42');
// ['name' => 'Ada', 'role' => 'admin']
```

Lists, sets, sorted sets, and counters follow the same pattern:

```php
Redis::rpush('queue:emails', 'ada@example.com');
Redis::sadd('tags', 'php', 'redis');
Redis::incr('views');
```

## Calling any command with command()

Most commands have a matching method. For anything unusual, or a command Laravel does not wrap, call it by name with `command()`:

```php
Redis::command('SET', ['flag', '1']);
```

You will rarely need this, but it is there when you do.

One thing the facade does not do for you: serialize. `Redis::set('user', ['name' => 'Ada'])` will not store your array as JSON the way the `Cache` facade would. The raw facade deals in plain Redis values, so encode structured data yourself with `json_encode` before storing it and decode it on the way out. That is the trade for talking to Redis at the command level.

## The default connection the facade uses

When you write `Redis::get(...)`, Laravel uses the `default` connection from `config/database.php` (the one on Redis database `0`). To reach a different connection, name it explicitly:

```php
Redis::connection('cache')->get('some-key');
```

Without `connection(...)`, you always get `default`. That is usually what you want for direct Redis work, and it keeps your hand-written keys away from the framework's cache database.

## The automatic REDIS_PREFIX on every key

Here is the surprise that trips people up. Look back at the config block from the last lesson:

```php
'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
```

Laravel automatically prepends this prefix to every key. So when you write:

```php
Redis::set('user:42:name', 'Ada');
```

the key stored in Redis is actually something like `laravel_database_user:42:name`. If you open `redis-cli` and run `GET user:42:name`, you get nothing, because the real key has the prefix. Use `KEYS 'laravel_database_*'` (or better, `SCAN`, as you learned in the console chapter) to see them.

This is deliberate: the prefix namespaces your app so two Laravel projects can share one Redis without their keys colliding.

## Common mistake

Wondering why `redis-cli` cannot find a key you just set from Laravel. You are looking for the bare key, but Laravel stored it with the `REDIS_PREFIX` in front. Either search with the prefix (`SCAN 0 MATCH 'laravel_database_*'`) or remember that the raw name in Redis is not the name you passed in PHP.

## FAQ

### Are the facade methods the same as redis-cli commands?

Yes. `Redis::set`, `Redis::hget`, `Redis::incr` map to `SET`, `HGET`, `INCR`. If you know the command, you know the method.

### Which connection does the Redis facade use by default?

The `default` connection from `config/database.php`, which sits on Redis database `0`. Call `Redis::connection('cache')` to use another one.

### Why can't I find my Laravel keys in redis-cli?

Laravel adds the `REDIS_PREFIX` to every key. The stored name is `laravel_database_yourkey`, not `yourkey`, so search with the prefix.
