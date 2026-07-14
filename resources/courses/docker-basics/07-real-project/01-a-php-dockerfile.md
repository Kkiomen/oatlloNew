---
title: "A Dockerfile for a PHP app"
slug: a-php-dockerfile
seo_title: "Write a Dockerfile for a PHP/Laravel application"
seo_description: "Write a Dockerfile for a PHP/Laravel app: pick the php:8.4 base image, install extensions, copy code and install Composer dependencies."
---

## What we're building

We'll package a PHP application into an image. Everything here uses instructions you
already learned - `FROM`, `WORKDIR`, `RUN`, `COPY`, `CMD` - just applied to a real
stack.

## The PHP Dockerfile

In your PHP project's root, create a `Dockerfile`:

```dockerfile
FROM php:8.4-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    unzip libpq-dev \
    && docker-php-ext-install pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-interaction

COPY . .

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

Let's walk through it with what you already know:

- `FROM php:8.4-cli` - start from the official PHP 8.4 image.
- `WORKDIR /app` - work inside `/app`.
- `RUN apt-get ... docker-php-ext-install pdo pdo_mysql` - install the system packages
  and PHP database extensions the app needs. `docker-php-ext-install` is a helper that
  ships with the official PHP image.
- `COPY --from=composer:latest ...` - grab the Composer tool from the official
  `composer` image. (You'll learn more about copying from another image in the
  [multi-stage lesson](/course/docker-basics/best-practices/multi-stage-builds) - for now,
this just gives us `composer`.)
- `COPY composer.json composer.lock ./` then `RUN composer install` - remember the
  [caching lesson](/course/docker-basics/building-images/layers-and-caching): we copy the
  dependency files and install **before** copying the rest
  of the code, so dependencies stay cached when only source files change.
- `COPY . .` - copy the rest of the application.
- `CMD [...]` - run Laravel's built-in server, bound to `0.0.0.0` so it's reachable
  from outside the container (binding to `localhost` inside a container would only
  reach the container itself).

## A .dockerignore

Add a `.dockerignore` so local junk and secrets don't get copied in:

```text
.git
vendor
node_modules
.env
storage/logs/*.log
```

This is the same idea from the
[`.dockerignore` lesson](/course/docker-basics/building-images/dockerignore), tuned for a PHP project.

Next, we'll
[pair this app image with a database using Compose](/course/docker-basics/real-project/compose-for-the-app).

## The extension mistake that wastes an afternoon

The error that eats the most time when dockerizing PHP is a **missing extension**. Your
app boots, then dies with "could not find driver" - which really means the `pdo_mysql`
extension isn't installed in the image. PHP images ship without most extensions; you add
them with `docker-php-ext-install`. So when an app fails only inside Docker, check the
extensions first.

## FAQ

### How do I install PHP extensions in a Docker image?

Use the `docker-php-ext-install` helper that ships with the official PHP images, for
example `docker-php-ext-install pdo pdo_mysql`. Some extensions also need system
packages installed first via `apt-get`.

### Why do I get "could not find driver" in a Dockerized PHP app?

The database PHP extension is missing from the image. Install it in your Dockerfile -
for MySQL that's `docker-php-ext-install pdo_mysql` - then rebuild.

### Why install Composer dependencies before copying all the code?

For build caching. Copy `composer.json`/`composer.lock` and run `composer install`
first, then copy the rest. That way the slow install layer stays cached when you change
only application code.
