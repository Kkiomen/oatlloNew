---
title: "listen and server_name"
slug: listen-and-server-name
seo_title: "Nginx server_name and listen: Host Many Domains"
seo_description: "How nginx server_name matches the domain and listen sets the port, so one nginx hosts several sites, plus the default_server catch-all."
---

One nginx can answer for many domains at once. Two directives decide which requests a [server block](/course/nginx-basics/serving-static-content/server-blocks) picks up: `listen` sets the port, and `server_name` matches the domain. This lesson explains both, plus the `default_server` that catches everything else.

## listen: the port

`listen` tells the server block which port to answer on.

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;
}
```

Port 80 is plain HTTP, the default for `http://` addresses. Port 443 is HTTPS, but that needs a certificate, which we cover later in the [HTTPS chapter](/course/nginx-basics/https-tls/listen-443-ssl). For now, port 80 is all you need.

You can also bind to a specific address:

```nginx
listen 127.0.0.1:80;
```

That means "only answer requests arriving on the local loopback address." Without an address, nginx listens on every network interface, which is what you usually want for a public site.

## server_name: the domain

Many server blocks can share port 80. So how does nginx know which one should answer? It [reads the `Host` header the browser sends](/course/nginx-basics/getting-started/how-a-web-request-works) (the domain in the address bar) and matches it against `server_name`.

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

A request for `example.com` lands in the first block. A request for `blog.example.com` lands in the second. Same port, different site.

`server_name` accepts more than one name:

```nginx
server_name example.com www.example.com;
```

Now both the bare domain and the `www` version hit this block.

## Hosting several domains

Put the two ideas together and you can host as many sites as you like from one nginx:

```nginx
server {
    listen 80;
    server_name shop.example.com;
    root /var/www/shop;
}

server {
    listen 80;
    server_name docs.example.com;
    root /var/www/docs;
}
```

Each domain points at its own folder. This is the everyday way to run many sites on one machine.

## What handles a request that matches no server_name?

Sooner or later a request arrives with a `Host` that matches no `server_name`. Maybe someone typed your server's raw IP address, or pointed a stray domain at it.

Nginx still has to answer with something, so it falls back to a **default server**. If you do not mark one, nginx quietly uses the first server block listening on that port. You can pick it on purpose instead:

```nginx
server {
    listen 80 default_server;
    server_name _;
    root /var/www/default;
}
```

The `default_server` flag is what actually says "send unmatched requests here." The `_` does not do that job; it is just a name that matches no real domain, a common habit for a catch-all block. Many people point the default at a plain page or a 404 so stray traffic never leaks into a real site.

That split trips people up: they write `server_name _;` expecting it to catch everything, leave off the flag, and wonder why a different block answers. The name is decoration. The flag decides.

## Common mistake

Setting `default_server` on more than one block for the same port. Nginx allows only one default per port and fails `nginx -t` if you add a second. Pick one block to be the default and drop the flag from the rest.

## FAQ

### Can two server blocks use the same port?

Yes. That is the whole point. They share the port and nginx tells them apart by `server_name`.

### What if I leave server_name out?

The block still loads, but it can only ever be reached as the default server, because it has no name to match.

### Do I need the www and non-www versions both?

Only if you want both to work. List every name you want this block to answer to in `server_name`.
