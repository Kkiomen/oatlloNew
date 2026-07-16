---
title: "Nginx 504 Gateway Timeout - Causes and Fix"
slug: 504-gateway-timeout
seo_title: "Nginx 504 Gateway Timeout - How to Fix It"
seo_description: "Fix nginx 504 Gateway Timeout: raise fastcgi_read_timeout or proxy_read_timeout, but remember the real fix is usually a slow backend."
---

## What the error looks like

The browser sits for a while, then shows:

```text
504 Gateway Timeout
nginx/1.24.0
```

The error log names the timeout:

```text
2026/07/16 12:41:09 [error] 1234#0: *12 upstream timed out (110: Connection timed out) while reading response header from upstream, client: 1.2.3.4, server: example.com, request: "GET /report HTTP/1.1"
```

## Why it happens

A 504 means nginx reached your backend but the backend took too long to answer. Nginx waited up to its timeout, gave up, and returned 504. The default read timeout is 60 seconds. The backend is slow: a heavy database query, a long report, or an external API call that hangs.

## How to fix it

1. Raise the timeout for the backend that is slow. For PHP-FPM (FastCGI, see [fastcgi_pass](/course/nginx-basics/nginx-and-php/fastcgi-pass)):

```nginx
location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_read_timeout 120;
}
```

2. For a proxied app (see [proxy_pass](/course/nginx-basics/reverse-proxy/proxy-pass)), raise the proxy timeouts:

```nginx
location / {
    proxy_pass http://127.0.0.1:3000;
    proxy_connect_timeout 10;
    proxy_read_timeout 120;
}
```

`proxy_connect_timeout` is how long nginx waits to open the connection. `proxy_read_timeout` is how long it waits for the response.

Worth knowing: `proxy_read_timeout` is an inactivity timer, not a total budget. It resets every time the backend sends a byte. A backend that streams a little output keeps the connection alive well past the number you set, while a backend that goes silent for that long gets cut. That is why streamed responses sometimes never time out and a stuck query always does.

3. Test and reload:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

4. If PHP is the backend, note that PHP has its own `max_execution_time`. A long request also needs PHP to allow it, so raise that in `php.ini` too.

## Common mistake

Treating the timeout as the fix. Raising the timeout only hides the symptom. If a page takes 90 seconds, users still wait 90 seconds. The real fix is almost always the slow backend: add a database index, cache the result, or move the heavy work to a background job.

## FAQ

### 504 vs 502, which is which?

502 means the backend gave nginx a bad or refused response right away. 504 means the backend was reachable but too slow to answer within the timeout. Slow points at 504.

### How high should I set the timeout?

Just above your slowest legitimate request, not higher. A very large timeout lets a stuck request tie up a worker for minutes. Fix the slowness instead of setting the timeout to 600.

### The timeout is raised but I still get 504. Why?

Check every layer. PHP's `max_execution_time`, PHP-FPM's own `request_terminate_timeout` (which kills the worker regardless of what nginx allows), a load balancer or Cloudflare in front (Cloudflare has its own ~100s limit), and the database can each cut the request off before nginx does. Raise the one that fires first, or you keep hitting the same wall.
