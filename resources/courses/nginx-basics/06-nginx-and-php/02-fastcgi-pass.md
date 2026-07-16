---
title: "The fastcgi_pass block"
slug: fastcgi-pass
seo_title: "Nginx fastcgi_pass to PHP-FPM: The Config, Line by Line"
seo_description: "The nginx fastcgi_pass PHP-FPM config, line by line: fastcgi_pass, include fastcgi_params, and why SCRIPT_FILENAME picks the file. Unix socket vs TCP."
---

## What the PHP location block actually does

You know from the last lesson that nginx hands `.php` requests to PHP-FPM. Now you need the actual config that does it: a single `location` block built around `fastcgi_pass`. It has a few directives, and every line has a job. This is the nginx fastcgi_pass PHP-FPM config every PHP site depends on.

## The standard fastcgi_pass block

Here is the standard PHP location. We'll take it apart line by line:

```nginx
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

## Matching .php requests

```nginx
location ~ \.php$ {
```

The `~` makes this a case-sensitive regular expression match, which you saw in [how location matching works](/course/nginx-basics/location-matching/how-location-matching-works). The pattern `\.php$` means "the path ends in `.php`". So `/index.php` and `/admin/login.php` match, but `/style.css` does not.

Regex locations are checked before the prefix `location /`, so PHP requests land here and everything else falls through to your static handling.

## include fastcgi_params

```nginx
include fastcgi_params;
```

FastCGI does not send HTTP the way a browser does. It sends a set of named **parameters**: request method, query string, remote address, content type, and many more. PHP reads these as its `$_SERVER` values.

Nginx ships a file, usually at `/etc/nginx/fastcgi_params`, that defines all the standard ones. The `include` directive (from [includes and sites](/course/nginx-basics/configuration-basics/includes-and-sites)) pulls them in so you don't have to write dozens of `fastcgi_param` lines yourself.

Put the `include` **before** your own `fastcgi_param` lines. Later lines win, so anything you set after the include overrides the defaults. Set it after, and the include would overwrite you.

## fastcgi_pass: where PHP-FPM listens

```nginx
fastcgi_pass unix:/run/php/php8.4-fpm.sock;
```

This is the address of PHP-FPM. It has two forms:

**Unix socket** (shown above). A socket is a special file on the same machine. It is the common choice when nginx and PHP-FPM run on one server, and it is slightly faster because it skips the network stack. The path must match PHP-FPM's own config (`listen = ...` in the pool file). A wrong path gives `502 Bad Gateway`.

**TCP** on localhost or another host:

```nginx
fastcgi_pass 127.0.0.1:9000;
```

Use TCP when PHP-FPM runs somewhere nginx cannot reach by file, most commonly in Docker, where PHP runs in a separate container:

```nginx
fastcgi_pass php:9000;
```

Here `php` is the service name of the PHP-FPM container. Same directive, just a network address instead of a socket path. If you set up PHP-FPM in the Docker course, this is how nginx reaches it.

One trap with the socket form: the path can be perfectly correct and still return `502` if nginx is not allowed to open the socket. PHP-FPM owns that file, and its pool config (`listen.owner`, `listen.group`, `listen.mode`) decides who may connect. On Debian and Ubuntu the defaults already give nginx's user access, but a hand-rolled pool or a custom user is a classic source of a permission-denied `502` that looks exactly like a wrong path.

## SCRIPT_FILENAME: the parameter that picks the file

```nginx
fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
```

This is the line people get wrong, so read it slowly.

PHP-FPM does not receive the file. It receives a **path** and opens that file from its own disk. `SCRIPT_FILENAME` is that path. It tells PHP-FPM exactly which file to execute.

It is built from two nginx variables:

- `$document_root` is your `root` directory, for example `/var/www/html/public`.
- `$fastcgi_script_name` is the script part of the URL, for example `/index.php`.

Joined together that is `/var/www/html/public/index.php`, the real file on disk. Get this right and PHP runs the correct file. Get it wrong and PHP-FPM cannot find the file.

## Common mistake: "File not found" from a broken SCRIPT_FILENAME

**"File not found." (or "Primary script unknown").** This is a broken `SCRIPT_FILENAME`, almost always. PHP-FPM was handed a path that does not point at a real file, so it gave up.

Two frequent causes:

- The `fastcgi_param SCRIPT_FILENAME` line is missing entirely, so PHP-FPM gets an empty path.
- Your `root` is wrong, so `$document_root` points at the wrong folder. Remember: this path is resolved by **PHP-FPM**, so the file must exist where PHP-FPM can see it. In Docker that means the file must be mounted into the PHP container, not only the nginx one.

A second, security-critical mistake is discussed in the working-site lesson: never let nginx pass paths that are not real `.php` files to PHP-FPM.

## FAQ

### Unix socket or TCP, which should I use?

If nginx and PHP-FPM are on the same machine, use a Unix socket. It is a little faster and needs no port. Use TCP when they are on different machines or in different Docker containers, where a socket file is not shared.

### Why does the socket path have a PHP version in it?

Because you can install several PHP versions side by side. `php8.4-fpm.sock` is the socket for PHP 8.4. Match the path to the version you actually run, or you get a `502`.

### What is the difference between fastcgi_pass and proxy_pass?

`proxy_pass` speaks HTTP to a backend that is an HTTP server. `fastcgi_pass` speaks the FastCGI protocol to PHP-FPM, which is not an HTTP server. Use `fastcgi_pass` for PHP.

### Where does fastcgi_params live?

It ships with nginx, usually at `/etc/nginx/fastcgi_params`. You `include` it by that name because nginx already knows its config directory. You rarely edit it.
