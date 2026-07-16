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
verified:
  verdict: approved
  at: 2026-07-16 07:11
  fingerprint: ab7965140d5550df28fe34f56dfa190981fdd7ab
  checks:
    - config:cache at build time freezing env() as null, and cache beating runtime env vars - traced to the article Caching happens at startup not build time bullet
    - entrypoint script matches the article version (post drops view:cache, harmless); exec  keeping php-fpm as PID 1 so SIGTERM lands is the article claim verbatim and is correct
    - env() outside config/*.php returns null once cached, read via config() - matches the article rule
    - migrate on container start races across replicas; docker compose run --rm app php artisan migrate --force matches the article command including --force
    - chown -R www-data:www-data storage bootstrap/cache is real and would run; those are the two paths the article names
  notes: |
    One thing for the human to weigh, not a blocker. The CTA gives the whole ninety percent to permissions, but the article FAQ splits that number across TWO causes: permissions on storage/bootstrap/cache OR a cached config with a null env(). The article does separately call permissions the single most common reason for a first-request 500, and ninety percent reads as idiom rather than measurement, so I did not fail it - but the stat is narrower in the source than on the slide. No version claims that will age; PHP 8.3 and php-fpm behaviour are stable.
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

Ninety percent of them are these two paths not being writable.

