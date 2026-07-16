---
title: "A working PHP site"
slug: a-working-php-site
seo_title: "Nginx PHP-FPM Server Block Example (Copy and Test)"
seo_description: "A complete nginx PHP-FPM server block example for a plain PHP site: listen, root, try_files, and the FastCGI location. Copy it, add phpinfo, test that PHP runs."
---

## The goal

Two lessons of theory, now the payoff: a complete nginx PHP-FPM server block example that serves a real PHP site. You'll copy it, drop in a test file, and confirm PHP actually runs end to end.

This is a plain PHP site, not Laravel yet. Laravel has one extra rule, and it gets its own lesson next.

## The complete server block

```nginx
server {
    listen 80;
    server_name example.com;

    root /var/www/html;
    index index.php index.html;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

That is the whole thing. Every directive is one you have already met. Let's read it top to bottom.

## Reading it line by line

```nginx
listen 80;
server_name example.com;
```

Listen on plain HTTP port 80 and answer for `example.com`. You saw both in [listen and server_name](/course/nginx-basics/serving-static-content/listen-and-server-name). HTTPS comes in Chapter 7; for now everything is port 80.

```nginx
root /var/www/html;
index index.php index.html;
```

`root` is the folder your site lives in. `index` is what to serve when the URL is a directory. Note `index.php` comes **first**, so a request for `/` serves `index.php` if it exists, and only falls back to `index.html` otherwise. This is from [root and index](/course/nginx-basics/serving-static-content/root-and-index).

```nginx
location / {
    try_files $uri $uri/ =404;
}
```

This handles everything that is not a `.php` request. `try_files` (from [try files](/course/nginx-basics/location-matching/try-files)) checks each option in order:

- `$uri` - is there a file at this exact path? Serve it. This is how `style.css` and `logo.png` get served straight from disk.
- `$uri/` - is it a directory? Use the `index`.
- `=404` - otherwise return a 404.

```nginx
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

This is the PHP block from [the fastcgi_pass block](/course/nginx-basics/nginx-and-php/fastcgi-pass). Because it is a regex location, it is checked before `location /`, so any URL ending in `.php` lands here and is sent to PHP-FPM.

## Test it with phpinfo

Create one file in your `root`:

```php
<?php
// /var/www/html/info.php
phpinfo();
```

Reload nginx safely first (`nginx -t` then reload, from [start, stop, reload](/course/nginx-basics/getting-started/start-stop-reload)):

```bash
sudo nginx -t && sudo systemctl reload nginx
```

Now visit `http://example.com/info.php`. You should see the PHP information page: version, loaded modules, configuration. If you see that, PHP is running through PHP-FPM. The whole chain works.

On that page, find the **Server API** row near the top. It should read `FPM/FastCGI`. That one line is the proof the request went through PHP-FPM over FastCGI and not some other route; if it says `CLI` you are looking at output from the command line, not from nginx.

```bash
curl -s http://example.com/info.php | head -n 5
```

Delete `info.php` when you are done. It exposes your PHP configuration and should never sit on a public server.

## Common mistake: the security hole

There is a well-known unsafe version of the PHP location that looks harmless:

```nginx
# DO NOT copy this - it is the dangerous version
location ~ \.php {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

Spot the difference: `\.php` with no `$` at the end. Ours has `\.php$`.

Without the `$`, the pattern matches any path that merely *contains* `.php`, not just paths that *end* in it. A URL like `/uploads/evil.jpg/x.php` or `/photo.php/malware.png` would match. Combined with certain PHP settings, nginx can be tricked into telling PHP-FPM to execute a file the attacker uploaded (an image, say) as PHP code. This is the classic PHP-FPM remote code execution setup.

The fix is the one character you already have: **always anchor the regex with `$`** so only real `.php` files reach PHP-FPM.

```nginx
location ~ \.php$ {   # the $ is not optional
```

## Common mistake: 502 vs 404 vs source code

Three different failures, three different causes:

- **Raw PHP source or a download.** The request never reached the PHP location. Check the `location ~ \.php$` block exists.
- **502 Bad Gateway.** Nginx found the block but could not reach PHP-FPM. Wrong socket path, or PHP-FPM is not running. Check `systemctl status php8.4-fpm` and that the socket path matches.
- **File not found.** PHP-FPM was reached but `SCRIPT_FILENAME` points at nothing. Check your `root` and the `SCRIPT_FILENAME` line.

## FAQ

### Where do I put this server block?

In its own file under `/etc/nginx/sites-available/`, then symlink it into `sites-enabled/`, exactly as in [includes and sites](/course/nginx-basics/configuration-basics/includes-and-sites). Test with `nginx -t` before reloading.

### Do I need index.html at all?

No. It is just a fallback for directories without a PHP index. A pure PHP app can list only `index index.php;`.

### Why does try_files use =404 instead of /index.php?

Because a plain PHP site serves each file directly; a missing file really is a 404. Laravel is different: it routes everything through one front controller, so its `try_files` ends in `/index.php`. That is the whole point of the next lesson.
