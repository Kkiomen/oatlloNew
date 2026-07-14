---
title: "Port mapping"
slug: port-mapping
seo_title: "Docker port mapping with -p explained"
seo_description: "Learn Docker port mapping: use the -p host:container flag in docker run to expose a container's port and reach the app from your browser."
---

## Containers are isolated by default

Each container has its own private network. That's why, back in the
[Nginx lesson](/course/docker-basics/images-and-containers/running-containers), you couldn't
open the server in your browser until you added `-p`. By default, a port
inside a container is not reachable from your computer.

## Publish a container port with -p

To make a container's port reachable, you **publish** it with `-p`, in the form
`-p host:container`:

```bash
docker run -d -p 8080:80 nginx
```

Read `8080:80` as: "traffic arriving at port `8080` on my computer is forwarded to
port `80` inside the container". Nginx listens on port 80 inside, so now
`http://localhost:8080` on your machine reaches it.

- The **left** number is the port on **your computer** (you choose it).
- The **right** number is the port **inside the container** (decided by the app).

## Choosing host and container ports

The two numbers don't have to match. If port 8080 is already busy, pick another host
port:

```bash
docker run -d -p 3000:80 nginx
```

Now the site is at `http://localhost:3000`, still hitting port 80 inside the
container. You can run several containers this way, each on a different host port:

```bash
docker run -d -p 8081:80 --name site-a nginx
docker run -d -p 8082:80 --name site-b nginx
```

Two separate Nginx servers, reachable at `localhost:8081` and `localhost:8082`.

## Checking what's published

`docker ps` shows the port mappings for running containers in its `PORTS` column, so
you can always see which host port reaches which container.

Port mapping connects a container to **your computer**. But how do containers talk to
**each other** - say, a web app talking to its database? That's
[the next lesson](/course/docker-basics/networking/container-networks).

## The mix-up to avoid: which port is which

Almost everyone reverses `-p host:container` at least once. Remember it left-to-right:
**your machine first, the container second.** With `-p 3000:80`, you open
`localhost:3000` in your browser, and it reaches port 80 inside. If the page won't load,
the usual cause is either a swapped pair or pointing your browser at the container port
(80) instead of the host port (3000).

## FAQ

### What does -p 8080:80 mean in Docker?

It maps port 8080 on your computer to port 80 inside the container. Traffic to
`localhost:8080` is forwarded to the app listening on port 80 in the container. Host port
on the left, container port on the right.

### Do the host and container ports have to match?

No. They're independent. `-p 3000:80` is perfectly valid - use any free host port on the
left; the right side must match the port the app actually listens on inside.

### Why can't I reach my container in the browser?

Most likely you didn't publish a port. A container's ports are isolated until you add
`-p host:container`. Check `docker ps` to confirm the mapping, and use the host (left)
port in your browser.
