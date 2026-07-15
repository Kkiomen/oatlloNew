---
slug: docker-image-size-carousel
type: carousel
language: en
title: "How to reduce Docker image size for PHP apps"
topic: docker
source_type: article
source: reduce-docker-image-size-php
link: https://oatllo.com/reduce-docker-image-size-php
publish_at: 2026-07-24 19:00
status: ready
formats: [post, reel]
hashtags: [docker, php, devops, containers, backend]
caption: |
  The first PHP image I shipped was 1.4 GB. CI started timing out on the registry push.

  Inside: the whole .git history, node_modules, a full Composer dev toolchain
  and Xdebug. None of it serves a request. Most of the win is just not shipping
  things production never needed.

  Full write-up linked in bio.

  How big is your production PHP image right now? Run docker history and look.
---

## Your PHP image is 1.4 GB. It doesn't need to be.

Every deploy pushes it. Every pull waits for it.

<!-- slide -->

## You shipped the toolbox with the tool

My 1.4 GB image held the entire `.git` history, `node_modules`, a full Composer
dev toolchain and Xdebug. Not one of them serves a single request.

<!-- slide -->

## Build in one stage, ship the other

```dockerfile
FROM php:8.3-fpm-alpine AS builder
COPY --from=composer:2 /usr/bin/composer \
     /usr/bin/composer
RUN composer install --no-dev \
      --optimize-autoloader

FROM php:8.3-fpm-alpine
COPY --from=builder /app /app
```

The builder is thrown away. Composer never exists in the final image.

<!-- slide -->

## COPY . . copies everything you forgot

```
.git
vendor
node_modules
tests
.env
```

Without a `.dockerignore`, all of it gets baked into a layer. Excluding `.git`
alone can save hundreds of megabytes on an old repo.

<!-- slide -->

## One line, hundreds of megabytes

```dockerfile
FROM php:8.3-fpm-alpine
```

The Debian base runs a few hundred megabytes. The alpine one is tens. It's musl
instead of glibc, so test your extensions first.

<!-- slide role="cta" -->

## 1.3 GB in. 140 MB out. Same app.

Alpine base alone: ~600 MB. Add multi-stage and `--no-dev`: ~250 MB. Add
`.dockerignore` and virtual build deps: ~140 MB. Start with `docker history`
and fix your worst layer.
