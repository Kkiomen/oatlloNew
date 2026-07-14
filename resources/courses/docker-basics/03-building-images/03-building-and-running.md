---
title: "Building and running your image"
slug: building-and-running
seo_title: "Build a Docker Image with docker build (-t tag)"
seo_description: "Build a Docker image from your Dockerfile with docker build -t, understand the build context, then run a container from your new image."
---

## Build the image with docker build

With the `Dockerfile` and `hello.sh` from
[the previous lesson](/course/docker-basics/building-images/writing-your-first-dockerfile) in
your current folder,
run:

```bash
docker build -t my-hello .
```

Two parts to notice:

- `-t my-hello` gives your image a **name** (tag) so you can refer to it later. You
  can read `-t` as "tag it as".
- The `.` at the end is the **build context**: the folder Docker looks in for your
  files (the current folder here). Docker needs this to find `hello.sh`.

Docker runs each instruction in your Dockerfile and prints its progress. When it
finishes, you have a new image called `my-hello`. Confirm it:

```bash
docker images
```

You should see `my-hello` in the list.

## Run a container from your image

Now start a container from it, exactly like you'd run any other image:

```bash
docker run my-hello
```

You'll see:

```text
Hello from inside my custom image!
```

That message came from your script, running inside a container built from **your**
image. Congratulations - you've built and run your own Docker image.

## Rebuilding after a change

Edit `hello.sh` to print something different, then build again:

```bash
docker build -t my-hello .
docker run my-hello
```

Because images are just built from the Dockerfile and your files, rebuilding picks up
your changes. You'll notice the second build is faster - the
[next lesson](/course/docker-basics/building-images/common-instructions) explains why, when
we look at the instructions in more detail.

## Don't forget the dot

The most common `docker build` mistake is leaving off the `.` at the end. That dot is
the build context - the folder Docker reads your files from - and it's required. If you
run `docker build -t my-hello` with no path, Docker errors out asking for the context.
The command is `docker build -t my-hello .`, dot included.

## FAQ

### What does the dot mean in docker build?

It's the **build context**: the folder Docker looks in for the files your Dockerfile
copies. `.` means the current folder. It's required, which is why leaving it off causes
an error.

### What does the -t flag do?

It **tags** (names) the image, so you can run it by name later. `docker build -t
my-hello .` produces an image called `my-hello`, which you then run with `docker run
my-hello`.

### Do I need to rebuild after changing my code?

Yes. An image is a snapshot taken at build time, so code changes only appear after you
run `docker build` again. The rebuild is usually fast thanks to Docker's cache (next
lesson).
