---
title: "Server blocks"
slug: server-blocks
seo_title: "Nginx Server Block Explained (aka Virtual Host)"
seo_description: "What an nginx server block is, why Apache calls it a virtual host, and how to write a minimal server { } block that serves one site."
---

One nginx can serve many different websites. The piece that describes a single website is called a **server block**, and it is the first thing you write for any site. This lesson shows what an nginx server block is and how to write the smallest useful one.

## What is an nginx server block?

A server block is a `server { }` section in your config. It tells nginx: "when a request comes in that matches this, here is how to answer it."

In the Apache world the same idea is called a **virtual host**. Nginx calls it a server block, but it means the same thing: one config for one site, and you can have many of them side by side.

You met contexts and directives in [Directives and contexts](/course/nginx-basics/configuration-basics/directives-and-contexts). A server block is a context. It lives inside the `http` block and holds directives that describe one site.

## A minimal server block

Here is about the smallest server block that does something real:

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;
}
```

Three lines, three jobs:

- `listen 80;` - answer requests on port 80 (plain HTTP).
- `server_name example.com;` - this block is for the domain `example.com`.
- `root /var/www/example;` - the files for this site live in this folder.

Do not worry about the exact meaning of each one yet. The next three lessons cover `listen` and `server_name`, then `root` and `index`, one at a time.

## Where the block lives

A server block does not float on its own. It sits inside the `http` context:

```nginx
http {
    server {
        listen 80;
        server_name example.com;
        root /var/www/example;
    }
}
```

In a real install you rarely edit the big `http` block by hand. As you saw in [Includes and sites](/course/nginx-basics/configuration-basics/includes-and-sites), each site usually gets its own file in `conf.d/` or `sites-available/`, and nginx pulls them all in with `include`. Each of those files holds one server block.

## Run many sites from one nginx

You can add a second server block for a second site:

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;
}

server {
    listen 80;
    server_name blog.example.com;
    root /var/www/blog;
}
```

Both listen on port 80. Nginx picks the right one using the `server_name`, which is the topic of the next lesson.

One thing to watch: if two blocks accidentally carry the same `server_name`, nginx still starts, but it prints a `conflicting server name` warning and quietly uses the first block it read. The site loads, so the bug hides until you notice the wrong page is answering.

## Common mistake

Forgetting that a server block must sit inside `http`. If you paste a bare `server { }` at the very top of `nginx.conf`, nginx refuses to load. Run `nginx -t` after every edit, as covered in [Testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely). It catches this in a second and prints the exact line.

## FAQ

### Is "server block" the same as "virtual host"?

Yes. Apache says virtual host, nginx says server block. Same concept: one config for one site.

### How many server blocks can I have?

As many as you need. One nginx routinely serves dozens of sites, each in its own server block.

### Do I need one file per server block?

No, but it is the common habit. One file per site in `sites-available/` keeps things tidy and easy to enable or disable.
