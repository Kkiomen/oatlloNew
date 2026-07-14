---
title: "Writing a docker-compose.yml"
slug: writing-compose-file
seo_title: "Write your first docker-compose.yml file"
seo_description: "Write your first docker-compose.yml: define a service with an image and ports, then start it with docker compose up and stop it with down."
---

## A one-service file

Let's start small. Create a file named `docker-compose.yml` with a single service - an
Nginx web server:

```yaml
services:
  web:
    image: nginx
    ports:
      - "8080:80"
```

Reading it:

- `services:` begins the list of containers.
- `web:` is the name we chose for this service.
- `image: nginx` - the image to use, exactly like `docker run nginx`.
- `ports:` publishes ports, exactly like `-p 8080:80`. Note the list item starts with
  `-` and the value is quoted.

## Start services with docker compose up

In the same folder, run:

```bash
docker compose up
```

Compose reads the file, pulls the image if needed, and starts the `web` service. Open
`http://localhost:8080` and you'll see Nginx. Your terminal shows the logs; press
`Ctrl + C` to stop.

To run it in the background, add `-d` (detached), just like with `docker run`:

```bash
docker compose up -d
```

## Stop everything with docker compose down

To stop and remove everything Compose created (containers and the network), run:

```bash
docker compose down
```

That's the core loop: `up` to start, `down` to stop. Notice how much shorter this is
than remembering all the individual flags.

## Check status with docker compose ps

Compose has its own status command that shows the services from your file:

```bash
docker compose ps
```

A one-service file isn't very exciting on its own - the real value shows up when your
app has several parts. Let's
[add a database next](/course/docker-basics/docker-compose/multi-container-apps).

## A small habit: rebuild when things look stale

`docker compose up` reuses what it already has. So when you change something and the app
doesn't seem to update, the usual fix is `docker compose up -d --build` (you'll meet
[`--build` properly in a later lesson](/course/docker-basics/docker-compose/build-and-environment))
or, for a truly clean slate, `docker compose down`
followed by `up`. Beginners often stare at an old result wondering why their change
didn't take - Compose simply reused the running containers. A quick down/up clears it.

## FAQ

### What is the difference between docker compose up and down?

`up` creates and starts everything defined in your Compose file (add `-d` to run in the
background). `down` stops and removes those containers and the network Compose created.
Start with `up`, tidy up with `down`.

### Where do I put the docker-compose.yml file?

In your project root, and run `docker compose` from that folder. Compose looks for a
file named `docker-compose.yml` in the current directory by default.

### How do I run Compose in the background?

Add `-d`: `docker compose up -d`. It starts the services detached and returns your
terminal, just like `-d` on `docker run`.
