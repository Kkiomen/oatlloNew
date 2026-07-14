---
title: "Running containers"
slug: running-containers
seo_title: "How to Run Docker Containers with docker run"
seo_description: "Learn how to run Docker containers with docker run: start them in the background with -d, name them with --name, and map ports with -p."
---

## The docker run command

You already met `docker run`. Let's use it with a real, long-running application: the
**Nginx** web server.

```bash
docker run nginx
```

Docker downloads the `nginx` image (once) and starts a container. This time the
container **keeps running** - Nginx is a server, so it stays alive waiting for
requests. Your terminal is now "stuck" showing the server's logs. Press `Ctrl + C` to
stop it.

## Run a container in the background with -d

Usually you don't want a server to occupy your terminal. Add the `-d` flag (short for
**detached**) to run the container in the background:

```bash
docker run -d nginx
```

Docker prints a long string of letters and numbers - the container's **ID** - and
gives your terminal back. The server keeps running behind the scenes.

## Name a container with --name

By default Docker assigns a random name to each container. You can choose your own
with `--name`:

```bash
docker run -d --name my-web nginx
```

Now you can refer to the container as `my-web` instead of a random ID, which is much
easier to type. Names must be unique - you can't have two containers called `my-web`
at the same time.

## Reaching the server from your browser

Right now Nginx is running, but you can't open it in your browser yet, because the
container's network is isolated from your computer. To connect them, you **map a
port** with `-p`:

```bash
docker run -d --name my-web -p 8080:80 nginx
```

The `-p 8080:80` part means "send traffic from port `8080` on my computer to port
`80` inside the container" (Nginx listens on port 80 by default). Now open
`http://localhost:8080` in your browser and you'll see the Nginx welcome page.

We'll cover [ports and networking](/course/docker-basics/networking/port-mapping) properly
in a later chapter. For now, just know that `-p host:container` is how you reach a service
running inside a container.

In the next lesson we'll learn how to
[see and manage the containers](/course/docker-basics/images-and-containers/listing-and-stopping-containers)
we've started.

## A gotcha with names and ports

Two errors you'll almost certainly hit once. If you reuse a `--name` that already
exists, Docker refuses with "name is already in use" - pick another name or remove the
old container first. And if a host port is already taken (say something else uses
`8080`), the run fails with a "port is already allocated" error - just map a different
host port like `-p 8081:80`. Both are normal, and both have a one-line fix.

## FAQ

### What does -d do in docker run?

It runs the container **detached** - in the background - so it doesn't tie up your
terminal. You get the container ID back and keep working. Leave it off and the
container's output stays attached to your terminal.

### How do I access a container from my browser?

Publish a port with `-p host:container`, for example `-p 8080:80`, then open
`http://localhost:8080`. Without a published port, the container's network is isolated
and the browser can't reach it.

### Why is my port already in use?

Another process (or another container) is already using that host port. Pick a free one
on the left side of `-p`, like `-p 8081:80`. The container side can stay the same.
