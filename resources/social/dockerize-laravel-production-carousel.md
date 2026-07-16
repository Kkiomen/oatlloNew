---
slug: dockerize-laravel-production-carousel
type: carousel
language: en
title: "Dockerize Laravel for prod"
topic: docker
source_type: article
source: dockerize-laravel-production
link: https://oatllo.com/dockerize-laravel-production
publish_at: 2026-08-26 19:00
status: ready
formats: [post, reel]
hashtags: [docker, laravel, php, devops, deployment]
caption: |
  Run config:cache in your Dockerfile and your DB password becomes null.

  env() resolves at build time, when the secrets don't exist yet. The cache
  freezes null and beats every runtime env var you inject after it.

  Full Dockerfile linked in bio.

  What bit you first: this, or storage permissions?
---

## Cache config at build time and your password is null

`env()` gets evaluated while your secrets don't exist yet. The cache freezes
them as null, and injecting real env vars at runtime then does nothing.

<!-- slide -->

## The cache already won

```dockerfile
# WRONG: the secrets don't exist yet
RUN php artisan config:cache
```

Real env vars arrive at runtime. The cached config was written before they
existed, and cache beats environment.

<!-- slide -->

## Cache at startup instead

```bash
#!/bin/sh
set -e
php artisan config:cache
php artisan route:cache
exec "$@"
```

`exec "$@"` keeps php-fpm as PID 1, so SIGTERM reaches PHP on a rolling deploy.

<!-- slide -->

## And the rule holds either way

Once config is cached, `env()` outside `config/*.php` returns null everywhere.
Read runtime values through `config()`. Nowhere else.

<!-- slide -->

## Never migrate on container start

```bash
docker compose run --rm app \
  php artisan migrate --force
```

Scale to three replicas and three containers race the same database. Run it
once per deploy, as its own step.

<!-- slide role="cta" -->

## The blank 500 on first request is permissions

```dockerfile
RUN chown -R www-data:www-data \
    storage bootstrap/cache
```

Ninety percent of them are these two paths not being writable. Full multi-stage
Dockerfile linked in bio.
