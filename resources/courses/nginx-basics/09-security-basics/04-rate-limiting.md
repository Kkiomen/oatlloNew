---
title: "Rate limiting requests"
slug: rate-limiting
seo_title: "nginx Rate Limiting With limit_req_zone and burst"
seo_description: "Set up nginx rate limiting with limit_req_zone and limit_req to shield login and API endpoints from floods. How burst, nodelay and limit_conn work."
---

## Why rate limit nginx requests

A login form or API endpoint can be hammered: thousands of requests a second guessing passwords or scraping data. Your app slows down or falls over. nginx rate limiting caps how often a single client hits a path, dropping the excess before it reaches your code.

## Define a zone, then apply it

Rate limiting has two parts. First define a shared memory **zone** in the `http` context. Then apply it with `limit_req` where you want the cap.

```nginx
http {
    # 10 MB of memory, max 10 requests per second per IP
    limit_req_zone $binary_remote_addr zone=login:10m rate=10r/s;
}
```

- `$binary_remote_addr` keys the limit by client IP (the compact form, so it uses less memory).
- `zone=login:10m` names the zone `login` and gives it 10 MB, enough to track many thousands of IPs.
- `rate=10r/s` is the steady limit: 10 requests per second per IP. You can also use `r/m` for slower paths.

Apply it to a location:

```nginx
location /login {
    limit_req zone=login burst=20 nodelay;

    # ... proxy_pass or fastcgi_pass to your app
}
```

## burst and nodelay

A strict `10r/s` is harsh, because real traffic comes in bursts. Those two extra words soften it:

- `burst=20` allows a queue of up to 20 extra requests above the rate. They wait their turn instead of being rejected immediately.
- `nodelay` serves those queued requests **right away** instead of spacing them out, while still counting them against the limit. Good for interactive traffic, so users are not slowed for no reason.

Without `nodelay`, bursts are trickled out one per rate slot, which feels sluggish. With it, short spikes go through fast and only sustained floods get blocked. Rejected requests get `503 Service Unavailable` by default.

Here is the part that trips people up: `rate=10r/s` is not a per-second bucket. nginx converts it to one request every 100ms, so two requests 50ms apart already exceed it even though you sent far fewer than ten in that second. That millisecond spacing is exactly why `burst` exists, and why a bare `rate` with no burst rejects normal-looking traffic.

## Limiting connections too

`limit_req` caps request rate. A related directive, `limit_conn`, caps the number of **simultaneous** connections per client, which helps against slow-drip attacks that hold many connections open:

```nginx
http {
    limit_conn_zone $binary_remote_addr zone=perip:10m;
}

server {
    limit_conn perip 10;   # max 10 open connections per IP
}
```

## Common mistake

Setting the rate too low. If `rate=1r/s` covers a whole site, a normal page that loads a dozen assets will trip the limit and users see `503` errors. Rate limit **specific** paths (`/login`, `/api`), not everything, and test with real traffic before trusting a number. Also remember the zone must be defined in `http` before any `limit_req` that uses it, or `nginx -t` fails.

## FAQ

### What status code do blocked requests get?

`503` by default. You can change it with `limit_req_status 429;` to return the more accurate "Too Many Requests".

### Does the zone size need tuning?

`10m` tracks roughly 160,000 IPs, plenty for most sites. Only raise it if logs show the zone is full.

### Behind a proxy or CDN, what does it limit by?

By the connecting IP, which may be the proxy. As in the [access control lesson](/course/nginx-basics/security-basics/access-control), you need the real client IP passed through for per-user limits to work correctly.
