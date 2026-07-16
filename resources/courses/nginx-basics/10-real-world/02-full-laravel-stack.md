---
title: "The full Laravel production config"
slug: full-laravel-stack
seo_title: "Complete Nginx Laravel Config for Production (PHP-FPM)"
seo_description: "The full nginx server block for a Laravel app: HTTPS redirect, root public, try_files front controller, PHP-FPM, gzip, caching, and security headers."
---

## What a Laravel production config has to do

Laravel has one entry point: `public/index.php`. Every request, whether it is `/`, `/login`, or `/api/users/42`, has to reach that file so Laravel's router can take over. A working nginx Laravel production config serves real files directly (CSS, images) and hands everything else to that front controller over PHP-FPM.

You learned the pieces in [nginx and PHP](/course/nginx-basics/nginx-and-php/how-nginx-runs-php). Here is the complete config, explained block by block.

## The full config

```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name example.com www.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name example.com www.example.com;

    root /var/www/example.com/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    charset utf-8;
    client_max_body_size 20M;

    # Compression
    gzip on;
    gzip_types text/css application/javascript image/svg+xml application/json;

    # The Laravel front controller
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Cache built assets
    location ~* \.(css|js|jpg|jpeg|png|gif|svg|woff2|ico)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Pass PHP to PHP-FPM
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
    }

    # Hide dotfiles like .env and .git
    location ~ /\.(?!well-known).* {
        deny all;
    }

    error_page 404 /index.php;
}
```

This is very close to the block Laravel's own docs ship. Now the walk-through.

## HTTP to HTTPS redirect

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name example.com www.example.com;
    return 301 https://$host$request_uri;
}
```

Identical to the static-site redirect from the previous lesson. Port 80 traffic gets a permanent 301 to the HTTPS URL, preserving the path with `$request_uri`. Pattern from [redirect HTTP to HTTPS](/course/nginx-basics/https-tls/redirect-http-to-https).

## The HTTPS listener and TLS

```nginx
listen 443 ssl;
listen [::]:443 ssl;
http2 on;
server_name example.com www.example.com;

ssl_certificate     /etc/letsencrypt/live/example.com/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;
ssl_protocols       TLSv1.2 TLSv1.3;
```

Same HTTPS setup as the static site: port 443 with the `ssl` flag, HTTP/2 on, and the certbot certificate from [Let's Encrypt with certbot](/course/nginx-basics/https-tls/lets-encrypt-certbot). `ssl_protocols` restricts to modern TLS, from [basic TLS hardening](/course/nginx-basics/https-tls/basic-tls-hardening).

## root points at public, not the project

```nginx
root /var/www/example.com/public;
index index.php;
```

This is the line people get wrong. `root` must point at Laravel's `public/` folder, not the project root. Everything above `public/` (your `.env`, `app/`, `vendor/`, `storage/`) must stay unreachable by the web. If you point `root` at the project folder instead, you expose your entire source and secrets. `root` and `index` are from [root and index](/course/nginx-basics/serving-static-content/root-and-index), and this exact rule appears in [a Laravel server block](/course/nginx-basics/nginx-and-php/a-laravel-server-block).

## Security headers

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

These tell the browser to behave more safely, all from [security headers](/course/nginx-basics/security-basics/security-headers):

- `X-Frame-Options SAMEORIGIN` stops other sites from embedding yours in an iframe (clickjacking defense).
- `X-Content-Type-Options nosniff` stops the browser from guessing a file's type and running it as something it is not.
- `Referrer-Policy` limits how much of your URL leaks to other sites.

The `always` keyword makes nginx send the header even on error responses like 404 or 500, not just on 200s.

## charset and upload size

```nginx
charset utf-8;
client_max_body_size 20M;
```

`charset utf-8` adds the UTF-8 label to text responses so accented characters render correctly. `client_max_body_size 20M` sets the largest request body nginx will accept. The default is 1M, which rejects most file uploads with a `413` error. Raise it to whatever your uploads need.

## gzip

```nginx
gzip on;
gzip_types text/css application/javascript image/svg+xml application/json;
```

Same compression as the static site, from [gzip compression](/course/nginx-basics/performance-and-caching/gzip-compression). This matters extra for Laravel JSON API responses, which compress very well.

## The front controller

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

This is what makes Laravel routing work. For each request, `try_files` from [try_files](/course/nginx-basics/location-matching/try-files) does:

- `$uri`: is there a real file here? If yes (a built CSS file, an image), serve it directly and skip PHP.
- `$uri/`: is it a real directory?
- `/index.php?$query_string`: otherwise, hand the request to Laravel's front controller, keeping the query string so `?page=2` and friends survive.

That last fallback is the whole trick. `/login` is not a file, so it falls through to `index.php`, and Laravel's router decides what to do. Note there is no `=404` here: unknown paths are Laravel's job, not nginx's, so Laravel can show its own 404.

## The PHP location

```nginx
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    fastcgi_hide_header X-Powered-By;
}
```

This is the FastCGI block from [the fastcgi_pass block](/course/nginx-basics/nginx-and-php/fastcgi-pass). The `~ \.php$` regex matches any `.php` request, `include fastcgi_params` loads the standard parameters, and `fastcgi_pass` points at the PHP-FPM socket. Match the PHP version in the socket path to the version you run, or you get a `502`.

One change from the earlier lesson: `$realpath_root` instead of `$document_root`. It resolves symlinks to their real path, which matters for zero-downtime deploys (Envoyer, Deployer) where `public` is a symlink to the current release. `fastcgi_hide_header X-Powered-By` strips PHP's version header, the same idea as hiding nginx's version in [hide version](/course/nginx-basics/security-basics/hide-version).

Worth hardening: this block will run any `.php` file it can find under `root`. On a stock Laravel app only `index.php` lives in `public/`, so the risk is small, but add `try_files $uri =404;` as the first line inside this location and nginx refuses to hand PHP-FPM a path that does not resolve to a real file. That closes off the classic trick of smuggling PHP through a crafted path.

## Hiding dotfiles

```nginx
location ~ /\.(?!well-known).* {
    deny all;
}
```

Files and folders starting with a dot (`.env`, `.git`, `.htaccess`) hold secrets or internals and must never be served. This regex matches any path segment beginning with a dot and denies it, using `deny` from [access control](/course/nginx-basics/security-basics/access-control). The `(?!well-known)` negative lookahead is an exception: it keeps `/.well-known/` reachable, which certbot needs to renew your certificate. Without that exception, renewals fail.

## error_page

```nginx
error_page 404 /index.php;
```

If nginx itself produces a 404 (for example a bad `.php` path), this routes it back through Laravel so the user sees your app's error page, not a bare nginx one. Concept from [custom error pages](/course/nginx-basics/serving-static-content/custom-error-pages).

## Deploying it

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Test with `nginx -t` from [testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely), then reload. If PHP changes do not appear, two caches sit above nginx: Laravel's own config and route cache (`artisan config:clear`), and PHP's OPcache, which holds compiled files in the FPM process. Reloading nginx clears neither. When new code refuses to show up, reload `php8.4-fpm`, not nginx.

## FAQ

### Why does root point at public and not the app folder?

Because everything outside `public/` is private: your `.env` with database passwords, your source code, your `vendor` folder. Only `public/` is meant to be web-accessible. Pointing `root` higher exposes all of it. This is the single most important line in the file.

### I get a 502 Bad Gateway on every page.

PHP-FPM is not reachable. Either it is not running (`systemctl status php8.4-fpm`) or the socket path in `fastcgi_pass` does not match PHP-FPM's own `listen` setting. Fix the path or start the service.

### Uploads fail with 413 Request Entity Too Large.

That is `client_max_body_size` being too small. Raise it here, and also raise `upload_max_filesize` and `post_max_size` in PHP, because both nginx and PHP enforce their own limits.

### Where do queue workers and the scheduler fit?

They do not touch nginx. `php artisan queue:work` and the scheduler run as separate processes (systemd or supervisor). Nginx only handles incoming web requests; background work lives outside it entirely.
