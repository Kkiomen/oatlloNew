---
title: "location basics"
slug: location-basics
seo_title: "Nginx location Block Basics: Prefix Match for Beginners"
seo_description: "The nginx location block for beginners: what a prefix location matches, why you would use one, and how it changes rules for part of a URL."
---

Up to now a server block has applied the same settings to your whole site. An nginx `location` block changes that: it lets you handle just one part of the URL differently. This lesson introduces the idea with the simplest kind, a prefix location.

## What does an nginx location block do?

A `location` block matches part of the request path and applies rules just to that part.

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;

    location /images/ {
        root /var/www/media;
    }
}
```

Requests starting with `/images/` use `root /var/www/media`. Every other request uses the site's main `root`, `/var/www/example`.

So `example.com/images/logo.png` maps to `/var/www/media/images/logo.png`, while `example.com/about.html` maps to `/var/www/example/about.html`. One site, but one path is handled differently.

## Why you would use one

A few everyday reasons:

- Serve one folder (like `/images/` or `/downloads/`) from a different disk location.
- Turn a setting on for one path only, such as a directory listing (the autoindex lesson uses a location).
- Give one URL its own error page or its own rules.

You do not need a location for a plain static site. The server block alone serves files fine. Reach for a location when one part of the site needs to behave differently from the rest.

## A prefix location

The example above is a **prefix location**. It matches any path that *starts with* the text you give.

```nginx
location /docs/ {
    # applies to /docs/, /docs/intro.html, /docs/api/v1.html, ...
}
```

`/docs/` matches `/docs/`, `/docs/intro.html`, and anything deeper. That "starts with" behaviour is what makes it a prefix match.

The trailing slash is worth a second look. `location /docs` (no slash) also matches `/docs`, but because it is a plain prefix it matches `/docs-archive/` too, which is probably not what you meant. Keeping the slash, `location /docs/`, ties the match to that folder and avoids the surprise. When a prefix stands for a directory, write the slash.

## Locations nest inside a server block

A `location` always lives inside a `server` block. It is a context inside a context:

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;

    location /downloads/ {
        root /var/www/files;
    }
}
```

You can have several locations in one server block, each for a different path.

## More than one way to match

Prefix is only the beginning. Nginx can also match with exact rules, priorities, and regular expressions, and it has clear rules for which location wins when several could match. That is a whole topic on its own, and it is covered in the next chapter, [Location matching](/course/nginx-basics/location-matching/how-location-matching-works). For now, stick to simple prefix locations.

## Common mistake

Expecting a location to do the whole job on its own. A location changes settings for a path, but it still needs a `root` (its own or the block's) to find files. Leaving a location empty just means "handle this path with the normal rules," which may be exactly what you want, or may do nothing useful.

## FAQ

### Do I need a location to serve a static site?

No. A server block with `root` and `index` serves a static site by itself. Add a location only when one path needs different handling.

### What is a prefix location?

A location that matches any URL path starting with the given text, like `/images/` matching `/images/logo.png`.

### Why not learn all the matching rules now?

Because they get detailed fast. The next chapter is dedicated to them. Simple prefix locations cover most beginner needs.
