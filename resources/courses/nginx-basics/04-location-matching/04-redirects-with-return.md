---
title: "Redirects with return"
slug: redirects-with-return
seo_title: "Nginx Redirect With return 301 and 302: A Beginner Guide"
seo_description: "Set up an nginx redirect with return 301 and 302 - move a single path or a whole domain, keep the query string, and know why return beats rewrite."
---

A redirect tells the browser "this page lives at a new URL, go there instead." The simplest and fastest way to set up an nginx redirect is the `return` directive: one line, no regular expression, and processing stops the moment it runs.

## 301 vs 302: which redirect should you use?

There are two redirect types you will use constantly:

- **301** is a **permanent** redirect. Search engines update their index to the new URL. Use it when the move is for good.
- **302** is a **temporary** redirect. Use it for short-lived moves, like sending users to a maintenance page.

Pick 301 unless you truly mean "this is temporary." Getting this wrong affects SEO.

## Redirecting a single path

Say `/old-page` moved to `/new-page`. Add a location that returns a 301:

```nginx
location = /old-page {
    return 301 /new-page;
}
```

The `=` makes it an exact match (from the matching lessons), so only `/old-page` is redirected. The browser receives a 301 and requests `/new-page`.

The `=` is doing more work than it looks. Swap it for a plain `location /old-page` and you have a prefix match, so `/old-page-archive` and `/old-page/comments` get swept into the same redirect. For a single moved URL, exact match keeps the blast radius to exactly one path.

## Redirecting a whole domain

A very common task: send `www.example.com` to `example.com` (or the reverse). You do it with a dedicated `server` block.

```nginx
server {
    listen 80;
    server_name www.example.com;
    return 301 http://example.com$request_uri;
}
```

`$request_uri` is the full original path and query string, so `www.example.com/blog?page=2` redirects to `example.com/blog?page=2`. The path is preserved, not lost.

You can point an old domain at a new one the same way:

```nginx
server {
    listen 80;
    server_name old-domain.com;
    return 301 http://new-domain.com$request_uri;
}
```

## Why return, not rewrite

You may have seen redirects written with `rewrite`. For simple cases, `return` is preferred:

- It is **clearer**. The intent ("redirect to X") is obvious on one line.
- It is **faster**. There is no regular expression to evaluate.
- It **stops processing** immediately, so there is no risk of another directive running afterwards.

Reach for `rewrite` only when you need pattern matching or to capture parts of the URL, which is the next lesson.

## FAQ

### Should I use 301 or 302?
Use 301 for permanent moves (the default choice) and 302 only when the change is temporary. Browsers and search engines cache 301s aggressively, so do not use one while testing.

### Why did my redirect lose the path or query string?
You wrote a fixed target instead of appending `$request_uri`. Use `return 301 http://example.com$request_uri;` to keep the original path and query.

### Do I need a separate server block to redirect a domain?
Yes. Domain redirects match on `server_name`, so the source domain needs its own `server` block that returns to the target.
