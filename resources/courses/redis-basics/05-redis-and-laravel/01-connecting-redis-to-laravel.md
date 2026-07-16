---
title: "Connecting Redis to Laravel"
slug: connecting-redis-to-laravel
seo_title: "Connect Redis to Laravel 11: config, .env, phpredis"
seo_description: "Connect Redis to a Laravel 11 app: the redis config block, REDIS_HOST/PORT/PASSWORD env keys, phpredis vs predis, and a Redis::ping() check."
---

## Laravel ships with a Redis connection already

To connect Redis to Laravel you do not need a package. A fresh Laravel 11 install already defines a Redis connection out of the box. Point it at your server, pick a client, done. Three moving parts do the work: the config block, the `.env` keys, and the client library. This lesson walks all three.

## The redis config block in config/database.php

Open `config/database.php` and scroll to the `redis` array. It looks like this:

```php
'redis' => [

    'client' => env('REDIS_CLIENT', 'phpredis'),

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],

    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],

    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],

],
```

Notice there are two connections already: `default` and `cache`. They point at the same server but use different Redis databases (`0` and `1`). That keeps your application data and your cache from stepping on each other, which matters the day you flush the cache. More on that in the cache lesson.

## The .env keys that configure Redis

You almost never edit `config/database.php` directly. Values go in `.env`, and the config reads them with `env(...)`. For a local Redis, these are the keys that matter:

```ini
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

If you ran Redis with Docker back in [Getting started](/course/redis-basics/getting-started/run-redis-with-docker), `127.0.0.1` and `6379` are exactly what you exposed. A local Redis usually has no password, so `null` is fine. On a real server you set a real password here.

## phpredis vs predis: the client

`REDIS_CLIENT` picks how PHP actually talks to Redis. There are two choices.

- **phpredis** is a C extension you install into PHP itself (`pecl install redis`, or via your OS package manager). It is compiled, so it is faster and uses less memory. This is the default in Laravel 11 and the recommended option.
- **predis** is a pure-PHP library. Nothing to compile, you just pull it in with Composer:

```bash
composer require predis/predis
```

Then set `REDIS_CLIENT=predis` in `.env`.

Use phpredis if you can install the extension. Reach for predis only when you cannot add extensions to your PHP (some shared hosts). Both speak the same Redis protocol, so your application code does not change between them. Worth knowing: Laravel Sail and most official PHP Docker images bundle the phpredis extension already, so if you ran Redis in a container you likely have it without doing anything.

## Verify the connection with Redis::ping()

The fastest way to confirm Laravel can reach Redis is Tinker. From your project root:

```bash
php artisan tinker
```

Then send a `PING`. You met `PING` in the console chapter; it just asks Redis if it is alive.

```php
Redis::ping();
```

```text
= true
```

A `true` (with phpredis) or `"PONG"` (with predis) means Laravel found Redis and the credentials work. If you get a connection error instead, check that Redis is running and that `REDIS_HOST` and `REDIS_PORT` match where it listens.

## Common mistake

Setting `REDIS_CLIENT=predis` without installing the package. If you switch to predis but forget `composer require predis/predis`, Laravel throws a class-not-found error the moment it tries to connect. Either install predis or leave the client as `phpredis` (and make sure the PHP extension is loaded, which you can check with `php -m | grep redis`).

## FAQ

### Do I have to change config/database.php?

No. Keep your changes in `.env`. The config file already reads every value from there, so editing `.env` is enough for host, port, password, and client.

### Which client is faster, phpredis or predis?

phpredis. It is a compiled C extension, so it is faster and lighter than the pure-PHP predis. Prefer it unless you cannot install PHP extensions on your host.

### Why are there two connections, default and cache?

They point at the same server but different Redis databases, so flushing the cache never wipes your application data. You will use this separation in the cache-driver lesson.
