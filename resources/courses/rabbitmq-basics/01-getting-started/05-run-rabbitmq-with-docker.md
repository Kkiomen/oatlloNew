---
title: "Run RabbitMQ with Docker"
slug: run-rabbitmq-with-docker
seo_title: "Run RabbitMQ with Docker - Quick Start Command"
seo_description: "Run RabbitMQ locally with one Docker command using the rabbitmq:3-management image, then open the management UI at localhost:15672 and log in."
---

Enough theory. Let's run RabbitMQ with Docker - one command gives you a full broker
plus a web dashboard, with nothing to install by hand and nothing to uninstall later.
This lesson assumes you already have Docker installed and working.

## The one command

Run this in your terminal:

```bash
docker run -d --name rabbitmq -p 5672:5672 -p 15672:15672 rabbitmq:3-management
```

That's it. RabbitMQ is now running in the background. Let's unpack what each part does.

## What the command means

- `docker run` - start a new container.
- `-d` - run it **detached** (in the background), so your terminal is free.
- `--name rabbitmq` - give the container a friendly name so you can refer to it later.
- `-p 5672:5672` - map port **5672**, the port apps use to talk to RabbitMQ over AMQP.
- `-p 15672:15672` - map port **15672**, the **management UI** (the web dashboard).
- `rabbitmq:3-management` - the image to run. The `-management` tag includes the web
  dashboard; the plain `rabbitmq` image does not.

The two ports do two different jobs. **5672** is where your producers and consumers
connect. **15672** is where *you* connect with a browser to see what's going on.

## Verify it's running

First, check the container is up:

```bash
docker ps
```

You should see a row for `rabbitmq` with a status like `Up 30 seconds`. If it's there,
the broker is alive.

One thing that catches people: `docker ps` showing the container as up does not mean
the broker is ready to serve. RabbitMQ takes a few seconds to boot its internals, so if
you open the dashboard the instant the container starts you may get a blank page or a
refused connection. Give it about ten seconds and refresh.

Now open the dashboard in your browser:

```text
http://localhost:15672
```

Log in with the default credentials:

```text
username: guest
password: guest
```

If you see the RabbitMQ management dashboard, everything works. We'll tour that
dashboard in the [next lesson](/course/rabbitmq-basics/getting-started/the-management-ui-tour).

## Stopping and starting again

You don't need to recreate the container each time. To stop it:

```bash
docker stop rabbitmq
```

To start the same one back up later:

```bash
docker start rabbitmq
```

Only use `docker run` again if you've removed the container - otherwise you'll get a
name conflict.

## Common mistake: the wrong image tag

If you run `rabbitmq` instead of `rabbitmq:3-management`, the broker starts fine but
**port 15672 shows nothing** - there's no web dashboard in the plain image. If
`localhost:15672` won't load, check that you used the `:3-management` tag. Remove the
old container and re-run:

```bash
docker rm -f rabbitmq
docker run -d --name rabbitmq -p 5672:5672 -p 15672:15672 rabbitmq:3-management
```

The other common issue is a port already in use. If Docker complains that port 5672 or
15672 is taken, something else is using it - stop that program, or map a different host
port (for example `-p 5673:5672`).

## FAQ

### Why are there two ports?

5672 is the AMQP port your application code connects to. 15672 is the web management UI
you open in a browser. They serve completely different clients - programs versus you.

### Are guest/guest safe to use?

For local development, yes. But the `guest` account only works from localhost by
default, and you should never use it in production. We'll create proper users in a
later chapter.

### Where does my data go if I remove the container?

By default, removing the container removes its data. That's fine while learning. For
real use you'd attach a Docker volume so queues survive a restart - a topic for the
production chapter.
