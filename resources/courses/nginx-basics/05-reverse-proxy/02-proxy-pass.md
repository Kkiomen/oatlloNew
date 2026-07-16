---
title: "proxy_pass"
slug: proxy-pass
seo_title: "Nginx proxy_pass: Forward Requests to a Local Port"
seo_description: "Use nginx proxy_pass to forward requests to a Node or other app on a local port, and master the trailing-slash rule that trips people up."
---

## What the proxy_pass directive does

Forwarding a request to your app comes down to a single directive: `proxy_pass`. You put nginx `proxy_pass` inside a `location`, and every request that matches that location gets sent to the address you name.

```nginx
server {
    listen 80;
    server_name app.example.com;

    location / {
        proxy_pass http://127.0.0.1:3000;
    }
}
```

Say your Node app is running on port 3000. With this config, a browser request to `http://app.example.com/` is forwarded to `http://127.0.0.1:3000/`. The app answers, nginx relays the answer back.

## Reading the proxy_pass address

`http://127.0.0.1:3000` has three parts:

- **`http://`** - nginx talks to the app over plain HTTP. The app does not need HTTPS; nginx handles the public side.
- **`127.0.0.1`** - localhost. The app runs on the same machine. You could also proxy to another host, like `http://10.0.0.5:3000`.
- **`:3000`** - the port your app listens on. Match this to whatever your app prints on startup.

You can use a hostname too: `proxy_pass http://localhost:3000;`. An IP is one fewer lookup, so many people prefer `127.0.0.1`.

## How do I proxy only some paths?

You do not have to proxy everything. A common pattern is to serve static files from disk and send only `/api` to the app:

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/site;

    location / {
        try_files $uri $uri/ =404;
    }

    location /api/ {
        proxy_pass http://127.0.0.1:3000;
    }
}
```

Now `/style.css` is read from disk (using [try_files](/course/nginx-basics/location-matching/try-files)), while `/api/users` goes to the app.

## The trailing-slash gotcha

This is the part that catches everyone. Whether you put a trailing slash on the `proxy_pass` URL changes the path the app receives.

**Without a trailing slash on `proxy_pass`**, nginx forwards the full original path:

```nginx
location /api/ {
    proxy_pass http://127.0.0.1:3000;
}
```

Request `/api/users` -> app receives `/api/users`.

**With a trailing slash on `proxy_pass`**, nginx strips the location prefix and replaces it:

```nginx
location /api/ {
    proxy_pass http://127.0.0.1:3000/;
}
```

Request `/api/users` -> app receives `/users`. The `/api/` part is cut off.

So the trailing slash means "strip the matched prefix". Use it when your app does not know about the `/api` prefix and expects paths to start at the root. Leave it off when the app expects the full path.

Worth knowing before it bites you: the moment you add a path after the port, like `proxy_pass http://127.0.0.1:3000/;`, that path counts as present even when it is just `/`. That is why a lone slash flips the behaviour. A bare `http://127.0.0.1:3000` with no path at all is the "forward everything untouched" form.

## Common mistake

The most common bug is a mismatched trailing slash giving you a wrong path: you proxy `/api/` to `http://127.0.0.1:3000/` (with slash), and suddenly your app gets `/users` instead of `/api/users` and returns 404. If your proxied routes 404 but the app works when you hit it directly, check this slash first.

A second mistake: forgetting to actually start the app. If nginx returns `502 Bad Gateway`, nginx is fine but nothing is listening on that port. Start the app and confirm with `curl`.

```bash
curl http://127.0.0.1:3000/
```

## FAQ

### What does 502 Bad Gateway mean?

nginx tried to reach your app and could not. Usually the app is not running, or it is on a different port than the one in `proxy_pass`.

### Can I proxy to an HTTPS backend?

Yes: `proxy_pass https://...`. On the same machine it is rarely worth it - keep the app on HTTP and let nginx handle public HTTPS (Chapter 7).

### Do I need proxy_set_header here?

For a request to arrive, no. But without extra headers your app sees nginx as the client, not the real visitor. That is the next lesson: [proxy headers](/course/nginx-basics/reverse-proxy/proxy-headers).

### Should proxy_pass point at an IP or a hostname?

Either works. An IP like `127.0.0.1` skips a name lookup, which is why many people use it for a local app. A hostname is fine too and reads more clearly when the backend lives on another machine.
