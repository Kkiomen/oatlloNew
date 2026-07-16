---
title: "Hide the nginx version"
slug: hide-version
seo_title: "Hide the nginx Version With server_tokens off"
seo_description: "Hide the nginx version with server_tokens off so responses and error pages stop leaking it. Where to set the directive and why it matters."
---

## Why hide the nginx version

To hide the nginx version you turn off one directive, but it helps to know what you are hiding first. By default nginx tells the world exactly which build it is running. It shows up in the `Server` response header and on its built-in error pages:

```bash
curl -I http://example.com
```

```text
HTTP/1.1 200 OK
Server: nginx/1.24.0
```

That version number is a gift to anyone scanning for targets. If a security bug is announced for `nginx/1.24.0`, an attacker can search for servers advertising exactly that and try the exploit first. Hiding it does not fix any bug, but it removes an easy filter and makes casual scanning less useful. It is a small, free win.

## Turn it off

The directive is `server_tokens`. Set it to `off` in the `http` context so it applies to every server block at once:

```nginx
http {
    server_tokens off;

    # ... your other http settings, includes, etc.
}
```

- `http` context means the setting is inherited by all `server` and `location` blocks below it. You saw contexts back in [directives and contexts](/course/nginx-basics/configuration-basics/directives-and-contexts).
- You can also set it inside a single `server {}` or `location {}` if you only want it there, but `http` is the sensible default.
- Inheritance runs one way: a child block that sets `server_tokens on;` overrides the parent and puts the version back for that block only. If one site still leaks after you set it globally, look for a stray `on` further down.

Test and reload after any change:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Now the header just says the software name, without the number:

```text
Server: nginx
```

## What it does and does not do

- It removes the version from the `Server` header and from nginx error pages (404, 500, and so on).
- It does **not** remove the `Server: nginx` word entirely. Doing that needs a third-party module or a build change, which is out of scope here and rarely worth it.
- It does not touch headers your app sends, like `X-Powered-By: PHP`. If your PHP or Laravel app leaks its own version, fix that in the app.

## Common mistake

Setting `server_tokens off;` but forgetting to reload nginx. The config file is only read on start or reload, so `curl -I` still shows the old header until you run `nginx -t` and `systemctl reload nginx`. Always test then reload.

## FAQ

### Does this improve real security?

Only a little. It is defense in depth, not a fix. Keep nginx updated too, since that is what actually closes known bugs.

### Where should I put it?

In the `http` block of your main config (often `/etc/nginx/nginx.conf`) so every site inherits it.

### Can I fully hide that it is nginx?

Not with stock nginx. `server_tokens off;` is as far as the standard build goes. Fully replacing the `Server` header needs extra modules and is usually not worth the effort.

### What values does server_tokens accept?

Three: `on`, `off`, and `build`. `on` is the leaky default, `off` is what you want, and `build` adds the build name on top of the version, so it leaks more, not less. Stick with `off`.
