---
title: "Building images and using variables"
slug: build-and-environment
seo_title: "Docker Compose: build, .env and depends_on"
seo_description: "Build your own image in Docker Compose, load variables from a .env file, and control startup order with depends_on between services."
---

## Building your own image in Compose

So far our services used ready-made images. But you can also point a service at a
**Dockerfile** and have Compose build it. Instead of `image:`, use `build:`:

```yaml
services:
  app:
    build: .
    ports:
      - "8080:8080"
```

`build: .` tells Compose to build the image from the `Dockerfile` in the current
folder (the same `.` build context you used with `docker build`). When you run
`docker compose up --build`, Compose builds the image first, then starts the
container.

The `--build` flag forces a rebuild so your latest code changes are included:

```bash
docker compose up -d --build
```

## Loading variables from a .env file

Hard-coding passwords in your Compose file isn't ideal. Compose automatically reads a
file named `.env` in the same folder and lets you reference those values with
`${...}`.

Create a `.env` file:

```text
DB_PASSWORD=secret
DB_NAME=app
```

Then use the variables in your Compose file:

```yaml
services:
  db:
    image: mysql:8
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: ${DB_NAME}
```

Now the secrets live in `.env` (which you keep out of version control, remember the
`.dockerignore` and `.gitignore` lessons) instead of in the Compose file itself.

## Controlling startup order with depends_on

If your app needs the database to start first, `depends_on` expresses that:

```yaml
services:
  app:
    build: .
    depends_on:
      - db

  db:
    image: mysql:8
```

`depends_on` makes Compose start `db` before `app`. For basic ordering, it does the job -
but there's an important catch, which the note below covers.

You now have all the Compose building blocks. In the next chapter we'll put
everything together and
[dockerize a real PHP/Laravel app](/course/docker-basics/real-project/a-php-dockerfile).

## The depends_on trap

`depends_on` is more limited than it looks, and it catches people out. It waits for the
database **container to start**, not for the database to be **ready for connections**.
A database can take a few seconds to accept queries after its container starts, so an
app that connects immediately may still fail on the first try. The practical fix is to
let your app **retry** its connection - most frameworks do this already - rather than
assuming `depends_on` guarantees readiness.

## FAQ

### What does depends_on do in Docker Compose?

It sets startup order - Compose starts the listed service first. But it only waits for
that container to **start**, not to be fully ready, so pair it with connection retries in
your app for reliability.

### How do I use environment variables in a Compose file?

Put values in a `.env` file next to your Compose file and reference them with `${...}`,
like `${DB_PASSWORD}`. Compose reads `.env` automatically, keeping secrets out of the
committed Compose file.

### When should I use build instead of image?

Use `image:` to run a ready-made image, and `build:` to build your own from a local
Dockerfile. Add `--build` to `docker compose up` to force a rebuild after code changes.
