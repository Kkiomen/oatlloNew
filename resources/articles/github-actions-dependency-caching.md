---
name: "Speeding Up CI with GitHub Actions Dependency Caching"
slug: github-actions-dependency-caching
short_description: "How to cache Composer and npm on GitHub Actions with restore-keys, lockfile hashing, and setup-* built-in caching - and why caches silently never hit."
language: en
published_at: 2027-04-14 09:00:00
is_published: true
tags: [devops, github-actions, ci, php, node]
---

The first time I looked hard at a CI bill, the culprit wasn't the tests. It was two lines in the log: `composer install` taking 48 seconds and `npm ci` taking a minute and a half, on every single push, for a diff that touched one Blade file. That's dead time you pay for in minutes and in your own patience while you wait for a green check. Caching dependencies fixes most of it, and the setup is short - but the way it fails is quiet, so people either skip it or ship a config that never actually hits. Here's how it works, the exact YAML for a Laravel + Node repo, and the mistakes that make a cache look configured while it does nothing.

## What actually gets cached, and where

A GitHub Actions cache is a tarball keyed by a string you choose. `actions/cache` looks for that key in the repo's cache store at the start of a step, restores the tarball if it finds one, and - if the key wasn't found - saves the directory back under that key when the job finishes successfully. That's the whole model. There's no magic invalidation, no content awareness. You give it a key and a path, and it does exactly what the key tells it to.

The path is the part people get wrong first. You do **not** cache `vendor/` or `node_modules/` blindly. Well, you can, but the better target is the package manager's global download cache:

- Composer downloads to `~/.composer/cache/files` (or `$COMPOSER_HOME/cache`). Cache that, and `composer install` skips the network and unpacks locally.
- npm keeps a content-addressable store in `~/.npm`. Restore it and `npm ci` installs from disk instead of the registry.

Why the download cache rather than the installed folder? Because `node_modules` is platform-specific and huge, and restoring a stale one can leave you with a broken partial install if the lockfile moved. The download cache is just tarballs keyed by integrity hash - install still runs, it just doesn't hit the network. It's the safer default. I'll show the `vendor`-caching variant too, because for pure-PHP projects it's genuinely faster and there's no native code to worry about.

## The key is everything

Here's the rule that makes or breaks the whole thing: **the cache key must change when your dependencies change, and stay identical when they don't.** The lockfile is the perfect signal for that. `composer.lock` and `package-lock.json` change exactly when a dependency version changes. So you hash the lockfile into the key with `hashFiles()`:

```yaml
key: composer-${{ runner.os }}-${{ hashFiles('composer.lock') }}
```

Same lockfile, same hash, same key - a hit. Someone bumps a version, `composer.lock` changes, the hash changes, you get a miss and rebuild the cache fresh. That's the entire correctness story.

Then there's `restore-keys`, which is the part that's easy to leave out and expensive to skip:

```yaml
restore-keys: |
  composer-${{ runner.os }}-
```

When the exact `key` isn't found, `actions/cache` walks the `restore-keys` list and does a **prefix** match, newest first. So on a dependency bump you don't start from an empty cache - you restore last build's cache (matched by the `composer-Linux-` prefix), and Composer only downloads the handful of packages that actually changed. Without `restore-keys`, every lockfile change means a cold, from-scratch download. This is the single highest-leverage line in the config and the one most tutorials omit.

## Caches are written once - that's a feature, not a bug

This trips people up: once a cache is saved under a key, it is **immutable**. You cannot overwrite it. If `key` already exists, the save step at the end of the job is a no-op - it won't refresh the contents even if the directory changed.

That's deliberate. It's what makes caches safe to read concurrently across parallel jobs without tearing. But it has a real consequence: **your key must contain everything that should trigger a rebuild.** If you key only on `runner.os` and forget the lockfile hash, the first job to run writes the cache and every subsequent run - forever - restores that first stale tarball, even after you upgrade Laravel. It'll look like caching works. It'll just be serving you last month's dependencies. Hashing the lockfile in is what keeps the key honest.

## The workflow: Laravel + Node, done properly

Most of the time you don't even need `actions/cache` directly, because the official setup actions have caching built in. This is the version I reach for first:

```yaml
name: CI

on:
  push:
  pull_request:

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          tools: composer:v2
          coverage: none
        # setup-php can cache extensions, but Composer's own cache
        # is handled below - keep the two concerns separate.

      - name: Cache Composer packages
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ runner.os }}-${{ hashFiles('composer.lock') }}
          restore-keys: |
            composer-${{ runner.os }}-

      - name: Install PHP dependencies
        run: composer install --no-interaction --prefer-dist --no-progress

      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'          # <- built-in, caches ~/.npm keyed on package-lock.json

      - name: Install JS dependencies
        run: npm ci

      - name: Build assets
        run: npm run build

      - name: Run tests
        run: php artisan test
```

Two things to notice. `actions/setup-node` with `cache: 'npm'` handles the entire npm side for you - it finds `package-lock.json`, hashes it, and caches `~/.npm` with sensible restore-keys. You write one line instead of a `cache` block. It also supports `cache: 'yarn'` and `cache: 'pnpm'` if that's your stack.

For Composer I'm caching `vendor` directly here rather than the download cache. For a Laravel app with no compiled extensions, that's the faster path - `composer install` becomes almost a no-op when `vendor` is restored and the lock matches. The `--prefer-dist` flag matters: it pulls zipped distributions instead of cloning git repos, which is both faster and friendlier to the cache.

If you prefer the download-cache approach (safer for anything with platform-specific builds), swap the path:

```yaml
- name: Cache Composer downloads
  uses: actions/cache@v4
  with:
    path: ~/.composer/cache/files
    key: composer-dl-${{ runner.os }}-${{ hashFiles('composer.lock') }}
    restore-keys: |
      composer-dl-${{ runner.os }}-
```

With this variant `composer install` still runs fully but skips the network. Slightly slower than caching `vendor`, more robust. Pick based on how weird your dependencies are.

## Why "the cache never hits"

Nine times out of ten a cache that never restores comes down to one of these:

- **Wrong path.** You cached `~/.npm` but your project uses pnpm, which stores in `~/.local/share/pnpm/store`. Or you cached `vendor` but run `composer install` before the restore step. Order matters - restore before install, always.
- **A key that never repeats.** If `hashFiles()` points at a file that doesn't exist, it returns an empty string, and your key becomes `composer-Linux-` - constant, but so is the cache it writes, so you never get the fresh-dependency rebuild. Worse is keying on something volatile like `github.sha`, which is unique per commit: the key is different every run, so it's always a miss and the cache save is pure overhead.
- **Branch scoping.** Caches are scoped to a branch and its base. A cache created on a feature branch is visible to that branch and to child branches, but a sibling feature branch can only read caches from the default branch. So the first build on a new branch often misses even when `main` has a warm cache - that's expected, and `restore-keys` still pulls the base branch's cache as a fallback.
- **The lockfile isn't committed.** If `composer.lock` or `package-lock.json` is gitignored (it happens more than it should), `hashFiles()` has nothing to hash. Commit your lockfiles.

The fastest way to diagnose this is the log itself. `actions/cache` prints `Cache restored from key: ...` on a hit and `Cache not found for input keys: ...` on a miss. If you see a miss on every run of an unchanged branch, your key is wrong. Read that line before you touch anything else.

## Size limits and eviction

The cache store has a hard ceiling: **10 GB per repository.** When you cross it, GitHub evicts caches least-recently-used until you're back under. There's also a 7-day untouched expiry - a cache no build has read in a week gets dropped.

The practical failure this causes: a repo with many branches, each writing its own multi-hundred-MB `node_modules` cache, blows past 10 GB, and the eviction starts throwing out caches you actually wanted. Two habits keep you clean. First, cache the download stores (`~/.npm`, `~/.composer/cache/files`) rather than installed folders where you can - they're smaller and shared across more branches. Second, don't put anything build-specific in the cache path; keep it to dependency downloads. If you're genuinely tight, you can prune old caches manually:

```bash
# list caches, largest first
gh cache list --sort size --order desc

# delete a specific one
gh cache delete <id>
```

## A word on Docker layer caching

If your CI builds a Docker image, the same "cache the expensive, stable part" idea applies, but the mechanism is different. Docker's build cache is layer-based, and `docker/build-push-action` can persist it between runs using GitHub's cache backend:

```yaml
- uses: docker/build-push-action@v6
  with:
    context: .
    push: false
    cache-from: type=gha
    cache-to: type=gha,mode=max
```

`type=gha` stores layer cache in the same Actions cache store (so it counts against that 10 GB budget too). The big win is the same as with dependencies: order your Dockerfile so `COPY composer.lock` and the install step come **before** `COPY . .`, and the dependency layer stays cached across every code-only change. Copy your whole source in first and every build reinstalls everything - the layer cache can't help you because the layer's inputs changed.

## FAQ

**Should I cache `vendor` and `node_modules`, or the download caches?**
For a plain Laravel app, caching `vendor` directly is fine and fastest. For `node_modules`, prefer caching `~/.npm` and letting `npm ci` run - restoring a full `node_modules` risks a broken install if native modules or the lockfile shifted. When in doubt, cache the download store; `install`/`ci` still runs but skips the network.

**Do I still need `actions/cache` if `setup-node` has `cache: 'npm'`?**
Not for Node - the built-in caching covers `~/.npm` with proper lockfile hashing. Use `actions/cache` for things the setup actions don't handle: Composer's `vendor`, Playwright browsers, a build artifact you want to reuse.

**Why is my cache saved but never restored on other branches?**
Cache access is scoped by branch. A feature branch can read its own caches and the default branch's, but not a sibling's. New branches miss until they either write their own cache or fall back to `main` via `restore-keys`. That's working as designed, not a bug.

**What does `restore-keys` actually do differently from `key`?**
`key` is an exact match. `restore-keys` is an ordered list of prefixes tried when the exact key misses - it gives you the most recent close-enough cache so a dependency bump downloads only what changed instead of everything. Leaving it out is the most common reason caching underdelivers.

## Where the time actually goes

Getting this right turned my 48-second Composer step and 90-second npm step into a combined restore of a few seconds on every unchanged-dependency run - which is most runs. The setup is four lines of YAML for Composer and one for Node. The reason it's worth writing about isn't the config, it's that a broken cache is invisible: it doesn't error, it doesn't warn, it just quietly does nothing while you keep paying for full installs. So after you add it, open one log and read the `Cache restored from key` line. If it's there on a second run, you're done. If it says `not found` on an unchanged branch, your key is lying to you - fix that before you trust it.
