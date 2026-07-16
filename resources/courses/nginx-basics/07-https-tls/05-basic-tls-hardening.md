---
title: "Basic TLS hardening"
slug: basic-tls-hardening
seo_title: "Nginx TLS Hardening: ssl_protocols, Ciphers, HSTS"
seo_description: "Beginner-safe nginx TLS hardening: ssl_protocols TLSv1.2 TLSv1.3, ssl_ciphers, ssl_prefer_server_ciphers and an HSTS Strict-Transport-Security header."
---

## Why nginx defaults still need hardening

Working HTTPS is not the same as modern HTTPS. Out of the box, nginx may still negotiate old, weak TLS versions and weak ciphers with clients that ask for them. A few lines of TLS hardening - starting with `ssl_protocols` - close those doors and leave the encrypted connection genuinely current. Everything here stays safe and well understood, with no exotic tuning.

## Restrict the protocol versions

```nginx
ssl_protocols TLSv1.2 TLSv1.3;
```

This tells nginx to accept only TLS 1.2 and 1.3. The older TLS 1.0 and 1.1 are deprecated and insecure; there is no reason to allow them. Every current browser speaks 1.2 or 1.3, so nothing breaks.

## Choose the ciphers

A cipher is the specific set of algorithms used to encrypt a connection. You want strong ones only:

```nginx
ssl_ciphers HIGH:!aNULL:!MD5;
ssl_prefer_server_ciphers on;
```

- **`ssl_ciphers`** - `HIGH` means strong ciphers only; `!aNULL` and `!MD5` explicitly rule out unauthenticated and weak-hash options. This is a solid, conservative list.
- **`ssl_prefer_server_ciphers on`** - when the browser and server both support several ciphers, let the **server** pick, so a client can't push you toward a weaker one.

TLS 1.3 manages its own strong cipher set, so these mainly guard TLS 1.2.

## Turn on HSTS

HSTS (HTTP Strict Transport Security) is a response header that tells the browser "for this domain, always use HTTPS - never even try plain HTTP". After the first visit, the browser upgrades every request to HTTPS on its own, before it ever hits your redirect.

```nginx
add_header Strict-Transport-Security "max-age=31536000" always;
```

- **`add_header`** - attaches a header to every response from this block. This is your first header directive; you'll use it more in [Chapter 9](/course/nginx-basics/security-basics/security-headers).
- **`Strict-Transport-Security`** - the HSTS header name.
- **`max-age=31536000`** - remember this for one year (in seconds).
- **`always`** - send the header even on error responses like 404, not just on 200.

## Putting it together

Drop these into your HTTPS `server` block from [listen 443 ssl](/course/nginx-basics/https-tls/listen-443-ssl):

```nginx
server {
    listen 443 ssl;
    server_name example.com;

    ssl_certificate     /etc/nginx/ssl/example.com/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/example.com/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    add_header Strict-Transport-Security "max-age=31536000" always;

    root /var/www/example.com/public;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

Then, as always:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## Common mistake

**Turning on HSTS before HTTPS is solid.** HSTS is sticky. Once a browser has seen `max-age=31536000`, it refuses plain HTTP for your domain for a year - you can't undo it from the server side. Only add the header once HTTPS works everywhere. While testing, use a small value like `max-age=300` (five minutes), then raise it.

**Adding HSTS to the port 80 block.** The header only means something over HTTPS, so put it in the `listen 443 ssl` block, not the redirect block.

**A `location` with its own `add_header` drops HSTS.** nginx does not merge `add_header` across levels: the moment any `location` sets its own header, that block stops inheriting the ones from the `server` block, so your Strict-Transport-Security quietly vanishes for those URLs. If a location needs its own header, repeat the HSTS line there too. This inheritance rule bites more headers in [Chapter 9](/course/nginx-basics/security-basics/security-headers).

**Chasing a perfect score.** You can spend hours on OCSP stapling, custom DH parameters and cipher ordering. For a beginner-safe setup, the lines above are enough. Deeper security headers come in [Chapter 9](/course/nginx-basics/security-basics/security-headers).

## FAQ

### Will these settings lock out older browsers?

Only truly ancient ones (think Internet Explorer on Windows XP). Anything from the last decade supports TLS 1.2, so in practice no real users are affected.

### What does ssl_prefer_server_ciphers actually change?

It decides who chooses the cipher when both sides support several. `on` gives the choice to your server, so you control the strength rather than trusting the client.

### Should I add includeSubDomains to HSTS?

Only if every subdomain is already HTTPS. `max-age=31536000; includeSubDomains` forces HTTPS on all of them too - great when true, breaking when a subdomain is still HTTP. Start without it.
