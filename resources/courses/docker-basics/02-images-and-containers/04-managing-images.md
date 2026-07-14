---
title: "Managing images"
slug: managing-images
seo_title: "Manage Docker Images: pull, list, rmi and tags"
seo_description: "Manage Docker images with docker pull, docker images and docker rmi, and learn how image tags and Docker Hub versions work in practice."
---

## Where images come from

So far Docker downloaded images automatically when you ran them. That download comes
from a **registry** - a place that stores images. The default public registry is
**Docker Hub**, which hosts thousands of ready-made images like `nginx`, `alpine`,
`php`, `mysql` and more.

You can download an image ahead of time with `docker pull`:

```bash
docker pull nginx
```

## Tags: choosing a version

Images have **tags** that usually mark a version. You write them as `name:tag`:

```bash
docker pull nginx:1.27
docker pull php:8.4-cli
```

If you don't specify a tag, Docker assumes `:latest`. So `docker pull nginx` is the
same as `docker pull nginx:latest`. On real projects it's a good habit to pin a
specific version (like `nginx:1.27`) so your setup doesn't change unexpectedly when a
new "latest" is released.

## List images with docker images

To see the images stored on your computer:

```bash
docker images
```

You'll get a table with the image name (repository), its tag, an ID, and its size.

## Remove an image with docker rmi

To delete an image you no longer need, use `docker rmi` (remove image) with its name
or ID:

```bash
docker rmi nginx:1.27
```

Docker won't remove an image if a container is still using it - stop and remove those
containers first (you learned how in
[the previous lesson](/course/docker-basics/images-and-containers/listing-and-stopping-containers)).

## Clean up unused images with docker system prune

Over time you accumulate stopped containers and unused images. This command removes
everything that isn't currently in use:

```bash
docker system prune
```

Docker asks for confirmation and then frees up space. Use it when your machine starts
filling up. Next, let's step
[**inside** a running container](/course/docker-basics/images-and-containers/interactive-containers).

## A habit worth forming early: pin your tags

It's tempting to always pull `nginx` or `php` and move on. The catch is that `latest`
quietly changes when a new version is published, so a setup that worked last month can
behave differently today - with nothing in your files to explain why. Pinning a real
version like `nginx:1.27` makes your builds predictable and your debugging far easier.
Reach for a specific tag on anything you actually depend on.

## FAQ

### What does the latest tag really mean?

Less than people think. `latest` is just the default tag Docker uses when you don't
specify one - it is not guaranteed to be the newest or most stable release. Pin a
specific version for anything you rely on.

### What is Docker Hub?

Docker Hub is the default public registry - a large online library of ready-made images
like `nginx`, `php`, and `mysql`. When you pull an image without naming a registry, it
comes from Docker Hub.

### How do I free up disk space used by Docker?

Run `docker system prune` to remove stopped containers and unused images. It asks for
confirmation first and only removes things nothing is currently using.
