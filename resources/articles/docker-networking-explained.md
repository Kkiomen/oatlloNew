---
name: "Docker Networking Explained"
slug: docker-networking-explained
short_description: "Why containers can't reach each other, when DNS by service name works, published vs exposed ports, and how to talk to services on the host."
language: en
published_at: 2027-03-26 09:00:00
is_published: true
tags: [docker, devops, networking, tooling]
---

I spent an embarrassing forty minutes once staring at a `Connection refused` error, convinced my database was down. Postgres was up. The container was healthy. My app just couldn't find it — because I had run the two containers with `docker run` on the default network, where name resolution doesn't exist. The moment I learned why that network is different from the one Compose builds for you, half of my Docker confusion evaporated.

Networking is where Docker stops being "just run the container" and starts having opinions. Most of those opinions are reasonable once you know the rules. This is the set of rules I wish someone had drawn on a whiteboard for me: which network you're on, whether DNS works, and what a published port actually does.

## The default bridge is not the network you think it is

When you install Docker, you get a network called `bridge`. Every container you start with plain `docker run` — no `--network` flag — lands there. It works: containers get an IP, they can reach the internet, you can talk to them from the host through published ports.

What it does *not* give you is DNS between containers. On the default bridge, one container cannot resolve another by name. You'd have to dig out the raw IP address, which changes every time the container restarts. This is the single fact that trips up almost everyone starting out.

```bash
docker run -d --name db postgres:16
docker run -d --name app alpine sleep 3600

# Try to resolve "db" from inside "app"
docker exec app ping db
# ping: bad address 'db'
```

That `bad address` is Docker telling you the default bridge has no service discovery. There's an ancient `--link` flag that papered over this, but it's legacy and you should never reach for it.

## User-defined bridges give you DNS for free

Create your own bridge network and everything changes. Docker runs an embedded DNS server (at `127.0.0.11` inside each container) that resolves container names to their current IPs automatically. Names, not addresses. Restart-proof.

```bash
docker network create appnet

docker run -d --name db --network appnet postgres:16
docker run -d --name app --network appnet alpine sleep 3600

docker exec app ping db
# PING db (172.18.0.2): 56 data bytes  ← it resolves
```

Same two containers, one difference — a user-defined network instead of the default — and now `db` is a hostname. This is why the official guidance is to always create a network rather than lean on the default bridge. You get isolation (only containers on `appnet` can see each other) and you get names.

Here's the part that matters for debugging: **Compose does this for you.** When you write a `docker-compose.yml`, Compose creates a user-defined bridge network for the project and attaches every service to it. That's why service names "just work" in Compose but not in raw `docker run`. It was never Compose magic — it was a user-defined network the whole time.

## host and none: the two networks people forget exist

Bridge is the default, but Docker ships two more network drivers worth knowing.

**`--network host`** removes the isolation entirely. The container shares the host's network stack directly — no NAT, no published ports, no separate IP. A process listening on port 8080 inside the container is listening on the host's 8080. This is faster (you skip the NAT hop) and occasionally necessary for things that need raw access to the host interface, like some monitoring agents. The catch: it only works properly on Linux. On Docker Desktop for Mac and Windows the containers run inside a VM, so `host` mode doesn't behave the way the docs describe. And port conflicts become your problem — two containers both wanting 8080 will collide.

**`--network none`** gives the container no networking at all, just a loopback interface. Useful for batch jobs that crunch data and should have zero network access, either for security or to prove a process genuinely works offline.

```bash
docker run --rm --network none alpine ip addr
# only "lo" — no eth0, no route to anywhere
```

## Talking between services in Compose

In practice you'll spend most of your time in Compose, so let's make the service-name rule concrete. Every service can reach every other service by its **service name** as the hostname, on the container's *internal* port.

```yaml
services:
  app:
    build: .
    ports:
      - "8080:80"
    environment:
      DB_HOST: db          # the service name, not localhost
      DB_PORT: 5432
    depends_on:
      - db

  db:
    image: postgres:16
    environment:
      POSTGRES_PASSWORD: secret
```

Inside the `app` container, the database lives at `db:5432`. Not `localhost:5432` — that's the mistake I've seen a dozen times. `localhost` inside a container means *that container*, so `localhost:5432` looks for Postgres running in the app container itself, where it isn't. The hostname is the service name.

One subtlety worth internalizing: `depends_on` controls *start order*, not *readiness*. Compose will start `db` before `app`, but it won't wait for Postgres to finish booting and accept connections. Your app can win the race and try to connect before the database is listening. Handle that with a retry loop in your app or a proper healthcheck — don't assume `depends_on` means "ready."

## Published vs exposed ports — they are not the same thing

This distinction causes real bugs, so slow down here.

**Exposing** a port (`EXPOSE 5432` in a Dockerfile, or `expose:` in Compose) is documentation plus intra-network reachability. It says "this container listens here" and makes the port reachable *from other containers on the same network*. It does **not** open the port to your host machine.

**Publishing** a port (`-p 8080:80` or `ports:` in Compose) creates a mapping from a host port to a container port. `8080:80` means "traffic hitting the host's 8080 gets forwarded to port 80 in the container." This is what lets you open `localhost:8080` in your browser.

| | Reachable from other containers | Reachable from host / outside |
|---|---|---|
| `expose:` only | Yes | No |
| `ports:` (publish) | Yes | Yes |
| neither | Yes, if on same user-defined network | No |

The trap: you do **not** need to publish a port for containers to talk to each other. Container-to-container traffic goes over the internal network on the container's own port. So your database usually needs *no* published port at all — the app reaches it on `db:5432` internally. Publishing `5432:5432` just exposes your database to the whole host (and potentially the network), which is a security smell in most setups. Only publish what a human or an external client actually needs to hit.

## Reaching a service running on the host

Sooner or later you'll want a container to talk to something running *on your host machine* — a database you're running natively, an API on `localhost:3000`, whatever. Inside the container, `localhost` is the container, so that won't work.

The answer is a special DNS name Docker provides: **`host.docker.internal`**. It resolves to the host's IP from inside the container.

```bash
# From inside a container, reach a Postgres running natively on the host:
psql -h host.docker.internal -p 5432 -U postgres
```

On Docker Desktop (Mac/Windows) this works out of the box. On Linux it isn't automatic — you add it explicitly:

```yaml
services:
  app:
    build: .
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

`host-gateway` is a magic value Docker translates to the host's gateway IP. Add that `extra_hosts` line and `host.docker.internal` resolves on Linux too.

## A quick word on overlay networks

Everything above is single-host. The moment you spread containers across *multiple* machines — Docker Swarm, or a cluster — bridge networks don't span hosts. That's what the **overlay** driver is for: it creates a virtual network that sits on top of the physical network so containers on different machines can talk by service name as if they were local. You rarely create these by hand; Swarm and orchestrators manage them. If you're on a single box, you'll never need overlay — but it's good to know the word so multi-host networking doesn't feel like a different universe. (In Kubernetes this job is handled by the CNI plugin instead, which is a whole separate topic.)

## The debugging story: "why can't my app reach the database?"

Here's the one that actually cost me an afternoon, because the symptom pointed the wrong way.

A colleague's Laravel app was throwing `SQLSTATE[HY000] [2002] Connection refused` on every request. The `.env` said `DB_HOST=127.0.0.1`. Postgres was clearly running — we could connect to it from our laptop with a GUI client on port 5432. So the database was up and reachable. The app just refused to talk to it.

The mistake was `DB_HOST=127.0.0.1`. That `.env` had been written for running the app *natively*, where `127.0.0.1` correctly means "the database on this same machine." But now the app ran inside a container. Inside the app container, `127.0.0.1` means the app container itself — where nothing listens on 5432. The GUI client worked because it ran on the host and hit the *published* port. The app failed because it was looking in the wrong place entirely.

The fix was one line:

```bash
# .env — wrong, points at the container itself
DB_HOST=127.0.0.1

# right, points at the db service by name
DB_HOST=db
```

How I'd diagnose this from scratch, in order:

```bash
# 1. Are both containers on the same network?
docker network inspect <project>_default --format '{{range .Containers}}{{.Name}} {{end}}'

# 2. Can the app resolve the db service name at all?
docker compose exec app getent hosts db
# no output = DNS failure (wrong network / typo'd service name)

# 3. Can the app actually reach the port?
docker compose exec app nc -zv db 5432
# "open" = network is fine, look at credentials/db readiness instead
```

That three-step check — same network, name resolves, port open — settles nearly every "can't reach the container" problem. If step 2 fails you're on the wrong network or the service name is misspelled. If step 2 passes but step 3 fails, the database probably isn't listening yet (readiness race) or is bound to the wrong interface. If both pass, stop blaming the network and look at credentials.

## FAQ

**Why does `localhost` work in Compose sometimes but not others?**
It "works" only when the thing you're reaching is inside the *same* container. Between services you must use the service name as the hostname. `localhost` inside a container always means that container, never a sibling.

**Do I need to publish my database port for my app to connect to it?**
No. Container-to-container traffic uses the internal network on the container's own port, so your app reaches the database on `db:5432` with no `ports:` mapping at all. Publish it only if a client outside Docker (a GUI tool, a native process) needs to connect.

**Why does `host.docker.internal` not resolve on my Linux box?**
Because Docker only wires it up automatically on Docker Desktop. On Linux, add `extra_hosts: ["host.docker.internal:host-gateway"]` to the service, and it will resolve to the host's gateway.

**Can containers on two different `docker network create` networks talk to each other?**
Not by default — that's the isolation working as intended. A container can be attached to multiple networks (`docker network connect`), which is how you deliberately bridge two otherwise-separate groups of services.

## Wrapping up

Almost every Docker networking headache reduces to three questions: are these containers on the same user-defined network, does the name resolve, and is the port actually open? Get in the habit of running that check — `getent hosts`, then `nc -zv` — before you touch a single config file. The next time an app can't reach its database, you'll spend two minutes confirming where it's actually looking instead of forty minutes assuming the database is down.
