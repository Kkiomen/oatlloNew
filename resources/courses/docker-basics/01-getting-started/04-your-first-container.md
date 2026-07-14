---
title: "Your first container"
slug: your-first-container
seo_title: "Run Your First Docker Container (docker run)"
seo_description: "Run your first Docker container with docker run hello-world, and learn step by step what happens when Docker pulls an image and starts it."
---

## Running hello-world with docker run

Docker ships with a tiny test container called `hello-world`. Let's run it:

```bash
docker run hello-world
```

The first time you run this, you'll see a message that Docker couldn't find the image
locally, so it's downloading it. Then the container runs and prints a friendly
message that starts with:

```text
Hello from Docker!
This message shows that your installation appears to be working correctly.
```

If you see that, Docker is working.

## What just happened?

That one command did a few things in order:

1. Docker looked for an **image** called `hello-world` on your computer.
2. It wasn't there, so Docker **downloaded** it from Docker Hub (a public library of
   images - more on that later).
3. Docker created a **container** from that image and ran it.
4. The container printed its message and then **stopped**, because its only job was
   to print that text.

Don't worry about the exact difference between an "image" and a "container" yet -
we'll define both carefully in the
[images and containers chapter](/course/docker-basics/images-and-containers/images-vs-containers).
The key command to remember is
`docker run`, which is how you start a container from an image.

## Run another container: alpine

Let's run something a little more interesting - a lightweight Linux system - and ask
it to print a message:

```bash
docker run alpine echo "Hi from inside a container"
```

Here `alpine` is a very small Linux image, and `echo "..."` is the command we want to
run inside it. Docker downloads `alpine` (once), starts a container, runs the `echo`
command, prints the result, and the container stops.

You've now run containers. In the next chapter we'll slow down and really understand
images and containers.

## What trips people up the first time

Two things surprise beginners here. First, the initial run is slow because Docker has
to **download** the image; run it again and it's instant, because the image is now
cached on your machine. Second, `hello-world` and `alpine echo` exit immediately -
that's normal. A container runs until its command finishes, and these commands finish
right away. Servers like a web app keep running because their command never exits.

## FAQ

### Why did my first container exit immediately?

Because its job was done. A container runs only as long as its main command is running.
`hello-world` prints a message and finishes, so the container stops. Long-running apps
(like a web server) stay up because their process keeps running.

### What is docker run hello-world for?

It's a tiny built-in test. If it prints "Hello from Docker!", your installation can
download an image, create a container, and run it - so Docker is working end to end.

### Where does Docker download images from?

From a registry - by default **Docker Hub**, a public library of ready-made images. We
look at [Docker Hub and image versions](/course/docker-basics/images-and-containers/managing-images)
more closely in the next chapter.
