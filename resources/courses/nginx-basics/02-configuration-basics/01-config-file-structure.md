---
title: "The nginx.conf config file structure"
slug: config-file-structure
seo_title: "nginx.conf File Location and Structure on Linux"
seo_description: "Find the nginx.conf file location on Linux, learn its default path and structure, and read the file at a glance before you edit it."
---

In [Getting started](/course/nginx-basics/getting-started/start-stop-reload) you installed nginx and reloaded it. All of that behaviour comes from one or more text files. The main one is `nginx.conf`, and before you change anything you need two things: the nginx.conf file location on your machine, and a rough sense of how the file is laid out.

## Where is the nginx.conf file located on Linux?

On most Linux systems the main file is here:

```bash
/etc/nginx/nginx.conf
```

That is the file nginx reads first when it starts. If you installed with the system package manager (apt, dnf), this path is the default. To confirm on your machine, ask nginx directly:

```bash
nginx -t
```

The output prints the path of the config file it is testing, for example `configuration file /etc/nginx/nginx.conf test is successful`. That path is not a guess or a documentation default. It is the file this exact nginx will actually read.

## What does the nginx.conf file structure look like?

Open it and you will see plain text with lines, curly braces `{ }`, and semicolons `;`. Here is a trimmed version of a typical `nginx.conf`:

```nginx
user www-data;
worker_processes auto;

events {
    worker_connections 1024;
}

http {
    include       /etc/nginx/mime.types;
    default_type  application/octet-stream;

    access_log /var/log/nginx/access.log;
    error_log  /var/log/nginx/error.log;

    include /etc/nginx/conf.d/*.conf;
}
```

Do not worry about every line yet. Notice the shape instead:

- Some lines stand alone and end with a semicolon, like `worker_processes auto;`.
- Some lines open a block with `{`, contain more lines, and close with `}`, like `events { ... }`.
- Blocks can sit inside other blocks. `access_log` lives inside `http`.

That is the whole grammar. Two shapes, nested. The next lesson, [Directives and contexts](/course/nginx-basics/configuration-basics/directives-and-contexts), gives those shapes names and explains the nesting.

## Why nginx.conf mostly points to other files

Look again at the two `include` lines. Real setups rarely cram everything into one file. The main `nginx.conf` sets a handful of global options, then pulls in extra files from other folders. We cover that in [Includes and sites](/course/nginx-basics/configuration-basics/includes-and-sites). For now, treat `nginx.conf` as the starting point, not always the whole story. A short main file is normal, not a sign that something is missing.

## Common mistake

Editing a file inside `conf.d/` or `sites-available/` and then wondering why `nginx.conf` "did not change". It should not change. Those included files are the config too. `nginx.conf` is simply the front door that loads them.

## FAQ

### What if nginx.conf is not in /etc/nginx?

It depends how nginx was installed. Custom builds or Docker images may put it under `/usr/local/nginx/conf/` or elsewhere. Running `nginx -t` always prints the real path, so use that instead of guessing.

### Can I keep everything in one big nginx.conf file?

Yes, nginx does not require the extra folders. But splitting config with `include` keeps each site separate and easier to read. You will see why in the includes lesson.

### Do I need to restart my machine after editing it?

No. You edit the text file, test it, and reload nginx. Editing the file alone changes nothing until nginx re-reads it, which is exactly what the testing lesson covers.
