---
title: "Multi-stage builds"
slug: multi-stage-builds
seo_title: "Docker Multi-Stage Builds Explained"
seo_description: "Use Docker multi-stage builds to compile or install in one stage and ship only the result in a small final image with a second FROM."
---

## What a multi-stage build is

Building an app often needs tools you don't want in the final image - compilers, build
systems, dev dependencies. A **multi-stage build** lets you use those tools in one
stage, then copy only the finished result into a clean, small final stage. The heavy
tools are left behind.

You do this by having **more than one `FROM`** in a single Dockerfile. Each `FROM`
starts a new stage.

## Multi-stage build example

Here's a build for a compiled app. The first stage builds it; the second stage keeps
only the built program:

```dockerfile
# Stage 1: build
FROM node:22 AS build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: final image
FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
```

What's happening:

- The first stage is named `build` (with `AS build`). It installs all dependencies and
  runs `npm run build`, producing files in `/app/dist`.
- The second stage starts fresh from a tiny `nginx:alpine` image.
- `COPY --from=build /app/dist ...` copies **only** the built files out of the first
  stage into the final image.

The final image contains just Nginx and your built files - none of Node, npm, or the
`node_modules` folder. You saw this `--from` copy trick earlier when we
[grabbed Composer from another image](/course/docker-basics/real-project/a-php-dockerfile);
multi-stage builds are the same idea taken further.

## Why it's worth it

The result can be dramatically smaller - sometimes from hundreds of megabytes down to
tens. You get the full power of your build tools during the build, and a lean image to
ship. Only the last stage becomes the final image; earlier stages are discarded.

Multi-stage builds are one of the most valuable Docker techniques for real projects.
Last, let's cover a few [security basics](/course/docker-basics/best-practices/security-basics).

## When a multi-stage build is (and isn't) worth it

Multi-stage builds shine when there's a clear split between **building** and **running** -
compiled languages, or front-end assets built with Node then served by a tiny web
server. If your app has no build step and just runs interpreted code with its
dependencies, a single well-ordered stage may be simpler and just as small. Reach for
multi-stage when your final image is dragging along tools it only needed at build time;
don't add the complexity when there's nothing heavy to leave behind.

## FAQ

### What problem do multi-stage builds solve?

They keep build-only tools (compilers, dev dependencies, build systems) out of the final
image. You build in one stage and copy only the finished result into a small final
stage, so the shipped image stays lean.

### How does COPY --from work?

`COPY --from=<stage>` copies files from an earlier build stage into the current one. You
name a stage with `AS <name>` on its `FROM` line, then pull just the built artifacts out
of it - leaving that stage's heavy tooling behind.

### Do earlier stages end up in the final image?

No. Only the last stage becomes the image. Earlier stages are used during the build and
then discarded, which is exactly why the final image is smaller.
