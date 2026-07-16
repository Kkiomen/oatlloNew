---
title: "alias: serve a directory under a different URL"
slug: alias-serving-another-directory
seo_title: "Nginx alias vs root: Serve a Folder Under Any URL"
seo_description: "Nginx alias vs root explained: map any folder to a URL path, the difference from root, the trailing-slash rule, and the common doubled-path 404."
---

The [root and index](/course/nginx-basics/serving-static-content/root-and-index) lesson showed that `root` glues the URL path onto a base folder. That is not always what you want. Say your files sit in a folder like `/var/www/files/website/public`, and you want them to appear under a short URL like `/files/`, without that long `website/public` path showing up. The nginx `alias` directive is built for exactly this, and it behaves differently from `root`.

## root appends, alias replaces

This one sentence is the whole lesson:

- `root` takes the **full URL path** and appends it to the base folder.
- `alias` takes the `location` prefix and **replaces** it with the folder.

Same request, `/files/style.css`, two different results:

```nginx
# root: appends the whole URL path
location /files/ {
    root /var/www;
    # -> /var/www/files/style.css
}
```

```nginx
# alias: strips /files/ and substitutes the folder
location /files/ {
    alias /var/www/files/website/public/;
    # -> /var/www/files/website/public/style.css
}
```

With `alias`, the `/files/` prefix disappears and is swapped for the real folder, so the long `website/public` path never appears in the URL.

## Version 1: mount the folder as the whole site (root)

If you want that folder to be the entire site - the home page and everything under it - point `root` at it directly in the server block:

```nginx
server {
    listen 80;
    server_name example.com;

    root /var/www/files/website/public;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

Now `example.com/` serves `.../public/index.html` and `example.com/style.css` serves `.../public/style.css`. The whole tree under that folder is reachable by its path. Use this when the folder *is* your site.

## Version 2: mount the folder under one URL prefix (alias)

If the rest of the server should stay as it is and you only want that one folder to appear under `/files/`, use `alias` inside a location:

```nginx
location /files/ {
    alias /var/www/files/website/public/;
    index index.html;
    try_files $uri $uri/ =404;
}
```

Now:

- `example.com/files/` serves `.../public/index.html`
- `example.com/files/style.css` serves `.../public/style.css`

Only that folder is exposed under `/files/`. Nothing else on the server leaks into that path.

## The trailing-slash rule

The most reliable way to use `alias` is to keep a trailing slash on **both** the location and the alias path:

```nginx
location /files/ {
    alias /var/www/files/website/public/;
}
```

If one has a slash and the other does not, nginx can glue the path together wrong and you get 404s. When in doubt, match them: slash on both, or slash on neither.

One more thing that catches people: `alias` only works **inside a `location`**. It has no meaning in the bare `server` block, because it replaces a location prefix, and without a location there is no prefix to replace. If you want a whole-site base folder, that is the job of `root` (Version 1 above), not `alias`.

## Common mistake

Using `root` when you meant `alias`. The symptom is a 404 with a **doubled** path in the error log, like `/var/www/files/website/public/files/style.css` - notice the extra `/files/`. That extra segment is the URL prefix that `root` appended but `alias` would have stripped. Read the error log (see [access and error logs](/course/nginx-basics/configuration-basics/access-and-error-logs)); it prints the exact path nginx tried, which makes this obvious.

## Verify it

```bash
sudo nginx -t && sudo systemctl reload nginx
curl -I http://example.com/files/style.css
```

A `200 OK` with the right `Content-Type` means the mapping works. A 404 almost always means a `root`/`alias` mix-up or a trailing-slash mismatch.

## FAQ

### What is the difference between root and alias in nginx?

`root` appends the full URL path to the folder, so `/files/x` becomes `root/files/x`. `alias` replaces the matched location prefix with the folder, so `/files/x` becomes `alias/x`. Use `alias` when the URL prefix and the folder name do not match.

### When should I use alias instead of root?

Use `alias` when you want a specific folder to appear under a specific URL that does not mirror the folder's real path on disk - like serving `/var/www/files/website/public` under `/files/`. Use `root` when the folder is the site root or the URL structure matches the folder structure.

### Why do I get a 404 after switching to alias?

Usually a trailing-slash mismatch between the location and the alias path, or you left `root` in by accident. Check the error log for a doubled path, and make sure both the `location` and the `alias` value end with `/`.
