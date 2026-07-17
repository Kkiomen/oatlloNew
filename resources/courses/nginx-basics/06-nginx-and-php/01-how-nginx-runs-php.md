---
title: "How nginx runs PHP"
slug: how-nginx-runs-php
seo_title: "How Nginx Runs PHP with PHP-FPM (FastCGI)"
seo_description: "How nginx runs PHP with PHP-FPM: nginx never executes PHP, it passes .php requests to PHP-FPM over FastCGI. Follow the full request flow, step by step."
---

## Why nginx serves raw PHP source

You point nginx at a folder with an `index.php` file, open it in the browser, and instead of a running page you get the raw PHP source, or a download prompt. Nothing executed.

That is not a bug. Understanding how nginx runs PHP starts right here: nginx is a web server, not a PHP interpreter. It never runs your PHP code. To turn a `.php` file into a page, something else has to, and nginx hands the job to PHP-FPM over FastCGI.

## Nginx does not execute PHP

For static content, nginx does everything itself. A request for `style.css` maps to a file on disk (with `root` and `index` from [root and index](/course/nginx-basics/serving-static-content/root-and-index)), and nginx reads the bytes and sends them back.

PHP is different. A `.php` file is a program. To turn it into a page, someone has to run the PHP engine, execute the code, and produce HTML. Nginx has no PHP engine built in and never will. So it delegates.

The program that runs PHP is **PHP-FPM** (FastCGI Process Manager). It is a separate service that keeps a pool of PHP worker processes ready. Nginx talks to it over a protocol called **FastCGI**.

## FastCGI: how nginx talks to PHP-FPM

FastCGI is a simple, long-lived protocol for a web server to ask a separate process to handle a request.

You already saw nginx forward requests to an app in [what is a reverse proxy](/course/nginx-basics/reverse-proxy/what-is-a-reverse-proxy). FastCGI is the same idea with a different language on the wire. A reverse proxy speaks HTTP to the backend. FastCGI speaks the FastCGI protocol to PHP-FPM.

The difference matters:

- **proxy_pass** sends an HTTP request to something that is itself an HTTP server (a Node or Python app).
- **fastcgi_pass** sends a FastCGI request to PHP-FPM, which is not an HTTP server. It only speaks FastCGI.

So you do not use `proxy_pass` for PHP. You use `fastcgi_pass`. That is [the next lesson](/course/nginx-basics/nginx-and-php/fastcgi-pass).

## The PHP request flow, step by step

Here is what happens when a browser asks for `http://example.com/contact.php`:

```text
Browser  ->  nginx  ->  PHP-FPM  ->  runs contact.php
Browser  <-  nginx  <-  PHP-FPM  <-  HTML output
```

1. The browser sends `GET /contact.php` to nginx on port 80.
2. Nginx matches the request against its `location` blocks. A `.php` request matches the PHP location.
3. Instead of reading the file, nginx opens a FastCGI connection to PHP-FPM and passes along details: which file to run, the query string, the request method, headers, and so on.
4. PHP-FPM picks a free worker, runs `contact.php` with the PHP engine, and the script produces output (usually HTML).
5. PHP-FPM sends that output back to nginx over FastCGI.
6. Nginx wraps it in an HTTP response and sends it to the browser.

The key line is step 3: nginx does not open the `.php` file itself. It tells PHP-FPM *which* file to run and lets PHP-FPM do the running.

## Where PHP-FPM lives

PHP-FPM is its own service, started separately from nginx. On a Debian or Ubuntu box it is a package like `php8.4-fpm`, managed with `systemctl`:

```bash
sudo systemctl status php8.4-fpm
```

Nginx reaches it in one of two ways, both covered next lesson:

- A **Unix socket**, a special file like `/run/php/php8.4-fpm.sock`.
- A **TCP port** on localhost, like `127.0.0.1:9000`.

In [Docker](/course/docker-basics), PHP-FPM usually runs in its own container and nginx reaches it over TCP by service name. The idea is identical; only the address changes.

Because the two are separate services, they restart separately. Reloading nginx does not touch PHP-FPM, and a new `.env` or a changed opcache setting needs `systemctl reload php8.4-fpm`, not `reload nginx`. People chase a "stale config" bug for a long time before noticing they kept reloading the wrong service.

## Common mistake: raw source instead of a page

**Seeing PHP source or a download instead of a page.** That is the classic symptom of the request never reaching PHP-FPM. Either there is no `location` block for `.php` at all, or PHP-FPM is not running. Nginx fell back to treating the `.php` file as a plain static file and served it as text. The fix is a working PHP location plus a running PHP-FPM service, both built in the coming lessons.

## FAQ

### Do I need Apache too?

No. Apache is an alternative web server that can run PHP with its own module. With nginx you use PHP-FPM instead. You do not run both.

### Is PHP-FPM the same as the `php` command line?

No. `php` on the command line runs a script once and exits. PHP-FPM is a long-running service with a pool of workers waiting for FastCGI requests from a web server. Same engine, different way of being used.

### Why not just let nginx run PHP directly?

Keeping them separate is the point. Nginx stays a small, fast front door, and PHP-FPM manages PHP processes with its own tuning. Each does one job well, and you can restart or scale them independently.
