---
title: "Keepalive connections"
slug: keepalive
seo_title: "Nginx Keepalive: keepalive_timeout and Upstream Keepalive"
seo_description: "Reuse connections with nginx keepalive: keepalive_timeout for clients and upstream keepalive for backends, plus the three lines pooling needs."
---

Opening a network connection is not free. There is a handshake, and with HTTPS a TLS negotiation on top. If every request opened a fresh connection and closed it, you would pay that cost over and over. Nginx keepalive holds a connection open so several requests can reuse it. This matters in two places: between the browser and nginx, and between nginx and your backend.

## Client keepalive

This one is on by default. It controls how long nginx holds a browser's connection open, waiting for the next request:

```nginx
http {
    keepalive_timeout 65;
}
```

`keepalive_timeout 65;` means nginx keeps the connection open for 65 seconds of idle time. A page loads its HTML, then its CSS, JS, and images over that same connection instead of opening a new one each time. The result is a faster page load and less work per request.

The default is already good. Setting it too high wastes memory holding idle connections; setting it to `0` disables keepalive entirely, which you almost never want.

## Upstream keepalive

This is the one people forget. When nginx proxies to a backend (from [upstream blocks](/course/nginx-basics/reverse-proxy/upstream-blocks)), by default it opens a new connection to the backend for every request. Under load that adds up. You can pool and reuse those connections instead:

```nginx
upstream backend {
    server 127.0.0.1:3000;
    keepalive 32;
}

server {
    location / {
        proxy_pass http://backend;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
    }
}
```

## Why those three lines are needed

Upstream keepalive only works if all three pieces are present:

- `keepalive 32;` inside the `upstream` block keeps up to 32 idle connections to the backend ready for reuse. This is the line that enables pooling.
- `proxy_http_version 1.1;` is required because HTTP/1.0 closes the connection after each response. Keepalive needs HTTP/1.1. Nginx defaults to 1.0 when proxying, so you must set this.
- `proxy_set_header Connection "";` clears the `Connection` header. By default nginx would send `Connection: close`, which tells the backend to hang up after replying, defeating the whole point. An empty value lets the connection stay open. `proxy_set_header` was covered in [proxy headers](/course/nginx-basics/reverse-proxy/proxy-headers).

Miss any one and the connection is not actually reused.

## Common mistake: setting keepalive without the version and header

The most common error is adding `keepalive 32;` to the upstream block and stopping there. Without `proxy_http_version 1.1;` and the empty `Connection` header, nginx still closes each backend connection, and you get no benefit while thinking you tuned it. All three lines go together.

## FAQ

### Is client keepalive already on?

Yes. `keepalive_timeout` has a sensible default, so browsers already reuse connections to nginx. You usually only touch the upstream side.

### What number should keepalive use?

`keepalive 32;` is a fine starting point for a single backend. It is a cap on idle connections held ready, not a limit on total requests. Raise it only if you proxy very heavy traffic.

One detail that catches people: the count is per worker process, not per server. With `worker_processes auto;` on a 4-core box, `keepalive 32;` means up to 32 idle connections in each of the 4 workers, so up to 128 held open to the backend. Keep that in mind so the pool stays comfortably under whatever connection limit your backend enforces.

### Does this help a small site?

The gain grows with traffic and with TLS between nginx and the backend. On a low-traffic site the difference is small, but the config is cheap and correct, so it is worth setting once.
