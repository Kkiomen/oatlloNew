---
title: "Caching static assets"
slug: caching-static-assets
seo_title: "Nginx Cache Static Assets with expires and Cache-Control"
seo_description: "Cache static assets in nginx so browsers reuse CSS, JS, and images. Set expires and Cache-Control, pick safe cache times, and handle cache-busting."
---

Every time someone visits a page, their browser downloads the CSS, JavaScript, and images. Those files rarely change, so pulling them again on every visit is wasted work. When you cache static assets in nginx, you tell browsers to keep a local copy for a while. Return visits load almost instantly, and your server answers fewer requests.

## Set a long expiry on static files

Match your static files by extension and give them a long cache time. Add this inside a `server` block:

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example/public;

    location ~* \.(css|js|jpg|jpeg|png|gif|webp|svg|ico|woff2)$ {
        expires 30d;
        add_header Cache-Control "public";
    }
}
```

The `location ~* \.(...)$` block matches those file extensions, case-insensitively. If location matching is new, see [how location matching works](/course/nginx-basics/location-matching/how-location-matching-works).

## What the expires and Cache-Control headers do

- `expires 30d;` makes nginx send an `Expires` header and a matching `Cache-Control: max-age=2592000` (30 days in seconds). The browser will reuse the file for that long without asking the server.
- `add_header Cache-Control "public";` marks the file as cacheable by browsers and shared caches like a CDN. `add_header` was covered in [basic TLS hardening](/course/nginx-basics/https-tls/basic-tls-hardening).

You can check it worked:

```bash
curl -I https://example.com/assets/app.css
```

Look for `Cache-Control: max-age=2592000` and an `Expires` date in the response.

## The cache-busting problem

Long cache times create one issue: if you edit `app.css` but the filename stays the same, returning visitors keep the old cached copy for 30 days. They will not see your change.

The fix is to change the filename whenever the content changes. This is called cache-busting. Build tools do it automatically by adding a content hash:

```text
app.a1b2c3d4.css
```

New content means a new filename, so the browser is forced to download it. Old files keep their long cache safely. Most bundlers (Vite, webpack, Laravel Mix) produce hashed filenames out of the box. Because of this, a 30-day or even 1-year cache is safe for hashed assets.

Once your filenames are hashed, you can go further and add `immutable` to the header: `add_header Cache-Control "public, immutable";`. That tells the browser the file will never change at this URL, so it skips the revalidation request it would otherwise fire on a hard refresh. For a page with dozens of assets, that is dozens of round trips saved. Only use `immutable` on hashed files, never on ones that keep the same name.

## Common mistake: caching HTML like an asset

Do not put `.html` in that long-cache block, and do not cache the page itself for 30 days. HTML is your entry point. It usually links to the current asset filenames. If the HTML is stale, visitors get old links and never pick up new content. Let HTML be fetched fresh (or cached for only seconds), and keep long caching for the hashed assets it points to.

## FAQ

### How long should I cache?

For hashed filenames, up to a year is fine (`expires 1y;`). For files that keep the same name, be cautious. A day or less avoids showing stale content too long.

### What is the difference between Expires and Cache-Control?

They do the same job. `Cache-Control: max-age` is the modern header; `Expires` is the older one. The `expires` directive sends both, so every browser understands at least one.

### My asset changed but visitors still see the old one. Why?

Their browser is honoring your cache time. Use hashed filenames so the URL changes when the content does. That is the only reliable fix.
