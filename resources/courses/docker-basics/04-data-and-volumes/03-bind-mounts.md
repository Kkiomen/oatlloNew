---
title: "Bind mounts"
slug: bind-mounts
seo_title: "Docker Bind Mounts: Share Host Folders"
seo_description: "Learn how Docker bind mounts link a folder on your computer into a container with -v, ideal for editing source code during local development."
---

## Share a host folder with a bind mount

A **bind mount** links a specific folder on **your computer** directly into a
container. Whatever is in that folder appears inside the container, and changes flow
both ways in real time.

This is different from a volume:

- A **volume** is storage Docker manages somewhere internal - you don't pick a folder
  on your disk.
- A **bind mount** points at an exact folder you choose on your machine.

## Why it's great for development

Imagine you're developing an app inside a container, but you want to edit the code
with your normal editor on your computer. With a bind mount, you edit files on your
machine and the container sees the changes immediately - no rebuild needed.

The syntax is the same `-v` flag, but instead of a volume name you give a **path** on
your computer:

```bash
docker run -it -v "$(pwd):/app" -w /app node bash
```

Breaking it down:

- `"$(pwd)"` is your current folder on your computer. (On Windows PowerShell you can
  use `${PWD}` instead.)
- `:/app` mounts it at `/app` inside the container.
- `-w /app` sets the working directory to `/app` (same idea as `WORKDIR`, but as a
  run-time flag).

Now `/app` inside the container **is** your project folder. Edit a file on your
computer, and the running container sees the new version instantly.

## Volumes vs bind mounts - which to use

A simple guideline:

- Use a **volume** for data the container owns and should persist (databases, uploads
  managed by the app).
- Use a **bind mount** to share **your source code** into a container during local
  development.

You now know how to keep and share data. Next, we'll connect containers to the
outside world and to each other with [networking](/course/docker-basics/networking/port-mapping).

## The path gotcha on Windows and macOS

Bind mounts need an **absolute path**, and this is where beginners get stuck. `$(pwd)`
handles it on Linux and macOS; in PowerShell on Windows use `${PWD}`. If you pass a
relative path or get the quoting wrong, you'll either see an error or - worse - an empty
folder mounted over your files. When a bind mount "loses" your files, the path is almost
always the culprit. Double-check it's absolute and correctly quoted.

## FAQ

### When should I use a bind mount instead of a volume?

Use a bind mount during **development**, to share your source code from your computer
into the container so edits show up live. Use a volume for data the container should
own and persist, like a database.

### Why are my files empty inside the bind mount?

Usually a wrong or relative host path, so Docker mounts the wrong (empty) folder over
your target. Make sure the host path is absolute - `$(pwd)` on Linux/macOS or `${PWD}`
in Windows PowerShell.

### Do bind mount changes sync both ways?

Yes. A bind mount is a live link: edit a file on your computer and the container sees it
instantly, and files the container writes appear on your computer too.
