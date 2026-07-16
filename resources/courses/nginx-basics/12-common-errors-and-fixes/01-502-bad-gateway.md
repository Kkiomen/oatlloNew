---
title: "Nginx 502 Bad Gateway - Causes and Fix"
slug: 502-bad-gateway
seo_title: "Nginx 502 Bad Gateway - Causes and How to Fix It"
seo_description: "Fix nginx 502 Bad Gateway: PHP-FPM down, wrong fastcgi_pass socket, wrong proxy_pass port, or socket permissions. Diagnose with the error log."
---

## What the error looks like

In the browser you get a short page:

```text
502 Bad Gateway
nginx/1.24.0
```

The real clue is in the error log (see [access and error logs](/course/nginx-basics/configuration-basics/access-and-error-logs)):

```text
2026/07/16 10:22:41 [error] 1234#0: *5 connect() to unix:/run/php/php8.4-fpm.sock failed (2: No such file or directory) while connecting to upstream, client: 1.2.3.4, server: example.com, request: "GET /index.php HTTP/1.1"
```

## Why it happens

A 502 means nginx reached your backend but got no valid response. Nginx is fine. The thing behind it is not. The usual causes:

- **PHP-FPM or your app is down or crashed.** Nothing is listening, so the connection is refused.
- **Wrong `fastcgi_pass` socket path.** The socket file in your config does not match the one PHP-FPM actually creates. See [fastcgi_pass](/course/nginx-basics/nginx-and-php/fastcgi-pass).
- **Wrong `proxy_pass` port.** For a proxied app (see [proxy_pass](/course/nginx-basics/reverse-proxy/proxy-pass)), the port in your config does not match the port the app listens on.
- **PHP-FPM ran out of workers.** Under load the pool hits `pm.max_children`, every worker is busy, and new connections stall or fail. FPM is technically "running" but cannot answer, so you see 502 only when traffic spikes.
- **Permissions or SELinux on the socket.** The socket exists, but the nginx user cannot connect to it.

The one non-obvious trap: on Red Hat and friends, SELinux blocks nginx from opening a network connection to a proxied backend even when the port is right. The nginx error log says `Permission denied` while everything looks fine. `setsebool -P httpd_can_network_connect 1` is the fix there.

## How to fix it

1. Read the error log first. It names the exact cause:

```bash
sudo tail -f /var/log/nginx/error.log
```

2. Check the backend is actually running:

```bash
sudo systemctl status php8.4-fpm
sudo systemctl restart php8.4-fpm
```

3. Confirm the socket path matches. Look at PHP-FPM's `listen` value and make your `fastcgi_pass` line match it exactly:

```bash
grep listen /etc/php/8.4/fpm/pool.d/www.conf
```

```nginx
fastcgi_pass unix:/run/php/php8.4-fpm.sock;
```

4. For a proxied app, confirm the port. If your app runs on 3000, your config must say 3000:

```nginx
proxy_pass http://127.0.0.1:3000;
```

5. If it is a permissions issue, make sure PHP-FPM's pool runs as the same user nginx expects (`user` and `group` in the pool file), then reload:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

## Common mistake

Restarting nginx over and over. Nginx is not the problem in a 502. The backend is. Restart PHP-FPM or your app, not nginx.

## FAQ

### Why do I get 502 only on .php pages but static files work?

Static files never touch PHP-FPM. A 502 only on PHP means the FastCGI backend is down or the `fastcgi_pass` path is wrong. Your static config is fine.

### 502 Bad Gateway vs 504 Gateway Timeout, what is the difference?

502 means the backend answered with garbage or refused the connection. 504 means the backend was reached but took too long to reply. Different causes, different fixes.

### I updated PHP and now everything is 502. Why?

The socket filename usually contains the version, like `php8.4-fpm.sock`. After an upgrade the old socket is gone. Update `fastcgi_pass` to the new version's socket and reload.
