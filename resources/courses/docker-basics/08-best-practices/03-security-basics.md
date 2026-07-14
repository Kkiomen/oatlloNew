---
title: "Security basics"
slug: security-basics
seo_title: "Docker Security Basics: Best Practices"
seo_description: "Simple Docker security best practices: pin image versions, keep secrets out of images, and run containers as a non-root user with USER."
---

## A few habits that go a long way

You don't need to be a security expert to avoid the most common Docker mistakes. Here
are the essentials, all building on things you already learned.

## Pin your image versions

Back in the [images chapter](/course/docker-basics/images-and-containers/managing-images) we
mentioned tags. Relying on `latest` means your build can
change unexpectedly when a new version is published. Pin a specific version so builds
stay predictable:

```dockerfile
# avoid
FROM node

# prefer
FROM node:22-alpine
```

Predictable builds are also more secure builds - you know exactly what you're running.

## Never bake secrets into images

Passwords, API keys and tokens should **never** be written into a Dockerfile or copied
into an image. Anyone who has the image can read them. Instead:

- Pass secrets as **environment variables** at run time (the `-e` flag or Compose
  `environment`).
- Keep them in a `.env` file that you exclude with `.dockerignore` and `.gitignore`.

## Run as a non-root user

By default, processes inside a container run as `root`, the all-powerful user. If an
attacker breaks into your app, running as root gives them more power. You can switch to
a normal user with the `USER` instruction:

```dockerfile
FROM node:22-alpine
WORKDIR /app
COPY . .
RUN addgroup app && adduser -S -G app app
USER app
CMD ["node", "server.js"]
```

- `RUN addgroup ... adduser ...` creates a non-root user (the exact command depends on
  the base image; this is the Alpine form).
- `USER app` switches to it, so everything after runs as that limited user.

## Keep base images updated

Security fixes arrive in new versions of base images. Rebuilding periodically with an
updated base pulls in those fixes. Combined with pinned versions, a good rhythm is:
pin a version, and deliberately bump it now and then.

## The leak that outlives the delete

We just said not to bake secrets in - here's the part people miss about why a quick fix
won't save you. **Deleting the file in a later step doesn't remove it.** The secret still
sits in the earlier layer, readable by anyone who pulls the image. Layers remember
everything. So the rule isn't "delete it afterwards", it's "never let it into a layer in
the first place".

## FAQ

### What are the most important Docker security practices for beginners?

Pin image versions instead of `latest`, keep secrets out of images (pass them at run
time), and run containers as a non-root user with `USER`. Those three cover the most
common mistakes.

### Why shouldn't I put secrets in a Docker image?

Because anyone with the image can read them, and they persist in the image layers even
if a later step deletes the file. Provide secrets via environment variables at run time,
and exclude `.env` with `.dockerignore`.

### How do I run a container as a non-root user?

Create a user in your Dockerfile and switch to it with the `USER` instruction, so the
app no longer runs as all-powerful `root`. This limits the damage if the app is ever
compromised.

## You made it

That's the course. You can now explain what Docker is, run and manage containers,
build your own images with a Dockerfile, persist data with volumes, connect
containers over networks, orchestrate multi-container apps with Compose, dockerize a
real PHP app, and follow the practices that keep images small and secure.

From here, great next steps are learning about container orchestration in production
(such as Kubernetes) and setting up automated image builds in your CI pipeline - but
you now have the solid foundation they all build on.
