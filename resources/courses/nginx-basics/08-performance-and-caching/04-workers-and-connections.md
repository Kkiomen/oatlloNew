---
title: "Workers and connections"
slug: workers-and-connections
seo_title: "Nginx worker_processes and worker_connections Explained"
seo_description: "Understand nginx worker_processes and worker_connections: what they control, how they set the connection ceiling, and the defaults you rarely change."
---

Nginx handles traffic using worker processes. Two settings, `worker_processes` and `worker_connections`, control how many workers run and how many connections each one can hold. The defaults are already sensible for almost everyone, so read this to understand your config, not because you must tune it.

## Where worker_processes and worker_connections live

These live at the very top of `nginx.conf`, outside the `http` block:

```nginx
worker_processes auto;

events {
    worker_connections 1024;
}
```

That is the standard setup, and for most sites you should leave it exactly like this.

## What worker_processes does

A worker process is one nginx instance handling requests. `worker_processes` sets how many run.

`auto` tells nginx to start one worker per CPU core. A 4-core server gets 4 workers. This is the right choice, because nginx spreads work across cores and one worker per core uses the machine well without them fighting over the CPU.

You could hard-code a number like `worker_processes 2;`, but `auto` adapts if you move to a bigger or smaller server. Prefer `auto`.

## What worker_connections does

`worker_connections` sets how many simultaneous connections *one* worker can handle. The default 1024 is plenty for most sites, and you can raise it if you genuinely serve huge traffic.

The rough ceiling of total simultaneous connections is:

```text
worker_processes x worker_connections
```

So 4 workers at 1024 each is about 4096 connections at once. That is a lot. A connection is only held while a request is in flight, so a single visitor loading a page does not tie up a slot for long.

One catch that surprises people who do raise the number: `worker_connections` cannot exceed the operating system's per-process file descriptor limit. Every connection uses a file descriptor, and if `ulimit -n` is 1024 (a common default), setting `worker_connections 8192;` buys you nothing past 1024. The real ceiling is the lower of the two. If you ever push this setting up, raise the OS limit with `worker_rlimit_nofile` to match.

## A note on proxying

When nginx proxies to a backend (see [reverse proxy](/course/nginx-basics/reverse-proxy/proxy-pass)), each client request can use *two* connections: one from the browser to nginx, and one from nginx to the backend. Keep that in mind if you ever calculate limits, but you are unlikely to hit them on a normal site.

## Common mistake: cranking the numbers up "for speed"

Setting `worker_processes 16;` on a 2-core server does not make it faster. You get more processes than cores, so they compete for CPU time and can end up slower. More workers than cores rarely helps. Trust `auto`.

Likewise, raising `worker_connections` to a huge number does not add capacity your hardware cannot back up. If a server is slow under load, the bottleneck is usually the backend, the database, or memory, not these two numbers.

## FAQ

### Do I need to change these at all?

For a typical website, no. `worker_processes auto;` and `worker_connections 1024;` are fine. Only revisit them if monitoring shows you are actually running out of connections.

### How do I know if I am hitting the connection limit?

Your error log (see [access and error logs](/course/nginx-basics/configuration-basics/access-and-error-logs)) will show `worker_connections are not enough` warnings. Until you see that message, you are not near the limit.

### Where do these directives go?

`worker_processes` sits at the top level of `nginx.conf`. `worker_connections` goes inside the `events` block. Both are outside `http`, so they are set once for the whole server, not per site.
