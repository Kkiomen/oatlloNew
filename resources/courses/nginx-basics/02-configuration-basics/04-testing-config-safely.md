---
title: "Testing nginx config safely with nginx -t"
slug: testing-config-safely
seo_title: "nginx -t: Test Nginx Config Before Reload Safely"
seo_description: "Run nginx -t to test your nginx config before reload. Learn reload vs restart, what a syntax error looks like, and how to avoid downtime."
---

You have edited a config file. Before you tell nginx to use it, you test it. Running `nginx -t` to check your nginx config before a reload is the single most important habit in this course. A broken config that reaches a running server can take your site down, and this one command exists precisely to stop that.

## Run nginx -t before every reload

The command is short:

```bash
sudo nginx -t
```

It reads your full config, checks the syntax, and reports back. It does **not** apply anything. It is a dry run. A good config prints:

```bash
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

Make this reflex automatic: edit, then `nginx -t`, then reload. Never reload blind. The two seconds the test takes are the cheapest insurance you will ever buy.

## Reload vs restart: which one to use

In [Start, stop, reload](/course/nginx-basics/getting-started/start-stop-reload) you met these commands. Here is why the choice matters once real traffic is involved.

A **reload** tells the running nginx to re-read its config gracefully:

```bash
sudo systemctl reload nginx
```

Nginx starts new worker processes with the new config and lets the old ones finish the requests they are already handling. Visitors notice nothing. No connection is dropped.

A **restart** stops nginx completely and starts it again:

```bash
sudo systemctl restart nginx
```

For a moment there is no server running at all, so requests in that gap fail. Restart only when you must, for example after changing something a reload cannot pick up. For everyday config edits, **reload**.

The safe sequence is always:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

The `&&` means the reload only runs **if** the test passed. If the config is broken, nothing reloads and your live site keeps serving the old, working config.

## What a syntax error looks like

Say you forgot a semicolon, the mistake from [Directives and contexts](/course/nginx-basics/configuration-basics/directives-and-contexts). `nginx -t` will not stay quiet:

```bash
nginx: [emerg] directive "worker_connections" is not terminated by ";" in /etc/nginx/nginx.conf:8
nginx: configuration file /etc/nginx/nginx.conf test failed
```

Read it left to right:

- `[emerg]` - severity, an emergency that blocks starting.
- the message - what is wrong, here a missing `;`.
- `/etc/nginx/nginx.conf:8` - the exact file and line number.

That last part is the gift. Open the file, jump to line 8, fix it, and test again. Because you tested instead of reloading, the live server never saw the broken file.

## Common mistake

Reloading without testing, then getting an error, and assuming the site is down. Often it is not: a failed reload leaves the old config running. But do not rely on that. If you `restart` on a broken config, nginx refuses to start and the site really does go down. Test first and this never happens.

## FAQ

### Why does nginx -t need sudo?

It reads the config and may touch files and sockets that only root can access, such as log paths. Without `sudo` you may get permission errors that look like config errors but are not.

### Does nginx -t catch every problem?

No. It catches syntax and structural errors, like a bad directive or a missing brace. It cannot know that a path you typed does not exist on disk, or that your logic is wrong. It is a spell-check, not a proofread.

### What is the difference between reload and restart again?

Reload swaps the config with zero downtime and keeps existing connections alive. Restart fully stops and starts nginx, causing a brief outage. Prefer reload; keep restart for the rare cases reload cannot handle.
