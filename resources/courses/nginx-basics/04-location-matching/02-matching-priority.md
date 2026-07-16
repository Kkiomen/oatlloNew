---
title: "Matching priority"
slug: matching-priority
seo_title: "Nginx location Priority Order: How Blocks Are Matched"
seo_description: "The exact nginx location priority order: exact = first, then ^~, then regex in file order, then longest prefix. Worked examples that clear up the confusion."
---

When several `location` blocks could match a URL, nginx does not pick the first one in the file. It follows a fixed priority order, and that nginx location priority is where most beginner confusion lives. Learn the sequence once and the "why did the wrong block run" mystery disappears.

## What order does nginx check locations in?

For each request, nginx checks in this sequence:

1. **Exact match `=`**. If a `location = /path` matches the URL exactly, nginx uses it and stops. Nothing else is checked.
2. **Prefix matches**. Nginx finds the longest matching prefix location and remembers it.
3. If that longest prefix has the `^~` modifier, nginx uses it and stops (no regex).
4. **Regex matches `~` / `~*`**. Nginx tries them in the order they appear in the file. The first regex that matches wins.
5. If no regex matched, nginx falls back to the longest prefix it remembered in step 2.

Two things surprise beginners: regex is checked in **file order**, but prefixes are chosen by **length**, not order.

## Worked example

```nginx
server {
    location / { }                    # A
    location /images/ { }             # B
    location = /images/logo.png { }   # C
    location ~* \.png$ { }            # D
}
```

- Request `/images/logo.png`: the exact block **C** wins immediately (step 1).
- Request `/images/photo.png`: no exact match. Longest prefix is **B** (`/images/`), but it has no `^~`, so nginx tries regex. **D** matches `.png`, so **D** wins.
- Request `/images/readme.txt`: no exact, no regex matches `.txt`. Longest prefix is **B**, so **B** wins.
- Request `/about`: only **A** (`/`) matches, so **A** wins.

## Making a prefix beat regex

Say you want everything under `/assets/` served straight from disk, even `.png` files. Add `^~`:

```nginx
location ^~ /assets/ { }   # E
location ~* \.png$ { }     # D
```

Now `/assets/logo.png` matches **E**. Because **E** uses `^~` and is the longest prefix, nginx stops at step 3 and never reaches the regex **D**.

## Common mistake

The order of blocks in your file does **not** decide prefix priority - length does. This config confuses people:

```nginx
location / { root /var/www/a; }        # written first
location /downloads/ { root /var/www/b; }
```

A request for `/downloads/file.zip` is served from `/var/www/b`, even though `location /` is written first. The longer prefix `/downloads/` wins. Writing `location /` first does not make it a fallback that "catches" the URL early.

The opposite trap is with regex: here order **does** matter.

```nginx
location ~ /api/ { }    # F
location ~ \.json$ { }  # G
```

For `/api/data.json`, both match, but **F** wins simply because it is written first. Reorder them and the result changes. When two regex locations overlap, put the more specific one on top.

## FAQ

### Why did my regex block never run?
A `^~` prefix (or an exact `=`) matched first and stopped the search. Check whether a longer prefix location covers the same path.

### Prefix or regex - which is "stronger"?
Neither in general. Exact `=` is strongest, then `^~` prefixes, then regex, then plain prefixes. A regex beats a plain prefix, but a `^~` prefix beats a regex.

### How do I debug which location ran?
Add a distinct [error page or log entry](/course/nginx-basics/configuration-basics/access-and-error-logs) per block, or return a test string with `return 200 "matched B";` temporarily. You will meet `return` in a later lesson.
