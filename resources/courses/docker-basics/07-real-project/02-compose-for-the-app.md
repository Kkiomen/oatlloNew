---
title: "Wiring the app and database with Compose"
slug: compose-for-the-app
seo_title: "Docker Compose for a Laravel app and MySQL database"
seo_description: "Write a docker-compose.yml that builds your PHP/Laravel app image and connects it to a MySQL database, then run migrations with compose exec."
---

## The Compose file

Now let's connect the app image from
[the previous lesson](/course/docker-basics/real-project/a-php-dockerfile) to a MySQL database. In your
project root, create `docker-compose.yml`:

```yaml
services:
  app:
    build: .
    ports:
      - "8000:8000"
    environment:
      DB_HOST: db
      DB_DATABASE: ${DB_NAME}
      DB_USERNAME: root
      DB_PASSWORD: ${DB_PASSWORD}
    depends_on:
      - db

  db:
    image: mysql:8
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_DATABASE: ${DB_NAME}
    volumes:
      - db-data:/var/lib/mysql

volumes:
  db-data:
```

Everything here comes from the
[Compose chapter](/course/docker-basics/docker-compose/what-is-compose):

- `app` uses `build: .` to build from our Dockerfile.
- `ports` publishes port 8000 so you can open the app in your browser.
- `environment` on `app` tells Laravel how to reach the database. Notice `DB_HOST: db`
  - that's the **service name** of the database, thanks to Compose's automatic
    name-based networking.
- `db` runs MySQL and stores its data in the `db-data` volume, so it survives
  restarts.
- `depends_on` starts the database before the app.

## The .env file

Create a `.env` file next to the Compose file for the secrets it references:

```text
DB_NAME=app
DB_PASSWORD=secret
```

## Starting the whole app

Build and start everything with one command:

```bash
docker compose up -d --build
```

Compose builds your app image, starts MySQL, creates the network and volume, and
launches both containers. Open `http://localhost:8000` to see your app.

## Run artisan commands with compose exec

You'll often need to run commands inside the app container - like database
migrations. Use `docker compose exec` (the Compose version of `docker exec`):

```bash
docker compose exec app php artisan migrate
```

This runs `php artisan migrate` inside the running `app` container, which can reach
the database at host `db`.

## Tearing down

Stop and remove everything when you're done:

```bash
docker compose down
```

You've now dockerized a real PHP/Laravel app with a database - using only the pieces
this course taught. The final chapter covers
[practices that make your images smaller, faster and safer](/course/docker-basics/best-practices/smaller-images).

## The migration timing gotcha

If you run migrations too early, they fail with a "connection refused" error - not
because your config is wrong, but because MySQL needs a few seconds to be ready after
its container starts, and `depends_on` doesn't wait for that. If `docker compose exec
app php artisan migrate` fails right after `up`, give the database a moment and run it
again. In real projects people add a small wait-or-retry step so this happens
automatically.

## FAQ

### How do I run artisan (or other) commands inside a container?

Use `docker compose exec <service> <command>`, for example `docker compose exec app php
artisan migrate`. It runs the command inside the already-running service container,
which can reach the database by its service name.

### Why does php artisan migrate fail right after docker compose up?

The database container has started but MySQL isn't ready for connections yet. Wait a few
seconds and rerun the migration, or add a wait/retry step so the app doesn't connect too
early.

### What host should Laravel use to reach the database in Compose?

The database **service name** from your Compose file (here `db`), not `localhost`. Inside
a container, `localhost` points at that same container, so the service name is what
reaches MySQL.
