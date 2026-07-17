---
title: "root and index"
slug: root-and-index
seo_title: "Nginx root and index: Map a URL to a File on Disk"
seo_description: "How the nginx root directive points at your files and index sets the default page, and exactly how a URL path becomes a real file on disk."
---

Every server block needs to know where its files live. In nginx, `root` sets that folder and `index` sets which file to serve when the URL points at a directory. This lesson shows how a URL turns into a real file on disk, step by step.

## root: where the files live

`root` is the folder that holds your site's files.

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;
}
```

Everything nginx serves for this site comes from `/var/www/example`.

## How a URL maps to a file

This is the key idea. Nginx takes the path from the URL and glues it onto `root`. That gives the file it looks for.

With `root /var/www/example`:

- `example.com/` maps to `/var/www/example/` (a directory, see `index` below).
- `example.com/about.html` maps to `/var/www/example/about.html`.
- `example.com/img/logo.png` maps to `/var/www/example/img/logo.png`.

The rule is simple: **file path = root + URL path**. If the file exists, nginx sends it. If not, nginx returns a 404.

## index: the default file

When the URL points at a directory (like `/`), there is no filename to serve. `index` says which file to fall back to.

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;
    index index.html;
}
```

Now a request for `example.com/` serves `/var/www/example/index.html`. This is why home pages are almost always called `index.html`.

You can list several, tried in order:

```nginx
index index.html index.htm;
```

Nginx serves the first one it finds in that directory. It checks `index.html` first, and only falls back to `index.htm` if the first is missing.

One point that catches beginners: `index` only fires when the URL maps to a directory. A request for a named file that is missing, like `/about.html`, does not fall back to the index file; it returns a plain 404. The index is for "someone asked for a folder," not "the file I wanted was not there."

## A complete working example

Put it together and you have a real static site:

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;
    index index.html;
}
```

Create `/var/www/example/index.html`:

```html
<!doctype html>
<html>
  <body>
    <h1>Hello from nginx</h1>
  </body>
</html>
```

Reload nginx (see [Start, stop, reload](/course/nginx-basics/getting-started/start-stop-reload)) and visit the site. You built your very first page back in Chapter 1; this is the same idea, now driven by `root` and `index`.

## Common mistake

Putting a slash on the end of `root`, like `root /var/www/example/;`. It usually still works, but the clean habit is no trailing slash. A worse mistake is pointing `root` at a folder that does not exist or that nginx cannot read; you will get [403](/course/nginx-basics/common-errors-and-fixes/403-forbidden) or 404 errors. Check the path and its permissions, then read the error log as shown in [Access and error logs](/course/nginx-basics/configuration-basics/access-and-error-logs).

## FAQ

### What if the file does not exist?

Nginx returns a 404 Not Found. You can serve a nicer page for that, covered in the custom error pages lesson.

### Can root be set outside a server block?

Yes, you can set `root` in the `http` context as a default, but setting it per server block is clearest. A block's own `root` wins.

### Does index only work for the home page?

No. It applies to any directory. A request for `example.com/docs/` serves `/var/www/example/docs/index.html` if it exists.
