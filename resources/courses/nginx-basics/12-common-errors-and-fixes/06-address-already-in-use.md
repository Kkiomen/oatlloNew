---
title: "Nginx bind() to 0.0.0.0:80 failed (98: Address already in use)"
slug: address-already-in-use
seo_title: "Nginx Address Already in Use (98) on Port 80 - Fix"
seo_description: "Fix nginx bind() to 0.0.0.0:80 failed (98: Address already in use): find the service holding port 80 or the duplicate default_server, then reload."
---

## What the error looks like

Nginx refuses to start, and `nginx -t` or the service log shows:

```text
nginx: [emerg] bind() to 0.0.0.0:80 failed (98: Address already in use)
nginx: [emerg] still could not bind()
```

## Why it happens

Nginx tries to listen on port 80, but something already holds it. Two common cases:

- **Another service owns port 80.** Very often it is Apache, installed by default on many boxes and started automatically. Only one program can listen on a port at a time.
- **Two nginx blocks both claim it.** Two `server` blocks both marked `listen 80 default_server`, so nginx conflicts with itself.

## How to fix it

1. Find what is holding port 80:

```bash
sudo ss -ltnp | grep :80
```

The output names the process, for example `users:(("apache2",pid=812,...))`.

2. If it is another service like Apache, stop and disable it so it does not come back on reboot:

```bash
sudo systemctl stop apache2
sudo systemctl disable apache2
```

3. If it is a leftover nginx process, stop it fully before starting again:

```bash
sudo systemctl stop nginx
sudo systemctl start nginx
```

4. If the conflict is a duplicate `default_server`, only one block per port may have it. Fix the extra one. See [testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely) before you reload:

```nginx
server {
    listen 80 default_server;
    server_name _;
}

server {
    listen 80;
    server_name example.com;
}
```

5. Test and start:

```bash
sudo nginx -t && sudo systemctl restart nginx
```

## Common mistake

Running `nginx` by hand while the service is already running. The systemd nginx already holds port 80, and your manual copy cannot bind it. Manage nginx through `systemctl`, not by launching a second copy.

A small detail that sends people in circles: `ss -ltnp` only prints the owning process when you run it as root. Without `sudo`, you see the port is taken but the process column is blank, so it looks like nothing holds it. Always run the check with `sudo`, or you are diagnosing half-blind.

## FAQ

### How do I know it is Apache and not nginx?

`sudo ss -ltnp | grep :80` prints the process name. If it says `apache2` or `httpd`, Apache has the port. If it says `nginx`, an nginx process is already running.

### What does default_server mean here?

`default_server` marks the block that handles requests not matched by any `server_name` on that port. Only one block per port may be the default. Two of them cause this same emerg error.

### Same error but on port 443?

Same cause, different port. Something already listens on 443. Run `sudo ss -ltnp | grep :443` and stop the conflicting service or remove the duplicate `listen 443` default.
