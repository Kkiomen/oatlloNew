---
title: "Layers and caching"
slug: layers-and-caching
seo_title: "Docker Layers and Build Cache Explained"
seo_description: "Learn how Docker builds images in layers, how the build cache works, and how to order Dockerfile instructions for much faster builds."
---

## Images are built in layers

Each instruction in a Dockerfile creates a **layer** - a snapshot of the changes that
instruction made. Your final image is a stack of these layers, one on top of another.
A Dockerfile with five instructions produces roughly five layers.

## The build cache

Layers make builds fast, because Docker **caches** them. When you rebuild, Docker
walks through your instructions and reuses the cached layer for any instruction whose
inputs haven't changed. As soon as it hits an instruction that **did** change, it
rebuilds that layer and every layer after it.

That's why your second build in
[an earlier lesson](/course/docker-basics/building-images/building-and-running) was faster:
nothing had changed, so
Docker reused everything.

## Order your instructions to help the cache

This caching behaviour has a practical consequence: **put things that change rarely
near the top, and things that change often near the bottom.**

Imagine an app where you install dependencies and then copy your code. Compare two
orderings. First, the slow way:

```dockerfile
FROM node
WORKDIR /app
COPY . .
RUN npm install
```

Here `COPY . .` comes before `RUN npm install`. Every time you change **any** source
file, that `COPY` layer changes, so Docker throws away the cache for it - and re-runs
`npm install` even though your dependencies didn't change. Slow.

Now the fast way:

```dockerfile
FROM node
WORKDIR /app
COPY package.json package-lock.json .
RUN npm install
COPY . .
```

Here we first copy only the dependency files and install. That layer only changes
when your dependencies change. Then we copy the rest of the code. Now editing a source
file only invalidates the final `COPY . .` - the expensive `npm install` layer stays
cached. Much faster.

## Key takeaways on layers and caching

- Each instruction is a cached layer.
- A change invalidates that layer and all layers below it.
- Copy and install **dependencies first**, then copy the rest of your code.

This ordering trick is one of the most useful Docker skills. Next, we'll keep builds
clean and small with a [`.dockerignore` file](/course/docker-basics/building-images/dockerignore).

## The cache trap that wastes hours

Here's the flip side of caching that bites people: sometimes Docker reuses a layer you
*wanted* rebuilt. A classic case is `RUN apt-get update` on its own line - Docker caches
it and keeps serving a stale package list on later builds. Combining it with the install
in one `RUN` ([from the previous lesson](/course/docker-basics/building-images/common-instructions))
avoids this. And if you ever need to force a
completely fresh build, `docker build --no-cache` ignores the cache entirely.

## FAQ

### Why is my Docker build so slow every time?

Usually because an early layer keeps changing and invalidates everything after it. Copy
your dependency files and install them **before** copying the rest of your code, so the
expensive install layer stays cached when only source files change.

### How does the Docker build cache work?

Docker caches each instruction as a layer. On rebuild it reuses cached layers until it
hits one whose inputs changed, then rebuilds that layer and every layer below it.

### How do I force Docker to rebuild without the cache?

Run `docker build --no-cache`. It rebuilds every layer from scratch - handy when you
suspect a stale cached layer is causing problems.
