---
name: "CI/CD for Laravel with GitHub Actions: Step by Step"
slug: laravel-github-actions
short_description: "Set up Laravel GitHub Actions CI/CD from scratch: a full ci.yml with PHP, MySQL service, caching, and php artisan test explained line by line."
language: en
published_at: 2026-09-11 09:00:00
is_published: true
tags: [laravel, github-actions, ci-cd, devops, testing]
---

The first time I wired up **Laravel GitHub Actions** for a client project, the pipeline went green on my machine and red on the runner. The culprit was boring: I had forgotten that the CI box has no `.env`, no app key, and no database sitting around waiting for me. That single failed build taught me more about continuous integration than any tutorial had.

This guide walks through building a working CI pipeline for a Laravel app on GitHub Actions, from an empty `.github/workflows` folder to a job that boots MySQL, installs dependencies, and runs your test suite on every push. No hand-waving. You get the full YAML and an explanation of what each block actually does.

## Why bother with CI for a Laravel app

Running `php artisan test` locally is fine until three people push code on the same afternoon. Someone forgets to commit a migration, someone else bumps a dependency, and suddenly `main` is broken and nobody noticed for two days.

Continuous integration moves that check to a neutral machine that runs the same steps every single time. When a pull request opens, GitHub spins up a fresh Ubuntu box, installs your stack, and tells you within a couple of minutes whether the branch is safe to merge.

A few things it buys you:

- **A clean-room build.** The runner has nothing cached from yesterday, so "works on my machine" stops being an argument.
- **Fast feedback on PRs.** Reviewers see a green or red check before they even read the diff.
- **A gate you can enforce.** Branch protection rules can block merges until the pipeline passes.

If you eventually want to ship containers too, the same pipeline can hand off to a deploy job. I won't cover that here, but it pairs well with a proper container setup like the one in [/blog/dockerize-laravel-production](/blog/dockerize-laravel-production).

## Where the workflow file lives

GitHub Actions reads any YAML file inside `.github/workflows/`. Create one:

```bash
mkdir -p .github/workflows
touch .github/workflows/ci.yml
```

The filename doesn't matter to GitHub, only the folder does. I usually name it `ci.yml` so its purpose is obvious. Commit that folder and, on your next push, Actions picks it up automatically, provided the feature is enabled for the repository.

## The full workflow

Here is a complete pipeline that runs your test suite against MySQL. Drop this into `.github/workflows/ci.yml`:

```yaml
name: CI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: laravel_test
          MYSQL_ROOT_PASSWORD: password
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping -h 127.0.0.1 -uroot -ppassword"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, dom, pdo, pdo_mysql, bcmath, intl
          coverage: none

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          restore-keys: composer-

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-interaction

      - name: Prepare environment
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Run tests
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: laravel_test
          DB_USERNAME: root
          DB_PASSWORD: password
        run: php artisan test
```

That's the whole thing. Let's take it apart.

## Reading the file piece by piece

### The trigger

```yaml
on: [push, pull_request]
```

This runs the pipeline on two events: any push to any branch, and any pull request. The `pull_request` event is the one that matters most for review gates, since it runs against the merge result. Having both means you also get a signal on direct pushes to feature branches, which is handy when you're working alone.

### The runner and the job

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
```

`test` is just a name I picked; call it whatever you like. `runs-on: ubuntu-latest` asks GitHub for a fresh Ubuntu virtual machine. It comes with PHP, Composer, and MySQL client tools preinstalled, but we pin our own PHP version below rather than trust whatever ships by default.

### The MySQL service

The `services:` block is the part people skip and then wonder why their database tests fail.

```yaml
services:
  mysql:
    image: mysql:8.0
    env:
      MYSQL_DATABASE: laravel_test
      MYSQL_ROOT_PASSWORD: password
    ports:
      - 3306:3306
    options: >-
      --health-cmd="mysqladmin ping -h 127.0.0.1 -uroot -ppassword"
      --health-interval=10s
      --health-timeout=5s
      --health-retries=5
```

GitHub starts a real MySQL 8.0 container alongside your job. The `env` keys tell the official MySQL image to create a database named `laravel_test` and set the root password on first boot. Mapping port `3306` makes it reachable from the job at `127.0.0.1:3306`.

The health check is the important bit. Containers report "started" long before MySQL is actually ready to accept connections. Those `--health-*` options make the runner poll with `mysqladmin ping` and hold your steps until the database answers. Without it, your test step can race ahead and hit a connection-refused error on a cold database.

One note from experience: use `127.0.0.1`, not `localhost`. On some setups `localhost` resolves to a Unix socket that isn't there, and you'll chase a phantom bug for an hour.

### Checkout and PHP

```yaml
- name: Checkout code
  uses: actions/checkout@v4

- name: Setup PHP
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.3'
    extensions: mbstring, dom, pdo, pdo_mysql, bcmath, intl
    coverage: none
```

`actions/checkout@v4` clones your repository onto the runner. Nothing happens without it.

`shivammathur/setup-php@v2` is the de facto standard for PHP on Actions. Pin `php-version` to whatever your app targets, and list the extensions your app needs. `pdo_mysql` is non-negotiable here since we're talking to MySQL. I set `coverage: none` because generating coverage data slows the run noticeably, and you rarely need it on every push.

### Caching Composer

```yaml
- name: Cache Composer dependencies
  uses: actions/cache@v4
  with:
    path: vendor
    key: composer-${{ hashFiles('composer.lock') }}
    restore-keys: composer-
```

The cache key is built from a hash of `composer.lock`. When the lockfile hasn't changed, the runner restores the exact `vendor` directory from a previous run and `composer install` finishes in seconds. Change a dependency and the hash changes, so the cache misses and Composer does a full install. The `restore-keys` fallback lets a partial older cache seed the install even on a miss.

### The environment dance

```yaml
- name: Prepare environment
  run: |
    cp .env.example .env
    php artisan key:generate
```

This is the step my very first pipeline was missing. A fresh checkout has no `.env` file, so Laravel can't boot. Copy `.env.example`, then generate an app encryption key. Both are required before any artisan command that touches config will work.

### Running the suite

```yaml
- name: Run tests
  env:
    DB_CONNECTION: mysql
    DB_HOST: 127.0.0.1
    DB_PORT: 3306
    DB_DATABASE: laravel_test
    DB_USERNAME: root
    DB_PASSWORD: password
  run: php artisan test
```

The `env` block overrides your database config just for this step, pointing Laravel at the service container we defined earlier. `php artisan test` runs your suite; if you're on Pest, swap the command for `./vendor/bin/pest` instead. Either works, and the difference between the two is worth understanding if you're still choosing — see [/blog/pest-vs-phpunit](/blog/pest-vs-phpunit).

If your tests rely on schema, add a migration step right before this one:

```bash
php artisan migrate --force
```

Many teams instead use the `RefreshDatabase` trait so migrations run inside the test bootstrap, in which case you don't need a separate step.

## Pitfalls I keep seeing

- **No health check on MySQL.** The job starts, tests fire before the DB is ready, and you get intermittent red builds that pass on re-run. Add the `--health-*` options.
- **Forgetting `key:generate`.** Symptom is a `No application encryption key has been specified` error deep in a stack trace. Copy the env file and generate the key.
- **Caching the wrong path.** Cache `vendor`, keyed on `composer.lock`. Keying on `composer.json` or caching Composer's global cache leads to stale or useless caches.
- **Using `localhost` for the DB host.** Prefer `127.0.0.1` to force a TCP connection to the service container.
- **Committing a real `.env`.** CI should build its config from `.env.example`. If your example file drifts from reality, new environment keys silently default to empty and tests break in confusing ways.
- **Not pinning the PHP version.** The runner's default PHP can change under you. Set `php-version` explicitly so upgrades are a deliberate choice.

## FAQ

### How long should a Laravel CI run take?

For a mid-sized app with a few hundred tests, expect roughly two to four minutes once caching is warm. Composer install dominates the first run; after that the cache restore shaves most of it off. If you're creeping past ten minutes, look at parallelizing tests or splitting the suite before you reach for bigger runners.

### Do I need Docker to run Laravel tests on GitHub Actions?

No. The `services:` block already runs MySQL in a container for you, and `setup-php` handles the PHP runtime directly on the Ubuntu runner. You only need your own Dockerfile if you want the pipeline to build and publish the same image you deploy.

### Can I run against SQLite instead of MySQL to speed things up?

You can, and it's faster since there's no service container to boot. The catch is that SQLite behaves differently from MySQL on things like column types, JSON handling, and certain constraints. If production runs MySQL, test on MySQL. Use SQLite only when your app genuinely targets it.

### Will this run on every branch?

With `on: [push, pull_request]`, yes. Every push to any branch triggers the job, plus every pull request. If you want to limit pushes to specific branches, change the trigger to `on: { push: { branches: [main] }, pull_request: {} }`.

## Wrapping up

A working Laravel CI pipeline is not much YAML, but every line earns its place: the service container gives you a real database, the health check keeps it from racing, caching keeps runs quick, and the env-prep step keeps Laravel bootable. Copy the `ci.yml` above into your repo, adjust the PHP version and extensions to match your app, and push.

Once it's green, turn on branch protection and require the `test` job to pass before merging. That one setting is what turns a nice-to-have script into an actual safety net for your team. From there, adding a lint step or a static analysis pass is a matter of dropping in one more job — the hard part, the part that used to burn my afternoons, is already done.