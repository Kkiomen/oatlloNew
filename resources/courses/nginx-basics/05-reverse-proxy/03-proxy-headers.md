---
title: "Passing the real client info"
slug: proxy-headers
seo_title: "Nginx proxy_set_header: Host, X-Real-IP, X-Forwarded-For"
seo_description: "Forward the real client IP, host and protocol to your app with nginx proxy_set_header so logging, redirects and rate limits work behind nginx."
---

## Why your app sees nginx as the client

When nginx proxies a request, your app no longer talks to the browser. It talks to nginx, and `proxy_set_header` is how you hand the real details back to the app. From the app's point of view, without it:

- Every request comes from `127.0.0.1` (nginx), not the real visitor.
- The `Host` header may be nginx's idea of the host, not the domain the user typed.
- The app cannot tell if the original request was HTTP or HTTPS.

That breaks a lot of things: access logs show one IP for everyone, redirects point to the wrong host, "you are on HTTP, redirect to HTTPS" logic loops forever, and rate limiting by IP is useless.

The fix is to have nginx pass the real information along in request headers, using `proxy_set_header`.

## The standard proxy_set_header block

This block is on almost every [reverse proxy](/course/nginx-basics/reverse-proxy/what-is-a-reverse-proxy) in the world. Learn it as one unit:

```nginx
location / {
    proxy_pass http://127.0.0.1:3000;

    proxy_set_header Host              $host;
    proxy_set_header X-Real-IP         $remote_addr;
    proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

`proxy_set_header NAME VALUE` sets a header on the request nginx sends to the app. The values are nginx variables, filled in per request.

## What each line does

**`Host $host`** - passes the original hostname the visitor requested. `$host` is the domain from the request. Without this, an app that builds absolute URLs (redirects, links, canonical tags) can produce the wrong domain.

**`X-Real-IP $remote_addr`** - `$remote_addr` is the IP of whoever connected to nginx, i.e. the real visitor. This gives your app a single, clean "who is this" value.

**`X-Forwarded-For $proxy_add_x_forwarded_for`** - the standard chain-of-proxies header. This special variable takes any existing `X-Forwarded-For` and appends the current client IP. If there is a load balancer in front of nginx too, you get the whole path. Apps read the first entry as the original client.

**`X-Forwarded-Proto $scheme`** - `$scheme` is `http` or `https` as seen by nginx. Once nginx handles HTTPS (Chapter 7), the app itself only sees plain HTTP, so this header is how it learns the request was really HTTPS. Frameworks use it to build `https://` links and to avoid redirect loops.

## What breaks without these headers

Think about a redirect-to-HTTPS rule inside the app. If the app cannot tell the request was already HTTPS, it redirects to HTTPS again, and again - an infinite loop. `X-Forwarded-Proto` stops that.

Or think about logging and abuse protection. If every request looks like it came from `127.0.0.1`, you cannot ban anyone or count requests per user. `X-Real-IP` and `X-Forwarded-For` give you the real address.

## Reuse it everywhere

Because this block repeats on every proxy, people put it in its own file and include it. You already know includes from [includes and sites](/course/nginx-basics/configuration-basics/includes-and-sites):

```nginx
# /etc/nginx/snippets/proxy.conf
proxy_set_header Host              $host;
proxy_set_header X-Real-IP         $remote_addr;
proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Proto $scheme;
```

```nginx
location / {
    proxy_pass http://127.0.0.1:3000;
    include snippets/proxy.conf;
}
```

## Common mistake

Forgetting `proxy_set_header Host` is the classic one. nginx then sends the backend's own address as the host, and any absolute URL your app generates points at `127.0.0.1:3000` instead of your real domain. Logins that redirect, password-reset links and canonical URLs all break in confusing ways. If your app "works but redirects to the wrong address", check this header first.

Also remember: these headers are only trustworthy because *your* nginx sets them. Never blindly trust an incoming `X-Forwarded-For` from the public internet - a client can fake it. The `$proxy_add_x_forwarded_for` pattern is safe because your app should read the IP your own proxy added.

One inheritance rule surprises people: `proxy_set_header` directives do not add up across contexts. The moment you write a single `proxy_set_header` inside a `location`, nginx ignores every `proxy_set_header` you set in the parent `server` or `http` block for that location. So if you keep the shared block at `server` level and then add one extra header inside a location, you have quietly dropped the whole shared set. Repeat the full block (or include the snippet) wherever you add even one line.

## FAQ

### What is the difference between $host and $http_host?

`$host` is the hostname nginx settled on (from the request line or `Host` header, lowercased, port stripped) and always has a sensible value. `$http_host` is the raw `Host` header and can be empty. Prefer `$host`.

### Do I need X-Real-IP if I already send X-Forwarded-For?

Not strictly - `X-Forwarded-For` carries the client IP too. But `X-Real-IP` is a single clean value, and many app stacks read it by default, so sending both is common and harmless.

### My app still sees 127.0.0.1 as the client. Why?

The app must be told to trust the proxy headers. Frameworks have a "trusted proxies" setting; enable it so the app reads `X-Forwarded-For` instead of the socket address.
