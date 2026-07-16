---
title: "Security headers"
slug: security-headers
seo_title: "nginx Security Headers: X-Frame-Options, CSP, add_header"
seo_description: "Add nginx security headers with add_header and always: X-Frame-Options, X-Content-Type-Options, Referrer-Policy and a starter CSP."
---

## What nginx security headers do

A handful of response headers tighten how the browser treats your site: they stop it being framed by attackers, stop content-type guessing, and control what referrer data leaks. These security headers are cheap to add and cost nothing to serve. You met `add_header` in [basic TLS hardening](/course/nginx-basics/https-tls/basic-tls-hardening); here are the ones worth setting.

## The headers

Add them in a `server {}` block so they apply to every response from that site:

```nginx
server {
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # ... your listen, root, location blocks
}
```

- `X-Frame-Options: SAMEORIGIN` stops other sites putting yours in an `<iframe>`, which blocks clickjacking.
- `X-Content-Type-Options: nosniff` tells the browser to trust your declared MIME types and not guess, which stops some content-type tricks.
- `Referrer-Policy: strict-origin-when-cross-origin` sends the full URL as referrer within your own site, but only the domain to other sites, so you leak less.

## The always flag

Notice `always` at the end of each line. Without it, nginx only adds the header on successful responses (2xx and a few others). With `always`, the header is sent on **error** responses too, like `403` and `500`. You want protection on every page, including error pages, so add `always`.

## A note on Content-Security-Policy

CSP is the strongest of these, but also the easiest to get wrong. It controls which sources of scripts, styles, and images the browser will load. A very strict policy looks like:

```nginx
add_header Content-Security-Policy "default-src 'self'" always;
```

That says "only load resources from my own domain". It is powerful, but it will break most sites instantly by blocking any external script, font, or analytics tag. Do not paste a CSP into production blind. Build it up gradually, test every page, and start in report-only mode if your setup supports it. For a first pass, ship the three headers above and treat CSP as a separate, careful project.

## Common mistake

`add_header` does not merge, it **resets**. If a `location {}` block adds even one `add_header` of its own, nginx drops **all** the headers inherited from the `server` block for that location. So a location that adds a caching header can silently lose your security headers. The fix: either keep security headers only where nothing overrides them, or repeat them in any location that adds its own header. This reset rule surprises everyone once. When a page is missing a header, check whether an inner block added one.

## FAQ

### Should these go in http, server, or location?

`server` is the usual home so they cover the whole site. Because of the reset rule above, avoid scattering `add_header` across locations unless you understand it.

### Do I still need HTTPS for these to matter?

Yes. Headers assume the connection is trusted. Serve over HTTPS (see [why HTTPS](/course/nginx-basics/https-tls/why-https)) so an attacker cannot strip or alter them in transit.

### How do I check they are set?

Use `curl -I https://example.com` and read the response headers, or your browser's network tab. Confirm they show on error pages too, which proves `always` is working.

### What about HSTS (Strict-Transport-Security)?

That is a security header too, but it belongs with TLS, so it lives in [basic TLS hardening](/course/nginx-basics/https-tls/basic-tls-hardening) rather than here. Set it only once you are sure the whole site is HTTPS, because it forces browsers to refuse plain HTTP for the duration you specify.
