---
title: "A Node app behind nginx"
slug: node-app-behind-nginx
seo_title: "Nginx Reverse Proxy for a Node App (HTTPS + WebSockets)"
seo_description: "A complete nginx reverse proxy config for a Node app on 127.0.0.1:3000: HTTPS, proxy headers, upstream keepalive, and WebSocket upgrade headers."
---

## Why put nginx in front of a Node app

Your Node app (Express, Nest, Next.js, whatever) runs happily on `127.0.0.1:3000`. Exposing port 3000 to the internet directly is a bad idea: it has no HTTPS, it should not run as root to bind port 80, and you want one hardened process guarding it. This is where an nginx reverse proxy for a Node app earns its place. It terminates TLS, adds the headers Node needs, and forwards clean HTTP to localhost.

You met the idea in [what is a reverse proxy](/course/nginx-basics/reverse-proxy/what-is-a-reverse-proxy). Here is the full config, WebSockets included, explained.

## The full config

```nginx
# The Node backend, with a keepalive pool
upstream node_app {
    server 127.0.0.1:3000;
    keepalive 32;
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name app.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name app.example.com;

    ssl_certificate     /etc/letsencrypt/live/app.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.example.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;

    location / {
        proxy_pass http://node_app;

        # Preserve the original request details
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # WebSocket support
        proxy_http_version 1.1;
        proxy_set_header Upgrade    $http_upgrade;
        proxy_set_header Connection $connection_upgrade;

        # Timeouts
        proxy_read_timeout 60s;
    }
}
```

One extra piece is needed at the top of the config for WebSockets, shown at the end. First, the walk-through.

## The upstream block

```nginx
upstream node_app {
    server 127.0.0.1:3000;
    keepalive 32;
}
```

An `upstream` names a group of backend servers, from [upstream blocks](/course/nginx-basics/reverse-proxy/upstream-blocks). Even with a single Node process it is worth using, because it lets you add `keepalive`.

`keepalive 32` keeps up to 32 idle connections to Node open and reused, instead of opening a fresh TCP connection for every request. This is the keepalive optimization from [keepalive](/course/nginx-basics/performance-and-caching/keepalive), and it noticeably cuts latency on busy apps. Keepalive to an upstream requires `proxy_http_version 1.1` in the location, which we set below.

To scale later, add more `server` lines and nginx load-balances across them, exactly as in [load balancing](/course/nginx-basics/reverse-proxy/load-balancing):

```nginx
upstream node_app {
    server 127.0.0.1:3000;
    server 127.0.0.1:3001;
    keepalive 32;
}
```

## The redirect and TLS

```nginx
server {
    listen 80;
    ...
    return 301 https://$host$request_uri;
}
```

Same HTTP to HTTPS bounce as the other lessons, from [redirect HTTP to HTTPS](/course/nginx-basics/https-tls/redirect-http-to-https). The HTTPS block opens port 443 with the certbot certificate, from [listen 443 ssl](/course/nginx-basics/https-tls/listen-443-ssl) and [Let's Encrypt with certbot](/course/nginx-basics/https-tls/lets-encrypt-certbot). Note there is no `root` here: nginx serves nothing from disk, it only proxies.

## proxy_pass

```nginx
location / {
    proxy_pass http://node_app;
    ...
}
```

`proxy_pass` forwards the request to the backend, from [proxy_pass](/course/nginx-basics/reverse-proxy/proxy-pass). Here it targets `http://node_app`, the upstream name we defined, so nginx uses the keepalive pool. Everything under `/` goes to Node, which is what you want for a single-page or server-rendered app that owns all its routes.

## Proxy headers: telling Node the truth

```nginx
proxy_set_header Host              $host;
proxy_set_header X-Real-IP         $remote_addr;
proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Proto $scheme;
```

This is the most important part, and it is the topic of [proxy headers](/course/nginx-basics/reverse-proxy/proxy-headers). Without these, Node sees every request as coming from `127.0.0.1` over plain HTTP, because that is literally what nginx sends it.

- `Host $host` passes the domain the browser asked for, so Node can generate correct absolute URLs.
- `X-Real-IP $remote_addr` gives Node the visitor's real IP, not nginx's.
- `X-Forwarded-For` appends the client IP to any existing chain, the standard header proxies use.
- `X-Forwarded-Proto $scheme` tells Node the original request was `https`. This is critical: frameworks use it to build `https://` links and to set the Secure flag on cookies. Miss it and your app may redirect-loop trying to "upgrade" a request it thinks is HTTP.

In Express you enable `app.set('trust proxy', 1)` so it reads these headers. Other frameworks have the same switch.

## WebSocket upgrade headers

```nginx
proxy_http_version 1.1;
proxy_set_header Upgrade    $http_upgrade;
proxy_set_header Connection $connection_upgrade;
```

A WebSocket starts as an HTTP request that asks to "upgrade" the connection to a persistent socket. That upgrade only survives the proxy if nginx forwards two special headers.

- `proxy_http_version 1.1` is required, because the upgrade mechanism does not exist in HTTP/1.0. This same directive is what enables upstream keepalive too.
- `Upgrade $http_upgrade` passes through the client's upgrade request.
- `Connection $connection_upgrade` sets the connection header to `upgrade` for WebSocket requests and `close` for normal ones.

That `$connection_upgrade` variable is not built in. You define it once in the `http` context, above all your `server` blocks (in `nginx.conf` or a snippet in `conf.d/`):

```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}
```

The `map` reads the incoming `Upgrade` header: if it is set (a WebSocket), `$connection_upgrade` becomes `upgrade`; if empty (a normal request), it becomes `close`. This keeps regular HTTP and WebSockets both working through one location. Without this map, Socket.io, live reload, and any realtime feature silently fail to connect.

There is a subtle cost worth knowing. The `close` value tells the backend to hang up after each normal response, which quietly defeats the upstream `keepalive` pool for plain HTTP requests: the two features pull against each other. On most sites the WebSocket path matters more than shaving a TCP handshake, so the map wins and you leave it. If your traffic is nearly all plain HTTP and you want the keepalive pool to actually hold, set the empty-header case to `''` instead of `close`. Do not chase both at once without measuring.

## Timeouts

```nginx
proxy_read_timeout 60s;
```

`proxy_read_timeout` is how long nginx waits for Node to send data before giving up with a `504 Gateway Timeout`. The default is 60 seconds. Raise it if your app has legitimately long requests (a slow report), but a better fix is usually to move slow work to a background job.

## Test and reload

```bash
sudo nginx -t
sudo systemctl reload nginx
```

The `map` lives in the `http` context, so if you put it in the wrong place `nginx -t` from [testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely) will tell you before you reload.

## FAQ

### Why proxy to 127.0.0.1 and not 0.0.0.0?

Bind Node to `127.0.0.1:3000` so it is only reachable from the same machine. Then the only way in is through nginx, which handles TLS and headers. If Node listened on `0.0.0.0`, people could hit port 3000 directly and bypass everything.

### My WebSockets connect but keep dropping.

Two usual causes. Either the `Upgrade`/`Connection` headers are missing (add the map and the two `proxy_set_header` lines), or `proxy_read_timeout` is too short and idle sockets get cut. Raise the timeout for long-lived connections.

### Do I still need proxy headers if I only run one server?

Yes. The headers are not about scaling, they are about telling Node who the real client is and whether the original request was HTTPS. Skip `X-Forwarded-Proto` and your app can end up in a redirect loop.

### Can I serve static files with nginx instead of Node?

Yes, and you should. Add a `location` for your assets folder with `root` and `expires` (from [caching static assets](/course/nginx-basics/performance-and-caching/caching-static-assets)) so nginx serves them directly and Node only handles dynamic routes. Nginx is much faster at static files.
