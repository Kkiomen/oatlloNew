---
title: "The .dockerignore file"
slug: dockerignore
seo_title: "How to Use a .dockerignore File in Docker"
seo_description: "Learn how a .dockerignore file keeps unwanted files out of your build context for smaller, faster and safer Docker images."
---

## Keeping junk out of your image

Remember the `.` at the end of `docker build -t my-image .`? That folder is the
**build context** - everything in it is sent to Docker so it can be copied into the
image. If your project folder contains large or sensitive files, they get sent too,
even if you don't want them.

A **`.dockerignore`** file tells Docker which files and folders to leave out. It works
just like a `.gitignore` file: list patterns, one per line.

## A .dockerignore example

Create a file named `.dockerignore` next to your `Dockerfile`:

```text
node_modules
vendor
.git
.env
*.log
```

Now when you build, Docker skips those paths. This matters for three reasons:

- **Smaller and faster builds** - you don't send huge folders like `node_modules` or
  `vendor` that will be rebuilt inside the image anyway.
- **Cleaner images** - build artifacts and local junk don't sneak in.
- **Safer images** - secret files like `.env` don't accidentally get baked in (more on
  this below).

## A good default .dockerignore

For most projects, ignoring dependency folders, version-control folders, environment
files and logs is a sensible start:

```text
.git
.gitignore
node_modules
vendor
.env
*.log
Dockerfile
docker-compose.yml
```

Adjust it to your stack. Even a small `.dockerignore` makes your builds noticeably
faster and your images cleaner.

You can now build solid images. But there's a catch we've been ignoring: when a
container is removed, its data disappears. The next chapter
[fixes that with volumes](/course/docker-basics/data-and-volumes/why-data-disappears).

## The security reason this matters most

Beyond speed, the `.dockerignore` file has a security payoff people overlook: it keeps
your `.env` file and other secrets out of the image. Without it, a `COPY . .` can bake
API keys and passwords straight into the image - and anyone who pulls that image can
read them, even if you later delete the file. Add `.env` to `.dockerignore` from day
one; it's the cheapest security win in Docker.

## FAQ

### What should I put in a .dockerignore file?

Anything that shouldn't be sent to the build: dependency folders (`node_modules`,
`vendor`), version control (`.git`), secrets (`.env`), logs, and local build artifacts.
Keep the image small and clean.

### Is .dockerignore the same as .gitignore?

The syntax is the same and they often overlap, but they serve different tools.
`.gitignore` controls what Git tracks; `.dockerignore` controls what gets sent into the
Docker build. You usually want both.

### What happens if I don't use a .dockerignore?

Docker sends your entire project folder as the build context, so large folders and
secret files can be copied into the image by accident - slower builds, bigger images,
and a real risk of leaking credentials.
