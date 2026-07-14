---
title: "What is a Dockerfile?"
slug: what-is-a-dockerfile
seo_title: "What is a Dockerfile? A Beginner's Guide"
seo_description: "What is a Dockerfile? Learn how this plain text file of instructions (FROM, RUN, COPY, CMD) becomes the recipe Docker uses to build an image."
---

## The recipe for an image

In [the last chapter](/course/docker-basics/images-and-containers/images-vs-containers) we
said an image is like a recipe. A **Dockerfile** is where you
write that recipe. It's a plain text file, named exactly `Dockerfile` (no extension),
containing a list of **instructions**. Docker reads them top to bottom and builds an
image from them.

Each instruction is written in capital letters, followed by its arguments. Here's a
tiny example so you can see the shape - don't worry about the details yet:

```dockerfile
FROM alpine
CMD ["echo", "Hello from my own image"]
```

Reading it in plain English:

- `FROM alpine` - start from the small `alpine` Linux image as a base.
- `CMD [...]` - when a container starts from this image, run this command.

## Why write a Dockerfile?

A Dockerfile lets you **describe your app's environment as code**. Instead of telling
a teammate "install PHP 8.4, then this extension, then copy these files, then run
this", you write it once in a Dockerfile. Anyone can then build the exact same image
with a single command.

That gives you three big wins:

- **Repeatable** - the same Dockerfile always produces the same setup.
- **Shareable** - commit it to your project so the whole team uses it.
- **Version-controlled** - it lives in git next to your code, so changes are tracked.

## The most common instructions

You'll learn these one at a time in the next lessons, but here's the list so the
words look familiar:

- `FROM` - the base image to build on.
- `WORKDIR` - the working directory inside the image.
- `COPY` - copy files from your computer into the image.
- `RUN` - run a command while building the image.
- `CMD` / `ENTRYPOINT` - what to run when a container starts.

Let's [write a real Dockerfile](/course/docker-basics/building-images/writing-your-first-dockerfile)
in the next lesson.

## One detail that saves confusion later

The file must be named exactly `Dockerfile` - capital D, no extension. Beginners often
save it as `dockerfile.txt` or `Dockerfile.md` and then wonder why `docker build` can't
find it. If your build complains it can't locate the Dockerfile, check the filename
first. (You can use a different name, but then you have to point Docker at it explicitly,
which we won't need in this course.)

## FAQ

### What is a Dockerfile used for?

It's the recipe Docker follows to build an image. You list instructions - the base
image, files to copy, commands to run - and `docker build` turns them into a reusable
image you can run anywhere.

### Is a Dockerfile the same as docker-compose?

No. A Dockerfile describes how to build **one** image.
[Docker Compose](/course/docker-basics/docker-compose/what-is-compose) (a later chapter)
describes how to run several containers together. They're complementary, not the same
tool.

### Does the Dockerfile need a file extension?

No - it's named `Dockerfile` with no extension. That exact name is what `docker build`
looks for by default.
