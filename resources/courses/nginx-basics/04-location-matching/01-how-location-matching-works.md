---
title: "How location matching works"
slug: how-location-matching-works
seo_title: "Nginx location Match Types: Prefix, =, ~, ~*, ^~ Explained"
seo_description: "The five nginx location match types - prefix, exact =, regex ~ and ~*, and ^~ - and exactly which URLs each one matches, with clear examples."
---

In [location basics](/course/nginx-basics/serving-static-content/location-basics) you saw a plain `location /` block. That is only one of the nginx location match types. Each type compares the request URL in a different way, and picking the right one is how you control which block handles a request.

## What are the five nginx location match types?

Every `location` has a modifier (or none) that decides how it compares against the request path.

```nginx
location /images/ { }      # prefix (no modifier)
location = /favicon.ico { }  # exact match
location ~ \.php$ { }       # regex, case-sensitive
location ~* \.(jpg|png)$ { }  # regex, case-insensitive
location ^~ /assets/ { }    # prefix, stops regex search
```

## Prefix match (no modifier)

A plain `location /path` matches if the request URL **starts with** that string.

```nginx
location /images/ {
    root /var/www/site;
}
```

This matches `/images/`, `/images/logo.png`, and `/images/icons/cart.svg`. It does not need to match the whole URL, just the beginning.

## Exact match `=`

The `=` modifier matches only if the URL is **exactly** equal to the string.

```nginx
location = /favicon.ico {
    root /var/www/site;
}
```

This matches `/favicon.ico` and nothing else. Not `/favicon.ico/`, not `/favicon.icon`. It is the fastest check, great for single, hot URLs.

## Regex match `~` and `~*`

A regex `location` uses a regular expression instead of a fixed string. `~` is case-sensitive, `~*` is case-insensitive.

```nginx
location ~ \.php$ {
    # only .php (lowercase)
}

location ~* \.(jpg|jpeg|png|gif)$ {
    # .jpg, .JPG, .Png ... all match
}
```

The `$` anchors the match to the end of the URL, so `\.php$` matches paths ending in `.php`. Regex is how you match by file extension or a pattern rather than a folder.

A common trip-up: `~` is case-sensitive, so `\.php$` will not match a request for `/index.PHP`. Uploads and hand-typed URLs do show up in mixed case, and reaching for `~*` after the fact means editing config under pressure. If a rule targets a file extension, `~*` is usually the safer default.

## Prefix that beats regex `^~`

`^~` is a prefix match (like a plain one), but with a promise: if this prefix is the best match, nginx **will not** run any regex locations afterwards.

```nginx
location ^~ /assets/ {
    root /var/www/site;
}
```

Everything under `/assets/` is served from disk, even if a regex like `~* \.png$` also exists. You will see exactly why that matters in [the next lesson on matching priority](/course/nginx-basics/location-matching/matching-priority).

## FAQ

### Do I need the trailing slash in a prefix location?
No, but it changes what matches. `location /img` also matches `/imgabc`, while `location /img/` only matches paths under `/img/`. Use the slash when you mean a folder.

### Which type should I use most?
Plain prefix locations for folders, `=` for single files like `/favicon.ico`, and regex for extension rules. Reach for `^~` only when a prefix must win over a regex.

### Is `location /` a catch-all?
Yes. Since every URL starts with `/`, a plain `location /` matches everything as a fallback. It has the shortest prefix, so any longer match wins over it.
