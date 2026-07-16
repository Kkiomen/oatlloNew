---
title: "Nginx 403 Forbidden - Causes and Fix"
slug: 403-forbidden
seo_title: "Nginx 403 Forbidden - Causes and How to Fix It"
seo_description: "Fix nginx 403 Forbidden: file permissions, missing index file with autoindex off, deny rules, or a wrong root. Diagnose with the error log."
---

## What the error looks like

The browser shows:

```text
403 Forbidden
nginx/1.24.0
```

The error log tells you which cause it is. A permissions problem looks like this:

```text
2026/07/16 09:14:02 [error] 1234#0: *3 open() "/var/www/html/index.html" failed (13: Permission denied), client: 1.2.3.4, server: example.com, request: "GET / HTTP/1.1"
```

A missing index file looks like this:

```text
2026/07/16 09:15:40 [error] 1234#0: *4 directory index of "/var/www/html/" is forbidden, client: 1.2.3.4, server: example.com
```

## Why it happens

403 means nginx found the request but refuses to serve it. The common causes:

- **Filesystem permissions.** The nginx user (`www-data` or `nginx`) cannot read the file, or cannot traverse (enter) a directory in the path. Note the `(13: Permission denied)` in the log.
- **Missing index file with autoindex off.** The URL points at a directory, there is no `index.html` or `index.php`, and directory listing is off. See [root and index](/course/nginx-basics/serving-static-content/root-and-index).
- **A `deny` rule.** An access-control block is blocking the client. See [access control](/course/nginx-basics/security-basics/access-control).
- **Wrong `root`.** Nginx is looking in the wrong folder, so it lands somewhere it cannot serve.

## How to fix it

1. Read the error log to see which cause it is:

```bash
sudo tail -f /var/log/nginx/error.log
```

2. For a permissions error, check the file and its whole directory path. Every directory in the path needs the execute bit so nginx can enter it:

```bash
namei -l /var/www/html/index.html
```

Fix ownership and permissions so nginx can read the files:

```bash
sudo chown -R www-data:www-data /var/www/html
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;
```

3. For "directory index is forbidden", add an index file, or point the request at a real file, or turn on [autoindex](/course/nginx-basics/serving-static-content/autoindex) if you actually want a listing.

4. For a `deny` rule, check your `location` and `server` blocks for `deny` and `allow` lines and confirm your client is not blocked.

5. Confirm `root` points at the real directory, then reload:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

## Common mistake

Fixing permissions on the file but not the parent directories. If any folder in the path lacks the execute bit for the nginx user, you still get 403. `namei -l` shows exactly where it breaks.

The classic real-world version of this: serving a site out of a home directory like `/home/deploy/app/public`. A home directory is usually `700`, so nginx cannot even enter it, and no amount of `chmod` on the files inside will help. Either move the site under `/var/www`, or give the home directory the execute bit (`chmod o+x /home/deploy`).

## FAQ

### Why 403 and not 404?

404 means nginx could not find the file. 403 means it found it but is not allowed to serve it. A 403 points at permissions, a `deny` rule, or a missing directory index, not a missing file.

### My files are 777 and I still get 403. Why?

Then it is usually not the file itself. Check the directories above it (they need execute), check for a `deny` rule, and check whether the request lands on a directory with no index file.

### Does SELinux cause 403?

It can. On Red Hat systems SELinux can block nginx from reading files even when normal permissions look fine. Check `sudo tail /var/log/audit/audit.log` and set the right file context if so.
