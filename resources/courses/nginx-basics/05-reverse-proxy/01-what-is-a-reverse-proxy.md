---
title: "What is a reverse proxy"
slug: what-is-a-reverse-proxy
seo_title: "Nginx Reverse Proxy Explained: How It Works"
seo_description: "What an nginx reverse proxy is, how a request flows from browser to nginx to your app, and why you put nginx in front of an application."
---

## Why your app server is a poor front door

Your application server can already speak HTTP. A Node app, a Python app, a Go binary - they all start a web server and listen on a port like 3000 or 8000. So why add nginx at all?

A raw app server is not built to face the internet. It usually listens on a high port, serves one app, has no TLS, and is not tuned for slow clients or for serving files. You want something small and hardened out front, with your app tucked behind it. That something is nginx acting as an **nginx reverse proxy**.

## What is a reverse proxy?

A reverse proxy is a server that receives requests from clients and forwards them to another server, then passes the response back.

The word "reverse" is just to tell it apart from a normal (forward) proxy. A forward proxy sits in front of *clients* and hides them. A reverse proxy sits in front of *servers* and hides them. The browser thinks it is talking to nginx. It never sees your app directly.

## How a request flows through nginx

```text
Browser  ->  nginx (port 80/443)  ->  your app (127.0.0.1:3000)
Browser  <-  nginx                <-  your app
```

Step by step:

1. The browser connects to your domain on port 80 (or 443).
2. nginx accepts the connection.
3. nginx opens its own connection to your app on `127.0.0.1:3000` and repeats the request.
4. The app builds a response and hands it back to nginx.
5. nginx sends that response to the browser.

The app listens on `127.0.0.1` (localhost) on purpose. That address is not reachable from the outside, so the only way in is through nginx.

One thing that trips people up early: nginx opens a *fresh* connection to the app. The browser's connection ends at nginx. So the app never sees the visitor's IP or the original hostname unless you pass them along by hand, which is exactly what the [proxy headers](/course/nginx-basics/reverse-proxy/proxy-headers) lesson is about.

## Why put nginx in front of your app

- **One public entry point.** Port 80 and 443 are handled by nginx. Your app can listen on any local port.
- **TLS/HTTPS in one place.** nginx terminates HTTPS so your app can stay plain HTTP (covered in Chapter 7).
- **Serve static files fast.** nginx serves images, CSS and JS straight from disk and only proxies the dynamic requests.
- **Many apps, one server.** Different `server_name` values or `location` blocks can route to different backends.
- **Buffering and safety.** nginx shields your app from slow connections and can add limits, caching and compression later.

## A minimal reverse proxy config

You already know `server` and `location` blocks from [location basics](/course/nginx-basics/serving-static-content/location-basics). A reverse proxy config looks the same, except the location forwards to an app instead of reading a file:

```nginx
server {
    listen 80;
    server_name app.example.com;

    location / {
        proxy_pass http://127.0.0.1:3000;
    }
}
```

That one `proxy_pass` line is the whole idea. The next lesson takes it apart.

## FAQ

### Is a reverse proxy the same as a load balancer?

Not exactly. A load balancer spreads traffic across several backends. A reverse proxy forwards to a backend. nginx can do both, and a load balancer is just a reverse proxy with more than one server behind it. You will see that in [load balancing](/course/nginx-basics/reverse-proxy/load-balancing).

### Does my app still need its own web server?

Yes. nginx does not run your code. Your app keeps listening on its port. nginx just forwards to it.

### Can nginx serve static files and proxy at the same time?

Yes, and that is the common setup. One `location` serves files, another proxies to the app. You match them with the rules from [how location matching works](/course/nginx-basics/location-matching/how-location-matching-works).

### What is the difference between a forward proxy and a reverse proxy?

A forward proxy sits in front of clients and hides who is making the request. A reverse proxy sits in front of servers and hides which backend answered. Same relay idea, opposite side of the conversation.
