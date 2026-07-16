---
title: "Password-protect with basic auth"
slug: basic-auth
seo_title: "nginx Basic Auth Setup With auth_basic and htpasswd"
seo_description: "Set up nginx basic auth to password-protect a directory. Create the htpasswd file and add auth_basic to a location, then serve it over HTTPS."
---

## When to use nginx basic auth

Sometimes you want a username and password prompt in front of an area without building a login system: a staging site, an internal tool, a `/admin` folder. nginx basic auth covers exactly that, with two directives and a small password file.

## Create the password file

nginx does not store passwords itself. You make a file with the `htpasswd` tool. On Debian or Ubuntu it comes with `apache2-utils`:

```bash
sudo apt install apache2-utils
sudo htpasswd -c /etc/nginx/.htpasswd alice
```

- `-c` **creates** the file. Use it only the first time, because it overwrites an existing file.
- It prompts for a password and stores it hashed, not in plain text.

To add more users later, run it **without** `-c`:

```bash
sudo htpasswd /etc/nginx/.htpasswd bob
```

## Turn on auth in nginx

Point a location at that file with two directives:

```nginx
location /admin {
    auth_basic           "Restricted area";
    auth_basic_user_file /etc/nginx/.htpasswd;

    # ... your normal handling
}
```

- `auth_basic` turns the prompt on. The text in quotes is the "realm", shown by some browsers in the login dialog.
- `auth_basic_user_file` is the path to the file you just made.

Test and reload:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Now visiting `/admin` pops a username and password box. Wrong or missing credentials get `401 Unauthorized`.

## Protecting a whole site

Move the two directives up into the `server {}` block to guard everything. To protect the site but leave one path open, set `auth_basic off;` in that inner location:

```nginx
server {
    auth_basic           "Staging";
    auth_basic_user_file /etc/nginx/.htpasswd;

    location /health {
        auth_basic off;   # let uptime checks through
    }
}
```

## Common mistake

Basic auth sends the password on every request, only lightly encoded, not encrypted. Over plain HTTP anyone on the network can read it. **Always serve it over HTTPS** so the connection is encrypted. You set that up in [why HTTPS](/course/nginx-basics/https-tls/why-https). Also do not run `htpasswd -c` a second time, or you wipe every existing user.

## FAQ

### Where should the .htpasswd file live?

Anywhere nginx can read, commonly `/etc/nginx/.htpasswd`. Keep it outside your web root so it is never served as a file.

### Can I combine this with IP restrictions?

Yes. Add `allow`/`deny` from the [previous lesson](/course/nginx-basics/security-basics/access-control) in the same location for two layers: right network **and** right password. By default nginx demands both pass (as if `satisfy all;` were set). If you instead want either one to be enough, say a trusted office IP that skips the prompt while everyone else still needs the password, add `satisfy any;` to the location.

### Is basic auth secure enough for real users?

For a real user-facing login, no. Use a proper app login. Basic auth is best for internal tools, staging, and quick gates behind HTTPS.
