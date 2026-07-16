---
title: "A Laravel server block"
slug: a-laravel-server-block
seo_title: "Nginx Laravel Server Block: The Canonical Config"
seo_description: "The canonical nginx Laravel server block config: root at /public, try_files to index.php, the PHP-FPM location, and dotfile protection, explained line by line."
---

## Two rules that make Laravel different

A Laravel app is still a PHP app, so the last lesson's server block is almost right. The nginx Laravel server block adds two rules a plain PHP site does not have:

1. The public web root is the `public/` folder, not the project root.
2. Every request that is not a real file is routed through one file, `public/index.php` (the front controller).

Get these two right and Laravel just works. Get them wrong and you either expose your source code or get 404s on every page. This lesson is the canonical config, explained.

## The complete server block

```nginx
server {
    listen 80;
    server_name myapp.test;

    root /var/www/myapp/public;
    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\. {
        deny all;
    }
}
```

This is the config the Laravel docs themselves recommend, trimmed to the essentials you now understand. Let's walk through it.

## root points at /public

```nginx
root /var/www/myapp/public;
```

This is the single most important line. Laravel's folder looks like this:

```text
myapp/
  app/
  config/
  .env          <- secrets: DB password, app key
  vendor/
  public/       <- root points HERE
    index.php
    css/
    js/
```

The `root` points at `public/`, one level inside the project, **not** at `myapp/` itself. Everything outside `public/` (your code, your `.env`, your `vendor/`) then sits *above* the web root, so nginx cannot serve it no matter what URL someone types. Only `public/` is reachable from the internet.

Point `root` at the project folder instead, and anyone could request `/.env` and download your database password. This is not theoretical; it is one of the most common Laravel leaks.

## index is index.php

```nginx
index index.php;
```

Laravel has no `index.html`. The one entry point is `public/index.php`, so that is the only index needed.

## try_files: the front controller

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

This is the line that makes Laravel routing work. Compare it to the plain PHP site, which ended in `=404`. Laravel ends in `/index.php?$query_string`. Here is why.

`try_files` checks each option in order (from [try files](/course/nginx-basics/location-matching/try-files)):

- `$uri` - is there a real file at this path? A request for `/css/app.css` hits a real file, so nginx serves it straight from disk. Static assets never touch PHP.
- `$uri/` - is it a directory with an index?
- `/index.php?$query_string` - **otherwise, hand the request to Laravel's front controller.** A URL like `/users/42` has no matching file, so it falls through to here.

That last step is the heart of it. Laravel does not have a `users.php` file. It has routes defined in code, and every non-file request is sent to `public/index.php`, which boots the framework and lets Laravel's router decide what to do. `$query_string` preserves things like `?page=2` so the app still sees them.

Without this, a URL like `/about` would return 404, because there is no `about` file on disk. With it, `/about` flows to `index.php` and Laravel handles the route.

The order matters more than it looks. Nginx tries `$uri` first, so a real file always wins over a Laravel route of the same name. Leave a stray `public/about.html` next to an `/about` route and nginx serves the file, Laravel never runs, and you get a confusing "my route stopped working" with no error anywhere. When a route mysteriously returns the wrong content, check `public/` for a file shadowing it.

## The PHP location

```nginx
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

Identical to [a working PHP site](/course/nginx-basics/nginx-and-php/a-working-php-site). When `try_files` rewrites a request to `/index.php`, that path ends in `.php`, so it matches here and goes to PHP-FPM. `SCRIPT_FILENAME` resolves to `/var/www/myapp/public/index.php`, the front controller. Keep the `$` anchor for the security reason from the last lesson.

## Deny dotfiles

```nginx
location ~ /\. {
    deny all;
}
```

This blocks any request for a path with a segment starting with a dot: `/.env`, `/.git/config`, `/.htaccess`. Your `root` already keeps `.env` out of reach because it lives above `public/`, but this is a cheap second layer. It also protects things like a `.git` folder if one ever ends up inside the web root by accident.

`deny all;` returns 403 Forbidden for every matching request. You met `deny` conceptually as access control; here it is one focused rule.

## Common mistake: root at the project, not /public

```nginx
# WRONG - exposes your whole app
root /var/www/myapp;
```

The number-one Laravel nginx mistake. Symptoms range from "the CSS loads but every page is a 404" to the far worse "someone downloaded my `.env`". Always point `root` at the `public` subfolder.

## Common mistake: forgetting the front controller

If your `try_files` ends in `=404` (copied from a plain PHP site), the Laravel homepage might load but every other route 404s, because only `/` maps to `index.php` via the `index` directive. Every deeper URL needs the `/index.php?$query_string` fallback. This looks like "Laravel routing is broken" but it is really the nginx config.

## Verify it

```bash
sudo nginx -t && sudo systemctl reload nginx
curl -I http://myapp.test/
```

A `200 OK` on the homepage and a working `/some/route` means the front controller wiring is correct. If routes 404, re-check `try_files`. If you get a 502, re-check `fastcgi_pass` and that PHP-FPM is running, exactly as in the previous lesson.

## FAQ

### Do I need to change this config when I add new routes?

No. That is the beauty of the front controller. Nginx sends every non-file request to `index.php`, and Laravel's router decides everything from there. You edit routes in PHP, never in nginx.

### My storage or upload files 404. Why?

Laravel serves user files through a symlink at `public/storage`, created by `php artisan storage:link`. If that link is missing, the files sit outside the web root and nginx cannot see them. Run the command; no nginx change is needed.

### Where does the HTTPS version go?

Everything here stays on port 80 for now. In Chapter 7 you add a `listen 443 ssl;` block and redirect HTTP to HTTPS. The `root`, `try_files`, and PHP location are exactly the same; only the listen and certificate lines change.

### Is this the same for Symfony or WordPress?

The shape is the same: a public web root, a front controller via `try_files`, and a `\.php$` FastCGI location. The web root folder and the front controller filename differ per framework, but the pattern you learned here carries over.
