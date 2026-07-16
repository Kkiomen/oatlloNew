---
title: "Reading nginx access and error logs"
slug: access-and-error-logs
seo_title: "Nginx Access and Error Logs: Location and Debugging"
seo_description: "Where nginx access and error logs live, what error log levels mean, and how to tail them to debug problems in real time."
---

When something does not work, the logs tell you why. Nginx keeps two: the access and error logs. One records every request it serves, the other records problems. Knowing where these nginx access and error logs live and how to read them turns "the site is broken" into "line 42 says permission denied".

## Where nginx access and error logs live

You already saw both directives in [Config file structure](/course/nginx-basics/configuration-basics/config-file-structure):

```nginx
access_log /var/log/nginx/access.log;
error_log  /var/log/nginx/error.log;
```

- **access_log** records every request that reaches nginx: who asked, for what, and the status code returned.
- **error_log** records nginx's own problems: bad config at runtime, permission issues, upstream failures.

On most Linux installs the default location is `/var/log/nginx/`. If yours differs, the paths in your config (above) are the source of truth.

## Reading a line in the access log

A typical access log line looks like this:

```bash
203.0.113.7 - - [16/Jul/2026:10:22:05 +0000] "GET /about HTTP/1.1" 200 1024 "-" "Mozilla/5.0"
```

Left to right: the visitor's IP, the timestamp, the request line (`GET /about`), the **status code** `200`, and the response size `1024`. The status code is what you scan for. A `404` means the file was not found, a `500` means the server errored. Seeing `200` for the URL you requested confirms nginx served it. One thing the access log will not tell you is *why* a `404` or `500` happened. It records that the request came and how it ended, not the reason. For the reason, you switch to the error log.

## Nginx error log levels explained

The error log is where you look when a request fails. Each line carries a **level** telling you how serious it is. From least to most severe:

```bash
debug  info  notice  warn  error  crit  alert  emerg
```

You set the minimum level to record as a second value on the directive:

```nginx
error_log /var/log/nginx/error.log warn;
```

That records `warn` and everything more severe, and ignores the chatty lower levels. `warn` or `error` is a sensible default. Only turn on `debug` when you are chasing a specific hard problem, because it produces a flood of output.

A real error line looks like:

```bash
2026/07/16 10:25:41 [error] 1234#0: *5 open() "/var/www/html/missing.png" failed (2: No such file or directory)
```

It gives the time, the level `[error]`, and a plain message: nginx tried to open a file that is not there. That is your bug, named directly.

## How to tail nginx logs in real time

The most useful trick is watching a log update in real time. `tail -f` prints new lines as they arrive:

```bash
sudo tail -f /var/log/nginx/error.log
```

Leave it running, reload the failing page in your browser, and watch the new error appear the instant it happens. Do the same with the access log to confirm requests are even reaching nginx:

```bash
sudo tail -f /var/log/nginx/access.log
```

Press `Ctrl+C` to stop watching. This pairing, refresh the page and watch the log, is how most nginx problems get solved.

## Common mistake

Debugging in silence with no log open. You change something, refresh, see the same error, and guess. Instead, run `tail -f` on the error log in one terminal, refresh in the browser, and let nginx tell you exactly what went wrong. The answer is almost always already written there.

## FAQ

### Nothing appears in the access log when I load the page. Why?

That usually means the request never reached this nginx or this server config. Check that nginx is running, that you are hitting the right address and port, and that the site's config is actually loaded (see [Includes and sites](/course/nginx-basics/configuration-basics/includes-and-sites)).

### Can I turn the access log off?

Yes, with `access_log off;` in the relevant context. People do this for very high-traffic sites to save disk writes. Keep it on while you are learning; it is too useful to lose.

### The log file is huge. Is that a problem?

Over time logs grow. Most systems handle this automatically with a tool called logrotate, which archives and trims them. You rarely need to touch it, but it is why yesterday's log may be a `.gz` file next to the current one.
