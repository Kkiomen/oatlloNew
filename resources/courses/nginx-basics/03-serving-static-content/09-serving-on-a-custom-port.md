---
title: "Serving on a custom port"
slug: serving-on-a-custom-port
seo_title: "Nginx Listen on a Custom Port like 8080"
seo_description: "Make nginx serve a site on a custom port instead of 80, why the port shows in the URL, the firewall step, and how it differs from a reverse proxy."
---

Every site so far has used port 80, the default for `http://`. But nginx can listen on a custom port just as easily: 8080, 8000, 9000, anything free. This lesson shows how to set it with `listen`, why the port then shows up in the address bar, and how this differs from putting nginx in front of an app that runs on another port.

## Change the port with listen

You already met `listen` in [listen and server_name](/course/nginx-basics/serving-static-content/listen-and-server-name). To serve on a different port, just change the number:

```nginx
server {
    listen 8080;
    server_name example.com;
    root /var/www/example;
    index index.html;
}
```

After `nginx -t` and a reload, the site answers on port 8080 instead of 80.

## The port shows up in the URL

This is the part that surprises people. With `listen 8080`, the site is at:

```text
http://example.com:8080
```

You have to type `:8080`. Here is why. A browser only assumes a port when you leave it out: `:80` for `http://` and `:443` for `https://`. Any other port must be written in the address, or the browser goes to port 80 and finds nothing there.

So `http://example.com` (no port) still tries port 80. Only `http://example.com:8080` reaches your block.

## Listen on more than one port

A single server block can answer on several ports at once. Just list `listen` more than once:

```nginx
server {
    listen 80;
    listen 8080;
    server_name example.com;
    root /var/www/example;
    index index.html;
}
```

Now the same site responds on both `http://example.com` and `http://example.com:8080`.

## Open the port in the firewall

On a real server, changing the port is not enough on its own. If a firewall is running, it will block the new port until you allow it. nginx is listening, but the traffic never arrives.

```bash
sudo ufw allow 8080/tcp
```

If you skip this, the page simply times out from the outside even though `nginx -t` passed and the service is running. It is one of the most common "but it works locally" traps.

There is a matching gotcha in the other direction. Ports below 1024, including 80 itself, are privileged: only a process started with root privileges may bind them. High ports like 8080 are not privileged, so a quick test on 8080 can succeed while the same config on 80 fails to bind. If a low port refuses to open, check that nginx is being started the usual way (through its service) rather than by hand as a normal user.

## This is not the same as a reverse proxy

Here is the distinction that trips everyone up.

- `listen 8080` means **nginx itself** answers on port 8080. The port is part of the public URL.
- If instead you have an **app** running on a port (say a Node app on 3000) and you want visitors to reach it on plain port 80, you do **not** use `listen 3000`. You keep nginx on port 80 and forward requests to the app with `proxy_pass`, which is the [reverse proxy](/course/nginx-basics/reverse-proxy/what-is-a-reverse-proxy) chapter.

A quick way to remember it:

- **`listen PORT`** - the port **nginx** receives on (visible in the URL).
- **`proxy_pass http://127.0.0.1:PORT`** - the port of an **internal app** nginx forwards to (invisible to the visitor).

Wanting to "hide" an app that runs on 3000 by writing `listen 3000` in nginx is the classic mix-up: that tells *nginx* to listen on 3000 instead of forwarding to the app there. They are opposite things.

## Common mistake

Forgetting the port in the URL. After setting `listen 8080`, opening `http://example.com` (no port) hits port 80, where nothing is listening, so it fails. The site is fine - you just need `http://example.com:8080`. The second most common miss is the firewall: the port works on the server itself but not from another machine until you allow it.

## FAQ

### How do I run nginx on port 8080 instead of 80?

Set `listen 8080;` in the server block, run `nginx -t`, reload nginx, and open `http://yourdomain:8080`. On a server, also allow the port in the firewall with `sudo ufw allow 8080/tcp`.

### Why do I have to put the port in the address?

Browsers only assume a port for the standard schemes: 80 for `http://` and 443 for `https://`. Any other port is not assumed, so you must type it, like `:8080`.

### Can nginx listen on two ports at the same time?

Yes. Add more than one `listen` line to the same server block, for example `listen 80;` and `listen 8080;`, and the block answers on both.

### My app runs on port 3000 - should I use listen 3000?

No. Leave nginx on 80 (or 443) and forward to the app with `proxy_pass http://127.0.0.1:3000;`. `listen` sets the port nginx receives on; `proxy_pass` sets the app port it forwards to. See the [reverse proxy](/course/nginx-basics/reverse-proxy/what-is-a-reverse-proxy) chapter.
