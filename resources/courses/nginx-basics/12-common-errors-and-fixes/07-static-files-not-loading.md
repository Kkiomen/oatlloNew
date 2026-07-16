---
title: "Nginx Not Serving Static Files - CSS Not Loading Fix"
slug: static-files-not-loading
seo_title: "Nginx Not Serving Static Files / CSS Not Loading - Fix"
seo_description: "Fix nginx not serving static files: wrong root or alias, missing try_files, missing mime.types (CSS as text/plain), permissions, or a greedy front controller."
---

## What the error looks like

The page loads but has no styling, and assets 404. In the browser console you see:

```text
GET https://example.com/css/app.css 404 (Not Found)
Refused to apply style from '.../app.css' because its MIME type ('text/plain') is not a supported stylesheet MIME type
```

The nginx access log confirms it:

```text
1.2.3.4 - - [16/Jul/2026:14:02:11] "GET /css/app.css HTTP/1.1" 404 153
```

## Why it happens

Several different problems all show up as "my CSS will not load":

- **Wrong `root` or `alias`.** Nginx looks in the wrong folder, so the file is not there. See [root and index](/course/nginx-basics/serving-static-content/root-and-index) and [alias](/course/nginx-basics/serving-static-content/alias-serving-another-directory).
- **Missing `include mime.types`.** Without it, CSS is sent as `text/plain` and the browser refuses to apply it. See [mime types](/course/nginx-basics/serving-static-content/mime-types).
- **Missing or wrong `try_files`.** The request never resolves to the real file.
- **Permissions.** The nginx user cannot read the asset.
- **A greedy front controller.** In a Laravel or SPA config, `try_files $uri $uri/ /index.php` can send asset requests to PHP instead of serving the file.

## How to fix it

1. Make sure MIME types are included. This is the fix for the `text/plain` warning. It belongs in the `http` block, usually already in the main `nginx.conf`:

```nginx
http {
    include mime.types;
    default_type application/octet-stream;
}
```

2. Confirm `root` points at the directory that actually holds the assets, and that the file exists there:

```bash
ls -l /var/www/html/public/css/app.css
```

3. For a Laravel or SPA site, put `$uri` first in `try_files` so real files are served before falling back to the front controller:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Because `$uri` comes first, `/css/app.css` is served as a file and never reaches PHP.

4. Fix permissions if needed, then reload:

```bash
sudo chown -R www-data:www-data /var/www/html
sudo nginx -t && sudo systemctl reload nginx
```

## Common mistake

Trailing slash confusion with `alias`. If your `location /assets/` uses `alias`, the alias path must end in a slash too. A missing slash makes nginx build the wrong file path and every asset 404s.

Another one that hides in plain sight: a regex `location`. A block like `location ~* \.(css|js|png)$ { ... }` wins over your normal `location /`, because regex locations are matched before prefix ones. If that block sets a different `root`, or forgets to set one, your assets 404 while the rest of the site is fine. When only certain file types break, look for a regex location handling exactly those extensions.

## FAQ

### Why is my CSS served as text/plain?

Nginx does not know the file is CSS. Add `include mime.types;` in the `http` block. That maps `.css` to `text/css` so the browser accepts it.

### Assets 404 only after I added a PHP app. Why?

Your `try_files` probably sends everything to `index.php`. Keep `$uri` as the first fallback so existing files are served directly and only missing paths reach PHP.

### The file exists but still 404s. What now?

Check the path nginx is actually building. Turn on the error log, request the asset, and read the `open() "..." failed` line. It shows the exact path nginx tried, which usually reveals a wrong `root` or `alias`.
