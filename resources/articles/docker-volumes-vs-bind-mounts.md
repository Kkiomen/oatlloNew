---
name: "Docker Volumes vs Bind Mounts"
slug: docker-volumes-vs-bind-mounts
short_description: "When to use a named volume, a bind mount, or tmpfs in Docker, plus the Linux permission and macOS performance traps that bite in production."
language: en
published_at: 2027-03-08 09:00:00
is_published: true
tags: [docker, devops, database, tooling]
---

The first time I lost a database I had done everything "right" — or so I thought. I ran `docker compose down` to rebuild a container, brought it back up, and every row in Postgres was gone. The data had been living inside the container's writable layer the whole time, and `down` removes containers. That single afternoon taught me more about Docker storage than any tutorial had.

This is the part nobody explains well: the *default* place your container writes files is the worst place to keep anything you care about. Volumes and bind mounts both fix that, but they solve different problems and fail in different ways. Here's how I decide, and the traps that cost me hours so they don't cost you any.

## The three ways to persist data

Docker gives you three mount types. They all make files survive a container restart, but that's where the similarity ends.

- **Named volumes** — Docker owns the storage. On Linux it lives under `/var/lib/docker/volumes/`, and you never touch that path directly. You reference it by name. This is the managed, portable option.
- **Bind mounts** — you point a container path at an *exact* directory on the host. The container and the host see the same files, live. Great for development, sharp edges everywhere else.
- **tmpfs mounts** — the mount lives in RAM and vanishes when the container stops. Nothing hits disk. Useful for secrets or scratch data you *want* to disappear.

The mental model that stuck for me: a volume is a managed disk you rent from Docker, a bind mount is a window into your own filesystem, and tmpfs is a scratchpad that self-destructs.

## When each one actually fits

Rules of thumb are only useful if they're specific, so here are the ones I follow.

**Database data → named volume, always.** Postgres, MySQL, Redis persistence, Elasticsearch indices. You want Docker to manage the storage, you want it decoupled from any host path, and you never need to open those files in an editor. A bind mount for a database also drags you straight into the permission mess I'll get to below.

**Source code in development → bind mount.** You edit a file in your IDE, the running container sees the change instantly, your dev server hot-reloads. That live link is the entire point of a bind mount. Nobody wants to rebuild an image to fix a typo.

**Config files you edit by hand → bind mount, read-only.** An `nginx.conf`, a `php.ini` tweak. Mount it with `:ro` so the container can't rewrite it.

**Secrets, PID files, session scratch → tmpfs.** If it should never touch the disk, or it's fine to lose on restart, keep it in RAM.

A quick way to remember it: if a human types the file, bind mount it. If a service writes the file, use a volume.

## The syntax, three ways

Let's make it concrete. A named volume on the CLI:

```bash
# Create a volume once, reuse it across containers
docker volume create pgdata

# Attach it: -v <volume-name>:<path-in-container>
docker run -d --name db \
  -e POSTGRES_PASSWORD=secret \
  -v pgdata:/var/lib/postgresql/data \
  postgres:16
```

A bind mount uses the same `-v` flag but with an **absolute host path** on the left. That's the whole tell: a leading `/` (or `./` in Compose) means bind mount, a bare name means volume.

```bash
# Bind mount: host path : container path
docker run -d --name web \
  -v "$(pwd)/src":/app/src \
  -v "$(pwd)/nginx.conf":/etc/nginx/nginx.conf:ro \
  node:22
```

I strongly prefer the newer `--mount` syntax for anything non-trivial, because it's explicit and it *fails loudly*. With `-v`, if you typo a host path, Docker silently creates an empty directory and mounts that — you get a container full of nothing and no error. `--mount` with `type=bind` errors out when the source doesn't exist, which is exactly what you want.

```bash
docker run -d --name web \
  --mount type=bind,source="$(pwd)/src",target=/app/src \
  --mount type=volume,source=pgdata,target=/var/lib/postgresql/data \
  --mount type=tmpfs,target=/tmp/cache \
  node:22
```

## Compose is where you'll actually live

Almost nobody runs raw `docker run` for long. Here's a Compose file that uses all three deliberately — a typical PHP app in development:

```yaml
services:
  app:
    build: .
    volumes:
      # Bind mount: your code, edited live
      - ./:/var/www/html
      # Named volume: keep vendor/ out of the bind mount (see below)
      - vendor_data:/var/www/html/vendor
    tmpfs:
      # Framework cache that we don't care about persisting
      - /var/www/html/storage/framework/cache

  db:
    image: mysql:8.4
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: app
    volumes:
      # Named volume: the database lives here, survives `down`
      - db_data:/var/lib/mysql

volumes:
  db_data:
  vendor_data:
```

That `vendor_data` volume solves a genuinely annoying problem. When you bind-mount your project root over `/var/www/html`, the host's (usually empty or platform-mismatched) `vendor/` shadows whatever the image built. Mounting a named volume over just `/var/www/html/vendor` lets the container keep its own dependencies while your source stays live. The same trick works for `node_modules` in a Node project. It looks odd the first time — a volume layered on top of a bind mount — but it's the standard fix.

One detail: named volumes declared under the top-level `volumes:` key are namespaced per project. Compose prefixes them with the project name (the directory name, or `-p`), so `db_data` becomes `myapp_db_data`. If you rename the folder, Compose looks for a *new* volume and your data appears to vanish. It's still there under the old name — `docker volume ls` will show it.

## The Linux permission trap

This is the one that generates the most confused Stack Overflow questions, and it only happens with bind mounts.

Inside the container, processes run as some user — often `root`, but plenty of hardened images run as UID 1000 or a named app user. When that process writes to a **bind-mounted** directory, the file lands on your host owned by *that* UID. If the container's user is UID 999 and you're UID 1000 on the host, you get files you can't edit without `sudo`. Flip it around — the container runs as a non-root user and can't write to a directory your host owns — and you get:

```
Permission denied: '/var/www/html/storage/logs/laravel.log'
```

Named volumes mostly dodge this because Docker initializes a fresh volume by copying the image's directory *including its ownership*, so the container's user already owns everything. Bind mounts can't do that — the host directory already exists with host ownership, and Docker won't touch it.

The fix I reach for is matching the UID at build time so the container user *is* you:

```dockerfile
FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

RUN groupadd -g ${GID} app \
 && useradd -u ${UID} -g app -m app

USER app
```

```yaml
services:
  app:
    build:
      context: .
      args:
        UID: ${UID:-1000}
        GID: ${GID:-1000}
```

Then run with `UID=$(id -u) GID=$(id -g) docker compose up --build`. Now files written in the container come out owned by you on the host. On Docker Desktop for Mac and Windows you can usually ignore all of this — the VM layer handles UID translation — but on native Linux and in CI it will absolutely bite you.

If you inherit a project that's already gone wrong, the blunt recovery is `sudo chown -R $(id -u):$(id -g) .` on the host, then fix the Dockerfile so it doesn't happen again.

## Performance on macOS and Windows

Here's a thing that surprises people coming from Linux: **bind mounts are slow on Mac and Windows**, and not by a little. Docker doesn't run natively there — it runs inside a Linux VM, and every bind-mounted file read has to cross the boundary between the host filesystem and the VM. For a directory Docker walks constantly, like `node_modules` or `vendor`, that crossing dominates. I've seen a `composer install` that takes 20 seconds on Linux crawl for minutes on an Intel Mac with everything bind-mounted.

The mitigations, in order of how much I trust them:

1. **Don't bind-mount dependency directories.** Use the named-volume-over-bind-mount trick above for `node_modules`/`vendor`. This alone fixes most of the pain because those directories are where the file churn lives.
2. **Use consistency flags** on macOS: append `:cached` or `:delegated` to a bind mount (`- ./:/app:cached`) to tell Docker it can relax host/container sync guarantees. On Apple Silicon with VirtioFS enabled, these matter far less than they used to.
3. **Turn on VirtioFS** in Docker Desktop settings if you're on a recent version — it's a large step up from the older gRPC-FUSE sharing.

Named volumes don't have this problem at all, because they live *inside* the VM's own filesystem. No boundary to cross. That's another quiet reason databases belong in volumes even in development.

## Backing up a volume

The catch with named volumes is that you can't just `cp` them — they're buried in Docker-managed storage. The idiom is to spin up a throwaway container that mounts both the volume and a host directory, then tar between them:

```bash
# Back up: tar the volume into the current host directory
docker run --rm \
  -v db_data:/data:ro \
  -v "$(pwd)":/backup \
  alpine tar czf /backup/db_data-$(date +%F).tar.gz -C /data .
```

```bash
# Restore: untar into a (possibly new) volume
docker run --rm \
  -v db_data:/data \
  -v "$(pwd)":/backup \
  alpine sh -c "tar xzf /backup/db_data-2027-03-08.tar.gz -C /data"
```

The `alpine` container is a disposable tool here — it exists for the length of one `tar` and `--rm` deletes it. Mounting the source `:ro` during backup is cheap insurance against fat-fingering the direction.

For databases specifically, I'd still rather use the database's own dump tool (`pg_dump`, `mysqldump`) run via `docker exec`, because a logical dump is portable across engine versions and a raw file copy is not. Restoring a Postgres 16 data directory into a Postgres 15 image will simply refuse to start. The tar approach is the right call for volumes holding *arbitrary* files where no smarter export exists.

## A short field guide to the traps

- A bare name (`pgdata`) is a volume; a path (`./src`, `/etc/nginx`) is a bind mount. Typo the path with `-v` and Docker silently mounts an empty directory.
- `docker compose down` removes containers but **keeps** named volumes. `down -v` deletes them — that flag has erased more dev databases than any bug.
- Renaming your project folder changes the Compose volume prefix; the old data is still there under the old name.
- Bind-mounting over a directory the image populated (like `vendor/`) shadows the image's contents with the host's.
- On Mac/Windows, bind-mounted dependency folders are the performance killer, not your app code.

## FAQ

**Does `docker compose down` delete my volumes?**
No — plain `down` removes containers and networks but leaves named volumes untouched. It only deletes them if you add `-v` (or `--volumes`). Anonymous volumes attached to the removed containers can go with `--remove-orphans` in some setups, which is another reason to always *name* volumes you care about.

**Can I convert a bind mount to a named volume without losing data?**
Not automatically, but it's a two-step copy. Create the volume, then run a temporary container that mounts both the old host path (as a bind) and the new volume, and `cp -a` the data across. After that, point your Compose file at the volume.

**Why are my files owned by root after a bind mount on Linux?**
Because a process running as root inside the container wrote them, and the host sees files with UID 0. Match the container's user to your host UID at build time (the `useradd -u $(id -u)` pattern above), or `chown` the directory back to yourself. This is a bind-mount-only problem; volumes inherit the image's ownership.

**Should I use a bind mount for my database in production?**
I wouldn't. Use a named volume so Docker manages the storage and you avoid the permission and performance issues bind mounts drag in. In production the honest answer is often to run the database *outside* Docker entirely, or on managed infrastructure — but if it's containerized, it's a volume.

## Where this leaves you

The one rule that would have saved me that lost database: anything a service writes and you'd miss goes in a **named volume**, and you decide that on day one, not after the crash. Bind mounts are for the files *you* edit — code and config in development — and everywhere else they trade convenience for permission headaches and slow I/O off Linux.

Next time you write a Compose file, go line by line through every path you mount and say out loud which of the three it is and why. If you can't justify a bind mount, it's probably a volume. That thirty-second habit is the whole lesson.
