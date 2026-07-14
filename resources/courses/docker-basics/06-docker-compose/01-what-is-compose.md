---
title: "What is Docker Compose?"
slug: what-is-compose
seo_title: "What is Docker Compose? A Beginner's Guide"
seo_description: "What is Docker Compose? Learn how it describes a multi-container app in a single docker-compose.yml file you start with one command."
---

## The problem Compose solves

In [the last chapter](/course/docker-basics/networking/container-networks), running an app
with a database meant: create a network, run the
database with the right flags, run the app with the right flags and ports, and
remember all of it. For anything beyond one container, that's a lot of commands to
type and keep in sync.

**Docker Compose** lets you describe all of that in a single file called
`docker-compose.yml`, and then bring the whole thing up (or down) with one command.

## What Compose gives you

- **One file** describes every part of your app - each container, its image, ports,
  volumes and environment.
- **One command** starts everything: `docker compose up`.
- **Automatic networking** - Compose puts all your containers on a shared network, so
  they can reach each other by name automatically (no manual `docker network create`).
- **Reproducible** - the file lives in your project, so anyone can start the exact
  same setup.

## Docker Compose services explained

In Compose, each container is called a **service**. A typical web app might have two
services: `app` (your application) and `db` (its database). You describe each service
once, and Compose handles running them together.

## YAML in one minute

Compose files are written in **YAML**, a simple text format based on indentation.
Two things to know:

- Indentation (spaces, not tabs) shows structure - items indented under a line
  "belong to" it.
- `key: value` sets a value; a `-` starts a list item.

You'll see plenty of examples in the next lessons, so it'll become familiar quickly.

Let's [write our first `docker-compose.yml`](/course/docker-basics/docker-compose/writing-compose-file).

## The one thing that breaks Compose files: indentation

YAML uses **spaces** for structure, and it does not allow **tabs**. This bites almost
everyone at least once: a stray tab or a misaligned line produces a confusing error and
the file won't run. Use two spaces per level, stay consistent, and if Compose complains
about the file, suspect indentation before anything else. Set your editor to insert
spaces instead of tabs and most of this pain disappears.

## FAQ

### What is Docker Compose used for?

Running multi-container applications. You describe every service (container) in one
`docker-compose.yml` file, then start them all together with `docker compose up` -
including their networking and volumes - instead of typing many separate `docker run`
commands.

### Is Docker Compose separate from Docker?

Compose is part of the modern Docker install (the `docker compose` command). It builds
on the same engine - it's just a friendlier way to run several containers at once.

### What is a service in Docker Compose?

A service is one container definition in your Compose file - its image, ports, volumes,
and environment. A typical app has a couple of services, such as `app` and `db`.
