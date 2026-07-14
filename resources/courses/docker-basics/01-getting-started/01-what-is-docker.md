---
title: "What is Docker?"
slug: what-is-docker
seo_title: "What is Docker? Containers Explained for Beginners"
seo_description: "Learn what Docker is and what a container is - and why developers use Docker to package and run applications the same way on every machine."
---

**Docker** packages an application together with everything it needs to run into a
single unit called a **container**. Why that matters becomes obvious the first time an
app works on your laptop and nowhere else.

## The problem Docker solves

You've probably heard the phrase "but it works on my machine". An app runs fine on
your laptop, then breaks on a colleague's computer or on the server. Usually the
reason is the same: the two machines have different versions of a language, a
library, or some system tool.

**Docker** solves this by packaging your application together with everything it
needs to run - the code, the runtime, the libraries, the settings - into a single
unit called a **container**. That container runs the same way everywhere: your
laptop, a coworker's machine, or a production server.

## What is a container?

A **container** is an isolated process running on your computer. Think of it as a
lightweight, self-contained box that holds one application and everything that
application needs. Inside the box, the app believes it has its own operating system,
its own files, and its own network - but it's actually sharing your computer's
resources with other containers.

Because each container is isolated, you can run many of them side by side without
them interfering with each other. One container can use one version of a tool while
another uses a completely different version, and neither one knows about the other.

## What is Docker, then?

**Docker** is the tool that builds, runs and manages these containers. You describe
what your app needs, Docker packages it up, and then you can run that package
anywhere Docker is installed.

In the next lessons we'll break these ideas down further and actually run something.
For now, remember the one-sentence version:

> Docker packages an application with everything it needs so it runs the same way
> everywhere.

We'll define the exact difference between a **container** and an **image** in the
[images and containers lesson](/course/docker-basics/images-and-containers/images-vs-containers)
- for now, "container = a running, isolated box with your app inside" is all you need.

## A common misconception to clear up

A lot of people meet Docker expecting it to make their app *faster*. It usually
doesn't. Docker's real job is **consistency**, not speed: the same environment on
every machine, so "works on my machine" stops being a problem. It's also not a virtual
machine (that's [the next lesson](/course/docker-basics/getting-started/containers-vs-virtual-machines)).
Keep that framing and Docker will make a lot more sense as you go.

## FAQ

### Is Docker free to use?

Yes. Docker Engine is open source and free, and Docker Desktop is free for personal
use, education, and small businesses. You can follow this entire course without paying.

### Do I need to know Linux to use Docker?

Not to get started. A little comfort with the terminal helps, and most images are
Linux-based, but you can run Docker on Windows or macOS and pick up the Linux parts as
you need them.

### Is Docker a programming language?

No. Docker is a tool for packaging and running applications. You use it alongside
whatever language your app is written in - PHP, JavaScript, Python, and so on.
