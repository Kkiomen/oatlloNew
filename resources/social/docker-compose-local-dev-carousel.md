---
slug: docker-compose-local-dev-carousel
type: carousel
language: en
title: "Docker Compose local dev"
topic: docker
source_type: article
source: docker-compose-local-dev
link: https://oatllo.com/docker-compose-local-dev
publish_at: 2026-08-19 19:00
status: ready
formats: [post, reel]
hashtags: [docker, dockercompose, devops, php, webdev]
caption: |
  One `docker compose down -v` and your local database is just gone.

  The -v drops the named volumes too. Exactly what you want for a clean reset,
  exactly what you don't want when you meant to stop the stack for the night.

  Full compose.yaml linked in bio.

  Ever wiped a local DB on muscle memory?
---

## One docker compose down -v and your DB is gone

The -v removes the named volumes as well as the containers. Two commands, one
letter apart, and only one of them is reversible.

<!-- slide -->

## Two commands, one letter apart

```bash
# stops the stack, keeps your data
docker compose down

# stops the stack, wipes the database
docker compose down -v
```

Muscle memory from `up -d` is what bites here.

<!-- slide -->

## depends_on waits for start, not ready

```yaml
depends_on:
  db:
    condition: service_healthy
```

Plain `depends_on` only waits for the container to start. Your app races a
MySQL that is still initializing and falls over.

<!-- slide -->

## Which is why the healthcheck isn't decor

```yaml
healthcheck:
  test: ["CMD", "mysqladmin", "ping",
    "-h", "localhost"]
  interval: 5s
  retries: 10
```

Without it, `service_healthy` has nothing to wait on.

<!-- slide -->

## Bind mount MySQL's data and pay for it

```yaml
volumes:
  - db-data:/var/lib/mysql   # named: safe
  # - ./data:/var/lib/mysql  # perms grief
```

Works on Linux. Causes permission and corruption headaches on macOS and
Windows.

<!-- slide role="cta" -->

## Delete the version key while you're in there

`version: "3.8"` predates the current spec and Compose v2 dropped it. If a
tutorial still opens with it, it predates a lot more. Full stack linked in bio.
