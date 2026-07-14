---
title: "Why data disappears"
slug: why-data-disappears
seo_title: "Why Docker Container Data Disappears"
seo_description: "Understand why data written inside a Docker container is ephemeral and disappears when the container is removed - and how to keep it safe."
---

## Containers are temporary

When a container runs, it can create and change files, just like any program. But
those changes live **inside that one container**. When you remove the container, its
files go with it. This is by design: containers are meant to be disposable.

## See container data disappear

Let's prove it. Start an interactive Ubuntu container and create a file:

```bash
docker run -it --name data-test ubuntu bash
```

Inside the container:

```bash
echo "important notes" > /root/notes.txt
cat /root/notes.txt
exit
```

The file was there. Now start a **new** container from the same image:

```bash
docker run -it ubuntu bash
```

Inside this new container:

```bash
cat /root/notes.txt
```

You'll get "No such file or directory". The file only existed in the first container.
A fresh container starts clean from the image, with none of the previous container's
changes.

## Why this is actually good

This might feel like a problem, but it's a feature. It means every container starts
from a known, clean state - no leftover mess from a previous run. It's a big part of
why Docker is so reliable.

## But we need to keep some data

Of course, real apps need to keep data: a database must not lose its records when the
container restarts. We don't want to abandon the clean-container model - we just want
to store the important data **outside** the container, so it survives.

Docker gives us two ways to do that:

- **Volumes** - storage managed by Docker, ideal for things like databases.
- **Bind mounts** - link a folder on your computer into the container, ideal for
  sharing your source code during development.

We'll cover both in the next lessons, starting with
[volumes](/course/docker-basics/data-and-volumes/volumes).

## The mistake that costs people real data

This lesson's idea has a painful real-world version: someone runs a database in a plain
container, works with it for weeks, then runs `docker rm` (or `docker compose down`)
and every record is gone. There's no undo. The rule to internalise now, before the next
lessons show the fix: **anything you can't afford to lose must not live only inside a
container.** Treat containers as throwaway and put real data in a volume.

## FAQ

### Why does my data disappear when I restart a container?

If you *restart* the same container, the data is still there. It disappears when the
container is **removed** and you start a fresh one - a new container begins clean from
the image, with none of the old container's changes.

### Is data lost when a container just stops?

No. Stopping keeps the container and its data; you can start it again. Data is lost only
when the container is removed (`docker rm`, `docker rm -f`, or `docker compose down`).

### How do I keep data between containers?

Store it outside the container - in a **volume** (managed by Docker) or a **bind mount**
(a folder on your computer). The next two lessons cover both.
