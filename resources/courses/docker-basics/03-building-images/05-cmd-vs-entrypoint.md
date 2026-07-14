---
title: "CMD vs ENTRYPOINT"
slug: cmd-vs-entrypoint
seo_title: "CMD vs ENTRYPOINT in a Dockerfile Explained"
seo_description: "CMD vs ENTRYPOINT: understand the difference, how each handles arguments, and when to use CMD, ENTRYPOINT, or both together in a Dockerfile."
---

## Both define what runs - but differently

Both `CMD` and `ENTRYPOINT` tell Docker what to run when a container starts. The
difference is how easily that command can be **overridden** when you run the
container.

## CMD: an easily-replaced default

`CMD` sets a **default** command. If you provide a command when running the
container, it **replaces** the `CMD`. Take this image:

```dockerfile
FROM alpine
CMD ["echo", "default message"]
```

Running it normally uses the default:

```bash
docker run my-image
# prints: default message
```

But if you pass your own command, it overrides `CMD` completely:

```bash
docker run my-image echo "something else"
# prints: something else
```

So `CMD` is best when the container has a sensible default but you're happy to let
people replace it.

## ENTRYPOINT: a fixed command

`ENTRYPOINT` sets a command that is **not** replaced by arguments - instead, anything
you pass gets **appended** to it. This is useful when your image is really a wrapper
around one specific program.

```dockerfile
FROM alpine
ENTRYPOINT ["echo"]
```

Now whatever you pass becomes arguments to `echo`:

```bash
docker run my-image hello there
# prints: hello there
```

The container always runs `echo`; you only control what it echoes.

## Using them together

A common pattern is to combine them: `ENTRYPOINT` fixes the program, and `CMD`
provides default arguments that are easy to override.

```dockerfile
FROM alpine
ENTRYPOINT ["echo"]
CMD ["default message"]
```

- `docker run my-image` prints `default message`.
- `docker run my-image hello` prints `hello`.

## Which should you use?

- Use **`CMD`** alone for a simple default command (most beginner images).
- Use **`ENTRYPOINT`** when the image exists to run one specific tool, and you want
  arguments passed to it.

Next, let's understand why rebuilds are sometimes fast and sometimes slow - the world of
[layers and caching](/course/docker-basics/building-images/layers-and-caching).

## The formatting gotcha almost everyone hits

Write these in the **exec form** with a JSON-style array and double quotes:
`CMD ["php", "artisan", "serve"]`. If you write them as a plain string instead, Docker
runs them through a shell, which changes how signals and arguments behave and can lead
to a container that won't stop cleanly. And note the quotes must be double quotes -
single quotes are not valid JSON and Docker will complain. When in doubt, use the array
form.

## FAQ

### What is the difference between CMD and ENTRYPOINT?

`CMD` sets a default command that arguments **replace** when you run the container.
`ENTRYPOINT` sets a fixed command that arguments are **appended** to. Use `CMD` for an
overridable default, `ENTRYPOINT` when the image always runs one specific program.

### Can I use CMD and ENTRYPOINT together?

Yes, and it's a common pattern: `ENTRYPOINT` fixes the program and `CMD` supplies
default arguments that are easy to override. Running the container with no arguments
uses the `CMD` defaults.

### Which should a beginner use?

Start with `CMD` alone - it's the simplest way to set a default command. Reach for
`ENTRYPOINT` only when your image exists to wrap one specific tool.
