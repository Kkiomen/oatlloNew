---
title: "HTTPS with Let's Encrypt"
slug: https-with-lets-encrypt
seo_title: "Nginx Let's Encrypt Certbot: Free HTTPS in 5 Steps"
seo_description: "Set up free HTTPS on nginx with Let's Encrypt and certbot: install the nginx plugin, open ports 80 and 443, run certbot --nginx, and confirm auto-renewal."
---

## The problem

Your app is live on `http://yourdomain.com`, unencrypted. Browsers flag it "Not secure", passwords and cookies travel in the clear, and modern browser features refuse to run without TLS. The fix is HTTPS, and with **Let's Encrypt** and **certbot** on nginx it costs nothing.

The deep dive on how TLS certificates work lives in Chapter 7's [Let's Encrypt with Certbot](/course/nginx-basics/https-tls/lets-encrypt-certbot). This lesson is the practical, in-context version: take the site you just deployed and put it on HTTPS in a few commands.

The one thing that must already be true: **DNS points at this server** (from [point a domain at your VPS](/course/nginx-basics/deploying-to-a-vps/point-a-domain-at-your-vps)). Let's Encrypt proves you control the domain by fetching a file over HTTP, so the domain has to resolve to this box first.

One more thing to check before you start. Your `server_name` must already list the exact domains you plan to certify. Certbot reads those names straight out of the nginx block to know what to request, so if it still says `myapp.test`, fix that first and reload.

## Step 1: Install certbot and the nginx plugin

Certbot is the tool that gets and installs the certificate. The nginx plugin lets it edit your config for you:

```bash
apt install -y certbot python3-certbot-nginx
```

## Step 2: Open ports 80 and 443

If you use the `ufw` firewall, it must allow HTTP (port 80) **and** HTTPS (port 443). Certbot needs port 80 for the challenge, and your visitors need 443 for the encrypted site.

```bash
ufw allow 'Nginx Full'
```

`Nginx Full` is a shortcut profile that opens both 80 and 443 at once. Check what is allowed:

```bash
ufw status
```

If your VPS provider also has a separate cloud firewall in its dashboard, open 80 and 443 there too, or the challenge still fails.

## Step 3: Run certbot

Point certbot at your site by naming every domain the certificate should cover:

```bash
certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

- `--nginx` tells certbot to use the nginx plugin: it reads your server block, gets the certificate, and edits the config automatically.
- Each `-d` is one domain name. List the same names you put in `server_name`, both the bare domain and `www`.

Certbot asks for an email (for expiry warnings) and to accept the terms. Then it does the [HTTP-01 challenge](/course/nginx-basics/https-tls/lets-encrypt-certbot): it places a file on your server and Let's Encrypt fetches it over port 80 to confirm you control the domain. On success you get a certificate.

One trap worth naming here. Let's Encrypt enforces a rate limit of five failed validations per hostname per hour. If your first run fails on a closed port or bad DNS and you keep re-running it, you can lock yourself out for the rest of the hour. Fix the actual cause once, confirm it, then run certbot again rather than retrying blindly.

## Step 4: What certbot changed for you

You do not have to hand-write any SSL config. When certbot finishes, it has rewritten your server block to:

- Add a new `listen 443 ssl;` block with the certificate paths filled in (this is the [listen 443 ssl](/course/nginx-basics/https-tls/listen-443-ssl) block, written for you).
- Add a **redirect from HTTP to HTTPS**, so anyone visiting `http://` is sent to `https://` automatically. That is the [redirect http to https](/course/nginx-basics/https-tls/redirect-http-to-https) pattern, inserted into your port 80 block.

Certbot reloads nginx itself, so the change is already live.

## Step 5: Verify HTTPS works

Open `https://yourdomain.com` in a browser. You should see the padlock and no warning. From the command line:

```bash
curl -I https://yourdomain.com
```

A `200 OK` confirms TLS is working. Check the redirect too, an `http://` request should answer with `301` to the `https://` address:

```bash
curl -I http://yourdomain.com
```

## Step 6: Confirm auto-renewal

Let's Encrypt certificates last 90 days. Certbot installs a systemd timer that renews them automatically before they expire, so you never touch this again. Confirm the timer is active:

```bash
systemctl status certbot.timer
```

You want to see `active (waiting)`. Then do a **dry run** to prove renewal actually works, without using up any rate limits:

```bash
certbot renew --dry-run
```

If it reports success, renewal is wired up correctly and your certificate will refresh on its own.

## Common mistake: running certbot before DNS points at the server

This is the number-one failure. If `yourdomain.com` does not yet resolve to this server, the HTTP-01 challenge cannot reach it and certbot fails with a "challenge failed" or "connection refused" error.

Confirm DNS first, exactly as in the previous lesson:

```bash
dig +short yourdomain.com
```

The IP must be your server's IP. Only then run certbot. If DNS is fine but the challenge still fails, it is almost always a **closed port 80** (see below).

## Common mistake: port 443 (or 80) closed

Certbot needs **80** open for the challenge, and visitors need **443** open for the site. If you opened only one, or forgot the firewall entirely, you get a failed challenge or a working certificate that nobody can reach on `https://`.

Fix both at once:

```bash
ufw allow 'Nginx Full'
```

And remember the cloud provider's dashboard firewall, if it has one, needs the same two ports open.

## FAQ

### Do I need to edit the nginx config myself?

No. The `--nginx` plugin edits your server block for you: it adds the `443` block, the certificate lines, and the HTTP-to-HTTPS redirect, then reloads nginx. If you prefer to understand or tweak every line by hand, Chapter 7's [listen 443 ssl](/course/nginx-basics/https-tls/listen-443-ssl) and [redirect http to https](/course/nginx-basics/https-tls/redirect-http-to-https) show the manual version.

### How often does the certificate renew?

The timer checks twice a day and renews when a certificate is within 30 days of expiry, so a 90-day certificate refreshes with plenty of margin. You do nothing. `certbot renew --dry-run` is how you prove it works without waiting or spending rate limit.

### Can I add more domains later?

Yes. Re-run certbot with all the `-d` flags you want covered, including the ones already on the certificate. Certbot replaces the old certificate with a new one covering the full list.

### Should I harden the TLS settings?

The defaults certbot writes are already solid for most sites. When you want to go further (protocols, ciphers, HSTS), see [basic TLS hardening](/course/nginx-basics/https-tls/basic-tls-hardening) and the [security headers](/course/nginx-basics/security-basics/security-headers) lesson.

### Certbot failed but my DNS and ports look fine. What now?

Add `--dry-run` and re-read the error, or point certbot at the staging server with `--test-cert` while you debug. Both use Let's Encrypt's staging endpoint, which has generous limits, so you can retry as often as you like without burning the production rate limit. Once a staging run succeeds, drop the flag and request the real certificate.
