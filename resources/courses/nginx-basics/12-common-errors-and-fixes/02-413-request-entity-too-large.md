---
title: "Nginx 413 Request Entity Too Large - Fix"
slug: 413-request-entity-too-large
seo_title: "Nginx 413 Request Entity Too Large - How to Fix"
seo_description: "Fix nginx 413 Request Entity Too Large: raise client_max_body_size above the default 1M, reload, and bump PHP upload_max_filesize too."
---

## What the error looks like

When you upload a file that is too big, the browser shows:

```text
413 Request Entity Too Large
nginx/1.24.0
```

And the error log records it:

```text
2026/07/16 11:03:15 [error] 1234#0: *8 client intended to send too large body: 5242880 bytes, client: 1.2.3.4, server: example.com, request: "POST /upload HTTP/1.1"
```

## Why it happens

Nginx limits how big a request body can be. The directive is `client_max_body_size`, and its default is only **1M** (one megabyte). Any upload larger than that is rejected before it ever reaches your app. The number in the log (`5242880 bytes` above) is the size the client tried to send.

## How to fix it

1. Set `client_max_body_size` to a value big enough for your uploads. It can go in the `http`, `server`, or `location` block:

```nginx
server {
    listen 80;
    server_name example.com;

    client_max_body_size 20m;
}
```

Put it in a `location` if you only want to allow big uploads on one route:

```nginx
location /upload {
    client_max_body_size 50m;
}
```

2. Test and reload:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

3. If your app is PHP, raise PHP's own limits too. Nginx and PHP each have their own cap, and PHP's default is also low. Edit `php.ini`:

```text
upload_max_filesize = 20M
post_max_size = 21M
```

Then restart PHP-FPM:

```bash
sudo systemctl restart php8.4-fpm
```

Keep `post_max_size` a little larger than `upload_max_filesize`, since the POST body carries the file plus other fields.

## Common mistake

Raising `client_max_body_size` but forgetting PHP. The upload now passes nginx, then PHP rejects it. Both limits must be raised for the upload to work end to end.

There is a subtler version that catches people who use Certbot. Certbot [splits your site into a plain port 80 block and a separate port 443 block](/course/nginx-basics/https-tls/redirect-http-to-https). If you added `client_max_body_size` to the HTTP block by hand, the HTTPS block does not have it, and real uploads over HTTPS still 413. Put the directive in the `http` block, or in `server`, so both listeners inherit it.

## FAQ

### What is a good value for client_max_body_size?

Set it to the largest upload you actually expect, plus a little headroom. Do not set it to something huge like `1000m` to be safe, since that lets a single request tie up memory and disk.

### Can I disable the limit completely?

Yes, `client_max_body_size 0;` turns the check off. Avoid it on public servers. It removes a basic guard against oversized requests.

### I set it in http but it did not work. Why?

A `server` or `location` block can override the `http` value. If a more specific block sets its own `client_max_body_size`, that one wins for those requests. Set it where the request is actually handled.
