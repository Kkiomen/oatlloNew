---
title: "RUN, COPY, WORKDIR and friends"
slug: common-instructions
seo_title: "Dockerfile Instructions: RUN, COPY, WORKDIR, ENV"
seo_description: "Learn the everyday Dockerfile instructions - RUN, COPY, ADD, WORKDIR and ENV - and exactly what each one does when Docker builds your image."
---

## Installing things with RUN

The `RUN` instruction executes a command **while the image is being built**. It's how
you install software and set things up. For example, to install `curl` on a
Debian-based image:

```dockerfile
FROM debian
RUN apt-get update && apt-get install -y curl
```

Each `RUN` runs during the build, and its result becomes part of the image. So after
this builds, `curl` is baked into the image and available in every container you
start from it.

Notice we combined two commands with `&&` into one `RUN`. There's a good reason for
that, which the "layers and caching" lesson will explain.

## Copying files with COPY

You've seen `COPY` already. It copies files or folders from your build context (your
project) into the image:

```dockerfile
COPY . /app
```

This copies everything in the current folder into `/app` inside the image. You'll use
`COPY` to bring your application's code into the image.

There's a similar instruction called `ADD`, which can also download URLs and unpack
archives. As a rule, **prefer `COPY`** - it's simpler and does exactly what it says.
Use `ADD` only when you specifically need its extra features.

## Setting the working directory with WORKDIR

`WORKDIR` sets the folder that later instructions (and the running container) use as
their "current directory":

```dockerfile
WORKDIR /app
COPY . .
```

Here the second `.` means `/app`, because that's the working directory. Using
`WORKDIR` is cleaner than writing full paths everywhere.

## Setting variables with ENV

`ENV` defines an **environment variable** inside the image - a named value your app
can read:

```dockerfile
ENV APP_ENV=production
```

Now any process in the container can read `APP_ENV`. This is a common way to
configure applications.

## Putting it together

A slightly bigger Dockerfile using these instructions might look like:

```dockerfile
FROM debian
WORKDIR /app
RUN apt-get update && apt-get install -y curl
COPY . .
ENV APP_ENV=production
CMD ["bash"]
```

Next we'll clear up a common source of confusion:
[`CMD` versus `ENTRYPOINT`](/course/docker-basics/building-images/cmd-vs-entrypoint).

## A RUN mistake worth avoiding

A frequent slip is splitting related commands across separate `RUN` lines - like one
`RUN apt-get update` and a later `RUN apt-get install`. Because each `RUN` is cached
independently, the install can run against a stale package index and fail in confusing
ways. The reliable pattern is to chain them in a single `RUN` with `&&`, exactly as in
the examples above. You'll see the deeper reason in the
[layers and caching lesson](/course/docker-basics/building-images/layers-and-caching).

## FAQ

### What is the difference between RUN and CMD?

`RUN` executes during the **build** and its result is baked into the image (for example,
installing a package). `CMD` sets what runs **when a container starts**. Build-time
versus run-time.

### Why combine commands with && in a RUN?

Each `RUN` becomes its own cached layer. Chaining related steps in one `RUN` keeps them
consistent (an update and its install stay together) and avoids extra layers, which
keeps the image smaller.

### What does ENV do?

`ENV` sets an environment variable inside the image, like `ENV APP_ENV=production`. Any
process in the container can read it, which is a common way to configure an app.
