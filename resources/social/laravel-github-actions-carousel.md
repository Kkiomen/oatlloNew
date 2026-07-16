---
slug: laravel-github-actions-carousel
type: carousel
language: en
title: "Laravel CI"
topic: devops
source_type: article
source: laravel-github-actions
link: https://oatllo.com/laravel-github-actions
publish_at: 2026-10-02 19:00
status: ready
formats: [post]
hashtags: [laravel, githubactions, cicd, devops, testing]
caption: |
  The pipeline went green on my machine and red on the runner. No .env, no app key, no database.

  Copy .env.example, generate the key, health-check MySQL, use 127.0.0.1. Four
  lines that fix most first-build failures.

  Full ci.

  yml linked in bio.

  What broke your first CI run?
---

## CI dies on a cryptic key error: you forgot key:generate

Green on your machine, red on the runner. A fresh checkout has no `.env`, no app
key and no database sitting around waiting for you. Laravel cannot even boot.

<!-- slide -->

## The two lines the runner needs

```yaml
- name: Prepare environment
  run: |
    cp .env.example .env
    php artisan key:generate
```

Without them the symptom is `No application encryption key has been specified`,
buried deep in a stack trace that mentions nothing about CI.

<!-- slide -->

## MySQL says "started" long before it is ready

```yaml
options: >-
  --health-cmd="mysqladmin ping"
  --health-interval=10s
  --health-retries=5
```

Skip the health check and your test step races ahead into a connection refused.
That is the intermittent red build that passes on re-run.

<!-- slide -->

## Use 127.0.0.1, never localhost

```yaml
env:
  DB_HOST: 127.0.0.1
  DB_PORT: 3306
  DB_DATABASE: laravel_test
run: php artisan test
```

On some setups `localhost` resolves to a Unix socket that is not there. That is
an hour of chasing a phantom bug.

<!-- slide role="cta" -->

## Then make the check required

Turn on branch protection and require the `test` job to pass before merging.
That one setting turns a nice script into an actual safety net for the team.
Full ci.
