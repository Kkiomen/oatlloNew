---
title: "A full static site with HTTPS"
slug: full-static-site
seo_title: "Complete Nginx Static Site Config with HTTPS"
seo_description: "A complete, annotated nginx server block for a static site: HTTP to HTTPS redirect, root, gzip, asset caching, and a custom 404. Every line explained."
---

## What a full nginx static site config needs

You have a folder of HTML, CSS, images, maybe some JavaScript, and a domain you own. The goal: live on `https://example.com`, fast, with a real padlock and a 404 page that matches your design.

Every part of this nginx static site config was taught in an earlier chapter. This lesson bolts the pieces into one file you can copy, then walks through every line so nothing stays magic.

## The full config

This is the whole thing. Two `server` blocks: one to bounce plain HTTP to HTTPS, one to serve the site.

```nginx
# Redirect all HTTP traffic to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name example.com www.example.com;

    return 301 https://$host$request_uri;
}

# The real site, over HTTPS
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name example.com www.example.com;

    root /var/www/example.com/public;
    index index.html;

    # TLS certificate (from certbot)
    ssl_certificate     /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;

    # Compress text responses
    gzip on;
    gzip_types text/css application/javascript image/svg+xml application/json;

    # Serve the file, or fall back to a 404
    location / {
        try_files $uri $uri/ =404;
    }

    # Cache static assets hard
    location ~* \.(css|js|jpg|jpeg|png|gif|svg|woff2|ico)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Custom 404 page
    error_page 404 /404.html;
    location = /404.html {
        internal;
    }
}
```

## The redirect server block

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name example.com www.example.com;

    return 301 https://$host$request_uri;
}
```

This block exists only to move visitors off plain HTTP. `listen 80` is the HTTP port, and `listen [::]:80` is the same for IPv6, both from [listen and server_name](/course/nginx-basics/serving-static-content/listen-and-server-name). `server_name` matches both the bare domain and the `www` version.

The single `return 301` sends a permanent redirect to the HTTPS version of whatever was requested. `$host` is the domain the browser asked for and `$request_uri` is the full path plus query string, so `/blog?page=2` survives the jump. This is exactly the pattern from [redirect HTTP to HTTPS](/course/nginx-basics/https-tls/redirect-http-to-https), and using `return` instead of `rewrite` is the clean approach from [redirects with return](/course/nginx-basics/location-matching/redirects-with-return).

## Opening the HTTPS listener

```nginx
listen 443 ssl;
listen [::]:443 ssl;
http2 on;
server_name example.com www.example.com;
```

Port 443 is HTTPS, and the `ssl` flag tells nginx to expect a TLS handshake here, covered in [listen 443 ssl](/course/nginx-basics/https-tls/listen-443-ssl). `http2 on` turns on HTTP/2, which lets the browser fetch many assets over one connection. The `server_name` matches the same two hostnames, so both resolve to this site.

`http2 on;` on its own line is the newer syntax (nginx 1.25 and up). Older tutorials write `listen 443 ssl http2;`, which still works but is deprecated. If your nginx is older than 1.25, use the old form or the config will not parse.

## root and index

```nginx
root /var/www/example.com/public;
index index.html;
```

`root` is the folder on disk that holds your files, and `index` is the file to serve when someone asks for a directory like `/`. Both are from [root and index](/course/nginx-basics/serving-static-content/root-and-index). A request for `/` serves `/var/www/example.com/public/index.html`.

## The certificate

```nginx
ssl_certificate     /etc/letsencrypt/live/example.com/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;
ssl_protocols       TLSv1.2 TLSv1.3;
```

These three lines are the padlock. `ssl_certificate` is the public certificate chain and `ssl_certificate_key` is the private key, both written by certbot when you ran it in [Let's Encrypt with certbot](/course/nginx-basics/https-tls/lets-encrypt-certbot). `ssl_protocols` limits the connection to modern TLS versions, the first step in [basic TLS hardening](/course/nginx-basics/https-tls/basic-tls-hardening).

One thing that trips people up: if you run certbot with the `--nginx` plugin, it edits this file for you and often adds its own port-80 redirect block. When you also hand-write the redirect above, you end up with two. Nginx will warn about a conflicting `server_name`, and only one block wins. Pick one source of the redirect, not both.

## gzip compression

```nginx
gzip on;
gzip_types text/css application/javascript image/svg+xml application/json;
```

`gzip on` compresses responses before sending them, so CSS and JS travel smaller and pages feel faster. `gzip_types` lists which content types to compress. HTML is always compressed, so it is not listed. Images like JPG and PNG are already compressed, so squeezing them again wastes CPU for nothing. This is straight from [gzip compression](/course/nginx-basics/performance-and-caching/gzip-compression).

## Serving files with try_files

```nginx
location / {
    try_files $uri $uri/ =404;
}
```

This is the heart of a static site. For any request, `try_files` checks each option in order:

- `$uri` looks for a file at that exact path, so `/about.html` serves that file.
- `$uri/` looks for a directory and uses its `index` file.
- `=404` gives up and returns a 404 if neither exists.

`try_files` was taught in [try_files](/course/nginx-basics/location-matching/try-files). Without the `=404` fallback, a missing file would produce nginx's default error handling; with it, you get a clean 404 that your custom page can catch below.

## Caching static assets

```nginx
location ~* \.(css|js|jpg|jpeg|png|gif|svg|woff2|ico)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
}
```

The `~*` is a case-insensitive regex match from [how location matching works](/course/nginx-basics/location-matching/how-location-matching-works), and the pattern catches common asset extensions. For those files:

- `expires 1y` tells browsers to keep the file for a year without asking again.
- `Cache-Control "public, immutable"` says the file will never change under this name, so the browser should not even revalidate it. This is the fingerprinted-asset pattern from [caching static assets](/course/nginx-basics/performance-and-caching/caching-static-assets).
- `access_log off` stops logging every image hit, from [access and error logs](/course/nginx-basics/configuration-basics/access-and-error-logs), keeping your log readable.

If you edit a cached file, change its name (for example `app.9f2c.css`) so browsers see a new URL. That is why bundlers add hashes.

## The custom 404 page

```nginx
error_page 404 /404.html;
location = /404.html {
    internal;
}
```

`error_page 404 /404.html` tells nginx to serve `/404.html` whenever a 404 happens, the technique from [custom error pages](/course/nginx-basics/serving-static-content/custom-error-pages). The `location = /404.html` block uses an exact match (from [matching priority](/course/nginx-basics/location-matching/matching-priority)), and `internal` means the page can only be reached through an error, not typed directly into the browser. Put your styled 404 file at `/var/www/example.com/public/404.html`.

## Testing and reloading

Never trust a config until nginx has parsed it. From [testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely):

```bash
sudo nginx -t
sudo systemctl reload nginx
```

`nginx -t` checks the syntax without touching the running server. Only reload if it says `test is successful`, using the reload from [start, stop, reload](/course/nginx-basics/getting-started/start-stop-reload).

## FAQ

### Do I need the www version if I only use the bare domain?

If you want `www.example.com` to work at all, keep it in `server_name`. Many people type `www` out of habit. If you truly do not want it, drop it, but then a `www` visitor gets an error instead of your site.

### Why cache assets for a whole year?

Because a fingerprinted file (name includes a hash) never changes. A year is effectively "forever," and it means repeat visitors download nothing on their second visit. When the content changes, the filename changes, so there is no stale-cache risk.

### My 404 page shows the wrong styling.

Your CSS request is probably also 404ing. If the 404 page links to `/css/style.css` and that file is missing, the page loads unstyled. Make sure the assets your error page needs actually exist under `root`.

### Can I serve both example.com and another domain from one nginx?

Yes. Add a second pair of `server` blocks with a different `server_name` and `root`. Nginx picks the block by hostname, which is the whole point of server blocks from [server blocks](/course/nginx-basics/serving-static-content/server-blocks).
