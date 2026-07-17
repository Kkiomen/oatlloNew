---
title: "Nginx ERR_TOO_MANY_REDIRECTS - Redirect Loop Fix"
slug: too-many-redirects
seo_title: "Nginx ERR_TOO_MANY_REDIRECTS - Fix the Redirect Loop"
seo_description: "Fix nginx ERR_TOO_MANY_REDIRECTS: an HTTP to HTTPS loop behind Cloudflare or a proxy. Check X-Forwarded-Proto or switch Cloudflare to Full SSL."
---

## What the error looks like

The browser stops and shows:

```text
ERR_TOO_MANY_REDIRECTS
This page isn't working. example.com redirected you too many times.
```

The access log shows the same URL being redirected again and again:

```text
1.2.3.4 - - [16/Jul/2026:13:20:01] "GET / HTTP/1.1" 301 169 "-" "Mozilla/5.0"
1.2.3.4 - - [16/Jul/2026:13:20:01] "GET / HTTP/1.1" 301 169 "-" "Mozilla/5.0"
1.2.3.4 - - [16/Jul/2026:13:20:02] "GET / HTTP/1.1" 301 169 "-" "Mozilla/5.0"
```

## Why it happens

Something redirects HTTP to HTTPS, but the request never actually arrives as HTTPS, so it gets redirected forever. The classic setup is a proxy in front of nginx:

- **Cloudflare Flexible SSL.** Cloudflare talks HTTPS to the browser but plain HTTP to your server. Your nginx sees HTTP, redirects to HTTPS, Cloudflare turns it back into HTTP to your server, and the loop repeats.
- **A load balancer** that terminates TLS and forwards plain HTTP, with nginx still forcing a redirect.
- **A [bad redirect rule](/course/nginx-basics/location-matching/redirects-with-return)** that redirects a request back to itself.

## How to fix it

1. When nginx is behind a proxy, do not redirect blindly. Check the [`X-Forwarded-Proto` header, which the proxy sets to the original scheme](/course/nginx-basics/reverse-proxy/proxy-headers), and only redirect when it is not already HTTPS:

```nginx
server {
    listen 80;
    server_name example.com;

    if ($http_x_forwarded_proto = "http") {
        return 301 https://$host$request_uri;
    }
}
```

Now, if Cloudflare already served the browser over HTTPS, it sends `X-Forwarded-Proto: https` and nginx stops redirecting.

2. If you use Cloudflare, switch the SSL mode from **Flexible** to **Full** (or **Full (strict)**) in the Cloudflare dashboard. Flexible SSL is the single most common cause of this loop. Full means Cloudflare talks HTTPS to your server too, so the scheme is consistent. This needs a certificate on your server, which you set up in [redirect HTTP to HTTPS](/course/nginx-basics/https-tls/redirect-http-to-https).

3. Test and reload:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

## Common mistake

Adding a "force HTTPS" redirect in nginx AND turning on Cloudflare Flexible SSL. Each one is fine alone, together they build the loop. Pick one place to handle the scheme.

One thing that wastes an hour: after you fix the config, the loop can seem to survive. Browsers cache a `301` hard, so the old redirect keeps firing from cache even though the server no longer sends it. Test the fix in a private window or with `curl -I` before you conclude it did not work.

## FAQ

### How do I confirm it is a redirect loop?

Open the browser dev tools Network tab and reload. You will see the same URL returning 301 over and over to itself. That is the loop.

### Why does it work locally but loop in production?

Locally there is no proxy, so nginx sees the real scheme. In production Cloudflare or a load balancer sits in front and hides it. The `X-Forwarded-Proto` check fixes the gap.

### Is Cloudflare Flexible SSL bad?

For most sites, yes. It leaves the connection between Cloudflare and your server unencrypted and it causes this loop with any HTTPS redirect. Use Full once you have a certificate on the server.
