---
title: "Writing your first Dockerfile"
slug: writing-your-first-dockerfile
seo_title: "Write your first Dockerfile step by step"
seo_description: "Write your first Dockerfile step by step using FROM, WORKDIR, COPY and CMD to package a small script into a Docker image."
---

## What we'll build

Let's package a tiny program into an image. We'll use a simple shell script so we can
focus on the Dockerfile itself, not on any particular language.

Create a folder for this and add a file called `hello.sh`:

```bash
echo "Hello from inside my custom image!"
```

## Writing the Dockerfile

In the same folder, create a file named `Dockerfile` (exactly that, no extension):

```dockerfile
FROM alpine

WORKDIR /app

COPY hello.sh .

CMD ["sh", "hello.sh"]
```

Let's read it line by line:

- `FROM alpine` - start from the small Alpine Linux base image. Every image builds on
  top of another one, and `FROM` picks that starting point.
- `WORKDIR /app` - set the working directory inside the image to `/app`. Docker
  creates the folder if it doesn't exist, and all following instructions run there.
- `COPY hello.sh .` - copy `hello.sh` from your computer into the image. The `.` means
  "into the current working directory", which is `/app`.
- `CMD ["sh", "hello.sh"]` - the command to run when a container starts from this
  image: run our script with `sh`.

## What each part achieves

Notice the progression: we pick a base (`FROM`), choose where to work (`WORKDIR`),
bring our files in (`COPY`), and define what runs (`CMD`). That's the shape of almost
every Dockerfile you'll write.

You now have a Dockerfile, but it's still just a text file - Docker hasn't built
anything yet. In the next lesson we'll
[turn it into a real image with `docker build`](/course/docker-basics/building-images/building-and-running).

## A small habit that pays off: order matters

Even in a Dockerfile this short, the order of instructions affects build speed - and on
a real project it's the difference between a rebuild that takes a second and one that
reinstalls everything. The habit to start now: put things that rarely change (the base
image, installing tools) near the top, and things that change often (your own code) near
the bottom. We'll see exactly why in the
[layers and caching lesson](/course/docker-basics/building-images/layers-and-caching); for
now, just notice that `FROM` sits above `COPY`.

## FAQ

### What does WORKDIR do in a Dockerfile?

It sets the working directory inside the image, creating the folder if needed. Later
instructions - and the running container - use it as their current directory, so you
can write short relative paths instead of full ones.

### What's the difference between COPY and ADD?

`COPY` simply copies files from your project into the image. `ADD` can also fetch URLs
and unpack archives. Prefer `COPY` unless you specifically need `ADD`'s extras - it's
clearer about what it does.

### Do I have to use alpine as the base image?

No. `alpine` is just a small, popular starting point. `FROM` can use any image - a full
Linux like `debian`, or a language image like `php` or `node`. Pick whatever your app
needs.
