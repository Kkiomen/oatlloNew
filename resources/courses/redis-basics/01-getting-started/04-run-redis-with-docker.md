---
title: "Run Redis with Docker"
slug: run-redis-with-docker
seo_title: "Run Redis With Docker: docker run, Compose, redis-cli"
seo_description: "Run Redis locally with Docker. Start a container with docker run, add a docker-compose service, open redis-cli, and verify with PING."
---

## Why run Redis with Docker?

To use Redis you need Redis running somewhere. The easiest way to run Redis on your machine is Docker: you install nothing system-wide, and you can throw the whole thing away cleanly when you are done.

This lesson assumes you have Docker installed and running. If you can run `docker --version` and see a number, you are ready.

## Start Redis with one command

Open a terminal and run:

```bash
docker run -d --name redis -p 6379:6379 redis:7
```

That is the whole thing. Redis is now running. Let's break down each part:

- `docker run` starts a new container.
- `-d` runs it **detached**, in the background, so your terminal stays free.
- `--name redis` gives the container the name `redis` so you can refer to it later.
- `-p 6379:6379` maps port 6379 inside the container to port 6379 on your machine. 6379 is the default Redis port.
- `redis:7` is the image to run: official Redis, version 7.

Check that it is running:

```bash
docker ps
```

You should see a container named `redis` in the list.

## Run Redis with docker-compose instead

If you prefer a file you can commit to your project, use a minimal `docker-compose.yml`:

```yaml
services:
  redis:
    image: redis:7
    ports:
      - "6379:6379"
```

Then, from the folder with that file, start it:

```bash
docker compose up -d
```

This does the same job as the `docker run` command above. The advantage is that the setup lives in a file, so your teammates get the exact same Redis with one command.

## Get a shell into Redis

Redis ships with a built-in command-line tool called `redis-cli`. It lets you type commands to Redis directly. Because Redis is running inside the container, we run the tool inside the container too:

```bash
docker exec -it redis redis-cli
```

Breaking it down:

- `docker exec` runs a command inside a running container.
- `-it` makes it **interactive** so you can type and see responses.
- `redis` is the container name from earlier.
- `redis-cli` is the tool we want to run.

Your prompt changes to something like this:

```text
127.0.0.1:6379>
```

That prompt means you are talking to Redis. We will use it constantly in the next lesson.

## Verify it works with PING

The simplest way to confirm Redis is alive is the `PING` command. At the `redis-cli` prompt, type:

```bash
PING
```

Redis replies:

```text
PONG
```

`PONG` means Redis heard you and is healthy. That is the "hello world" of Redis.

To leave the prompt, type `exit` or press `Ctrl+D`. The container keeps running in the background.

## Common mistake

If `docker run` fails with a message about port 6379 being **already in use**, something else (often a Redis you installed earlier, or another container) is using that port. Stop the other one, or map a different local port like `-p 6380:6379`.

A second snag catches people the day after: you `docker stop redis`, come back later, run the exact same `docker run` command, and Docker complains the name `redis` is **already in use**. Stopping a container does not delete it, so the name is still taken. Either start the existing one again with `docker start redis`, or remove it first with `docker rm redis` and then run fresh.

## FAQ

### Do I have to install Redis to follow this course?

No. Docker runs it for you. That is why we start here instead of installing Redis directly.

### How do I stop and remove the container?

Run `docker stop redis` to stop it and `docker rm redis` to remove it. With compose, use `docker compose down`.

### Why version 7?

`redis:7` pins a stable, widely used major version so everyone in this course runs the same Redis and sees the same behavior.

### The container is running but PING does nothing. What now?

Make sure you are actually at the `redis-cli` prompt (it shows `127.0.0.1:6379>`). `PING` is a Redis command, not a shell command, so it only works inside `redis-cli`.
