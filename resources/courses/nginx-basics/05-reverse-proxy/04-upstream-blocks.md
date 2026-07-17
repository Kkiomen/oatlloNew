---
title: "Naming backends with upstream"
slug: upstream-blocks
seo_title: "Nginx upstream Block: Name a Group of Backends"
seo_description: "Define an nginx upstream block to name one or more backend servers, then point proxy_pass at the name instead of a hard-coded address."
---

## Why hard-coding a backend address gets old

So far `proxy_pass` has pointed straight at an address, and the nginx upstream block is what you graduate to next:

```nginx
proxy_pass http://127.0.0.1:3000;
```

A single address works for one backend. But what if you run two copies of the app, or you want to reuse the same backend in several locations, or you just want a readable name instead of an IP scattered through the file? Hard-coding the address everywhere becomes a chore, and there is nowhere to hang extra options.

The `upstream` block solves this. It gives a group of backend servers a name.

## Defining an nginx upstream block

An `upstream` block lives in the `http` context, next to your `server` blocks (not inside one):

```nginx
upstream app_backend {
    server 127.0.0.1:3000;
}

server {
    listen 80;
    server_name app.example.com;

    location / {
        proxy_pass http://app_backend;
    }
}
```

Two things changed:

1. `upstream app_backend { ... }` defines a named group with one `server` inside.
2. `proxy_pass http://app_backend;` uses the **name** instead of an address.

`app_backend` is just a label you invent. nginx resolves it to the servers listed in the block.

## Listing more than one server

The point of a named group is that it can hold several servers:

```nginx
upstream app_backend {
    server 127.0.0.1:3000;
    server 127.0.0.1:3001;
    server 127.0.0.1:3002;
}
```

Now you run three copies of your app on ports 3000, 3001 and 3002, and nginx spreads requests across all three. You did not change `proxy_pass` at all - it still says `http://app_backend`. How nginx chooses between them is the next lesson: [load balancing](/course/nginx-basics/reverse-proxy/load-balancing).

## Why name a group of backends?

- **One place to edit.** Change ports or add a server in the `upstream` block, and every `proxy_pass http://app_backend` follows automatically.
- **Readable config.** `proxy_pass http://payments` says what it is. `proxy_pass http://10.0.0.42:9000` does not.
- **Room for options.** Load-balancing method, weights, health settings and keepalive all go inside the `upstream` block. A bare `proxy_pass` address has nowhere to put them.
- **Reuse.** Several locations or servers can point at the same upstream.

## The path and headers still apply

Everything from the earlier lessons still applies. The [trailing-slash rule](/course/nginx-basics/reverse-proxy/proxy-pass) and the [header block](/course/nginx-basics/reverse-proxy/proxy-headers) work exactly the same with a named upstream:

```nginx
upstream app_backend {
    server 127.0.0.1:3000;
}

server {
    listen 80;
    server_name app.example.com;

    location / {
        proxy_pass http://app_backend;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## Common mistake

Putting the `upstream` block **inside** a `server` block. It does not belong there - `upstream` is defined at the `http` level, alongside your servers. If you nest it, `nginx -t` fails with a "directive is not allowed here" error. Test after editing, as you learned in [testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely):

```bash
nginx -t
```

Another slip: writing the port on the upstream name, like `proxy_pass http://app_backend:3000;`. The port belongs on the `server` line inside the block, not on the name. The name already carries everything.

And keep the `http://` in front of the name. `proxy_pass app_backend;` without a scheme is not the same directive to nginx and fails the config test. The upstream name replaces the host and port in the URL, nothing more.

## FAQ

### Can an upstream have just one server?

Yes. A single-server upstream is perfectly normal - you use it for the readable name and to leave room for options later.

### Where exactly do I put the upstream block?

In the `http` context, typically in the same file as your server or in an included config. Not inside `server`, not inside `location`.

### Does the upstream name need to be unique?

Yes, within your config. Each `upstream` name must be distinct, and it must not collide with a real hostname you also proxy to by address.
