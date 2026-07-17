---
title: "Proxy caching"
slug: proxy-caching
seo_title: "Nginx Proxy Cache: proxy_cache_path and proxy_cache_valid"
seo_description: "Set up nginx proxy caching with proxy_cache_path and proxy_cache. Cache backend responses, tune proxy_cache_valid, and read the X-Cache-Status header."
---

In [reverse proxy](/course/nginx-basics/reverse-proxy/what-is-a-reverse-proxy) you sent requests to a backend app. Nginx proxy caching stores that backend's response and serves the next visitor straight from disk, so the backend is not touched at all. When a page is slow or does heavy work but rarely changes, this turns it into an instant one.

## Defining the proxy cache with proxy_cache_path

First tell nginx where to store cached responses. This goes in the `http` block, because it sets up shared memory used across sites:

```nginx
http {
    proxy_cache_path /var/cache/nginx
        keys_zone=app_cache:10m
        max_size=1g
        inactive=60m;
}
```

- `/var/cache/nginx` is the folder on disk where cached responses live.
- `keys_zone=app_cache:10m` names this cache `app_cache` and reserves 10 MB of memory for its keys. 10 MB holds roughly 80,000 entries.
- `max_size=1g` caps the cache at 1 GB on disk. Nginx removes the least recently used items when it fills up.
- `inactive=60m` drops anything not requested for 60 minutes, even if there is room.

## Using the cache

Now switch it on [where you proxy to the backend](/course/nginx-basics/reverse-proxy/proxy-pass):

```nginx
server {
    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_cache app_cache;
        proxy_cache_valid 200 10m;
        proxy_cache_valid 404 1m;
        add_header X-Cache-Status $upstream_cache_status;
    }
}
```

- `proxy_cache app_cache;` uses the cache zone you defined.
- `proxy_cache_valid 200 10m;` caches successful responses for 10 minutes. During that window the backend is skipped.
- `proxy_cache_valid 404 1m;` caches "not found" briefly, so a flood of bad URLs cannot hammer the backend.

## The cache key

Nginx has to know when two requests are "the same". It builds a key, and by default that key is the scheme, host, and full URI. So `GET /pricing` and `GET /about` are separate entries, which is what you want. You can set it yourself:

```nginx
proxy_cache_key "$scheme$host$request_uri";
```

The default is usually fine. Just be aware the query string is part of the URI, so `/search?q=cats` and `/search?q=dogs` cache separately.

## Watching it work with X-Cache-Status

The `add_header X-Cache-Status $upstream_cache_status;` line above exposes what nginx did:

```bash
curl -I https://example.com/pricing
```

- `MISS` means it was not cached, so nginx asked the backend and stored the result.
- `HIT` means it was served from cache. The backend was never called.
- `EXPIRED` means the cached copy was too old, so nginx refreshed it.

Request the same URL twice. The first is `MISS`, the second should be `HIT`.

## Common mistake: caching pages you should not

Never cache logged-in or personalized pages by URL alone. If `/account` is cached, the first user's account page gets served to everyone else. Only cache responses that are the same for all visitors, such as marketing pages, blog posts, or public API results. For anything user-specific, do not enable `proxy_cache` on that location.

There is a quieter safeguard worth knowing. By default nginx will not cache a response that carries a `Set-Cookie` header, because that cookie is usually a session tied to one user. So a login response that sets a session cookie is skipped on its own, which is helpful. Do not lean on it, though: a page can still be personalized without setting a cookie on that exact request, and then it would cache. Judge by "is this the same for everyone", not by whether you spot a cookie.

Also watch cache folder permissions. Nginx worker processes must be able to write to `/var/cache/nginx`. If they cannot, nothing caches and you will see only `MISS`. On most installs nginx creates and owns this folder already.

## FAQ

### Does nginx cache POST requests?

No, only `GET` and `HEAD` by default. POST usually changes data, so caching it would be wrong.

### How do I clear the cache?

The simple way is to stop nginx, delete the files under the cache folder, and start again. Purging single URLs needs a commercial module, so most people just use short `proxy_cache_valid` times instead.

### The backend sends Cache-Control: no-cache. What happens?

Nginx respects that and skips caching for that response. Your `proxy_cache_valid` only applies when the backend does not forbid caching.
