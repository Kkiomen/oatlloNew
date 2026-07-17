---
title: "Serving files with try_files"
slug: try-files
seo_title: "Nginx try_files Explained: $uri $uri/ =404 Fallbacks"
seo_description: "How nginx try_files checks if a file exists, falls back to a folder index, and returns 404 - plus the front-controller fallback pattern for real sites."
---

The nginx `try_files` directive tries a list of possible files for a request and uses the first one that exists. It is one of the directives you reach for most when serving real sites, because it replaces a pile of fragile `if` checks with a single line.

## Why root and index alone fall short

With `root` and `index` (from [root and index](/course/nginx-basics/serving-static-content/root-and-index)), nginx maps a URL to one file. Real sites want a choice: serve the file if it exists, otherwise serve a folder's index, otherwise show a 404. Chaining `if` blocks to express that is fragile and easy to get wrong. `try_files` is the clean answer.

## A minimal try_files example

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/site;

    location / {
        try_files $uri $uri/ =404;
    }
}
```

`try_files` takes a list of things to try, left to right, and uses the first that works. The last item is the fallback.

## What each part means

- **`$uri`** is the requested path, like `/about.html`. This says "try a file at that exact path on disk."
- **`$uri/`** adds a trailing slash, so nginx looks for a **directory** and serves its `index` file (usually `index.html`).
- **`=404`** is the fallback. If nothing above existed, return a 404 status.

So a request for `/about.html` checks `/var/www/site/about.html` first. A request for `/blog/` checks the file, then the folder and its index. A request for `/missing` finds neither and returns 404.

## The fallback can be a file

Instead of `=404`, the last argument can be a real path. If nothing matched, nginx serves that file.

```nginx
location / {
    try_files $uri $uri/ /maintenance.html;
}
```

Here any URL that is not a real file falls back to `/maintenance.html`. Note the difference: `=404` is a **status code** (it has the `=`), while `/maintenance.html` is a **path** served from your `root`.

Watch that `=` sign. Drop it and write `try_files $uri $uri/ 404;`, and nginx treats `404` as a filename, hunting for a file literally called `404` in your root. When it does not find one you get the wrong error, and the config still loads without complaint, so nothing points you at the typo.

## Front-controller pattern (intro)

Many apps route every request through a single entry file. You can point every unmatched URL at one file:

```nginx
location / {
    try_files $uri $uri/ /index.html;
}
```

Now `/`, `/about`, and `/products/42` all fall back to `/index.html` when there is no matching file. That is the core of the "front controller" idea: static files served directly, everything else handed to one file. You will [build the full version for a real app](/course/nginx-basics/nginx-and-php/a-laravel-server-block) later in the course, once you have proxying and PHP.

## FAQ

### Why does the order in try_files matter?
Nginx uses the first item that exists, so put the most specific first. Checking `$uri` before `$uri/` means a real file wins over a directory listing.

### What is the difference between =404 and a path?
`=404` returns an HTTP status directly. A path like `/index.html` serves that file with a 200 status. Use the `=` form only for status codes.

### Do I still need index with try_files?
Yes. The `$uri/` step relies on your `index` directive to know which file to serve for a directory, such as `index index.html;`.
