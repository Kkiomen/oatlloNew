---
title: "Load balancing across backends"
slug: load-balancing
seo_title: "Nginx Load Balancing: round-robin, weight, least_conn"
seo_description: "Set up nginx load balancing across backends: round-robin, weight, least_conn and ip_hash, plus marking servers down or backup."
---

## How nginx spreads traffic across backends

Once an [upstream](/course/nginx-basics/reverse-proxy/upstream-blocks) has more than one server, nginx has to decide which one gets each request. That decision is **nginx load balancing**: spreading traffic so no single backend is overwhelmed, and so one crashed instance does not take the whole site down.

There is no switch to flip. List two servers in the upstream and nginx starts balancing on its own.

## Round-robin (the default)

With no method chosen, nginx uses round-robin: it sends the first request to server one, the next to server two, the next to server three, then back to one, and so on.

```nginx
upstream app_backend {
    server 127.0.0.1:3000;
    server 127.0.0.1:3001;
    server 127.0.0.1:3002;
}
```

Requests are handed out evenly, one after another. This is a fine default when your backends are equally powerful and requests take roughly the same time.

## Weights

If one machine is bigger, give it a larger share with `weight`:

```nginx
upstream app_backend {
    server 127.0.0.1:3000 weight=3;
    server 127.0.0.1:3001 weight=1;
}
```

Now roughly 3 out of every 4 requests go to the first server. The default weight is `1`. Use this when your servers are not equal.

## least_conn

Round-robin ignores how busy each server already is. If some requests are slow, one backend can pile up connections while another sits idle. `least_conn` sends each new request to the server with the fewest active connections:

```nginx
upstream app_backend {
    least_conn;
    server 127.0.0.1:3000;
    server 127.0.0.1:3001;
}
```

Reach for this when request times vary a lot.

## ip_hash

Sometimes you want the same visitor to keep hitting the same backend - for example if sessions are stored in each instance's memory. `ip_hash` picks the server based on the client's IP, so a given IP always lands on the same backend:

```nginx
upstream app_backend {
    ip_hash;
    server 127.0.0.1:3000;
    server 127.0.0.1:3001;
}
```

This is a simple form of "sticky sessions". The cleaner fix is to store sessions somewhere shared (a database or cache) so any backend can serve any user - but `ip_hash` works when you cannot change the app.

There is a catch that shows up the day you scale. Adding or removing a server changes the hash math, so most clients get remapped to a different backend and their in-memory sessions vanish at once. This is also why you take an `ip_hash` server out with the `down` flag instead of deleting its line: `down` keeps the slot, so everyone else stays put.

## Marking servers down or backup

You can flag individual servers:

```nginx
upstream app_backend {
    server 127.0.0.1:3000;
    server 127.0.0.1:3001 down;
    server 127.0.0.1:3002 backup;
}
```

- **`down`** - take this server out of rotation entirely. Handy while you deploy or restart it, without deleting the line.
- **`backup`** - only use this server when all the normal ones are unavailable. A standby that sits quiet until it is needed.

## Basic health checks

Open-source nginx has passive health checking built in. If a request to a backend fails, nginx marks it unavailable for a while and stops sending traffic there. You tune it per server:

```nginx
upstream app_backend {
    server 127.0.0.1:3000 max_fails=3 fail_timeout=30s;
    server 127.0.0.1:3001 max_fails=3 fail_timeout=30s;
}
```

This reads: if 3 requests fail within 30 seconds, consider the server down for 30 seconds, then try it again. It is "passive" because nginx only learns of failures from real traffic - it does not actively poll a health endpoint. That active checking exists only in the commercial nginx Plus, but passive checks are enough for most sites.

## Common mistake

Adding `ip_hash` and expecting even balancing. It is not even - it is sticky. Many visitors can share one IP (an office, a mobile carrier) and all land on the same backend, leaving others underused. Only use `ip_hash` when you actually need the same client on the same server, not as a general balancer.

Also, remember to reload after editing, as you learned in [start, stop and reload](/course/nginx-basics/getting-started/start-stop-reload):

```bash
nginx -t && nginx -s reload
```

## FAQ

### Which method should I start with?

Plain round-robin (the default). Move to `least_conn` if some requests are slow, and only use `ip_hash` if your app truly needs sticky sessions.

### Can I combine weight with least_conn?

Yes. `least_conn` plus `weight` on the servers works together - nginx factors the weight into its choice.

### What happens when a backend crashes?

With passive health checks, nginx notices failed requests, marks that server unavailable for `fail_timeout`, and routes around it to the healthy servers. When the timeout passes it tries the server again.
