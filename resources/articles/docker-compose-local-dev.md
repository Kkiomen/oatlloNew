---
name: "Docker Compose for Local Development Setup: A Practical Guide"
slug: docker-compose-local-dev
short_description: "Build a Docker Compose local dev setup with app, MySQL, and Redis: a full compose.yaml explained service by service, healthchecks, and live reload."
language: en
published_at: 2026-11-16 09:00:00
is_published: true
tags: [docker, docker-compose, devops, local-development]
---

A good **docker compose local dev** setup is the difference between a new teammate being productive in ten minutes and losing their first afternoon to "which PHP version do you have installed again?" I have onboarded people onto projects where the README was a wall of `brew install` lines, and every one of those lines was a small chance to get something subtly wrong. Compose fixes that by writing the whole environment down as one file that everyone runs the same way.

This guide builds a real local stack from scratch: an application container, a MySQL database, and Redis for cache and queues. You get the full `compose.yaml`, an explanation of every service, the commands you actually type each day, and the mistakes that cost me the most time so you can skip them.

## What Docker Compose gives you locally

Running one container by hand is easy. The pain starts when your app needs a database, a cache, and maybe a queue worker, and all of them have to find each other on a network. Doing that with raw `docker run` flags means memorizing a paragraph of arguments and repeating it perfectly every time.

Compose lets you declare all of those services in a single YAML file and bring them up together with one command. A few concrete wins:

- **One command to start everything.** `docker compose up -d` boots the whole stack, wired together, in the right order.
- **A private network for free.** Services reach each other by name, so your app connects to `db`, not to some IP that changes.
- **Reproducible across machines.** The file is committed to the repo, so Linux, macOS, and Windows developers get the identical stack.

One naming note before we go further. Modern Compose is a plugin, invoked as `docker compose` (two words, v2 CLI), not the old standalone `docker-compose` binary. The v2 tooling also dropped the top-level `version:` key in the file. If a tutorial still opens with `version: "3.8"`, it predates the current spec, and you can safely leave that line out.

## The project layout

Nothing exotic here. A typical PHP or Node project ends up looking like this:

```bash
myapp/
├── compose.yaml
├── compose.override.yaml
├── .env
├── Dockerfile
└── src/
```

Compose reads `compose.yaml` (or the older `docker-compose.yml`) from whatever directory you run the command in. The `.env` file next to it is picked up automatically for variable interpolation, which we lean on below.

## A complete compose.yaml

Here is a working stack for a PHP app backed by MySQL and Redis. Read it once top to bottom, then I will break down each service.

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8080:80"
    volumes:
      - ./src:/var/www/html
    environment:
      DB_HOST: db
      DB_DATABASE: ${DB_DATABASE}
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}
      REDIS_HOST: redis
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_started

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
    ports:
      - "3306:3306"
    volumes:
      - db-data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-p${DB_ROOT_PASSWORD}"]
      interval: 5s
      timeout: 3s
      retries: 10

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

volumes:
  db-data:
```

### The app service

This is your code. Instead of pulling a prebuilt image, `build:` points Compose at the local `Dockerfile`, so you control the exact PHP extensions and system packages baked in.

The line that matters most for daily work is the bind mount: `./src:/var/www/html`. It maps your source folder straight into the container, so editing a file on your host is instantly reflected inside. No rebuild, no restart, live reload for interpreted languages. That is the whole reason local Compose feels fast.

`ports: "8080:80"` publishes the container's port 80 to `localhost:8080` on your machine. The `depends_on` block is where a lot of setups quietly break, so it gets its own section below.

### The db service

MySQL 8.0 from the official image. The `environment` keys with the `MYSQL_` prefix are read by that image on first boot to create a database and a user for you, which saves a round of manual SQL.

The critical piece is `db-data:/var/lib/mysql`. That is a **named volume**, and it is what keeps your data alive when the container is recreated. Bind-mounting a host folder for MySQL data works but tends to cause permission headaches across operating systems, so a named volume is the safer default for databases.

Notice the `healthcheck`. MySQL takes a few seconds to become ready even after the container reports "started," and that gap is the source of the classic connection-refused error on boot.

### The redis service

The lightest of the three. The `redis:7-alpine` image is tiny and needs no configuration to run as a cache or queue backend. It gets no volume here because losing local cache data on restart is fine; if you were persisting real data you would add one.

## Getting depends_on right

Plain `depends_on` only waits for a container to **start**, not for the service inside it to be ready to accept connections. Your app will happily launch, try to connect to a MySQL that is still initializing, and fall over.

The fix is the two lines in the app service:

```yaml
depends_on:
  db:
    condition: service_healthy
```

With `condition: service_healthy`, Compose holds the app back until the `db` healthcheck actually passes. This is why the healthcheck on the database is not optional decoration; it is what makes ordered startup work. Redis starts fast and needs no such gate, so `service_started` is enough there.

## The .env file

The `${DB_PASSWORD}` style references in the compose file are filled in from a `.env` file sitting beside it:

```bash
DB_DATABASE=myapp
DB_USERNAME=myapp
DB_PASSWORD=secret
DB_ROOT_PASSWORD=rootsecret
```

Two things worth saying out loud. Keep `.env` out of version control and commit a `.env.example` with dummy values instead. And note that this interpolation happens on your machine before the containers even start, which is not the same as the environment variables that end up inside the running container. Mixing those two up trips people up constantly.

## The commands you use every day

Once the file is in place, the daily loop is small:

```bash
# Build images and start everything in the background
docker compose up -d --build

# Watch logs from all services (or name one, e.g. app)
docker compose logs -f app

# Open a shell or run a command inside the app container
docker compose exec app bash
docker compose exec app php artisan migrate

# Stop containers but keep your database volume
docker compose down

# Stop AND delete volumes — wipes the database, use with care
docker compose down -v
```

That last one deserves a warning label. `docker compose down -v` removes the named volumes too, which means your local database is gone. It is exactly what you want for a clean reset and exactly what you do not want when you just meant to stop the stack for the night.

## Override files and profiles

Not every developer should be forced into the same tweaks, and debug-only services have no business running for the whole team. Compose has two features for this.

An **override file** named `compose.override.yaml` is merged on top of the base file automatically. It is the natural home for local-only changes such as mounting extra debug config or exposing an additional port, and since it can be gitignored, each person can keep their own.

**Profiles** let you tag optional services so they only start when asked. Tag a mail-catcher or an admin UI with `profiles: ["tools"]` and it stays dormant until you run `docker compose --profile tools up -d`. Handy for keeping the default startup lean.

## Pitfalls that cost me real time

- **Leaving the `version:` key in.** Harmless but noisy warnings on modern Compose, and it signals a copied-from-2019 config. Delete it.
- **Forgetting the healthcheck.** Without it, `condition: service_healthy` cannot work and your app races the database on every boot.
- **Bind-mounting MySQL's data directory.** Works on Linux, causes permission and corruption grief on macOS and Windows. Use a named volume.
- **Port clashes.** If `3306` is already taken by a MySQL you installed years ago, the container fails to bind. Change the host side to something like `"3307:3306"`.
- **Editing `.env` and expecting a live change.** Interpolated values are read at `up` time, so you have to recreate the containers for a new value to take effect.
- **Running `down -v` out of habit.** Muscle memory from `up` can bite. Reserve the `-v` for deliberate resets.

If you are moving from this local setup toward shipping, the concerns shift to image size and multi-stage builds. Two follow-ups worth reading are [/blog/dockerize-laravel-production](/blog/dockerize-laravel-production) for the production side and [/blog/laravel-github-actions](/blog/laravel-github-actions) for running the same containers in CI.

## FAQ

### Should I use one Dockerfile for local and production?

You can, with a multi-stage Dockerfile that has a `dev` target and a `prod` target. Local Compose builds the `dev` stage with dev dependencies and the bind mount; your production build targets the lean stage. It keeps behavior consistent without shipping your whole toolchain to production.

### Why can my app not connect to the database on the first `up`?

Almost always the readiness gap. The container started but MySQL inside it is still initializing. Add a `healthcheck` to the db service and `depends_on` with `condition: service_healthy` on the app, as shown above.

### Do I need nginx in the local stack?

Not for a simple setup where your app image already serves HTTP. Add an nginx service when you want to mirror production's reverse proxy, serve static files separately, or run several apps behind one entry point. For a first local environment it is optional weight.

### Is `docker-compose` (with the hyphen) still valid?

The hyphenated v1 binary is deprecated. Use `docker compose` as two words, which ships as part of modern Docker. The file syntax is largely the same, but v2 is what receives updates.

## Wrapping up

A solid local Compose file is boring in the best way. You clone the repo, drop in a `.env`, run `docker compose up -d --build`, and you are looking at a running app with a real database and cache in under a minute. No version drift, no "works on my machine," no half-day of setup for the next person who joins.

Start with the `compose.yaml` above, adjust the image versions and ports to your project, and lean on the healthcheck-plus-`depends_on` pattern so your stack boots in the right order every time. Once that feels natural, layer in an override file for personal tweaks and profiles for the optional extras. That is the whole foundation, and it will carry you a long way before you ever need something heavier.