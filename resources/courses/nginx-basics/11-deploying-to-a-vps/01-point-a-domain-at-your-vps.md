---
title: "Point a domain at your VPS"
slug: point-a-domain-at-your-vps
seo_title: "Point a Domain at Your VPS: DNS A Record + Nginx"
seo_description: "Point a domain to your VPS the right way: add A and AAAA records for @ and www, wait for DNS propagation, verify with dig, and match it in nginx server_name."
---

## The problem

To point a domain at your VPS you need two systems, DNS and nginx, agreeing on the same name. So far you have tested everything with a fake hostname like `myapp.test`, or by typing the server's raw IP address into the browser. That works for you and nobody else. It also does not look like a real site.

Going live means two halves meet in the middle:

1. **DNS** tells the internet "the name `yourdomain.com` lives at this IP address".
2. **Nginx** decides which site answers when a request arrives for that name, using `server_name` (from [listen and server_name](/course/nginx-basics/serving-static-content/listen-and-server-name)).

Get both right and typing `yourdomain.com` lands on your server, and your server hands back the correct site. This lesson wires up both halves.

## What is a DNS A record?

Your domain lives at a registrar or DNS provider (Namecheap, Cloudflare, OVH, and so on). There you edit **DNS records**. The one that matters most is the **A record**.

An A record maps a hostname to an **IPv4 address**:

```text
Type   Name   Value             TTL
A      @      203.0.113.10      3600
```

- **Type** is `A`.
- **Name** is `@`, which means the bare domain itself (`yourdomain.com`).
- **Value** is your server's **public IPv4 address**. Your VPS provider shows this in its dashboard, or you can run `curl -4 ifconfig.me` on the server itself.
- **TTL** (time to live) is how many seconds resolvers may cache the answer. More on that below.

If your server also has an **IPv6 address**, add an **AAAA record** too. It is the same idea, but for IPv6:

```text
Type   Name   Value                     TTL
AAAA   @      2001:db8::10              3600
```

AAAA is optional. If you do not have or do not want IPv6, skip it. But if you add an AAAA record, make sure it points at a working address, because some visitors will use it. A stale AAAA left over from an old host is a nasty one to debug: IPv6 clients try it first, fail silently, and the site looks down for half your audience while `dig +short yourdomain.com` (IPv4) looks perfect.

## Add @ and www

Most people want the site to answer on both `yourdomain.com` and `www.yourdomain.com`. That is two names, so you add a record for each.

The simplest, most reliable setup is two A records pointing at the same IP:

```text
Type   Name   Value             TTL
A      @      203.0.113.10      3600
A      www    203.0.113.10      3600
```

`@` covers the bare domain and `www` covers the `www.` version. Both now resolve to your server. (You can instead make `www` a `CNAME` pointing at the bare domain, but two A records is easier to reason about when you are starting out.)

Save the records in your provider's panel. That is the DNS half done.

## TTL and DNS propagation: why changes are not instant

DNS answers are cached all over the internet to keep it fast. When you add or change a record, the new value is not instant everywhere. This delay is called **propagation**.

- **TTL** is the cache lifetime in seconds. `3600` means one hour. A resolver that already cached the old answer may keep using it until the TTL expires.
- A brand-new record (there was nothing before) usually shows up within minutes, because there was no old value to cache.
- A **changed** record can take up to the old TTL to clear everywhere. If you plan to change a record soon, lower its TTL first (say to `300`), wait, then make the change.

The short version: if a fresh record does not resolve right away, wait a bit before assuming something is broken. One practical tip before a planned migration: drop the TTL to `300` a day ahead. The lower TTL itself has to propagate first, so setting it at the last minute buys you nothing.

## Check DNS propagation with dig

Do not guess whether DNS is ready. Ask a resolver directly from your own machine.

```bash
dig +short yourdomain.com
```

`+short` trims the output to just the answer. When the record is live you'll see your server's IP:

```text
203.0.113.10
```

Check `www` too:

```bash
dig +short www.yourdomain.com
```

If `dig` is not installed (it comes from `dnsutils`), or you are on Windows, `nslookup` does the same job:

```bash
nslookup yourdomain.com
```

You are looking for one thing: the IP in the answer matches your VPS's public IP. If `dig` prints nothing, the record has not propagated yet (or you typed the name wrong). Wait and try again.

## Match the domain in nginx server_name

DNS gets the request to your server. Now nginx has to claim the name. Open the server block for your site (you set these up in [includes and sites](/course/nginx-basics/configuration-basics/includes-and-sites)) and set `server_name` to both names:

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/myapp/public;
    # ... the rest of your config
}
```

`server_name` lists every name this block should answer to, separated by spaces. Here it claims both the bare domain and the `www` version, which is exactly the two names you set up in DNS.

Test and reload, the same safe two-step you learned in [testing config safely](/course/nginx-basics/configuration-basics/testing-config-safely):

```bash
sudo nginx -t && sudo systemctl reload nginx
```

## Test the whole path

Now check both halves together. From your own machine:

```bash
curl -I http://yourdomain.com
```

`-I` fetches just the response headers. A `200 OK` (or a redirect you set up on purpose) means the request travelled all the way: DNS pointed at your server, and nginx matched the name and answered. Open the domain in a browser for the final confirmation.

## Common mistake: DNS has not propagated yet

The classic "it does not work" is really "it does not work **yet**". You added the record, typed the domain, got nothing, and assumed the config is broken.

Always confirm DNS first:

```bash
dig +short yourdomain.com
```

No answer, or the wrong IP, means the problem is DNS, not nginx. There is nothing to fix in your config. Wait for propagation and re-check.

## Common mistake: server_name does not match

DNS is correct, the request reaches your server, but you see the wrong page (often nginx's default "Welcome to nginx" page). That means no server block's `server_name` matched the incoming name, so nginx fell back to the **default** site.

This happens when:

- You put `yourdomain.com` in DNS but left `server_name myapp.test;` in the config.
- You added the domain but forgot `www`, so `www.yourdomain.com` matches nothing.

Fix it by listing the exact names in `server_name`, then reload. When the name in the request matches the name in the block, your site answers instead of the default. (Chapter 3's [listen and server_name](/course/nginx-basics/serving-static-content/listen-and-server-name) covers exactly how nginx picks a block.)

## FAQ

### How long does DNS really take?

A new record is usually usable within a few minutes and almost always within an hour. Changes to an existing record can take up to its old TTL. If it has been well over an hour and `dig` still shows nothing, re-check that you saved the record at the right provider and used the correct IP.

### Do I need both A and AAAA records?

No. A single A record (IPv4) is enough to be reachable by everyone. Add an AAAA record only if your server has a real IPv6 address and you want IPv6 visitors to use it. A broken AAAA record is worse than none, because IPv6 clients will try it first.

### Should I use @ and www, or just one?

Set up both names in DNS and in `server_name` so neither version is a dead end. Then, once HTTPS is on, it is common to pick one as canonical and redirect the other to it (see [redirect http to https](/course/nginx-basics/https-tls/redirect-http-to-https) for the redirect pattern).

### dig works but the browser still fails. Why?

Your browser or operating system may be caching an older lookup, or you are behind a VPN or corporate resolver. Confirm with `dig +short` from the command line first. If `dig` is correct, flush your local DNS cache or try a different network before touching the server config.
