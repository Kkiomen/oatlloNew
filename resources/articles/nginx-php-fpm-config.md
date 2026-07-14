---
name: "Configuring Nginx for PHP-FPM in Production"
slug: nginx-php-fpm-config
short_description: "A practical nginx php-fpm config guide for production: server block, security, gzip, caching, and PHP-FPM pool tuning that actually holds up."
language: en
published_at: 2027-02-10 09:00:00
is_published: true
tags: [nginx, php-fpm, devops, laravel]
---

The first time I shipped a PHP app to a bare VPS, I copied an nginx php-fpm config off a random gist, restarted the service, and called it done. It worked. Then a bot found `/.env` two weeks later and the "it works" feeling evaporated fast.

This guide is the config I wish I'd had back then. It covers a full production `server` block for a PHP application (Laravel-style, but the shape is the same for Symfony or a plain framework), the security rules that keep you off breach forums, and the PHP-FPM pool tuning that decides whether your box falls over at 200 concurrent requests or shrugs. Every directive here is one I've run in production, not something I copied hoping it was real.

## How Nginx and PHP-FPM actually talk

Nginx doesn't run PHP. It never has. When a request comes in for a `.php` file, nginx hands it off to a separate process, PHP-FPM (FastCGI Process Manager), over a socket, waits for the rendered output, and streams that back to the client.

That handoff is the whole game. Two independent programs, one connection between them. Most production headaches (502s, permission errors, mysterious hangs) live at that boundary. So it helps to hold a clear picture: nginx is the front door and traffic cop; PHP-FPM is the pool of workers that actually execute your code.

The connection between them is either a **Unix socket** (a file like `/run/php/php8.3-fpm.sock`) or a **TCP socket** (`127.0.0.1:9000`). On a single box, the Unix socket is marginally faster and I default to it. If nginx and PHP-FPM live on different machines, you need TCP.

## A production server block, top to bottom

Here's a complete `server` block for a PHP app served over HTTPS. I'll break down the parts that matter after.

```nginx
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name example.com;

    root /var/www/example.com/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/example.com/privkey.pem;

    server_tokens off;
    client_max_body_size 20m;

    # All non-file requests get routed through the front controller
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Hand PHP off to FPM
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT   $realpath_root;
        fastcgi_read_timeout 60s;
    }

    # Block access to hidden files and dotfiles (.env, .git, etc.)
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

A few things I want to call out, because they're the lines people get subtly wrong.

**`root` points at `public/`, not the project root.** This is non-negotiable for any modern PHP framework. Your app code, `.env`, vendor directory, and config all sit *above* the web root, so nginx physically cannot serve them. If you set `root` to the project directory instead, you've just published your entire source tree.

**`try_files $uri $uri/ /index.php?$query_string`** is the front-controller pattern. Nginx checks if the request maps to a real file, then a real directory, and if neither exists, it falls through to `index.php` with the query string preserved. That last part, `?$query_string`, is easy to forget, and when you do, your route params silently vanish.

**`SCRIPT_FILENAME $realpath_root$fastcgi_script_name`** tells PHP-FPM which file to execute. Using `$realpath_root` (which resolves symlinks) instead of `$document_root` matters a lot if you deploy with symlinked release directories, like Deployer or Envoyer do. Get this wrong and you'll see opcache serving stale code from the previous release. I lost an afternoon to exactly that.

**`try_files $uri =404;` inside the PHP location** is a security line, not a convenience. Without it, a crafted request like `/uploads/evil.jpg/x.php` can trick nginx into passing a non-PHP file to FPM for execution. The `=404` says: if this exact `.php` file doesn't exist on disk, refuse the request.

## Locking it down

The config above already does the two biggest things, root at `public/` and `deny all` on dotfiles, but production needs a bit more.

### Stop PHP from executing in upload directories

If your app accepts file uploads, assume an attacker will try to upload a `.php` file and then request it. Deny PHP execution anywhere users can write:

```nginx
location ~* /(?:uploads|files|storage)/.*\.php$ {
    deny all;
}
```

Place this **before** your `location ~ \.php$` block. Nginx regex locations match in order, so the first hit wins.

### Turn off the version banner

`server_tokens off;` (already in the block above) stops nginx from advertising its exact version in response headers and error pages. It won't stop a determined attacker, but it removes the free reconnaissance that lets a scanner match your version against a CVE list in one pass.

### Actually block the sensitive paths

The dotfile rule catches `.env`, `.git`, `.htaccess` and friends. Note the `(?!well-known)` negative lookahead. That keeps `/.well-known/` reachable, which you need for ACME/Let's Encrypt renewals and things like Apple app-site associations. Deny everything hidden *except* that.

One quick test after deploying: open `https://yoursite.com/.env` in a browser. You want a 403. If you get your actual env contents, stop reading and fix it right now.

## Performance without cargo-culting

Three levers give you most of the wins. I'll skip the ones that sound impressive but rarely move the needle.

### Compression

```nginx
gzip on;
gzip_vary on;
gzip_comp_level 5;
gzip_min_length 1024;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml image/svg+xml;
```

Don't crank `gzip_comp_level` to 9. The extra CPU per request buys you a rounding error of bandwidth. Level 5 is the sweet spot I keep landing on. And `gzip_min_length 1024` avoids the silliness of compressing tiny responses where the overhead costs more than it saves.

If you can, add Brotli via `ngx_brotli` for static assets. It compresses better than gzip for text. But it's a compiled module, so only bother if your nginx build already has it.

### Cache static assets hard

Your CSS, JS, fonts, and images don't change between deploys (assuming you fingerprint filenames, which your build tool does). Tell browsers to keep them:

```nginx
location ~* \.(?:css|js|woff2?|png|jpe?g|gif|svg|ico|webp)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
    try_files $uri =404;
}
```

`immutable` is the underrated one here: it tells the browser not to even send a revalidation request on reload. Combined with a fingerprinted filename, that's a static asset the browser never asks about twice.

This is also one of the cheapest ways to shave server response time. If you're chasing that specifically, I wrote a whole piece on it: [how to reduce TTFB](/blog/reduce-ttfb).

### Timeouts and keepalive

```nginx
fastcgi_read_timeout 60s;
keepalive_timeout 65s;
```

`fastcgi_read_timeout` is how long nginx waits for PHP-FPM to respond before giving up with a 504. The default 60s is fine for web requests. If you have a genuinely slow endpoint (a report export, say), bump it *for that location only*. Don't raise it globally, or a hung worker ties up a connection for minutes. Better yet, push slow work to a queue. This is closely related to PHP's own `max_execution_time`; if you're hitting [PHP maximum execution time exceeded](/blog/php-maximum-execution-time-exceeded) errors, the two settings interact and you'll want both in view.

## Tuning the PHP-FPM pool

This is the part that gets skipped, and it's the part that decides how many concurrent requests your server survives. Pool config lives in a file like `/etc/php/8.3/fpm/pool.d/www.conf`.

The critical knob is `pm` (process manager mode) and its child counts:

```ini
pm = dynamic
pm.max_children = 20
pm.start_servers = 4
pm.min_spare_servers = 2
pm.max_spare_servers = 8
pm.max_requests = 500
```

**`pm` has three modes:**

- `dynamic`: FPM keeps a floating number of workers between the spare bounds, spawning more under load. Good default for most apps.
- `static`: a fixed number of workers, always running. Lowest latency, highest baseline RAM. Worth it on a dedicated box under steady heavy load.
- `ondemand`: workers spawn only when needed and die after idle timeout. Great for low-traffic sites or many small apps sharing a box, at the cost of a cold-start penalty on the first request.

**Sizing `pm.max_children` is the one number people guess at.** Don't guess. Measure the real memory a single worker uses, then divide the RAM you're willing to give PHP by that number:

```bash
# Peak RSS per FPM worker, in MB
ps --no-headers -o rss -C php-fpm8.3 | sort -n | tail -1 | awk '{print $1/1024 " MB"}'
```

Say each worker peaks around 60 MB and you have a 4 GB box where you'll let PHP use ~2.5 GB (leaving room for nginx, the database, and the OS). That's `2500 / 60 ≈ 41`, so `pm.max_children = 40` is a sane ceiling. Set it too high and a traffic spike swaps your machine into oblivion; too low and requests queue behind a wall of busy workers while your CPU sits idle.

**`pm.max_requests = 500`** recycles a worker after it's handled 500 requests. This is your cheap insurance against slow memory leaks in extensions or long-lived state: the worker restarts fresh before it bloats. It costs almost nothing and I set it on every pool.

## Pitfalls I've actually hit

- **`root` at the project directory instead of `public/`.** The classic. You'll serve `.env` and your whole source tree to anyone who asks. Always point at `public/`.
- **502 Bad Gateway right after setup.** Nearly always a socket mismatch: the `fastcgi_pass` path in nginx doesn't match `listen` in the FPM pool, or the PHP version in the socket name is wrong (`php8.3-fpm.sock` vs `php8.2-fpm.sock`). Check both files agree.
- **502 from permissions.** The FPM socket is owned by a user (often `www-data`) and nginx runs as another. Match `listen.owner` / `listen.group` in the pool to the nginx user, or you'll get permission-denied on the socket.
- **Forgetting `?$query_string` in `try_files`.** Everything looks fine until you notice query params never reach your controllers. Silent and infuriating.
- **`pm.max_children` set to a hopeful round number.** 50 workers at 80 MB each is 4 GB of RAM you may not have. Size it against measured worker memory, not vibes.
- **Editing config and forgetting to reload.** `nginx -t` then `systemctl reload nginx`, and `systemctl reload php8.3-fpm` for pool changes. A reload that never happened is the bug behind more "but I changed it!" moments than I'd like to admit.

## FAQ

### Unix socket or TCP for fastcgi_pass?

On a single server, use the Unix socket (`unix:/run/php/php8.3-fpm.sock`). It skips the TCP stack and is a touch faster with no downside. Switch to TCP (`127.0.0.1:9000`) only when nginx and PHP-FPM run on separate hosts, or when you're load-balancing across multiple FPM machines.

### Why do I get 502 Bad Gateway after everything looks correct?

In order of likelihood: the socket path in `fastcgi_pass` doesn't match the FPM pool's `listen`; the PHP version in the socket name is wrong; the socket file's owner/group doesn't match the nginx user; or PHP-FPM simply isn't running. Check `systemctl status php8.3-fpm` and tail `/var/log/nginx/error.log`, where the real reason is almost always printed.

### How many pm.max_children should I set?

Divide the RAM you'll allocate to PHP by the peak memory of one worker. Measure the worker with `ps`, don't assume. A 4 GB box giving PHP 2.5 GB with 60 MB workers lands around 40. Leave headroom for nginx, your database, and the OS.

### Do I still need this if I use Docker?

Yes. The nginx and PHP-FPM config is identical whether the processes run on the host or in containers; only the socket/networking between them changes (usually TCP over the Docker network). If that's your setup, see [dockerizing Laravel for production](/blog/dockerize-laravel-production) for how the two containers wire together.

## Wrapping up

A production nginx php-fpm config comes down to four things done right: `root` at `public/` with the front-controller `try_files`, a `location ~ \.php$` block that passes real files to FPM over the correct socket, hard denials on dotfiles and upload-dir PHP, and a pool whose `pm.max_children` is sized against measured memory.

Copy the `server` block above, adjust the paths and PHP version to your box, run `nginx -t`, reload both services, and then — before you close the terminal — request `/.env` in a browser and confirm you get a 403. That one check would have saved me two weeks and a lot of embarrassment.