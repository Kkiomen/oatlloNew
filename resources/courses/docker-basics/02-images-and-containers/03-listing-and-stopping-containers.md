---
title: "Listing and stopping containers"
slug: listing-and-stopping-containers
seo_title: "docker ps, stop and rm: Manage Docker Containers"
seo_description: "Manage Docker containers from the command line: list them with docker ps, stop with docker stop, and remove with docker rm and the --rm flag."
---

## List running containers with docker ps

To list the containers that are **currently running**, use:

```bash
docker ps
```

You'll see a table with each container's ID, the image it came from, its name, when
it started, and its status. If you started the `my-web` container from the previous
lesson, it should appear here.

To see **all** containers, including ones that have stopped, add `-a` (all):

```bash
docker ps -a
```

This is useful because stopped containers don't disappear on their own - they stick
around until you remove them.

## Stop a container with docker stop

To stop a running container, use `docker stop` with its name or ID:

```bash
docker stop my-web
```

Docker asks the app inside to shut down gracefully. The container stops, but it still
exists (you'll see it in `docker ps -a` with a status like "Exited").

## Remove a container with docker rm

A stopped container still takes up a little space and keeps its name reserved. To
delete it completely, use `docker rm`:

```bash
docker rm my-web
```

Now the name `my-web` is free again, and the container is gone.

You can only remove a container that's stopped. If you try to remove a running one,
Docker refuses. You can force it with `-f`, which stops and removes in one step:

```bash
docker rm -f my-web
```

## Auto-remove a container with --rm

If you don't need a container to stick around after it exits, add `--rm` when you run
it. Docker will automatically remove it once it stops:

```bash
docker run --rm alpine echo "I clean up after myself"
```

This keeps your machine tidy - great for one-off commands. Next, let's do the same
kind of [management for images](/course/docker-basics/images-and-containers/managing-images).

## The habit that saves you disk space

Stopped containers don't disappear on their own - they pile up silently and each keeps
its name reserved. Run `docker ps -a` after a busy session and you'll often find a
dozen exited containers you forgot about. Getting into the habit of `--rm` for one-off
runs, and occasionally clearing out old containers, keeps that clutter from building up.

## FAQ

### What's the difference between docker stop and docker rm?

`docker stop` shuts a running container down but keeps it (you can start it again).
`docker rm` deletes it for good. Stop first, then remove - or use `docker rm -f` to do
both at once.

### Do I need to stop a container before removing it?

Yes, unless you force it. `docker rm` refuses to delete a running container. Either
`docker stop` it first, or use `docker rm -f` to stop and remove in one step.

### How do I see containers that already stopped?

Run `docker ps -a`. Plain `docker ps` shows only running containers; the `-a` flag adds
the stopped ones too.
