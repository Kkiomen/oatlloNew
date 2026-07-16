---
title: "Redirect HTTP to HTTPS"
slug: redirect-http-to-https
seo_title: "Nginx Redirect HTTP to HTTPS with return 301"
seo_description: "Force HTTPS in nginx with a port 80 server block and return 301 https://$host$request_uri. Learn why return beats rewrite and how to avoid loops."
---

## Why HTTP still loads after you enable HTTPS

Turning on HTTPS does not turn off HTTP. `http://example.com` still answers on port 80, so visitors who type the bare address, click an old link, or open a bookmark land on the insecure version. To redirect HTTP to HTTPS in nginx, you send every port-80 request straight to its HTTPS twin.

The tool is a small server block on port 80 whose only job is that redirect.

## The redirect block

```nginx
server {
    listen 80;
    server_name example.com www.example.com;

    return 301 https://$host$request_uri;
}
```

That is the whole thing. Line by line:

- **`listen 80`** - this block handles plain HTTP.
- **`server_name`** - the domains it answers for. List every hostname you serve.
- **`return 301 https://$host$request_uri`** - send the browser a permanent redirect to the HTTPS version of the exact same URL.

Two built-in variables do the work:

- **`$host`** - the hostname the visitor asked for, so `example.com` and `www.example.com` each redirect to themselves.
- **`$request_uri`** - the full path and query string, e.g. `/blog/post?id=5`. Keeping it means someone who requested a deep link lands on that same page over HTTPS, not the homepage.

`301` means "moved permanently". Browsers and search engines remember it and go straight to HTTPS next time. You met `return` in [redirects with return](/course/nginx-basics/location-matching/redirects-with-return); this is that same directive doing one focused job.

## Why return, not rewrite

You could also write:

```nginx
rewrite ^ https://$host$request_uri permanent;
```

It works, but `return` is the right tool here:

- **It is clearer.** `return 301 ...` says exactly what happens - one status code, one destination.
- **It is faster.** `rewrite` runs a regular expression on every request. `return` does not.
- **It is safer.** `rewrite` is a mini pattern-matching engine that is easy to get subtly wrong. For a whole-site redirect there is nothing to match - you always send everyone to HTTPS - so the regex is pure overhead.

Rule of thumb: use `return` when the destination is fixed, `rewrite` only when you must transform the path.

## Common mistake

**Redirect loop behind a proxy or load balancer.** If nginx sits behind something that already terminated HTTPS (a load balancer, Cloudflare), nginx sees plain HTTP on port 80 even when the visitor came over HTTPS. A blind `return 301 https://...` then redirects forever. In that case, redirect based on the forwarded header instead:

```nginx
server {
    listen 80;
    server_name example.com;

    if ($http_x_forwarded_proto = "http") {
        return 301 https://$host$request_uri;
    }
}
```

On a normal single server without a proxy in front, the simple version is correct - there is no loop because the HTTPS request is handled by the separate `listen 443 ssl` block.

**Redirecting to a hardcoded host.** Writing `return 301 https://example.com$request_uri` forces `www` visitors onto the bare domain (or the reverse). Use `$host` unless you deliberately want to merge them.

## FAQ

### Do I put this in the same file as the HTTPS block?

Usually yes - two `server` blocks in one site file: one on port 80 that redirects, one on port 443 that serves. nginx picks the right one by port.

### Certbot already added a redirect. Do I need this?

If you answered "redirect" to Certbot's prompt, it wrote a block like this for you. Knowing how it works means you can read and adjust it.

### Why 301 and not 302?

`301` is permanent, so clients cache it and stop hitting HTTP at all. Use `302` only for a temporary redirect you plan to remove. HTTPS is not temporary.

### How do I test the redirect without a browser?

Ask for the HTTP URL and read the headers: `curl -I http://example.com/some/path`. You want `HTTP/1.1 301 Moved Permanently` and a `Location:` line pointing at the matching `https://` path. Browsers cache 301s hard, so `curl` is the honest way to see what nginx actually returns.
