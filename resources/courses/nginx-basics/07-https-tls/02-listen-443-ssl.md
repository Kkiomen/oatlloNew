---
title: "listen 443 ssl"
slug: listen-443-ssl
seo_title: "Nginx listen 443 ssl: Enable HTTPS Step by Step"
seo_description: "Enable HTTPS in nginx with listen 443 ssl, ssl_certificate and ssl_certificate_key. See what the cert and private key files are, then test and reload."
---

## What nginx needs to serve HTTPS

Two ingredients turn a plain nginx site into an HTTPS one: a listener on the HTTPS port, and a pointer to the certificate files. You already have a certificate, from the next lesson or bought elsewhere. This lesson wires it in with `listen 443 ssl` and the two `ssl_certificate` directives.

## The two files

A TLS setup is always two files:

- **The certificate** - the public part, signed by the CA, safe to share. Often ends in `.pem` or `.crt`. With Let's Encrypt this is `fullchain.pem` (your cert plus the CA chain).
- **The private key** - the secret half. It must stay on the server and readable only by root. Often `.key` or `privkey.pem`.

They are a matched pair. The certificate says "here is my public key"; the private key is the matching secret that proves the server really owns it. If the key leaks, the certificate is worthless and must be replaced.

## A minimal HTTPS server block

```nginx
server {
    listen 443 ssl;
    server_name example.com;

    ssl_certificate     /etc/nginx/ssl/example.com/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/example.com/privkey.pem;

    root /var/www/example.com/public;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

Each new part:

- **`listen 443 ssl`** - listen on port 443 (the HTTPS port) and treat connections as TLS. Port 80 is plain HTTP; 443 is HTTPS. The `ssl` flag is what turns encryption on for this listener.
- **`ssl_certificate`** - full path to the certificate file (public, includes the chain).
- **`ssl_certificate_key`** - full path to the private key file (secret).

Everything below that - `root`, `index`, `location`, `try_files` - is the same static-site config you already know from [root and index](/course/nginx-basics/serving-static-content/root-and-index). HTTPS only changes how the connection is protected, not how you serve content.

## Test and reload

Never reload blind. Check the config first, then apply it:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

`nginx -t` catches a bad path or typo before it can take the site down. `reload` applies the change without dropping existing connections. This is the same safe workflow from [testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely).

## Common mistake

**Wrong certificate path.** A single typo in `ssl_certificate` makes `nginx -t` fail with `cannot load certificate ... No such file or directory`. Copy the paths, don't type them, and confirm the files exist:

```bash
sudo ls -l /etc/nginx/ssl/example.com/
```

**Swapping the two files.** The cert goes in `ssl_certificate`, the key in `ssl_certificate_key`. Reversed, nginx refuses to start. If you see `key values mismatch`, the cert and key are not a pair.

**Forgetting to open port 443.** If the config is fine but the browser times out, the server firewall is probably still blocking 443. More on that in the [Certbot lesson](/course/nginx-basics/https-tls/lets-encrypt-certbot).

**Private key unreadable.** nginx loads the key with its master process, which starts as root, so a key locked down to `root:root` with mode `600` is correct - the worker running as `www-data` never touches it. Locking it down is right; just make sure root can still read the path, or `nginx -t` fails with a permission error.

## FAQ

### Do I still need a port 80 block?

Yes - to redirect HTTP visitors to HTTPS. That is the [next-but-one lesson](/course/nginx-basics/https-tls/redirect-http-to-https). Without it, `http://example.com` just fails to load.

### What is fullchain vs the plain cert?

`fullchain.pem` is your certificate plus the CA's intermediate certificate. Always use the fullchain - with only your own cert, some clients can't verify the trust path and show errors.

### Can one server block serve several domains over HTTPS?

Each domain generally needs its own certificate and its own `server` block. nginx picks the right one by `server_name` using SNI, which every modern browser sends.
