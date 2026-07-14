---
title: "Working inside a container"
slug: interactive-containers
seo_title: "Open a Shell in a Docker Container (docker exec -it)"
seo_description: "Open an interactive shell inside a Docker container: use docker run -it for a new container and docker exec -it bash to enter a running one."
---

## Opening a shell in a new container

Sometimes you want to poke around **inside** a container - look at its files, run
commands, see what's installed. You can start a container and drop straight into its
shell with two flags:

```bash
docker run -it ubuntu bash
```

- `-i` keeps the input open so you can type.
- `-t` gives you a proper terminal.
- `ubuntu` is the image, and `bash` is the shell we want to run.

Together, `-it ... bash` means "start an Ubuntu container and give me a shell inside
it". Your prompt changes - you're now typing **inside** the container. Try a few
commands:

```bash
ls
cat /etc/os-release
whoami
```

When you're done, type `exit` to leave. Because the shell was the container's main
process, leaving it stops the container.

## Enter a running container with docker exec

What if a container is already running (like our Nginx server) and you want to look
inside it **without** stopping it? Use `docker exec`:

```bash
docker exec -it my-web bash
```

This runs a new `bash` shell inside the existing `my-web` container. You can inspect
files, check configuration, and so on. Type `exit` to leave - this time the container
keeps running, because you only exited the extra shell you started, not the main
process.

## Why this is useful

Being able to open a shell inside a container is one of the most useful debugging
skills in Docker. When something isn't working, you can go in and check:

- Are the files where you expect them?
- Is the config correct?
- Can the app reach the things it needs?

You now know how to run, manage and inspect containers and images. In the next chapter
we'll [build our **own** image](/course/docker-basics/building-images/what-is-a-dockerfile)
from scratch.

## A gotcha: not every image has bash

You'll eventually run `docker exec -it somecontainer bash` and get "executable file not
found". That's because small images like `alpine` don't ship `bash` - they include the
lighter `sh` shell instead. When `bash` fails, try `sh`:

```bash
docker exec -it my-container sh
```

It's a tiny thing, but it confuses almost everyone the first time.

## FAQ

### What is the difference between docker run -it and docker exec -it?

`docker run -it` starts a **new** container and drops you into its shell. `docker exec
-it` runs a shell inside a container that is **already running**, without disturbing it.
Use `run` to start fresh, `exec` to look inside something live.

### Why do I get "executable file not found" when I run bash?

The image probably doesn't include `bash`. Minimal images such as `alpine` ship only
`sh`. Run `docker exec -it my-container sh` instead.

### Does exiting the shell stop the container?

It depends. If the shell was the container's main process (a `docker run -it ... bash`),
exiting stops the container. If you attached with `docker exec`, exiting only ends that
extra shell and the container keeps running.
