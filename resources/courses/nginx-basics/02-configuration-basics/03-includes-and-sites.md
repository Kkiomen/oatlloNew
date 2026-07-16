---
title: "include, conf.d and sites-available"
slug: includes-and-sites
seo_title: "Nginx sites-available vs sites-enabled, include and conf.d"
seo_description: "Nginx sites-available vs sites-enabled explained, plus the include directive and conf.d folder for keeping each site in its own file."
---

In [Config file structure](/course/nginx-basics/configuration-basics/config-file-structure) you saw that `nginx.conf` ends with lines like `include /etc/nginx/conf.d/*.conf;`. This lesson explains that one directive and the two folder layouts built on top of it: the simple `conf.d` approach, and the nginx sites-available vs sites-enabled pattern used on Debian and Ubuntu. Both exist for the same reason, to keep each site in its own file instead of one giant config.

## The include directive

`include` tells nginx to read another file (or many files) as if its contents were pasted right there:

```nginx
http {
    include /etc/nginx/conf.d/*.conf;
}
```

The `*.conf` is a wildcard. It pulls in every file ending in `.conf` from that folder, in alphabetical order. Drop a new file into `conf.d/` and it becomes part of the config, with no editing of `nginx.conf` needed.

One detail trips people up: `include` splices text in, so the included file's contents must be valid **for the context where the include sits**. An include inside `http` should contain things allowed in `http`, such as `server` blocks. A file that would be fine on its own can still break the config if it lands in the wrong context.

## The conf.d approach

The simplest pattern is a single folder:

```bash
/etc/nginx/conf.d/
```

You create one file per site, for example `blog.conf` and `shop.conf`, each holding a `server` block. Because `nginx.conf` already includes `conf.d/*.conf`, every file is picked up automatically. This is the default on RHEL, CentOS, Fedora, and most Docker images.

## The sites-available and sites-enabled pattern

Debian and Ubuntu use a two-folder pattern instead:

```bash
/etc/nginx/sites-available/    # every site config you have written
/etc/nginx/sites-enabled/      # only the ones currently turned on
```

The main config includes only the enabled folder:

```nginx
include /etc/nginx/sites-enabled/*;
```

You write the real file in `sites-available/`. To switch a site **on**, you create a symbolic link (a symlink, a pointer) to it inside `sites-enabled/`:

```bash
sudo ln -s /etc/nginx/sites-available/blog /etc/nginx/sites-enabled/
```

To switch it **off**, you delete the link, not the real file:

```bash
sudo rm /etc/nginx/sites-enabled/blog
```

The real config stays safe in `sites-available/`, so turning a site off is reversible. The link in `sites-enabled/` is disposable. That split is the whole point: `sites-available` is your library of everything you have written, `sites-enabled` is what is actually live right now.

## conf.d or sites-available: which should you use?

Both work. Use whichever your system ships with:

- `conf.d/` only - simpler, one place to look. Common on RHEL family and containers.
- `sites-available` plus `sites-enabled` - lets you keep a config on the server without serving it. Common on Debian and Ubuntu.

You can even mix them, since `nginx.conf` can include both folders.

## Common mistake

Editing the file in `sites-enabled/` directly. On the symlink pattern that file is only a pointer, so it usually works, but people then copy it and forget which is the real one. Always edit the version in `sites-available/` and let the link point to it.

Another classic: creating the file in `sites-available/` and never linking it. Nginx never loads it, and you wonder why your site does not appear. Check that the symlink exists in `sites-enabled/`.

## FAQ

### What is a symlink in plain terms?

A symlink is a file that just points at another file. Opening the link opens the target. `sites-enabled/blog` is a link that points at `sites-available/blog`, so nginx reads the real config without a second copy existing.

### Does the order of included files matter?

Sometimes. Nginx reads them alphabetically. For separate `server` blocks it rarely matters, but if two files set the same global directive, the later one can win. Naming files `10-`, `20-` controls order when you need it.

### Do I have to reload nginx after adding an include?

Yes. Adding or linking a file changes nothing until nginx re-reads its config. Test and reload it as shown in [Testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely).
