---
name: "How to Reduce Docker Image Size for PHP Apps"
slug: reduce-docker-image-size-php
short_description: "Practical ways to reduce Docker image size for PHP apps: multi-stage builds, alpine, a tight .dockerignore, and slimmer autoloaders."
language: en
published_at: 2026-10-19 09:00:00
is_published: true
tags: [docker, php, devops, laravel]
---

The first PHP image I ever shipped was 1.4 GB. It ran fine, so I didn't think much about it until our CI started timing out on the registry push and the ops lead asked, politely, what exactly was in there. Turns out: my entire `.git` history, `node_modules`, a full Composer dev toolchain, and Xdebug. If you want to reduce Docker image size for a PHP app, most of the win comes from *not shipping things you never needed in production*, and the rest comes from picking the right base and structuring the build so intermediate junk never lands in the final layer.

Here's what actually moved the needle, in the order I'd tackle it.

## Start by looking at what you already have

Before changing anything, measure. Guessing which layer is fat is a waste of time when Docker will just tell you.

```bash
# Overall size of your images
docker images

# Per-layer breakdown of a specific image
docker history php-app:latest
```

`docker history` shows you the command that created each layer and how many bytes it added. That alone usually exposes the culprit: a `COPY . .` that dragged in 300 MB, or an `apt-get install` that pulled half of Debian.

For a proper audit, install [dive](https://github.com/wagoodman/dive). It walks each layer interactively and flags wasted space (files added in one layer and deleted in a later one still count against you).

```bash
dive php-app:latest
```

The "efficiency score" dive reports is a blunt instrument, but the file tree per layer is genuinely useful. I've caught a leftover `/var/cache/apk` and a stray composer cache this way more than once.

## Swap the base image for alpine

The single biggest lever is the base. The default `php:8.3-fpm` is built on Debian and carries a lot you don't need at runtime. The alpine variant is dramatically smaller because Alpine Linux uses musl libc and BusyBox instead of the full GNU userland.

```dockerfile
# Heavier, Debian-based
FROM php:8.3-fpm

# Much smaller, Alpine-based
FROM php:8.3-fpm-alpine
```

The alpine base image is on the order of tens of megabytes versus a few hundred for the Debian one. You inherit that saving directly.

A word of caution from experience: musl is not glibc. Most PHP extensions compile fine on alpine, but if you depend on something with a hard glibc assumption (some proprietary drivers, certain ICU quirks), test it before committing. For a standard Laravel or Symfony stack, alpine has never bitten me.

## Use a multi-stage build

This is where the real discipline lives. A multi-stage Docker build lets you do all your heavy lifting (compiling extensions, running Composer with its dev dependencies, building frontend assets) in a **builder stage**, then copy only the finished artifacts into a clean runtime stage. The build tools never touch the final image.

```dockerfile
# ---- Stage 1: build ----
FROM php:8.3-fpm-alpine AS builder

# Composer binary, copied from the official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy only what Composer needs first, so this layer caches
# even when your application code changes
COPY composer.json composer.lock ./

RUN composer install \
      --no-dev \
      --optimize-autoloader \
      --no-scripts \
      --no-interaction \
      --prefer-dist

# Now bring in the rest of the source
COPY . .

RUN composer dump-autoload --optimize --no-dev

# ---- Stage 2: runtime ----
FROM php:8.3-fpm-alpine

WORKDIR /app

# Copy the app plus its already-installed vendor directory
COPY --from=builder /app /app

CMD ["php-fpm"]
```

The key flags on that Composer line:

- `--no-dev` skips PHPUnit, Faker, debug bars, and everything else in `require-dev`. In a real project that's easily tens of megabytes of code you'd otherwise ship to production.
- `--optimize-autoloader` (and the explicit `dump-autoload --optimize`) builds a classmap so PHP doesn't stat the filesystem on every class load. It doesn't shrink the image much, but it's a free runtime speedup and belongs here anyway.
- `--no-scripts` during install avoids running framework post-install hooks before the full source is present; those often fail or do unnecessary work in a build context.

Because the builder stage is thrown away, the Composer binary, any dev packages, and build-time caches simply don't exist in your final image.

## Add a real .dockerignore

If you take one thing from this article, take this. A `COPY . .` copies *everything* in the build context except what `.dockerignore` excludes, which is exactly why the file matters so much. Without it, your `.git` directory, local `vendor`, `node_modules`, and test fixtures all get sent to the daemon and baked into a layer.

```
# .dockerignore
.git
.gitignore
vendor
node_modules
tests
storage/logs/*
storage/framework/cache/*
.env
.env.*
Dockerfile
docker-compose*.yml
*.md
```

Excluding `vendor` and `node_modules` is deliberate: you *want* Composer to reinstall inside the builder so you get the `--no-dev` tree, not whatever happens to be on your laptop. Excluding `.git` alone can save hundreds of megabytes on an older repo.

## Handle build dependencies as virtual packages

Some PHP extensions need compilers and headers to build: `gd`, `intl`, `zip`, and friends. You need those tools during the build but never at runtime. Alpine's `--virtual` flag lets you group them under a label and delete the whole group in the same layer once the extension is compiled.

```dockerfile
RUN apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS \
      libpng-dev \
      icu-dev \
      libzip-dev \
 && docker-php-ext-install -j$(nproc) gd intl zip \
 && apk del .build-deps \
 && apk add --no-cache \
      libpng \
      icu-libs \
      libzip
```

What's happening:

- `--no-cache` tells apk not to keep its package index, so there's nothing to clean up afterward.
- `.build-deps` bundles the `-dev` packages (headers, compilers) that only the build step needs.
- `apk del .build-deps` removes them, and because it runs in the *same* `RUN` layer, the space is never committed.
- We then add back only the slim runtime libraries (`libpng`, not `libpng-dev`).

Doing the install and the delete in separate `RUN` instructions would defeat the point; the deleted files would still live in the earlier layer. Keep it in one instruction.

## Keep your layers tidy

A few smaller habits that add up:

- **Chain related commands** with `&&` inside a single `RUN` so you don't create a layer per step, and clean caches in that same instruction.
- **Order instructions from least to most frequently changed.** Copy `composer.json`/`composer.lock` and install dependencies *before* copying source. Your dependency layer then stays cached across code edits, which speeds up rebuilds even if it doesn't shrink the final image.
- **Don't install "recommended" extras** you won't use. There's no `--no-install-recommends` on alpine like on apt, but the principle holds: only add the extensions and packages the app genuinely needs.
- **Skip Xdebug in production images.** It's a dev tool. If it's in your base setup, make sure it's only enabled in a dev target.

## Before and after

Here's a rough shape of what these changes did on one of our internal Laravel services. Treat the numbers as illustrative (your mileage depends on your dependency tree), but the *proportions* are representative:

| Stage | Approx. size |
|---|---|
| Original: `php:8.3-fpm`, single stage, `COPY . .`, dev deps, `.git` included | ~1.3 GB |
| Alpine base only | ~600 MB |
| Alpine + multi-stage + `--no-dev` | ~250 MB |
| Alpine + multi-stage + `.dockerignore` + virtual build deps | ~140 MB |

The last two rows are the ones that matter, and neither required a fancier registry or any magic flag — just not shipping the toolchain and not copying the repo history.

If you're wiring this into a full production setup, my walkthrough on how to [dockerize a Laravel app for production](/blog/dockerize-laravel-production) covers the surrounding pieces: opcache config, running as a non-root user, and the Nginx/php-fpm split.

## FAQ

### Why is my PHP Docker image so large?

Almost always one of three things: you're on a Debian-based image instead of alpine, you copied dev dependencies (or the whole repo including `.git`) into the final image, or you compiled extensions and left the build toolchain behind. Run `docker history` to see which layer is biggest, then attack that one first.

### Does alpine actually reduce PHP image size?

Yes, significantly — the base is tens of megabytes rather than a few hundred. The tradeoff is musl libc instead of glibc, so test any extension or binary with glibc assumptions. For typical Laravel and Symfony apps, alpine is a safe default.

### How do I remove build dependencies from a PHP Docker image?

Install them under an apk virtual package (`apk add --virtual .build-deps ...`), compile your extensions, then `apk del .build-deps`, all inside a single `RUN` instruction so the removed files never get committed to a layer. Add back only the slim runtime libraries afterward.

### Is a multi-stage build worth it for a small app?

Even for a small app, yes. The moment you run `composer install` or build any assets, a multi-stage build keeps that machinery out of the runtime image for essentially no extra complexity. The Dockerfile is a few lines longer; the payoff is a runtime image that contains only what actually serves requests.

## Wrapping up

There's no single trick here. To reduce Docker image size for a PHP app you stack a handful of unglamorous decisions: pick `php:8.3-fpm-alpine`, split the build into stages so Composer's dev tree and your compilers stay in the builder, write a `.dockerignore` that keeps `vendor`, `node_modules`, `.git`, and tests out of the context, and treat build packages as virtual so they self-destruct in the same layer.

Do that and a 1 GB-plus image comfortably drops into the low hundreds of megabytes. Start with `docker history` to find your worst layer, fix that one, and re-measure — the feedback loop is fast and the savings are real.