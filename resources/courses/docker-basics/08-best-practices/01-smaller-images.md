---
title: "Smaller images"
slug: smaller-images
seo_title: "How to Make Docker Images Smaller"
seo_description: "Reduce Docker image size with slim and alpine base images, combined-and-cleaned RUN steps, and a good .dockerignore file."
---

## Why size matters

Smaller images are faster to build, faster to download, and faster to start. They
also have a smaller "attack surface" - fewer things installed means fewer things that
can have security problems. A few simple habits make a big difference.

## Pick a smaller base image

The base image you choose in `FROM` sets your starting size. Many official images
offer smaller variants:

- `slim` variants strip out extras. For example, Debian offers `debian:bookworm-slim`
  instead of the full `debian:bookworm`.
- `alpine` variants are built on Alpine Linux and are tiny (for example
  `php:8.4-fpm-alpine` or `node:22-alpine`).

For example, switching a Node image:

```dockerfile
# larger
FROM node:22

# much smaller
FROM node:22-alpine
```

Alpine images are small but use a slightly different system underneath, so
occasionally a package needs extra tweaking. When that's not a problem, they're a
great default.

## Combine RUN steps and clean up

Remember that each instruction is a layer. Every `RUN` that installs something adds to
the image, and leftover package caches stay in that layer forever. Combine related
commands and clean up in the **same** `RUN`:

```dockerfile
RUN apt-get update \
    && apt-get install -y curl \
    && rm -rf /var/lib/apt/lists/*
```

The `rm -rf /var/lib/apt/lists/*` deletes the package index cache in the same layer,
so it never bloats the image. If you cleaned up in a **separate** `RUN`, the files
would still exist in the earlier layer.

## Use a .dockerignore

[We covered this earlier](/course/docker-basics/building-images/dockerignore), but it's worth
repeating as a best practice: a good `.dockerignore` keeps big folders like `node_modules`
and `vendor` out of your build
context, so they aren't copied into the image by accident.

These three habits - a slim base, combined-and-cleaned `RUN` steps, and a
`.dockerignore` - already shrink most images a lot. The next lesson introduces a more
powerful technique: [multi-stage builds](/course/docker-basics/best-practices/multi-stage-builds).

## Measure before you optimize

Before chasing a smaller image, look at what you actually have: `docker images` shows
each image's size, and `docker history <image>` breaks it down layer by layer so you can
see which instruction added the most weight. Nine times out of ten the bloat is one
obvious thing - a full base image, a copied `node_modules`, or a leftover package cache -
and fixing that one layer beats a dozen micro-optimizations. Optimize what the numbers
point at, not what you guess.

## FAQ

### Why are my Docker images so large?

Usually a heavy base image, copied dependency folders (`node_modules`, `vendor`), or
package caches left inside a layer. Run `docker history <image>` to see which layer is
biggest, then target that.

### Does alpine always make images smaller?

It usually does, because Alpine Linux is tiny. The trade-off is that it uses a different
system library, so occasionally a package needs extra setup. When that's not an issue,
it's an excellent default.

### How do I check the size of a Docker image?

Run `docker images` for the total size of each image, and `docker history <image>` to
see how much each layer contributes.
