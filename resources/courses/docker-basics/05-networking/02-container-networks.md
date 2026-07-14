---
title: "Connecting containers with networks"
slug: container-networks
seo_title: "Docker Networks: Connect Containers by Name"
seo_description: "Create a Docker network so containers reach each other by name - like a web app connecting to its database - using docker network create."
---

## The problem: containers can't find each other

Suppose you run a web app in one container and a database in another. The web app
needs to connect to the database. By default, separate containers can't easily reach
each other - each is isolated.

The solution is a **network**. When two containers are on the same Docker network,
they can talk to each other, and - very handily - they can find each other **by
container name**.

## Create a Docker network

Create a network with a name of your choice:

```bash
docker network create my-net
```

## Connect containers to the network

Start each container with `--network` pointing at that network. Let's run a database
and then a small container that can reach it:

```bash
docker run -d --name db --network my-net \
  -e MYSQL_ROOT_PASSWORD=secret \
  mysql:8
```

Now start another container on the **same** network:

```bash
docker run -it --network my-net alpine sh
```

Inside that container, you can reach the database using its **name**, `db`, as if it
were a hostname:

```bash
ping db
```

You'll get replies. Docker runs a small built-in DNS that turns container names into
addresses, but only for containers **on the same network**. So the web app would
connect to the database using the host name `db` and its port `3306` - no IP
addresses to hunt down.

## Why name-based networking is so useful

Container IP addresses can change every time they restart, so hard-coding them would
be fragile. Names are stable: as long as the database container is called `db`, other
containers on the network reach it at `db`. This is exactly how multi-container apps
wire themselves together.

## Cleaning up

List and remove networks like other Docker objects:

```bash
docker network ls
docker network rm my-net
```

Manually creating networks and starting each container works, but it's a lot of
typing for an app with several parts. In the next chapter,
[**Docker Compose**](/course/docker-basics/docker-compose/what-is-compose) lets us describe
the whole setup - containers, networks and volumes - in one file.

## The catch that confuses newcomers

Name-based networking only works for containers on the **same custom network**. Two
containers started without `--network` land on Docker's default bridge and can't reach
each other by name - which is exactly why a web app "can't find the database" even
though both are running. The fix is simply to put both on the same network you created.
Also note you connect to the container's **internal** port (like `3306`), not any port
you published with `-p` - published ports are for reaching in from your computer, not
for container-to-container traffic.

## FAQ

### How do containers communicate with each other in Docker?

Put them on the same user-defined network (`docker network create`, then `--network`).
Docker's built-in DNS then lets each container reach the others by container name, so no
IP addresses are needed.

### Why can't my app connect to the database container?

Almost always because they're not on the same custom network, so name lookup fails. Put
both containers on the same network and connect using the database's container name as
the host.

### Do I need to publish a port for containers to talk to each other?

No. Publishing (`-p`) is only for reaching a container from your computer. Containers on
the same network reach each other on the internal port directly, no `-p` required.
