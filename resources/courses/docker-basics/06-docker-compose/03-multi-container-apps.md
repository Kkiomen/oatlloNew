---
title: "Multi-container apps"
slug: multi-container-apps
seo_title: "Run a multi-container app with Docker Compose"
seo_description: "Run a multi-container app with Docker Compose: define several services in docker-compose.yml with automatic networking, volumes and env vars."
---

## Adding a database

Most real apps need more than one container. Let's describe an app together with a
MySQL database. Here each part is a **service**:

```yaml
services:
  web:
    image: nginx
    ports:
      - "8080:80"

  db:
    image: mysql:8
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: app
    volumes:
      - db-data:/var/lib/mysql

volumes:
  db-data:
```

New pieces compared to the last lesson:

- A second service, `db`, using the `mysql:8` image.
- `environment:` sets environment variables inside the container - here the database
  password and an initial database name. (This is the Compose version of the `-e`
  flag you used earlier.)
- `volumes:` under `db` mounts a volume at MySQL's data folder, so the data survives
  `docker compose down`, just like the
  [volumes chapter](/course/docker-basics/data-and-volumes/volumes) taught.
- A top-level `volumes:` section declares the `db-data` volume so Compose creates and
  manages it.

## Automatic networking

Here's the best part. You did **not** create a network, yet `web` and `db` can already
reach each other. Compose automatically puts all services on a shared network and lets
them find each other **by service name**. So your app would connect to the database
using the host name `db` and port `3306` - no extra configuration.

This is the same name-based networking from the
[previous chapter](/course/docker-basics/networking/container-networks), set up for you
automatically.

## Start the multi-container app

Start both services with one command:

```bash
docker compose up -d
```

Compose creates the network, the volume, and both containers. Check them:

```bash
docker compose ps
```

And when you're done:

```bash
docker compose down
```

To also delete the volume (and its data), add `-v`:

```bash
docker compose down -v
```

You can now describe a whole multi-part app in one file. Next, let's make that file
cleaner and more flexible with
[build settings and environment variables](/course/docker-basics/docker-compose/build-and-environment).

## The connection detail everyone gets wrong once

When your app connects to the database, the host is the **service name** (`db`), not
`localhost`. This trips up almost everyone: inside a container, `localhost` means *that
same container*, so pointing the app at `localhost` for the database fails. Use `db`
(the service name) and the database's internal port `3306`. Remember this and you'll
skip the single most common Compose connection bug.

## FAQ

### How do containers in Docker Compose talk to each other?

Compose puts every service on a shared network automatically, and they reach each other
by **service name**. Your app connects to the database at host `db` (the service name),
with no manual network setup.

### Why does my app connect to the database with the service name, not localhost?

Because `localhost` inside a container refers to that container itself. The database runs
in a different container, so you address it by its service name (`db`) on the shared
Compose network.

### How do I persist database data in Docker Compose?

Give the database service a named volume mounted at its data folder (for MySQL,
`/var/lib/mysql`) and declare that volume in the top-level `volumes:` section. The data
then survives `docker compose down`.
