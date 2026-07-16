---
title: "Rewrite basics"
slug: rewrite-basics
seo_title: "Nginx rewrite Rules: last, break, redirect, permanent Flags"
seo_description: "How nginx rewrite rules work with regex captures and the last, break, redirect and permanent flags, plus when to use rewrite instead of return."
---

The nginx `rewrite` directive changes the request URL using a regular expression. Unlike `return`, it can capture parts of the URL and reuse them, which makes it the tool for pattern-based URL changes rather than fixed one-to-one moves.

## The nginx rewrite syntax

```nginx
rewrite REGEX REPLACEMENT [flag];
```

Nginx matches the current URL against `REGEX`. If it matches, the URL becomes `REPLACEMENT`. The optional flag controls what happens next.

## A capturing example

Suppose old article URLs looked like `/article/123` and now they are `/posts/123`. You can capture the number and reuse it:

```nginx
rewrite ^/article/(\d+)$ /posts/$1 permanent;
```

- `^/article/(\d+)$` matches `/article/` followed by digits. The parentheses **capture** those digits.
- `$1` in the replacement is the captured value, so `123` is carried over.
- `permanent` makes it a 301 redirect.

A request for `/article/123` becomes a 301 to `/posts/123`.

## The four rewrite flags

The flag decides what nginx does after the rewrite:

- **`last`** - stop this rewrite, then search the `location` blocks again with the new URL. Use this to hand off to another location.
- **`break`** - stop rewriting and keep processing the request in the **current** block with the new URL. No new location search.
- **`redirect`** - send a **302** redirect to the new URL (the browser sees the change).
- **`permanent`** - send a **301** redirect to the new URL.

`last` and `break` rewrite **internally** (the browser URL does not change). `redirect` and `permanent` are visible **external** redirects.

One trap hides in `last`: it restarts the location search with the new URL, so if your rewritten path still matches the same location and rewrite, you build a loop. Nginx cuts it off after 10 rounds and hands back a 500. When you use `last`, make sure the replacement lands somewhere the rule cannot fire again.

## Internal rewrite example

This serves a "pretty" URL from a real file without the browser ever seeing the change:

```nginx
location / {
    rewrite ^/team/(.+)$ /people/$1.html last;
    try_files $uri $uri/ =404;
}
```

A request for `/team/anna` is rewritten to `/people/anna.html`, then nginx re-runs matching and `try_files` serves the file. The user still sees `/team/anna` in the address bar.

## Rewrite vs return

Both can redirect, so which do you use?

- Use **`return`** for simple, fixed redirects. It is clearer and faster (see [redirects with return](/course/nginx-basics/location-matching/redirects-with-return)).
- Use **`rewrite`** when you need a **regex** or need to **capture** parts of the URL, or when you want an **internal** rewrite that the browser never sees.

If you are not capturing anything and the target is a fixed string, prefer `return`.

## FAQ

### What is the difference between last and break?
`last` restarts the location search with the new URL; `break` stops rewriting and stays in the current block. Use `last` to reach another location, `break` to finish where you are.

### Why is my rewrite redirecting the browser when I did not want it to?
You used `redirect` or `permanent`, which are external 301/302 redirects. For an invisible internal change, use `last` or `break` instead.

### Should I always use rewrite for redirects?
No. For simple fixed redirects use `return`, which is faster and clearer. Save `rewrite` for regex and captured URLs.
