---
name: "Setting Up Nginx as a Reverse Proxy"
slug: nginx-reverse-proxy-guide
short_description: "How to put nginx in front of a Node or PHP app: proxy_pass, the forwarded headers your app needs, WebSockets, timeouts and a full server block."
language: en
published_at: 2027-05-17 09:00:00
is_published: true
tags: [nginx, devops, node, websockets]
---

The first time I put nginx in front of a Node app, everything worked in the browser and nothing worked in the logs. Every request came from `127.0.0.1`. Rate limiting was useless, the "who did this" audit trail was a wall of localhost, and the app's secure-cookie logic thought the whole world was on plain HTTP. Nothing was broken, exactly. I just hadn't told nginx to pass along the parts of the request it was quietly eating.

That's the thing about a reverse proxy: the happy path is trivial, and the details that bite you don't show up until later. This is a walkthrough of the reverse-proxy role specifically — nginx sitting in front of an application server (a Node process, or a PHP app behind php-fpm) and forwarding traffic to it. If you're wiring nginx directly to php-fpm with `fastcgi_pass`, that's a different mechanism and a different article. Here we're talking to something that speaks HTTP.

## What "reverse proxy" actually buys you

A reverse proxy takes the client's request, hands it to one of your backend servers, and passes the reply back. "Reverse" because it fronts *your* servers, not the client's outbound traffic. You reach for one because your app server shouldn't be spending its time on jobs nginx does better:

- **TLS termination.** nginx holds the certificate, decrypts HTTPS, and talks plain HTTP to your app over the loopback. Your Node process never touches a cert. One place to renew, one place to configure ciphers.
- **A single entry point.** Port 443 goes to nginx. Behind it you can run an app on `:3000`, an admin panel on `:3001`, and static files off disk — all under one hostname, routed by path.
- **Load balancing.** One nginx, several identical app instances. It spreads requests and stops sending traffic to a process that fell over.
- **Serving static assets directly.** Let nginx hand out `/build/*.js` from disk at wire speed and only bother your app with requests that need application logic.

You don't need all four to justify it. TLS termination alone is usually enough.

## The minimal proxy that works

Here's the smallest thing that does something useful. Assume a Node app listening on `127.0.0.1:3000`.

```nginx
server {
    listen 80;
    server_name app.example.com;

    location / {
        proxy_pass http://127.0.0.1:3000;
    }
}
```

`proxy_pass` is the whole trick: nginx opens a connection to the address you name and forwards the request there. This will serve pages. It will also mislead your application about who's calling and how, because by default nginx rewrites the `Host` header to the proxy target and doesn't tell the backend anything about the original client. So don't stop here.

## The headers your app is starving for

When nginx proxies a request, the backend sees nginx — its IP, its connection, its scheme. If the app needs to know anything about the real client, you have to forward it explicitly. Three headers matter, and skipping them causes the most common "it works but behaves weirdly" bugs.

```nginx
location / {
    proxy_pass http://127.0.0.1:3000;

    proxy_set_header Host              $host;
    proxy_set_header X-Real-IP         $remote_addr;
    proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Host  $host;
}
```

What each one fixes:

- **`Host $host`** — preserves the hostname the client actually asked for. Without it, apps that route by domain (multi-tenant setups, or anything generating absolute URLs) get the wrong host and build broken links.
- **`X-Forwarded-For`** — the real client IP. `$proxy_add_x_forwarded_for` appends `$remote_addr` to any existing header value, so a chain of proxies leaves a full trail instead of clobbering it. This is the one that fixes my "everything is 127.0.0.1" story.
- **`X-Forwarded-Proto $scheme`** — was the *original* request HTTP or HTTPS? Since nginx terminates TLS and talks plain HTTP to the app, the app thinks every request is insecure unless you tell it. This is why apps redirect-loop behind a proxy: the framework sees HTTP, forces a redirect to HTTPS, nginx serves it, the app sees HTTP again, and around you go.

Setting the headers is only half the job. **Your app has to be told to trust them.** Any client can send an `X-Forwarded-For`, so a framework that blindly believes it would let anyone spoof their IP. Frameworks solve this with a trusted-proxy list: only honor forwarded headers when the connection came from a proxy you control.

In Express, one line:

```js
// Trust the first proxy in front of the app (nginx on loopback).
app.set('trust proxy', 'loopback');
```

Now `req.ip` reflects `X-Forwarded-For` and `req.protocol` reflects `X-Forwarded-Proto` — but only for connections from loopback, so nobody on the open internet can forge them. Laravel has the same concept in `App\Http\Middleware\TrustProxies` (set `$proxies = '*'` only if nginx is the sole thing that can reach the app). The rule is identical everywhere: forward the headers at nginx, trust them at the app, and make "trust" specific to the proxy's address.

## Upstreams and load balancing

Hardcoding `http://127.0.0.1:3000` is fine for one process. The moment you run two, name them in an `upstream` block:

```nginx
upstream app_backend {
    server 127.0.0.1:3000;
    server 127.0.0.1:3001;
    server 127.0.0.1:3002;
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

Default balancing is round-robin. Two options earn their keep:

- **`least_conn;`** sends each request to the backend with the fewest active connections. Better than round-robin when request durations vary — a slow endpoint won't pile requests onto a busy worker.
- **Sticky sessions.** If your app keeps session state in memory (it shouldn't, but sometimes it does), a user bouncing between instances loses their login. `ip_hash;` pins a client IP to one backend. The real fix is externalizing sessions to Redis so any instance can serve anyone, but `ip_hash` buys you time.

You can also mark a backend `down` to drain it before a deploy, or add `max_fails=3 fail_timeout=30s` so nginx stops routing to an instance that's returning errors and retries it after 30 seconds.

## WebSockets: the bug everyone hits

This is the one that generates support tickets. HTTP works, the app loads, and then live chat / notifications / the dev server's hot reload just… doesn't connect. The browser console shows a WebSocket that opened and immediately closed.

A WebSocket starts life as an HTTP request carrying `Connection: Upgrade` and `Upgrade: websocket`. nginx, by default, does **not** forward hop-by-hop headers like `Connection` and `Upgrade` to the backend. So the handshake never completes — the app never sees the upgrade request. You have to pass those two headers through explicitly, and bump the protocol to HTTP/1.1 (WebSockets need it; nginx defaults to 1.0 upstream):

```nginx
location / {
    proxy_pass http://app_backend;

    proxy_http_version 1.1;
    proxy_set_header Upgrade    $http_upgrade;
    proxy_set_header Connection $connection_upgrade;

    proxy_set_header Host              $host;
    proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

`$connection_upgrade` isn't a built-in variable — you define it with a `map` at the `http` level, so plain requests still get `Connection: close` and only upgrade requests get `Connection: upgrade`:

```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}
```

Setting `Connection "upgrade"` as a flat string works too, but the map is cleaner: it doesn't force the upgrade semantics onto ordinary requests. Either way, the failure signature is worth memorizing — HTTP fine, WebSocket dead — because you'll diagnose it faster than you'll re-read the docs.

## Timeouts and buffering, or why long requests get cut off

Two defaults trip people up on real workloads.

**Timeouts.** nginx gives up on a slow backend after `proxy_read_timeout` (60 seconds by default). For a big file export, a slow report, or a long-lived Server-Sent Events stream, 60 seconds is short — the client gets a `504 Gateway Time-out` mid-response. Raise it only where you need it:

```nginx
location /api/export {
    proxy_pass http://app_backend;
    proxy_read_timeout 300s;
    proxy_send_timeout 300s;
}
```

Don't crank it globally to hide a slow endpoint — a 300s read timeout on `/` just means a hung backend ties up connections for five minutes.

**Buffering.** By default nginx buffers the backend's whole response before sending it on, which is great for throughput on normal responses. It's wrong for streaming. If you're pushing SSE or a live log, buffering makes nginx hold the data and the client sees nothing until the buffer flushes or the response ends. Turn it off for those routes:

```nginx
location /events {
    proxy_pass http://app_backend;
    proxy_buffering off;
    proxy_read_timeout 3600s;
}
```

Leave buffering on everywhere else. Disabling it globally trades away real performance to fix a problem you have on two endpoints.

## gzip

Compress text responses at nginx so you're not shipping uncompressed JSON and HTML over the wire. It's a few lines in the `http` block:

```nginx
gzip on;
gzip_vary on;
gzip_comp_level 5;
gzip_min_length 256;
gzip_types text/plain text/css application/json application/javascript
           text/xml application/xml image/svg+xml;
```

`gzip_comp_level 5` is the sweet spot — level 9 costs noticeably more CPU for a fraction of a percent smaller payload. `gzip_min_length 256` skips tiny responses where the compression overhead outweighs the saving. Don't add already-compressed types (JPEG, PNG, `.woff2`); you'd burn CPU shrinking nothing.

## A full working server block

Putting it together — TLS termination, a load-balanced upstream, static files served straight off disk, WebSocket support, and the forwarded headers, with an HTTP→HTTPS redirect out front:

```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}

upstream app_backend {
    least_conn;
    server 127.0.0.1:3000 max_fails=3 fail_timeout=30s;
    server 127.0.0.1:3001 max_fails=3 fail_timeout=30s;
}

server {
    listen 80;
    server_name app.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name app.example.com;

    ssl_certificate     /etc/letsencrypt/live/app.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.example.com/privkey.pem;

    # Serve built assets directly, skip the app entirely.
    location /build/ {
        root /var/www/app/public;
        access_log off;
        expires 30d;
    }

    location / {
        proxy_pass http://app_backend;

        proxy_http_version 1.1;
        proxy_set_header Upgrade    $http_upgrade;
        proxy_set_header Connection $connection_upgrade;

        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_read_timeout 60s;
    }
}
```

The `/build/` block matters more than it looks: static requests never wake your app up, and `expires 30d` lets browsers cache hashed assets. Every request nginx answers itself is one your Node process didn't have to.

After any edit, test the config before reloading — `nginx -t` catches syntax errors and bad paths, and `nginx -s reload` applies changes without dropping live connections:

```bash
sudo nginx -t && sudo nginx -s reload
```

## FAQ

### Why does my app see every request as coming from 127.0.0.1?

Because that *is* who's connecting — nginx, on the loopback. The real client IP lives in `X-Forwarded-For`, which you have to forward with `proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;` and then trust in the app (Express `trust proxy`, Laravel `TrustProxies`). Set both sides or IP-based logic and rate limiting stay blind.

### My WebSocket connects then drops immediately behind nginx. What's missing?

The `Upgrade` and `Connection` headers, plus `proxy_http_version 1.1`. nginx doesn't forward hop-by-hop headers by default, so the backend never sees the upgrade handshake. Add the `map` for `$connection_upgrade` and set those three directives on the location.

### Reverse proxy vs. `fastcgi_pass` to php-fpm — which do I use for a PHP app?

Different protocols. `proxy_pass` speaks HTTP, so use it when your app runs as its own HTTP server (Node, a Go binary, or PHP served by something like FrankenPHP or Octane). `fastcgi_pass` speaks FastCGI and talks to php-fpm directly — that's the classic Laravel/WordPress setup. If your PHP app already listens on an HTTP port, treat it like any other upstream and `proxy_pass` to it.

### Do I still need TLS between nginx and the backend?

Over loopback or a trusted private network, usually no — plain HTTP is fine and simpler, which is why we terminate TLS at nginx. If the backend sits on a network you don't fully control, re-encrypt with `proxy_pass https://...` and configure `proxy_ssl_*` directives. Most single-box setups don't need it.

## Where to go from here

Get the four forwarded headers right and trust them at the app, and the majority of proxy weirdness disappears before it starts — the localhost logs, the redirect loops, the dead WebSockets. Everything after that is tuning: timeouts where you have slow endpoints, buffering off where you stream, gzip on for text. Start from the full server block above, delete what you don't use, and run `nginx -t` before every reload. The next thing worth adding is rate limiting with `limit_req` — now that nginx knows the real client IP, it can actually enforce per-client limits instead of throttling itself.
