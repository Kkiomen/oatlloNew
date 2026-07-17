---
title: "autoindex"
slug: autoindex
seo_title: "Nginx autoindex: Show a Directory Listing of Files"
seo_description: "Turn on nginx autoindex to show a directory listing when a folder has no index file, when it is genuinely useful, and why to keep it off."
---

When a URL points at a folder that has no index file, nginx returns a [403 Forbidden](/course/nginx-basics/common-errors-and-fixes/403-forbidden) by default. Turn on `autoindex` and nginx shows a directory listing of the folder instead. This lesson covers when that listing actually helps and when to leave it off.

## What autoindex is for

Say a visitor opens `example.com/downloads/`. If that folder has no `index.html`, nginx has nothing to serve for the directory, so by default it answers 403 Forbidden.

Sometimes that is fine. But sometimes you actually want people to see and pick from the files in that folder, like a public downloads directory. That is what `autoindex` is for.

## Turning it on

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;

    location /downloads/ {
        autoindex on;
    }
}
```

Now `example.com/downloads/` shows an auto-generated page listing every file and subfolder, each as a clickable link, with sizes and dates. Nginx builds this page on the fly; you do not write any HTML.

Notice it is inside a `location` (see [location basics](/course/nginx-basics/serving-static-content/location-basics)). That way only `/downloads/` gets a listing, and the rest of the site does not.

If the folder also contains an `index.html`, that file wins and you never see the listing. `index` is checked before autoindex, so the listing only appears when there is no index file to serve. Drop an `index.html` into a folder and its auto-listing quietly disappears, which is sometimes exactly how people "turn it off" for one folder.

## When it is useful

- A public folder of downloads, releases, or documents people should browse.
- An internal or temporary file share where a plain list is all you need.
- Quick debugging, to confirm which files nginx can actually see in a folder.

## When to keep it off

`autoindex` is **off by default**, and that default is usually the right one. A listing exposes every file in the folder, including ones you did not mean to share. Turn it on only for folders whose entire contents are meant to be public.

- Do not enable it on your whole site root.
- Do not enable it on folders that hold config, backups, or private files.
- Prefer switching it on for one specific `location`, never globally, unless you really mean it.

If a folder should have a normal home page instead of a listing, give it an `index.html` and leave `autoindex` off. The `index` directive from [root and index](/course/nginx-basics/serving-static-content/root-and-index) takes over.

## A tidier listing

Two optional settings make the output nicer:

```nginx
location /downloads/ {
    autoindex on;
    autoindex_exact_size off;
    autoindex_localtime on;
}
```

- `autoindex_exact_size off;` shows sizes as KB and MB instead of exact bytes.
- `autoindex_localtime on;` shows file times in the server's local time instead of UTC.

## Common mistake

Leaving `autoindex on` at the site root and forgetting about it. If there is no `index.html` in the root, the whole site becomes a browsable file list, which can leak files you never meant to publish. Scope it to a single `location` and only for content that is safe to show.

## FAQ

### Why do I get 403 instead of a listing?

Because `autoindex` is off and the folder has no index file. Either add an `index.html` or turn on `autoindex` for that path.

### Does autoindex work on subfolders too?

Yes. A listing links into subfolders, and each one is listed the same way as long as the location applies.

### Is autoindex safe to leave on?

Only for folders whose full contents are meant to be public. For anything else, keep it off. It is off by default for good reason.
