---
title: "A troubleshooting checklist"
slug: troubleshooting-checklist
seo_title: "Nginx Troubleshooting: 502, 504, 403, 404 and Redirect Loops"
seo_description: "Read nginx errors like a pro: fix 502/504, 403, 404, configs that never applied, and redirect loops using nginx -t and the error log. A practical checklist."
---

## An nginx troubleshooting checklist that starts with the signal

Something is broken. A page shows the wrong thing, an error code, or nothing at all. Good nginx troubleshooting means reading the signal nginx is already giving you instead of guessing and reloading in circles. Almost every problem falls into a handful of buckets, and each bucket has a tell.

This lesson is that checklist. Start at the top every time.

## First: is the config even valid and applied?

Before debugging behavior, rule out the two things that fool everyone.

```bash
sudo nginx -t
```

`nginx -t` parses your config without touching the running server, from [testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely). It either says `syntax is ok` / `test is successful`, or it prints the exact file and line of the problem. Fix that line first, everything else is noise until it passes.

Then remember: **editing a config file changes nothing until you reload.**

```bash
sudo systemctl reload nginx
```

Reload re-reads the config on a running server, from [start, stop, reload](/course/nginx-basics/getting-started/start-stop-reload). If a change "did not work," nine times out of ten it was never loaded. Test, then reload, every time.

## Read the error log, always

The error log is where nginx explains itself, from [access and error logs](/course/nginx-basics/configuration-basics/access-and-error-logs). Watch it live while you reproduce the problem:

```bash
sudo tail -f /var/log/nginx/error.log
```

Then load the broken page in another window. The line that appears is usually the answer: a permission denied, a missing file with its full path, or a connection refused to a backend. Do not debug blind when the log is right there.

## 502 Bad Gateway: the backend is not answering

A 502 means nginx tried to reach a backend (PHP-FPM or your Node/upstream app) and got nothing usable.

Check, in order:

- **Is the backend running?** `systemctl status php8.4-fpm` for PHP, or check your Node process. A stopped backend is the most common cause.
- **Is the address right?** The `fastcgi_pass` socket path (from [the fastcgi_pass block](/course/nginx-basics/nginx-and-php/fastcgi-pass)) or the `proxy_pass` target (from [proxy_pass](/course/nginx-basics/reverse-proxy/proxy-pass)) must match where the backend actually listens. A wrong socket path or port gives an instant 502.
- **Permissions on the socket.** For a Unix socket, the nginx user must be allowed to read it. The error log will say `permission denied` on the socket path.

The error log line for a 502 usually contains `connect() failed` or `connect() to ... failed`, which points straight at the address.

One 502 catches people on RHEL, CentOS, Rocky, and Fedora: the paths and permissions all look right, but the log says `permission denied` on a socket that is world-readable. That is SELinux blocking nginx from talking to the backend, not a file permission at all. `setsebool -P httpd_can_network_connect 1` fixes it. On Debian and Ubuntu you will not hit this, which is exactly why it is so confusing when you move a working config to a Red Hat box.

## 504 Gateway Timeout: the backend is too slow

A 504 is different from a 502. Nginx *did* reach the backend, but the backend took too long to respond and nginx gave up.

- The backend is stuck on a slow query, an external API call, or a heavy report.
- Or `proxy_read_timeout` / `fastcgi_read_timeout` is set too low for a legitimately long request.

The real fix is usually to make the request faster or move the work to a background job, not to raise the timeout forever. But you can raise it if the request is genuinely long:

```nginx
proxy_read_timeout 120s;
```

A tell for reading these: if the page dies at almost exactly 60 seconds, that is nginx's default timeout cutting the cord, not your app failing on its own. Raise the nginx timeout and you may then hit PHP's `max_execution_time` a bit later, or Node's own server timeout. The layers stack, so a real fix often means lifting more than one ceiling, which is another reason background jobs beat long requests.

## 403 Forbidden: nginx refuses to serve it

A 403 means nginx found the request but will not serve it. Three usual causes:

- **File permissions.** The nginx user cannot read the file or traverse into the folder. The error log says `permission denied`. Fix ownership and permissions so nginx can read the path.
- **No index file.** A request for a directory (like `/`) with no `index` file and no `autoindex on` returns 403, because nginx has nothing to show and will not list the folder. This is the behavior from [autoindex](/course/nginx-basics/serving-static-content/autoindex).
- **An explicit `deny`.** An access rule from [access control](/course/nginx-basics/security-basics/access-control) is blocking the request, for example the dotfile rule that hides `.env`. Check whether you accidentally denied something legitimate.

## 404 Not Found: nginx looked in the wrong place

A 404 means nginx resolved a path and no file was there. This is almost always `root` or `try_files`.

- **Wrong `root`.** If `root` points at the wrong folder, every path 404s. Print the real path nginx is building: it is `root` + the URI. Compare that to where the file actually is. `root` is from [root and index](/course/nginx-basics/serving-static-content/root-and-index).
- **`try_files` fallback.** A `try_files $uri $uri/ =404;` returns 404 when nothing matches, which is correct for a static site but wrong for an app that should route unknown paths to a front controller. For Laravel the last argument is `/index.php?$query_string`, not `=404`. Both from [try_files](/course/nginx-basics/location-matching/try-files).
- **Trailing slash or matching.** The wrong `location` may be handling the request. Review [matching priority](/course/nginx-basics/location-matching/matching-priority) to confirm which block wins.

## Config change did not apply

If behavior did not change after an edit:

- **You forgot to reload.** Run `nginx -t` then `systemctl reload nginx`. This is the number-one cause.
- **You edited a file nginx does not include.** A file in `sites-available` does nothing until it is symlinked into `sites-enabled` (or included by `conf.d`), from [includes and sites](/course/nginx-basics/configuration-basics/includes-and-sites). Check that your file is actually pulled in.
- **The browser cached it.** Especially with `expires 1y` on assets (from [caching static assets](/course/nginx-basics/performance-and-caching/caching-static-assets)), your browser may serve the old file. Hard-refresh or test with `curl -I` to see nginx's real response without the browser cache.

## Redirect loop (ERR_TOO_MANY_REDIRECTS)

The browser bounces forever between two URLs. The cause is almost always HTTPS confusion behind a proxy.

- **The app does not know it is on HTTPS.** Nginx terminates TLS and sends plain HTTP to the backend, so the app thinks the request is insecure, redirects to `https://`, which hits nginx, which sends plain HTTP again, forever. The fix is `proxy_set_header X-Forwarded-Proto $scheme;` from [proxy headers](/course/nginx-basics/reverse-proxy/proxy-headers), plus telling your framework to trust it (Laravel's `TrustProxies`, Express's `trust proxy`).
- **A redirect pointing at itself.** A `return 301` (from [redirects with return](/course/nginx-basics/location-matching/redirects-with-return)) whose target matches the same block loops. Make sure your HTTP-to-HTTPS redirect lives in the port-80 block only, not the 443 block.

Trace it with curl, which shows each hop:

```bash
curl -IL https://example.com
```

## The 30-second checklist

Run this top to bottom before anything else:

```bash
sudo nginx -t                          # 1. is the config valid?
sudo systemctl reload nginx            # 2. did you actually reload?
sudo tail -f /var/log/nginx/error.log  # 3. what does nginx say?
curl -I https://example.com            # 4. what does nginx really return?
systemctl status php8.4-fpm            # 5. is the backend alive?
```

Most problems reveal themselves in these five commands. The error log (step 3) is the single most useful one; keep it open while you reproduce the issue.

## FAQ

### 502 or 504, what is the difference?

A 502 means nginx could not get a valid response from the backend at all (it is down or the address is wrong). A 504 means nginx reached the backend but it did not answer in time (it is too slow). Down versus slow.

### The site works but my config change did nothing.

You almost certainly did not reload, or you edited a file that is not included. Run `nginx -t` then `systemctl reload nginx`, and confirm your file is symlinked into `sites-enabled` or covered by an `include`.

### How do I see nginx's real response without my browser caching it?

Use `curl -I https://example.com`. It sends one request and prints only the response headers, with no cache. Add `-L` to follow redirects and reveal loops.

### Where is the error log if the default path is empty?

Check the `error_log` directive in your `nginx.conf` and in the specific `server` block; a site can log to its own file. If nginx failed to start at all, the reason is usually printed by `nginx -t` or `journalctl -u nginx` instead.
