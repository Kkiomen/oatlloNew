---
name: "How to Dockerize a Laravel App for Production"
slug: dockerize-laravel-production
short_description: "A practical guide to dockerize Laravel for production: multi-stage builds, PHP-FPM + nginx, opcache, migrations and queue workers."
language: en
published_at: 2026-07-27 09:00:00
is_published: true
tags: [laravel, docker, php, devops]
---

If you want to **dockerize Laravel for production**, the goal is a small, immutable image that boots fast, serves requests through PHP-FPM behind nginx, and never leaks your dev dependencies onto a live server. That's a different job from local development. Laravel Sail is lovely on your laptop, but it is not what you ship to prod.

I've shipped a handful of Laravel apps this way, and the setup below is the one I keep coming back to. It's a multi-stage build that ends on `php:8.3-fpm-alpine`, uses `composer:2` to resolve dependencies, bakes in opcache, and runs migrations and queue workers as separate concerns. Nothing exotic — just the pieces in the right order.

## Choosing a base image

Two decisions matter more than the rest: the PHP variant and the flavor of Linux.

- **PHP-FPM, not the CLI or Apache image.** For a web app you want `php:8.3-fpm` so nginx can talk to it over FastCGI. The `php:8.3-apache` image bundles Apache, which is fine but gives you less control.
- **Alpine for size, Debian for fewer surprises.** `php:8.3-fpm-alpine` lands around 80-90 MB before your app. Debian-slim images are bigger but occasionally save you from a musl libc quirk with an obscure extension. I default to Alpine and only switch if an extension misbehaves.

Pin the minor version (`8.3`, not `latest`). A surprise jump to a new PHP release during a deploy is not a fun way to spend an afternoon.

If image size is a recurring headache, it's worth a dedicated read: see our related post on how to **reduce Docker image size** for the layer-caching and `.dockerignore` tricks that pair well with everything here.

## The multi-stage Dockerfile

The whole idea of a multi-stage build is that the tools you need to *build* the image (Composer, dev packages, maybe Node) never make it into the *final* image. You copy over only the artifacts.

Here's a build I'd actually run:

```dockerfile
# syntax=docker/dockerfile:1

# --- Stage 1: Composer dependencies ---
FROM composer:2 AS vendor

WORKDIR /app

# Copy only what Composer needs first, so this layer caches.
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# Now the app code, then finish autoloading.
COPY . .

RUN composer dump-autoload --optimize --no-dev

# --- Stage 2: Runtime ---
FROM php:8.3-fpm-alpine AS runtime

WORKDIR /var/www/html

# System deps + PHP extensions Laravel commonly needs.
RUN apk add --no-cache \
        libpng libjpeg-turbo freetype icu-libs \
    && apk add --no-cache --virtual .build-deps \
        libpng-dev libjpeg-turbo-dev freetype-dev icu-dev $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd intl bcmath opcache pcntl \
    && apk del .build-deps

# opcache tuning for production.
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Bring in vendored deps and app code from the build stage.
COPY --from=vendor /app /var/www/html

# Entrypoint caches config/routes/views at *startup*, not build time.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Laravel needs these writable at runtime.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

USER www-data

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
```

A few things worth calling out:

- **`composer install --no-dev --optimize-autoloader`** (expressed here as split steps ending in `dump-autoload --optimize`) strips PHPUnit, Faker, and friends, then builds a classmap so autoloading doesn't hit the filesystem on every request.
- **The `.build-deps` virtual package** gets installed, used to compile extensions, then deleted in the same `RUN` layer. Compiler toolchains have no business in a production image.
- **`storage` and `bootstrap/cache` must be writable** by `www-data`. This is the single most common reason a freshly dockerized Laravel app throws a 500 on first request.
- **Caching happens at startup, not build time.** This one bit me: if you run `config:cache` in the Dockerfile, `env()` gets evaluated while your database password and other secrets don't exist yet, so the cached config freezes those values as `null`. Injecting real env vars at runtime then does nothing, because the cache already won. Running the caches in an entrypoint, once the container has its real environment, avoids the whole trap.

Here's the entrypoint (`docker/entrypoint.sh`) referenced above. It caches with the real runtime environment, then hands off to whatever the container's `CMD` is — `php-fpm` for the web app, a queue worker elsewhere:

```bash
#!/bin/sh
set -e

# Env vars are populated by now, so env() resolves to real values.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Replace the shell with the actual process (PID 1, signals intact).
exec "$@"
```

The `exec "$@"` matters: it swaps the shell out for `php-fpm` so signals like `SIGTERM` reach PHP directly during a rolling deploy. Note that `route:cache` fails loudly if you still have closure-based routes. Move those into controllers before you rely on it.

Once config is cached, `env()` calls outside `config/*.php` return `null` everywhere. So the rule holds regardless of timing: read runtime values through `config()`, never `env()`, anywhere but the config files themselves.

Add a `.dockerignore` so you don't copy `vendor/`, `node_modules/`, `.git`, or `.env` into the build context:

```
.git
vendor
node_modules
.env
storage/logs/*
tests
```

## opcache settings that matter

opcache is the biggest free performance win you get. Drop this in `docker/php/opcache.ini`:

```ini
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=192
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

The key line is **`opcache.validate_timestamps=0`**. In production your code never changes inside a running container, so there's no reason to `stat()` every file on every request. Turn it off and PHP trusts the compiled bytecode. The catch: to deploy new code you deploy a new image, which you were doing anyway.

Bump `max_accelerated_files` well above your file count — a typical Laravel app plus vendor easily clears 10,000 files. I left `opcache.preload` out on purpose: preloading squeezes out a little more speed but pins classes into a shared parent process, and getting the `preload_user` and permissions right is fiddly enough that I only add it once an app is stable and I've measured that I need it.

## Wiring up PHP-FPM and nginx

PHP-FPM speaks FastCGI on port 9000; it does not serve HTTP. You put nginx in front to handle the connection, static files, and the handoff. Here's a minimal, correct server block:

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    charset utf-8;
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        # "app" is the PHP-FPM service name in docker-compose.
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

The **`root` points at `public/`**, never the project root — that keeps `.env` and your app code out of the web path. The `try_files` line is the front-controller pattern every Laravel app relies on.

Now tie the two containers together with Compose:

```yaml
services:
  app:
    build:
      context: .
      target: runtime
    restart: unless-stopped
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      DB_HOST: mysql
      DB_DATABASE: app
      DB_USERNAME: app
      DB_PASSWORD: ${DB_PASSWORD}
    depends_on:
      mysql:
        condition: service_healthy
    volumes:
      - storage:/var/www/html/storage

  web:
    image: nginx:1.27-alpine
    restart: unless-stopped
    ports:
      - "80:80"
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: app
      MYSQL_USER: app
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    volumes:
      - dbdata:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 10

volumes:
  storage:
  dbdata:
```

One subtlety: nginx needs the `public/` files to resolve `try_files`, but the PHP code lives in the `app` container. For pure PHP requests this works because nginx only checks existence and passes the path to FPM. If you serve heavy static assets, either share a volume between `app` and `web` or (better) push assets to a CDN.

## Running migrations

Do **not** run `php artisan migrate` inside your `CMD` or on every container start. If you scale to three replicas, three containers race to migrate the same database. Run it once per deploy as a separate step:

```bash
# One-off migration against the running stack.
docker compose run --rm app php artisan migrate --force

# --force is required: artisan refuses to migrate in
# production without it, as a guardrail against accidents.
```

In Kubernetes this becomes an init container or a Job. In a simple Compose deploy, a shell step in your CI pipeline right after the image is pushed does the trick.

## Queue workers as their own container

Queue workers are long-running processes, so they belong in a separate service using the same image — not crammed into the web container. Add this to your Compose file:

```yaml
  worker:
    build:
      context: .
      target: runtime
    restart: unless-stopped
    command: php artisan queue:work --tries=3 --max-time=3600 --sleep=1
    environment:
      APP_ENV: production
      DB_HOST: mysql
      DB_PASSWORD: ${DB_PASSWORD}
    depends_on:
      - mysql
```

Notes from the trenches:

- **`--max-time=3600`** recycles the worker every hour so a slow memory leak in a job never balloons. Docker's `restart: unless-stopped` brings it right back.
- **Always redeploy workers with new code.** Because workers boot the framework once and keep it in memory, an old worker will happily run stale code against a freshly migrated schema. Restart them as part of every deploy.
- For scheduled tasks, run a tiny extra container with `command: php artisan schedule:work` rather than fiddling with cron inside the image.

## Pitfalls to avoid

- **Shipping dev dependencies.** Forgetting `--no-dev` bloats the image and exposes tooling. Verify with `composer show --no-dev`.
- **`APP_DEBUG=true` in production.** It leaks stack traces, env values, and DB credentials. Set it to `false` explicitly.
- **Baking secrets into the image.** Never `COPY .env` into the image. Inject config as runtime environment variables or use a secrets manager.
- **Forgetting writable paths.** No write access to `storage` or `bootstrap/cache` equals an instant 500.
- **`config:cache` plus stray `env()` calls.** Once cached, `env()` outside `config/` returns `null`. Route everything through `config()`.
- **Running as root.** Drop to `www-data`. A container escape from a root process is far worse.
- **Using Sail in production.** Sail is a dev convenience wrapper around Docker Compose with Xdebug and dev tooling. Build a lean image like the one above instead.

## FAQ

### Can I use the same image for web, worker, and scheduler?
Yes, and you should. Build one `runtime` image and change only the `command` per service. It guarantees every process runs identical code and keeps your CI simple.

### Should I run `php artisan migrate` on container startup?
No. With more than one replica you get a migration race and potential data corruption. Run it as a single `docker compose run --rm app php artisan migrate --force` step in your deploy pipeline instead.

### Why is my dockerized Laravel app returning 500 with a blank page?
Ninety percent of the time it's permissions on `storage`/`bootstrap/cache`, or a cached config referencing an `env()` value that's now `null`. Check `storage/logs/laravel.log`, and temporarily set `APP_DEBUG=true` on a non-public instance to see the trace.

### Is Alpine safe for production PHP?
Yes, for the vast majority of apps. The only real gotcha is musl libc versus glibc with a rare C extension. If you hit one, switch the runtime stage to `php:8.3-fpm` (Debian slim) and move on.

## Wrapping up

To **dockerize Laravel for production** cleanly, keep the shape simple: a `composer:2` stage that installs `--no-dev --optimize-autoloader`, a slim `php:8.3-fpm-alpine` runtime with opcache and `validate_timestamps=0`, nginx in front over FastCGI, writable `storage` and `bootstrap/cache`, migrations as a one-off deploy step, and queue workers in their own container off the same image.

Start from the Dockerfile above, add your app's specific PHP extensions, and you have an image that boots in under a second and behaves the same on your machine, in CI, and in production. From there, tighten the layers with the techniques in our post on reducing Docker image size, but the setup here is production-ready as written.