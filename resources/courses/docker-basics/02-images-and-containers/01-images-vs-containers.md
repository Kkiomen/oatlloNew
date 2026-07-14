---
title: "Images vs containers"
slug: images-vs-containers
seo_title: "Docker Image vs Container: What's the Difference?"
seo_description: "Docker image vs container explained with a simple analogy: an image is the read-only template, a container is a running instance of that image."
---

## Docker image vs container: the key difference

These two words get mixed up all the time, so let's make the difference crystal
clear.

- An **image** is a read-only template: a packaged snapshot of an app plus everything
  it needs (files, libraries, settings). It doesn't run - it just sits there, ready.
- A **container** is a **running instance** of an image. When you start an image, you
  get a container.

## Image vs container: a simple analogy

Think of an image like a **recipe**, and a container like a **meal** you cook from
it. From one recipe you can cook the same meal many times. From one image you can
start many containers, and they'll all be identical to begin with.

Another common analogy for programmers: an image is like a **class**, and a container
is like an **object** (an instance) created from that class. One class, many objects.

## Why this matters

When you ran `docker run hello-world` in
[the previous chapter](/course/docker-basics/getting-started/your-first-container), Docker took the
`hello-world` **image** and created a **container** from it. If you ran the command
again, you'd get a second, separate container from the same image.

This separation is powerful:

- Images are **shareable and reusable**. You build one and run it anywhere.
- Containers are **disposable**. You can start them, stop them, throw them away, and
  start fresh - the image is untouched.

Keep this in mind for the rest of the course:

> Image = the template (doesn't run). Container = a running copy of that template.

Next, let's practice
[running containers](/course/docker-basics/images-and-containers/running-containers) and
controlling them.

## Where this trips people up

The confusion usually comes from the word "run". When you edit files inside a running
container and then throw that container away, your changes go with it - the **image**
never changed. To actually change what every future container contains, you rebuild the
image (a later chapter). Beginners often expect edits inside a container to "stick";
they don't, and that's by design.

## FAQ

### Can one image have multiple containers?

Yes. An image is just a template, so you can start as many containers from it as you
like, and they all begin identical. This is exactly how you run several copies of the
same app.

### If I change a file inside a container, does the image change?

No. Changes live in that one container. The image stays untouched, and a new container
from that image starts clean. To bake changes into the image, you rebuild it.

### Which comes first, the image or the container?

The image. You build or download an image first, then create containers from it. No
image, no container.
