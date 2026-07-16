---
title: "Nginx Shows Welcome Page Instead of My Site - Fix"
slug: welcome-page-instead-of-my-site
seo_title: "Nginx Shows Default Welcome Page Instead of My Site - Fix"
seo_description: "Fix nginx showing the default Welcome to nginx page: disable the default site, symlink your config into sites-enabled, match server_name, and reload."
---

## What the error looks like

You visit your domain and instead of your site you get the stock page:

```text
Welcome to nginx!
If you see this page, the nginx web server is successfully installed and
working. Further configuration is required.
```

## Why it happens

Nginx is running, but it is not using your site config. It falls back to the default one. The usual causes:

- **The default server block is still enabled.** On Debian and Ubuntu it lives at `/etc/nginx/sites-enabled/default` and serves that welcome page.
- **Your site config is not symlinked into `sites-enabled`.** You wrote it in `sites-available` but never enabled it. See [includes and sites](/course/nginx-basics/configuration-basics/includes-and-sites).
- **`server_name` does not match.** Your block exists but its `server_name` does not match the domain you typed, so the default block answers instead. See [listen and server_name](/course/nginx-basics/serving-static-content/listen-and-server-name).
- **You forgot to reload.** The config is right on disk but nginx is still running the old one.

## How to fix it

1. Remove the default site's symlink so it stops answering:

```bash
sudo rm /etc/nginx/sites-enabled/default
```

2. Enable your own site by symlinking it from `sites-available` into `sites-enabled`:

```bash
sudo ln -s /etc/nginx/sites-available/example.com /etc/nginx/sites-enabled/
```

3. Check that your `server_name` matches the domain you are visiting:

```nginx
server {
    listen 80;
    server_name example.com www.example.com;
    root /var/www/example.com/public;
}
```

4. Test the config and reload. A reload is required for any change to take effect:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

## Common mistake

Editing the config but never reloading. Nginx keeps running the previously loaded config until you reload it. Every fix in this lesson ends with `sudo systemctl reload nginx` for that reason.

A trap worth knowing: `sites-available` and `sites-enabled` are a Debian and Ubuntu convention, not nginx itself. If you installed from the official nginx.org repo, `nginx.conf` usually includes `conf.d/*.conf` instead, and there is no `sites-enabled` in the include path at all. You can symlink sites forever and nothing changes, because nginx never reads that directory. Run `nginx -T` to see which files are actually loaded.

### The default_server catch

If two blocks listen on port 80 and neither is marked `default_server`, or the default one is, nginx uses the first matching or the default block for a request whose `Host` does not match any `server_name`. Making sure your `server_name` matches, and removing the default site, sends the request to the right place.

## FAQ

### I deleted the default file but still see the welcome page. Why?

You probably deleted it from `sites-available`, not `sites-enabled`. The active one is the symlink in `sites-enabled`. Remove that, then reload nginx.

### Do I edit sites-available or sites-enabled?

Edit the real file in `sites-available`. `sites-enabled` holds only symlinks that point back to it. Enabling a site means creating the symlink, disabling it means removing the symlink.

### My domain works but a subdomain shows the welcome page. Why?

The subdomain does not match any `server_name`, so it falls to the default block. Add the subdomain to a `server_name`, or point it at the right server block, then reload.
