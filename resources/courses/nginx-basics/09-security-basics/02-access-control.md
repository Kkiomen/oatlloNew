---
title: "Restrict access by IP"
slug: access-control
seo_title: "nginx Restrict Access by IP: allow and deny"
seo_description: "Restrict access by IP in nginx with allow and deny to lock an admin area to office IPs. How CIDR ranges work and why rule order matters."
---

## Why restrict access by IP

An admin panel, a staging area, or an internal dashboard rarely needs the whole internet. It only has to be reachable from a few known places, like your office. To restrict access by IP, nginx can allow or block requests by address before they ever reach your app.

## allow and deny

Two directives do the work: `allow` lets an address through, `deny` blocks it. You list the addresses you trust, then deny everyone else:

```nginx
location /admin {
    allow 203.0.113.10;      # office static IP
    allow 198.51.100.0/24;   # a whole range (see below)
    deny all;                # block everyone else

    # ... your normal handling, e.g. proxy_pass or try_files
}
```

A blocked request gets `403 Forbidden`. This sits in front of whatever the location normally does, so it works for static files, PHP, and reverse proxies alike. You met the `location` block in [location basics](/course/nginx-basics/serving-static-content/location-basics).

## CIDR: matching a range

`198.51.100.0/24` is CIDR notation. It means a block of addresses, not just one:

- `/32` is a single address (`203.0.113.10/32` equals `203.0.113.10`).
- `/24` is 256 addresses, `198.51.100.0` through `198.51.100.255`.
- Smaller numbers mean bigger ranges.

Use a range when your office IP is not fixed but stays within one block your provider gave you.

## Why allow must come before deny all

nginx reads the rules **top to bottom and stops at the first match**. So the specific `allow` lines must come before the catch-all `deny all`. If you flip them, the deny matches first and nobody gets in:

```nginx
location /admin {
    deny all;            # matches everything first...
    allow 203.0.113.10;  # ...so this is never reached
}
```

Rule: list every `allow` first, then `deny all` last.

One inheritance quirk to know: `allow`/`deny` rules are inherited from a parent block only when the child block defines none of its own. The moment a `location` adds a single `allow` or `deny`, it replaces the whole inherited set for that location rather than adding to it. So a `deny all` in `server` will not protect a location that has its own access rules unless you repeat the deny there.

## Common mistake

Behind a reverse proxy or CDN, every request looks like it comes from the proxy, so your `allow` for the real office IP never matches (or worse, `deny all` blocks legit traffic). nginx sees the connecting IP, which is the proxy. To match the true client you need the real IP passed through (the `X-Forwarded-For` header you saw in [proxy headers](/course/nginx-basics/reverse-proxy/proxy-headers)) plus the `realip` module. On a plain server with no proxy in front, `allow`/`deny` just works.

## FAQ

### Can I use this on a whole server, not one location?

Yes. Put `allow`/`deny` directly in the `server {}` block to guard the entire site, or in `http` to guard everything.

### What response do blocked visitors get?

`403 Forbidden`. You can style it with a [custom error page](/course/nginx-basics/serving-static-content/custom-error-pages).

### Is IP blocking enough to protect an admin panel?

No. IPs can change and are not a login. Combine it with a password (next lesson) and HTTPS. Treat it as one layer, not the only one.
