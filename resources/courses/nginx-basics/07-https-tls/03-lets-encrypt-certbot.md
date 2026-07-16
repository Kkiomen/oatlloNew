---
title: "Let's Encrypt with Certbot"
slug: lets-encrypt-certbot
seo_title: "Nginx Let's Encrypt: Free SSL with certbot --nginx"
seo_description: "Get a free HTTPS certificate for nginx with Let's Encrypt and Certbot. certbot --nginx, the HTTP-01 challenge, and automatic renewal with a systemd timer."
---

## Free certificates with Let's Encrypt

Certificates used to cost money and take a slow manual back-and-forth. That is over. For nginx, Let's Encrypt is a free, automated CA, and **Certbot** is the tool that talks to it, fetches your certificate, and edits it into your nginx config for you.

The catch: Let's Encrypt certs are only valid for 90 days. That is by design, and Certbot handles renewal automatically, so it is not a chore.

## Before you start

Two things must be true:

1. Your domain's DNS points to this server (an `A` record for `example.com`).
2. Port 80 **and** port 443 are open in the firewall.

```bash
sudo ufw allow 'Nginx Full'
```

`Nginx Full` opens both 80 and 443. Let's Encrypt needs port 80 to verify you, and browsers need 443 to reach the site.

## Install Certbot and run it

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d example.com -d www.example.com
```

The `--nginx` plugin does the heavy lifting:

1. It asks Let's Encrypt for a certificate for your domains.
2. It proves you control them (the challenge below).
3. It **edits your nginx config for you** - adds `listen 443 ssl`, the `ssl_certificate` lines, and an HTTP-to-HTTPS redirect.
4. It reloads nginx.

Certbot asks for an email (for expiry warnings) and whether to redirect HTTP to HTTPS - say yes. When it finishes, `https://example.com` works.

## The HTTP-01 challenge

How does Let's Encrypt know the domain is really yours? The **HTTP-01 challenge**:

1. Certbot asks for a certificate for `example.com`.
2. Let's Encrypt replies with a random token.
3. Certbot places that token at `http://example.com/.well-known/acme-challenge/<token>` on port 80.
4. Let's Encrypt fetches that URL over the public internet. If it finds the right token, it confirms you control the domain and issues the certificate.

This is why port 80 and correct DNS matter: the challenge is an HTTP request to your domain. The `--nginx` plugin sets up and cleans away that temporary path automatically.

## Automatic renewal

Certbot installs a renewal job when it runs - usually a **systemd timer** (or a cron entry on older systems). It runs twice a day, checks if any certificate is within 30 days of expiring, and renews only those. Confirm it is active:

```bash
sudo systemctl list-timers | grep certbot
```

Test that renewal actually works, without touching the real certificate:

```bash
sudo certbot renew --dry-run
```

`--dry-run` runs the full renewal against a staging server and throws the result away. If it prints success, real renewals will work too. Run this once after setup and you can forget about it.

## Common mistake

**Port 80 closed.** If you jumped ahead and only opened 443, the HTTP-01 challenge fails with a timeout or connection-refused error. The challenge is always over port 80. Open it.

**DNS not propagated yet.** If you just pointed the domain here, the challenge can fail because Let's Encrypt still sees the old IP. Wait for DNS to update and try again.

**Rate limits.** Let's Encrypt limits how many certs you can request per domain per week. While experimenting, add `--dry-run` or `--staging` so failed attempts don't burn your quota.

**`server_name` doesn't match `-d`.** The `--nginx` plugin finds the block to edit by matching your `-d example.com` against a `server_name` in the config. If your block still says `server_name _;` or a different host, Certbot can't locate it and either errors out or creates a fresh block you didn't expect. Set `server_name` to the real domain first.

## FAQ

### Do I need to configure ssl_certificate myself?

Not with `--nginx` - Certbot writes those lines for you. Knowing them from the [previous lesson](/course/nginx-basics/https-tls/listen-443-ssl) just means you understand what it changed.

### What happens when the cert expires?

It shouldn't, if the timer is running. Renewal happens ~30 days early. If the machine was off for a long time, run `sudo certbot renew` by hand.

### Is Let's Encrypt as good as a paid certificate?

For encryption, identical - browsers trust it the same way. Paid certs mainly add things like warranties or organization validation, which most sites don't need.
