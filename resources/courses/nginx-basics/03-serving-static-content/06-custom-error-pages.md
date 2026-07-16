---
title: "Custom error pages"
slug: custom-error-pages
seo_title: "Nginx Custom Error Pages: Serve Your Own 404 and 50x"
seo_description: "Set up nginx custom error pages with error_page: your own 404 and 50x HTML, plus an internal location so the file can't be opened directly."
---

The built-in nginx error page is plain and unbranded. With the `error_page` directive you can serve your own HTML for a 404 or a server error instead. This lesson shows how to set up nginx custom error pages, including the small trick that keeps the error file itself private.

## How the error_page directive works

`error_page` maps a status code to a file nginx should serve when that error happens.

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;

    error_page 404 /404.html;
}
```

Now, when nginx would return a 404 Not Found, it serves `/var/www/example/404.html` instead of its built-in page. The path is relative to `root`, just like any other file (see [root and index](/course/nginx-basics/serving-static-content/root-and-index)).

Create the file:

```html
<!doctype html>
<html>
  <body>
    <h1>Page not found</h1>
    <p>Sorry, that page does not exist.</p>
  </body>
</html>
```

## Handling server errors (50x)

Errors in the 500 range mean something went wrong on the server side. You can group several codes onto one page:

```nginx
error_page 500 502 503 504 /50x.html;
```

One `/50x.html` covers all four. This is such a common pattern that the default nginx config already ships with it.

Keep in mind that each status code needs its own `error_page` entry. A `404` line does nothing for a `403 Forbidden`, which is the code you get when a folder has no index file and no directory listing. If you want a custom page there too, add `error_page 403 /403.html;` as a separate line; nginx will not reuse the 404 page for it.

## Making the error page internal

There is a subtle problem. Because `404.html` lives under `root`, a visitor could request `example.com/404.html` directly, as if it were a normal page. Usually you do not want that; the file should only appear as an error response.

The fix is an `internal` location:

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;

    error_page 404 /404.html;

    location /404.html {
        internal;
    }
}
```

`internal` tells nginx: "only serve this from an internal redirect, never from a direct request." So nginx can still show it as your 404 page, but a visitor typing `/404.html` gets a real 404. The full details of location matching come in the [next chapter](/course/nginx-basics/location-matching/how-location-matching-works), so just copy this pattern for now.

## A complete example

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/example;
    index index.html;

    error_page 404 /404.html;
    error_page 500 502 503 504 /50x.html;

    location /404.html {
        internal;
    }

    location /50x.html {
        internal;
    }
}
```

Reload nginx after saving, and remember to test first with `nginx -t`, as covered in [Testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely).

## Common mistake

Pointing `error_page` at a file that does not exist. If nginx cannot find your `404.html`, it cannot show it, and you end up with a confusing error about the error. Double-check the file is really under `root` at the path you named.

## FAQ

### Does the error page keep the right status code?

Yes. Visitors still receive a real 404 or 500 status; only the body is replaced with your HTML. Search engines and tools still see the correct code.

### Why use internal for the error file?

So people cannot open your error page as if it were a normal page. It should only show up as a genuine error response.

### Can I set error pages once for the whole server?

Yes. Put `error_page` in the `http` block to apply it to every site, or in a single server block for just that site.
