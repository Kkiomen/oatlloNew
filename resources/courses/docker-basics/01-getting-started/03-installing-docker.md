---
title: "Installing Docker"
slug: installing-docker
seo_title: "How to Install Docker on Windows, macOS and Linux"
seo_description: "How to install Docker: set up Docker Desktop on Windows and macOS or Docker Engine on Linux, then verify it with the docker version command."
---

## Installing Docker

The easiest way to get Docker on **Windows** or **macOS** is to install **Docker
Desktop**. It's a single application that includes everything you need: the Docker
engine (which runs your containers) and a command-line tool called `docker`.

1. Go to the official Docker website and download **Docker Desktop** for your
   operating system.
2. Run the installer and follow the steps.
3. Start Docker Desktop. Wait until it says it's running (you'll usually see a whale
   icon in your taskbar or menu bar).

On **Linux**, you can install **Docker Engine** directly from your distribution's
package manager. The official docs have per-distribution instructions; on many
systems it's a matter of adding Docker's package repository and installing the
`docker` package.

## Verify the installation with docker version

Open a terminal and check the version:

```bash
docker version
```

If Docker is installed and running, you'll see version information for both the
client and the engine. If you get an error like "cannot connect to the Docker
daemon", it usually means Docker Desktop (or the Docker service on Linux) isn't
running yet - start it and try again.

You can also see a summary of your setup:

```bash
docker info
```

Once `docker version` works, you're ready. In the next lesson we'll
[run our first container](/course/docker-basics/getting-started/your-first-container).

## The most common install snag

The number one beginner problem isn't the install itself - it's forgetting that
**Docker Desktop has to be running** before any `docker` command works. If you see
"cannot connect to the Docker daemon", Docker just isn't started. Open Docker Desktop,
wait for the whale icon to go steady, and the same command will work. On Windows,
enabling **WSL 2** when the installer asks gives you the smoothest experience.

## FAQ

### Do I need Docker Desktop, or just Docker?

On Windows and macOS, Docker Desktop is the standard way to get everything (engine plus
the `docker` command) in one install. On Linux you typically install Docker Engine from
your package manager instead - Desktop is optional there.

### Is Docker Desktop free?

Yes for personal use, education, and small businesses. Larger companies need a paid
subscription, but nothing in this course requires it.

### How do I check that Docker installed correctly?

Run `docker version`. If it shows both a client and an engine version, you're set. You
can also run `docker run hello-world` (the next lesson) as a full end-to-end test.
